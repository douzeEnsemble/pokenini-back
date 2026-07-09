<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class GetElectionReportApiService extends AbstractApiService
{
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
     *   metrics: array{
     *     view_count: array{sum: int, max: int},
     *     win_count: array{sum: int, max: int},
     *     completion: array{under_max_count: int, at_max_count: int},
     *     dex_total_count: int,
     *   },
     * }
     */
    public function getReport(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): array {
        $json = $this->requestContent(
            'GET',
            "/election/{$trainerId}/{$dexSlug}",
            [
                'query' => [
                    'election_slug' => $electionSlug,
                    'count' => $count,
                ],
            ],
        );

        /** @var array{top: array<int, array{pokemon: array{slug: string, labels: array{name: string, simplified_name: string, french_name: string, simplified_french_name: string, forms_label: null|string, forms_french_label: null|string}, national_dex_number: int, regional_dex_number: null|int, icon: string, family_order: int, family_lead: array{slug: string}, original_game_bundle: array{slug: string}, order_number: string, game_bundles: array{normal: array<int, array{slug: string}>, shiny: array<int, array{slug: string}>}}, forms: null|array{variant?: array{slug: string, name: string, french_name: string}, regional?: array{slug: string, name: string, french_name: string}, special?: array{slug: string, name: string, french_name: string}}, types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}}, score: array{elo: int, significance: bool}}>, metrics: array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int}} */
        return JsonDecoder::decode($json);
    }
}
