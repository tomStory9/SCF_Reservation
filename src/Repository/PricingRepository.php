<?php

namespace App\Repository;

use App\Entity\Pricing;
use App\Entity\TimeSlot;
use App\Entity\WeekDay;
use App\Entity\Zone;
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

    public function getPricingByTrainingLocationWeekDayAndTimeSlot(Zone $zone, TimeSlot $timeSlot, WeekDay $weekDay): ?Pricing
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.zone = :zone')
            ->andWhere('p.timeSlot = :timeSlot')
            ->andWhere('p.weekDay = :weekDay')
            ->setParameter('zone', $zone)
            ->setParameter('timeSlot', $timeSlot)
            ->setParameter('weekDay', $weekDay)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
