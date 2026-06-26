<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class GetElectionMetricsApiService extends AbstractApiService
{
    /**
     * @return array{
     *   view_count: array{sum: int, max: int},
     *   win_count: array{sum: int, max: int},
     *   completion: array{under_max_count: int, at_max_count: int},
     *   dex_total_count: int,
     * }
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

        /** @var array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int} */
        return JsonDecoder::decode($json);
    }
}
