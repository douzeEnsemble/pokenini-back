<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ModifyFailedException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class ImagePipelineApiService extends AbstractApiService
{
    public function create(string $correlationId): void
    {
        try {
            $this->requestContent('POST', '/istration/image-pipeline-runs', [
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
            $this->requestContent('PATCH', "/istration/image-pipeline-runs/{$correlationId}", [
                'json' => $fields,
            ]);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLatest(): ?array
    {
        try {
            $content = $this->requestContent('GET', '/istration/image-pipeline-runs/latest');
        } catch (ClientExceptionInterface $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                return null;
            }

            throw new ModifyFailedException($e->getMessage(), previous: $e);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }

        /** @var array<string, mixed> */
        return json_decode($content, true);
    }
}
