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
     *   pokemon: array{
     *     slug: string,
     *     labels: array{name: string, simplified_name: string, french_name: string, simplified_french_name: string, forms_label: null|string, forms_french_label: null|string},
     *     national_dex_number: int,
     *     regional_dex_number: null|int,
     *     icon: string,
     *     family_order: int,
     *     family_lead: array{slug: string},
     *     original_game_bundle: array{slug: string},
     *     order_number: string,
     *     game_bundles: array{normal: array<int, array{slug: string}>, shiny: array<int, array{slug: string}>},
     *   },
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
