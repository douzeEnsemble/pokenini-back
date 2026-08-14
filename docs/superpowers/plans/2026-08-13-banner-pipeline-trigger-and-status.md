# Banner Pipeline Trigger + Status (pokenini-back) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dispatch the `update-banners.yml` GitHub Actions workflow from an admin action (`update_banners`, alongside the existing `update_images`) and expose its status by polling GitHub, mirroring the `update_images` pipeline's back-end plumbing as a fully separate, duplicated vertical slice.

**Architecture:** New `BannerPipelineApiService` (talks to pokenini-api's `/istration/banner-pipeline-runs`, from the `pokenini-api` plan), `TriggerBannersPipelineService`, `BannerPipelineStatusService`/`BannerPipelineStatusController`, `BannerPipelineStatus`/`BannerPipelineStageStatus` DTOs. `GithubQueryApiService` (already generic — takes repo/workflow-file as method arguments) is reused as-is. `GithubActionsApiService` (workflow file is baked into its constructor) needs a second, banner-flavored instance — added via a named Symfony autowiring alias in `services.yaml`, with zero changes to the existing default instance or its consumer. `AdminActionTriggerController` (the one existing file this plan modifies) gains a second allowed `name` and branches to the right trigger service.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, PHPUnit (`#[CoversClass]`, `#[Test]`, `createMock`/`createStub`).

## Global Constraints

- `declare(strict_types=1)` in every file.
- Controllers, DTOs go through the existing Deptrac layers unchanged: `Controller` → `Service` → `Service\Api`.
- 100% coverage and 100% MSI (`make coverage`, `make infection`) must stay green.
- Every test class: `/** @internal */` + `#[CoversClass(TargetClass::class)]`.
- No changes to `TriggerImagesPipelineService`, `ImagePipelineApiService`, `ImagePipelineStatusService`, `ImagePipelineStatusController`, `GithubActionsApiService`'s class body, or any of their existing tests — the images pipeline is untouched code.
- `AdminActionTriggerController` is the one existing file this plan modifies (constructor gains a dependency, route condition gains a value) — its existing test must be updated in the same task, not left broken.
- The resources-repo PR branch name stays `sync-images-<merge sha>` for banners too (not `sync-banners-...`) — `publish-images-to-resources.yml` is shared, unmodified, and always names its branch that way regardless of what changed under `images/**`.

---

### Task 1: Config — env vars + named `GithubActionsApiService` instance for banners

**Files:**
- Modify: `.env.dev`
- Modify: `.env.test`
- Modify: `config/services.yaml`

**Interfaces:**
- Produces: a named service `GithubActionsApiService $bannerGithubActionsApiService`, autowired into any constructor parameter typed `GithubActionsApiService` and named exactly `$bannerGithubActionsApiService`. (The `GITHUB_BANNERS_WORKFLOW_FILE`/`GITHUB_BANNERS_WORKFLOW_B_FILE` env vars are added here too, but the corresponding `_defaults.bind` scalars are added later, in Task 4, at the point their first consumer exists — see that task's note. Symfony's `ResolveBindingsPass` hard-fails the container on a bind entry with zero consumers, so adding the bind before any class asks for `$githubBannersWorkflowFile`/`$githubBannersWorkflowBFile` by constructor-parameter name would break `cache:clear`, `bin/console`, and every integration test until Task 4 landed.)

- [ ] **Step 1: Add the new env vars**

In `.env.dev` and `.env.test`, next to the existing `GITHUB_IMAGES_*`/`GITHUB_RESOURCES_REPO` block (`.env.dev:17-21`):

```
GITHUB_BANNERS_WORKFLOW_FILE=update-banners.yml
GITHUB_BANNERS_WORKFLOW_B_FILE=publish-images-to-resources.yml
```

(Same repo — `pokenini-icon` — and same token as images, so no new `GITHUB_BANNERS_REPO` or `GITHUB_BANNERS_WORKFLOW_TOKEN` is needed; both pipelines' workflows live in the same repo. These two vars stay unread by any bind until Task 4 — that's fine, Symfony doesn't validate that a declared env var has a consumer, only that a declared *bind* does.)

- [ ] **Step 2: Declare the named `GithubActionsApiService` instance**

In `config/services.yaml`, after the `App\:` resource block (`config/services.yaml:26-31`), add a named autowiring alias for the banner-flavored `GithubActionsApiService` — `GithubActionsApiService`'s constructor takes its workflow file as a plain constructor argument, so a second differently-configured instance needs its own service id; only `$githubImagesWorkflowFile` is overridden here, the rest ($logger, $client, $githubImagesWorkflowToken, $githubImagesRepo) still resolve through the existing global binds since this service inherits `_defaults` (`autowire: true`):

```yaml
    App\Service\Api\GithubActionsApiService $bannerGithubActionsApiService:
        class: App\Service\Api\GithubActionsApiService
        arguments:
            $githubImagesWorkflowFile: '%env(GITHUB_BANNERS_WORKFLOW_FILE)%'
```

- [ ] **Step 3: Verify the container compiles**

Run: `docker compose exec php php bin/console debug:autowiring GithubActionsApiService`
Expected: lists both `App\Service\Api\GithubActionsApiService` (default) and `App\Service\Api\GithubActionsApiService $bannerGithubActionsApiService` (the new alias).

Run: `docker compose exec php php bin/console cache:clear`
Expected: no container compilation errors.

- [ ] **Step 4: Commit**

```bash
git add .env.dev .env.test config/services.yaml
git commit -m "feat: add GITHUB_BANNERS_* config and a named GithubActionsApiService instance for banners"
```

---

### Task 2: `BannerPipelineApiService`

**Files:**
- Create: `src/Service/Api/BannerPipelineApiService.php`
- Test: `tests/src/Unit/Service/Api/BannerPipelineApiServiceTest.php`

**Interfaces:**
- Consumes: `AbstractApiService` (existing base class — `requestContent(string $method, string $endpointUrl, array $options = []): string`).
- Produces: `App\Service\Api\BannerPipelineApiService` — `create(string $correlationId): void`, `updateFields(string $correlationId, array $fields): void` (`array<string, int|string>`), `getLatest(): ?array` (`?array<string, mixed>`). Consumed by `TriggerBannersPipelineService` (Task 3) and `BannerPipelineStatusService` (Task 4).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\BannerPipelineApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(BannerPipelineApiService::class)]
final class BannerPipelineApiServiceTest extends TestCase
{
    #[Test]
    public function create(): void
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
                'https://api.domain/istration/banner-pipeline-runs',
                $this->callback(fn (array $o): bool => ['correlationId' => 'corr-1'] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->create('corr-1');
    }

    #[Test]
    public function updateFields(): void
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
                'https://api.domain/istration/banner-pipeline-runs/corr-1',
                $this->callback(fn (array $o): bool => ['workflowARunId' => 42] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->updateFields('corr-1', ['workflowARunId' => 42]);
    }

    #[Test]
    public function getLatestReturnsDecodedBody(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"correlation_id":"corr-1","workflow_a_run_id":null}');
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->getLatest();

        $this->assertSame(['correlation_id' => 'corr-1', 'workflow_a_run_id' => null], $result);
    }

    #[Test]
    public function getLatestReturnsNullOn404(): void
    {
        $errorResponse = $this->createStub(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(404);

        $exception = $this->createStub(ClientExceptionInterface::class);
        $exception->method('getResponse')->willReturn($errorResponse);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($exception)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $this->assertNull($this->getService($client)->getLatest());
    }

    #[Test]
    public function createFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->create('corr-1');
    }

    #[Test]
    public function updateFieldsFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->updateFields('corr-1', ['workflowARunId' => 42]);
    }

    #[Test]
    public function getLatestFailsOnNonNotFoundHttpError(): void
    {
        $errorResponse = $this->createStub(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(500);

        $exception = $this->createStub(ClientExceptionInterface::class);
        $exception->method('getResponse')->willReturn($errorResponse);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($exception)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->getLatest();
    }

    #[Test]
    public function getLatestFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->getLatest();
    }

    private function getService(HttpClientInterface $client): BannerPipelineApiService
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        return new BannerPipelineApiService(
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

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/BannerPipelineApiServiceTest.php`
Expected: FAIL — `Class "App\Service\Api\BannerPipelineApiService" not found`.

- [ ] **Step 3: Write the service**

```php
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
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/BannerPipelineApiServiceTest.php`
Expected: PASS (8 tests).

- [ ] **Step 5: Commit**

```bash
git add src/Service/Api/BannerPipelineApiService.php tests/src/Unit/Service/Api/BannerPipelineApiServiceTest.php
git commit -m "feat: add BannerPipelineApiService"
```

---

### Task 3: `TriggerBannersPipelineService` + extend `AdminActionTriggerController`

**Files:**
- Create: `src/Service/TriggerBannersPipelineService.php`
- Test: `tests/src/Unit/Service/TriggerBannersPipelineServiceTest.php`
- Modify: `src/Controller/Admin/AdminActionTriggerController.php`
- Modify: `tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php`

**Interfaces:**
- Consumes: `GithubActionsApiService $bannerGithubActionsApiService` (Task 1), `BannerPipelineApiService` (Task 2).
- Produces: `App\Service\TriggerBannersPipelineService::triggerUpdateBanners(): void`. `AdminActionTriggerController` now accepts `name in ['update_images', 'update_banners']`.

- [ ] **Step 1: Write the failing test for `TriggerBannersPipelineService`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubActionsApiService;
use App\Service\TriggerBannersPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(TriggerBannersPipelineService::class)]
final class TriggerBannersPipelineServiceTest extends TestCase
{
    #[Test]
    public function triggerUpdateBannersDispatchesAndRegistersRun(): void
    {
        $dispatchedCorrelationId = null;

        $bannerGithubActionsApiService = $this->createMock(GithubActionsApiService::class);
        $bannerGithubActionsApiService
            ->expects($this->once())
            ->method('dispatchWorkflow')
            ->with($this->matchesRegularExpression('/^[0-9a-f-]{36}$/'))
            ->willReturnCallback(function (string $correlationId) use (&$dispatchedCorrelationId): void {
                $dispatchedCorrelationId = $correlationId;
            })
        ;

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (string $correlationId) use (&$dispatchedCorrelationId): bool {
                return $correlationId === $dispatchedCorrelationId;
            }))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $service = new TriggerBannersPipelineService($bannerGithubActionsApiService, $bannerPipelineApiService, $logger);

        $service->triggerUpdateBanners();
    }

    #[Test]
    public function registrationFailureIsLoggedButDoesNotThrow(): void
    {
        $bannerGithubActionsApiService = $this->createMock(GithubActionsApiService::class);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('create')
            ->willThrowException(new \RuntimeException('pokenini-api unreachable'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('pokenini-api unreachable')
        ;

        $service = new TriggerBannersPipelineService($bannerGithubActionsApiService, $bannerPipelineApiService, $logger);

        $service->triggerUpdateBanners();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/TriggerBannersPipelineServiceTest.php`
Expected: FAIL — `Class "App\Service\TriggerBannersPipelineService" not found`.

- [ ] **Step 3: Write `TriggerBannersPipelineService`**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubActionsApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class TriggerBannersPipelineService
{
    public function __construct(
        private readonly GithubActionsApiService $bannerGithubActionsApiService,
        private readonly BannerPipelineApiService $bannerPipelineApiService,
        private readonly LoggerInterface $logger,
    ) {}

    public function triggerUpdateBanners(): void
    {
        $correlationId = Uuid::v4()->toRfc4122();

        $this->bannerGithubActionsApiService->dispatchWorkflow($correlationId);

        try {
            $this->bannerPipelineApiService->create($correlationId);
        } catch (\RuntimeException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
```

The constructor parameter name `$bannerGithubActionsApiService` must match exactly — that's what makes Symfony's autowiring resolve it to the named service from Task 1 instead of the default `GithubActionsApiService` instance.

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/TriggerBannersPipelineServiceTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Extend `AdminActionTriggerController`**

Replace the full contents of `src/Controller/Admin/AdminActionTriggerController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\AdminAction;
use App\Service\TriggerBannersPipelineService;
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
        private readonly TriggerBannersPipelineService $triggerBannersPipelineService,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(
        '/{name}',
        methods: ['POST'],
        condition: "params['name'] in ['update_images', 'update_banners']"
    )]
    #[RateLimit(limiter: 'write_api', key: new Expression("request.headers.get('Authorization') ?? request.getClientIp() ?? 'unknown'"))]
    public function process(string $name): JsonResponse
    {
        $state = 'ok';
        $error = '';

        try {
            if ('update_images' === $name) {
                $this->triggerImagesPipelineService->triggerUpdateImages();
            } else {
                $this->triggerBannersPipelineService->triggerUpdateBanners();
            }

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

- [ ] **Step 6: Update the existing controller test's 3 tests for the new constructor param, and add banner-trigger coverage**

Replace the full contents of `tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AdminActionTriggerController;
use App\Service\TriggerBannersPipelineService;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionTriggerController::class)]
final class AdminActionTriggerControllerTest extends TestCase
{
    #[Test]
    public function action(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
        ;

        $triggerBannersPipelineService = $this->createMock(TriggerBannersPipelineService::class);
        $triggerBannersPipelineService
            ->expects($this->never())
            ->method('triggerUpdateBanners')
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
            $triggerBannersPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(202, $response->getStatusCode());
    }

    #[Test]
    public function failTriggerLogs(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
            ->willThrowException(new \RuntimeException('Aouch'))
        ;

        $triggerBannersPipelineService = $this->createMock(TriggerBannersPipelineService::class);

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
            $triggerBannersPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(500, $response->getStatusCode());
    }

    #[Test]
    public function failTriggerLogsWithInvalidArgumentException(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
            ->willThrowException(new \InvalidArgumentException('Aouch'))
        ;

        $triggerBannersPipelineService = $this->createMock(TriggerBannersPipelineService::class);

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
            $triggerBannersPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(500, $response->getStatusCode());
    }

    #[Test]
    public function bannerAction(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->never())
            ->method('triggerUpdateImages')
        ;

        $triggerBannersPipelineService = $this->createMock(TriggerBannersPipelineService::class);
        $triggerBannersPipelineService
            ->expects($this->once())
            ->method('triggerUpdateBanners')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Admin action succeeded: trigger update_banners')
        ;

        $controller = new AdminActionTriggerController(
            $triggerImagesPipelineService,
            $triggerBannersPipelineService,
            $logger
        );

        $response = $controller->process('update_banners');

        $this->assertSame(202, $response->getStatusCode());
    }

    #[Test]
    public function failBannerTriggerLogs(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);

        $triggerBannersPipelineService = $this->createMock(TriggerBannersPipelineService::class);
        $triggerBannersPipelineService
            ->expects($this->once())
            ->method('triggerUpdateBanners')
            ->willThrowException(new \RuntimeException('Aouch'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'update_banners',
                    'action' => 'trigger',
                ])
            )
        ;

        $controller = new AdminActionTriggerController(
            $triggerImagesPipelineService,
            $triggerBannersPipelineService,
            $logger
        );

        $response = $controller->process('update_banners');

        $this->assertSame(500, $response->getStatusCode());
    }
}
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php tests/src/Unit/Service/TriggerBannersPipelineServiceTest.php`
Expected: PASS (5 + 2 tests).

- [ ] **Step 8: Commit**

```bash
git add src/Service/TriggerBannersPipelineService.php tests/src/Unit/Service/TriggerBannersPipelineServiceTest.php src/Controller/Admin/AdminActionTriggerController.php tests/src/Unit/Controller/Admin/AdminActionTriggerControllerTest.php
git commit -m "feat: trigger update_banners from the admin action controller"
```

---

### Task 4: `BannerPipelineStatusService` + `BannerPipelineStatusController`

**Files:**
- Create: `src/DTO/BannerPipelineStageStatus.php`
- Create: `src/DTO/BannerPipelineStatus.php`
- Create: `src/Service/BannerPipelineStatusService.php`
- Create: `src/Controller/Admin/BannerPipelineStatusController.php`
- Test: `tests/src/Unit/Service/BannerPipelineStatusServiceTest.php`
- Test: `tests/src/Unit/Controller/Admin/BannerPipelineStatusControllerTest.php`

**Interfaces:**
- Consumes: `BannerPipelineApiService` (Task 2), `GithubQueryApiService` (existing, unmodified, reused as-is).
- Produces: `GET /istration/action/trigger/update_banners/status?refresh=` — consumed by `pokenini-web`'s `GetBannerPipelineStatusService` (see the `pokenini-web` plan).

- [ ] **Step 0: Bind the two scalars this task's service consumes**

Task 1 added the `GITHUB_BANNERS_WORKFLOW_FILE`/`GITHUB_BANNERS_WORKFLOW_B_FILE` env vars but deliberately left them unbound (an unconsumed `_defaults.bind` entry hard-fails Symfony's container compilation, and this task is their first real consumer). In `config/services.yaml`, add to the existing `_defaults.bind` map (after `string $githubImagesWorkflowBFile`):

```yaml
            string $githubBannersWorkflowFile: '%env(GITHUB_BANNERS_WORKFLOW_FILE)%'
            string $githubBannersWorkflowBFile: '%env(GITHUB_BANNERS_WORKFLOW_B_FILE)%'
```

Run `docker compose exec php php bin/console cache:clear` to confirm the container now compiles cleanly with these bound (it would have failed before this task, by design — see Task 1's note).

- [ ] **Step 1: Write `BannerPipelineStageStatus` and `BannerPipelineStatus`**

```php
<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Same verified php-code-coverage false-negative as the sibling
 * ImagePipelineStageStatus (identical property-promotion shape) —
 * see that class's docblock for how it was verified.
 *
 * @codeCoverageIgnore
 */
final class BannerPipelineStageStatus
{
    public function __construct(
        public readonly string $state,
        public readonly ?string $url = null,
    ) {}
}
```

```php
<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class BannerPipelineStatus
{
    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        #[SerializedName('correlation_id')]
        public readonly string $correlationId,
        #[SerializedName('workflow_a')]
        public readonly BannerPipelineStageStatus $workflowA,
        #[SerializedName('icon_pr')]
        public readonly BannerPipelineStageStatus $iconPr,
        #[SerializedName('workflow_b')]
        public readonly BannerPipelineStageStatus $workflowB,
        #[SerializedName('resources_pr')]
        public readonly BannerPipelineStageStatus $resourcesPr,
    ) {}
}
```

- [ ] **Step 2: Write the failing test for `BannerPipelineStatusService`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubQueryApiService;
use App\Service\BannerPipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BannerPipelineStatusService::class)]
final class BannerPipelineStatusServiceTest extends TestCase
{
    #[Test]
    public function returnsNullWhenNoRunExists(): void
    {
        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $this->createMock(GithubQueryApiService::class));

        $this->assertNull($service->getStatus(refresh: false));
    }

    #[Test]
    public function withoutRefreshReturnsLatestUnchanged(): void
    {
        $latest = ['correlation_id' => 'corr-1', 'workflow_a_conclusion' => null];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: false));
    }

    #[Test]
    public function refreshPollsWorkflowAWhenNotYetConcluded(): void
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

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
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
            ->with('douzeensemble/pokenini-icon', 'update-banners.yml', 'Update banners (corr-1)')
            ->willReturn([
                'id' => 2,
                'status' => 'completed',
                'conclusion' => 'success',
                'htmlUrl' => 'https://github.com/x/y/actions/runs/2',
                'headSha' => 'sha2',
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshPollsIconPrWhenWorkflowASucceededButNotYetMerged(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];
        $refreshed = array_merge($latest, ['icon_pr_state' => 'open']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'iconPrNumber' => 5,
                'iconPrUrl' => 'https://github.com/x/y/pull/5',
                'iconPrState' => 'open',
                'iconPrMergeCommitSha' => 'merge-sha-5',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findPullRequestByBranch')
            ->with('douzeensemble/pokenini-icon', 'update-banners-2')
            ->willReturn([
                'number' => 5,
                'htmlUrl' => 'https://github.com/x/y/pull/5',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => 'merge-sha-5',
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshPollsWorkflowBWhenIconPrMergedButNotYetConcluded(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => null,
        ];
        $refreshed = array_merge($latest, ['workflow_b_conclusion' => 'success']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'workflowBRunId' => 9,
                'workflowBStatus' => 'completed',
                'workflowBConclusion' => 'success',
                'workflowBUrl' => 'https://github.com/x/y/actions/runs/9',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findWorkflowRunByHeadSha')
            ->with('douzeensemble/pokenini-icon', 'publish-images-to-resources.yml', 'merge-sha-5')
            ->willReturn([
                'id' => 9,
                'status' => 'completed',
                'conclusion' => 'success',
                'htmlUrl' => 'https://github.com/x/y/actions/runs/9',
                'headSha' => 'merge-sha-5',
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshPollsResourcesPrWhenWorkflowBSucceededUsingTheSharedSyncImagesBranchPrefix(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => 'success',
            'resources_pr_state' => null,
        ];
        $refreshed = array_merge($latest, ['resources_pr_state' => 'open']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'resourcesPrNumber' => 11,
                'resourcesPrUrl' => 'https://github.com/x/z/pull/11',
                'resourcesPrState' => 'open',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findPullRequestByBranch')
            // Not "sync-banners-..." - publish-images-to-resources.yml is shared,
            // unmodified, and always names its branch "sync-images-<sha>" no
            // matter which images/** path changed.
            ->with('douzeensemble/pokenini-resources', 'sync-images-merge-sha-5')
            ->willReturn([
                'number' => 11,
                'htmlUrl' => 'https://github.com/x/z/pull/11',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => null,
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenWorkflowAAlreadyFailed(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'failure',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findPullRequestByBranch');
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByHeadSha');
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenPollFindsNothingNew(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findWorkflowRunByDisplayTitle')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    private function getService(
        BannerPipelineApiService $bannerPipelineApiService,
        GithubQueryApiService $githubQueryApiService,
    ): BannerPipelineStatusService {
        return new BannerPipelineStatusService(
            $bannerPipelineApiService,
            $githubQueryApiService,
            'douzeensemble/pokenini-icon',
            'update-banners.yml',
            'publish-images-to-resources.yml',
            'douzeensemble/pokenini-resources',
        );
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/BannerPipelineStatusServiceTest.php`
Expected: FAIL — `Class "App\Service\BannerPipelineStatusService" not found`.

- [ ] **Step 4: Write `BannerPipelineStatusService`**

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubQueryApiService;

class BannerPipelineStatusService
{
    public function __construct(
        private readonly BannerPipelineApiService $bannerPipelineApiService,
        private readonly GithubQueryApiService $githubQueryApiService,
        private readonly string $githubImagesRepo,
        private readonly string $githubBannersWorkflowFile,
        private readonly string $githubBannersWorkflowBFile,
        private readonly string $githubResourcesRepo,
    ) {}

    /**
     * @return null|array<string, mixed>
     */
    public function getStatus(bool $refresh): ?array
    {
        $latest = $this->bannerPipelineApiService->getLatest();

        if (null === $latest || !$refresh) {
            return $latest;
        }

        $updates = $this->pollNextStage($latest);

        if ([] === $updates) {
            return $latest;
        }

        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $this->bannerPipelineApiService->updateFields($correlationId, $updates);

        return $this->bannerPipelineApiService->getLatest();
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollNextStage(array $latest): array
    {
        if (null === $latest['workflow_a_conclusion']) {
            return $this->pollWorkflowA($latest);
        }

        if ('success' === $latest['workflow_a_conclusion'] && 'merged' !== $latest['icon_pr_state']) {
            return $this->pollIconPr($latest);
        }

        if (null !== $latest['icon_pr_merge_commit_sha'] && null === $latest['workflow_b_conclusion']) {
            return $this->pollWorkflowB($latest);
        }

        if ('success' === $latest['workflow_b_conclusion']) {
            return $this->pollResourcesPr($latest);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollWorkflowA(array $latest): array
    {
        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $run = $this->githubQueryApiService->findWorkflowRunByDisplayTitle(
            $this->githubImagesRepo,
            $this->githubBannersWorkflowFile,
            "Update banners ({$correlationId})",
        );

        if (null === $run) {
            return [];
        }

        $updates = [
            'workflowARunId' => $run['id'],
            'workflowAStatus' => $run['status'],
            'workflowAUrl' => $run['htmlUrl'],
        ];

        if (null !== $run['conclusion']) {
            $updates['workflowAConclusion'] = $run['conclusion'];
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollIconPr(array $latest): array
    {
        /** @var int|string $workflowARunId */
        $workflowARunId = $latest['workflow_a_run_id'];

        $pullRequest = $this->githubQueryApiService->findPullRequestByBranch(
            $this->githubImagesRepo,
            "update-banners-{$workflowARunId}",
        );

        if (null === $pullRequest) {
            return [];
        }

        $updates = [
            'iconPrNumber' => $pullRequest['number'],
            'iconPrUrl' => $pullRequest['htmlUrl'],
            'iconPrState' => $pullRequest['state'],
        ];

        if (null !== $pullRequest['mergeCommitSha']) {
            $updates['iconPrMergeCommitSha'] = $pullRequest['mergeCommitSha'];
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollWorkflowB(array $latest): array
    {
        /** @var string $mergeCommitSha */
        $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

        $run = $this->githubQueryApiService->findWorkflowRunByHeadSha(
            $this->githubImagesRepo,
            $this->githubBannersWorkflowBFile,
            $mergeCommitSha,
        );

        if (null === $run) {
            return [];
        }

        $updates = [
            'workflowBRunId' => $run['id'],
            'workflowBStatus' => $run['status'],
            'workflowBUrl' => $run['htmlUrl'],
        ];

        if (null !== $run['conclusion']) {
            $updates['workflowBConclusion'] = $run['conclusion'];
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollResourcesPr(array $latest): array
    {
        /** @var string $mergeCommitSha */
        $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

        $pullRequest = $this->githubQueryApiService->findPullRequestByBranch(
            $this->githubResourcesRepo,
            "sync-images-{$mergeCommitSha}",
        );

        if (null === $pullRequest) {
            return [];
        }

        return [
            'resourcesPrNumber' => $pullRequest['number'],
            'resourcesPrUrl' => $pullRequest['htmlUrl'],
            'resourcesPrState' => $pullRequest['state'],
        ];
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/BannerPipelineStatusServiceTest.php`
Expected: PASS (8 tests).

- [ ] **Step 6: Write the failing test for `BannerPipelineStatusController`**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\BannerPipelineStatusController;
use App\Service\BannerPipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
#[CoversClass(BannerPipelineStatusController::class)]
final class BannerPipelineStatusControllerTest extends TestCase
{
    #[Test]
    public function noRunReturnsEmptyObject(): void
    {
        $service = $this->createMock(BannerPipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(false)->willReturn(null);

        $controller = new BannerPipelineStatusController($service, $this->buildSerializer());

        $response = $controller->get(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
    }

    #[Test]
    public function idleStagesWhenNothingKnownYet(): void
    {
        $service = $this->createMock(BannerPipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(false)->willReturn([
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

        $controller = new BannerPipelineStatusController($service, $this->buildSerializer());

        $response = $controller->get(new Request());

        /**
         * @var array{
         *     correlation_id: string,
         *     workflow_a: array{state: string},
         *     icon_pr: array{state: string},
         * }
         */
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('corr-1', $data['correlation_id']);
        $this->assertSame('idle', $data['workflow_a']['state']);
        $this->assertSame('idle', $data['icon_pr']['state']);
    }

    #[Test]
    public function refreshQueryParamIsForwarded(): void
    {
        $service = $this->createMock(BannerPipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(true)->willReturn(null);

        $controller = new BannerPipelineStatusController($service, $this->buildSerializer());

        $controller->get(new Request(query: ['refresh' => '1']));
    }

    #[Test]
    public function runningAndFailedStates(): void
    {
        $service = $this->createMock(BannerPipelineStatusService::class);
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

        $controller = new BannerPipelineStatusController($service, $this->buildSerializer());

        $response = $controller->get(new Request());

        /**
         * @var array{
         *     workflow_a: array{state: string},
         *     workflow_b: array{state: string},
         * }
         */
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('running', $data['workflow_a']['state']);
        $this->assertSame('failed', $data['workflow_b']['state']);
    }

    #[Test]
    public function doneAndPrPassthroughStates(): void
    {
        $service = $this->createMock(BannerPipelineStatusService::class);
        $service->method('getStatus')->willReturn([
            'correlation_id' => 'corr-1',
            'workflow_a_status' => 'completed',
            'workflow_a_conclusion' => 'success',
            'workflow_a_url' => 'https://github.com/x/y/actions/runs/1',
            'icon_pr_state' => 'merged',
            'icon_pr_url' => 'https://github.com/x/y/pull/1',
            'workflow_b_status' => null,
            'workflow_b_conclusion' => null,
            'workflow_b_url' => null,
            'resources_pr_state' => 'open',
            'resources_pr_url' => 'https://github.com/x/z/pull/2',
        ]);

        $controller = new BannerPipelineStatusController($service, $this->buildSerializer());

        $response = $controller->get(new Request());

        /**
         * @var array{
         *     workflow_a: array{state: string},
         *     icon_pr: array{state: string},
         *     resources_pr: array{state: string},
         * }
         */
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('done', $data['workflow_a']['state']);
        $this->assertSame('merged', $data['icon_pr']['state']);
        $this->assertSame('open', $data['resources_pr']['state']);
    }

    private function buildSerializer(): SerializerInterface
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

        return new Serializer(
            [new ObjectNormalizer($classMetadataFactory, $nameConverter)],
            [new JsonEncoder()]
        );
    }
}
```

- [ ] **Step 7: Run the test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/BannerPipelineStatusControllerTest.php`
Expected: FAIL — `Class "App\Controller\Admin\BannerPipelineStatusController" not found`.

- [ ] **Step 8: Write `BannerPipelineStatusController`**

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\BannerPipelineStageStatus;
use App\DTO\BannerPipelineStatus;
use App\Service\BannerPipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/istration/action/trigger/update_banners')]
final class BannerPipelineStatusController extends AbstractController
{
    public function __construct(
        private readonly BannerPipelineStatusService $service,
        private readonly SerializerInterface $serializer,
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

        /** @var ?string $workflowAStatus */
        $workflowAStatus = $latest['workflow_a_status'] ?? null;

        /** @var ?string $workflowAConclusion */
        $workflowAConclusion = $latest['workflow_a_conclusion'] ?? null;

        /** @var ?string $workflowAUrl */
        $workflowAUrl = $latest['workflow_a_url'] ?? null;

        /** @var ?string $iconPrState */
        $iconPrState = $latest['icon_pr_state'] ?? null;

        /** @var ?string $iconPrUrl */
        $iconPrUrl = $latest['icon_pr_url'] ?? null;

        /** @var ?string $workflowBStatus */
        $workflowBStatus = $latest['workflow_b_status'] ?? null;

        /** @var ?string $workflowBConclusion */
        $workflowBConclusion = $latest['workflow_b_conclusion'] ?? null;

        /** @var ?string $workflowBUrl */
        $workflowBUrl = $latest['workflow_b_url'] ?? null;

        /** @var ?string $resourcesPrState */
        $resourcesPrState = $latest['resources_pr_state'] ?? null;

        /** @var ?string $resourcesPrUrl */
        $resourcesPrUrl = $latest['resources_pr_url'] ?? null;

        $status = new BannerPipelineStatus(
            correlationId: $correlationId,
            workflowA: $this->runStage($workflowAStatus, $workflowAConclusion, $workflowAUrl),
            iconPr: $this->prStage($iconPrState, $iconPrUrl),
            workflowB: $this->runStage($workflowBStatus, $workflowBConclusion, $workflowBUrl),
            resourcesPr: $this->prStage($resourcesPrState, $resourcesPrUrl),
        );

        return JsonResponse::fromJsonString($this->serializer->serialize($status, 'json'));
    }

    private function runStage(?string $status, ?string $conclusion, ?string $url): BannerPipelineStageStatus
    {
        $state = match (true) {
            null === $status => 'idle',
            'completed' === $status && 'success' === $conclusion => 'done',
            'completed' === $status => 'failed',
            default => 'running',
        };

        return new BannerPipelineStageStatus($state, $url);
    }

    private function prStage(?string $state, ?string $url): BannerPipelineStageStatus
    {
        return new BannerPipelineStageStatus($state ?? 'idle', $url);
    }
}
```

- [ ] **Step 9: Run the test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Controller/Admin/BannerPipelineStatusControllerTest.php`
Expected: PASS (5 tests).

- [ ] **Step 10: Commit**

```bash
git add src/DTO/BannerPipelineStageStatus.php src/DTO/BannerPipelineStatus.php src/Service/BannerPipelineStatusService.php src/Controller/Admin/BannerPipelineStatusController.php tests/src/Unit/Service/BannerPipelineStatusServiceTest.php tests/src/Unit/Controller/Admin/BannerPipelineStatusControllerTest.php
git commit -m "feat: add BannerPipelineStatusService + BannerPipelineStatusController"
```

---

### Task 5: Quality gate

**Files:** none new — verification only.

- [ ] **Step 1: Run the full test suite**

Run: `make tests`
Expected: all pass, including the untouched images-pipeline suites.

- [ ] **Step 2: Run coverage and mutation testing**

Run: `make coverage && make infection`
Expected: both 100%. If `BannerPipelineStageStatus`'s constructor (or a similarly-shaped new class) shows as uncovered despite the controller/service tests demonstrably exercising it, this is the same known coverage-tool artifact already documented on `ImagePipelineStageStatus` — already pre-handled with `@codeCoverageIgnore` in the code above; only investigate further if a *different*, unexpected line is flagged.

- [ ] **Step 3: Run quality checks**

Run: `make quality`
Expected: PHPStan level 9, Psalm strict, PHP CS Fixer, PHPMD, Deptrac all pass with no new baseline entries.

- [ ] **Step 4: Commit any formatting fixes**

```bash
make phpcsfixer-fix
git add -A
git commit -m "style: apply cs-fixer" --allow-empty
```

(Skip the commit if `phpcsfixer-fix` made no changes.)

## Self-Review Notes

- **Spec coverage**: config + named `GithubActionsApiService` instance ✓ (Task 1), `BannerPipelineApiService` ✓ (Task 2), `TriggerBannersPipelineService` + `AdminActionTriggerController` extension ✓ (Task 3), `BannerPipelineStatusService`/`Controller` ✓ (Task 4). `GithubQueryApiService` reused unmodified — no task touches it, matching the spec.
- **Placeholder scan**: the `GithubActionsApiService` dual-instance wiring is fully specified (exact `services.yaml` YAML, exact constructor parameter name that must match), not deferred as "configure DI somehow."
- **Type/name consistency**: `$bannerGithubActionsApiService` is spelled identically in `services.yaml` (Task 1) and `TriggerBannersPipelineService`'s constructor (Task 3) — this exact match is what makes Symfony's named-autowiring-alias resolve correctly; `sync-images-` (not `sync-banners-`) is used consistently in both `BannerPipelineStatusService::pollResourcesPr()` and its test, with an explicit test name/comment calling out why.
