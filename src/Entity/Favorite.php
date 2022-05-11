<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FavoriteRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ApiResource(
    forceEager: false,
    normalizationContext: ['groups' => ['read:Favorite']],
    denormalizationContext: ['groups' => ['write:Favorite']]
)]
#[ApiFilter(SearchFilter::class, properties: ['user' => 'exact', 'owner' => 'exact'])]

class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Favorite", "write:Favorite", "read:User"])]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'favorites')]
    #[Groups(["read:Favorite", "write:Favorite", "read:User"])]
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

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:Favorite", "write:Favorite"])]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:Favorite", "write:Favorite"])]
    private $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'favoritesOwner')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:Favorite", "write:Favorite", "read:User"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/users/2",
            ],
        ],
    )]
    private $owner;

/*     #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/users/2",
            ],
        ],
    )]
    #[Groups(["read:Favorite", "write:Favorite"])]
    #[MaxDepth(1)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $owner; */

    public function getId(): ?int
    {
        return $this->id;
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

     public function getCreatedUser(): ?User
    {
        return $this->createdUser;
    }

    public function setCreatedUser(?User $createdUser): self
    {
        $this->createdUser = $createdUser;

        return $this;
    }
/* 
    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    } */

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
