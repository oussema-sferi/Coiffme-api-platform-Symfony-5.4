<?php

namespace App\Repository;

use DateTime;
use App\Entity\User;
use App\Entity\Service;
use Doctrine\ORM\ORMException;
use App\Repository\ServiceRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{

    

    public function __construct(
        ManagerRegistry $registry,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
        private ServiceRepository $serviceRep,
        private MailerInterface $mailer
        )
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = true): void
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
    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }


    public function create($data, $mangoPayService)
    {
        $user = new User();
        $hashedPassword = $this->passwordHasher
                               ->hashPassword($user, $data->password);
        $user->setFirstName($data->firstName)
             ->setLastName($data->lastName)
             ->setEmail($data->email)
             ->setRoles(["ROLE_CLIENT"])
             ->setPassword($hashedPassword)
            ->setMangoUserId($mangoPayService->createNaturalUser($data->firstName, $data->lastName, $data->email));
        
       if(isset($data->phone)){
            $user->setPhone($data->phone);
        }

        if(isset($data->birthdate)){
            $user->setBirthDate(new \DateTime($data->birthdate));
        }
        $this->_em->persist($user);
        $user->setMangoWalletId($mangoPayService->createWalletForNaturalUser($user->getMangoUserId()));
        $this->_em->flush();
        $this->processSendingRegistrationEmail($data->email, $this->mailer);
        return $user;
    }


    public function createPro($data,  $mangoPayService)
    {
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user , $data->password);
        $user->setFirstName($data->firstName)
             ->setLastName($data->lastName)
             ->setEmail($data->email)
             ->setRoles(["ROLE_PRO"])
             ->setPassword($hashedPassword)
             ->setSiret($data->siret)
            ->setMangoUserId($mangoPayService->createNaturalUser($data->firstName, $data->lastName, $data->email));;

        if(isset($data->phone)){
            $user->setPhone($data->phone);
        }
        
        if(isset($data->birthDate)){
            $user->setBirthDate(new \DateTime($data->birthDate));
        }
        
        if(isset($data->description)){
            $user->setDescription($data->description);
        }

        if(isset($data->pictureFile)){
            $user->setPictureFile($data->pictureFile);
        }

        if(isset($data->typeUser)){
            $user->setTypeUser($data->typeUser);
        }
        $this->_em->persist($user);
        //$user->setMangoWalletId($mangoPayService->createWalletForNaturalUser($user->getMangoUserId()));
        $this->_em->flush();
        $this->processSendingRegistrationEmail($data->email, $this->mailer);
        return $user;
    }



    public function persist($data)
    {

        $user = $this->find($this->security->getUser()->getId());


        $user->addService($data->services);
        $this->_em->persist($user);
        $this->_em->flush();
        return $data;
    }

    // public function remove($data, array $context = [])
    // {
    //     return $this->decorated->remove($data, $context);
    // }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function processSendingRegistrationEmail(string $userEmail, MailerInterface $mailer)
    {
        $email = (new TemplatedEmail())
            ->from(new Address('support@coiffme.fr', 'Service Client CoiffMe'))
            ->to($userEmail)
            ->subject('Création de votre compte')
            ->htmlTemplate('registration/registration_email.html.twig');
        ;
        $mailer->send($email);
    }
}
