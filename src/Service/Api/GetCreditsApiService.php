<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetCreditsApiService extends AbstractApiService
{
    /**
     * @return array<int, array{credit: string, images: array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}>}>
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

        /** @var array<int, array{credit: string, images: array<int, array{pokemon_slug: string, pokemon_name: string, pokemon_french_name: string, pokemon_icon: string, size: string, is_shiny: bool}>}> */
        return JsonDecoder::decode($json);
    }
}
