<?php

namespace App\Command;

use App\Service\ImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:products',
    description: 'Import products from CSV file',
)]
class ImportProductsCommand extends Command
{
    private ImportService $importService;

    public function __construct(ImportService $importService)
    {
        parent::__construct();
        $this->importService = $importService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to CSV file')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Run in test mode (no database insertion)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $testMode = $input->getOption('test');

        $io->title('Product Import Process');
        $io->section('Starting Import');

        if ($testMode) {
            $io->note('Running in TEST MODE - No database insertion will occur');
        }

        $io->text("Processing CSV file: {$filePath}");

        try {
            $report = $this->importService->import($filePath, $testMode);

            $io->section('Processing Results');

            // Display progress
            $total = $report->getTotalProcessed();
            $successful = $report->getSuccessfulImports();
            $skipped = $report->getSkipped();
            $failed = $report->getFailed();

            $io->text("Total rows found: {$total}");
            $io->text("Header row skipped: 1");
            $io->text("Data rows to process: " . ($total));

            $io->newLine();
            $io->text("Processing...");

            // Display successful items
            foreach ($report->getSuccessfulItems() as $item) {
                $io->writeln("✓ {$item['code']} - {$item['name']} - Imported successfully");
            }

            // Display skipped items
            foreach ($report->getSkippedItems() as $item) {
                $io->writeln("✗ {$item['code']} - Skipped: {$item['reason']}");
            }

            // Display failed items
            foreach ($report->getFailedItems() as $item) {
                $io->writeln("✗ {$item['code']} - Failed: {$item['reason']}");
            }

            // Display summary
            $io->section('Import Summary');
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Total processed', $total],
                    ['Successfully imported', $successful],
                    ['Skipped', $skipped],
                    ['Failed', $failed],
                ]
            );

            if ($skipped > 0) {
                $io->section('Skipped Items');
                $skippedData = array_map(function ($item) {
                    return [$item['code'], $item['reason']];
                }, $report->getSkippedItems());
                $io->table(['Product Code', 'Reason'], $skippedData);
            }

            if ($failed > 0) {
                $io->section('Failed Items');
                $failedData = array_map(function ($item) {
                    return [$item['code'], $item['reason']];
                }, $report->getFailedItems());
                $io->table(['Product Code', 'Reason'], $failedData);
            }

            if ($testMode) {
                $io->note('This was a test run. No data was inserted into the database.');
            }

            $io->success('Import process completed!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

