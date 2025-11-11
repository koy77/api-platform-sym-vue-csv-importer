<?php

namespace App\Rule;

/**
 * Interface for business rules that determine if a product should be imported
 */
interface ImportRuleInterface
{
    /**
     * Check if the product should be skipped
     *
     * @param array $productData Product data array
     * @return array ['should_skip' => bool, 'reason' => string|null]
     */
    public function shouldSkip(array $productData): array;
}

