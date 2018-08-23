<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class ChatRepository extends EntityRepository
{
    /**
     * @param User $user1
     * @param User $user2
     * @return array
     */
    public function getMessages(User $user1, User $user2):array
    {
        $builder = $this->createQueryBuilder('c')
            ->select('c')
            ->andWhere('c.recipient = :user1 AND c.sender = :user2')
            ->orWhere('c.recipient = :user2 AND c.sender = :user1')
            ->orderBy('c.createdAt', 'DESC')
            ->setParameters([
                'user1'=>$user1,
                'user2'=>$user2,
            ])
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @param User $sender
     * @return array
     */
    public function getMessagesForMe(User $user, User $sender):array
    {
        $builder = $this->createQueryBuilder('c')
            ->select('c')
            ->andWhere('c.recipient = :user AND c.sender = :sender')
            ->setParameters([
                'user'=>$user,
                'sender'=>$sender,
            ])
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getCountNew(User $user)
    {
        $builder = $this->createQueryBuilder('c')
            ->select('count(c)')
            ->andWhere('c.recipient = :recipient')
            ->andWhere('c.readed = 0')
            ->setParameters([
                'recipient'=>$user,
            ])
        ;

        return $builder->getQuery()->getSingleScalarResult();
    }
}
