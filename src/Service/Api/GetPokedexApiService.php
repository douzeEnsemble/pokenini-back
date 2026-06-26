<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class GetPokedexApiService extends AbstractApiService
{
    /**
     * @param string[]|string[][] $filters
     *
     * @return array{
     *   dex: null|array<string, mixed>,
     *   pokemons: array<int, array<string, mixed>>,
     *   report?: array<string, mixed>,
     *   filtered_report?: array<string, mixed>,
     * }
     */
    public function get(
        string $dexSlug,
        string $trainerId,
        array $filters = [],
    ): array {
        $key = KeyMaker::getPokedexKey($dexSlug, $trainerId, $filters);

        $json = $this->cache->get($key, function (ItemInterface $item) use ($dexSlug, $trainerId, $filters) {
            $item->tag([
                KeyMaker::getAlbumKey(),
                KeyMaker::getTrainerIdKey($trainerId),
            ]);

            $url = "/album/{$trainerId}/{$dexSlug}";

            return $this->requestContent(
                'GET',
                $url,
                [
                    'query' => $filters,
                ],
            );
        });

        /** @var array{dex: null|array<string, mixed>, pokemons: array<int, array<string, mixed>>, report?: array<string, mixed>, filtered_report?: array<string, mixed>} */
        return JsonDecoder::decode($json);
    }
}
