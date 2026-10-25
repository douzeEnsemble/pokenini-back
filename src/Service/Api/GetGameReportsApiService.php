<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetGameReportsApiService extends AbstractApiService
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $key = KeyMaker::getGameReportsKey();

        $json = $this->cache->get($key, function () {
            return $this->requestContent(
                'GET',
                '/reports/games',
            );
        });

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }
}
