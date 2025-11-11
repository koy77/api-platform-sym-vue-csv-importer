<?php

namespace App\Rule;

/**
 * Implements rule: Skip items where cost > $1000
 */
class HighValueRule implements ImportRuleInterface
{
    public function shouldSkip(array $productData): array
    {
        $price = $productData['price'] ?? null;

        // If price is missing, don't skip (let other validators handle it)
        if ($price === null) {
            return ['should_skip' => false, 'reason' => null];
        }

        $priceFloat = (float)$price;

        if ($priceFloat > 1000.0) {
            return [
                'should_skip' => true,
                'reason' => 'Cost > $1000',
            ];
        }

        return ['should_skip' => false, 'reason' => null];
    }
}

