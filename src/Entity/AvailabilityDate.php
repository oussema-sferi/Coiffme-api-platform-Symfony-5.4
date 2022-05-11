<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Controller\MultipleDatesController;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AvailabilityDateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Context;
use App\Controller\GetAvailableDatesByYearController;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;


#[ORM\Entity(repositoryClass: AvailabilityDateRepository::class)]
#[ApiResource(
   
    // security:  "is_granted('ROLE_PRO')",
    collectionOperations: [
        "post" => [
            // "security" => "is_granted('ROLE_ADMIN')",
            'openapi_context' => [
                'security' => [
                    ['bearerAuth' => []], 
                    "is_granted('ROLE_PRO')"
                ],
        ]
        ],
        'planing_by_year' => [
            'pagination_enabled' => false,
            'path' => '/planing_by_year',
            'method' => 'GET',
            'controller' => GetAvailableDatesByYearController::class,
            'read' => true,
          
            'openapi_context' => [
                    'security' => [['bearerAuth' => []]],
                    "parameters" =>  [
                        "yearParam" => [
                            'name' => 'year',
                            'in' => 'query',
                            "schema" => [
                                'type' => 'string',
                                ]
                            ],
                        "userParam" => [
                            'name' => 'user',
                            'in' => 'query',
                            "schema" => [
                                'type' => 'string',
                                ]
                            ]
                    ],
            ]
        ],
        'create_multiple_dates' => [
            "security" => "is_granted('ROLE_USER')",
            'openapi_context' => [
                'security' => [['bearerAuth' => []]],
                'summary'     => "Saves AvailabilityDate ressources and associated AvailabilityPerDate ressources per selected month",
                'description' => "Saves AvailabilityDate ressources and associated AvailabilityPerDate ressources per selected month",
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema'  => [
                                'type'       => 'object',
                                'properties' =>
                                    [
                                        'month'        => ['type' => 'string'],
                                        'days' => ['type' => 'array'],
                                        'timeSlots' => ['type' => 'array'],
                                    ],
                            ],
                            'example' => [
                                "month" => "09",
                                "days" => [2, 4, 7],
                              "timeSlots" => [
                                 ["startTime" => "08:00", "endTime" => "09:00"],
                                  ["startTime" => "12:30", "endTime" => "15:00"]
                               ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'AvailabilityDate resources and associated AvailabilityPerDate ressources created',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    "availableDays" => [
                                        [
                                            "id" => 90,
                                            "date" => "2022-01-03",
                                            "availableTimes" => [
                                                [
                                                    "id"=> 179,
                                                    "startTime" => "08:00",
                                                    "endTime" => "09:00"
                                                ],
                                                [
                                                    "id"=> 180,
                                                    "startTime" => "14:00",
                                                    "endTime" => "15:00"
                                                ]
                                            ]
                                        ],
                                        [
                                            "id" => 91,
                                            "date" => "2022-01-08",
                                            "availableTimes" => [
                                                [
                                                    "id"=> 185,
                                                    "startTime" => "08:00",
                                                    "endTime" => "09:00"
                                                ],
                                                [
                                                    "id"=> 186,
                                                    "startTime" => "14:00",
                                                    "endTime" => "15:00"
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'pagination_enabled' => false,
            'path' => '/create_multiple_dates',
            'method' => 'POST',
            'controller' => MultipleDatesController::class,
        ],
        "get"
    ],
    itemOperations: [
       "delete", "get"
    ],
    normalizationContext: ['groups' => ['read:AvailabilityDate']],
    denormalizationContext: ['groups' => ['write:AvailabilityDate']]

)]
#[ApiFilter(SearchFilter::class, properties: [
        'date' => 'exact',
        'availabilityPerDates.startHour' => 'partial',
        'user.roles' => 'exact',
])]
class AvailabilityDate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:AvailabilityDate", "read:User", "read:Favorite"])]
    private $id;

    #[ORM\Column(type: 'date')]
    #[Groups(["read:AvailabilityDate", "write:AvailabilityDate", "read:User", "read:Appointment", "read:Favorite"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "date-time",
                "example" => "2022-05-17",
            ],
        ],
    )]
    private $date;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'availabilityDates', cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:AvailabilityDate", "write:AvailabilityDate"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/users/1",
            ],
        ],
    )]
    private $user;

    #[ORM\OneToMany(mappedBy: 'availabilityDate', targetEntity: AvailabilityPerDate::class, cascade: ['persist', 'remove'])]
    #[Groups(["read:AvailabilityDate", "write:AvailabilityDate", "read:User", "read:Favorite"])]
    private $availabilityPerDates;

    public function __construct()
    {
        $this->availabilityPerDates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    /**
     * @return Collection<int, AvailabilityPerDate>
     */
    public function getAvailabilityPerDates(): Collection
    {
        return $this->availabilityPerDates;
    }

    public function addAvailabilityPerDate(AvailabilityPerDate $availabilityPerDate): self
    {
        if (!$this->availabilityPerDates->contains($availabilityPerDate)) {
            $this->availabilityPerDates[] = $availabilityPerDate;
            $availabilityPerDate->setAvailabilityDate($this);
        }

        return $this;
    }

    public function removeAvailabilityPerDate(AvailabilityPerDate $availabilityPerDate): self
    {
        if ($this->availabilityPerDates->removeElement($availabilityPerDate)) {
            // set the owning side to null (unless already changed)
            if ($availabilityPerDate->getAvailabilityDate() === $this) {
                $availabilityPerDate->setAvailabilityDate(null);
            }
        }

        return $this;
    }
}
