<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SalesforceClient
{
    private const API_VERSION = 'v62.0';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(SALESFORCE_LOGIN_URL)%')]
        private readonly string $loginUrl,
        #[Autowire('%env(SALESFORCE_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(SALESFORCE_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        #[Autowire('%env(SALESFORCE_USERNAME)%')]
        private readonly string $username,
        #[Autowire('%env(SALESFORCE_PASSWORD)%')]
        private readonly string $password,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->loginUrl !== ''
            && $this->clientId !== ''
            && $this->clientSecret !== ''
            && $this->username !== ''
            && $this->password !== '';
    }

    /**
     * @param array{account: array<string, string>, contact: array<string, string>} $payload
     * @return array{accountId: string, contactId: string}
     */
    public function createAccountAndContact(array $payload): array
    {
        $token = $this->authenticate();
        $accountId = $this->createRecord($token, 'Account', $payload['account']);
        $contact = $payload['contact'];
        $contact['AccountId'] = $accountId;

        return [
            'accountId' => $accountId,
            'contactId' => $this->createRecord($token, 'Contact', $contact),
        ];
    }

    /**
     * @return array{access_token: string, instance_url: string}
     */
    private function authenticate(): array
    {
        $data = $this->post(rtrim($this->loginUrl, '/').'/services/oauth2/token', [
            'body' => [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);

        $accessToken = $data['access_token'] ?? null;
        $instanceUrl = $data['instance_url'] ?? null;
        if (!\is_string($accessToken) || $accessToken === '' || !\is_string($instanceUrl) || $instanceUrl === '') {
            throw new \RuntimeException('salesforce');
        }

        return [
            'access_token' => $accessToken,
            'instance_url' => $instanceUrl,
        ];
    }

    /**
     * @param array{access_token: string, instance_url: string} $token
     * @param array<string, string> $fields
     */
    private function createRecord(array $token, string $object, array $fields): string
    {
        $data = $this->post($token['instance_url'].'/services/data/'.self::API_VERSION.'/sobjects/'.$object, [
            'headers' => [
                'Authorization' => 'Bearer '.$token['access_token'],
            ],
            'json' => $fields,
        ]);

        $id = $data['id'] ?? null;
        if (!\is_string($id) || $id === '') {
            throw new \RuntimeException('salesforce');
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function post(string $url, array $options): array
    {
        $response = $this->httpClient->request('POST', $url, $options);
        $status = $response->getStatusCode();
        $path = (string) parse_url($url, PHP_URL_PATH);
        $errorCode = null;
        $errorText = null;
        if ($status < 200 || $status >= 300) {
            try {
                $payload = $response->toArray(false);
                $errorCode = isset($payload['error']) && \is_string($payload['error']) ? $payload['error'] : null;
                $description = $payload['error_description'] ?? null;
                if (\is_string($description) && preg_match('/^[A-Za-z0-9 .:,-]{1,80}$/', $description) === 1) {
                    $errorText = $description;
                }
            } catch (\Throwable) {
                $errorCode = null;
            }
        }
        // #region agent log
        file_put_contents('/Users/olloberganabdullaev/Desktop/course-project/.cursor/debug-b15be3.log', json_encode(['sessionId' => 'b15be3', 'hypothesisId' => $status >= 200 && $status < 300 ? 'D' : 'C', 'location' => 'SalesforceClient.php:post', 'message' => 'salesforce http', 'data' => ['status' => $status, 'path' => $path, 'error' => $errorCode, 'errorText' => $errorText], 'timestamp' => (int) round(microtime(true) * 1000)])."\n", FILE_APPEND);
        // #endregion
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('salesforce');
        }

        return $response->toArray(false);
    }
}
