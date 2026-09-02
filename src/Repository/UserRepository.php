<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[]
     */
    public function findRecent(int $limit = 6): array
    {
        return $this->createQueryBuilder('user')
            ->orderBy('user.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la répartition des nationalités.
     */
    public function getNationalityStats(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.nationalitie as name, COUNT(u.id) as count')
            ->groupBy('u.nationalitie')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Récupère la répartition des villes de résidence pour la carte.
     */
    public function getCityStats(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.residenceCity as city, COUNT(u.id) as count')
            ->where('u.residenceCity IS NOT NULL')
            ->groupBy('u.residenceCity')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Récupère le nombre d'utilisateurs par spécialité de cirque.
     */
    public function getSpecialtyStats(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.specialties', 's')
            ->select('s.name as name, COUNT(u.id) as count')
            ->groupBy('s.name')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule le nombre d'années de pratique moyen.
     */
    public function getAveragePracticeYears(): float
    {
        $currentYear = (int) date('Y');

        $result = $this->createQueryBuilder('u')
            ->select('AVG(:currentYear - u.practiceStartYear) as avgYears')
            ->where('u.practiceStartYear IS NOT NULL')
            ->setParameter('currentYear', $currentYear)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round((float) $result, 1) : 0.0;
    }

    /**
     * Compte le nombre total d'utilisateurs.
     */
    public function countTotalUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
