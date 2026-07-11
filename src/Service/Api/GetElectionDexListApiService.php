<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetElectionDexListApiService extends AbstractApiService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWithPremium(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWithUnreleasedAndPremium(string $trainerId): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ]);
    }

    /**
     * @param string[] $queryParams
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDexWithParam(string $trainerId, array $queryParams = []): array
    {
        $key = KeyMaker::getElectionDexListKeyForTrainer($trainerId, $queryParams);

        $urlQueryParams = http_build_query(array_merge(['count' => '0'], $queryParams));

        $json = $this->cache->get($key, function (ItemInterface $item) use ($trainerId, $urlQueryParams) {
            $item->tag([
                KeyMaker::getDexKey(),
                KeyMaker::getElectionDexListKey(),
                KeyMaker::getTrainerIdKey($trainerId),
            ]);

            return $this->requestContent(
                'GET',
                "/election/{$trainerId}/list?{$urlQueryParams}",
            );
        });

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }
}
