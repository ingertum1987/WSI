<?php

namespace App\Entity;

use App\Model\UploadInterface;
use App\Service\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface
{
    const FOTO_SIZE_W = 200;
    const FOTO_SIZE_H = 200;
    const FOTO_EXT = 'jpeg';
    const FOTO_DIR = 'user/foto/';
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $surname;

    private $parentId;

    private $plainPassword;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="json_array")
     */
    private $roles = [
        'ROLE_USER',
        'ROLE_ADMIN',
        'ROLE_MANAGER'
    ];

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $promoCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bitcoinWallet;

    /**
     * One User has Many Users.
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="parent")
     */
    private $children;

    /**
     * Many Users have One User.
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    private $parent;

    /**
     * Many Transaction have One User.
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="user")
     */
    private $transactions;

    /**
     * Many Notification have One User.
     * @ORM\OneToMany(targetEntity="App\Entity\Notification", mappedBy="user")
     */
    private $notifications;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $hash;

    /**
     * @ORM\Column(type="boolean")
     */
    private $showInvestor;

    /**
     * @ORM\Column(type="boolean")
     */
    private $receivedRequestShowInvestor = false;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $hashShowInvestor;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    private $balance;
    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     */
    private $profitRate = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $foto = false;

    public function __construct()
    {
        $this->isActive = true;
        $this->children = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

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

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getBalance(): ?array
    {
        return $this->balance;
    }

    public function setBalance(?array $balance): self
    {
        $this->balance = $balance;

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

    public function getBitcoinWallet(): ?string
    {
        return $this->bitcoinWallet;
    }

    public function setBitcoinWallet(?string $bitcoinWallet): self
    {
        $this->bitcoinWallet = $bitcoinWallet;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles() : ?array
    {
        return $this->roles;
    }

    public function setRoles($roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getShowInvestor(): ?bool
    {
        return $this->showInvestor;
    }

    public function setShowInvestor(bool $showInvestor): self
    {
        $this->showInvestor = $showInvestor;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

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

    /**
     * @param User $children
     * @return $this
     */
    public function addChildren (User $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * @param Transaction $transactions
     * @return $this
     */
    public function addTransactions (Transaction $transactions)
    {
        $this->transactions[] = $transactions;

        return $this;
    }

    /**
     * @param Notification $notifications
     * @return $this
     */
    public function addNotifications (Notification $notifications)
    {
        $this->notifications[] = $notifications;

        return $this;
    }

    /**
     * @param User $parent
     * @return $this
     */
    public function setParent (User $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
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

    public function getProfitRate(): ?int
    {
        return $this->profitRate;
    }

    public function setProfitRate(?int $profitRate): self
    {
        $this->profitRate = $profitRate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReceivedRequestShowInvestor()
    {
        return $this->receivedRequestShowInvestor;
    }

    /**
     * @param mixed $receivedRequestShowInvestor
     */
    public function setReceivedRequestShowInvestor($receivedRequestShowInvestor): void
    {
        $this->receivedRequestShowInvestor = $receivedRequestShowInvestor;
    }

    /**
     * @return mixed
     */
    public function getHashShowInvestor()
    {
        return $this->hashShowInvestor;
    }

    /**
     * @param mixed $hashShowInvestor
     */
    public function setHashShowInvestor($hashShowInvestor = null): void
    {
        $this->hashShowInvestor = $hashShowInvestor;
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        $fullname=trim($this->getName().' '.$this->getSurname());

        return ($fullname) ?: $this->getUsername();
    }

    /**
     * @return bool
     */
    public function getFoto(): bool
    {
        return $this->foto;
    }

    /**
     * @param bool $foto
     */
    public function setFoto(bool $foto): void
    {
        $this->foto = $foto;
    }

    /**
     * @return string
     */
    public function getFotoFilename(): string
    {
        return $this->username . '.' . self::FOTO_EXT;
    }

    /**
     * @return string
     */
    public function getFotoSubdir(): string
    {
        return substr($this->username, 0, 2) . '/' . substr($this->username, 2, 2) . '/';
    }

    /**
     * @return string
     */
    public function getFotoWithPath(): string
    {
        if ($this->getFoto()) {
            return $this->getFotoFullPath() . $this->getFotoFilename();
        } else {
            return $this->getFotoFullPath() . sprintf('%dx%d.png', self::FOTO_SIZE_W, self::FOTO_SIZE_H);
        }
    }

    /**
     * @return string
     */
    public function getFotoFullPath(): string
    {
        if ($this->getFoto()) {
            return $this->getFotoPath() . $this->getFotoSubdir();
        } else {
            return $this->getFotoPath();
        }
    }

    /**
     * @return string
     */
    public function getFotoPath(): string
    {
        if ($this->getFoto()) {
            return $this->getFotoRootPath() . UploadInterface::IMAGE_SUBDIR;
        } else {
            return $this->getFotoRootPath() . UploadInterface::NOFOTO_SUBDIR;
        }
    }

    /**
     * @return string
     */
    public function getFotoRootPath(): string
    {
        if ($this->getFoto()) {
            return UploadInterface::UPLOAD_DIR . self::FOTO_DIR;
        } else {
            return UploadInterface::UPLOAD_DIR;
        }
    }

    /**
     * @param Filesystem $filesystem
     */
    public function removeFilesAndDirs(Filesystem $filesystem): void
    {
        if (($this->getFoto()) && (strstr($this->getFotoFullPath(), UploadInterface::NOFOTO_SUBDIR) === false)) {
            if (is_file($this->getFotoWithPath())) {
                $filesystem->remove([
                    $this->getFotoWithPath(),
                ]);
            }
            if ($this->getFotoSubdir()) {
                $pathArr = explode('/', preg_replace('%/$%', '', $this->getFotoPath()));
                $folders = explode('/', preg_replace('%/$%', '', $this->getFotoSubdir()));
                if (count($folders) > 1) {
                    while (count($folders) > 0) {
                        $dir = (string)implode('/', array_merge($pathArr, $folders));
                        if ((is_dir($dir)) && (File::isEmptyDir($dir))) {
                            $dirs = [$dir];
                            $filesystem->remove($dirs);
                        }
                        array_pop($folders);
                    }
                }
            }
        }
    }
}
