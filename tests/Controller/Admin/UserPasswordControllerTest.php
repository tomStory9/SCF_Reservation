<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Entity\UserRole;
use App\Enum\UserStatus;
use App\Repository\UserLogEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserPasswordControllerTest extends WebTestCase
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
        $this->createUserRoles();
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

    public function testAdministratorCanChangeAUserPassword(): void
    {
        $administrator = $this->createUser('password-admin@example.com', ['ROLE_ADMIN']);
        $target = $this->createUser('password-target@example.com', ['ROLE_DEFAULT_USER']);
        $targetId = $target->getId();

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $target->setPassword($passwordHasher->hashPassword($target, 'Original1!'));
        $this->entityManager->flush();

        $this->client->loginUser($administrator);

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $editUrl = $urlGenerator->generate('admin_user_edit', ['entityId' => $targetId]);

        $crawler = $this->client->request('GET', $editUrl);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="User[newPassword]"][type="password"]');

        $form = $crawler->filter('form[name="User"]')->form();
        $form['User[newPassword]'] = 'NewPassword1!';
        $this->client->submit($form);

        self::assertResponseRedirects();

        $target = $this->entityManager->find(User::class, $targetId);
        self::assertInstanceOf(User::class, $target);
        self::assertTrue($passwordHasher->isPasswordValid($target, 'NewPassword1!'));
        self::assertFalse($passwordHasher->isPasswordValid($target, 'Original1!'));

        $savedPassword = $target->getPassword();
        $crawler = $this->client->request('GET', $editUrl);
        $this->client->submit($crawler->filter('form[name="User"]')->form());

        self::assertResponseRedirects();

        $target = $this->entityManager->find(User::class, $targetId);
        self::assertInstanceOf(User::class, $target);
        self::assertSame($savedPassword, $target->getPassword());

        /** @var UserLogEntryRepository $historyRepository */
        $historyRepository = self::getContainer()->get(UserLogEntryRepository::class);
        foreach ($historyRepository->findForUser($target) as $historyEntry) {
            self::assertArrayNotHasKey('password', $historyEntry->getData() ?? []);
            self::assertArrayNotHasKey('newPassword', $historyEntry->getData() ?? []);
        }
    }

    private function createUser(string $email, array $roles): User
    {
        $user = new User()
            ->setEmail($email)
            ->setRoles($roles)
            ->setPassword('test-password-hash')
            ->setName('Password')
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

    private function createUserRoles(): void
    {
        foreach ([
            ['ROLE_ADMIN', 'Administrator', 'FREE'],
            ['ROLE_DEFAULT_USER', 'Default User', 'FULL'],
        ] as [$roleName, $label, $tarif]) {
            $role = new UserRole()
                ->setRoleName($roleName)
                ->setLabel($label)
                ->setAllocatedHoursPerMonth(0)
                ->setMaxAdvanceBookingDays(30)
                ->setTarif($tarif);

            $this->entityManager->persist($role);
        }

        $this->entityManager->flush();
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
