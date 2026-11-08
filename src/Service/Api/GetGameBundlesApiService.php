<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetGameBundlesApiService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function get(?string $lang = null): array
    {
        $key = KeyMaker::getGameBundlesKey($lang);

        $json = $this->cache->get($key, function () use ($lang) {
            return $this->requestContent(
                'GET',
                '/game_bundles',
                $this->langOptions($lang),
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
