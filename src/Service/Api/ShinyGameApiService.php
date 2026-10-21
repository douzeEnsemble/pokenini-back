<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class ShinyGameApiService extends AbstractApiService
{
    /**
     * @param string[] $queryParams
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDexList(string $trainerId, array $queryParams = []): array
    {
        $urlQueryParams = http_build_query($queryParams);

        $json = $this->requestContent('GET', "/shiny_game/{$trainerId}/dex?{$urlQueryParams}");

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGames(string $trainerId): array
    {
        $json = $this->requestContent('GET', "/shiny_game/{$trainerId}/games");

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function createGame(string $trainerId, string $dexSlug): array
    {
        $json = $this->requestContent(
            'POST',
            "/shiny_game/{$trainerId}/games",
            [
                'body' => json_encode(['dex_slug' => $dexSlug], JSON_THROW_ON_ERROR),
            ]
        );

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGame(string $trainerId, string $gameId): array
    {
        $json = $this->requestContent('GET', "/shiny_game/{$trainerId}/games/{$gameId}");

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(string $trainerId, string $gameId, int $position, bool $isShiny): array
    {
        $json = $this->requestContent(
            'POST',
            "/shiny_game/{$trainerId}/games/{$gameId}/answers",
            [
                'body' => json_encode(['position' => $position, 'is_shiny' => $isShiny], JSON_THROW_ON_ERROR),
            ]
        );

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }
}
