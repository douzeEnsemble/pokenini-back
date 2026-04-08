<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetReportsApiService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        $key = KeyMaker::getReportsKey();

        $json = $this->cache->get($key, function () {
            return $this->requestContent(
                'GET',
                '/reports',
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
