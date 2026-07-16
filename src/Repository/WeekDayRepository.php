<?php

namespace App\Repository;

use App\Entity\WeekDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeekDay>
 */
class WeekDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeekDay::class);
    }

    public function getWeekDayByDayNumber(int $dayNumber): ?WeekDay
    {
        return $this->createQueryBuilder('day')
            ->andWhere('day.dayNumber = :dayNumber')
            ->setParameter('dayNumber', $dayNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
