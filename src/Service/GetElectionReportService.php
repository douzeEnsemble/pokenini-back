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
     * @return array{
     *   top: array<int, array{
     *     pokemon: array{
     *       slug: string,
     *       labels: array{name: string, simplified_name: string, french_name: string, simplified_french_name: string, forms_label: null|string, forms_french_label: null|string},
     *       national_dex_number: int,
     *       regional_dex_number: null|int,
     *       icon: string,
     *       family_order: int,
     *       family_lead: array{slug: string},
     *       original_game_bundle: array{slug: string},
     *       order_number: string,
     *       game_bundles: array{normal: array<int, array{slug: string}>, shiny: array<int, array{slug: string}>},
     *     },
     *     forms: null|array{variant?: array{slug: string, name: string, french_name: string}, regional?: array{slug: string, name: string, french_name: string}, special?: array{slug: string, name: string, french_name: string}},
     *     types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}},
     *     score: array{elo: int, significance: bool},
     *   }>,
     *   metrics: ElectionMetrics,
     * }
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
