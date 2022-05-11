<?php

namespace App\Repository;

use App\Entity\AvailabilityDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AvailabilityDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvailabilityDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvailabilityDate[]    findAll()
 * @method AvailabilityDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvailabilityDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityDate::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AvailabilityDate $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(AvailabilityDate $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return AvailabilityDate[] Returns an array of AvailabilityDate objects
    //  */
    
    public function findAvailableDateByYear($year, $user = null )
    {
        $qb = $this->createQueryBuilder('a')
                    ->andWhere('YEAR(a.date) = :year')
                    ->setParameter('year', (int)$year)
                    ->orderBy('a.date', 'DESC');

                    if ($user) {
                        $qb->leftJoin('a.user', 'u')
                            ->andWhere('u.id = :user')
                            ->setParameter('user', $user);
                    }

            return $qb->getQuery()->getResult();
        ;
    }

    public function getAvailableDatePerUser($userId, $date)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('a.date = :date')
            ->setParameter('userId', $userId)
            ->setParameter('date', $date->format('Y-m-d'));
        return $qb->getQuery()->getResult();
    }

    /*
    public function findOneBySomeField($value): ?AvailabilityDate
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
