<?php

namespace App\Repository;

use App\Entity\Location;
use App\Entity\Pricing;
use App\Entity\TimeSlot;
use App\Entity\WeekDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pricing>
 */
class PricingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pricing::class);
    }

    // pour les lieux d'entrainement SAUF KANDA !!!
    public function getPricingByTrainingLocationWeekDayAndTimeSlot(Location $location, TimeSlot $timeSlot, WeekDay $weekDay): ?Pricing
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.location = :location')
            ->andWhere('p.timeSlot = :timeSlot')
            ->andWhere('p.weekDay = :weekDay')
            ->setParameter('location', $location)
            ->setParameter('timeSlot', $timeSlot)
            ->setParameter('weekDay', $weekDay)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getPrincingKandaByWeekDayTimeSlotAndGuestCount(WeekDay $weekDay, TimeSlot $timeSlot, int $guestCount): ?Pricing
    {
        return $this->createQueryBuilder('p')
            ->join('p.location', 'pl')
            ->andWhere('pl.code = :locationCode')
            ->andWhere('p.weekDay = :weekDay')
            ->andWhere('p.timeSlot = :timeSlot')
            ->andWhere('p.guestCount = :guestCount')
            ->setParameter('locationCode', 'KANDA')
            ->setParameter('weekDay', $weekDay)
            ->setParameter('timeSlot', $timeSlot)
            ->setParameter('guestCount', $guestCount)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
