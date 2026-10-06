<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DropboxClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DropboxClientTest extends TestCase
{
    public function testUploadWithoutCredentialsDoesNotCallHttp(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::never())->method('request');

        $client = new DropboxClient($http, '', '', '', '/SupportTickets');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not_configured');

        $client->upload('support.json', '{}');
    }

    public function testUploadRefreshesTokenThenSendsFile(): void
    {
        $requests = [];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            if (str_contains($url, '/oauth2/token')) {
                return new MockResponse('{"access_token":"token-1"}');
            }

            return new MockResponse('{"name":"support.json"}');
        });

        $client = new DropboxClient($http, 'app-key', 'app-secret', 'refresh-token', '/SupportTickets');
        $client->upload('support.json', '{"Priority":"High"}');

        self::assertCount(2, $requests);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame('https://api.dropboxapi.com/oauth2/token', $requests[0]['url']);
        self::assertSame('POST', $requests[1]['method']);
        self::assertSame('https://content.dropboxapi.com/2/files/upload', $requests[1]['url']);

        $headers = $requests[1]['options']['headers'];
        self::assertSame('application/octet-stream', $this->header($headers, 'Content-Type'));
        self::assertSame('Bearer token-1', $this->header($headers, 'Authorization'));

        $argument = json_decode($this->header($headers, 'Dropbox-API-Arg'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('/SupportTickets/support.json', $argument['path']);
        self::assertSame('add', $argument['mode']);
        self::assertTrue($argument['autorename']);
        self::assertSame('{"Priority":"High"}', $requests[1]['options']['body']);
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (\is_string($key) && strcasecmp($key, $name) === 0) {
                return \is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }

            if (!\is_string($value) || !str_contains($value, ':')) {
                continue;
            }

            [$headerName, $headerValue] = explode(':', $value, 2);
            if (strcasecmp(trim($headerName), $name) === 0) {
                return trim($headerValue);
            }
        }

        return '';
    }
}
