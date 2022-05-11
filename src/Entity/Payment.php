<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read:Payment']],
    denormalizationContext: ['groups' => ['write:Payment']]
)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Payment", "read:Appointment"])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $cardId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $amount;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $status;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $mangoBuyerId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $mangoBuyerWalletId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $mangoSellerId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $mangoSellerWalletId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $payinId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $transferId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $refundId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $currency;

    #[ORM\OneToMany(mappedBy: 'associatedPayment', targetEntity: Appointment::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["read:Payment"])]
    private $appointments;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $buyerId;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Payment", "write:Payment", "read:Appointment"])]
    private $sellerId;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMangoBuyerId(): ?string
    {
        return $this->mangoBuyerId;
    }

    public function setMangoBuyerId(string $mangoBuyerId): self
    {
        $this->mangoBuyerId = $mangoBuyerId;

        return $this;
    }

    public function getMangoBuyerWalletId(): ?string
    {
        return $this->mangoBuyerWalletId;
    }

    public function setMangoBuyerWalletId(string $mangoBuyerWalletId): self
    {
        $this->mangoBuyerWalletId = $mangoBuyerWalletId;

        return $this;
    }

    public function getMangoSellerId(): ?string
    {
        return $this->mangoSellerId;
    }

    public function setMangoSellerId(string $mangoSellerId): self
    {
        $this->mangoSellerId = $mangoSellerId;

        return $this;
    }

    public function getMangoSellerWalletId(): ?string
    {
        return $this->mangoSellerWalletId;
    }

    public function setMangoSellerWalletId(string $mangoSellerWalletId): self
    {
        $this->mangoSellerWalletId = $mangoSellerWalletId;

        return $this;
    }

    public function getPayinId(): ?string
    {
        return $this->payinId;
    }

    public function setPayinId(string $payinId): self
    {
        $this->payinId = $payinId;

        return $this;
    }

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function setCardId(string $cardId): self
    {
        $this->cardId = $cardId;

        return $this;
    }

    public function getTransferId(): ?string
    {
        return $this->transferId;
    }

    public function setTransferId(?string $transferId): self
    {
        $this->transferId = $transferId;

        return $this;
    }

    public function getRefundId(): ?string
    {
        return $this->refundId;
    }

    public function setRefundId(?string $refundId): self
    {
        $this->refundId = $refundId;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

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
            $appointment->setAssociatedPayment($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getAssociatedPayment() === $this) {
                $appointment->setAssociatedPayment(null);
            }
        }

        return $this;
    }

    public function getBuyerId(): ?string
    {
        return $this->buyerId;
    }

    public function setBuyerId(string $buyerId): self
    {
        $this->buyerId = $buyerId;

        return $this;
    }

    public function getSellerId(): ?string
    {
        return $this->sellerId;
    }

    public function setSellerId(string $sellerId): self
    {
        $this->sellerId = $sellerId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
