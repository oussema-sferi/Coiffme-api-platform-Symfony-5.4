<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Appointment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Appointment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Appointment[]    findAll()
 * @method Appointment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Appointment $entity, bool $flush = true): void
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
    public function remove(Appointment $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    /**
     * @return Appointment[] Returns an array of Appointment objects
    */
    public function getPendingAppointementQueryBuilder()
    {
        return $this->createQueryBuilder('c')
           ->andWhere('c.status = :status')
           ->andWhere('c.calculateStartDate <= :date')
           ->setParameters([
                'status' => 'pending',
                'date' => new \DateTimeImmutable('20 min ago')
            ]) 
           ->getQuery()
           ->getResult();
    }

   /**
     * @return Appointment[] Returns an array of Appointment objects
    */
    public function getOutDatedAppointementQueryBuilder()
    {
        return $this->createQueryBuilder('c')
           ->andWhere('c.status = :status')
           ->andWhere('c.calculateStartDate <= :date')
           ->setParameters([
                'status' => 'pending',
                'date' => new \DateTimeImmutable('30 min ago')
            ]) 
           ->getQuery()
           ->getResult();
    }

    // /**
    //  * @return Appointment[] Returns an array of Appointment objects
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
    public function findOneBySomeField($value): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getCreatedAndPendingAppointments()
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameters([
                'status' => 'pending',
            ])
            ->getQuery()
            ->getResult();
    }

    public function getAcceptedAppointments()
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameters([
                'status' => 'accepted',
            ])
            ->getQuery()
            ->getResult();
    }
}
