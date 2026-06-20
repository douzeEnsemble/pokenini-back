<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class GetElectionTopApiService extends AbstractApiService
{
    /**
     * @return array<int, array{
     *   pokemon: array{slug: string, labels: array{name: string, french_name: string}, national_dex_number: int},
     *   forms: null|array{variant?: array{slug: string, name: string, french_name: string}, regional?: array{slug: string, name: string, french_name: string}, special?: array{slug: string, name: string, french_name: string}},
     *   types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}},
     *   score: array{elo: int, significance: bool},
     * }>
     */
    public function getTop(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
        int $count,
    ): array {
        $json = $this->requestContent(
            'GET',
            "/election/top?trainer_external_id={$trainerId}&dex_slug={$dexSlug}&election_slug={$electionSlug}&count={$count}"
        );

        /** @var array<int, array{pokemon: array{slug: string, labels: array{name: string, french_name: string}, national_dex_number: int}, forms: null|array{variant?: array{slug: string, name: string, french_name: string}, regional?: array{slug: string, name: string, french_name: string}, special?: array{slug: string, name: string, french_name: string}}, types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}}, score: array{elo: int, significance: bool}}> */
        return JsonDecoder::decode($json);
    }
}
