<?php

namespace App\Controller;

use App\Service\ExpensiveReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WorkerLabController extends AbstractController
{
    #[Route('/worker-lab', name: 'app_worker_lab', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('worker_lab/index.html.twig');
    }

    #[Route('/api/worker-lab', name: 'app_worker_lab_api', methods: ['GET'])]
    public function api(ExpensiveReportService $reportService): JsonResponse
    {
        $startedAt = microtime(true);
        $report = $reportService->generate();

        $response = $this->json([
            'mode' => getenv('DEMO_MODE') ?: 'inconnu',
            'duree_serveur_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'pid_php' => getmypid(),
            ...$report,
        ]);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
