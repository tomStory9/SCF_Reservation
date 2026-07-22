<?php

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use App\Repository\PricingRepository;
use App\Repository\WeekDayRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookingFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly WeekDayRepository $dayRepository,
        private readonly PricingRepository $pricingRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            $admin = $this->getReference(UserFixtures::ADMIN, User::class),
            $ca_user = $this->getReference(UserFixtures::CA_USER, User::class),
            $aa_user = $this->getReference(UserFixtures::AA_USER, User::class),
            $fa_user = $this->getReference(UserFixtures::FA_USER, User::class),
            $tm_user = $this->getReference(UserFixtures::TM_USER, User::class),
            $default_user = $this->getReference(UserFixtures::DEFAULT_USER, User::class),
        ];

        $locationsTraining = [
            ZoneFixtures::KUMA_CUBE,
            ZoneFixtures::KUMA_LAB,
            ZoneFixtures::KODA1A,
            ZoneFixtures::KODA1B,
            ZoneFixtures::KODA1C,
            ZoneFixtures::KODA1D,
        ];

        $timeSlotPeriod = [
            TimeSlotFixtures::MATIN,
            TimeSlotFixtures::APRES_MIDI,
            TimeSlotFixtures::SOIR,
            TimeSlotFixtures::H11,
            TimeSlotFixtures::H16,
            TimeSlotFixtures::H20,
        ];

        $codeKoda = [
          'ZONEA',
          'ZONEB',
          'ZONEC',
          'ZONED',
        ];

        foreach ($users as $user) {
            $day = 1;
            for ($i = 0; $i < 3; ++$i) {
                $zone = $this->getReference($locationsTraining[$i], Zone::class);
                $period = $this->getReference($timeSlotPeriod[rand(0, 5)], TimeSlot::class);

                $randomDays = mt_rand(0, 60);

                $randomDate = new \DateTimeImmutable('today')->modify("+$randomDays days");

                $dateStart = $randomDate->setTime(
                    (int) $period->getStartTime()->format('H'),
                    (int) $period->getStartTime()->format('i'),
                );

                $dateEnd = $randomDate->setTime(
                    (int) $period->getEndTime()->format('H'),
                    (int) $period->getEndTime()->format('i'),
                );

                $dayNumber = (int) $randomDate->format('N');
                $weekDay = $this->dayRepository->getWeekDayByDayNumber($dayNumber);

                $guestCount = in_array($zone->getCode(), $codeKoda) ? 1 : rand(1, 4);

                $pricing = $this->pricingRepository->getPricingByTrainingLocationWeekDayAndTimeSlot($zone, $period, $weekDay);

                $booking = new Booking();
                $booking->setUserBooking($user);
                $booking->setZone($zone);
                $booking->setGuestCount($guestCount);
                $booking->setIsFullDay(false);
                $booking->setBookingStatus(BookingStatus::PENDING);
                $booking->setCreatedDate(new \DateTimeImmutable());
                $booking->setEndDate($dateEnd);
                $booking->setStartDate($dateStart);
                $booking->setPrice($pricing->getFullPrice());

                $manager->persist($booking);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PricingFixtures::class,
        ];
    }
}
