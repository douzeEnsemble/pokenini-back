<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\ColorGameApiService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class ColorGameService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ColorGameApiService $apiService,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDexList(bool $includePremium, bool $includeUnreleased, ?string $lang = null): array
    {
        $queryParams = [];

        if ($includePremium) {
            $queryParams['include_premium_dex'] = '1';
        }

        if ($includeUnreleased) {
            $queryParams['include_unreleased_dex'] = '1';
        }

        return $this->apiService->getDexList($this->getTrainerId(), $queryParams, $lang);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGames(?string $lang = null): array
    {
        return $this->apiService->getGames($this->getTrainerId(), $lang);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function create(string $dexSlug, ?int $roundCount, ?string $lang = null): array
    {
        try {
            return $this->apiService->createGame($this->getTrainerId(), $dexSlug, $roundCount, $lang);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function get(string $gameId, ?string $lang = null): array
    {
        try {
            return $this->apiService->getGame($this->getTrainerId(), $gameId, $lang);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function getRounds(string $gameId, ?string $lang = null): array
    {
        try {
            return $this->apiService->getRounds($this->getTrainerId(), $gameId, $lang);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function answer(string $gameId, int $position, int $choice, ?string $lang = null): array
    {
        try {
            return $this->apiService->answer($this->getTrainerId(), $gameId, $position, $choice, $lang);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    private function getTrainerId(): string
    {
        return $this->userTokenService->getLoggedUserToken();
    }
}
