<?php

namespace App\Repository;

use App\Entity\AvailabilityPerDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AvailabilityPerDate|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvailabilityPerDate|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvailabilityPerDate[]    findAll()
 * @method AvailabilityPerDate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvailabilityPerDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityPerDate::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AvailabilityPerDate $entity, bool $flush = true): void
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
    public function remove(AvailabilityPerDate $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getAvailableDatePerDateAndTimeSlots($startTime, $endTime, $AvailabilityDateId)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.availabilityDate', 'u') 
            ->where('u.id = :availabilityDate') 
            ->andWhere('a.startHour = :startHour')
            ->andWhere('a.endHour = :endHour')
            ->setParameter('availabilityDate', $AvailabilityDateId)
            ->setParameter('startHour', $startTime->format('H:i:s'))
            ->setParameter('endHour', $endTime->format('H:i:s'));
        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return AvailabilityPerDate[] Returns an array of AvailabilityPerDate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AvailabilityPerDate
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
