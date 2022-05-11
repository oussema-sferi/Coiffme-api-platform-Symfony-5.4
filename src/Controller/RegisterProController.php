<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\MangoPayService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class RegisterProController extends AbstractController
{

    public function __construct( 
        private UserRepository $userRepository, 
        private SerializerInterface $serializer
        ){}

    public function __invoke(Request $request, MangoPayService $mangoPayService)
    {
        $data = json_decode($request->getContent());
        $user = $this->userRepository->createPro($data, $mangoPayService);
        return $this->json($user, 201);
    }
}
