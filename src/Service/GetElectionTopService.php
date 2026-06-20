<?php

declare(strict_types=1);

namespace App\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionTopApiService;

class GetElectionTopService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetElectionTopApiService $apiService,
        private readonly int $topCount,
    ) {}

    /**
     * @return array<int, array{
     *   pokemon: array{slug: string, labels: array{name: string, french_name: string}, national_dex_number: int},
     *   forms: null|array{variant?: array{slug: string, name: string, french_name: string}, regional?: array{slug: string, name: string, french_name: string}, special?: array{slug: string, name: string, french_name: string}},
     *   types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}},
     *   score: array{elo: int, significance: bool},
     * }>
     */
    public function getTop(string $dexSlug, string $electionSlug): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        return $this->apiService->getTop($trainerId, $dexSlug, $electionSlug, $this->topCount);
    }
}
