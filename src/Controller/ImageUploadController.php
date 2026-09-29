<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SupabaseStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageUploadController extends AbstractController
{
    public function __construct(
        private readonly SupabaseStorageService $storage,
    ) {
    }

    #[Route('/images/upload-url', name: 'app_image_upload_url', methods: ['POST'])]
    public function uploadUrl(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->storage->isConfigured()) {
            return $this->json(['error' => 'not_configured'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $payload = $request->toArray();
        } catch (JsonException) {
            return $this->json(['error' => 'invalid_json'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfTokenValid('image_upload', (string) ($payload['_token'] ?? ''))) {
            throw $this->createAccessDeniedException();
        }

        $contentType = (string) ($payload['contentType'] ?? '');
        $filename = (string) ($payload['filename'] ?? '');

        try {
            return $this->json($this->storage->createUpload($contentType, $filename));
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'unsupported_type'], Response::HTTP_BAD_REQUEST);
        }
    }
}
