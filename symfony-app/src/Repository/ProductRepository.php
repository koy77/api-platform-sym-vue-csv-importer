<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Handles database operations for Product entities
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Check if product code already exists
     *
     * @param string $productCode Product code to check
     * @return bool True if exists, false otherwise
     */
    public function productCodeExists(string $productCode): bool
    {
        $product = $this->findOneBy(['productCode' => $productCode]);
        return $product !== null;
    }

    /**
     * Save product to database
     *
     * @param Product $product Product entity to save
     * @return bool True on success, false on failure
     */
    public function save(Product $product): bool
    {
        try {
            $em = $this->getEntityManager();
            $em->persist($product);
            $em->flush();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

