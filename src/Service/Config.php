<?php

namespace App\Service;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManager;

class Config
{
    private $entityManager;
    private $defaultAdminEmail;

    /**
     * @param EntityManager $entityManager
     * @param string $defaultAdminEmail
     */
    public function __construct(EntityManager $entityManager, string $defaultAdminEmail)
    {
        $this->entityManager = $entityManager;
        $this->defaultAdminEmail = $defaultAdminEmail;
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        /** @var ConfigurationRepository $repo */
        $repo = $this->entityManager->getRepository(Configuration::class);
        if (!empty($repo->findOneByName('adminEmail'))) {
            return $repo->findOneByName('adminEmail')->getValue();
        } else {
            return $this->defaultAdminEmail;
        }
    }
}