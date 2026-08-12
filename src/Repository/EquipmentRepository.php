<?php

namespace App\Repository;

use App\Entity\Equipment;
use App\Entity\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipment>
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    public function findByZoneOrNull(Zone $zone): array
    {
        return $this->createQueryBuilder('equipment')
            ->andWhere(
                'equipment.zone = :zone OR equipment.zone IS NULL'
            )
            ->setParameter('zone', $zone)
            ->orderBy('equipment.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableForZoneAndPeriod(
        Zone $zone,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        array $blockingStatuses,
    ): array {
        $rows = $this->createQueryBuilder('equipment')
            ->select([
                'equipment.id AS id',
                'equipment.name AS name',
                'equipment.unitPrice AS unitPrice',
                'equipment.maxQuantity AS maxQuantity',
                'COALESCE(SUM(bookingEquipment.quantity), 0) AS reservedQuantity',
            ])
            ->leftJoin(
                'equipment.bookingEquipment',
                'bookingEquipment'
            )
            ->leftJoin(
                'bookingEquipment.booking',
                'booking',
                'WITH',
                '
                    booking.zone = :zone
                    AND booking.startDate < :endDate
                    AND booking.endDate > :startDate
                    AND booking.bookingStatus IN (:blockingStatuses)
                '
            )
            ->where('equipment.zone = :zone OR equipment.zone IS NULL')
            ->setParameter('zone', $zone)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('blockingStatuses', $blockingStatuses)
            ->groupBy('equipment.id')
            ->addGroupBy('equipment.name')
            ->addGroupBy('equipment.unitPrice')
            ->addGroupBy('equipment.maxQuantity')
            ->orderBy('equipment.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static function (array $row): array {
                $maxQuantity = (int) $row['maxQuantity'];
                $reservedQuantity = (int) $row['reservedQuantity'];

                return [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'unitPrice' => (int) $row['unitPrice'],
                    'maxQuantity' => $maxQuantity,
                    'reservedQuantity' => $reservedQuantity,
                    'availableQuantity' => max(
                        0,
                        $maxQuantity - $reservedQuantity
                    ),
                ];
            },
            $rows
        );
    }

    public function calculateTotalEquipmentPrice($booking): int
    {
        $totalPrice = 0;

        foreach ($booking->getBookingEquipment() as $bookingEquipment) {
            $totalPrice += $bookingEquipment->getTotalPrice();
        }

        return $totalPrice;
    }

    //    /**
    //     * @return Equipment[] Returns an array of Equipment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Equipment
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
