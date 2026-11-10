<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class GetElectionReportApiService extends AbstractApiService
{
    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array{
     *   top: array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>, score: array{elo: int, significance: bool}}>,
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
        ?string $lang = null,
    ): array {
        $json = $this->requestContent(
            'GET',
            "/election/{$trainerId}/{$dexSlug}",
            [
                'query' => [
                    'election_slug' => $electionSlug,
                    'count' => $count,
                ] + (null === $lang ? [] : ['lang' => $lang]),
            ],
        );

        /** @var array{top: array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>, score: array{elo: int, significance: bool}}>, metrics: array{view_count: array{sum: int, max: int}, win_count: array{sum: int, max: int}, completion: array{under_max_count: int, at_max_count: int}, dex_total_count: int}} */
        return JsonDecoder::decode($json);
    }
}
