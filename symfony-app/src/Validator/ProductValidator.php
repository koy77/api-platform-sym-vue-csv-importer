<?php

namespace App\Validator;

/**
 * Validates product data format and completeness
 */
class ProductValidator
{
    /**
     * Validate product data from CSV row
     *
     * @param array $rowData Product data from CSV
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(array $rowData): array
    {
        $errors = [];

        // Check required fields
        if (empty($rowData['Product Code'] ?? '')) {
            $errors[] = 'Product Code is required';
        }

        if (empty($rowData['Product Name'] ?? '')) {
            $errors[] = 'Product Name is required';
        }

        if (empty($rowData['Product Description'] ?? '')) {
            $errors[] = 'Product Description is required';
        }

        // Validate stock (if provided, must be numeric)
        if (isset($rowData['Stock']) && $rowData['Stock'] !== '') {
            $stock = $rowData['Stock'];
            if (!is_numeric($stock) || (int)$stock < 0) {
                $errors[] = 'Stock must be a non-negative integer';
            }
        }

        // Validate price (if provided, must be numeric after cleaning)
        if (isset($rowData['Cost in GBP']) && $rowData['Cost in GBP'] !== '') {
            $price = $this->cleanPrice($rowData['Cost in GBP']);
            if ($price === null || !is_numeric($price) || (float)$price < 0) {
                $errors[] = 'Cost in GBP must be a valid positive number';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Clean price string by removing currency symbols and formatting
     *
     * @param string $price Raw price string
     * @return string|null Cleaned price or null if invalid
     */
    public function cleanPrice(string $price): ?string
    {
        // Remove currency symbols and whitespace
        $cleaned = preg_replace('/[£$€,\s]/', '', $price);
        
        // Check if it's a valid number
        if (preg_match('/^-?\d+(\.\d+)?$/', $cleaned)) {
            return $cleaned;
        }

        return null;
    }
}

