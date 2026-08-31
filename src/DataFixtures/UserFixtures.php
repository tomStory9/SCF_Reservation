<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const string ADMIN = 'admin';
    public const string CA_USER = 'ca_user';
    public const string AA_USER = 'aa_user';
    public const string FA_USER = 'fa_user';
    public const string TM_USER = 'tm_user';
    public const string DEFAULT_USER = 'default_user';

    public const string DEFAULT_PASSWORD = 'test';
    public const string ADMIN_PASSWORD = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setName('SCF');
        $admin->setLastname('ADMIN');
        $admin->setEmail('admin@test.test');
        $admin->setPhone('');
        $admin->setFilledInfo(true);
        $admin->setIsVerified(true);
        $admin->setUserStatus(UserStatus::APPROVED);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, self::ADMIN_PASSWORD)
        );
        $admin->setLanguage('ja');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setNationalitie('Japonaise');
        $admin->setResidenceCity('Takamatsu');
        $admin->setBirthDate(new \DateTimeImmutable('1980-01-01'));
        $admin->setPracticeStartYear(2010);
        $admin->setLastPerformance('SCF Opening');
        $admin->setInstagramAccount('@scf_japan');
        $manager->persist($admin);
        $this->addReference(self::ADMIN, $admin);

        $ca_user = new User();
        $ca_user->setName('Loëvann');
        $ca_user->setLastname('Guegan');
        $ca_user->setEmail('lguegan@test.test');
        $ca_user->setPhone('');
        $ca_user->setFilledInfo(true);
        $ca_user->setIsVerified(true);
        $ca_user->setUserStatus(UserStatus::APPROVED);
        $ca_user->setPassword(
            $this->passwordHasher->hashPassword($ca_user, self::DEFAULT_PASSWORD)
        );
        $ca_user->setRoles(['ROLE_CA_USER']);
        $ca_user->setLanguage('fr');
        $ca_user->setNationalitie('Française');
        $ca_user->setResidenceCity('Reims');
        $ca_user->setBirthDate(new \DateTimeImmutable('2000-05-15'));
        $ca_user->setPracticeStartYear(2021);
        $ca_user->setLastPerformance('Spectacle de fin d\'année');
        $ca_user->setInstagramAccount('@loevann');
        $manager->persist($ca_user);
        $this->addReference(self::CA_USER, $ca_user);

        $aa_user = new User();
        $aa_user->setName('Tom');
        $aa_user->setLastname('Raineri');
        $aa_user->setEmail('traineri@test.test');
        $aa_user->setPhone('');
        $aa_user->setFilledInfo(true);
        $aa_user->setIsVerified(true);
        $aa_user->setUserStatus(UserStatus::APPROVED);
        $aa_user->setPassword(
            $this->passwordHasher->hashPassword($aa_user, self::DEFAULT_PASSWORD)
        );
        $aa_user->setRoles(['ROLE_AA_USER']);
        $aa_user->setLanguage('fr');
        $aa_user->setNationalitie('Française');
        $aa_user->setResidenceCity('Paris');
        $aa_user->setBirthDate(new \DateTimeImmutable('1995-10-20'));
        $aa_user->setPracticeStartYear(2018);
        $aa_user->setLastPerformance(null);
        $aa_user->setInstagramAccount(null);
        $manager->persist($aa_user);
        $this->addReference(self::AA_USER, $aa_user);

        $fa_user = new User();
        $fa_user->setName('Simon');
        $fa_user->setLastname('Ledoux');
        $fa_user->setEmail('sledoux@test.test');
        $fa_user->setPhone('');
        $fa_user->setFilledInfo(true);
        $fa_user->setIsVerified(true);
        $fa_user->setUserStatus(UserStatus::APPROVED);
        $fa_user->setPassword(
            $this->passwordHasher->hashPassword($fa_user, self::DEFAULT_PASSWORD)
        );
        $fa_user->setRoles(['ROLE_FA_USER']);
        $fa_user->setLanguage('fr');
        $fa_user->setNationalitie('Française');
        $fa_user->setResidenceCity('Takamatsu');
        $fa_user->setBirthDate(new \DateTimeImmutable('1992-08-08'));
        $fa_user->setPracticeStartYear(2015);
        $fa_user->setLastPerformance('Open Laboratory');
        $fa_user->setInstagramAccount('@simonledoux');
        $manager->persist($fa_user);
        $this->addReference(self::FA_USER, $fa_user);

        $tm_user = new User();
        $tm_user->setName('Cacary');
        $tm_user->setLastname('Riendenin');
        $tm_user->setEmail('criendenin@test.test');
        $tm_user->setPhone('');
        $tm_user->setFilledInfo(true);
        $tm_user->setIsVerified(true);
        $tm_user->setUserStatus(UserStatus::APPROVED);
        $tm_user->setPassword(
            $this->passwordHasher->hashPassword($tm_user, self::DEFAULT_PASSWORD)
        );
        $tm_user->setRoles(['ROLE_TM_USER']);
        $tm_user->setLanguage('fr');
        $tm_user->setNationalitie('Française');
        $tm_user->setResidenceCity('Lyon');
        $tm_user->setBirthDate(new \DateTimeImmutable('1990-12-12'));
        $tm_user->setPracticeStartYear(2010);
        $tm_user->setLastPerformance(null);
        $tm_user->setInstagramAccount(null);
        $manager->persist($tm_user);
        $this->addReference(self::TM_USER, $tm_user);

        $default_user = new User();
        $default_user->setName('Grobin');
        $default_user->setLastname('Ciboulette');
        $default_user->setEmail('gciboulette@test.test');
        $default_user->setPhone('');
        $default_user->setFilledInfo(true);
        $default_user->setIsVerified(true);
        $default_user->setUserStatus(UserStatus::APPROVED);
        $default_user->setPassword(
            $this->passwordHasher->hashPassword($default_user, self::DEFAULT_PASSWORD)
        );
        $default_user->setRoles(['ROLE_DEFAULT_USER']);
        $default_user->setLanguage('fr');
        $default_user->setNationalitie('Française');
        $default_user->setResidenceCity('Marseille');
        $default_user->setBirthDate(new \DateTimeImmutable('1985-03-30'));
        $default_user->setPracticeStartYear(2005);
        $default_user->setLastPerformance(null);
        $default_user->setInstagramAccount(null);
        $manager->persist($default_user);
        $this->addReference(self::DEFAULT_USER, $default_user);

        $manager->flush();
    }
}
