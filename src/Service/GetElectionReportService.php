<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionMetrics;
use App\Security\UserTokenService;
use App\Service\Api\GetElectionReportApiService;

class GetElectionReportService
{
    public function __construct(
        private readonly UserTokenService $userTokenService,
        private readonly GetElectionReportApiService $apiService,
        private readonly int $topCount,
        private readonly int $electionCandidateCount,
    ) {}

    /**
     * @return array{top: array<int, array<string, mixed>>, metrics: ElectionMetrics}
     */
    public function getReport(string $dexSlug, string $electionSlug): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $data = $this->apiService->getReport($trainerId, $dexSlug, $electionSlug, $this->topCount);

        return [
            'top' => $data['top'],
            'metrics' => new ElectionMetrics($data['metrics'], $this->electionCandidateCount),
        ];
    }
}
