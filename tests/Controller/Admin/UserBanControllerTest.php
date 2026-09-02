<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatus;
use App\Repository\UserLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserBanControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
        $this->createSettings();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();

        parent::tearDown();
    }

    public function testAdministratorCanBanAnotherUserFromTheAdmin(): void
    {
        $administrator = $this->createUser('ban-admin@example.com', ['ROLE_ADMIN']);
        $target = $this->createUser('ban-target@example.com');
        $targetId = $target->getId();
        $this->client->loginUser($administrator);

        $crawler = $this->client->request('GET', '/admin/user');

        self::assertResponseIsSuccessful();
        $targetRowSelector = sprintf('tr[data-id="%d"]', $targetId);
        $banAction = $crawler->filter($targetRowSelector.' .action-ban');
        self::assertCount(1, $banAction);

        $formId = $banAction->attr('data-ea-action-form-id');
        self::assertNotNull($formId);
        $form = $crawler->filter('form#'.$formId)->form();
        $invalidUrl = preg_replace('/([?&]_token=)[^&]+/', '$1invalid', $form->getUri());

        self::assertNotNull($invalidUrl);
        self::assertNotSame($form->getUri(), $invalidUrl);

        $this->client->request('POST', $invalidUrl);
        self::assertResponseStatusCodeSame(403);

        $target = $this->entityManager->find(User::class, $targetId);
        self::assertInstanceOf(User::class, $target);
        self::assertSame(UserStatus::APPROVED, $target->getUserStatus());

        $crawler = $this->client->request('GET', '/admin/user');
        $formId = $crawler->filter($targetRowSelector.' .action-ban')->attr('data-ea-action-form-id');
        self::assertNotNull($formId);
        $this->client->submit($crawler->filter('form#'.$formId)->form());

        self::assertResponseRedirects();

        $target = $this->entityManager->find(User::class, $targetId);
        self::assertInstanceOf(User::class, $target);
        self::assertSame(UserStatus::SUSPENDED, $target->getUserStatus());

        /** @var UserLogEntryRepository $historyRepository */
        $historyRepository = self::getContainer()->get(UserLogEntryRepository::class);
        $history = $historyRepository->findForUser($target);
        self::assertSame('suspended', $history[0]->getData()['userStatus']);

        $crawler = $this->client->followRedirect();
        self::assertCount(0, $crawler->filter($targetRowSelector.' .action-ban'));
    }

    public function testBanButtonIsNotDisplayedForTheCurrentAdministrator(): void
    {
        $administrator = $this->createUser('self-ban-admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);

        $crawler = $this->client->request('GET', '/admin/user');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter(sprintf(
            'tr[data-id="%d"] .action-ban',
            $administrator->getId(),
        )));
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles = []): User
    {
        $user = new User()
            ->setEmail($email)
            ->setRoles($roles)
            ->setPassword('test-password-hash')
            ->setName('Ban')
            ->setLastname('Tester')
            ->setPhone('0123456789')
            ->setCompany(null)
            ->setNationalitie('French')
            ->setResidenceCity('Paris')
            ->setBirthDate(new \DateTimeImmutable('1990-01-01'))
            ->setPracticeStartYear(2000)
            ->setFilledInfo(true)
            ->setIsVerified(true)
            ->setUserStatus(UserStatus::APPROVED)
            ->setLanguage('fr');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createSettings(): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
            INSERT INTO settings (
                is_room_booking_enabled,
                is_user_validation_required,
                min_day_booking,
                min_day_room_booking,
                is_pending_booking_blocking,
                hour_check_in_room,
                hour_check_out,
                is_pending_room_blocking
            ) VALUES (TRUE, TRUE, 0, 1, FALSE, 15, 10, FALSE)
            SQL);
    }
}
