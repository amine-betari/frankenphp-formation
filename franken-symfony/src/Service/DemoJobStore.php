<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class DemoJobStore
{
    private string $directory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->directory = $projectDir.'/var/messenger-demo';
    }

    /** @param array<string, mixed> $data */
    public function write(string $jobId, array $data): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Impossible de créer le dossier des tâches Messenger.');
        }

        $file = $this->file($jobId);
        $temporaryFile = $file.'.tmp';
        file_put_contents($temporaryFile, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        rename($temporaryFile, $file);
    }

    /** @return array<string, mixed>|null */
    public function read(string $jobId): ?array
    {
        $file = $this->file($jobId);
        if (!is_file($file)) {
            return null;
        }

        return json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    private function file(string $jobId): string
    {
        if (1 !== preg_match('/^[a-f0-9]{16}$/', $jobId)) {
            throw new \InvalidArgumentException('Identifiant de tâche invalide.');
        }

        return $this->directory.'/'.$jobId.'.json';
    }
}
