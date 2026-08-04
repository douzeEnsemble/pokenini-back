<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class TrainerDexLinkApiService extends AbstractApiService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(string $dexSlug, string $trainerId): array
    {
        $json = $this->requestContent('GET', "/trainer_dex_link/{$trainerId}/{$dexSlug}");

        /** @var array<int, array<string, mixed>> */
        return JsonDecoder::decode($json);
    }

    public function create(
        string $dexSlug,
        string $targetDexSlug,
        bool $bidirectional,
        string $trainerId,
    ): void {
        $body = json_encode(
            [
                'sourceDexSlug' => $dexSlug,
                'targetDexSlug' => $targetDexSlug,
                'bidirectional' => $bidirectional,
            ],
            JSON_THROW_ON_ERROR
        );

        $this->requestContent(
            'POST',
            "/trainer_dex_link/{$trainerId}",
            [
                'body' => $body,
            ]
        );
    }

    public function delete(string $linkId, string $trainerId): void
    {
        $this->requestContent('DELETE', "/trainer_dex_link/{$trainerId}/{$linkId}");
    }
}
