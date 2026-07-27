<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findNearestBooking(User $user, \DateTimeImmutable $timestamp): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->addSelect(
                'CASE
                    WHEN :timestamp BETWEEN b.startDate AND b.endDate THEN 0
                    WHEN ABS(DATE_DIFF(b.startDate, :timestamp)) <= ABS(DATE_DIFF(b.endDate, :timestamp))
                        THEN ABS(DATE_DIFF(b.startDate, :timestamp))
                    ELSE ABS(DATE_DIFF(b.endDate, :timestamp))
                END AS HIDDEN proximity'
            )
            ->andWhere('b.userBooking = :user')
            ->setParameter('user', $user)
            ->setParameter('timestamp', $timestamp, Types::DATETIME_IMMUTABLE)
            ->orderBy('proximity', 'ASC')
            ->addOrderBy('b.startDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
