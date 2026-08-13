<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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

    public function findAllBookingInACertainTime(string $time): array
    {
        $targetDayStart = new \DateTimeImmutable()
            ->modify($time)
            ->setTime(0, 0, 0);

        $targetDayEnd = $targetDayStart->modify('+1 day');

        return $this->createQueryBuilder('b')
            ->andWhere('b.startDate >= :dayStart')
            ->andWhere('b.startDate < :dayEnd')
            ->setParameter('dayStart', $targetDayStart)
            ->setParameter('dayEnd', $targetDayEnd)
            ->getQuery()
            ->getResult();
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

    /**
     * @return Booking[]
     */
    public function findUserBookingsForPeriod(
        User $user,
        \DateTimeImmutable $monthStart,
        \DateTimeImmutable $nextMonth,
    ): array {
        return $this->createQueryBuilder('booking')
            ->andWhere('booking.userBooking = :user')
            ->andWhere('booking.startDate >= :monthStart')
            ->andWhere('booking.startDate < :nextMonth')
            ->andWhere('booking.bookingStatus IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonth', $nextMonth)
            ->setParameter('statuses', [
                BookingStatus::PENDING,
                BookingStatus::APPROVED,
            ])
            ->orderBy('booking.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasOverlap($zone, \DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeBookingId = null): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.zone = :zone')
            ->andWhere('b.startDate < :end')
            ->andWhere('b.endDate > :start')
            ->andWhere('b.bookingStatus = :status')
            ->setParameter('zone', $zone)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('status', BookingStatus::APPROVED);

        if (null !== $excludeBookingId) {
            $qb->andWhere('b.id != :excludeId')
                ->setParameter('excludeId', $excludeBookingId);
        }

        return count($qb->getQuery()->getResult()) > 0;
    }

    public function getRemainingFreeHoursThisMonth(User $user): float
    {
        $em = $this->getEntityManager();

        $allocatedHours = (float) $em->createQueryBuilder()
            ->select('MAX(ur.allocatedHoursPerMonth)')
            ->from(UserRole::class, 'ur')
            ->where('ur.roleName IN (:roles)')
            ->setParameter('roles', $user->getRoles())
            ->getQuery()
            ->getSingleScalarResult();

        if ($allocatedHours <= 0) {
            return 0.0;
        }

        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');
        $endOfMonth = new \DateTimeImmutable('first day of next month midnight');

        $bookings = $this->createQueryBuilder('b')
            ->where('b.userBooking = :user')
            ->andWhere('b.startDate >= :startOfMonth')
            ->andWhere('b.startDate < :endOfMonth')
            ->andWhere('b.bookingStatus IN (:validStatuses)')
            ->setParameter('user', $user)
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->setParameter('validStatuses', [
                BookingStatus::PENDING,
                BookingStatus::APPROVED,
            ])
            ->getQuery()
            ->getResult();

        $usedHours = 0.0;
        foreach ($bookings as $booking) {
            $diffInSeconds = $booking->getEndDate()->getTimestamp() - $booking->getStartDate()->getTimestamp();
            $usedHours += $diffInSeconds / 3600;
        }

        return max(0.0, $allocatedHours - $usedHours);
    }
}
