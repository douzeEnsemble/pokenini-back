<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;
use Symfony\Contracts\Cache\ItemInterface;

class ColorGameApiService extends AbstractApiService
{
    /**
     * @param string[]    $queryParams
     * @param null|string $lang        the language of the names, null for the legacy ones (English and French)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDexList(string $trainerId, array $queryParams = [], ?string $lang = null): array
    {
        if (null !== $lang) {
            $queryParams['lang'] = $lang;
        }

        $urlQueryParams = http_build_query($queryParams);

        $json = $this->requestContent('GET', "/color_game/{$trainerId}/dex?{$urlQueryParams}");

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGames(string $trainerId, ?string $lang = null): array
    {
        $key = KeyMaker::getGamesKeyForTrainer('color_game', $trainerId, $lang);

        $json = $this->cache->get($key, function (ItemInterface $item) use ($trainerId, $lang) {
            // The games in every language share the tag of the games of the trainer
            $item->tag([
                KeyMaker::getTrainerIdKey($trainerId),
                KeyMaker::getGamesKeyForTrainer('color_game', $trainerId),
            ]);

            return $this->requestContent('GET', self::withLang("/color_game/{$trainerId}/games", $lang));
        });

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<string, mixed>
     */
    public function createGame(string $trainerId, string $dexSlug, ?int $roundCount, ?string $lang = null): array
    {
        try {
            $json = $this->requestContent(
                'POST',
                self::withLang("/color_game/{$trainerId}/games", $lang),
                [
                    'body' => json_encode(['dex_slug' => $dexSlug, 'round_count' => $roundCount], JSON_THROW_ON_ERROR),
                ]
            );
        } finally {
            // The list of games changes with every new game and every answer
            $this->cache->invalidateTags([KeyMaker::getGamesKeyForTrainer('color_game', $trainerId)]);
        }

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<string, mixed>
     */
    public function getGame(string $trainerId, string $gameId, ?string $lang = null): array
    {
        $json = $this->requestContent('GET', self::withLang("/color_game/{$trainerId}/games/{$gameId}", $lang));

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<string, mixed>
     */
    public function getRounds(string $trainerId, string $gameId, ?string $lang = null): array
    {
        $json = $this->requestContent('GET', self::withLang("/color_game/{$trainerId}/games/{$gameId}/rounds", $lang));

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     *
     * @return array<string, mixed>
     */
    public function answer(string $trainerId, string $gameId, int $position, int $choice, ?string $lang = null): array
    {
        try {
            $json = $this->requestContent(
                'POST',
                self::withLang("/color_game/{$trainerId}/games/{$gameId}/answers", $lang),
                [
                    'body' => json_encode(['position' => $position, 'choice' => $choice], JSON_THROW_ON_ERROR),
                ]
            );
        } finally {
            // The list of games changes with every new game and every answer
            $this->cache->invalidateTags([KeyMaker::getGamesKeyForTrainer('color_game', $trainerId)]);
        }

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }
}
