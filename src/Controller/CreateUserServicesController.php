<?php

namespace App\Controller;



use App\Repository\UserRepository;
use App\Repository\ServiceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class CreateUserServicesController extends AbstractController
{
    
    
    public function __construct(
        private SerializerInterface $serializer,
        private UserRepository $user,
        private ServiceRepository $service,
        private Security $security,
        private OpenApiFactoryInterface $decorated
        
    ){}
    
    public function __invoke($data, Request $request, ManagerRegistry $doctrine)
    {

        
     
     
        $manager = $doctrine->getManager();
        
        $data = json_decode($request->getContent());
       
        $user = $this->user->find($this->security->getUser()->getId());
         
        foreach ($data->services as  $service) {
            $service_id = trim($service, "/api/services/");
            $service = $this->service->find($service_id);
            $user->addService($service);
            $manager->persist($user);
            
        }
        $manager->flush();
        
        return $this->json([$user], 201);
        
    }
}