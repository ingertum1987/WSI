<?php
namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\TransactionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function getInvitedIncomes($user, $status, $filters = null)
    {
        $builder = $this->createQueryBuilder('i')
            ->leftJoin('i.user', 'u')
            ->where('u.parent = :user')
            ->andWhere('i.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
        ;
        if ($filters){
            if ($filters['id']){
                $builder
                    ->andWhere('u.id = :id')
                    ->setParameter('id', $filters['id'])
                ;
            }
            if ($filters['name']){
                $builder
                    ->andWhere('u.name LIKE :name')
                    ->setParameter('name', '%'.$filters['name'].'%')
                ;
            }
            if ($filters['surname']){
                $builder
                    ->andWhere('u.surname LIKE :surname')
                    ->setParameter('surname', '%'.$filters['surname'].'%')
                ;
            }
            if ($filters['createdAt']){
                $start = new \DateTime($filters['createdAt']);
                $end = new \DateTime($filters['createdAt']);
                $end->modify('+1 day');
                $builder
                    ->andWhere('i.createdAt >= :start')
                    ->andWhere('i.createdAt < :end')
                    ->setParameter('start', $start->format('Y-m-d'))
                    ->setParameter('end', $end->format('Y-m-d'))
                ;
            }
            if ($filters['sum']){
                $builder
                    ->andWhere('i.sum = :sum')
                    ->setParameter('sum', $filters['sum'])
                ;
            }
        }

        return $builder->getQuery()->getResult();
    }

    public function getUserTotal($user, $status)
    {
        return $this->createQueryBuilder('i')
            ->select('SUM(i.sum), c.id')
            ->leftJoin('i.currency', 'c')
            ->where('i.user = :user')
            ->setParameter('user', $user)
            ->andWhere('i.status = :status')
            ->setParameter('status', $status)
            ->groupBy('c.id')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getUserBalance($user, $currency, $status)
    {

        $inSum = $this->createQueryBuilder('t')
            ->select('SUM(t.sum)')
            ->where('t.user = :user')
            ->andWhere('t.direction = :direction')
            ->andWhere('t.currency = :currency')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('currency', $currency)
            ->setParameter('status', $status)
            ->setParameter('direction', 'in')
            ->getQuery()
            ->getSingleScalarResult();

        $outSum = $this->createQueryBuilder('t')
            ->select('SUM(t.sum)')
            ->where('t.user = :user')
            ->andWhere('t.currency = :currency')
            ->andWhere('t.direction = :direction')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('currency', $currency)
            ->setParameter('status', $status)
            ->setParameter('direction', 'out')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $inSum - (int) $outSum;
    }

    public function getTotalUserAccount($user, $account, $status)
    {
        $inSum = $this->createQueryBuilder('t')
            ->select('SUM(t.sum)')
            ->where('t.user = :user')
            ->andWhere('t.account = :account')
            ->andWhere('t.direction = :direction')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('account', $account)
            ->setParameter('status', $status)
            ->setParameter('direction', 'in')
            ->getQuery()
            ->getSingleScalarResult();

        $outSum = $this->createQueryBuilder('t')
            ->select('SUM(t.sum)')
            ->where('t.user = :user')
            ->andWhere('t.account = :account')
            ->andWhere('t.direction = :direction')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('account', $account)
            ->setParameter('status', $status)
            ->setParameter('direction', 'out')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $inSum - (float) $outSum;
    }

    public function getTransactionsWithFilters($filters)
    {
        $builder = $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $filters['user'])
            ->leftJoin('t.account', 'a')
            ->leftJoin('t.status', 's')
            ->leftJoin('t.currency', 'c')
            ->orderBy('t.id', 'desc')
        ;

        if (!empty($filters['id'])){
            $builder->andWhere('t.id = :id')->setParameter('id', $filters['id']);
        }

        if (!empty($filters['operationType'])){
            $builder->andWhere('t.type LIKE :type')->setParameter('type', '%' . $filters['operationType'] . '%');
        }

        if (!empty($filters['status'])){
            $builder->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }

        if (!empty($filters['date'])){
            $builder->andWhere('DATE(t.createdAt) = :date')->setParameter('date', $filters['date']);
        }

        if (!empty($filters['sum'])){
            $builder->andWhere('t.sum = :sum')->setParameter('sum', $filters['sum']);
        }

        return $builder->getQuery();
    }

    public function getThisMonthProfitTransaction($user = null)
    {
        $query = $this->createQueryBuilder('t')
            ->where('t.type = :type')
            ->setParameter('type', 'Начисление итогов месяца')
            ->andWhere('MONTH(t.createdAt) = :month')
            ->setParameter('month', date('m'))
            ->andWhere('YEAR(t.createdAt) = :year')
            ->setParameter('year', date('Y'));

        if (!empty($user)){
            $query->andWhere('t.user = :user')->setParameter('user', $user);
        }

        return $query->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param TransactionStatus $status
     * @return Query
     */
    public function getQueryAllByStatus(TransactionStatus $status):Query
    {
        $builder = $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->leftJoin('t.user', 'u')
            ->leftJoin('t.account', 'a')
            ->leftJoin('t.currency', 'c')
            ->orderBy('t.id', 'desc')
            ->setParameter('status', $status)
        ;

        return $builder->getQuery();
    }
}
