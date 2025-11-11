<?php

namespace App\Service;

use SplFileObject;

/**
 * Responsible for reading and parsing CSV files
 */
class CsvReader
{
    /**
     * Read CSV file and return rows as arrays
     *
     * @param string $filePath Path to CSV file
     * @return array Array of rows, each row is an associative array
     * @throws \RuntimeException If file cannot be read
     */
    public function read(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("CSV file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException("CSV file is not readable: {$filePath}");
        }

        $rows = [];
        $headers = [];
        $isFirstRow = true;

        // Open file with auto-detection of line endings
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(
            SplFileObject::READ_CSV |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY |
            SplFileObject::DROP_NEW_LINE
        );

        foreach ($file as $lineNumber => $row) {
            // Skip empty rows
            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            // Remove BOM if present
            if ($isFirstRow && !empty($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
            }

            // First row contains headers
            if ($isFirstRow) {
                $headers = array_map('trim', $row);
                $isFirstRow = false;
                continue;
            }

            // Map row to associative array
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            $rows[] = [
                'line_number' => $lineNumber + 1,
                'data' => $rowData,
            ];
        }

        return $rows;
    }
}

