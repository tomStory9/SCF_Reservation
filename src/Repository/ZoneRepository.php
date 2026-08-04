<?php

namespace App\Repository;

use App\Entity\Facility;
use App\Entity\Zone;
use App\Enum\ZoneType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function getTrainingZonesByFacility(Facility $facility): array
    {
        return $this->createQueryBuilder('z')
            ->AndWhere('z.facility = :facility')
            ->andWhere('z.typeZone = :zoneType')
            ->setParameter('facility', $facility)
            ->setParameter('zoneType', ZoneType::TRAINING)
            ->getQuery()
            ->getResult();
    }
}
