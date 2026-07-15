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

        $matin_ete = $this->getReference(TimeSlotFixtures::MATIN_ETE, TimeSlot::class);
        $matin_autre = $this->getReference(TimeSlotFixtures::MATIN_AUTRE, TimeSlot::class);
        $apresMidi = $this->getReference(TimeSlotFixtures::APRES_MIDI, TimeSlot::class);
        $soir = $this->getReference(TimeSlotFixtures::SOIR, TimeSlot::class);

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

        $wideTimeSlots = [
            $matin_ete,
            $matin_autre,
            $apresMidi,
            $soir,
        ];

        foreach ($weekDays as $weekDay) {
            foreach ($wideTimeSlots as $wideTimeSlot) {
                $pricingDayWideSlotCube = new Pricing();
                $pricingDayWideSlotCube->setWeekDay($weekDay);
                $pricingDayWideSlotCube->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotCube->setLocation($cube);
                $pricingDayWideSlotCube->setFullPrice(6000);
                $pricingDayWideSlotCube->setReducedPriceA(5000);
                $pricingDayWideSlotCube->setReducedPriceB(3000);
                $manager->persist($pricingDayWideSlotCube);

                $pricingDayWideSlotLab = new Pricing();
                $pricingDayWideSlotLab->setWeekDay($weekDay);
                $pricingDayWideSlotLab->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotLab->setLocation($lab);
                $pricingDayWideSlotLab->setFullPrice(3000);
                $pricingDayWideSlotLab->setReducedPriceA(2000);
                $pricingDayWideSlotLab->setReducedPriceB(1000);
                $manager->persist($pricingDayWideSlotLab);

                $pricingDayWideSlotKandaFull = new Pricing();
                $pricingDayWideSlotKandaFull->setWeekDay($weekDay);
                $pricingDayWideSlotKandaFull->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotKandaFull->setLocation($kanda);
                $pricingDayWideSlotKandaFull->setFullPrice(6000);
                $pricingDayWideSlotKandaFull->setReducedPriceA(5000);
                $pricingDayWideSlotKandaFull->setReducedPriceB(3000);
                $pricingDayWideSlotKandaFull->setGuestCount(4);
                $manager->persist($pricingDayWideSlotKandaFull);

                $pricingDayWideSlotKandaDemi = new Pricing();
                $pricingDayWideSlotKandaDemi->setWeekDay($weekDay);
                $pricingDayWideSlotKandaDemi->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotKandaDemi->setLocation($kanda);
                $pricingDayWideSlotKandaDemi->setFullPrice(4000);
                $pricingDayWideSlotKandaDemi->setReducedPriceA(3000);
                $pricingDayWideSlotKandaDemi->setReducedPriceB(1500);
                $pricingDayWideSlotKandaDemi->setGuestCount(2);
                $manager->persist($pricingDayWideSlotKandaDemi);

                $pricingDayWideSlotKandaQuart = new Pricing();
                $pricingDayWideSlotKandaQuart->setWeekDay($weekDay);
                $pricingDayWideSlotKandaQuart->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotKandaQuart->setLocation($kanda);
                $pricingDayWideSlotKandaQuart->setFullPrice(2000);
                $pricingDayWideSlotKandaQuart->setReducedPriceA(1000);
                $pricingDayWideSlotKandaQuart->setReducedPriceB(500);
                $pricingDayWideSlotKandaQuart->setGuestCount(1);
                $manager->persist($pricingDayWideSlotKandaQuart);
            }

            foreach ($timeSlots as $timeSlot) {
                $pricingDayHourCube = new Pricing();
                $pricingDayHourCube->setWeekDay($weekDay);
                $pricingDayHourCube->setTimeSlot($timeSlot);
                $pricingDayHourCube->setLocation($cube);
                $pricingDayHourCube->setFullPrice(2000);
                $pricingDayHourCube->setReducedPriceA(1500);
                $pricingDayHourCube->setReducedPriceB(1000);
                $manager->persist($pricingDayHourCube);

                $pricingDayHourLab = new Pricing();
                $pricingDayHourLab->setWeekDay($weekDay);
                $pricingDayHourLab->setTimeSlot($timeSlot);
                $pricingDayHourLab->setLocation($lab);
                $pricingDayHourLab->setFullPrice(2000);
                $pricingDayHourLab->setReducedPriceA(1000);
                $pricingDayHourLab->setReducedPriceB(500);
                $manager->persist($pricingDayHourLab);

                $pricingDayHourKandaFull = new Pricing();
                $pricingDayHourKandaFull->setWeekDay($weekDay);
                $pricingDayHourKandaFull->setTimeSlot($timeSlot);
                $pricingDayHourKandaFull->setLocation($kanda);
                $pricingDayHourKandaFull->setFullPrice(3000);
                $pricingDayHourKandaFull->setReducedPriceA(2000);
                $pricingDayHourKandaFull->setReducedPriceB(1000);
                $pricingDayHourKandaFull->setGuestCount(4);
                $manager->persist($pricingDayHourKandaFull);

                $pricingDayHourKandaDemi = new Pricing();
                $pricingDayHourKandaDemi->setWeekDay($weekDay);
                $pricingDayHourKandaDemi->setTimeSlot($timeSlot);
                $pricingDayHourKandaDemi->setLocation($kanda);
                $pricingDayHourKandaDemi->setFullPrice(1500);
                $pricingDayHourKandaDemi->setReducedPriceA(1000);
                $pricingDayHourKandaDemi->setReducedPriceB(500);
                $pricingDayHourKandaDemi->setGuestCount(2);
                $manager->persist($pricingDayHourKandaDemi);

                $pricingDayHourKandaQuart = new Pricing();
                $pricingDayHourKandaQuart->setWeekDay($weekDay);
                $pricingDayHourKandaQuart->setTimeSlot($timeSlot);
                $pricingDayHourKandaQuart->setLocation($kanda);
                $pricingDayHourKandaQuart->setFullPrice(1000);
                $pricingDayHourKandaQuart->setReducedPriceA(500);
                $pricingDayHourKandaQuart->setReducedPriceB(200);
                $pricingDayHourKandaQuart->setGuestCount(1);
                $manager->persist($pricingDayHourKandaQuart);
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
