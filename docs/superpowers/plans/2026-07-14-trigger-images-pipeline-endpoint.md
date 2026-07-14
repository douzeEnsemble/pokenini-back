# Trigger Images Pipeline Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `POST /istration/action/trigger/update_images` endpoint that dispatches the `update-images.yml` GitHub Actions workflow in the `pokenini-icon` repository, so `pokenini-web`'s admin page can trigger the image pipeline from prod.

**Architecture:** A new, standalone controller (`AdminActionTriggerController`) → a thin `Service` (`TriggerImagesPipelineService`) → a new `Service\Api` class (`GithubActionsApiService`) that calls the GitHub REST API with a Bearer-token PAT. This does **not** reuse `AbstractAdminActionController`/`AdminActionApiService` — those are hard-wired to pokenini-api's host/auth scheme (Basic auth) and to an unconditional cache-invalidation call that would misreport this action's success as failure (see companion spec).

**Tech Stack:** Symfony 8.0 / PHP ≥ 8.5, Symfony `HttpClientInterface`, PHPUnit.

## Global Constraints

- `declare(strict_types=1)` in every new PHP file (workspace-wide convention).
- New `Controller`/`DTO`/`Exception` classes are `final`; `Service` classes stay non-`final` so PHPUnit can mock them (workspace-wide convention — matches why `GithubActionsApiService` and `TriggerImagesPipelineService` below are not `final`).
- 100% line coverage and 100% Mutation Score Index are required (`make measures`); `make quality` (PHPStan level 9, Psalm strict, PHP CS Fixer, PHPMD, Deptrac) must be green before this is considered done.
- Controllers must not call `Service\Api\*` directly — always through a `Service` class (documented architecture rule; not Deptrac-enforced for this specific pair, but still required — see companion spec's pokenini-back section).
- The response must mean "GitHub accepted the dispatch", not "images are updated" — do not change log/response wording to imply pipeline completion.
- Companion spec: `../pokenini-web/docs/superpowers/specs/2026-07-14-update-images-pipeline-trigger-design.md`.
- Companion plan (must exist and be merged first, since this service calls its exact route): `../pokenini-icon/docs/superpowers/plans/2026-07-14-update-images-workflows.md` — the endpoint this task dispatches is `POST /repos/douzeensemble/pokenini-icon/actions/workflows/update-images.yml/dispatches`.

---

### Task 1: `GithubActionsApiService` — dispatch the workflow over HTTP

**Files:**
- Create: `src/Service/Api/GithubActionsApiService.php`
- Test: `tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`
- Modify: `config/services.yaml` (add 3 new binds)
- Modify: `.env.dev`, `.env.test`, `.env.ci`, `.env.int`, `.env.prod` (add 3 new env vars each)

**Interfaces:**
- Produces: `GithubActionsApiService::dispatchWorkflow(): void`, throwing `App\Exception\ModifyFailedException` on any transport/HTTP failure. Consumed by `TriggerImagesPipelineService` in Task 2.

- [ ] **Step 1: Add the three new env vars to every environment file**

Add these three lines to `.env.dev`, `.env.test`, `.env.ci`, and `.env.int` (right after the existing `API_URL=http://moco.api` line in each file, matching where the other pokenini-api-adjacent config sits):

```
GITHUB_IMAGES_WORKFLOW_TOKEN=test-token
GITHUB_IMAGES_REPO=douzeensemble/pokenini-icon
GITHUB_IMAGES_WORKFLOW_FILE=update-images.yml
```

Add the same three lines to `.env.prod`, but using the `!ChangeMe!` placeholder convention already used there for `APP_SECRET` (the real PAT must be supplied via an untracked `.env.prod.local` or the hosting platform's secret injection — never commit a real token):

```
GITHUB_IMAGES_WORKFLOW_TOKEN=!ChangeMe!
GITHUB_IMAGES_REPO=douzeensemble/pokenini-icon
GITHUB_IMAGES_WORKFLOW_FILE=update-images.yml
```

- [ ] **Step 2: Bind the new env vars in the service container**

In `config/services.yaml`, add these three lines inside the existing `_defaults: bind:` block (alongside `string $apiUrl: '%env(API_URL)%'`):

```yaml
            string $githubImagesWorkflowToken: '%env(GITHUB_IMAGES_WORKFLOW_TOKEN)%'
            string $githubImagesRepo: '%env(GITHUB_IMAGES_REPO)%'
            string $githubImagesWorkflowFile: '%env(GITHUB_IMAGES_WORKFLOW_FILE)%'
```

- [ ] **Step 3: Verify the container still compiles**

Run: `docker compose exec php php bin/console cache:clear --env=test`
Expected: no errors (in particular, no "environment variable not found" or "autowiring" errors).

- [ ] **Step 4: Write the failing test**

Create `tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\GithubActionsApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GithubActionsApiService::class)]
final class GithubActionsApiServiceTest extends TestCase
{
    public function testDispatchWorkflow(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(204)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.github.com/repos/douzeensemble/pokenini-icon/actions/workflows/update-images.yml/dispatches',
                [
                    'headers' => [
                        'accept' => 'application/vnd.github+json',
                        'authorization' => 'Bearer secret-token',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                    'json' => [
                        'ref' => 'main',
                    ],
                ],
            )
            ->willReturn($response)
        ;

        $service = new GithubActionsApiService(
            $logger,
            $client,
            'secret-token',
            'douzeensemble/pokenini-icon',
            'update-images.yml',
        );

        $service->dispatchWorkflow();
    }

    public function testDispatchWorkflowFails(): void
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

        $service = new GithubActionsApiService(
            $logger,
            $client,
            'secret-token',
            'douzeensemble/pokenini-icon',
            'update-images.yml',
        );

        $this->expectException(ModifyFailedException::class);

        $service->dispatchWorkflow();
    }
}
```

- [ ] **Step 5: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`
Expected: FAIL — `Class "App\Service\Api\GithubActionsApiService" not found`.

- [ ] **Step 6: Write the implementation**

Create `src/Service/Api/GithubActionsApiService.php`:

```php
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

            $this->logger->info("Response status code: {$response->getStatusCode()}", []);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }
}
```

- [ ] **Step 7: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php`
Expected: `OK (2 tests, ...)`

- [ ] **Step 8: Commit**

```bash
git add src/Service/Api/GithubActionsApiService.php tests/src/Unit/Service/Api/GithubActionsApiServiceTest.php config/services.yaml .env.dev .env.test .env.ci .env.int .env.prod
git commit -m "Add GithubActionsApiService to dispatch the update-images workflow"
```

---

### Task 2: `TriggerImagesPipelineService` — the business-layer wrapper

**Files:**
- Create: `src/Service/TriggerImagesPipelineService.php`
- Test: `tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`

**Interfaces:**
- Consumes: `GithubActionsApiService::dispatchWorkflow(): void` (Task 1).
- Produces: `TriggerImagesPipelineService::triggerUpdateImages(): void`. Consumed by `AdminActionTriggerController` in Task 3.

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GithubActionsApiService;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TriggerImagesPipelineService::class)]
final class TriggerImagesPipelineServiceTest extends TestCase
{
    public function testTriggerUpdateImages(): void
    {
        $githubActionsApiService = $this->createMock(GithubActionsApiService::class);
        $githubActionsApiService
            ->expects($this->once())
            ->method('dispatchWorkflow')
        ;

        $service = new TriggerImagesPipelineService($githubActionsApiService);

        $service->triggerUpdateImages();
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`
Expected: FAIL — `Class "App\Service\TriggerImagesPipelineService" not found`.

- [ ] **Step 3: Write the implementation**

Create `src/Service/TriggerImagesPipelineService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\GithubActionsApiService;

class TriggerImagesPipelineService
{
    public function __construct(
        private readonly GithubActionsApiService $githubActionsApiService,
    ) {}

    public function triggerUpdateImages(): void
    {
        $this->githubActionsApiService->dispatchWorkflow();
    }
}
```

- [ ] **Step 4: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php`
Expected: `OK (1 test, ...)`

- [ ] **Step 5: Commit**

```bash
git add src/Service/TriggerImagesPipelineService.php tests/src/Unit/Service/TriggerImagesPipelineServiceTest.php
git commit -m "Add TriggerImagesPipelineService wrapping GithubActionsApiService"
```

---

### Task 3: `AdminActionTriggerController` — the HTTP endpoint

**Files:**
- Create: `src/Controller/Admin/AdminActionTriggerController.php`
- Test: `tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php`

**Interfaces:**
- Consumes: `TriggerImagesPipelineService::triggerUpdateImages(): void` (Task 2), `App\DTO\AdminAction` (existing — constructor `(string $action, string $item, string $state, string $content = '', string $error = '')`).
- Produces: route `POST /istration/action/trigger/{name}` (condition `name in ['update_images']`), returning a `JsonResponse` wrapping `AdminAction('trigger', $name, 'ok'|'ko', '', $error)` with status `202` on success / `500` on failure — this exact route and response shape is what the `pokenini-web` plan's `AdminActionController::trigger()` calls and parses.

- [ ] **Step 1: Write the failing test**

Create `tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AdminActionTriggerController;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionTriggerController::class)]
final class AdminActionTriggerControllerTest extends TestCase
{
    public function testAction(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Admin action succeeded: trigger update_images')
        ;

        $controller = new AdminActionTriggerController(
            $triggerImagesPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testFailTriggerLogs(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
            ->willThrowException(new \RuntimeException('Aouch'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'update_images',
                    'action' => 'trigger',
                ])
            )
        ;

        $controller = new AdminActionTriggerController(
            $triggerImagesPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(500, $response->getStatusCode());
    }
}
```

- [ ] **Step 2: Run the test and confirm it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php`
Expected: FAIL — `Class "App\Controller\Admin\AdminActionTriggerController" not found`.

- [ ] **Step 3: Write the implementation**

Create `src/Controller/Admin/AdminActionTriggerController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminAction;
use App\Service\TriggerImagesPipelineService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action/trigger')]
final class AdminActionTriggerController extends AbstractController
{
    public function __construct(
        private readonly TriggerImagesPipelineService $triggerImagesPipelineService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(
        '/{name}',
        methods: ['POST'],
        condition: "params['name'] in ['update_images']"
    )]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function process(string $name): JsonResponse
    {
        $state = 'ok';
        $error = '';

        try {
            $this->triggerImagesPipelineService->triggerUpdateImages();
            $this->logger->info("Admin action succeeded: trigger {$name}");
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $state = 'ko';
            $error = $e->getMessage();

            $this->logger->critical(
                $e->getMessage(),
                [
                    'name' => $name,
                    'action' => 'trigger',
                ]
            );
        }

        $adminAction = new AdminAction('trigger', $name, $state, '', $error);

        $statusCode = 'ko' === $state
            ? Response::HTTP_INTERNAL_SERVER_ERROR
            : Response::HTTP_ACCEPTED;

        return new JsonResponse($adminAction, $statusCode);
    }
}
```

- [ ] **Step 4: Run the test and confirm it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php`
Expected: `OK (2 tests, ...)`

- [ ] **Step 5: Confirm the route is registered as expected**

Run: `docker compose exec php php bin/console debug:router | grep trigger`
Expected: a line showing `POST` and path `/istration/action/trigger/{name}`. Note the exact route *name* Symfony assigns (e.g. `app_admin_action_trigger_action_process` or similar auto-generated name) — the `pokenini-web` plan does not need this name (it only calls the URL path via `AdminActionService`'s HTTP client, not Symfony's URL generator), but write it down here for your own reference if useful.

- [ ] **Step 6: Commit**

```bash
git add src/Controller/Admin/AdminActionTriggerController.php tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php
git commit -m "Add AdminActionTriggerController for the update_images admin action"
```

---

### Task 4: Full quality and measures gate

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `docker compose exec php php vendor/bin/phpunit`
Expected: all tests pass, including the 3 new test files from Tasks 1–3.

- [ ] **Step 2: Run quality checks**

Run: `make quality`
Expected: PHPStan, Psalm, PHP CS Fixer, PHPMD, and Deptrac all pass with no new violations. If PHP CS Fixer reports formatting differences, run `make phpcsfixer-fix` and re-run `make quality`.

- [ ] **Step 3: Run coverage and mutation testing**

Run: `make measures`
Expected: coverage 100%, mutation score (Infection) 100% MSI. If mutation testing surfaces an uncovered mutant (e.g. the `ref: 'main'` literal, or the `202`/`500` status code branch), add an assertion to the relevant test in Tasks 1–3 that pins down that exact value, then re-run.

- [ ] **Step 4: Commit any fixes from Steps 2–3**

```bash
git add -A
git commit -m "Fix quality/coverage findings for the update_images trigger endpoint"
```

(Skip this step if Steps 2–3 already passed cleanly with nothing to fix.)
