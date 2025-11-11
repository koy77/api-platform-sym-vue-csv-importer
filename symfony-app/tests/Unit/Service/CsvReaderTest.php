<?php

namespace App\Tests\Unit\Service;

use App\Service\CsvReader;
use App\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CsvReaderTest extends TestCase
{
    private CsvReader $csvReader;
    private Filesystem $filesystem;
    private string $testCsvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csvReader = new CsvReader();
        $this->filesystem = new Filesystem();
        $this->testCsvPath = sys_get_temp_dir() . '/test_products.csv';
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testCsvPath)) {
            $this->filesystem->remove($this->testCsvPath);
        }
        parent::tearDown();
    }

    public function testReadValidCsv(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        $csvContent .= "P0001,TV,32\" Tv,10,399.99,\n";
        $csvContent .= "P0002,Cd Player,Nice CD player,11,50.12,yes\n";

        file_put_contents($this->testCsvPath, $csvContent);

        $result = $this->csvReader->read($this->testCsvPath);

        $this->assertCount(2, $result);
        $this->assertEquals('P0001', $result[0]['data']['Product Code']);
        $this->assertEquals('TV', $result[0]['data']['Product Name']);
    }

    public function testReadNonExistentFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSV file not found');

        $this->csvReader->read('/nonexistent/file.csv');
    }

    public function testReadEmptyCsv(): void
    {
        $csvContent = "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued\n";
        file_put_contents($this->testCsvPath, $csvContent);

        $result = $this->csvReader->read($this->testCsvPath);

        $this->assertEmpty($result);
    }
}

