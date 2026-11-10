<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetElectionDexListApiService extends AbstractApiService
{
    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(string $trainerId, ?string $lang = null): array
    {
        return $this->getDexWithParam($trainerId, [], $lang);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWithPremium(string $trainerId, ?string $lang = null): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_premium_dex' => '1',
        ], $lang);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWithUnreleasedAndPremium(string $trainerId, ?string $lang = null): array
    {
        return $this->getDexWithParam($trainerId, [
            'include_unreleased_dex' => '1',
            'include_premium_dex' => '1',
        ], $lang);
    }

    /**
     * @param string[]    $queryParams
     * @param null|string $lang        the language of the names, null for the legacy ones (English and French)
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDexWithParam(string $trainerId, array $queryParams, ?string $lang): array
    {
        $key = KeyMaker::getElectionDexListKeyForTrainer($trainerId, $queryParams, $lang);

        $urlQueryParams = http_build_query(
            array_merge(['count' => '1'], $queryParams, null === $lang ? [] : ['lang' => $lang]),
        );

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
