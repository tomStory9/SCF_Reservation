<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TermsConsentControllerTest extends WebTestCase
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

    public function testTermsPageIsPubliclyAccessible(): void
    {
        $this->client->request('GET', '/cgu');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
    }

    #[DataProvider('bookingCreationUrls')]
    public function testBookingCreationRequiresTermsConsent(string $url): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->jsonRequest('POST', $url, [
            'termsAccepted' => false,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $payload = json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertFalse($payload['success']);
        self::assertNotEmpty($payload['error']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function bookingCreationUrls(): iterable
    {
        yield 'training booking' => ['/booking/create'];
        yield 'room booking' => ['/room/booking/create'];
    }

    private function createUser(): User
    {
        $user = new User()
            ->setEmail(uniqid('terms-', true).'@example.com')
            ->setRoles([])
            ->setPassword('test-password-hash')
            ->setName('Terms')
            ->setLastname('Tester')
            ->setPhone('0123456789')
            ->setCompany(null)
            ->setFilledInfo(true)
            ->setIsVerified(true)
            ->setUserStatus(UserStatus::APPROVED)
            ->setLanguage('fr');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
