<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Rule\HighValueRule;
use App\Rule\ImportRuleInterface;
use App\Rule\LowValueLowStockRule;
use App\Transformer\ProductTransformer;
use App\Validator\ProductValidator;
use Psr\Log\LoggerInterface;

/**
 * Orchestrates the import process
 */
class ImportService
{
    private CsvReader $csvReader;
    private ProductValidator $validator;
    private ProductTransformer $transformer;
    private ProductRepository $repository;
    private ImportReport $report;
    private LoggerInterface $logger;
    private array $rules = [];
    private array $seenProductCodes = [];

    public function __construct(
        CsvReader $csvReader,
        ProductValidator $validator,
        ProductTransformer $transformer,
        ProductRepository $repository,
        ImportReport $report,
        LoggerInterface $logger
    ) {
        $this->csvReader = $csvReader;
        $this->validator = $validator;
        $this->transformer = $transformer;
        $this->repository = $repository;
        $this->report = $report;
        $this->logger = $logger;

        // Register business rules
        $this->rules[] = new LowValueLowStockRule($validator);
        $this->rules[] = new HighValueRule();
    }

    /**
     * Add a custom import rule
     */
    public function addRule(ImportRuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    /**
     * Import products from CSV file
     *
     * @param string $filePath Path to CSV file
     * @param bool $testMode If true, don't insert into database
     * @return ImportReport Import report with statistics
     */
    public function import(string $filePath, bool $testMode = false): ImportReport
    {
        $this->logger->info("Starting import process", ['file' => $filePath, 'test_mode' => $testMode]);

        try {
            $rows = $this->csvReader->read($filePath);
        } catch (\Exception $e) {
            $this->logger->error("Failed to read CSV file", ['error' => $e->getMessage()]);
            throw $e;
        }

        foreach ($rows as $row) {
            $this->report->incrementTotal();
            $rowData = $row['data'];
            $lineNumber = $row['line_number'];

            $productCode = trim($rowData['Product Code'] ?? '');

            // Validate product data
            $validation = $this->validator->validate($rowData);
            if (!$validation['valid']) {
                $reason = implode(', ', $validation['errors']);
                $this->report->addFailed($productCode ?: "Line {$lineNumber}", $reason);
                $this->logger->warning("Validation failed", [
                    'line' => $lineNumber,
                    'code' => $productCode,
                    'errors' => $validation['errors'],
                ]);
                continue;
            }

            // Check for duplicate product codes in current import
            if (isset($this->seenProductCodes[$productCode])) {
                $this->report->addSkipped($productCode, 'Duplicate product code in CSV');
                $this->logger->warning("Duplicate product code in CSV", ['code' => $productCode]);
                continue;
            }
            $this->seenProductCodes[$productCode] = true;

            // Check if product code already exists in database
            if (!$testMode && $this->repository->productCodeExists($productCode)) {
                $this->report->addSkipped($productCode, 'Duplicate product code in database');
                $this->logger->warning("Product code already exists", ['code' => $productCode]);
                continue;
            }

            // Transform CSV data to Product entity
            $transformation = $this->transformer->transform($rowData);
            if (!empty($transformation['errors'])) {
                $reason = implode(', ', $transformation['errors']);
                $this->report->addFailed($productCode, $reason);
                $this->logger->warning("Transformation failed", [
                    'code' => $productCode,
                    'errors' => $transformation['errors'],
                ]);
                continue;
            }

            $product = $transformation['product'];

            // Apply business rules
            $productData = [
                'price' => $product->getPrice(),
                'stock' => $product->getStock(),
            ];

            foreach ($this->rules as $rule) {
                $ruleResult = $rule->shouldSkip($productData);
                if ($ruleResult['should_skip']) {
                    $this->report->addSkipped($productCode, $ruleResult['reason']);
                    $this->logger->info("Product skipped by rule", [
                        'code' => $productCode,
                        'reason' => $ruleResult['reason'],
                    ]);
                    continue 2; // Skip to next row
                }
            }

            // Save to database (unless in test mode)
            if (!$testMode) {
                $saved = $this->repository->save($product);
                if (!$saved) {
                    $this->report->addFailed($productCode, 'Database insertion failed');
                    $this->logger->error("Failed to save product", ['code' => $productCode]);
                    continue;
                }
            }

            $productName = $product->getProductName();
            $discontinued = $product->getDiscontinued() !== null ? ' (Discontinued)' : '';
            $this->report->addSuccessful($productCode, $productName . $discontinued);
            $this->logger->info("Product imported successfully", [
                'code' => $productCode,
                'name' => $productName,
                'test_mode' => $testMode,
            ]);
        }

        $this->logger->info("Import process completed", [
            'total' => $this->report->getTotalProcessed(),
            'successful' => $this->report->getSuccessfulImports(),
            'skipped' => $this->report->getSkipped(),
            'failed' => $this->report->getFailed(),
        ]);

        return $this->report;
    }
}

