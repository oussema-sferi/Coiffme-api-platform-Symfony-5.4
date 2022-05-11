<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReviewRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['user' => 'exact', 'owner' => 'exact'])]
#[ApiResource(
    forceEager: false,
    normalizationContext: ['groups' => ['read:Review', 'read:Review:User']],
    denormalizationContext: ['groups' => ['write:Review']]
)]

class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Review", "read:Favorite"])]
    private $id;

    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Review", "write:Review", 'read:Review:User', "read:Favorite"])]
    private $stars;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Review", "write:Review",'read:Review:User', "read:Favorite"])]
    private $message;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:Review", 'read:Review:User',"read:Favorite"])]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:Review", 'read:Review:User', "read:Favorite"])]
    private $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:Review", "write:Review"])]
    private $user;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(["read:Review:User", "read:Review", "write:Review", "read:Favorite"])]
    private $owner;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStars(): ?int
    {
        return $this->stars;
    }

    public function setStars(int $stars): self
    {
        $this->stars = $stars;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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
}
