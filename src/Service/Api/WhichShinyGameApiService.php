<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class WhichShinyGameApiService extends AbstractApiService
{
    /**
     * @param string[] $queryParams
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDexList(string $trainerId, array $queryParams = []): array
    {
        $urlQueryParams = http_build_query($queryParams);

        $json = $this->requestContent('GET', "/which_shiny_game/{$trainerId}/dex?{$urlQueryParams}");

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGames(string $trainerId): array
    {
        $key = KeyMaker::getGamesKeyForTrainer('which_shiny_game', $trainerId);

        $json = $this->cache->get($key, function (ItemInterface $item) use ($trainerId) {
            $item->tag([
                KeyMaker::getTrainerIdKey($trainerId),
            ]);

            return $this->requestContent('GET', "/which_shiny_game/{$trainerId}/games");
        });

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function createGame(string $trainerId, string $dexSlug, ?int $roundCount): array
    {
        try {
            $json = $this->requestContent(
                'POST',
                "/which_shiny_game/{$trainerId}/games",
                [
                    'body' => json_encode(['dex_slug' => $dexSlug, 'round_count' => $roundCount], JSON_THROW_ON_ERROR),
                ]
            );
        } finally {
            // The list of games changes with every new game and every answer
            $this->cache->delete(KeyMaker::getGamesKeyForTrainer('which_shiny_game', $trainerId));
        }

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGame(string $trainerId, string $gameId): array
    {
        $json = $this->requestContent('GET', "/which_shiny_game/{$trainerId}/games/{$gameId}");

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRounds(string $trainerId, string $gameId): array
    {
        $json = $this->requestContent('GET', "/which_shiny_game/{$trainerId}/games/{$gameId}/rounds");

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(string $trainerId, string $gameId, int $position, int $slot): array
    {
        try {
            $json = $this->requestContent(
                'POST',
                "/which_shiny_game/{$trainerId}/games/{$gameId}/answers",
                [
                    'body' => json_encode(['position' => $position, 'slot' => $slot], JSON_THROW_ON_ERROR),
                ]
            );
        } finally {
            // The list of games changes with every new game and every answer
            $this->cache->delete(KeyMaker::getGamesKeyForTrainer('which_shiny_game', $trainerId));
        }

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }
}
