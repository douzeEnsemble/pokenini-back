<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetCreditsApiService extends AbstractApiService
{
    /**
     * @return array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}>
     */
    public function get(): array
    {
        $key = KeyMaker::getCreditsKey();

        $json = $this->cache->get($key, function () {
            return $this->requestContent(
                'GET',
                '/credits',
            );
        });

        /** @var array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}> */
        return JsonDecoder::decode($json);
    }
}
