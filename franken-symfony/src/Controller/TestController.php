<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController
{
    #[Route('/demo/code-version', name: 'app_code_version', methods: ['GET'])]
    public function codeVersion(): JsonResponse
    {
        return new JsonResponse([
            'version' => 3,
            'mode' => getenv('DEMO_MODE') ?: 'inconnu',
        ]);
    }

    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'application' => 'franken-symfony',
            'mode' => getenv('DEMO_MODE') ?: 'inconnu',
        ]);
    }

    #[Route('/demo/compteur', name: 'app_worker_counter')]
    public function workerCounter(): JsonResponse
    {
        static $requestCount = 0;

        ++$requestCount;

        return new JsonResponse([
            'mode' => getenv('DEMO_MODE') ?: 'non défini',
            'compteur_en_memoire' => $requestCount,
            'processus_php' => getmypid(),
            'explication' => $requestCount === 1
                ? 'Le compteur vient d’être initialisé.'
                : 'Le compteur a survécu à la requête précédente.',
        ]);
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return new Response('<h1>Accueil Symfony</h1>');
    }

    #[Route('/bonjour/{nom}', name: 'app_bonjour')]
    public function bonjour(string $nom): Response
    {
        return new Response(sprintf(
            '<h1>Bonjour %s !</h1><p>Cette page est générée par Symfony et servie par FrankenPHP.</p>',
            htmlspecialchars($nom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ));
    }

    #[Route('/api/test', name: 'app_api_test')]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Hello Symfony + FrankenPHP 🚀',
            'php' => PHP_VERSION,
        ]);
    }
}
