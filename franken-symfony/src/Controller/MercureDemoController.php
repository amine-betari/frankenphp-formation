<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

final class MercureDemoController extends AbstractController
{
    private const TOPIC = 'https://frankenphp.local/mercure-demo/messages';

    #[Route('/mercure-demo', name: 'app_mercure_demo', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('mercure_demo/index.html.twig', [
            'topic' => self::TOPIC,
        ]);
    }

    #[Route('/api/mercure/publish', name: 'app_mercure_publish', methods: ['POST'])]
    public function publish(Request $request, HubInterface $hub): JsonResponse
    {
        $payload = $request->toArray();
        $message = trim((string) ($payload['message'] ?? ''));

        if ('' === $message || mb_strlen($message) > 200) {
            return $this->json(['error' => 'Le message doit contenir entre 1 et 200 caractères.'], 422);
        }

        $event = [
            'message' => $message,
            'sent_at' => (new \DateTimeImmutable())->format('H:i:s'),
            'event_id' => bin2hex(random_bytes(4)),
        ];

        $hub->publish(new Update(
            self::TOPIC,
            json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ));

        return $this->json([
            'published' => true,
            'event' => $event,
        ]);
    }
}
