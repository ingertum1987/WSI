<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserPanel
{
    protected $entityManager;
    protected $user;

    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->user = $tokenStorage;
    }

    public function getNotifications()
    {
        $repository = $this->entityManager->getRepository('App:Notification');

        return $repository->findBy(['status' => 1, 'user' => $this->user->getToken()->getUser()]);
    }

    public function getReturnRate() {
        $repository = $this->entityManager->getRepository('App:Configuration');
        $returnRate = $repository->findOneByName('returnRate');

        if ($returnRate) {
            return (float)$returnRate->getValue();
        }

        return 0;
    }

    public function getInvestReturnRate($profitRate) {
        $returnRate = $this->getReturnRate();

        return $returnRate - ($returnRate / 100 * (100 - $profitRate));
    }

    public function getMessages()
    {
        $repository = $this->entityManager->getRepository('App:Message');

        return $repository->findByStatus(1);
    }

    public function getInvestAccountBalance()
    {
        $transactionRepository = $this->entityManager->getRepository('App:Transaction');
        $accountRepository = $this->entityManager->getRepository('App:Account');
        $account = $accountRepository->find(2);
        $transactionStatusRepository = $this->entityManager->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);

        if (!empty($this->user)){
            $balance = $transactionRepository->getTotalUserAccount($this->user->getToken()->getUser(), $account, $status);

            return $balance;
        } else {
            return 0;
        }
    }

    public function getReferalAccountBalance()
    {
        $transactionRepository = $this->entityManager->getRepository('App:Transaction');
        $accountRepository = $this->entityManager->getRepository('App:Account');
        $account = $accountRepository->find(3);
        $transactionStatusRepository = $this->entityManager->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);

        if (!empty($this->user)){
            $balance = $transactionRepository->getTotalUserAccount($this->user->getToken()->getUser(), $account, $status);

            return (int) $balance;
        } else {
            return 0;
        }
    }

    public function getPersonalAccountBalance()
    {
        $transactionRepository = $this->entityManager->getRepository('App:Transaction');
        $accountRepository = $this->entityManager->getRepository('App:Account');
        $account = $accountRepository->find(1);
        $transactionStatusRepository = $this->entityManager->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);

        if (!empty($this->user)){
            $balance = $transactionRepository->getTotalUserAccount($this->user->getToken()->getUser(), $account, $status);

            return (int) $balance;
        } else {
            return 0;
        }
    }

    public function getBalance()
    {
        $transactionRepository = $this->entityManager->getRepository('App:Transaction');
        $currencyRepository = $this->entityManager->getRepository('App:Currency');
        $currency = $currencyRepository->find(1);
        $transactionStatusRepository = $this->entityManager->getRepository('App:TransactionStatus');
        $status = $transactionStatusRepository->find(2);

        if (!empty($this->user)){
            $balance = $transactionRepository->getUserBalance($this->user->getToken()->getUser(), $currency, $status);

            return (int) $balance;
        } else {
            return 0;
        }
    }
}
