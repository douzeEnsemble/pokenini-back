<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetCreditsApiService extends AbstractApiService
{
    /**
     * @return array<int, array{name: string, url: string}>
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

        /** @var array<int, array{name: string, url: string}> */
        return JsonDecoder::decode($json);
    }
}
