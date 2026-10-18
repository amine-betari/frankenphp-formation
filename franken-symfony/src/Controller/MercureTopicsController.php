<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

final class MercureTopicsController extends AbstractController
{
    private const TOPIC_PREFIX = 'https://frankenphp.local/users/';

    #[Route('/mercure-private', name: 'app_mercure_topics', methods: ['GET'])]
    public function index(): Response
    {
        $username = $this->getUser()?->getUserIdentifier();
        if (null === $username) {
            throw $this->createAccessDeniedException();
        }
        $otherUsername = 'amine' === $username ? 'karim' : 'amine';

        return $this->render('mercure_topics/index.html.twig', [
            'username' => $username,
            'topic' => self::TOPIC_PREFIX.$username.'/notifications',
            'other_username' => $otherUsername,
            'other_topic' => self::TOPIC_PREFIX.$otherUsername.'/notifications',
        ]);
    }

    #[Route('/api/mercure/private/notify/{recipient}', name: 'app_mercure_user_notify', methods: ['POST'], requirements: ['recipient' => 'amine|karim'])]
    public function notify(string $recipient, \Symfony\Component\HttpFoundation\Request $request, HubInterface $hub): JsonResponse
    {
        $payload = $request->toArray();
        $message = trim((string) ($payload['message'] ?? ''));

        if ('' === $message || mb_strlen($message) > 200) {
            return $this->json(['error' => 'Le message doit contenir entre 1 et 200 caractères.'], 422);
        }

        $event = [
            'recipient' => $recipient,
            'message' => $message,
            'sent_at' => (new \DateTimeImmutable())->format('H:i:s'),
        ];

        $hub->publish(new Update(
            self::TOPIC_PREFIX.$recipient.'/notifications',
            json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            true,
        ));

        return $this->json(['published' => true, 'event' => $event]);
    }
}
