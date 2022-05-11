<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\MessageRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Controller\LatestMessageController;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['read:Message']],
    denormalizationContext: ['groups' => ['write:Message']],
    collectionOperations:[
        'statistic_appointment_services' => [
            'pagination_enabled' => false,
            'path' => '/filter_latest_message',
            'method' => 'GET',
            'controller' => LatestMessageController::class,
            'read' => true,
          
            'openapi_context' => [
                    // 'security' => [['bearerAuth' => []]],
                    "parameters" =>  [
                        "userParams" => [
                            'name' => 'user',
                            'in' => 'query',
                            "schema" => [
                                'type' => 'int',
                                ]
                        ]
                
                    ],
            ]
        ],
        'get','post'
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'recipient' => 'exact',
    'sender' => 'exact'
])]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["read:Message"])]
    private $id;

/*     #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(["read:Message", "write:Message", 'read:Message:User'])]
    private $sender; */

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(["read:Message", "write:Message"])]
    private $recipient;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["read:Message", "write:Message"])]
    private $message;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(["read:Message"])]
    private $createdAt;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(["read:Message", "write:Message"])]
    private $isRead = false;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["read:Message", "write:Message"])]
    private $sender;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

/*     public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    } */

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }
}
