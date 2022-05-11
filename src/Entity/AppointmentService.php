<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use App\Repository\AppointmentServiceRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\CountAppointmentServiceController;

#[ORM\Entity(repositoryClass: AppointmentServiceRepository::class)]
#[ORM\HasLifecycleCallbacks]

#[ApiResource(
    normalizationContext: ['groups' => ['read:AppointmentService']],
    denormalizationContext: ['groups' => ['write:AppointmentService']],
    collectionOperations:[
        'statistic_appointment_services' => [
            'pagination_enabled' => false,
            'path' => '/statistic_appointment_services',
            'method' => 'GET',
            'controller' => CountAppointmentServiceController::class,
            'read' => true,
          
            'openapi_context' => [
                    // 'security' => [['bearerAuth' => []]],
                    "parameters" =>  [
                        "userParams" => [
                            'name' => 'user',
                            'in' => 'query',
                            "schema" => [
                                'type' => 'int',
                                ]
                        ]
                
                    ],
            ]
        ],
        'post'
    ]
)]
class AppointmentService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:AppointmentService","read:Appointment"])]
    private $id;

    #[ORM\Column(type: 'integer')]
    #[Groups(["read:AppointmentService","write:AppointmentService","read:Appointment", "write:Appointment"])]
    private $quantity;

    #[ORM\ManyToOne(targetEntity: Appointment::class, inversedBy: 'appointmentServices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:AppointmentService","write:AppointmentService"])]
    private $appointment;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'appointmentServices')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:AppointmentService","write:AppointmentService","read:Appointment", "write:Appointment"])]
    private $service;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:AppointmentService"])]
    private $createdAt;

    #[ORM\Column(type: 'float')]
    #[Groups(["read:AppointmentService","write:AppointmentService"])]
    private $totalPrice;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): self
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    
    public function setTotalPrice(?float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    #[ORM\PrePersist]
    public function calculateTotalPrice()
    {
        $this->setTotalPrice($this->quantity * $this->service->getUnitPrice());
        
    }
}
