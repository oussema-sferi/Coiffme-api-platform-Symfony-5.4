<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function index(#[CurrentUser] ?User $user): Response
    {

        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // $token = ""; // somehow create an API token for $user
        return $this->json([
            'username'  => $user->getUserIdentifier(),
            'roles'  => $user->getRoles(),
        ]);

    }

    // #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    // public function logout()
    // {

    // }
}
