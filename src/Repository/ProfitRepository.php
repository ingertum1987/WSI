<?php

namespace App\Repository;

use App\Entity\Profit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Profit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Profit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Profit[]    findAll()
 * @method Profit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfitRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Profit::class);
    }

    public function getThisMonthProfit()
    {
        return $this->createQueryBuilder('p')
            ->andWhere('MONTH(p.createdAt) = :month')
            ->setParameter('month', date('m'))
            ->andWhere('YEAR(p.createdAt) = :year')
            ->setParameter('year', date('Y'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getPastMonthProfit()
    {
        return $this->createQueryBuilder('p')
            ->andWhere('MONTH(p.createdAt) = :month')
            ->setParameter('month', date('m', strtotime("first day of last month")))
            ->andWhere('YEAR(p.createdAt) = :year')
            ->setParameter('year', date('Y', strtotime("first day of last month")))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getProfitByMonthYear($month, $year)
    {
        if (strlen($month) == 2){
            $separator = $month;
        } else {
            $separator = '0' . $month;
        }
        return $this->createQueryBuilder('p')
            ->where('MONTH(p.createdAt) = :month')
            ->andWhere('YEAR(p.createdAt) = :year')
            ->setParameter(':month', $separator)
            ->setParameter(':year', $year)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getInThisMonth($start, $end)
    {
        $builder=$this->createQueryBuilder('p')
            ->select('p')
            ->andWhere('p.createdAt >= :start')
            ->andWhere('p.createdAt < :end')
            ->setParameters([
                'start'=>$start,
                'end'=>$end
            ])
        ;
        return $builder->getQuery()->getOneOrNullResult();
    }
}
