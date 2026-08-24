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
        $setting->setHourCheckInRoom(15);
        $setting->setHourCheckOut(10);
        $setting->setMinDayBooking(0);
        $setting->setMinDayRoomBooking(1);

        $manager->persist($setting);
        $manager->flush();
    }
}
