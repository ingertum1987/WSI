<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StatusRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Status
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="status")
     */
    private $messages;

    /**
     * Many notifications have One Status.
     * @ORM\OneToMany(targetEntity="App\Entity\Notification", mappedBy="status")
     */
    private $notifications;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function getId()
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

    /**
     * @ORM\PrePersist
     */
    public function SetCurrentDateValues()
    {
        $this->createdAt = new \DateTime();
    }
}
