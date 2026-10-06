<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PositionRepository;
use App\Service\PositionAggregateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PositionApiController extends AbstractController
{
    public function __construct(
        private readonly PositionRepository $positions,
        private readonly PositionAggregateService $aggregates,
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
