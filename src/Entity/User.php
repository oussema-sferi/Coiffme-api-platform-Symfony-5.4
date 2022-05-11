<?php

namespace App\Entity;

use App\Controller\ResetPasswordController;
use App\Services\MangoPayService;
use DateTime;
use App\Controller\MeController;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use App\Controller\RegisterController;
use App\Controller\PostPlaningController;
use App\Controller\RegisterProController;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Action\NotFoundAction;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Controller\CreateUserServicesController;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]

#[ApiResource(
    forceEager: false,
    normalizationContext: ['groups' => ['read:User', "read:Review:User"]],
    denormalizationContext: ['groups' => ['write:User']],
    // security:  "is_granted('ROLE_USER')",
    collectionOperations: [
        'get',
        'register' => [
            'pagination_enabled' => false,
            'path' => '/register',
            'method' => 'post',
            'controller' => RegisterController::class,
        ],
        'register-pro' => [
            'pagination_enabled' => false,
            'path' => '/register-pro',
            'method' => 'post',
            'controller' => RegisterProController::class,
        ],
        'post_services' => [
            'path' => 'post_services',
            'method' => 'post',
            'controller' => CreateUserServicesController::class,
            'openapi_context' => [
                'security' => [
                    ['bearerAuth' => []], 
                ],
            ]
        ],
        'reset-password' => [
            'openapi_context' => [
                'summary'     => "Requests a password reset & send a reset password email to the user",
                'description' => "Requests a password reset & send a reset password email to the user",
                "parameters" => null,
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema'  => [
                                'type'       => 'object',
                                'properties' =>
                                    [
                                        'email'        => ['type' => 'string'],
                                    ],
                            ],
                            'example' => [
                                "email" => "oussema.sferi@gmail.com",
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Email envoyé',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    "message" => "un email de réinitialisation du mot de passe vient de vous être envoyé! Veuillez vérifier votre boîte mail."
                                ],
                            ]
                        ]
                    ],
                    '201' => [
                        'description' => 'Ressource Created',
                    ],
                    '403' => [
                        'description' => 'Unauthorized',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    "message" => "Vous avez déjà demandé un e-mail de réinitialisation du mot de passe. Veuillez vérifier votre e-mail ou réessayer bientôt."
                                ],
                            ]
                        ]
                    ],
                    '404' => [
                        'description' => 'Not Found',
                        'content' => [
                            'application/json' => [
                                'example' => [
                                    "message" => "Cet utilisateur n'existe pas!"
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'pagination_enabled' => false,
            'path' => '/reset-password',
            'method' => 'post',
            'controller' => ResetPasswordController::class,

        
        ]
    ],
    itemOperations: [
        'get' ,'put','delete', 'patch'
     ],


                
)]
#[ApiFilter(SearchFilter::class, properties: [
    'availabilityDates.date' => 'exact',
    'availabilityDates.availabilityPerDates.startHour' => 'partial',
    'roles' => 'exact',
    'interventions.latitude' => 'partial',
    'interventions.longitude' => 'partial'
    
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups("read:User","read:Appointment", "read:Message","read:AvailabilityDate", "read:Favorite", "read:Review:User")]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups([
        "read:User",  
        "read:Appointment",
        "read:AvailabilityDate", 
        "read:Message", 
        "read:Review:User",
        "read:Favorite",
        "write:User",
    ])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "Foo",
            ],
        ],
    )]
    private $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:User","read:Review:User", "write:User", "read:Appointment", "read:AvailabilityDate", "read:Favorite" ,"read:Message"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "Bar",
            ],
        ],
    )]
    private $lastName;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(["read:User","read:Review:User","read:Appointment","write:User","read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "test@mail.com",
            ],
        ],
    )]
    private $email;

    #[ORM\ManyToOne(targetEntity: MediaObject::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(iri: 'http://schema.org/image')]
    #[Groups(["read:User", "write:User"])]
    public ?MediaObject $image = null;

    #[ORM\Column(type: 'json')]
    #[Groups("read:User","read:AvailabilityDate","read:Favorite")]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    #[Groups(["write:User"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "password",
            ],
        ],
    )]
    private $password;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:User", "write:User","read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "06 86 57 90 14",
            ],
        ],
    )]
    private $phone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:User", "write:User","read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "802 954 785 00028",
            ],
        ],
    )]
    private $siret;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(["read:User", "write:User","read:Favorite"])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "date-time",
                "example" => "2001-05-17",
            ],
        ],
    )]
    private $birthDate;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:User", "write:User","read:Favorite"])]
    private $address;

    #[ORM\Column(type: 'boolean')]
    #[Groups(["read:User","read:Favorite"])]
    private $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:User"])]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:User"])]
    private $updatedAt;

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'users', cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'user_service')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false)]
    #[ApiSubresource(
        maxDepth:1
    )]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/services/1",
            ],
            "json_schema_context" => [ // <- MAGIC IS HERE, you can override the json_schema_context here.
                "type" => "array",
                "items" => ["type" => "integer"]
            ]
        ],
    )]
    #[Groups(["read:User", "write:User","read:Favorite"])]
    private $services;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AvailabilityDate::class)]
    #[Groups(["read:User","read:Favorite"])]
    private $availabilityDates;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Appointment::class)]
    #[Groups(["read:User"])]
    private $appointments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Fidelity::class)]
    #[Groups(["read:User"])]
    private $fidelities;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class)]
    #[Groups(["read:User"])]
    private $notifications;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Review::class)]
    #[Groups(["read:Review:User", "read:Favorite"])]
    #[ApiProperty(fetchEager: true)]
    private $reviews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Favorite::class)]
    #[Groups(["read:User", "write:User"])]
    private $favorites;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(["read:User", "write:User"])]
    private $typeUser = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:User", "write:User", "read:Favorite"])]
    private $description;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["read:User", "write:User", "read:Favorite"])]
    private $pictureFile;
    
    #[Groups(["read:User", "write:User"])]
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Intervention::class)]
    private $interventions;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["read:User", "write:User"])]
    private $Diploma;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:User", "write:User"])]
    private $tokenExpo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mangoUserId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mangoWalletId;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Favorite::class, orphanRemoval: true)]
    private $favoritesOwner;

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: Message::class)]
    #[Groups(["read:User"])]
    private $messages;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isDeleted = false;



    public function __construct(
    )
    {
        $this->services = new ArrayCollection();
        $this->availabilityDates = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->fidelities = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->interventions = new ArrayCollection();
        $this->favoritesOwner = new ArrayCollection();
        $this->typeUser = ["Enfant", "Femme", "Homme"];
        $this->messages = new ArrayCollection();
    }


    #[Groups(["read:Appointment", "read:Message", "read:Favorite"])]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): ?self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername()
    {
        return $this->email;
    }


    public function getSalt()
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
         return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;
        
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

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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



    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }


    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {

        $this->updatedAt = $updatedAt;

        return $this;
    }


    public static function createFromPayload($id, array $payload)
    {
        return (new User())->setId($id)->setEmail($payload['username'] ?? '');
        
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
        }

        return $this;
    }

    public function removeService(Service $service): self
    {
        $this->services->removeElement($service);

        return $this;
    }

    /**
     * @return Collection<int, AvailabilityDate>
     */
    public function getAvailabilityDates(): Collection
    {
        return $this->availabilityDates;
    }

    public function addAvailabilityDate(AvailabilityDate $availabilityDate): self
    {
        if (!$this->availabilityDates->contains($availabilityDate)) {
            $this->availabilityDates[] = $availabilityDate;
            $availabilityDate->setUser($this);
        }

        return $this;
    }

    public function removeAvailabilityDate(AvailabilityDate $availabilityDate): self
    {
        if ($this->availabilityDates->removeElement($availabilityDate)) {
            // set the owning side to null (unless already changed)
            if ($availabilityDate->getUser() === $this) {
                $availabilityDate->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments[] = $appointment;
            $appointment->setUser($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getUser() === $this) {
                $appointment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Fidelity>
     */
    public function getFidelities(): Collection
    {
        return $this->fidelities;
    }

    public function addFidelity(Fidelity $fidelity): self
    {
        if (!$this->fidelities->contains($fidelity)) {
            $this->fidelities[] = $fidelity;
            $fidelity->setUser($this);
        }

        return $this;
    }

    public function removeFidelity(Fidelity $fidelity): self
    {
        if ($this->fidelities->removeElement($fidelity)) {
            // set the owning side to null (unless already changed)
            if ($fidelity->getUser() === $this) {
                $fidelity->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews[] = $review;
            $review->setUser($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): self
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
            }
        }

        return $this;
    }

    public function getTypeUser(): ?array
    {
        return $this->typeUser;
    }

    public function setTypeUser(array $typeUser): self
    {
        $this->typeUser = $typeUser;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPictureFile(): ?string
    {
        return $this->pictureFile;
    }

    public function setPictureFile(string $pictureFile): self
    {
        $this->pictureFile = $pictureFile;

        return $this;
    }

    /**
     * @return Collection<int, Intervention>
     */
    public function getInterventions(): Collection
    {
        return $this->interventions;
    }

    public function addIntervention(Intervention $intervention): self
    {
        if (!$this->interventions->contains($intervention)) {
            $this->interventions[] = $intervention;
            $intervention->setUser($this);
        }

        return $this;
    }

    public function removeIntervention(Intervention $intervention): self
    {
        if ($this->interventions->removeElement($intervention)) {
            // set the owning side to null (unless already changed)
            if ($intervention->getUser() === $this) {
                $intervention->setUser(null);
            }
        }

        return $this;
    }

    public function getDiploma(): ?string
    {
        return $this->Diploma;
    }

    public function setDiploma(?string $Diploma): self
    {
        $this->Diploma = $Diploma;

        return $this;
    }

    public function getTokenExpo(): ?string
    {
        return $this->tokenExpo;
    }

    public function setTokenExpo(?string $tokenExpo): self
    {
        $this->tokenExpo = $tokenExpo;

        return $this;
    }

    public function getMangoUserId(): ?string
    {
        return $this->mangoUserId;
    }

    public function setMangoUserId(string $mangoUserId): self
    {
        $this->mangoUserId = $mangoUserId;

        return $this;
    }

    public function getMangoWalletId(): ?string
    {
        return $this->mangoWalletId;
    }

    public function setMangoWalletId(string $mangoWalletId): self
    {
        $this->mangoWalletId = $mangoWalletId;

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavoritesOwner(): Collection
    {
        return $this->favoritesOwner;
    }

    public function addFavoritesOwner(Favorite $favoritesOwner): self
    {
        if (!$this->favoritesOwner->contains($favoritesOwner)) {
            $this->favoritesOwner[] = $favoritesOwner;
            $favoritesOwner->setOwner($this);
        }

        return $this;
    }

    public function removeFavoritesOwner(Favorite $favoritesOwner): self
    {
        if ($this->favoritesOwner->removeElement($favoritesOwner)) {
            // set the owning side to null (unless already changed)
            if ($favoritesOwner->getOwner() === $this) {
                $favoritesOwner->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setSender($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }

        return $this;
    }

    public function isIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(?bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }


}
