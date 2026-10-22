<?php

namespace App\Realtime;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final readonly class MessengerProgressPublisher
{
    public const TOPIC = 'https://frankenphp.local/messenger/jobs';

    public function __construct(private HubInterface $hub)
    {
    }

    /** @param array<string, mixed> $job */
    public function publish(array $job): void
    {
        $this->hub->publish(new Update(
            self::TOPIC,
            json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ));
    }
}
