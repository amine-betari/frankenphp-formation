<?php

namespace App\MessageHandler;

use App\Message\GenerateDemoReport;
use App\Realtime\MessengerProgressPublisher;
use App\Service\DemoJobStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateDemoReportHandler
{
    public function __construct(
        private DemoJobStore $jobStore,
        private MessengerProgressPublisher $progressPublisher,
    ) {
    }

    public function __invoke(GenerateDemoReport $message): void
    {
        $runningJob = [
            'id' => $message->jobId,
            'status' => 'running',
            'progress' => 25,
            'http_instance' => $message->httpInstance,
            'http_pid' => $message->httpPid,
            'worker_instance' => gethostname() ?: 'instance-worker-inconnue',
            'worker_pid' => getmypid(),
            'message' => 'Le Worker Messenger prépare le rapport…',
        ];
        $this->jobStore->write($message->jobId, $runningJob);
        $this->progressPublisher->publish($runningJob);

        if ($message->shouldFail) {
            usleep(500_000);

            throw new \RuntimeException('Erreur volontaire : génération du rapport impossible.');
        }

        sleep(3);

        $completedJob = [
            'id' => $message->jobId,
            'status' => 'completed',
            'progress' => 100,
            'http_instance' => $message->httpInstance,
            'http_pid' => $message->httpPid,
            'worker_instance' => gethostname() ?: 'instance-worker-inconnue',
            'worker_pid' => getmypid(),
            'message' => 'Rapport terminé par le Worker Messenger.',
            'result' => [
                'orders' => 128,
                'revenue' => '12 480 EUR',
                'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ],
        ];
        $this->jobStore->write($message->jobId, $completedJob);
        $this->progressPublisher->publish($completedJob);
    }
}
