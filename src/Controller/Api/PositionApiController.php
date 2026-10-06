<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PositionRepository;
use App\Service\PositionAggregateService;
use App\Service\PositionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PositionApiController extends AbstractController
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly PositionAggregateService $aggregates,
        private readonly PositionService $positionService,
        #[Autowire('%env(ODOO_EXPORT_TOKEN)%')]
        private readonly string $exportToken,
    ) {
    }

    #[Route('/api/positions', name: 'app_api_position', methods: ['GET'])]
    public function show(Request $request): JsonResponse
    {
        $token = $this->bearerToken($request);
        if ($token === null) {
            return $this->unauthorized();
        }

        $position = $this->positions->findOneByApiToken($token);
        if ($position === null) {
            return $this->unauthorized();
        }

        return new JsonResponse($this->aggregates->build($position));
    }

    #[Route('/api/positions', name: 'app_api_position_export', methods: ['POST'])]
    public function export(Request $request): JsonResponse
    {
        $token = $this->bearerToken($request);
        if ($this->exportToken === '' || $token === null || !hash_equals($this->exportToken, $token)) {
            return $this->unauthorized();
        }

        $payload = json_decode($request->getContent(), true);
        $title = is_array($payload) ? ($payload['title'] ?? null) : null;
        $attributes = is_array($payload) ? ($payload['attributes'] ?? null) : null;
        if (!is_string($title) || trim($title) === '' || !is_array($attributes)) {
            return new JsonResponse(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
        }

        $rows = [];
        foreach ($attributes as $row) {
            if (!is_array($row)) {
                return new JsonResponse(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
            }
            $rows[] = $row;
        }

        return new JsonResponse(
            $this->positionService->createExported(trim($title), $rows),
            Response::HTTP_CREATED,
        );
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->headers->get('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token === '' ? null : $token;
    }

    private function unauthorized(): JsonResponse
    {
        return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
