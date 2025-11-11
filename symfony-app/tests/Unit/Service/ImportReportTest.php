<?php

namespace App\Tests\Unit\Service;

use App\Service\ImportReport;
use App\Tests\TestCase;

class ImportReportTest extends TestCase
{
    private ImportReport $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->report = new ImportReport();
    }

    public function testReportStatistics(): void
    {
        $this->report->incrementTotal();
        $this->report->incrementTotal();
        $this->report->addSuccessful('P0001', 'TV');
        $this->report->addSkipped('P0002', 'Cost < $5 and Stock < 10');
        $this->report->addFailed('P0003', 'Invalid data');

        $this->assertEquals(2, $this->report->getTotalProcessed());
        $this->assertEquals(1, $this->report->getSuccessfulImports());
        $this->assertEquals(1, $this->report->getSkipped());
        $this->assertEquals(1, $this->report->getFailed());
    }

    public function testFormatReport(): void
    {
        $this->report->incrementTotal();
        $this->report->addSuccessful('P0001', 'TV');
        $this->report->addSkipped('P0002', 'Cost < $5 and Stock < 10');

        $formatted = $this->report->formatReport();

        $this->assertStringContainsString('Import Summary', $formatted);
        $this->assertStringContainsString('Total processed: 1', $formatted);
        $this->assertStringContainsString('P0002', $formatted);
    }
}

