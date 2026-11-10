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
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array{
     *   top: array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>, score: array{elo: int, significance: bool}}>,
     *   metrics: ElectionMetrics,
     * }
     */
    public function getReport(string $dexSlug, string $electionSlug, ?string $lang = null): array
    {
        $trainerId = $this->userTokenService->getLoggedUserToken();

        $data = $this->apiService->getReport($trainerId, $dexSlug, $electionSlug, $this->topCount, $lang);

        return [
            'top' => $data['top'],
            'metrics' => new ElectionMetrics($data['metrics'], $this->electionCandidateCount),
        ];
    }
}
