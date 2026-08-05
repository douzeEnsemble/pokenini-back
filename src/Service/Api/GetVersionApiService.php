<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class GetVersionApiService extends AbstractApiService
{
    public function get(): ?string
    {
        try {
            $content = $this->requestContent('GET', '/version');

            /** @var array{version: string} $decoded */
            $decoded = JsonDecoder::decode($content);

            return $decoded['version'];
        } catch (ExceptionInterface|\JsonException) {
            return null;
        }
    }
}
