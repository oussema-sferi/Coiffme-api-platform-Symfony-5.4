<?php

namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Repository\AvailabilityDateRepository;
use App\Repository\AvailabilityPerDateRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\AvailabilityDate;

final class AvailabilityDateDataPersister implements ContextAwareDataPersisterInterface
{
    private $adrep;
    private $apdrep;
    private $entityManager;

    public function __construct(AvailabilityDateRepository $adrep,  AvailabilityPerDateRepository $apdrep, EntityManagerInterface $entityManager)
    {
        $this->adrep = $adrep;
        $this->apdrep= $apdrep;
        $this->entityManager = $entityManager;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof AvailabilityDate;
    }

    public function persist($data, array $context = [])
    {
    
      $adrep = $this->adrep->findOneBy([
            'date' => $data->getDate(),
            'user' => $data->getUser()
      ]);
      
      if ( $adrep !== null) { 
        foreach ($data->getAvailabilityPerDates() as $AvailabilityPerDate) {
            $startHour = $AvailabilityPerDate->getStartHour();
            $EndHour = $AvailabilityPerDate->getEndHour();
            $AvailabilityDateId = $adrep->getId();
            $found = $this->apdrep->getAvailableDatePerDateAndTimeSlots($startHour, $EndHour, $AvailabilityDateId);
            if (empty($found)) {
                $adrep->addAvailabilityPerDate($AvailabilityPerDate);
            }
        }
        $this->entityManager->persist($adrep);
        $this->entityManager->flush();
        return $adrep;

      }
      else {
        $this->entityManager->persist($data);
        $this->entityManager->flush();
        return $data;
      }

    }

    public function remove($data, array $context = [])
    {
        // call your persistence layer to delete $data
    }
}