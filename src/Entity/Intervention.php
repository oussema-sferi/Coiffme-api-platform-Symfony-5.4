<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\InterventionRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read:Intervention']],
    denormalizationContext: ['groups' => ['write:Intervention']]
)]
class Intervention
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Intervention","read:User"])]
    private $id;

    #[ORM\Column(type: 'float')]
    #[Groups(["read:Intervention", "write:Intervention","read:User"])]
    private $longitude;

    #[ORM\Column(type: 'float')]
    #[Groups(["read:Intervention", "write:Intervention", "read:User"])]
    private $latitude;

    #[ORM\Column(type: 'float')]
    #[Groups(["read:Intervention", "write:Intervention", "read:User"])]
    private $zone;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'interventions')]
    #[Groups(["read:Intervention", "write:Intervention"])]
    private $user;

    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Intervention", "write:Intervention", "read:User"])]
    private $address;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getZone(): ?float
    {
        return $this->zone;
    }

    public function setZone(float $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }
}
