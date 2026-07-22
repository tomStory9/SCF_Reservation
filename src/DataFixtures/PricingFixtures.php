<?php

namespace App\DataFixtures;

use App\Entity\Pricing;
use App\Entity\TimeSlot;
use App\Entity\WeekDay;
use App\Entity\Zone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PricingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // lieu d'entrainement
        $cube = $this->getReference(ZoneFixtures::KUMA_CUBE, Zone::class);
        $lab = $this->getReference(ZoneFixtures::KUMA_LAB, Zone::class);

        $kodas = [
            $kodaA = $this->getReference(ZoneFixtures::KODA1A, Zone::class),
            $kodaB = $this->getReference(ZoneFixtures::KODA1B, Zone::class),
            $kodaC = $this->getReference(ZoneFixtures::KODA1C, Zone::class),
            $kodaD = $this->getReference(ZoneFixtures::KODA1D, Zone::class),
        ];

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

        $matin = $this->getReference(TimeSlotFixtures::MATIN, TimeSlot::class);
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
            $matin,
            $apresMidi,
            $soir,
        ];

        foreach ($weekDays as $weekDay) {
            foreach ($wideTimeSlots as $wideTimeSlot) {
                $pricingDayWideSlotCube = new Pricing();
                $pricingDayWideSlotCube->setWeekDay($weekDay);
                $pricingDayWideSlotCube->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotCube->setZone($cube);
                $pricingDayWideSlotCube->setFullPrice(6000);
                $pricingDayWideSlotCube->setReducedPriceA(5000);
                $pricingDayWideSlotCube->setReducedPriceB(3000);
                $manager->persist($pricingDayWideSlotCube);

                $pricingDayWideSlotLab = new Pricing();
                $pricingDayWideSlotLab->setWeekDay($weekDay);
                $pricingDayWideSlotLab->setTimeSlot($wideTimeSlot);
                $pricingDayWideSlotLab->setZone($lab);
                $pricingDayWideSlotLab->setFullPrice(3000);
                $pricingDayWideSlotLab->setReducedPriceA(2000);
                $pricingDayWideSlotLab->setReducedPriceB(1000);
                $manager->persist($pricingDayWideSlotLab);

                foreach ($kodas as $koda) {
                    $pricingDayWideSlotKodaQuart = new Pricing();
                    $pricingDayWideSlotKodaQuart->setWeekDay($weekDay);
                    $pricingDayWideSlotKodaQuart->setTimeSlot($wideTimeSlot);
                    $pricingDayWideSlotKodaQuart->setZone($koda);
                    $pricingDayWideSlotKodaQuart->setFullPrice(2000);
                    $pricingDayWideSlotKodaQuart->setReducedPriceA(1000);
                    $pricingDayWideSlotKodaQuart->setReducedPriceB(500);
                    $manager->persist($pricingDayWideSlotKodaQuart);
                }
            }

            foreach ($timeSlots as $timeSlot) {
                $pricingDayHourCube = new Pricing();
                $pricingDayHourCube->setWeekDay($weekDay);
                $pricingDayHourCube->setTimeSlot($timeSlot);
                $pricingDayHourCube->setZone($cube);
                $pricingDayHourCube->setFullPrice(2000);
                $pricingDayHourCube->setReducedPriceA(1500);
                $pricingDayHourCube->setReducedPriceB(1000);
                $manager->persist($pricingDayHourCube);

                $pricingDayHourLab = new Pricing();
                $pricingDayHourLab->setWeekDay($weekDay);
                $pricingDayHourLab->setTimeSlot($timeSlot);
                $pricingDayHourLab->setZone($lab);
                $pricingDayHourLab->setFullPrice(2000);
                $pricingDayHourLab->setReducedPriceA(1000);
                $pricingDayHourLab->setReducedPriceB(500);
                $manager->persist($pricingDayHourLab);

                foreach ($kodas as $koda) {
                    $pricingDayHourKodaQuart = new Pricing();
                    $pricingDayHourKodaQuart->setWeekDay($weekDay);
                    $pricingDayHourKodaQuart->setTimeSlot($timeSlot);
                    $pricingDayHourKodaQuart->setZone($koda);
                    $pricingDayHourKodaQuart->setFullPrice(1000);
                    $pricingDayHourKodaQuart->setReducedPriceA(500);
                    $pricingDayHourKodaQuart->setReducedPriceB(200);
                    $manager->persist($pricingDayHourKodaQuart);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ZoneFixtures::class,
            TimeSlotFixtures::class,
            WeekDayFixtures::class,
        ];
    }
}
