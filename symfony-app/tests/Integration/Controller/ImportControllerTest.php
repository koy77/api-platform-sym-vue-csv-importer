<?php

namespace App\Tests\Integration\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportControllerTest extends WebTestCase
{
    private ProductRepository $productRepository;
    private Filesystem $filesystem;
    private string $testCsvPath;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
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

    public function testImportViaApiWithValidFile(): void
    {
        // Create test CSV
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,CD Player,Nice CD player,11,50.12,yes\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $this->testCsvPath,
            'test_products.csv',
            'text/csv',
            null,
            true
        );

        $client = static::createClient();
        $client->request('POST', '/api/import', [
            'file' => $uploadedFile,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('successful', $response);
        $this->assertArrayHasKey('skipped', $response);
        $this->assertArrayHasKey('failed', $response);
        $this->assertEquals(2, $response['total']);
        $this->assertEquals(2, $response['successful']);

        // Verify products were saved
        $products = $this->productRepository->findAll();
        $this->assertCount(2, $products);
    }

    public function testImportViaApiWithTestMode(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $this->testCsvPath,
            'test_products.csv',
            'text/csv',
            null,
            true
        );

        $client = static::createClient();
        $client->request('POST', '/api/import?test=1', [
            'file' => $uploadedFile,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(1, $response['total']);
        $this->assertEquals(1, $response['successful']);

        // Verify no products were saved in test mode
        $products = $this->productRepository->findAll();
        $this->assertCount(0, $products);
    }

    public function testImportViaApiWithoutFile(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/import');

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('No file uploaded', $response['error']);
    }

    public function testImportViaApiWithBusinessRules(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,Cheap Item,Low value,5,4.50,\n"; // Should be skipped
        $csvContent .= "P0003,Expensive Item,High value,5,1200.00,\n"; // Should be skipped

        file_put_contents($this->testCsvPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $this->testCsvPath,
            'test_products.csv',
            'text/csv',
            null,
            true
        );

        $client = static::createClient();
        $client->request('POST', '/api/import', [
            'file' => $uploadedFile,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(3, $response['total']);
        $this->assertEquals(1, $response['successful']);
        $this->assertEquals(2, $response['skipped']);
        $this->assertCount(2, $response['skipped_items']);

        // Verify only valid product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
        $this->assertEquals('P0001', $products[0]->getProductCode());
    }

    public function testImportViaApiWithInvalidData(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= ",,Invalid row,,\n"; // Missing required fields

        file_put_contents($this->testCsvPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $this->testCsvPath,
            'test_products.csv',
            'text/csv',
            null,
            true
        );

        $client = static::createClient();
        $client->request('POST', '/api/import', [
            'file' => $uploadedFile,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertEquals(1, $response['successful']);
        $this->assertEquals(1, $response['failed']);
        $this->assertCount(1, $response['failed_items']);

        // Verify only valid product was saved
        $products = $this->productRepository->findAll();
        $this->assertCount(1, $products);
    }

    public function testImportViaApiReturnsDetailedReport(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" TV,10,399.99,\n";
        $csvContent .= "P0002,Cheap Item,Low value,5,4.50,\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $uploadedFile = new UploadedFile(
            $this->testCsvPath,
            'test_products.csv',
            'text/csv',
            null,
            true
        );

        $client = static::createClient();
        $client->request('POST', '/api/import', [
            'file' => $uploadedFile,
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('skipped_items', $response);
        $this->assertArrayHasKey('failed_items', $response);
        $this->assertIsArray($response['skipped_items']);
        $this->assertIsArray($response['failed_items']);

        // Verify skipped item details
        if (count($response['skipped_items']) > 0) {
            $skippedItem = $response['skipped_items'][0];
            $this->assertArrayHasKey('code', $skippedItem);
            $this->assertArrayHasKey('reason', $skippedItem);
        }
    }
}

