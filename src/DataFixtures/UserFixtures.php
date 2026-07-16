<?php

namespace App\DataFixtures;

use App\Entity\User;
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
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, self::DEFAULT_PASSWORD)
        );
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);
        $this->addReference(self::ADMIN, $admin);

        $ca_user = new User();
        $ca_user->setName('Loevann');
        $ca_user->setLastname('Guegan');
        $ca_user->setEmail('lguegan@test.test');
        $ca_user->setPhone('');
        $ca_user->setFilledInfo(true);
        $ca_user->setPassword(
            $this->passwordHasher->hashPassword($ca_user, self::DEFAULT_PASSWORD)
        );
        $ca_user->setRoles(['ROLE_CA_USER']);
        $manager->persist($ca_user);
        $this->addReference(self::CA_USER, $ca_user);

        $aa_user = new User();
        $aa_user->setName('Tom');
        $aa_user->setLastname('Raineri');
        $aa_user->setEmail('traineri@test.test');
        $aa_user->setPhone('');
        $aa_user->setFilledInfo(true);
        $aa_user->setPassword(
            $this->passwordHasher->hashPassword($aa_user, self::DEFAULT_PASSWORD)
        );
        $aa_user->setRoles(['ROLE_AA_USER']);
        $manager->persist($aa_user);
        $this->addReference(self::AA_USER, $aa_user);

        $fa_user = new User();
        $fa_user->setName('Simon');
        $fa_user->setLastname('Ledoux');
        $fa_user->setEmail('sledoux@test.test');
        $fa_user->setPhone('');
        $fa_user->setFilledInfo(true);
        $fa_user->setPassword(
            $this->passwordHasher->hashPassword($fa_user, self::DEFAULT_PASSWORD)
        );
        $fa_user->setRoles(['ROLE_FA_USER']);
        $manager->persist($fa_user);
        $this->addReference(self::FA_USER, $fa_user);

        $tm_user = new User();
        $tm_user->setName('Cacary');
        $tm_user->setLastname('Riendenin');
        $tm_user->setEmail('criendenin@test.test');
        $tm_user->setPhone('');
        $tm_user->setFilledInfo(true);
        $tm_user->setPassword(
            $this->passwordHasher->hashPassword($tm_user, self::DEFAULT_PASSWORD)
        );
        $tm_user->setRoles(['ROLE_TM_USER']);
        $manager->persist($tm_user);
        $this->addReference(self::TM_USER, $tm_user);

        $default_user = new User();
        $default_user->setName('Grobin');
        $default_user->setLastname('Ciboulette');
        $default_user->setEmail('gciboulette@test.test');
        $default_user->setPhone('');
        $default_user->setFilledInfo(true);
        $default_user->setPassword(
            $this->passwordHasher->hashPassword($default_user, self::DEFAULT_PASSWORD)
        );
        $default_user->setRoles(['ROLE_DEFAULT_USER']);
        $manager->persist($default_user);
        $this->addReference(self::DEFAULT_USER, $default_user);

        $manager->flush();
    }
}
