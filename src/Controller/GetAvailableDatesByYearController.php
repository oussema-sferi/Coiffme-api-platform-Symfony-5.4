<?php

namespace App\Controller;

use App\Entity\AvailabilityDate;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AvailabilityDateRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
#[Route('/api/planning_by_year', name: 'planing_by_year', methods:'GET')]
class GetAvailableDatesByYearController extends AbstractController
{
    
    public function __invoke(AvailabilityDateRepository $rep, Request $request)
    {

        $year = $request->query->get('year', '');
        $user = $request->query->get('user', '');
        $data = $rep->findAvailableDateByYear($year, $user);
        return $this->json($data);
    }
}
