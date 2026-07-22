<?php

namespace App\DataFixtures;

use App\Entity\UserRole;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserRoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $roleAdmin = new UserRole();
        $roleAdminEnum = UserRoleEnum::ADMIN;
        $roleAdmin->setRoleName($roleAdminEnum->value);
        $roleAdmin->setLabel($roleAdminEnum->getLabel());
        $roleAdmin->setAllocatedHoursPerMonth(0);
        $manager->persist($roleAdmin);

        $roleCA = new UserRole();
        $roleCAEnum = UserRoleEnum::CA_USER;
        $roleCA->setRoleName($roleCAEnum->value);
        $roleCA->setLabel($roleCAEnum->getLabel());
        $roleCA->setAllocatedHoursPerMonth(20);
        $roleCA->setMaxAdvanceBookingDays(60);
        $manager->persist($roleCA);

        $roleAA = new UserRole();
        $roleAAEnum = UserRoleEnum::AA_USER;
        $roleAA->setRoleName($roleAAEnum->value);
        $roleAA->setLabel($roleAAEnum->getLabel());
        $roleAA->setAllocatedHoursPerMonth(10);
        $roleAA->setMaxAdvanceBookingDays(40);
        $manager->persist($roleAA);

        $roleFA = new UserRole();
        $roleFAEnum = UserRoleEnum::FA_USER;
        $roleFA->setRoleName($roleFAEnum->value);
        $roleFA->setLabel($roleFAEnum->getLabel());
        $roleFA->setAllocatedHoursPerMonth(0);
        $roleFA->setMaxAdvanceBookingDays(30);
        $manager->persist($roleFA);

        $roleTM = new UserRole();
        $roleTMEnum = UserRoleEnum::TM_USER;
        $roleTM->setRoleName($roleTMEnum->value);
        $roleTM->setLabel($roleTMEnum->getLabel());
        $roleTM->setAllocatedHoursPerMonth(20);
        $roleTM->setMaxAdvanceBookingDays(3);
        $manager->persist($roleTM);

        $roleDefault = new UserRole();
        $roleDefaultEnum = UserRoleEnum::DEFAULT_USER;
        $roleDefault->setRoleName($roleDefaultEnum->value);
        $roleDefault->setLabel($roleDefaultEnum->getLabel());
        $roleDefault->setAllocatedHoursPerMonth(0);
        $roleDefault->setMaxAdvanceBookingDays(1);
        $manager->persist($roleDefault);

        $manager->flush();
    }
}
