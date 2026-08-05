<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRole>
 */
class UserRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }

    public function findRoleForUser(User $user): ?UserRole
    {
        return $this->createQueryBuilder('ur')
            ->where('ur.roleName IN (:roles)')
            ->setParameter('roles', $user->getRoles())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
