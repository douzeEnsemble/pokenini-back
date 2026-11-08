<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetCatchStatesApiService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function get(?string $lang = null): array
    {
        $key = KeyMaker::getCatchStatesKey($lang);

        $json = $this->cache->get($key, function () use ($lang) {
            return $this->requestContent(
                'GET',
                '/catch_states',
                $this->langOptions($lang),
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
