<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /** @return list<Product> */
    public function search(?string $query, bool $inStockOnly): array
    {
        $builder = $this->createQueryBuilder('product')
            ->orderBy('product.id', 'DESC');

        if (null !== $query && '' !== trim($query)) {
            $builder
                ->andWhere('LOWER(product.name) LIKE LOWER(:query)')
                ->setParameter('query', '%'.trim($query).'%');
        }

        if ($inStockOnly) {
            $builder->andWhere('product.stock > 0');
        }

        return $builder->getQuery()->getResult();
    }
}
