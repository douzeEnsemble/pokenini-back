<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetCreditsApiService extends AbstractApiService
{
    /**
     * @return array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name?: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}>
     */
    public function get(?string $lang = null): array
    {
        $key = KeyMaker::getCreditsKey($lang);

        $json = $this->cache->get($key, function () use ($lang) {
            return $this->requestContent(
                'GET',
                '/credits',
                $this->langOptions($lang),
            );
        });

        /** @var array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name?: string, pokemon_icon: string, small_regular_credit: ?array{credit: string}, small_shiny_credit: ?array{credit: string}, big_regular_credit: ?array{credit: string}, big_shiny_credit: ?array{credit: string}}> */
        return JsonDecoder::decode($json);
    }
}
