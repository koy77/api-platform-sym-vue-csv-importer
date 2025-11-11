<?php

namespace App\Service;

/**
 * Collects and formats import statistics
 */
class ImportReport
{
    private int $totalProcessed = 0;
    private int $successfulImports = 0;
    private int $skipped = 0;
    private int $failed = 0;
    private array $skippedItems = [];
    private array $failedItems = [];
    private array $successfulItems = [];

    public function incrementTotal(): void
    {
        $this->totalProcessed++;
    }

    public function addSuccessful(string $productCode, string $productName): void
    {
        $this->successfulImports++;
        $this->successfulItems[] = [
            'code' => $productCode,
            'name' => $productName,
        ];
    }

    public function addSkipped(string $productCode, string $reason): void
    {
        $this->skipped++;
        $this->skippedItems[] = [
            'code' => $productCode,
            'reason' => $reason,
        ];
    }

    public function addFailed(string $productCode, string $reason): void
    {
        $this->failed++;
        $this->failedItems[] = [
            'code' => $productCode,
            'reason' => $reason,
        ];
    }

    public function getTotalProcessed(): int
    {
        return $this->totalProcessed;
    }

    public function getSuccessfulImports(): int
    {
        return $this->successfulImports;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getSkippedItems(): array
    {
        return $this->skippedItems;
    }

    public function getFailedItems(): array
    {
        return $this->failedItems;
    }

    public function getSuccessfulItems(): array
    {
        return $this->successfulItems;
    }

    /**
     * Format report as string for console output
     */
    public function formatReport(): string
    {
        $output = "\n";
        $output .= "Import Summary\n";
        $output .= "==============\n";
        $output .= "Total processed: {$this->totalProcessed}\n";
        $output .= "Successfully imported: {$this->successfulImports}\n";
        $output .= "Skipped: {$this->skipped}\n";
        $output .= "Failed: {$this->failed}\n";

        if (!empty($this->skippedItems)) {
            $output .= "\nSkipped Items:\n";
            foreach ($this->skippedItems as $item) {
                $output .= "- {$item['code']}: {$item['reason']}\n";
            }
        }

        if (!empty($this->failedItems)) {
            $output .= "\nFailed Items:\n";
            foreach ($this->failedItems as $item) {
                $output .= "- {$item['code']}: {$item['reason']}\n";
            }
        }

        return $output;
    }
}

