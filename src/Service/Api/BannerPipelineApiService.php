<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ModifyFailedException;
use App\Utils\JsonDecoder;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class BannerPipelineApiService extends AbstractApiService
{
    public function create(string $correlationId): void
    {
        try {
            $this->requestContent('POST', '/istration/banner-pipeline-runs', [
                'json' => ['correlationId' => $correlationId],
            ]);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @param array<string, int|string> $fields
     */
    public function updateFields(string $correlationId, array $fields): void
    {
        try {
            $this->requestContent('PATCH', "/istration/banner-pipeline-runs/{$correlationId}", [
                'json' => $fields,
            ]);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @return null|array<string, mixed>
     */
    public function getLatest(): ?array
    {
        try {
            $content = $this->requestContent('GET', '/istration/banner-pipeline-runs/latest');
        } catch (ClientExceptionInterface $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                return null;
            }

            throw new ModifyFailedException($e->getMessage(), previous: $e);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }

        /** @var array<string, mixed> */
        return JsonDecoder::decode($content);
    }
}
