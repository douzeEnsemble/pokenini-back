<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ModifyFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GithubQueryApiService implements ApiServiceInterface
{
    private const string GITHUB_API_URL = 'https://api.github.com';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $client,
        private readonly string $githubImagesWorkflowToken,
    ) {}

    /**
     * @return array{id: int, status: string, conclusion: ?string, htmlUrl: string, headSha: string}|null
     */
    public function findWorkflowRunByDisplayTitle(string $repo, string $workflowFile, string $displayTitle): ?array
    {
        foreach ($this->listWorkflowRuns($repo, $workflowFile) as $run) {
            if (($run['display_title'] ?? null) === $displayTitle) {
                return $this->mapRun($run);
            }
        }

        return null;
    }

    /**
     * @return array{id: int, status: string, conclusion: ?string, htmlUrl: string, headSha: string}|null
     */
    public function findWorkflowRunByHeadSha(string $repo, string $workflowFile, string $headSha): ?array
    {
        $runs = $this->listWorkflowRuns($repo, $workflowFile, $headSha);

        return isset($runs[0]) ? $this->mapRun($runs[0]) : null;
    }

    /**
     * @return array{number: int, htmlUrl: string, state: string, mergedAt: ?string, mergeCommitSha: ?string}|null
     */
    public function findPullRequestByBranch(string $repo, string $branch): ?array
    {
        $owner = explode('/', $repo)[0];
        $endpointUrl = self::GITHUB_API_URL."/repos/{$repo}/pulls?head=".rawurlencode("{$owner}:{$branch}").'&state=all';

        /** @var list<array<string, mixed>> $prs */
        $prs = $this->request($endpointUrl);

        return isset($prs[0]) ? $this->mapPullRequest($prs[0]) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listWorkflowRuns(string $repo, string $workflowFile, ?string $headSha = null): array
    {
        $endpointUrl = self::GITHUB_API_URL."/repos/{$repo}/actions/workflows/{$workflowFile}/runs";

        if (null !== $headSha) {
            $endpointUrl .= '?head_sha='.rawurlencode($headSha);
        }

        /** @var array{workflow_runs: list<array<string, mixed>>} $data */
        $data = $this->request($endpointUrl);

        return $data['workflow_runs'];
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $endpointUrl): array
    {
        try {
            $this->logger->info("Requesting GET {$endpointUrl}", []);

            $response = $this->client->request(
                'GET',
                $endpointUrl,
                [
                    'headers' => [
                        'accept' => 'application/vnd.github+json',
                        'authorization' => "Bearer {$this->githubImagesWorkflowToken}",
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                ],
            );

            return $response->toArray();
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @param array<string, mixed> $run
     *
     * @return array{id: int, status: string, conclusion: ?string, htmlUrl: string, headSha: string}
     */
    private function mapRun(array $run): array
    {
        return [
            'id' => (int) $run['id'],
            'status' => (string) $run['status'],
            'conclusion' => null !== $run['conclusion'] ? (string) $run['conclusion'] : null,
            'htmlUrl' => (string) $run['html_url'],
            'headSha' => (string) $run['head_sha'],
        ];
    }

    /**
     * @param array<string, mixed> $pr
     *
     * @return array{number: int, htmlUrl: string, state: string, mergedAt: ?string, mergeCommitSha: ?string}
     */
    private function mapPullRequest(array $pr): array
    {
        return [
            'number' => (int) $pr['number'],
            'htmlUrl' => (string) $pr['html_url'],
            'state' => null !== $pr['merged_at'] ? 'merged' : (string) $pr['state'],
            'mergedAt' => null !== $pr['merged_at'] ? (string) $pr['merged_at'] : null,
            'mergeCommitSha' => null !== $pr['merge_commit_sha'] ? (string) $pr['merge_commit_sha'] : null,
        ];
    }
}
