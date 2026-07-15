<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ModifyFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubActionsApiService implements ApiServiceInterface
{
    private const string GITHUB_API_URL = 'https://api.github.com';
    private const string REF = 'main';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $client,
        private readonly string $githubImagesWorkflowToken,
        private readonly string $githubImagesRepo,
        private readonly string $githubImagesWorkflowFile,
    ) {}

    public function dispatchWorkflow(string $correlationId): void
    {
        $endpointUrl = self::GITHUB_API_URL."/repos/{$this->githubImagesRepo}/actions/workflows/{$this->githubImagesWorkflowFile}/dispatches";

        try {
            $this->logger->info("Requesting POST {$endpointUrl}", []);

            $response = $this->client->request(
                'POST',
                $endpointUrl,
                [
                    'headers' => [
                        'accept' => 'application/vnd.github+json',
                        'authorization' => "Bearer {$this->githubImagesWorkflowToken}",
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                    'json' => [
                        'ref' => self::REF,
                        'inputs' => [
                            'correlation_id' => $correlationId,
                        ],
                    ],
                ],
            );

            $response->getHeaders();

            $this->logger->info("Response status code: {$response->getStatusCode()}", []);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }
}
