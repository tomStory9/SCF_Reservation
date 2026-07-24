<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function getBookingsByZone(Zone $zone): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.zone = :zone')
            ->andWhere('b.bookingStatus = :status')
            ->setParameter('zone', $zone)
            ->setParameter('status', BookingStatus::APPROVED)
            ->getQuery()
            ->getResult();
    }
}
