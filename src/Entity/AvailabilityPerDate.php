<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AvailabilityPerDateRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Context;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: AvailabilityPerDateRepository::class)]
#[ApiResource(
    /*    security:  "is_granted('ROLE_USER')", */
       collectionOperations: [ "post", "get"],
       itemOperations: [
        "put", "delete","get"
     ],
     normalizationContext: ['groups' => ['read:AvailabilityPerDate']],
    denormalizationContext: ['groups' => ['write:AvailabilityPerDate']]
   )]
#[ApiFilter(SearchFilter::class, properties: [
    'startHour' => 'exact',
])]
class AvailabilityPerDate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:AvailabilityPerDate","read:AvailabilityDate", "write:AvailabilityDate", "read:User", "read:Appointment","read:Favorite"])]
    private $id;

    #[ORM\Column(type: 'time')]
    #[Groups(["write:AvailabilityPerDate","read:AvailabilityPerDate","read:AvailabilityDate", "write:AvailabilityDate", "read:User","read:Appointment","read:Favorite"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "time",
                "example" => "10:00",
            ],
        ],
    )]
    private $startHour;

    #[ORM\Column(type: 'time')]
    #[Groups(["read:AvailabilityPerDate","write:AvailabilityPerDate","read:AvailabilityDate", "write:AvailabilityDate", "read:User", "read:Appointment","read:Favorite"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'H:i'])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "time",
                "example" => "11:00",
            ],
        ],
    )]
    private $endHour;

    #[ORM\ManyToOne(targetEntity: AvailabilityDate::class, inversedBy: 'availabilityPerDates', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:Appointment","read:AvailabilityPerDate","write:AvailabilityPerDate"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/availability_dates/1",
            ],
        ],
    )]
    private $availabilityDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    
    public function getStartHour(): ?\DateTimeInterface
    {
        return $this->startHour;
    }

    
    public function setStartHour(\DateTimeInterface $startHour): self
    {
        $this->startHour = $startHour;

        return $this;
    }

    public function getEndHour(): ?\DateTimeInterface
    {
        return $this->endHour;
    }

    public function setEndHour(\DateTimeInterface $endHour): self
    {
        $this->endHour = $endHour;

        return $this;
    }

    public function getAvailabilityDate(): ?AvailabilityDate
    {
        return $this->availabilityDate;
    }

    public function setAvailabilityDate(?AvailabilityDate $availabilityDate): self
    {
        $this->availabilityDate = $availabilityDate;

        return $this;
    }
}
