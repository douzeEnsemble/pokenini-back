<?php

declare(strict_types=1);

namespace App\Controller\ShinyGame;

use App\Exception\ApiValidationException;
use App\Service\ShinyGameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/shiny_game')]
final class ShinyGameController extends AbstractController
{
    public function __construct(
        private readonly ShinyGameService $shinyGameService,
    ) {}

    #[Route('/dex', methods: ['GET'])]
    public function dex(): JsonResponse
    {
        return new JsonResponse($this->getPlayableDexList(), Response::HTTP_OK);
    }

    #[Route('/games', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->shinyGameService->getGames(), Response::HTTP_OK);
    }

    #[Route('/games', methods: ['POST'])]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function create(Request $request): JsonResponse
    {
        /** @var array{dex_slug?: mixed, round_count?: mixed} $content */
        $content = $this->decodeContent($request);
        $dexSlug = $content['dex_slug'] ?? null;
        // No round count to play the whole dex
        $roundCount = $content['round_count'] ?? null;

        if (!\is_string($dexSlug) || !self::isRoundCount($roundCount)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isPlayable($dexSlug)) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        try {
            $game = $this->shinyGameService->create($dexSlug, $roundCount);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($game, Response::HTTP_CREATED);
    }

    #[Route('/games/{gameId}', requirements: ['gameId' => Requirement::UUID], methods: ['GET'])]
    public function show(string $gameId): JsonResponse
    {
        try {
            $state = $this->shinyGameService->get($gameId);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($state, Response::HTTP_OK);
    }

    #[Route('/games/{gameId}/answers', requirements: ['gameId' => Requirement::UUID], methods: ['POST'])]
    public function answer(string $gameId, Request $request): JsonResponse
    {
        /** @var array{position?: mixed, is_shiny?: mixed} $content */
        $content = $this->decodeContent($request);
        $position = $content['position'] ?? null;
        $isShiny = $content['is_shiny'] ?? null;

        if (!\is_int($position) || !\is_bool($isShiny)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $answer = $this->shinyGameService->answer($gameId, $position, $isShiny);
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
        return $this->shinyGameService->getDexList(
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
     * @psalm-assert-if-true null|positive-int $roundCount
     *
     * @phpstan-assert-if-true null|positive-int $roundCount
     */
    private static function isRoundCount(mixed $roundCount): bool
    {
        return null === $roundCount || (\is_int($roundCount) && $roundCount > 0);
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
