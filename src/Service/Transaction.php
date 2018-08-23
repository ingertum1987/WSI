<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Currency;
use App\Entity\TransactionStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManager;

class Transaction
{
    const CURRENCY_USD = 1;
    const STATUS_SUCCESS = 2;
    const DIRECTION_OUT = 'out';
    const DIRECTION_IN = 'in';
    const ACCOUNT_PERSONAL = 1;
    const ACCOUNT_INVEST = 2;
    const ACCOUNT_REFFERAL = 3;

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(User $user, $accountId, string $direction, float $sum)
    {
        $currency = $this->entityManager->getRepository(Currency::class)->find(self::CURRENCY_USD);
        $status = $this->entityManager->getRepository(TransactionStatus::class)->find(self::STATUS_SUCCESS);
        $account = $this->entityManager->getRepository(Account::class)->find($accountId);
        $transaction = new \App\Entity\Transaction();
        $transaction->setUser($user);
        $transaction->setStatus($status);
        $transaction->setDirection($direction);
        $transaction->setAccount($account);
        $transaction->setSum($sum);
        $transaction->setCurrency($currency);

        return $transaction;
    }

    public function getSumUserByAccount(User $user, $account)
    {
        return $this->entityManager->getRepository(\App\Entity\Transaction::class)->getTotalUserAccount(
            $user, $account, self::STATUS_SUCCESS
        );
    }
}