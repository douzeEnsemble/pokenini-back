<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class GetVersionApiService extends AbstractApiService
{
    /**
     * @return array{version: ?string, updated_at: ?\DateTimeImmutable}
     */
    public function get(): array
    {
        try {
            $content = $this->requestContent('GET', '/version');

            /** @var array{version: string, updated_at: ?string} $decoded */
            $decoded = JsonDecoder::decode($content);

            return [
                'version' => $decoded['version'],
                'updated_at' => null !== $decoded['updated_at'] ? new \DateTimeImmutable($decoded['updated_at']) : null,
            ];
        } catch (ExceptionInterface|\JsonException) {
            return [
                'version' => null,
                'updated_at' => null,
            ];
        }
    }
}
