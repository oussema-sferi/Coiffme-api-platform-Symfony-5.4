<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class GetCategoriesController extends AbstractController
{
   
    public function __construct(Private CategoryRepository $rep){}

    public function __invoke(Request $request)
    {
        $name = $request->query->get('name', '');
        $data = $this->rep->getCategoriesTree($name);
        return $this->json($data);
    }
}
