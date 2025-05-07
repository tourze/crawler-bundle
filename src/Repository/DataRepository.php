<?php

namespace CrawlerBundle\Repository;

use CrawlerBundle\Entity\Data;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * @method Data|null find($id, $lockMode = null, $lockVersion = null)
 * @method Data|null findOneBy(array $criteria, array $orderBy = null)
 * @method Data[]    findAll()
 * @method Data[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
#[Autoconfigure(public: true)]
class DataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Data::class);
    }
}
