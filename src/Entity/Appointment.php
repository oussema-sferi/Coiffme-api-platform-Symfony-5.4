<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\AppointmentRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read:Appointment']],
    denormalizationContext: ['groups' => ['write:Appointment']]
)]
#[ApiFilter(SearchFilter::class, properties: [
                                    'user' => 'exact',
                                    'createdUser' => 'exact',
                                    'availableDateHour.availabilityDate.date' => 'exact',
                                    'owner' => 'exact',
                                    'user.interventions.longitude' => 'partial',
                                    'user.interventions.latitude' => 'partial'
])]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Appointment"])]
    private $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private $updatedAt;

    #[ORM\OneToMany(mappedBy: 'appointment', targetEntity: AppointmentService::class, cascade: ["persist"])]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $appointmentServices;

    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $user;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $userStatus;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $status = 'pending';

    #[ORM\ManyToOne(targetEntity: AvailabilityPerDate::class)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $availableDateHour;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $promoCode;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $message;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $owner;
    
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $address;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $phone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $fullName;

    #[ORM\ManyToOne(targetEntity: Payment::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["read:Appointment", "write:Appointment"])]
    private $associatedPayment;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(["read:Appointment"])]
    private $calculateStartDate;



    public function __construct()
    {
        $this->appointmentServices = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $appointmentService->setAppointment($this);
        }

        return $this;
    }

    public function removeAppointmentService(AppointmentService $appointmentService): self
    {
        if ($this->appointmentServices->removeElement($appointmentService)) {
            // set the owning side to null (unless already changed)
            if ($appointmentService->getAppointment() === $this) {
                $appointmentService->setAppointment(null);
            }
        }

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAvailableDateHour(): ?AvailabilityPerDate
    {
        return $this->availableDateHour;
    }

    public function setAvailableDateHour(?AvailabilityPerDate $availableDateHour): self
    {
        $this->availableDateHour = $availableDateHour;

        return $this;
    }

    public function getPromoCode(): ?string
    {
        return $this->promoCode;
    }

    public function setPromoCode(?string $promoCode): self
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getfullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAssociatedPayment(): ?Payment
    {
        return $this->associatedPayment;
    }

    public function setAssociatedPayment(?Payment $associatedPayment): self
    {
        $this->associatedPayment = $associatedPayment;

        return $this;
    }

    public function getUserStatus(): ?string
    {
        return $this->userStatus;
    }

    public function setUserStatus(?string $userStatus): self
    {
        $this->userStatus = $userStatus;

        return $this;
    }

    public function getCalculateStartDate(): ?\DateTimeInterface
    {
        return $this->calculateStartDate;
    }

    public function setCalculateStartDate(?\DateTimeInterface $calculateStartDate): self
    {
        $this->calculateStartDate = $calculateStartDate;

        return $this;
    }
}
