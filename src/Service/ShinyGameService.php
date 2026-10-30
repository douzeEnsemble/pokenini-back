<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiValidationException;
use App\Security\UserTokenService;
use App\Service\Api\ShinyGameApiService;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class ShinyGameService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly ShinyGameApiService $apiService,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDexList(bool $includePremium, bool $includeUnreleased): array
    {
        $queryParams = [];

        if ($includePremium) {
            $queryParams['include_premium_dex'] = '1';
        }

        if ($includeUnreleased) {
            $queryParams['include_unreleased_dex'] = '1';
        }

        return $this->apiService->getDexList($this->getTrainerId(), $queryParams);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGames(): array
    {
        return $this->apiService->getGames($this->getTrainerId());
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function create(string $dexSlug, ?int $roundCount): array
    {
        try {
            return $this->apiService->createGame($this->getTrainerId(), $dexSlug, $roundCount);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function get(string $gameId): array
    {
        try {
            return $this->apiService->getGame($this->getTrainerId(), $gameId);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function getRounds(string $gameId): array
    {
        try {
            return $this->apiService->getRounds($this->getTrainerId(), $gameId);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiValidationException
     */
    public function answer(string $gameId, int $position, bool $isShiny): array
    {
        try {
            return $this->apiService->answer($this->getTrainerId(), $gameId, $position, $isShiny);
        } catch (HttpExceptionInterface $e) {
            throw new ApiValidationException($e->getResponse()->getStatusCode());
        }
    }

    private function getTrainerId(): string
    {
        return $this->userTokenService->getLoggedUserToken();
    }
}
