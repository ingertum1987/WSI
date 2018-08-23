<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Query;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getInStructureQty($user)
    {
        return $this->createQueryBuilder('u')
            ->where('u.parent = :user')
            ->leftJoin('u.transactions', 'i')
            ->setParameter('user', $user)
            ->having('SUM(i.sum) > 0')
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }

    public function getInvitedQty($user)
    {
        return $this->createQueryBuilder('u')
            ->where('u.parent = :user')
            ->leftJoin('u.transactions', 'i')
            ->setParameter('user', $user)
            ->having('SUM(i.sum) IS NULL')
            ->groupBy('u.id')
            ->getQuery()
            ->getResult();
    }

    public function sendPasswordBySms($phone, $password)
    {
        return file_get_contents('https://smsc.ru/sys/send.php?login=' . urlencode('Gin.team')
            . '&psw=' . urlencode('Gin@0407')
            . '&cost=3&phones=' . urlencode($phone)
            . '&mes=' . urlencode('Ваш пароль (' . $password . ') на сайте wsinvest.ru') . '&translit=1&id=0&fmt=1&charset=utf-8 ');
    }


    public function generatePassword($length = 6){
        $chars = 'abdefjhiknrstyz23456789';
        $numChars = strlen($chars);
        $pass = '';

        for ($i = 0; $i < $length; $i++) {
            $pass .= substr($chars, rand(1, $numChars) - 1, 1);
        }

        return $pass;
    }

    public function generatePromoCode($length = 6){
        $chars = 'ABDEFGJHKLMNRSTWYZ23456789';
        $numChars = strlen($chars);
        $promoCode = '';

        for ($i = 0; $i < $length; $i++) {
            $promoCode .= substr($chars, rand(1, $numChars) - 1, 1);
        }
        $existPromoCode = $this->findOneByPromoCode($promoCode);

        if (!empty($existPromoCode)){
            $this->generatePromoCode();
        } else {
            return $promoCode;
        }
    }

    public function getUsersWithFilters($filters)
    {
        $query = $this->createQueryBuilder('u')
            ->leftJoin('u.parent', 'p')
        ;

        if (!empty($filters['id'])){
            $query->andWhere('u.id = :id')->setParameter('id', $filters['id']);
        }

        if (!empty($filters['name'])){
            $query->andWhere('u.name LIKE :name')->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['surname'])){
            $query->andWhere('u.surname LIKE :surname')->setParameter('surname', '%' . $filters['surname'] . '%');
        }

        if (!empty($filters['username'])){
            $query->andWhere('u.username LIKE :username')->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['parent'])){
            $query->leftJoin('u.parent', 's')
                ->andWhere('s.username LIKE :parent')
                ->setParameter('parent', '%' . $filters['parent'] . '%');
        }

        if (!empty($filters['percent'])){
            $query->andWhere('u.profitRate = :percent')->setParameter('percent', $filters['percent']);
        }

        return $query->getQuery();
    }

    public function getCountUsersWithFilters($filters)
    {
        $query = $this->createQueryBuilder('u')
            ->select('count(u)')
        ;

        if (!empty($filters['id'])){
            $query->andWhere('u.id = :id')->setParameter('id', $filters['id']);
        }

        if (!empty($filters['name'])){
            $query->andWhere('u.name LIKE :name')->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['surname'])){
            $query->andWhere('u.surname LIKE :surname')->setParameter('surname', '%' . $filters['surname'] . '%');
        }

        if (!empty($filters['username'])){
            $query->andWhere('u.username LIKE :username')->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['parent'])){
            $query->leftJoin('u.parent', 's')
                ->andWhere('s.username LIKE :parent')
                ->setParameter('parent', '%' . $filters['parent'] . '%');
        }

        if (!empty($filters['percent'])){
            $query->andWhere('u.profitRate = :percent')->setParameter('percent', $filters['percent']);
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    public function getInvestorsJson()
    {
        $users = $this->createQueryBuilder('u')
            ->select('u.username')
            ->getQuery()
            ->getScalarResult();

        return array_column($users, "username");
    }

    /**
     * @param bool $isAdmin
     * @param User $user
     * @return array
     */
    public function getAllByRole(bool $isAdmin, User $user):array
    {
        $builder = $this->createQueryBuilder('u')
            ->select('u AS user')
            ->addSelect('COUNT(DISTINCT c.id) AS messages_count')
            ->addSelect('COUNT(DISTINCT c_new.id) AS messages_new_count')
            ->addSelect('MAX(c.createdAt) AS createdAt')
            ->where("JSON_CONTAINS(u.roles, :role) = :isAdmin")
            ->leftJoin(Chat::class, 'c', Query\Expr\Join::WITH, 'c.recipient = :user AND c.sender=u.id')
            ->leftJoin(Chat::class, 'c_new', Query\Expr\Join::WITH, 'c_new.recipient = :user AND c_new.sender=u.id AND c_new.readed=0')
            ->groupBy('u.id')
            ->orderBy('createdAt', 'DESC')
        ;
        $builder->setParameters([
            'role'=> '["ROLE_ADMIN"]',
            'isAdmin' => ($isAdmin) ? 0 : 1,
            'user'=>$user->getId()
        ]);

        return $builder->getQuery()->getResult();
    }
}
