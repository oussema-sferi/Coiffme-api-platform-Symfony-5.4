<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Repository\AppointmentServiceRepository;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class CountAppointmentServiceController extends AbstractController
{
    public function __construct(private Security $security){}
    public function __invoke(AppointmentServiceRepository $rep, Request $request)
    {
        $user = $request->query->get('user');
        $data['today']['total_turnover'] = $rep->statServiceAppointement($user, true, 'today midnight', 'now');
        $data['today']['per_services'] = $rep->statServiceAppointement($user, false, 'today midnight', 'now');
        $data['this_week']['total_turnover'] = $rep->statServiceAppointement($user, true,  'monday this week midnight', 'now');
        $data['this_week']['per_services'] = $rep->statServiceAppointement($user, false,  'monday this week midnight', 'now');
        $data['this_month']['total_turnover'] = $rep->statServiceAppointement($user, true,  'first day of this month midnight', 'now');
        $data['this_month']['per_services'] = $rep->statServiceAppointement($user, false,  'first day of this month midnight', 'now');
        $data['last_month']['total_turnover'] = $rep->statServiceAppointement($user, true,  'first day of last month midnight', 'last day of last month midnight');
        $data['last_month']['per_services'] = $rep->statServiceAppointement($user, false,  'first day of this month midnight', 'last day of last month midnight');
        $data['this_year']['total_turnover'] = $rep->statServiceAppointement($user, true,  'first day of January', 'now');
        $data['this_year']['per_services'] = $rep->statServiceAppointement($user, false,  'first day of January', 'now');
        return $this->json($data);
    }
}
