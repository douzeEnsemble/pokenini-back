<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionMetrics;
use App\Security\UserTokenService;
use App\Service\Api\GetElectionMetricsApiService;

class GetElectionMetricsService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetElectionMetricsApiService $apiService,
        private readonly int $electionCandidateCount,
    ) {}

    public function getMetrics(string $dexSlug, string $electionSlug): ElectionMetrics
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $data = $this->apiService->getMetrics($trainerId, $dexSlug, $electionSlug);

        return new ElectionMetrics($data, $this->electionCandidateCount);
    }
}
