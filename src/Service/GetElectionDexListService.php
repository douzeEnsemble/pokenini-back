<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ElectionMetrics;
use App\Service\Api\GetElectionDexListApiService;

class GetElectionDexListService
{
    public function __construct(
        private readonly GetElectionDexListApiService $apiService,
        private readonly int $electionCandidateCount,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(string $trainerId): array
    {
        return $this->withRoundMetrics($this->apiService->get($trainerId));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWithPremium(string $trainerId): array
    {
        return $this->withRoundMetrics($this->apiService->getWithPremium($trainerId));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWithUnreleasedAndPremium(string $trainerId): array
    {
        return $this->withRoundMetrics($this->apiService->getWithUnreleasedAndPremium($trainerId));
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function withRoundMetrics(array $items): array
    {
        foreach ($items as &$item) {
            $report = $item['report'] ?? null;

            if (!\is_array($report) || !isset($report['metrics'])) {
                continue;
            }

            /** @var array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} $rawMetrics */
            $rawMetrics = $report['metrics'];

            $metrics = new ElectionMetrics($rawMetrics, $this->electionCandidateCount);

            $report['metrics'] = $metrics->raw + [
                'round_count' => $metrics->roundCount,
                'total_round_count' => $metrics->totalRoundCount,
                'winner_average' => $metrics->winnerAverage,
            ];

            $item['report'] = $report;
        }

        return $items;
    }
}
