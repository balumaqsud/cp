<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SupabaseStorageService
{
    private const SIGNED_TTL = '+10 minutes';

    /** @var array<string, string> */
    private const CONTENT_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /** @var array<string, string> */
    private const EXTENSIONS = [
        'jpg' => 'jpg',
        'jpeg' => 'jpg',
        'png' => 'png',
        'webp' => 'webp',
        'gif' => 'gif',
    ];

    public function __construct(
        #[Autowire('%env(default::SUPABASE_S3_ENDPOINT)%')]
        private readonly string $endpoint,
        #[Autowire('%env(default::SUPABASE_S3_REGION)%')]
        private readonly string $region,
        #[Autowire('%env(default::SUPABASE_ACCESS_KEY)%')]
        private readonly string $accessKey,
        #[Autowire('%env(default::SUPABASE_SECRET_KEY)%')]
        private readonly string $secretKey,
        #[Autowire('%env(default::SUPABASE_BUCKET)%')]
        private readonly string $bucket,
        #[Autowire('%env(default::SUPABASE_PUBLIC_URL)%')]
        private readonly string $publicUrl,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->endpoint !== ''
            && $this->region !== ''
            && $this->accessKey !== ''
            && $this->secretKey !== ''
            && $this->bucket !== ''
            && $this->publicUrl !== '';
    }

    /**
     * @return array{uploadUrl: string, publicUrl: string, contentType: string}
     */
    public function createUpload(string $contentType, string $filename): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Supabase Storage is not configured.');
        }

        $contentType = strtolower(trim($contentType));
        $extension = $this->extensionFor($contentType, $filename);
        $resolvedType = $this->contentTypeForExtension($extension);
        $key = 'photos/'.bin2hex(random_bytes(16)).'.'.$extension;

        $client = $this->client();
        $command = $client->getCommand('PutObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
            'ContentType' => $resolvedType,
        ]);
        $request = $client->createPresignedRequest($command, self::SIGNED_TTL);

        return [
            'uploadUrl' => (string) $request->getUri(),
            'publicUrl' => rtrim($this->publicUrl, '/').'/storage/v1/object/public/'.$this->bucket.'/'.$key,
            'contentType' => $resolvedType,
        ];
    }

    private function extensionFor(string $contentType, string $filename): string
    {
        if (isset(self::CONTENT_TYPES[$contentType])) {
            return self::CONTENT_TYPES[$contentType];
        }

        $fromName = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (isset(self::EXTENSIONS[$fromName])) {
            return self::EXTENSIONS[$fromName];
        }

        throw new \InvalidArgumentException('unsupported_type');
    }

    private function contentTypeForExtension(string $extension): string
    {
        return match ($extension) {
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => throw new \InvalidArgumentException('unsupported_type'),
        };
    }

    private function client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'endpoint' => $this->endpoint,
            'use_path_style_endpoint' => true,
            'request_checksum_calculation' => 'when_required',
            'response_checksum_validation' => 'when_required',
            'credentials' => [
                'key' => $this->accessKey,
                'secret' => $this->secretKey,
            ],
        ]);
    }
}
