<?php

namespace App\Transformer;

use App\Entity\Product;
use App\Service\DiscontinuedHandler;
use App\Validator\ProductValidator;

/**
 * Transforms CSV data to Product entity format
 */
class ProductTransformer
{
    private ProductValidator $validator;
    private DiscontinuedHandler $discontinuedHandler;

    public function __construct(
        ProductValidator $validator,
        DiscontinuedHandler $discontinuedHandler
    ) {
        $this->validator = $validator;
        $this->discontinuedHandler = $discontinuedHandler;
    }

    /**
     * Transform CSV row data to Product entity
     *
     * @param array $rowData CSV row data
     * @return array ['product' => Product|null, 'errors' => array]
     */
    public function transform(array $rowData): array
    {
        $errors = [];

        // Clean and extract data
        $productCode = trim($rowData['Product Code'] ?? '');
        $productName = trim($rowData['Product Name'] ?? '');
        $productDesc = trim($rowData['Product Description'] ?? '');

        // Handle stock
        $stock = null;
        if (!empty($rowData['Stock'] ?? '')) {
            $stock = (int)$rowData['Stock'];
        }

        // Handle price
        $price = null;
        if (!empty($rowData['Cost in GBP'] ?? '')) {
            $cleanedPrice = $this->validator->cleanPrice($rowData['Cost in GBP']);
            if ($cleanedPrice !== null) {
                $price = $cleanedPrice;
            } else {
                $errors[] = 'Invalid price format: ' . $rowData['Cost in GBP'];
            }
        }

        // Create product entity
        $product = new Product();
        $product->setProductCode($productCode);
        $product->setProductName($productName);
        $product->setProductDesc($productDesc);
        $product->setStock($stock ?? 0);
        $product->setPrice($price ?? '0.00');
        $product->setAdded(new \DateTimeImmutable());
        $product->setTimestamp(new \DateTimeImmutable());

        // Handle discontinued
        $discontinuedDate = $this->discontinuedHandler->handleDiscontinued($rowData);
        $product->setDiscontinued($discontinuedDate);

        return [
            'product' => $product,
            'errors' => $errors,
        ];
    }
}

