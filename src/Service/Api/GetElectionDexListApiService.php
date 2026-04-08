<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetElectionDexListApiService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function get(): array
    {
        return $this->getDexWithParam([]);
    }

    /**
     * @return string[][]
     */
    public function getWithPremium(): array
    {
        return $this->getDexWithParam([
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @return string[][]
     */
    public function getWithUnreleasedAndPremium(): array
    {
        return $this->getDexWithParam([
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @param string[] $queryParams
     *
     * @return string[][]
     */
    private function getDexWithParam(array $queryParams = []): array
    {
        $key = KeyMaker::getElectionDexListKey($queryParams);

        $urlQueryParams = http_build_query($queryParams);

        $json = $this->cache->get($key, function (ItemInterface $item) use ($urlQueryParams) {
            $item->tag([
                KeyMaker::getDexKey(),
                KeyMaker::getElectionDexListKey(),
            ]);

            return $this->requestContent(
                'GET',
                '/dex/can_hold_election'.($urlQueryParams ? '?'.$urlQueryParams : ''),
            );
        });

        /** @var string[][] */
        return JsonDecoder::decode($json);
    }
}
