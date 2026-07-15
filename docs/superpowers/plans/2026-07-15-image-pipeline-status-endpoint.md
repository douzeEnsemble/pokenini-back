# Image Pipeline Status Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `GET /istration/action/trigger/update_images/status` (query param `refresh`), which reads/updates the pipeline's persisted status in `pokenini-api`, polling GitHub for whichever stage isn't final yet when `refresh` is requested.

**Architecture:** Two new `Service\Api` classes — `GithubQueryApiService` (read-only GitHub queries: workflow runs, PR search, works against either `pokenini-icon` or `pokenini-resources` since the repo is a parameter, not bound) and `ImagePipelineApiService` (extends the existing `AbstractApiService`, since it talks to `pokenini-api` over the same host/auth as `AdminActionApiService`). A new `Service\ImagePipelineStatusService` orchestrates: read latest from pokenini-api → if refresh, poll whichever stage is next → patch pokenini-api → re-fetch and return. `TriggerImagesPipelineService` is extended to generate a correlation id and register the run with pokenini-api right after a successful dispatch.

**Tech Stack:** Symfony 8.0 / PHP ≥ 8.5, Symfony `HttpClientInterface`, `Symfony\Component\Uid\Uuid`.

## Global Constraints

- `declare(strict_types=1)` in every new file. Controllers `final`; Services/`Service\Api` classes non-`final` (mockable).
- Response must mean "here's what's known so far", never imply the pipeline is further along than confirmed — a stage that hasn't been polled yet (or found nothing) stays `null`/idle, not guessed.
- If `pokenini-api` is unreachable when registering a new run after a successful dispatch, the trigger itself must still succeed — log and continue, don't fail the button click (see companion spec, "Error handling").
- A normal status request (no `refresh` param) must make zero GitHub API calls — only the `refresh` path polls GitHub (see companion spec, "Non-goals": no auto-polling).
- Companion spec: `../pokenini-web/docs/superpowers/specs/2026-07-15-image-pipeline-status-tracking-design.md`.
- Companion plans this depends on: `../pokenini-icon/docs/superpowers/plans/2026-07-15-workflow-correlation-id.md` (adds the `correlation_id` input Workflow A needs) and `../pokenini-api/docs/superpowers/plans/2026-07-15-image-pipeline-run-tracking.md` (the 3 endpoints this plan calls: `POST /istration/image-pipeline-runs`, `PATCH /istration/image-pipeline-runs/{correlationId}`, `GET /istration/image-pipeline-runs/latest`).

**Manual step needed after this plan is merged (not achievable in code):** the existing `GITHUB_IMAGES_WORKFLOW_TOKEN` PAT currently only grants `actions: write` on `pokenini-icon` (enough to dispatch Workflow A). This feature also needs it to **read**: `actions: read` + `pull-requests: read` on `pokenini-icon` (to list workflow runs and find the icon PR), and `pull-requests: read` on `pokenini-resources` (to find the resources PR). Broaden the same fine-grained PAT's repository access/permissions in GitHub's UI — no new secret name, no new env var for the token itself.

---

### Task 1: `GithubActionsApiService::dispatchWorkflow()` accepts a correlation id

**Files:**
- Modify: `src/Service/Api/GithubActionsApiService.php`
- Modify: `tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`

**Interfaces:**
- Produces: `GithubActionsApiService::dispatchWorkflow(string $correlationId): void` — the `$correlationId` is sent as `{"ref": "main", "inputs": {"correlation_id": "<value>"}}` in the dispatch request body, matching the `inputs.correlation_id` Workflow A now expects (see the `pokenini-icon` companion plan).

- [ ] **Step 1: Update the failing/changed tests first**

In `tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`, change both `dispatchWorkflow()` calls to `dispatchWorkflow('corr-123')`, and update the expected request body in `testDispatchWorkflow()`'s `->with(...)` from:

```php
'json' => [
    'ref' => 'main',
],
```

to:

```php
'json' => [
    'ref' => 'main',
    'inputs' => [
        'correlation_id' => 'corr-123',
    ],
],
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`
Expected: FAIL — either a "too few arguments" error (method signature not yet changed) or a request-body mismatch, depending on which change you make first. Make the test change first, confirm this failure, then implement.

- [ ] **Step 3: Update the implementation**

In `src/Service/Api/GithubActionsApiService.php`, change:

```php
    public function dispatchWorkflow(): void
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
                    ],
                ],
            );
```

to:

```php
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
```

(the rest of the method body is unchanged).

- [ ] **Step 4: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`
Expected: `OK (2 tests, ...)`

- [ ] **Step 5: Fix the call site**

`src/Service/TriggerImagesPipelineService.php` calls `dispatchWorkflow()` with no arguments — this now fails to compile/type-check. Leave it broken for now; Task 4 fixes it properly (it needs to generate the correlation id, not just satisfy the signature). Confirm this by running:

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`
Expected: FAIL — this is expected and will be fixed in Task 4. Do not patch it here.

- [ ] **Step 6: Commit**

```bash
git add src/Service/Api/GithubActionsApiService.php tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php
git commit -m "Pass a correlation id through to the update-images workflow dispatch"
```

---

### Task 2: `GithubQueryApiService` — read-only GitHub queries

**Files:**
- Create: `src/Service/Api/GithubQueryApiService.php`
- Test: `tests/src/Unit/Service/Api/GithubQueryApiServiceTest.php`

**Interfaces:**
- Produces:
  - `findWorkflowRunByDisplayTitle(string $repo, string $workflowFile, string $displayTitle): ?array{id: int, status: string, conclusion: ?string, htmlUrl: string, headSha: string}`
  - `findWorkflowRunByHeadSha(string $repo, string $workflowFile, string $headSha): ?array{id: int, status: string, conclusion: ?string, htmlUrl: string, headSha: string}`
  - `findPullRequestByBranch(string $repo, string $branch): ?array{number: int, htmlUrl: string, state: string, mergedAt: ?string, mergeCommitSha: ?string}`

  `$repo` is always an explicit parameter (e.g. `douzeensemble/pokenini-icon` or `douzeensemble/pokenini-resources`) — this class is not bound to one repo, unlike `GithubActionsApiService`, because it must query both.

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/Service/Api/GithubQueryApiServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GithubQueryApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
                $this->callback(fn (array $options): bool => 'Bearer secret-token' === $options['headers']['authorization']),
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

        $pr = $service->findPullRequestByBranch('douzeensemble/pokenini-icon', 'update-images-2');

        $this->assertSame([
            'number' => 7,
            'htmlUrl' => 'https://github.com/douzeensemble/pokenini-icon/pull/7',
            'state' => 'merged',
            'mergedAt' => '2026-07-15T10:00:00Z',
            'mergeCommitSha' => 'merge-sha-7',
        ], $pr);
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
}
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GithubQueryApiServiceTest.php`
Expected: FAIL — `Class "App\Service\Api\GithubQueryApiService" not found`.

- [ ] **Step 3: Write the implementation**

Create `src/Service/Api/GithubQueryApiService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ModifyFailedException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
        $prs = $this->request($endpointUrl)->toArray();

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
        $data = $this->request($endpointUrl)->toArray();

        return $data['workflow_runs'];
    }

    private function request(string $endpointUrl): ResponseInterface
    {
        try {
            $this->logger->info("Requesting GET {$endpointUrl}", []);

            return $this->client->request(
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
```

- [ ] **Step 4: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GithubQueryApiServiceTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add src/Service/Api/GithubQueryApiService.php tests/src/Unit/Service/Api/GithubQueryApiServiceTest.php
git commit -m "Add GithubQueryApiService for read-only workflow run / PR lookups"
```

---

### Task 3: `ImagePipelineApiService` — the pokenini-api client

**Files:**
- Create: `src/Service/Api/ImagePipelineApiService.php`
- Test: `tests/src/Unit/Service/Api/ImagePipelineApiServiceTest.php`

**Interfaces:**
- Produces:
  - `create(string $correlationId): void`
  - `updateFields(string $correlationId, array $fields): void` — `$fields` uses the same camelCase keys as `pokenini-api`'s `ImagePipelineRunPatch` (e.g. `workflowARunId`, `iconPrState`).
  - `getLatest(): ?array` (the decoded JSON body, snake_case keys per `pokenini-api`'s `ImagePipelineRunResponse`) — returns `null` on a 404 (no run yet), which is a normal outcome, not an error.
- This extends `AbstractApiService`, reusing the existing `apiUrl`/`apiCafilePath`/`apiLogin`/`apiPassword` binds already wired for `AdminActionApiService` — no new env vars needed for this class.

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/Service/Api/ImagePipelineApiServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\ImagePipelineApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ImagePipelineApiService::class)]
final class ImagePipelineApiServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('');
        $response->method('getStatusCode')->willReturn(201);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.domain/istration/image-pipeline-runs',
                $this->callback(fn (array $o): bool => ['correlationId' => 'corr-1'] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->create('corr-1');
    }

    public function testUpdateFields(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('');
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'https://api.domain/istration/image-pipeline-runs/corr-1',
                $this->callback(fn (array $o): bool => ['workflowARunId' => 42] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->updateFields('corr-1', ['workflowARunId' => 42]);
    }

    public function testGetLatestReturnsDecodedBody(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"correlation_id":"corr-1","workflow_a_run_id":null}');
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->getLatest();

        $this->assertSame(['correlation_id' => 'corr-1', 'workflow_a_run_id' => null], $result);
    }

    public function testGetLatestReturnsNullOn404(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->method('request')
            ->willReturnCallback(function () use ($response): ResponseInterface {
                $exception = $this->createMock(ClientException::class);
                $exception->method('getResponse')->willReturn($response);

                throw $exception;
            })
        ;

        $this->assertNull($this->getService($client)->getLatest());
    }

    public function testCreateFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(\Symfony\Component\HttpClient\Exception\TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->create('corr-1');
    }

    private function getService(HttpClientInterface $client): ImagePipelineApiService
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        return new ImagePipelineApiService(
            $this->createMock(LoggerInterface::class),
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $cache,
            'web',
            'douze',
        );
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/ImagePipelineApiServiceTest.php`
Expected: FAIL — `Class "App\Service\Api\ImagePipelineApiService" not found`.

- [ ] **Step 3: Write the implementation**

Create `src/Service/Api/ImagePipelineApiService.php`:

```php
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
```

- [ ] **Step 4: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/ImagePipelineApiServiceTest.php`
Expected: `OK (5 tests, ...)`

- [ ] **Step 5: Commit**

```bash
git add src/Service/Api/ImagePipelineApiService.php tests/src/Unit/Service/Api/ImagePipelineApiServiceTest.php
git commit -m "Add ImagePipelineApiService client for pokenini-api's image-pipeline-runs endpoints"
```

---

### Task 4: `ImagePipelineStatusService` and wiring `TriggerImagesPipelineService`

**Files:**
- Create: `src/Service/ImagePipelineStatusService.php`
- Test: `tests/src/Unit/Service/ImagePipelineStatusServiceTest.php`
- Modify: `src/Service/TriggerImagesPipelineService.php`
- Modify: `tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`
- Modify: `config/services.yaml` (2 new binds), `.env.dev`, `.env.test`, `.env.ci`, `.env.int`, `.env.prod` (2 new non-secret vars)

**Interfaces:**
- Produces: `ImagePipelineStatusService::getStatus(bool $refresh): ?array` (the raw snake_case array from `pokenini-api`, or `null` if no run has ever been triggered) — consumed by the controller in Task 5.
- Modifies: `TriggerImagesPipelineService::triggerUpdateImages(): void` now generates a correlation id, passes it to `dispatchWorkflow()`, and registers the run with `pokenini-api` (best-effort — a registration failure is logged, not thrown).

- [ ] **Step 1: Add the 2 new non-secret env vars**

Add to `.env.dev`, `.env.test`, `.env.ci`, `.env.int`, `.env.prod` (same line, real values in every file — these aren't secrets):

```
GITHUB_IMAGES_WORKFLOW_B_FILE=publish-images-to-resources.yml
GITHUB_RESOURCES_REPO=douzeensemble/pokenini-resources
```

- [ ] **Step 2: Bind them in services.yaml**

Add to `config/services.yaml`'s `_defaults: bind:` block:

```yaml
            string $githubImagesWorkflowBFile: '%env(GITHUB_IMAGES_WORKFLOW_B_FILE)%'
            string $githubResourcesRepo: '%env(GITHUB_RESOURCES_REPO)%'
```

- [ ] **Step 3: Verify the container still compiles**

Run: `docker compose exec php php bin/console cache:clear --env=test`
Expected: no errors.

- [ ] **Step 4: Write the failing test for `ImagePipelineStatusService`**

Create `tests/src/Unit/Service/ImagePipelineStatusServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GithubQueryApiService;
use App\Service\Api\ImagePipelineApiService;
use App\Service\ImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ImagePipelineStatusService::class)]
final class ImagePipelineStatusServiceTest extends TestCase
{
    public function testReturnsNullWhenNoRunExists(): void
    {
        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $this->createMock(GithubQueryApiService::class));

        $this->assertNull($service->getStatus(refresh: false));
    }

    public function testWithoutRefreshReturnsLatestUnchanged(): void
    {
        $latest = ['correlation_id' => 'corr-1', 'workflow_a_conclusion' => null];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: false));
    }

    public function testRefreshPollsWorkflowAWhenNotYetConcluded(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];
        $refreshed = array_merge($latest, ['workflow_a_conclusion' => 'success']);

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $imagePipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'workflowARunId' => 2,
                'workflowAStatus' => 'completed',
                'workflowAConclusion' => 'success',
                'workflowAUrl' => 'https://github.com/x/y/actions/runs/2',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findWorkflowRunByDisplayTitle')
            ->with('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-1)')
            ->willReturn([
                'id' => 2,
                'status' => 'completed',
                'conclusion' => 'success',
                'htmlUrl' => 'https://github.com/x/y/actions/runs/2',
                'headSha' => 'sha2',
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    public function testRefreshDoesNothingWhenPollFindsNothingNew(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findWorkflowRunByDisplayTitle')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    private function getService(
        ImagePipelineApiService $imagePipelineApiService,
        GithubQueryApiService $githubQueryApiService,
    ): ImagePipelineStatusService {
        return new ImagePipelineStatusService(
            $imagePipelineApiService,
            $githubQueryApiService,
            'douzeensemble/pokenini-icon',
            'update-images.yml',
            'publish-images-to-resources.yml',
            'douzeensemble/pokenini-resources',
        );
    }
}
```

Note: this covers the "Workflow A not yet concluded" branch explicitly (the most common poll case). The remaining 3 branches (icon PR lookup once Workflow A succeeds, Workflow B lookup once the icon PR is merged, resources PR lookup once Workflow B succeeds) follow the exact same shape — Step 6 below implements all 4; if Task 6's mutation-testing pass surfaces an uncovered branch among the other 3, add a test for it there rather than here.

- [ ] **Step 5: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/ImagePipelineStatusServiceTest.php`
Expected: FAIL — `Class "App\Service\ImagePipelineStatusService" not found`.

- [ ] **Step 6: Write `ImagePipelineStatusService`**

Create `src/Service/ImagePipelineStatusService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\GithubQueryApiService;
use App\Service\Api\ImagePipelineApiService;

class ImagePipelineStatusService
{
    public function __construct(
        private readonly ImagePipelineApiService $imagePipelineApiService,
        private readonly GithubQueryApiService $githubQueryApiService,
        private readonly string $githubImagesRepo,
        private readonly string $githubImagesWorkflowFile,
        private readonly string $githubImagesWorkflowBFile,
        private readonly string $githubResourcesRepo,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function getStatus(bool $refresh): ?array
    {
        $latest = $this->imagePipelineApiService->getLatest();

        if (null === $latest || !$refresh) {
            return $latest;
        }

        $updates = $this->pollNextStage($latest);

        if ([] === $updates) {
            return $latest;
        }

        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $this->imagePipelineApiService->updateFields($correlationId, $updates);

        return $this->imagePipelineApiService->getLatest();
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollNextStage(array $latest): array
    {
        if (null === $latest['workflow_a_conclusion']) {
            /** @var string $correlationId */
            $correlationId = $latest['correlation_id'];

            $run = $this->githubQueryApiService->findWorkflowRunByDisplayTitle(
                $this->githubImagesRepo,
                $this->githubImagesWorkflowFile,
                "Update images ({$correlationId})",
            );

            if (null === $run) {
                return [];
            }

            return [
                'workflowARunId' => $run['id'],
                'workflowAStatus' => $run['status'],
                'workflowAConclusion' => $run['conclusion'] ?? '',
                'workflowAUrl' => $run['htmlUrl'],
            ];
        }

        if ('success' === $latest['workflow_a_conclusion'] && 'merged' !== $latest['icon_pr_state']) {
            $pr = $this->githubQueryApiService->findPullRequestByBranch(
                $this->githubImagesRepo,
                "update-images-{$latest['workflow_a_run_id']}",
            );

            if (null === $pr) {
                return [];
            }

            $updates = [
                'iconPrNumber' => $pr['number'],
                'iconPrUrl' => $pr['htmlUrl'],
                'iconPrState' => $pr['state'],
            ];

            if (null !== $pr['mergeCommitSha']) {
                $updates['iconPrMergeCommitSha'] = $pr['mergeCommitSha'];
            }

            return $updates;
        }

        if (null !== $latest['icon_pr_merge_commit_sha'] && null === $latest['workflow_b_conclusion']) {
            /** @var string $mergeCommitSha */
            $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

            $run = $this->githubQueryApiService->findWorkflowRunByHeadSha(
                $this->githubImagesRepo,
                $this->githubImagesWorkflowBFile,
                $mergeCommitSha,
            );

            if (null === $run) {
                return [];
            }

            return [
                'workflowBRunId' => $run['id'],
                'workflowBStatus' => $run['status'],
                'workflowBConclusion' => $run['conclusion'] ?? '',
                'workflowBUrl' => $run['htmlUrl'],
            ];
        }

        if ('success' === $latest['workflow_b_conclusion']) {
            /** @var string $mergeCommitSha */
            $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

            $pr = $this->githubQueryApiService->findPullRequestByBranch(
                $this->githubResourcesRepo,
                "sync-images-{$mergeCommitSha}",
            );

            if (null === $pr) {
                return [];
            }

            return [
                'resourcesPrNumber' => $pr['number'],
                'resourcesPrUrl' => $pr['htmlUrl'],
                'resourcesPrState' => $pr['state'],
            ];
        }

        return [];
    }
}
```

- [ ] **Step 7: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/ImagePipelineStatusServiceTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 8: Fix `TriggerImagesPipelineService`'s broken call site**

Update `tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php` to:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GithubActionsApiService;
use App\Service\Api\ImagePipelineApiService;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(TriggerImagesPipelineService::class)]
final class TriggerImagesPipelineServiceTest extends TestCase
{
    public function testTriggerUpdateImagesDispatchesAndRegistersRun(): void
    {
        $githubActionsApiService = $this->createMock(GithubActionsApiService::class);
        $githubActionsApiService
            ->expects($this->once())
            ->method('dispatchWorkflow')
            ->with($this->matchesRegularExpression('/^[0-9a-f-]{36}$/'))
        ;

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->expects($this->once())
            ->method('create')
            ->with($this->matchesRegularExpression('/^[0-9a-f-]{36}$/'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $service = new TriggerImagesPipelineService($githubActionsApiService, $imagePipelineApiService, $logger);

        $service->triggerUpdateImages();
    }

    public function testRegistrationFailureIsLoggedButDoesNotThrow(): void
    {
        $githubActionsApiService = $this->createMock(GithubActionsApiService::class);

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('create')
            ->willThrowException(new \RuntimeException('pokenini-api unreachable'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('pokenini-api unreachable')
        ;

        $service = new TriggerImagesPipelineService($githubActionsApiService, $imagePipelineApiService, $logger);

        $service->triggerUpdateImages();
    }
}
```

Update `src/Service/TriggerImagesPipelineService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\GithubActionsApiService;
use App\Service\Api\ImagePipelineApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class TriggerImagesPipelineService
{
    public function __construct(
        private readonly GithubActionsApiService $githubActionsApiService,
        private readonly ImagePipelineApiService $imagePipelineApiService,
        private readonly LoggerInterface $logger,
    ) {}

    public function triggerUpdateImages(): void
    {
        $correlationId = Uuid::v4()->toRfc4122();

        $this->githubActionsApiService->dispatchWorkflow($correlationId);

        try {
            $this->imagePipelineApiService->create($correlationId);
        } catch (\RuntimeException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
```

- [ ] **Step 9: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`
Expected: `OK (2 tests, ...)`

- [ ] **Step 10: Commit**

```bash
git add src/Service/ImagePipelineStatusService.php tests/src/Unit/Service/ImagePipelineStatusServiceTest.php src/Service/TriggerImagesPipelineService.php tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php config/services.yaml .env.dev .env.test .env.ci .env.int .env.prod
git commit -m "Add ImagePipelineStatusService and generate/register a correlation id on trigger"
```

---

### Task 5: Status controller

**Files:**
- Create: `src/DTO/ImagePipelineStageStatus.php`
- Create: `src/DTO/ImagePipelineStatus.php`
- Create: `src/Controller/Admin/ImagePipelineStatusController.php`
- Test: `tests/src/Unit/Controller/Admin/ImagePipelineStatusControllerTest.php`

**Interfaces:**
- Produces: `GET /istration/action/trigger/update_images/status`, query param `refresh` (any presence triggers the poll path — matches Symfony's usual `$request->query->has('refresh')` check), returning JSON:
  ```json
  {
    "correlationId": "...",
    "workflowA": {"state": "idle|running|done|failed", "url": "..."},
    "iconPr": {"state": "idle|open|merged", "url": "..."},
    "workflowB": {"state": "idle|running|done|failed", "url": "..."},
    "resourcesPr": {"state": "idle|open|merged", "url": "..."}
  }
  ```
  or `{}` (empty object, HTTP 200) if no run has ever been triggered. This exact shape is what the `pokenini-web` companion plan's UI partial renders.

- [ ] **Step 1: Write the DTOs**

Create `src/DTO/ImagePipelineStageStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO;

final class ImagePipelineStageStatus
{
    public function __construct(
        public readonly string $state,
        public readonly ?string $url = null,
    ) {}
}
```

Create `src/DTO/ImagePipelineStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTO;

final class ImagePipelineStatus
{
    public function __construct(
        public readonly string $correlationId,
        public readonly ImagePipelineStageStatus $workflowA,
        public readonly ImagePipelineStageStatus $iconPr,
        public readonly ImagePipelineStageStatus $workflowB,
        public readonly ImagePipelineStageStatus $resourcesPr,
    ) {}
}
```

- [ ] **Step 2: Write the failing test**

Create `tests/src/Unit/Controller/Admin/ImagePipelineStatusControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ImagePipelineStatusController;
use App\Service\ImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ImagePipelineStatusController::class)]
final class ImagePipelineStatusControllerTest extends TestCase
{
    public function testNoRunReturnsEmptyObject(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->method('getStatus')->with(false)->willReturn(null);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
    }

    public function testIdleStagesWhenNothingKnownYet(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->method('getStatus')->with(false)->willReturn([
            'correlation_id' => 'corr-1',
            'workflow_a_status' => null,
            'workflow_a_conclusion' => null,
            'workflow_a_url' => null,
            'icon_pr_state' => null,
            'icon_pr_url' => null,
            'workflow_b_status' => null,
            'workflow_b_conclusion' => null,
            'workflow_b_url' => null,
            'resources_pr_state' => null,
            'resources_pr_url' => null,
        ]);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('corr-1', $data['correlationId']);
        $this->assertSame('idle', $data['workflowA']['state']);
        $this->assertSame('idle', $data['iconPr']['state']);
    }

    public function testRefreshQueryParamIsForwarded(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(true)->willReturn(null);

        $controller = new ImagePipelineStatusController($service);

        $controller->get(new Request(query: ['refresh' => '1']));
    }

    public function testRunningAndFailedStates(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->method('getStatus')->willReturn([
            'correlation_id' => 'corr-1',
            'workflow_a_status' => 'in_progress',
            'workflow_a_conclusion' => null,
            'workflow_a_url' => 'https://github.com/x/y/actions/runs/1',
            'icon_pr_state' => null,
            'icon_pr_url' => null,
            'workflow_b_status' => 'completed',
            'workflow_b_conclusion' => 'failure',
            'workflow_b_url' => 'https://github.com/x/y/actions/runs/2',
            'resources_pr_state' => null,
            'resources_pr_url' => null,
        ]);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('running', $data['workflowA']['state']);
        $this->assertSame('failed', $data['workflowB']['state']);
    }
}
```

- [ ] **Step 3: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/ImagePipelineStatusControllerTest.php`
Expected: FAIL — `Class "App\Controller\Admin\ImagePipelineStatusController" not found`.

- [ ] **Step 4: Write the controller**

Create `src/Controller/Admin/ImagePipelineStatusController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\ImagePipelineStageStatus;
use App\DTO\ImagePipelineStatus;
use App\Service\ImagePipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action/trigger/update_images')]
final class ImagePipelineStatusController extends AbstractController
{
    public function __construct(
        private readonly ImagePipelineStatusService $service,
    ) {}

    #[Route('/status', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        $latest = $this->service->getStatus($request->query->has('refresh'));

        if (null === $latest) {
            return new JsonResponse(new \stdClass());
        }

        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $status = new ImagePipelineStatus(
            correlationId: $correlationId,
            workflowA: $this->runStage($latest['workflow_a_status'] ?? null, $latest['workflow_a_conclusion'] ?? null, $latest['workflow_a_url'] ?? null),
            iconPr: $this->prStage($latest['icon_pr_state'] ?? null, $latest['icon_pr_url'] ?? null),
            workflowB: $this->runStage($latest['workflow_b_status'] ?? null, $latest['workflow_b_conclusion'] ?? null, $latest['workflow_b_url'] ?? null),
            resourcesPr: $this->prStage($latest['resources_pr_state'] ?? null, $latest['resources_pr_url'] ?? null),
        );

        return new JsonResponse($status);
    }

    private function runStage(?string $status, ?string $conclusion, ?string $url): ImagePipelineStageStatus
    {
        $state = match (true) {
            null === $status => 'idle',
            'completed' === $status && 'success' === $conclusion => 'done',
            'completed' === $status => 'failed',
            default => 'running',
        };

        return new ImagePipelineStageStatus($state, $url);
    }

    private function prStage(?string $state, ?string $url): ImagePipelineStageStatus
    {
        return new ImagePipelineStageStatus($state ?? 'idle', $url);
    }
}
```

- [ ] **Step 5: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/ImagePipelineStatusControllerTest.php`
Expected: `OK (4 tests, ...)`

- [ ] **Step 6: Verify routing**

Run: `docker compose exec php php bin/console debug:router | grep "update_images/status"`
Expected: one line, `GET /istration/action/trigger/update_images/status`.

- [ ] **Step 7: Commit**

```bash
git add src/DTO/ImagePipelineStageStatus.php src/DTO/ImagePipelineStatus.php src/Controller/Admin/ImagePipelineStatusController.php tests/src/Unit/Controller/Admin/ImagePipelineStatusControllerTest.php
git commit -m "Add GET status endpoint for the update_images pipeline"
```

---

### Task 6: Full quality and measures gate

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `docker compose exec php php vendor/bin/phpunit`
Expected: all tests pass.

- [ ] **Step 2: Run quality checks**

Run: `make quality`
Expected: clean. Fix formatting with `make phpcsfixer-fix` if needed. If Deptrac flags `ImagePipelineStatusController` → `App\DTO` or `Service` → `Service\Api`, check `deptrac.yaml`'s existing `AppController`/`AppService` rules first — these dependencies already exist for the sibling `AdminAction*` classes and should already be allowed.

- [ ] **Step 3: Run coverage and mutation testing**

Run: `make measures`
Expected: 100% coverage, 100% MSI. `ImagePipelineStatusService::pollNextStage()`'s 4 branches and `ImagePipelineStatusController::runStage()`'s `match` are the most likely places for a surviving mutant — add the missing branch's test case to `ImagePipelineStatusServiceTest`/`ImagePipelineStatusControllerTest` respectively if one turns up (see the note at the end of Task 4 Step 4).

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "Fix quality/coverage findings for the image pipeline status endpoint"
```

(Skip if nothing needed fixing.)
