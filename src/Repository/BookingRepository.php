<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\Zone;
use App\Enum\BookingStatus;
use App\Enum\ZoneType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    private readonly SettingsRepository $settingsRepository;

    public function __construct(ManagerRegistry $registry, SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
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

    /**
     * @return array{
     *     items: list<Booking>,
     *     page: int,
     *     pages: int,
     *     total: int,
     *     perPage: int
     * }
     */
    public function paginateForUser(
        User $user,
        int $requestedPage,
        int $perPage,
        ?BookingStatus $status,
        string $scope,
        \DateTimeImmutable $now,
    ): array {
        $queryBuilder = $this->createQueryBuilder('booking')
            ->addSelect('zone', 'facility')
            ->join('booking.zone', 'zone')
            ->leftJoin('zone.facility', 'facility')
            ->andWhere('booking.userBooking = :user')
            ->setParameter('user', $user);

        if (null !== $status) {
            $queryBuilder
                ->andWhere('booking.bookingStatus = :status')
                ->setParameter('status', $status);
        }

        if ('upcoming' === $scope) {
            $queryBuilder
                ->andWhere('booking.endDate >= :now')
                ->andWhere('booking.bookingStatus != :declinedStatus')
                ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
                ->setParameter('declinedStatus', BookingStatus::DECLINED)
                ->orderBy('booking.startDate', 'ASC');
        } elseif ('past' === $scope) {
            $queryBuilder
                ->andWhere('booking.endDate < :now')
                ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
                ->orderBy('booking.startDate', 'DESC');
        } else {
            $queryBuilder->orderBy('booking.startDate', 'DESC');
        }

        $total = count(new Paginator($queryBuilder, true));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $requestedPage), $pages);

        $queryBuilder
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($queryBuilder, true);

        return [
            'items' => array_values(iterator_to_array($paginator->getIterator())),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'perPage' => $perPage,
        ];
    }

    /**
     * @return array{total: int, upcoming: int, pending: int, paymentDue: int, paid: int}
     */
    public function getUserDashboardSummary(User $user, \DateTimeImmutable $now): array
    {
        $summary = $this->createQueryBuilder('booking')
            ->select('COUNT(booking.id) AS total')
            ->addSelect('SUM(CASE WHEN booking.endDate >= :now AND booking.bookingStatus != :declinedStatus THEN 1 ELSE 0 END) AS upcoming')
            ->addSelect('SUM(CASE WHEN booking.bookingStatus = :pendingStatus THEN 1 ELSE 0 END) AS pending')
            ->addSelect('SUM(CASE WHEN booking.bookingStatus = :approvedStatus THEN 1 ELSE 0 END) AS paymentDue')
            ->addSelect('SUM(CASE WHEN booking.bookingStatus = :paidStatus THEN 1 ELSE 0 END) AS paid')
            ->andWhere('booking.userBooking = :user')
            ->setParameter('user', $user)
            ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ->setParameter('declinedStatus', BookingStatus::DECLINED)
            ->setParameter('pendingStatus', BookingStatus::PENDING)
            ->setParameter('approvedStatus', BookingStatus::APPROVED)
            ->setParameter('paidStatus', BookingStatus::PAID)
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) ($summary['total'] ?? 0),
            'upcoming' => (int) ($summary['upcoming'] ?? 0),
            'pending' => (int) ($summary['pending'] ?? 0),
            'paymentDue' => (int) ($summary['paymentDue'] ?? 0),
            'paid' => (int) ($summary['paid'] ?? 0),
        ];
    }

    public function hasOverlap($zone, \DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeBookingId = null, array $conflictingCodes = []): bool
    {
        $qb = $this->createQueryBuilder('b')
            ->join('b.zone', 'z');

        if (!empty($conflictingCodes)) {
            $qb->where('z = :zone OR z.code IN (:conflictingCodes)')
                ->setParameter('zone', $zone)
                ->setParameter('conflictingCodes', $conflictingCodes);
        } else {
            $qb->where('z = :zone')
                ->setParameter('zone', $zone);
        }

        $qb->andWhere('b.startDate < :end')
            ->andWhere('b.endDate > :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($this->settingsRepository->getSettings()->isPendingBookingBlocking()) {
            $qb->andWhere('b.bookingStatus IN (:statuses)')
                ->setParameter('statuses', [BookingStatus::APPROVED, BookingStatus::PENDING]);
        } else {
            $qb->andWhere('b.bookingStatus = :status')
                ->setParameter('status', BookingStatus::APPROVED);
        }

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
            ->join('b.zone', 'z')
            ->where('b.userBooking = :user')
            ->andWhere('z.typeZone = :trainingType')
            ->andWhere('b.startDate >= :startOfMonth')
            ->andWhere('b.startDate < :endOfMonth')
            ->andWhere('b.bookingStatus IN (:validStatuses)')
            ->setParameter('user', $user)
            ->setParameter('trainingType', ZoneType::TRAINING)
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->setParameter('validStatuses', [
                BookingStatus::PENDING,
                BookingStatus::APPROVED,
                BookingStatus::PAID,
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

    public function findBookingsByMonth(int $year, int $month): array
    {
        $startOfMonth = new \DateTimeImmutable("$year-$month-01 00:00:00");
        $endOfMonth = $startOfMonth->modify('first day of next month');

        return $this->createQueryBuilder('b')
            ->andWhere('b.startDate >= :startOfMonth')
            ->andWhere('b.startDate < :endOfMonth')
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->getQuery()
            ->getResult();
    }

    public function getBookingsForZoneAndConflicts(Zone $zone, array $conflictingCodes = [], string $type = 'training'): array
    {
        $qb = $this->createQueryBuilder('b')
            ->join('b.zone', 'z')
            ->andWhere('z = :zone')
            ->setParameter('zone', $zone);

        $allowedStatuses = [BookingStatus::APPROVED];

        if ('room' === $type && $this->settingsRepository->getSettings()->isPendingRoomBlocking()) {
            $allowedStatuses[] = BookingStatus::PENDING;
        }

        if ('training' === $type && $this->settingsRepository->getSettings()->isPendingBookingBlocking()) {
            $allowedStatuses[] = BookingStatus::PENDING;
        }

        $qb->andWhere('b.bookingStatus IN (:statuses)')
            ->setParameter('statuses', $allowedStatuses);

        if (!empty($conflictingCodes)) {
            $qb->andWhere('z.code IN (:conflictingCodes)')
                ->setParameter('conflictingCodes', $conflictingCodes);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param BookingStatus[] $statuses
     */
    public function countOverlappingPeriod(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        array $statuses,
    ): int {
        return (int) $this->createQueryBuilder('booking')
            ->select('COUNT(booking.id)')
            ->andWhere('booking.startDate < :end')
            ->andWhere('booking.endDate > :start')
            ->andWhere('booking.bookingStatus IN (:statuses)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param BookingStatus[] $statuses
     */
    public function sumValueStartingInPeriod(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        array $statuses,
    ): int {
        return (int) $this->createQueryBuilder('booking')
            ->select('COALESCE(SUM(booking.price), 0)')
            ->andWhere('booking.startDate >= :start')
            ->andWhere('booking.startDate < :end')
            ->andWhere('booking.bookingStatus IN (:statuses)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('booking')
            ->select('booking.bookingStatus AS status')
            ->addSelect('COUNT(booking.id) AS total')
            ->groupBy('booking.bookingStatus')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof BookingStatus
                ? $row['status']->value
                : (string) $row['status'];
            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findRecentPendingRows(int $limit = 6): array
    {
        return $this->createQueryBuilder('booking')
            ->select('booking.id AS id')
            ->addSelect('booking.startDate AS startDate')
            ->addSelect('booking.createdDate AS createdDate')
            ->addSelect('booking.price AS amount')
            ->addSelect('booking.bookingStatus AS status')
            ->addSelect('user.name AS userFirstName')
            ->addSelect('user.lastname AS userLastName')
            ->addSelect('zone.name AS zoneName')
            ->addSelect('facility.name AS facilityName')
            ->join('booking.userBooking', 'user')
            ->join('booking.zone', 'zone')
            ->leftJoin('zone.facility', 'facility')
            ->andWhere('booking.bookingStatus = :status')
            ->setParameter('status', BookingStatus::PENDING)
            ->orderBy('booking.createdDate', 'DESC')
            ->addOrderBy('booking.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param BookingStatus[] $statuses
     *
     * @return array<int, array<string, mixed>>
     */
    public function findCalendarRows(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        array $statuses,
    ): array {
        $rows = $this->createQueryBuilder('booking')
            ->select('booking.id AS id')
            ->addSelect('booking.startDate AS startDate')
            ->addSelect('booking.endDate AS endDate')
            ->addSelect('booking.isFullDay AS isFullDay')
            ->addSelect('booking.price AS amount')
            ->addSelect('booking.TotalPrice AS totalPrice')
            ->addSelect('booking.guestCount AS guests')
            ->addSelect('booking.bookingStatus AS status')
            ->addSelect('user.name AS userFirstName')
            ->addSelect('user.lastname AS userLastName')
            ->addSelect('zone.name AS zoneName')
            ->addSelect('facility.name AS facilityName')
            ->addSelect('bookingEquipment.quantity AS quantity')
            ->addSelect('bookingEquipment.TotalPrice AS bookingEquipmentTotalPrice')
            ->addSelect('equipment.id AS equipmentId')
            ->addSelect('equipment.name AS equipmentName')
            ->addSelect('equipment.unitPrice AS equipmentUnitPrice')
            ->addSelect('equipment.maxQuantity AS equipmentMaxQuantity')
            ->addSelect('equipmentZone.id AS equipmentZoneId')
            ->addSelect('equipmentZone.name AS equipmentZoneName')
            ->join('booking.userBooking', 'user')
            ->join('booking.zone', 'zone')
            ->leftJoin('zone.facility', 'facility')
            ->leftJoin('booking.bookingEquipment', 'bookingEquipment')
            ->leftJoin('bookingEquipment.equipment', 'equipment')
            ->leftJoin('equipment.zone', 'equipmentZone')
            ->andWhere('booking.startDate < :end')
            ->andWhere('booking.endDate > :start')
            ->andWhere('booking.bookingStatus IN (:statuses)')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statuses', $statuses)
            ->orderBy('booking.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        // Regroupement par réservation
        $grouped = [];
        foreach ($rows as $row) {
            $bookingId = $row['id'];

            if (!isset($grouped[$bookingId])) {
                $grouped[$bookingId] = [
                    'id' => $row['id'],
                    'startDate' => $row['startDate'],
                    'endDate' => $row['endDate'],
                    'isFullDay' => $row['isFullDay'],
                    'amount' => $row['amount'],
                    'totalPrice' => $row['totalPrice'],
                    'guests' => $row['guests'],
                    'status' => $row['status'],
                    'userFirstName' => $row['userFirstName'],
                    'userLastName' => $row['userLastName'],
                    'zoneName' => $row['zoneName'],
                    'facilityName' => $row['facilityName'],
                    'equipment' => [],
                    'equipmentTotalPrice' => 0,
                ];
            }

            $grouped[$bookingId]['equipment'][] = [
                'equipmentId' => $row['equipmentId'],
                'equipmentName' => $row['equipmentName'],
                'equipmentUnitPrice' => $row['equipmentUnitPrice'],
                'equipmentMaxQuantity' => $row['equipmentMaxQuantity'],
                'equipmentZoneId' => $row['equipmentZoneId'],
                'equipmentZoneName' => $row['equipmentZoneName'],
                'quantity' => $row['quantity'],
                'bookingEquipmentTotalPrice' => $row['bookingEquipmentTotalPrice'],
            ];
            $grouped[$bookingId]['equipmentTotalPrice'] += (int) $row['bookingEquipmentTotalPrice'];
        }

        return array_values($grouped);
    }
}
