<?php

namespace App\Controller;

use App\Entity\AvailabilityDate;
use App\Entity\AvailabilityPerDate;
use App\Repository\AvailabilityDateRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class MultipleDatesController extends AbstractController
{
    public function __construct(
        private AvailabilityDateRepository $availabilityDateRepository,
        private UserRepository $userRepository,
        private ManagerRegistry $em,
    )
    {
    }
    public function __invoke(Request $request): Response
    {
        $daysOfTheWeek = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday'];
        $loggedUserId = $this->getUser()->getId();
        $loggedUser = $this->userRepository->find($loggedUserId);
        $jsonData = json_decode($request->getContent(), true);
        $month = intval($jsonData["month"]);
        $actualYear = date("Y");
        $daysNumberPerSelectedMonth = cal_days_in_month(CAL_GREGORIAN, $month, $actualYear);
        $datesArray = [];
        foreach ($jsonData["days"] as $weekDay)
        {
            for ($i = 1; $i <= $daysNumberPerSelectedMonth; $i++)
            {
                if(date("l", mktime(0,0,0,$month,$i,$actualYear)) === $daysOfTheWeek[$weekDay])
                {
                    $datesArray[] = $i . "-" . $month . "-" . $actualYear;
                }
            }
        }
        foreach ($datesArray as $date)
        {
            $newDate = $date . " 00:00:00";
            $existentDateObject = $this->availabilityDateRepository->getAvailableDatePerUser($loggedUserId, \DateTime::createFromFormat('d-m-Y H:i:s', $newDate));
            if($existentDateObject)
            {
                $timeSlotsToRemove = $existentDateObject[0]->getAvailabilityPerDates();
                foreach ($timeSlotsToRemove as $timeSlotItem)
                {
                    $this->em->getManager()->remove($timeSlotItem);
                }
                $this->em->getManager()->remove($existentDateObject[0]);
                $this->em->getManager()->flush();
            }
            $availabilityDate = new AvailabilityDate();
            $availabilityDate->setUser($loggedUser);
            $availabilityDate->setDate(\DateTime::createFromFormat('d-m-Y', $date));
            foreach ($jsonData["timeSlots"] as $timeSlot)
            {
                $timeSlotObject = new AvailabilityPerDate();
                $timeSlotObject->setStartHour(\DateTime::createFromFormat('H:i', $timeSlot["startTime"]));
                $timeSlotObject->setEndHour(\DateTime::createFromFormat('H:i', $timeSlot["endTime"]));
                $availabilityDate->addAvailabilityPerDate($timeSlotObject);
                $this->em->getManager()->persist($timeSlotObject);
            }
            $this->em->getManager()->persist($availabilityDate);
            $this->em->getManager()->flush();
            $dayArr = [];
            $dayArr["id"] = $availabilityDate->getId();
            $dayArr["date"] = $availabilityDate->getDate()->format("Y-m-d");
            foreach ($availabilityDate->getAvailabilityPerDates() as $timeSlot)
            {
                $dayArr["availableTimes"][] = ["id" => $timeSlot->getId() , "startTime" => $timeSlot->getStartHour()->format('H:i'), "endTime" => $timeSlot->getEndHour()->format('H:i')];
            }
            $availableDays[] = $dayArr;
        }
        $result["availableDays"] = $availableDays;
        return $this->json($result, 201);
    }
}
