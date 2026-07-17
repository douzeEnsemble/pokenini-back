# Pokémon Image Credit System (pokenini-back) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Proxy pokenini-api's new `GET /credits` deduplicated endpoint through pokenini-back with caching, and prove the 4 new embedded credit fields on existing Pokémon endpoints flow through unchanged.

**Architecture:** A new `GetCreditsApiService` (cached, no-parameter GET, mirrors `GetTypesApiService`) behind a new standalone `CreditsController` (mirrors `TrainerDexListController`'s single-service pattern), registered as `PUBLIC_ACCESS` like `/labels`. Cache invalidation piggybacks on the existing `'labels'` invalidation type via a new `CreditsCacheInvalidatorService`. The embedded credit fields on `/album/*` and `/election/*` responses require **no service/controller code changes** — pokenini-back proxies Pokémon data as raw decoded JSON arrays, confirmed by tracing both the Album and Election flows end-to-end.

**Tech Stack:** Symfony 8 / PHP ≥ 8.5, Symfony Cache (`TagAwareCacheInterface`, APCu/Redis-backed), Moco (HTTP mock server), PHPUnit.

**Related documents:** Design spec at `pokenini-web/docs/superpowers/specs/2026-07-17-pokemon-image-credit-system-design.md`. This is the second of three repo-scoped plans (pokenini-api → **pokenini-back** → pokenini-web). The pokenini-api slice (entity, embedded credit fields, `/credits` endpoint, Sheets sync) is merged — see `pokenini-api/docs/superpowers/plans/2026-07-17-pokemon-image-credit-system.md` for its exact shapes, which this plan consumes as-is.

## Global Constraints

- `declare(strict_types=1)` in every PHP file.
- Controller: `final class`.
- Service/Api-Service/CacheInvalidator: NOT `final` (PHPUnit mocking).
- `KeyMaker`: `final class`, static methods only, no dependencies (Deptrac: `AppCache` layer has zero allowed dependencies).
- Every test class: `/** @internal */` + `#[CoversClass(TargetClass::class)]`.
- Deptrac layering: `Controller` → `Service` (includes `Service\Api` and `Service\CacheInvalidator` — same layer, no intermediate business-Service required, matching the `LabelsController`→`GetLabelsApiService` and `TrainerDexListController`→`GetDexListApiService` precedent of a Controller calling a `Service\Api\*` class directly).
- Integration tests: `#[Group('api-mocked-testing')]`, use `ClientRequestTrait` (`authenticatedRequest($client, 'trainer'|'collector'|'admin', ...)`) and `JsonResponseTrait` (`assertResponseContent($client, 'Path/file.json')` — compares against `tests/resources/functional/controller/Path/file.json`).
- 100% code coverage and 100% Mutation Score Index required (`make coverage`, `make infection`). PHPStan level 9, Psalm strict, Deptrac clean, PHP CS Fixer clean.
- Moco routing lives in `tests/resources/moco/Api/moco.json` (explicit request→file routing table, matched by `uri`/`queries`/`method`/`headers` — all mocked requests require `authorization: Basic d2ViOmRvdXpl`). Response bodies live in `tests/resources/moco/Api/responses/`.
- All commands run via `make`/`docker compose exec php` inside the Docker container.

---

### Task 1: `GET /credits` proxy endpoint

**Files:**
- Modify: `src/Cache/KeyMaker.php`
- Create: `src/Service/Api/GetCreditsApiService.php`
- Create: `src/Controller/CreditsController.php`
- Modify: `config/packages/security.yaml`
- Modify: `tests/resources/moco/Api/moco.json`
- Create: `tests/resources/moco/Api/responses/credits.json`
- Create: `tests/resources/unit/service/api/credits.json`
- Modify: `tests/src/Unit/Cache/KeyMakerTest.php`
- Create: `tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`
- Create: `tests/src/Integration/Credits/CreditsTest.php`
- Create: `tests/resources/functional/controller/Credits/all.json`

**Interfaces:**
- Produces: `App\Cache\KeyMaker::getCreditsKey(): string` (returns `'credits'`) — consumed by Task 2's invalidator.
- Produces: `App\Service\Api\GetCreditsApiService::get(): array` — returns the decoded `[{"name": ..., "url": ...}, ...]` array, cached under `KeyMaker::getCreditsKey()`.
- Produces: `GET /credits` (PUBLIC_ACCESS, no auth required) → the same array as JSON.

- [ ] **Step 1: Write the failing `KeyMaker` unit test**

Add to `tests/src/Unit/Cache/KeyMakerTest.php` (alongside the other simple no-arg key tests):

```php
    public function testGetCreditsKey(): void
    {
        $this->assertEquals('credits', KeyMaker::getCreditsKey());
    }
```

Run: `docker compose exec php php vendor/bin/phpunit --filter testGetCreditsKey tests/src/Unit/Cache/KeyMakerTest.php`
Expected: FAIL — `Call to undefined method App\Cache\KeyMaker::getCreditsKey()`.

- [ ] **Step 2: Implement `KeyMaker::getCreditsKey()`**

In `src/Cache/KeyMaker.php`, add a new constant next to the existing `CACHE_KEY_*` constants:

```php
    private const string CACHE_KEY_CREDITS = 'credits';
```

And a new method next to `getReportsKey()`:

```php
    public static function getCreditsKey(): string
    {
        return self::CACHE_KEY_CREDITS;
    }
```

Run: `docker compose exec php php vendor/bin/phpunit --filter testGetCreditsKey tests/src/Unit/Cache/KeyMakerTest.php`
Expected: PASS.

- [ ] **Step 3: Write the failing `GetCreditsApiService` unit test**

Create `tests/resources/unit/service/api/credits.json`:

```json
[
    {
        "name": "PokéSprite",
        "url": "https://github.com/msikma/pokesprite"
    },
    {
        "name": "PokemonDB",
        "url": "https://pokemondb.net"
    }
]
```

Create `tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetCreditsApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetCreditsApiService::class)]
final class GetCreditsApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $credits = $this->getService()->get();

        $this->assertCount(2, $credits);
        $this->assertSame('PokéSprite', $credits[0]['name']);
        $this->assertSame('https://github.com/msikma/pokesprite', $credits[0]['url']);

        /** @var string $value */
        $value = $this->cache->getItem('credits')->get();
        $this->assertNotEmpty($value);
        $this->assertJson($value);
    }

    private function getService(): GetCreditsApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/unit/service/api/credits.json');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/credits',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetCreditsApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`
Expected: FAIL — class `GetCreditsApiService` does not exist.

- [ ] **Step 4: Implement `GetCreditsApiService`**

```php
<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetCreditsApiService extends AbstractApiService
{
    /**
     * @return array<int, array{name: string, url: string}>
     */
    public function get(): array
    {
        $key = KeyMaker::getCreditsKey();

        $json = $this->cache->get($key, function () {
            return $this->requestContent(
                'GET',
                '/credits',
            );
        });

        /** @var array<int, array{name: string, url: string}> */
        return JsonDecoder::decode($json);
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Add the Moco route and response fixture**

Create `tests/resources/moco/Api/responses/credits.json`:

```json
[
    {
        "name": "PokéSprite",
        "url": "https://github.com/msikma/pokesprite"
    },
    {
        "name": "PokemonDB",
        "url": "https://pokemondb.net/sprites/bulbasaur"
    },
    {
        "name": "Bulbapedia",
        "url": "https://bulbapedia.bulbagarden.net"
    },
    {
        "name": "Serebii",
        "url": "https://serebii.net"
    }
]
```

In `tests/resources/moco/Api/moco.json`, add a new routing entry (anywhere among the other simple GET routes, e.g. near the `/types` entry):

```json
{
  "request": {
    "uri": "/credits",
    "method": "get",
    "headers": {
      "accept": "application/json",
      "authorization": "Basic d2ViOmRvdXpl"
    }
  },
  "response": {
    "status": "200",
    "file": "/var/moco/responses/credits.json"
  }
},
```

- [ ] **Step 6: Write the failing security.yaml + `CreditsController` integration test**

Create `tests/resources/functional/controller/Credits/all.json` (must exactly match `tests/resources/moco/Api/responses/credits.json` from Step 5, since the controller passes the upstream response through unchanged):

```json
[
    {
        "name": "PokéSprite",
        "url": "https://github.com/msikma/pokesprite"
    },
    {
        "name": "PokemonDB",
        "url": "https://pokemondb.net/sprites/bulbasaur"
    },
    {
        "name": "Bulbapedia",
        "url": "https://bulbapedia.bulbagarden.net"
    },
    {
        "name": "Serebii",
        "url": "https://serebii.net"
    }
]
```

Create `tests/src/Integration/Credits/CreditsTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration\Credits;

use App\Controller\CreditsController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGet(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/credits',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Credits/all.json');
    }

    public function testGetNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/credits',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Credits/all.json');
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Credits/CreditsTest.php`
Expected: FAIL — 404 (no such route) on both tests.

- [ ] **Step 7: Implement `CreditsController` and the `security.yaml` access rule**

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\GetCreditsApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/credits')]
final class CreditsController extends AbstractController
{
    public function __construct(
        private readonly GetCreditsApiService $service,
    ) {}

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return new JsonResponse(
            $this->service->get(),
            Response::HTTP_OK
        );
    }
}
```

In `config/packages/security.yaml`, add a new `access_control` entry immediately after the `^/labels` line (order matters — it must come before the catch-all `^/, roles: ROLE_USER`):

```yaml
    - { path: ^/labels, roles: PUBLIC_ACCESS }
    - { path: ^/credits, roles: PUBLIC_ACCESS }
    - { path: ^/, roles: ROLE_USER }
```

- [ ] **Step 8: Run tests and verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Credits/CreditsTest.php`
Expected: PASS (both `testGet` and `testGetNonAuthenticated`).

- [ ] **Step 9: Commit**

```bash
git add src/Cache/KeyMaker.php src/Service/Api/GetCreditsApiService.php src/Controller/CreditsController.php config/packages/security.yaml tests/resources/moco/Api/moco.json tests/resources/moco/Api/responses/credits.json tests/resources/unit/service/api/credits.json tests/resources/functional/controller/Credits/all.json tests/src/Unit/Cache/KeyMakerTest.php tests/src/Unit/Service/Api/GetCreditsApiServiceTest.php tests/src/Integration/Credits/CreditsTest.php
git commit -m "Add GET /credits proxy endpoint with caching"
```

---

### Task 2: Cache invalidation for credits

**Files:**
- Create: `src/Service/CacheInvalidator/CreditsCacheInvalidatorService.php`
- Create: `tests/src/Unit/Service/CacheInvalidator/CreditsCacheInvalidatorServiceTest.php`

**Interfaces:**
- Consumes: `KeyMaker::getCreditsKey()` from Task 1.
- Produces: a new class auto-tagged `app.cache_invalidator` (via `CacheInvalidatorInterface`'s `#[AutoconfigureTag]`, no manual service registration needed) supporting invalidation type `'labels'` — meaning `DELETE /istration/action/invalidate/labels` (an existing, already-allowed route name) now also clears the credits cache entry alongside types/forms/catch_states. No changes needed to `AdminActionInvalidateController` or `AdminActionUpdateController`, since `'labels'` is already in both of their allowed-name lists.

Credits are a small, rarely-changing, deduplicated lookup list — the same shape as types/forms/catch_states, all already grouped under the `'labels'` invalidation type. This task follows that exact precedent rather than inventing a new invalidation type.

- [ ] **Step 1: Write the failing unit test**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CacheInvalidator;

use App\Service\CacheInvalidator\CreditsCacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
#[CoversClass(CreditsCacheInvalidatorService::class)]
final class CreditsCacheInvalidatorServiceTest extends TestCase
{
    public function testGetSupportedTypes(): void
    {
        $service = new CreditsCacheInvalidatorService($this->createStub(TagAwareAdapter::class));

        $this->assertSame(['labels'], $service->getSupportedTypes());
    }

    public function testInvalidate(): void
    {
        $cachePool = new ArrayAdapter();
        $cache = new TagAwareAdapter($cachePool, new ArrayAdapter());

        $cache->get('douze', fn () => 'DouZe');
        $cache->get('credits', fn () => 'whatever');

        $service = new CreditsCacheInvalidatorService($cache);
        $service->invalidate();

        $this->assertTrue($cache->hasItem('douze'));
        $this->assertFalse($cache->hasItem('credits'));
    }
}
```

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/CacheInvalidator/CreditsCacheInvalidatorServiceTest.php`
Expected: FAIL — class does not exist.

- [ ] **Step 2: Implement `CreditsCacheInvalidatorService`**

```php
<?php

declare(strict_types=1);

namespace App\Service\CacheInvalidator;

use App\Cache\KeyMaker;

class CreditsCacheInvalidatorService extends AbstractCacheInvalidatorService implements CacheInvalidatorInterface
{
    #[\Override]
    public function getSupportedTypes(): array
    {
        return ['labels'];
    }

    #[\Override]
    public function invalidate(): void
    {
        $this->cache->delete(KeyMaker::getCreditsKey());
    }
}
```

- [ ] **Step 3: Run tests and verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/CacheInvalidator/CreditsCacheInvalidatorServiceTest.php`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add src/Service/CacheInvalidator/CreditsCacheInvalidatorService.php tests/src/Unit/Service/CacheInvalidator/CreditsCacheInvalidatorServiceTest.php
git commit -m "Invalidate credits cache alongside labels"
```

---

### Task 3: Prove embedded credit fields pass through unchanged

**Files:**
- Modify: `tests/resources/moco/Api/responses/album/default/demo.json`
- Modify: `tests/resources/functional/controller/Pokedex/demo.json`
- Modify: `tests/resources/moco/Api/responses/pokemons_topick_demolite_12.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolite.json`
- Modify: `src/Service/Api/GetPokemonsApiService.php`
- Modify: `src/DTO/ElectionPokemonsList.php`

**Interfaces:**
- Consumes: the 4-nested-field shape (`small_regular_credit`/`small_shiny_credit`/`big_regular_credit`/`big_shiny_credit`, each `null` or `{name: string, url: string}`) already present on pokenini-api's `PokemonDataResponse`.
- Produces: nothing new — this task only strengthens existing test fixtures/assertions and documentation-only type annotations. No production behavior changes (confirmed in Task 1's research: both the Album and Election flows decode JSON into raw arrays/loosely-typed DTOs and re-serialize without touching individual pokemon fields).

Both `AlbumPokedexTest::testGet()` (route `/album/demo`, comparing against `tests/resources/functional/controller/Pokedex/demo.json`) and `ElectionIndexTest::testDex('demolite')` (route `/election/demolite`, comparing against `tests/resources/functional/controller/ElectionIndex/demolite.json`) already do an exact JSON-body comparison (`assertJsonStringEqualsJsonFile` via `JsonResponseTrait::assertResponseContent()`). Adding the 4 new fields identically to both the upstream Moco fixture and the expected functional fixture, for the same pokemon entry, is sufficient to prove end-to-end passthrough — no new assertion code is needed, only synchronized fixture data.

- [ ] **Step 1: Add credit fields to the Album Moco fixture and its matching functional fixture**

In `tests/resources/moco/Api/responses/album/default/demo.json`, find the `bulbasaur` entry's `pokemon` object (nested under whichever list item has `"slug": "bulbasaur"`) and add 4 new keys to it:

```json
"small_regular_credit": {"name": "PokéSprite", "url": "https://github.com/msikma/pokesprite"},
"small_shiny_credit": null,
"big_regular_credit": {"name": "PokemonDB", "url": "https://pokemondb.net/sprites/bulbasaur"},
"big_shiny_credit": null
```

Apply the exact same 4 keys with the exact same values to the `bulbasaur` entry's `pokemon` object in `tests/resources/functional/controller/Pokedex/demo.json`.

- [ ] **Step 2: Run the Album integration test, confirm it's still green**

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Integration/Album/AlbumPokedexTest.php`
Expected: PASS. (If it fails, the two fixture files are not byte-identical for the new keys — compare them directly.)

- [ ] **Step 3: Add credit fields to the Election Moco fixture and its matching functional fixture**

In `tests/resources/moco/Api/responses/pokemons_topick_demolite_12.json`, find the `bulbasaur` entry's `pokemon` object and add the same 4 keys (reuse the exact same example values as Step 1, for consistency):

```json
"small_regular_credit": {"name": "PokéSprite", "url": "https://github.com/msikma/pokesprite"},
"small_shiny_credit": null,
"big_regular_credit": {"name": "PokemonDB", "url": "https://pokemondb.net/sprites/bulbasaur"},
"big_shiny_credit": null
```

Apply the exact same 4 keys with the exact same values to the `bulbasaur` entry's `pokemon` object in `tests/resources/functional/controller/ElectionIndex/demolite.json`.

- [ ] **Step 4: Run the Election integration test, confirm it's still green**

Run: `docker compose exec php php vendor/bin/phpunit --filter "testDex.*demolite$" tests/src/Integration/Election/ElectionIndexTest.php`
Expected: PASS.

- [ ] **Step 5: Update stale array-shape docblocks for documentation accuracy**

These are PHPStan/Psalm documentation annotations only — not runtime-enforced, but they should stay accurate. In `src/Service/Api/GetPokemonsApiService.php`, in the `/** @var array{...} */` docblock above `$data = JsonDecoder::decode($json);`, find the `pokemon` sub-shape's closing segment `game_bundles_shiny: array<int, array{slug: string}>}` and replace it with:

```
game_bundles_shiny: array<int, array{slug: string}>, small_regular_credit: null|array{name: string, url: string}, small_shiny_credit: null|array{name: string, url: string}, big_regular_credit: null|array{name: string, url: string}, big_shiny_credit: null|array{name: string, url: string}}
```

In `src/DTO/ElectionPokemonsList.php`, the identical `pokemon` sub-shape text appears **three times** (the `@var` docblock above the `$items` property, the `@param` docblock above the constructor, and the inline `/** @var ... */` docblock inside the constructor body). Apply the exact same replacement (`game_bundles_shiny: array<int, array{slug: string}>}` → the expanded version above) in all three occurrences.

- [ ] **Step 6: Run static analysis to confirm the docblock edits are syntactically valid**

Run: `docker compose exec php php tools/phpstan/vendor/bin/phpstan analyse src/Service/Api/GetPokemonsApiService.php src/DTO/ElectionPokemonsList.php --memory-limit=-1`
Expected: no new errors.

Run: `docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetPokemonsApiServiceTest.php`
Expected: PASS (docblock-only change, behavior unaffected).

- [ ] **Step 7: Commit**

```bash
git add tests/resources/moco/Api/responses/album/default/demo.json tests/resources/functional/controller/Pokedex/demo.json tests/resources/moco/Api/responses/pokemons_topick_demolite_12.json tests/resources/functional/controller/ElectionIndex/demolite.json src/Service/Api/GetPokemonsApiService.php src/DTO/ElectionPokemonsList.php
git commit -m "Prove embedded image-credit fields pass through Album/Election unchanged"
```

---

### Task 4: Final verification

- [ ] **Step 1: Run the full quality and measures suite**

```bash
make quality
make coverage
make infection
```

Expected: all green — 100% coverage, 100% MSI, PHPStan level 9 clean, Psalm strict clean, Deptrac clean, PHP CS Fixer clean.

- [ ] **Step 2: Run the full test suite**

```bash
make tests
```

Expected: all unit + integration tests pass.

- [ ] **Step 3: Manual smoke check against a real pokenini-api**

With both `pokenini-api` and `pokenini-back` stacks running locally (`make start` in each), request `/credits` through pokenini-back directly:

```bash
curl -H "Authorization: Bearer this-is-the-trainer-token" -H "X-Provider: mock" http://localhost:<pokenini-back-port>/credits
```

Confirm it returns pokenini-api's live `/credits` response (empty array `[]` is expected if the Sheet hasn't been backfilled with credit data yet — this is not a failure, matching the pokenini-api plan's noted out-of-scope manual content task).
