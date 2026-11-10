<?php

declare(strict_types=1);

namespace App\Controller\ColorGame;

use App\Controller\LangQueryTrait;
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
    use LangQueryTrait;

    /**
     * @param string[] $locales the enabled locales
     */
    public function __construct(
        private readonly ColorGameService $colorGameService,
        private readonly array $locales,
    ) {}

    #[Route('/dex', methods: ['GET'])]
    public function dex(Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->getPlayableDexList($this->getLang($request, $this->locales)),
            Response::HTTP_OK,
        );
    }

    #[Route('/games', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->colorGameService->getGames($this->getLang($request, $this->locales)),
            Response::HTTP_OK,
        );
    }

    #[Route('/games', methods: ['POST'])]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function create(Request $request): JsonResponse
    {
        $lang = $this->getLang($request, $this->locales);

        /** @var array{dex_slug?: mixed, round_count?: mixed} $content */
        $content = $this->decodeContent($request);
        $dexSlug = $content['dex_slug'] ?? null;
        // No round count to play the whole dex
        $roundCount = $content['round_count'] ?? null;

        if (!\is_string($dexSlug) || !self::isRoundCount($roundCount)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isPlayable($dexSlug, $lang)) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        try {
            $game = $this->colorGameService->create($dexSlug, $roundCount, $lang);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($game, Response::HTTP_CREATED);
    }

    #[Route('/games/{gameId}', requirements: ['gameId' => Requirement::UUID], methods: ['GET'])]
    public function show(string $gameId, Request $request): JsonResponse
    {
        $lang = $this->getLang($request, $this->locales);

        try {
            $state = $this->colorGameService->get($gameId, $lang);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($state, Response::HTTP_OK);
    }

    #[Route('/games/{gameId}/rounds', requirements: ['gameId' => Requirement::UUID], methods: ['GET'])]
    public function rounds(string $gameId, Request $request): JsonResponse
    {
        $lang = $this->getLang($request, $this->locales);

        try {
            $rounds = $this->colorGameService->getRounds($gameId, $lang);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($rounds, Response::HTTP_OK);
    }

    #[Route('/games/{gameId}/answers', requirements: ['gameId' => Requirement::UUID], methods: ['POST'])]
    public function answer(string $gameId, Request $request): JsonResponse
    {
        $lang = $this->getLang($request, $this->locales);

        /** @var array{position?: mixed, choice?: mixed} $content */
        $content = $this->decodeContent($request);
        $position = $content['position'] ?? null;
        $choice = $content['choice'] ?? null;

        if (!\is_int($position) || !\is_int($choice)) {
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);
        }

        try {
            $answer = $this->colorGameService->answer($gameId, $position, $choice, $lang);
        } catch (ApiValidationException $e) {
            return new JsonResponse([], $e->getStatusCode());
        }

        return new JsonResponse($answer, Response::HTTP_OK);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPlayableDexList(?string $lang = null): array
    {
        return $this->colorGameService->getDexList(
            $this->isGranted('ROLE_COLLECTOR'),
            $this->isGranted('ROLE_ADMIN'),
            $lang,
        );
    }

    private function isPlayable(string $dexSlug, ?string $lang): bool
    {
        foreach ($this->getPlayableDexList($lang) as $dex) {
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
