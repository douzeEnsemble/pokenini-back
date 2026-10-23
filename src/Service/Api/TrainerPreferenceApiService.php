<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class TrainerPreferenceApiService extends AbstractApiService
{
    /**
     * @return array<string, mixed>
     */
    public function get(string $trainerId): array
    {
        $json = $this->requestContent('GET', "/trainer_preference/{$trainerId}");

        /** @var array<string, mixed> */
        return JsonDecoder::decode($json);
    }

    public function modifyHomePage(string $homePage, string $trainerId): void
    {
        $body = json_encode(
            [
                'home_page' => $homePage,
            ],
            JSON_THROW_ON_ERROR
        );

        $this->requestContent(
            'PUT',
            "/trainer_preference/{$trainerId}",
            [
                'body' => $body,
            ]
        );
    }
}
