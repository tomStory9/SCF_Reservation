<?php

namespace App\DataFixtures;

use App\Entity\Location;
use App\Entity\Pricing;
use App\Entity\TimeSlot;
use App\Entity\WeekDay;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PricingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // lieu d'entrainement
        $cube = $this->getReference(LocationFixtures::CUBE, Location::class);
        $lab = $this->getReference(LocationFixtures::LAB, Location::class);
        $kanda = $this->getReference(LocationFixtures::KANDA, Location::class);

        // chambre
        $room1 = $this->getReference(LocationFixtures::ROOM1, Location::class);
        $room2 = $this->getReference(LocationFixtures::ROOM2, Location::class);
        $room3 = $this->getReference(LocationFixtures::ROOM3, Location::class);

        // jour de la semaine
        $lundi = $this->getReference(WeekDayFixtures::LUNDI, WeekDay::class);
        $mardi = $this->getReference(WeekDayFixtures::MARDI, WeekDay::class);
        $mercredi = $this->getReference(WeekDayFixtures::MERCREDI, WeekDay::class);
        $jeudi = $this->getReference(WeekDayFixtures::JEUDI, WeekDay::class);
        $vendredi = $this->getReference(WeekDayFixtures::VENDREDI, WeekDay::class);
        $samedi = $this->getReference(WeekDayFixtures::SAMEDI, WeekDay::class);
        $dimanche = $this->getReference(WeekDayFixtures::DIMANCHE, WeekDay::class);

        // plage horaire
        $h8 = $this->getReference(TimeSlotFixtures::H8, TimeSlot::class);
        $h9 = $this->getReference(TimeSlotFixtures::H9, TimeSlot::class);
        $h10 = $this->getReference(TimeSlotFixtures::H10, TimeSlot::class);
        $h11 = $this->getReference(TimeSlotFixtures::H11, TimeSlot::class);
        $h12 = $this->getReference(TimeSlotFixtures::H12, TimeSlot::class);
        $h13 = $this->getReference(TimeSlotFixtures::H13, TimeSlot::class);
        $h14 = $this->getReference(TimeSlotFixtures::H14, TimeSlot::class);
        $h15 = $this->getReference(TimeSlotFixtures::H15, TimeSlot::class);
        $h16 = $this->getReference(TimeSlotFixtures::H16, TimeSlot::class);
        $h17 = $this->getReference(TimeSlotFixtures::H17, TimeSlot::class);
        $h18 = $this->getReference(TimeSlotFixtures::H18, TimeSlot::class);
        $h19 = $this->getReference(TimeSlotFixtures::H19, TimeSlot::class);
        $h20 = $this->getReference(TimeSlotFixtures::H20, TimeSlot::class);

        $weekDays = [
            $lundi,
            $mardi,
            $mercredi,
            $jeudi,
            $vendredi,
            $samedi,
            $dimanche,
        ];

        $timeSlots = [
            $h8,
            $h9,
            $h10,
            $h11,
            $h12,
            $h13,
            $h14,
            $h15,
            $h16,
            $h17,
            $h18,
            $h19,
            $h20,
        ];

        foreach ($weekDays as $weekDay) {
            $pricingDayCube = new Pricing();
            $pricingDayCube->setWeekDay($weekDay);
            $pricingDayCube->setLocation($cube);
            $pricingDayCube->setPrice(6000);
            $manager->persist($pricingDayCube);

            $pricingDayLab = new Pricing();
            $pricingDayLab->setWeekDay($weekDay);
            $pricingDayLab->setLocation($lab);
            $pricingDayLab->setPrice(6000);
            $manager->persist($pricingDayLab);

            $pricingDayKanda = new Pricing();
            $pricingDayKanda->setWeekDay($weekDay);
            $pricingDayKanda->setLocation($kanda);
            $pricingDayKanda->setPrice(6000);
            $manager->persist($pricingDayKanda);

            foreach ($timeSlots as $timeSlot) {
                $pricingDayHourCube = new Pricing();
                $pricingDayHourCube->setWeekDay($weekDay);
                $pricingDayHourCube->setTimeSlot($timeSlot);
                $pricingDayHourCube->setLocation($cube);
                $pricingDayHourCube->setPrice(500);
                $manager->persist($pricingDayHourCube);

                $pricingDayHourLab = new Pricing();
                $pricingDayHourLab->setWeekDay($weekDay);
                $pricingDayHourLab->setTimeSlot($timeSlot);
                $pricingDayHourLab->setPrice(500);
                $manager->persist($pricingDayHourLab);

                $pricingDayHourKanda = new Pricing();
                $pricingDayHourKanda->setWeekDay($weekDay);
                $pricingDayHourKanda->setTimeSlot($timeSlot);
                $pricingDayHourLab->setPrice(500);
                $manager->persist($pricingDayHourKanda);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LocationFixtures::class,
            TimeSlotFixtures::class,
            WeekDayFixtures::class,
        ];
    }
}
