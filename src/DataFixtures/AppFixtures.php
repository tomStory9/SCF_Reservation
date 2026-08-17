<?php

namespace App\DataFixtures;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $setting = new Settings();
        $setting->setIsRoomBookingEnabled(true);
        $setting->setIsUserValidationRequired(true);
        $manager->persist($setting);

        $manager->flush();
    }
}
