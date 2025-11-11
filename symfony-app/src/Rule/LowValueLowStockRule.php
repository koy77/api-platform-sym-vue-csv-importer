<?php

namespace App\Rule;

use App\Validator\ProductValidator;

/**
 * Implements rule: Skip items where cost < $5 AND stock < 10
 */
class LowValueLowStockRule implements ImportRuleInterface
{
    private ProductValidator $validator;

    public function __construct(ProductValidator $validator)
    {
        $this->validator = $validator;
    }

    public function shouldSkip(array $productData): array
    {
        $price = $productData['price'] ?? null;
        $stock = $productData['stock'] ?? null;

        // If price or stock is missing, don't skip (let other validators handle it)
        if ($price === null || $stock === null) {
            return ['should_skip' => false, 'reason' => null];
        }

        $priceFloat = (float)$price;
        $stockInt = (int)$stock;

        if ($priceFloat < 5.0 && $stockInt < 10) {
            return [
                'should_skip' => true,
                'reason' => 'Cost < $5 and Stock < 10',
            ];
        }

        return ['should_skip' => false, 'reason' => null];
    }
}

