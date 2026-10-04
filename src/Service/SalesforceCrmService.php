<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\SalesforceCrmDTO;
use App\Entity\User;

final class SalesforceCrmService
{
    public function __construct(
        private readonly SalesforceClient $client,
        private readonly SalesforceCrmMapper $mapper,
    ) {
    }

    public function push(User $user, SalesforceCrmDTO $crm): void
    {
        if (!$this->client->isConfigured()) {
            throw new \RuntimeException('not_configured');
        }

        $this->client->createAccountAndContact($this->mapper->build($user, $crm));
    }
}
