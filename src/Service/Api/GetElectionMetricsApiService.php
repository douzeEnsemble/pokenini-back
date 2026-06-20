<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class GetElectionMetricsApiService extends AbstractApiService
{
    /**
     * @return float[]|int[]
     */
    public function getMetrics(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
    ): array {
        $json = $this->requestContent(
            'GET',
            '/election/metrics',
            [
                'query' => [
                    'trainer_external_id' => $trainerId,
                    'dex_slug' => $dexSlug,
                    'election_slug' => $electionSlug,
                ],
            ],
        );

        /** @var array{
         *  view_count: array{sum: int, max: int},
         *  win_count: array{sum: int, max: int},
         *  completion: array{under_max_count: int, at_max_count: int},
         *  dex_total_count: int,
         * } $raw
         */
        $raw = JsonDecoder::decode($json);

        /** @var float[]|int[] */
        return [
            'view_count_sum' => $raw['view_count']['sum'],
            'win_count_sum' => $raw['win_count']['sum'],
            'view_count_max' => $raw['view_count']['max'],
            'win_count_max' => $raw['win_count']['max'],
            'under_max_count' => $raw['completion']['under_max_count'],
            'at_max_count' => $raw['completion']['at_max_count'],
            'dex_total_count' => $raw['dex_total_count'],
        ];
    }
}
