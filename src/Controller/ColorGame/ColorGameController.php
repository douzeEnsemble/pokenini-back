<?php

declare(strict_types=1);

namespace App\Controller\ColorGame;

use App\Exception\ApiValidationException;
use App\Service\ColorGameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/color_game')]
final class ColorGameController extends AbstractController
{
    public function __construct(
        private readonly ColorGameService $colorGameService,
    ) {}

    #[Route('/dex', methods: ['GET'])]
    public function dex(): JsonResponse
    {
        return new JsonResponse($this->getPlayableDexList(), Response::HTTP_OK);
    }

    #[Route('/games', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->colorGameService->getGames(), Response::HTTP_OK);
    }

    #[Route('/games', methods: ['POST'])]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function create(Request $request): JsonResponse
    {
        /** @var array{dex_slug?: mixed} $content */
        $content = $this->decodeContent($request);
        $dexSlug = $content['dex_slug'] ?? null;

        if (!\is_string($dexSlug)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isPlayable($dexSlug)) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        try {
            $game = $this->colorGameService->create($dexSlug);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($game, Response::HTTP_CREATED);
    }

    #[Route('/games/{gameId}', requirements: ['gameId' => Requirement::UUID], methods: ['GET'])]
    public function show(string $gameId): JsonResponse
    {
        try {
            $state = $this->colorGameService->get($gameId);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($state, Response::HTTP_OK);
    }

    #[Route('/games/{gameId}/answers', requirements: ['gameId' => Requirement::UUID], methods: ['POST'])]
    public function answer(string $gameId, Request $request): JsonResponse
    {
        /** @var array{position?: mixed, choice?: mixed} $content */
        $content = $this->decodeContent($request);
        $position = $content['position'] ?? null;
        $choice = $content['choice'] ?? null;

        if (!\is_int($position) || !\is_int($choice)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $answer = $this->colorGameService->answer($gameId, $position, $choice);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($answer, Response::HTTP_OK);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPlayableDexList(): array
    {
        return $this->colorGameService->getDexList(
            $this->isGranted('ROLE_COLLECTOR'),
            $this->isGranted('ROLE_ADMIN'),
        );
    }

    private function isPlayable(string $dexSlug): bool
    {
        foreach ($this->getPlayableDexList() as $dex) {
            if ($dexSlug === ($dex['slug'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decodeContent(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (JsonException) {
            return [];
        }
    }
}
