<?php

namespace App\Controller;

use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{
    private ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    #[Route('/api/import', name: 'api_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        
        if (!$file) {
            return new JsonResponse([
                'error' => 'No file uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        $testMode = $request->query->getBoolean('test', false);

        try {
            // Save uploaded file temporarily
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.csv';
            $file->move(sys_get_temp_dir(), basename($tempPath));
            $fullPath = sys_get_temp_dir() . '/' . basename($tempPath);

            $report = $this->importService->import($fullPath, $testMode);

            // Clean up temp file
            @unlink($fullPath);

            return new JsonResponse([
                'total' => $report->getTotalProcessed(),
                'successful' => $report->getSuccessfulImports(),
                'skipped' => $report->getSkipped(),
                'failed' => $report->getFailed(),
                'skipped_items' => $report->getSkippedItems(),
                'failed_items' => $report->getFailedItems(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

