<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

/**
 * Not cached: the games are played all the time.
 */
class GetGameReportsApiService extends AbstractApiService
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/reports/games',
        );

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }
}
