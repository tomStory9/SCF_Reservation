<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\UserLogEntry;
use App\Enum\UserStatus;
use App\Repository\UserLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserLogEntryRepositoryTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
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

    public function testItRecordsUserChangesWithoutStoringThePassword(): void
    {
        $user = $this->createUser();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = self::getContainer()->get(TokenStorageInterface::class);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $user
            ->setCompany('SCF')
            ->setIsVerified(true);
        $this->entityManager->flush();

        /** @var UserLogEntryRepository $repository */
        $repository = self::getContainer()->get(UserLogEntryRepository::class);
        $history = $repository->findForUser($user);

        self::assertCount(2, $history);
        self::assertSame(UserLogEntry::ACTION_UPDATE, $history[0]->getAction());
        self::assertSame([
            'company' => 'SCF',
            'isVerified' => true,
        ], $history[0]->getData());
        self::assertSame('history@example.com', $history[0]->getUsername());
        self::assertSame(UserLogEntry::ACTION_CREATE, $history[1]->getAction());

        foreach ($history as $entry) {
            self::assertArrayNotHasKey('password', $entry->getData() ?? []);
        }
    }

    public function testAnAdministratorCanSeeTheUserHistoryPage(): void
    {
        $this->createSettings();
        $user = $this->createUser();
        $user->setCompany('SCF');
        $this->entityManager->flush();

        $this->client->loginUser($user);

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->client->request('GET', $urlGenerator->generate('admin_user_index'));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a.action-history');

        $this->client->request('GET', $urlGenerator->generate('admin_user_edit', [
            'entityId' => $user->getId(),
        ]));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a.action-history');

        $url = $urlGenerator->generate('admin_user_history', [
            'entityId' => $user->getId(),
        ]);

        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('title', 'History for History Test');
        self::assertSelectorTextContains('body', 'Updated');
        self::assertSelectorTextContains('body', 'SCF');
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

    private function createUser(): User
    {
        $user = new User()
            ->setEmail('history@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword('a-password-hash-that-must-not-be-logged')
            ->setName('History')
            ->setLastname('Test')
            ->setPhone('0123456789')
            ->setCompany(null)
            ->setFilledInfo(true)
            ->setIsVerified(false)
            ->setUserStatus(UserStatus::APPROVED)
            ->setLanguage('fr');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
