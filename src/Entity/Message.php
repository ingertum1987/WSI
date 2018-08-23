<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Message
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $sendUserId;

    /**
     * @ORM\Column(type="integer")
     */
    private $receiveUserId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Status", inversedBy="messages")
     * @ORM\JoinColumn(nullable=true)
     */
    private $status;

    public function getId()
    {
        return $this->id;
    }

    public function getSendUserId(): ?int
    {
        return $this->sendUserId;
    }

    public function setSendUserId(int $sendUserId): self
    {
        $this->sendUserId = $sendUserId;

        return $this;
    }

    public function getReceiveUserId(): ?int
    {
        return $this->receiveUserId;
    }

    public function setReceiveUserId(int $receiveUserId): self
    {
        $this->receiveUserId = $receiveUserId;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @ORM\PrePersist
     */
    public function SetCurrentDateValues()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setCurrentDateToUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }
}
