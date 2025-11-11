<?php

namespace App\Tests\Integration\Command;

use App\Entity\Product;
use App\Kernel;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ImportProductsCommandTest extends KernelTestCase
{
    private ProductRepository $productRepository;
    private Filesystem $filesystem;
    private string $testCsvPath;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
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

    public function testImportValidProducts(): void
    {
        // Create test CSV with valid products
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,CD Player,Nice CD player,11,50.12,yes\n";
        $csvContent .= "P0003,VCR,Top notch VCR,12,39.33,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Import process completed!', $output);
        $this->assertStringContainsString('Successfully imported: 3', $output);

        // Verify products were saved to database
        $products = $this->productRepository->findAll();
        $this->assertCount(3, $products);

        $product1 = $this->productRepository->findOneBy(['productCode' => 'P0001']);
        $this->assertNotNull($product1);
        $this->assertEquals('TV', $product1->getProductName());
        $this->assertEquals(10, $product1->getStock());
        $this->assertEquals('399.99', $product1->getPrice());
    }

    public function testImportWithTestMode(): void
    {
        // Create test CSV
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
            '--test' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('TEST MODE', $output);
        $this->assertStringContainsString('No database insertion will occur', $output);

        // Verify no products were saved to database
        $products = $this->productRepository->findAll();
        $this->assertCount(0, $products);
    }

    public function testImportSkipsLowValueLowStock(): void
    {
        // Create CSV with product that should be skipped (cost < $5 AND stock < 10)
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,Cheap Item,Low value item,5,4.50,\n"; // Should be skipped

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Skipped: 1', $output);
        $this->assertStringContainsString('Cost < $5 and Stock < 10', $output);

        // Verify only one product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertEquals('P0001', $products[0]->getProductCode());
    }

    public function testImportSkipsHighValue(): void
    {
        // Create CSV with product that should be skipped (cost > $1000)
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,Expensive Item,High value item,5,1200.00,\n"; // Should be skipped

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Skipped: 1', $output);
        $this->assertStringContainsString('Cost > $1000', $output);

        // Verify only one product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
    }

    public function testImportHandlesDiscontinuedProducts(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,yes\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // Verify discontinued date is set
        $product = $this->productRepository->findOneBy(['productCode' => 'P0001']);
        $this->assertNotNull($product);
        $this->assertNotNull($product->getDiscontinued());
    }

    public function testImportHandlesInvalidData(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= ",,Invalid row,,\n"; // Missing required fields

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Failed: 1', $output);

        // Verify only valid product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
    }

    public function testImportHandlesDuplicateProductCodes(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0001,TV Duplicate,Same code,5,200.00,\n"; // Duplicate code

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Skipped: 1', $output);
        $this->assertStringContainsString('Duplicate product code', $output);

        // Verify only one product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
    }

    public function testImportHandlesCurrencySymbols(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,\$399.99,\n"; // Price with currency symbol

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // Verify price was cleaned correctly
        $product = $this->productRepository->findOneBy(['productCode' => 'P0001']);
        $this->assertNotNull($product);
        $this->assertEquals('399.99', $product->getPrice());
    }

    public function testImportWithEmptyFile(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => $this->testCsvPath,
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Total processed: 0', $output);

        // Verify no products were saved
        $products = $this->productRepository->findAll();
        $this->assertCount(0, $products);
    }

    public function testImportWithNonExistentFile(): void
    {
        $application = new Application(static::$kernel);
        $command = $application->find('app:import:products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'file' => '/nonexistent/file.csv',
        ]);

        $this->assertEquals(CommandTester::FAILURE, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Import failed', $output);
    }
}

