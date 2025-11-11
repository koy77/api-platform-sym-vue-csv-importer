<?php

namespace App\Tests\Integration\Service;

use App\Entity\Product;
use App\Kernel;
use App\Repository\ProductRepository;
use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImportServiceIntegrationTest extends KernelTestCase
{
    private ImportService $importService;
    private ProductRepository $productRepository;
    private Filesystem $filesystem;
    private string $testCsvPath;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        if (!static::$kernel) {
            self::bootKernel();
        }
        $this->importService = static::getContainer()->get(ImportService::class);
        $this->productRepository = static::getContainer()->get(ProductRepository::class);
        $this->filesystem = new Filesystem();
        $this->testCsvPath = sys_get_temp_dir() . '/test_products_' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        // Clean up test CSV file
        if ($this->filesystem->exists($this->testCsvPath)) {
            $this->filesystem->remove($this->testCsvPath);
        }

        // Clean up test products
        $products = $this->productRepository->findAll();
        foreach ($products as $product) {
            $this->productRepository->getEntityManager()->remove($product);
        }
        $this->productRepository->getEntityManager()->flush();

        parent::tearDown();
    }

    public function testFullImportProcess(): void
    {
        // Create comprehensive test CSV
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,CD Player,Nice CD player,11,50.12,yes\n";
        $csvContent .= "P0003,Cheap Item,Low value,5,4.50,\n"; // Should be skipped
        $csvContent .= "P0004,Expensive Item,High value,5,1200.00,\n"; // Should be skipped

        file_put_contents($this->testCsvPath, $csvContent);

        $report = $this->importService->import($this->testCsvPath, false);

        // Verify report statistics
        $this->assertEquals(4, $report->getTotalProcessed());
        $this->assertEquals(2, $report->getSuccessfulImports());
        $this->assertEquals(2, $report->getSkipped());
        $this->assertEquals(0, $report->getFailed());

        // Verify products in database
        $products = $this->productRepository->findAll();
        $this->assertCount(2, $products);

        // Verify product details
        $product1 = $this->productRepository->findOneBy(['productCode' => 'P0001']);
        $this->assertNotNull($product1);
        $this->assertEquals('TV', $product1->getProductName());
        $this->assertEquals('32" TV', $product1->getProductDesc());
        $this->assertEquals(10, $product1->getStock());
        $this->assertEquals('399.99', $product1->getPrice());
        $this->assertNull($product1->getDiscontinued());

        $product2 = $this->productRepository->findOneBy(['productCode' => 'P0002']);
        $this->assertNotNull($product2);
        $this->assertNotNull($product2->getDiscontinued());
    }

    public function testImportInTestModeDoesNotSaveToDatabase(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $report = $this->importService->import($this->testCsvPath, true);

        // Verify report shows success
        $this->assertEquals(1, $report->getTotalProcessed());
        $this->assertEquals(1, $report->getSuccessfulImports());

        // Verify no products were saved
        $products = $this->productRepository->findAll();
        $this->assertCount(0, $products);
    }

    public function testImportHandlesAllBusinessRules(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,Valid Product,Good product,15,50.00,\n";
        $csvContent .= "P0002,Low Value Low Stock,Should skip,5,4.00,\n"; // Rule 1
        $csvContent .= "P0003,High Value,Should skip,10,1200.00,\n"; // Rule 2
        $csvContent .= "P0004,Discontinued Product,Should import,10,50.00,yes\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $report = $this->importService->import($this->testCsvPath, false);

        $this->assertEquals(4, $report->getTotalProcessed());
        $this->assertEquals(2, $report->getSuccessfulImports());
        $this->assertEquals(2, $report->getSkipped());

        // Verify skipped items have correct reasons
        $skippedItems = $report->getSkippedItems();
        $reasons = array_column($skippedItems, 'reason');
        $this->assertContains('Cost < $5 and Stock < 10', $reasons);
        $this->assertContains('Cost > $1000', $reasons);

        // Verify only valid products were saved
        $products = $this->productRepository->findAll();
        $this->assertCount(2, $products);
    }

    public function testImportHandlesDuplicateCodesInCsv(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0001,TV Duplicate,Same code,5,200.00,\n"; // Duplicate in CSV

        file_put_contents($this->testCsvPath, $csvContent);

        $report = $this->importService->import($this->testCsvPath, false);

        $this->assertEquals(2, $report->getTotalProcessed());
        $this->assertEquals(1, $report->getSuccessfulImports());
        $this->assertEquals(1, $report->getSkipped());

        // Verify only one product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
    }

    public function testImportHandlesDuplicateCodesInDatabase(): void
    {
        // First import
        $csvContent1 = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent1 .= "P0001,TV,32\" TV,10,399.99,\n";
        file_put_contents($this->testCsvPath, $csvContent1);
        $this->importService->import($this->testCsvPath, false);

        // Second import with same code
        $csvContent2 = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent2 .= "P0001,TV Duplicate,Same code,5,200.00,\n";
        file_put_contents($this->testCsvPath, $csvContent2);
        $report = $this->importService->import($this->testCsvPath, false);

        $this->assertEquals(1, $report->getTotalProcessed());
        $this->assertEquals(0, $report->getSuccessfulImports());
        $this->assertEquals(1, $report->getSkipped());
        $this->assertStringContainsString('Duplicate product code', $report->getSkippedItems()[0]['reason']);

        // Verify only one product exists
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
    }

    public function testImportHandlesCurrencySymbols(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,\$399.99,\n";
        $csvContent .= "P0002,CD Player,Nice CD player,11,£50.12,yes\n";
        $csvContent .= "P0003,Monitor,24\" Monitor,5,€35.99,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $report = $this->importService->import($this->testCsvPath, false);

        $this->assertEquals(3, $report->getSuccessfulImports());

        // Verify prices were cleaned correctly
        $product1 = $this->productRepository->findOneBy(['productCode' => 'P0001']);
        $this->assertEquals('399.99', $product1->getPrice());

        $product2 = $this->productRepository->findOneBy(['productCode' => 'P0002']);
        $this->assertEquals('50.12', $product2->getPrice());

        $product3 = $this->productRepository->findOneBy(['productCode' => 'P0003']);
        $this->assertEquals('35.99', $product3->getPrice());
    }

    public function testImportReportContainsAllDetails(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,Cheap Item,Low value,5,4.50,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $report = $this->importService->import($this->testCsvPath, false);

        // Verify report structure
        $this->assertGreaterThan(0, $report->getTotalProcessed());
        $this->assertIsArray($report->getSuccessfulItems());
        $this->assertIsArray($report->getSkippedItems());
        $this->assertIsArray($report->getFailedItems());

        // Verify successful items structure
        if (count($report->getSuccessfulItems()) > 0) {
            $item = $report->getSuccessfulItems()[0];
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('name', $item);
        }

        // Verify skipped items structure
        if (count($report->getSkippedItems()) > 0) {
            $item = $report->getSkippedItems()[0];
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('reason', $item);
        }
    }
}

