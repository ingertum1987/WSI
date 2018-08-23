<?php

namespace App\Entity;

use App\Model\IdCreatedChangedTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ChatRepository")
 */
class Chat
{
    use IdCreatedChangedTrait;

    const TEXT_MAX_LENGTH = 500;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="recipient_id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $recipient;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sender_id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $sender;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=Chat::TEXT_MAX_LENGTH)
     */
    private $text;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $readed = false;

    public function __construct()
    {
        $this->createdAt=new \DateTime();
        $this->changedAt=new \DateTime();
    }

    /**
     * @return User
     */
    public function getRecipient(): User
    {
        return $this->recipient;
    }

    /**
     * @param User $recipient
     */
    public function setRecipient(User $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return User
     */
    public function getSender(): User
    {
        return $this->sender;
    }

    /**
     * @param User $sender
     */
    public function setSender(User $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isReaded(): bool
    {
        return $this->readed;
    }

    /**
     * @param bool $readed
     */
    public function setReaded(bool $readed): void
    {
        $this->readed = $readed;
    }
}
