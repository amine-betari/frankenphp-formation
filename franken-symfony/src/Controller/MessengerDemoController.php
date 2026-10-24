<?php

namespace App\Controller;

use App\Message\GenerateDemoReport;
use App\Realtime\MessengerProgressPublisher;
use App\Service\DemoJobStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class MessengerDemoController extends AbstractController
{
    #[Route('/messenger-demo', name: 'app_messenger_demo', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('messenger_demo/index.html.twig');
    }

    #[Route('/messenger-mercure-demo', name: 'app_messenger_mercure_demo', methods: ['GET'])]
    public function realtime(): Response
    {
        return $this->render('messenger_demo/realtime.html.twig', [
            'topic' => MessengerProgressPublisher::TOPIC,
        ]);
    }

    #[Route('/api/messenger/jobs', name: 'app_messenger_create_job', methods: ['POST'])]
    public function create(Request $request, MessageBusInterface $bus, DemoJobStore $jobStore): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $shouldFail = is_array($payload) && true === ($payload['fail'] ?? false);
        $jobId = bin2hex(random_bytes(8));
        $httpInstance = gethostname() ?: 'instance-http-inconnue';
        $httpPid = getmypid();
        $jobStore->write($jobId, [
            'id' => $jobId,
            'status' => 'queued',
            'progress' => 0,
            'http_instance' => $httpInstance,
            'http_pid' => $httpPid,
            'should_fail' => $shouldFail,
            'message' => 'Tâche placée dans la file Messenger.',
        ]);

        $bus->dispatch(new GenerateDemoReport($jobId, $httpInstance, $httpPid, $shouldFail));

        return $this->json([
            'accepted' => true,
            'job_id' => $jobId,
            'status_url' => $this->generateUrl('app_messenger_job_status', ['jobId' => $jobId]),
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/messenger/jobs/{jobId}', name: 'app_messenger_job_status', methods: ['GET'], requirements: ['jobId' => '[a-f0-9]{16}'])]
    public function status(string $jobId, DemoJobStore $jobStore): JsonResponse
    {
        $job = $jobStore->read($jobId);

        return null === $job
            ? $this->json(['error' => 'Tâche introuvable.'], Response::HTTP_NOT_FOUND)
            : $this->json($job);
    }
}
