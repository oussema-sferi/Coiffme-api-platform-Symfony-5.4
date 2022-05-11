<?php

namespace App\Repository;

use App\Entity\AppointmentService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Boolean;
use DateTime;

/**
 * @method AppointmentService|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppointmentService|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppointmentService[]    findAll()
 * @method AppointmentService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppointmentServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppointmentService::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(AppointmentService $entity, bool $flush = true): void
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
    public function remove(AppointmentService $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }


    public function statServiceAppointement(?int $user = null, $totalTurnover = false, $from = null,  $to = null)
    {
       $from = new DateTime($from);
       $to = new DateTime($to);
       $qb =  $this->createQueryBuilder('a')
        ->leftJoin('a.appointment', 'ap')
        ->leftJoin('a.service', 's');

        if ($user && !$totalTurnover) {
            $qb->select(" o.id AS user, s.id, s.name , s.unitPrice, s.color, a.quantity, a.totalPrice")
            ->addSelect("SUM(a.totalPrice) AS totalUserService")
            ->leftJoin('ap.user', 'o')
            ->groupBy("s.id")
            ->orderBy('s.name', 'ASC')
           
            ->andWhere('a.createdAt BETWEEN :from AND :to')
            ->andWhere('ap.user = :user')
            ->andWhere('ap.status = :status')
            ->setParameter('status', 'accepted')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
           
            ->setParameter('user', $user);
        }
        if ($user && $totalTurnover) {
            $qb->select(" o.id AS user, SUM(a.totalPrice) AS totalUserService")
            ->leftJoin('ap.user', 'o')
            ->orderBy('s.name', 'ASC')
            ->andWhere('ap.user = :user')
            ->andWhere('ap.status = :status')
      
            ->andWhere('a.createdAt BETWEEN :from AND :to')
            ->setParameter('status', 'accepted')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
       
            ->setParameter('user', $user);
        }
            /*     if ($stat == true ) {
            $qb->select("s.id, o.id AS user, CONCAT(o.firstName,' ',o.lastName) AS fullName, s.name , s.unitPrice, SUM(a.quantity) AS totalQuantity, SUM(a.totalPrice) AS totalPriceService")
            ->leftJoin('ap.owner', 'o')
            ->groupBy("s.id")
            ->orderBy('s.name', 'ASC');
        } */
  /*       if ($service) {
            $qb->select("SUM(a.quantity) AS totalQuatity , s.name as service, s.unitPrice, SUM(a.totalPrice) AS TotalServiceTurnover")
            ->groupBy("s.id")
            ->orderBy('s.name', 'ASC')
            ->andWhere('s.id = :service')
            ->setParameter('service', $service);
        } */
    /*     if ($totalTurnover == true) {
            $qb->select("COUNT(o.id) totalClient, s.name as service, s.unitPrice, SUM(a.quantity) AS totalQuatity,  SUM(a.totalPrice) AS TotalServiceTurnover")
            ->leftJoin('ap.owner', 'o')
            ->groupBy("s.id");
        } */
       

        return $qb->getQuery()->getResult()
    ;
    }

    // /**
    //  * @return AppointmentService[] Returns an array of AppointmentService objects
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
    public function findOneBySomeField($value): ?AppointmentService
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
