<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiProperty;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read:Service']],
    denormalizationContext: ['groups' => ['write:Service']]
)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Category", "read:Service","read:User", 'write:User',"read:Appointment" ,"read:Favorite"])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Category", "read:Service", "write:Service","read:User", 'write:User', "read:Appointment" ,"read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "Shampoing",
            ],
        ],
    )]
    private $name;

    #[ORM\Column(type: 'float')]
    #[Groups(["read:Category", "read:Service", "write:Service", "read:User", "read:Appointment","read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "float",
                "example" => "29",
            ],
        ],
    )]
    protected $unitPrice;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'services', cascade: ['persist', 'remove'])]
    private $users;

    #[ORM\OneToMany(mappedBy: 'service', targetEntity: AppointmentService::class)]
    private $appointmentServices;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["read:Service", "write:Service", "read:User","read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/categories/3",
            ],
        ],
    )]
    private $Category;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(["read:Category", "read:Service", "write:Service","read:User", 'write:User', "read:Appointment","read:Favorite"])]
    private $description;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Category", "read:Service", "write:Service","read:User", 'write:User', "read:Appointment","read:Favorite"])]
    private $color;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->appointmentServices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addService($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeService($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, AppointmentService>
     */
    public function getAppointmentServices(): Collection
    {
        return $this->appointmentServices;
    }

    public function addAppointmentService(AppointmentService $appointmentService): self
    {
        if (!$this->appointmentServices->contains($appointmentService)) {
            $this->appointmentServices[] = $appointmentService;
            $appointmentService->setService($this);
        }

        return $this;
    }

    public function removeAppointmentService(AppointmentService $appointmentService): self
    {
        if ($this->appointmentServices->removeElement($appointmentService)) {
            // set the owning side to null (unless already changed)
            if ($appointmentService->getService() === $this) {
                $appointmentService->setService(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->Category;
    }

    public function setCategory(?Category $Category): self
    {
        $this->Category = $Category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }
}
