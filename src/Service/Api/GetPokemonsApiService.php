<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ElectionPokemonsList;
use App\Utils\JsonDecoder;

class GetPokemonsApiService extends AbstractApiService
{
    /**
     * @param string[]|string[][] $filters
     * @param null|string         $lang    the language of the names, null for the legacy ones (English and French)
     */
    public function get(
        string $trainerExternalId,
        string $dexSlug,
        string $electionSlug,
        int $count,
        array $filters,
        ?string $lang = null,
    ): ElectionPokemonsList {
        $json = $this->requestContent(
            'GET',
            '/pokemons/to_choose',
            [
                'query' => array_merge(
                    [
                        'trainer_external_id' => $trainerExternalId,
                        'dex_slug' => $dexSlug,
                        'election_slug' => $electionSlug,
                        'count' => $count,
                    ],
                    $filters,
                    null === $lang ? [] : ['lang' => $lang],
                ),
            ]
        );

        /** @var array{type: string, items: array<int, array{pokemon: array<string, mixed>, forms: null|array<string, mixed>, types: array<string, mixed>}>} */
        $data = JsonDecoder::decode($json);

        return new ElectionPokemonsList($data);
    }
}
