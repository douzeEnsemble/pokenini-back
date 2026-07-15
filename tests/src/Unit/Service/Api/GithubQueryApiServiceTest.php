<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\GithubQueryApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GithubQueryApiService::class)]
final class GithubQueryApiServiceTest extends TestCase
{
    public function testFindWorkflowRunByDisplayTitleFindsMatch(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'workflow_runs' => [
                [
                    'id' => 1,
                    'display_title' => 'Update images (other-corr)',
                    'status' => 'completed',
                    'conclusion' => 'success',
                    'html_url' => 'https://github.com/x/y/actions/runs/1',
                    'head_sha' => 'sha1',
                ],
                [
                    'id' => 2,
                    'display_title' => 'Update images (corr-123)',
                    'status' => 'completed',
                    'conclusion' => 'success',
                    'html_url' => 'https://github.com/x/y/actions/runs/2',
                    'head_sha' => 'sha2',
                ],
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.github.com/repos/douzeensemble/pokenini-icon/actions/workflows/update-images.yml/runs',
                [
                    'headers' => [
                        'accept' => 'application/vnd.github+json',
                        'authorization' => 'Bearer secret-token',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                ],
            )
            ->willReturn($response)
        ;

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $run = $service->findWorkflowRunByDisplayTitle('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-123)');

        $this->assertSame([
            'id' => 2,
            'status' => 'completed',
            'conclusion' => 'success',
            'htmlUrl' => 'https://github.com/x/y/actions/runs/2',
            'headSha' => 'sha2',
        ], $run);
    }

    public function testFindWorkflowRunByDisplayTitleReturnsNullWhenNoMatch(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['workflow_runs' => []]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $this->assertNull($service->findWorkflowRunByDisplayTitle('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-123)'));
    }

    public function testFindWorkflowRunByHeadShaFindsMatch(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'workflow_runs' => [
                [
                    'id' => 5,
                    'status' => 'in_progress',
                    'conclusion' => null,
                    'html_url' => 'https://github.com/x/y/actions/runs/5',
                    'head_sha' => 'merge-sha',
                ],
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.github.com/repos/douzeensemble/pokenini-icon/actions/workflows/publish-images-to-resources.yml/runs?head_sha=merge-sha',
            )
            ->willReturn($response)
        ;

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $run = $service->findWorkflowRunByHeadSha('douzeensemble/pokenini-icon', 'publish-images-to-resources.yml', 'merge-sha');

        $this->assertSame([
            'id' => 5,
            'status' => 'in_progress',
            'conclusion' => null,
            'htmlUrl' => 'https://github.com/x/y/actions/runs/5',
            'headSha' => 'merge-sha',
        ], $run);
    }

    public function testFindPullRequestByBranchFindsMatch(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            [
                'number' => 7,
                'html_url' => 'https://github.com/douzeensemble/pokenini-icon/pull/7',
                'state' => 'closed',
                'merged_at' => '2026-07-15T10:00:00Z',
                'merge_commit_sha' => 'merge-sha-7',
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.github.com/repos/douzeensemble/pokenini-icon/pulls?head=douzeensemble%3Aupdate-images-2&state=all',
            )
            ->willReturn($response)
        ;

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $pullRequest = $service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2');

        $this->assertSame([
            'number' => 7,
            'htmlUrl' => 'https://github.com/douzeensemble/pokenini-icon/pull/7',
            'state' => 'merged',
            'mergedAt' => '2026-07-15T10:00:00Z',
            'mergeCommitSha' => 'merge-sha-7',
        ], $pullRequest);
    }

    public function testFindWorkflowRunByHeadShaCastsMismatchedRawTypes(): void
    {
        // GitHub's real API always sends these as strings/int, but the raw
        // array is typed `mixed` - feed deliberately mismatched scalar types
        // here to prove mapRun()'s casts actually convert them, rather than
        // just passing through values that already happened to match.
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'workflow_runs' => [
                [
                    'id' => '9',
                    'status' => 200,
                    'conclusion' => 1,
                    'html_url' => 12345,
                    'head_sha' => 67890,
                ],
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $run = $service->findWorkflowRunByHeadSha('douzeensemble/pokenini-icon', 'publish-images-to-resources.yml', 'sha');

        $this->assertSame([
            'id' => 9,
            'status' => '200',
            'conclusion' => '1',
            'htmlUrl' => '12345',
            'headSha' => '67890',
        ], $run);
    }

    public function testFindPullRequestByBranchCastsMismatchedRawTypes(): void
    {
        // Same rationale as testFindWorkflowRunByHeadShaCastsMismatchedRawTypes:
        // deliberately mismatched raw types prove mapPullRequest()'s casts
        // (and the 'state' passthrough branch) actually convert, not just
        // pass through already-correctly-typed values.
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            [
                'number' => '42',
                'html_url' => 999,
                'state' => 111,
                'merged_at' => null,
                'merge_commit_sha' => 222,
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $pullRequest = $service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2');

        $this->assertSame([
            'number' => 42,
            'htmlUrl' => '999',
            'state' => '111',
            'mergedAt' => null,
            'mergeCommitSha' => '222',
        ], $pullRequest);
    }

    public function testFindPullRequestByBranchCastsMismatchedMergedAtType(): void
    {
        // testFindPullRequestByBranchCastsMismatchedRawTypes leaves merged_at
        // null, which never exercises (string) $mergedAt's cast - cover that
        // branch here with a non-null, mismatched-type value.
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            [
                'number' => 1,
                'html_url' => 'https://github.com/x/y/pull/1',
                'state' => 'closed',
                'merged_at' => 20260715,
                'merge_commit_sha' => 'sha',
            ],
        ]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $pullRequest = $service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2');

        $this->assertSame('20260715', $pullRequest['mergedAt'] ?? null);
    }

    public function testFindPullRequestByBranchReturnsNullWhenNoMatch(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new GithubQueryApiService($this->createMock(LoggerInterface::class), $client, 'secret-token');

        $this->assertNull($service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2'));
    }

    public function testFindWorkflowRunByDisplayTitleFailsOnHttpError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException('Network error'))
        ;

        $service = new GithubQueryApiService(
            $logger,
            $client,
            'secret-token',
        );

        $this->expectException(ModifyFailedException::class);

        $service->findWorkflowRunByDisplayTitle('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-123)');
    }

    public function testFindPullRequestByBranchFailsOnHttpError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException('Network error'))
        ;

        $service = new GithubQueryApiService(
            $logger,
            $client,
            'secret-token',
        );

        $this->expectException(ModifyFailedException::class);

        $service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2');
    }

    public function testFindWorkflowRunByDisplayTitleFailsOnHttpErrorStatus(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response)
        ;

        $service = new GithubQueryApiService(
            $logger,
            $client,
            'secret-token',
        );

        $this->expectException(ModifyFailedException::class);

        $service->findWorkflowRunByDisplayTitle('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-123)');
    }

    public function testFindPullRequestByBranchFailsOnHttpErrorStatus(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('toArray')
            ->willThrowException($this->createStub(ClientExceptionInterface::class))
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willReturn($response)
        ;

        $service = new GithubQueryApiService(
            $logger,
            $client,
            'secret-token',
        );

        $this->expectException(ModifyFailedException::class);

        $service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2');
    }
}
