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
        $configured = $this->client->isConfigured();
        // #region agent log
        file_put_contents('/Users/olloberganabdullaev/Desktop/course-project/.cursor/debug-b15be3.log', json_encode(['sessionId' => 'b15be3', 'hypothesisId' => 'B', 'location' => 'SalesforceCrmService.php:push', 'message' => 'salesforce configured', 'data' => ['configured' => $configured], 'timestamp' => (int) round(microtime(true) * 1000)])."\n", FILE_APPEND);
        // #endregion
        if (!$configured) {
            throw new \RuntimeException('not_configured');
        }

        $this->client->createAccountAndContact($this->mapper->build($user, $crm));
    }
}
