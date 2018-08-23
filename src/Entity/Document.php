<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Document
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
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="пожалуйста загрузите файл как изображение.")
     * @Assert\File(mimeTypes={
     *     "image/jpeg",
     *     "image/png",
     *     "image/pjpeg",
     *     })
     */
    private $img;

    /**
     * @ORM\Column(type="string")
     *
     * @Assert\NotBlank(message="пожалуйста загрузите документ как файл.")
     * @Assert\File(mimeTypes={
     *     "application/pdf",
     *     "image/jpeg",
     *     "image/png",
     *     "image/pjpeg",
     *     "application/msword",
     *     "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
     *     "application/vnd.ms-excel",
     *     "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *     "application/vnd.ms-powerpoint",
     *     "application/vnd.openxmlformats-officedocument.presentationml.presentation"
     *     })
     */
    private $file;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

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

    public function getImg()
    {
        return $this->img;
    }

    public function setImg($img)
    {
        $this->img = $img;

        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;

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

    /**
     * @ORM\PrePersist
     */
    public function SetCurrentDateValues()
    {
        $this->createdAt = new \DateTime();
    }
}
