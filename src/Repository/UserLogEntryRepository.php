<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserLogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLogEntry>
 */
class UserLogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLogEntry::class);
    }

    /**
     * @return UserLogEntry[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('entry')
            ->andWhere('entry.objectId = :objectId')
            ->andWhere('entry.objectClass = :objectClass')
            ->setParameter('objectId', (string) $user->getId())
            ->setParameter('objectClass', User::class)
            ->orderBy('entry.version', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
