<?php

namespace App\Service;

/**
 * Handles discontinued flag logic
 */
class DiscontinuedHandler
{
    /**
     * Check if product is discontinued and return appropriate date
     *
     * @param array $rowData CSV row data
     * @return \DateTimeInterface|null Discontinued date if marked as discontinued, null otherwise
     */
    public function handleDiscontinued(array $rowData): ?\DateTimeInterface
    {
        $discontinued = strtolower(trim($rowData['Discontinued'] ?? ''));

        if ($discontinued === 'yes') {
            return new \DateTimeImmutable();
        }

        return null;
    }
}

