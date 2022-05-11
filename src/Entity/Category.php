<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CategoryRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Controller\GetCategoriesController;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    collectionOperations: [
        'get' => [
            'pagination_enabled' => false,
            'path' => '/categories',
            'method' => 'get',
            'controller' => GetCategoriesController::class,
            'openapi_context' => [
                "parameters" =>  [
                    "searchName" => [
                        'name' => 'name',
                        'in' => 'query',
                        "schema" => [
                            'type' => 'string',
                            ]
                        ]
                ],
        ]
        ],
        'post'
        
    ],
    normalizationContext: ['groups' => ['read:Category']],
    denormalizationContext: ['groups' => ['write:Category']]
)]

// #[ApiFilter(SearchFilter::class, properties: [
//                                     'name' => 'exact',
//             ])
// ]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups("read:Category","read:User")]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(["read:Category", "write:Category","read:User", "read:Favorite"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "example" => "Coiffure",
            ],
        ],
    )]
    private $name;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Groups(["read:Category", "write:Category"])]
    #[ApiProperty(
        attributes: [
            "openapi_context" => [
                "type" => "string",
                "format" => "iri-reference",
                "example" => "/api/categories/1",
            ],
        ],
    )]
    private $parent;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade:["persist"])]
    #[Groups(["read:Category"])]

    private $children;

    #[ORM\OneToMany(mappedBy: 'Category', targetEntity: Service::class)]
    #[Groups(["read:Category"])]
    private $services;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->services = new ArrayCollection();
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
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
            $service->setCategory($this);
        }

        return $this;
    }

    public function removeService(Service $service): self
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getCategory() === $this) {
                $service->setCategory(null);
            }
        }

        return $this;
    }
}
