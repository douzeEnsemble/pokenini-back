<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ElectionPokemonsList;
use App\Utils\JsonDecoder;

class GetPokemonsApiService extends AbstractApiService
{
    /**
     * @param string[]|string[][] $filters
     */
    public function get(
        string $trainerExternalId,
        string $dexSlug,
        string $electionSlug,
        int $count,
        array $filters,
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
                ),
            ]
        );

        /** @var array{type: string, items: array<int, array{pokemon: array{slug: string, name: string, french_name: string, national_dex_number: int, regional_dex_number: null|int, simplified_name: string, forms_label: string, simplified_french_name: string, forms_french_label: string, icon: string, family_order: int, family_lead: null|array{slug: string}, original_game_bundle: array{slug: string}, order_number: string, game_bundles: array<int, array{slug: string}>, game_bundles_shiny: array<int, array{slug: string}>, small_regular_credit: null|array{credit: string}, small_shiny_credit: null|array{credit: string}, big_regular_credit: null|array{credit: string}, big_shiny_credit: null|array{credit: string}}, forms: array{category: null|array{slug: string, name: string, french_name: string}, regional: null|array{slug: string, name: string, french_name: string}, special: null|array{slug: string, name: string, french_name: string}, variant: null|array{slug: string, name: string, french_name: string}}, types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}}}>} */
        $data = JsonDecoder::decode($json);

        return new ElectionPokemonsList($data);
    }
}
