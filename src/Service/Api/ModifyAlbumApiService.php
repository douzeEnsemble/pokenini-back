<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;
use Symfony\Component\HttpFoundation\Request;

class ModifyAlbumApiService extends AbstractApiService
{
    /**
     * @return list<string>
     */
    public function modify(
        string $method,
        string $dexSlug,
        string $pokemonSlug,
        string $catchStateSlug,
        string $trainerId
    ): array {
        if (!in_array($method, [Request::METHOD_PATCH, Request::METHOD_PUT], true)) {
            throw new \InvalidArgumentException();
        }

        $json = $this->requestContent(
            $method,
            "/album/{$trainerId}/{$dexSlug}/{$pokemonSlug}",
            [
                'body' => $catchStateSlug,
            ]
        );

        /** @var array{updatedDexSlugs: list<string>} */
        $decoded = JsonDecoder::decode($json);

        return $decoded['updatedDexSlugs'];
    }
}
