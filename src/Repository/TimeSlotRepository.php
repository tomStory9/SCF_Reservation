<?php

namespace App\Repository;

use App\Entity\TimeSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeSlot>
 */
class TimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

    public function findAllPeriod(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.period, t.startTime, t.endTime')
            ->where('t.period NOT LIKE :period')
            ->setParameter('period', 'hourly')
            ->orderBy('t.period', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
