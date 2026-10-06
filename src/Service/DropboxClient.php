<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DropboxClient
{
    private const TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';
    private const UPLOAD_URL = 'https://content.dropboxapi.com/2/files/upload';
    private const DEFAULT_FOLDER = '/SupportTickets';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(DROPBOX_APP_KEY)%')]
        private readonly string $appKey,
        #[Autowire('%env(DROPBOX_APP_SECRET)%')]
        private readonly string $appSecret,
        #[Autowire('%env(DROPBOX_REFRESH_TOKEN)%')]
        private readonly string $refreshToken,
        #[Autowire('%env(DROPBOX_FOLDER)%')]
        private readonly string $folder,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->appKey !== ''
            && $this->appSecret !== ''
            && $this->refreshToken !== '';
    }

    public function upload(string $filename, string $contents): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('not_configured');
        }

        $this->assertSafeFilename($filename);
        $this->putFile($this->accessToken(), $this->destinationPath($filename), $contents);
    }

    private function accessToken(): string
    {
        $data = $this->post(self::TOKEN_URL, [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->appKey,
                'client_secret' => $this->appSecret,
            ],
        ]);

        $token = $data['access_token'] ?? null;
        if (!\is_string($token) || $token === '') {
            throw new \RuntimeException('dropbox');
        }

        return $token;
    }

    private function putFile(string $accessToken, string $path, string $contents): void
    {
        $this->post(self::UPLOAD_URL, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => $this->uploadArgument($path),
            ],
            'body' => $contents,
        ]);
    }

    private function uploadArgument(string $path): string
    {
        return json_encode([
            'path' => $path,
            'mode' => 'add',
            'autorename' => true,
            'mute' => false,
        ], JSON_THROW_ON_ERROR);
    }

    private function destinationPath(string $filename): string
    {
        $folder = trim($this->folder);
        if ($folder === '') {
            $folder = self::DEFAULT_FOLDER;
        }

        return '/'.trim($folder, '/').'/'.$filename;
    }

    private function assertSafeFilename(string $filename): void
    {
        if ($filename === '' || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new \RuntimeException('dropbox');
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function post(string $url, array $options): array
    {
        $response = $this->httpClient->request('POST', $url, $options);
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('dropbox');
        }

        return $response->toArray(false);
    }
}
