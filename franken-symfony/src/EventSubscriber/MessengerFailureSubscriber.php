<?php

namespace App\EventSubscriber;

use App\Message\GenerateDemoReport;
use App\Realtime\MessengerProgressPublisher;
use App\Service\DemoJobStore;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;

final readonly class MessengerFailureSubscriber implements EventSubscriberInterface
{
    private const MAX_ATTEMPTS = 4;

    public function __construct(
        private DemoJobStore $jobStore,
        private MessengerProgressPublisher $progressPublisher,
    ) {
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $envelope = $event->getEnvelope();
        $message = $envelope->getMessage();

        if (!$message instanceof GenerateDemoReport) {
            return;
        }

        $attempt = RedeliveryStamp::getRetryCountFromEnvelope($envelope) + 1;
        $willRetry = $event->willRetry();
        $job = [
            'id' => $message->jobId,
            'status' => $willRetry ? 'retrying' : 'failed',
            'progress' => 0,
            'attempt' => $attempt,
            'max_attempts' => self::MAX_ATTEMPTS,
            'http_instance' => $message->httpInstance,
            'http_pid' => $message->httpPid,
            'worker_instance' => gethostname() ?: 'instance-worker-inconnue',
            'worker_pid' => getmypid(),
            'message' => $willRetry
                ? sprintf('Tentative %d échouée. Messenger va réessayer.', $attempt)
                : sprintf('Échec définitif après %d tentatives. Message placé dans la file failed.', $attempt),
            'error' => 'Erreur volontaire : génération du rapport impossible.',
        ];

        $this->jobStore->write($message->jobId, $job);
        $this->progressPublisher->publish($job);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', 0],
        ];
    }
}
