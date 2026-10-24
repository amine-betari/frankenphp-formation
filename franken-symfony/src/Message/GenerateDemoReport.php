<?php

namespace App\Message;

final readonly class GenerateDemoReport
{
    public function __construct(
        public string $jobId,
        public string $httpInstance,
        public int $httpPid,
        public bool $shouldFail = false,
    ) {
    }
}
