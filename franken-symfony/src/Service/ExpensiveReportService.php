<?php

namespace App\Service;

final class ExpensiveReportService
{
    private readonly string $instanceId;
    private readonly float $initializedAt;
    private int $requestsHandled = 0;

    public function __construct()
    {
        $this->initializedAt = microtime(true);
        $this->instanceId = substr(bin2hex(random_bytes(8)), 0, 8);

        // Simule le chargement initial d'un modèle, d'une configuration ou d'un gros fichier.
        usleep(250_000);
    }

    /** @return array<string, int|string> */
    public function generate(): array
    {
        ++$this->requestsHandled;

        return [
            'instance_service' => $this->instanceId,
            'initialise_a' => date('H:i:s', (int) $this->initializedAt),
            'requetes_par_instance' => $this->requestsHandled,
            'resultat' => array_sum(range(1, 100)),
        ];
    }
}
