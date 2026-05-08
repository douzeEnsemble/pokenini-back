# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Pokenini-back is a **Symfony 8.0 backend** (PHP ≥ 8.5) serving as a BFF (Backend for Frontend) for a Pokémon living/alternate/gender extended dex tracker. It proxies and caches calls to an external `pokenini-api`, handles OAuth2 authentication (Google, Discord, and a fake authenticator for dev), and exposes a JSON API consumed by a frontend.

There is **no database** — all persistent data lives in the external API. The app primarily manages authentication sessions, caches API responses (APCu/Redis via Symfony Cache with tag-based invalidation), and applies business logic like access control and filters.

## Commands

All commands run **inside Docker** via `make`. Start the stack first with `make start`.

```bash
make start           # Build images, install vendors, clear cache
make stop            # Tear down containers
make sh              # Open shell in the PHP container

# Tests
make tests           # All tests (unit + integration)
make tests-unit      # Unit tests only
make tests-integration  # Integration tests only
make t               # Alias for tests

# To run a single test file or filter:
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/MyServiceTest.php
docker compose exec php php vendor/bin/phpunit --filter testMyMethod

# Quality (runs all checks)
make quality         # infra-quality + code-quality
make code-quality    # editorconfig, jsonlint, autoloader, php-cs-fixer, phpmd, psalm, phpstan, deptrac
make cq              # Alias

# Fix code style
make phpcsfixer-fix

# Individual tools
make phpstan
make psalm
make deptrac
make phpcsfixer

# Measures (requires Xdebug coverage mode)
make coverage        # PHPUnit coverage — must reach 100%
make infection       # Mutation testing — must reach 100% MSI
```

## Architecture

### Request flow

`HTTP Request → Symfony Security (OAuth2 / AccessToken / Fake) → Controller → Service → ApiService → External pokenini-api`

The app never touches a database. All data is fetched from the external API. Responses are cached with tag-aware cache for invalidation.

### Layer rules (enforced by deptrac)

| Layer | Can depend on |
|---|---|
| `Controller` | AlbumFilters, DTO, Exception, Security, Service, Validator |
| `Service` | Cache, DTO, Exception, Security, Utils |
| `DTO` | Helper |
| `Security` | Exception |
| `Validator` | Service |
| `AlbumFilters` | (only Symfony HttpFoundation) |

Controllers must not call API services directly — they go through business `Service` classes.

### Key namespaces

- `App\Controller\` — Slim controllers. Each controller handles one endpoint, delegates to a `Service`.
- `App\Service\` — Business logic. Two sub-groups:
  - `App\Service\Api\` — HTTP calls to pokenini-api, all extend `AbstractApiService`. Names follow `Get*ApiService` / `Modify*ApiService`.
  - `App\Service\CacheInvalidator\` — Tag-based cache invalidation services.
- `App\Security\` — OAuth2 authenticators (Google, Discord), FakeAuthenticator (dev only), MockAuthenticator (tests), `UserTokenService` (extracts trainer ID from session), `UserProvider`.
- `App\DTO\` — Typed value objects built from request/response data.
- `App\AlbumFilters\` — Parses URL query parameters into filter arrays for the API call.
- `App\Cache\KeyMaker` — Generates deterministic cache keys.
- `App\Validator\` — Symfony constraint for validating catch states.

### Authentication

Three roles: `admin`, `collector`, `trainer`. Role lists are env-var driven (`LIST_ADMIN`, `LIST_TRAINER`, `LIST_COLLECTOR`).

In dev/test: use `FakeAuthenticator` via `/fr/connect/f/c?t=admin|collector|trainer` to set a session without OAuth.

In tests: `MockAuthenticator` + `MockProvider` are used; integration tests call `$this->authenticatedRequest($client, 'trainer', ...)` from `ClientRequestTrait`.

### Testing

- **Unit tests**: `tests/src/Unit/` — pure PHPUnit, no HTTP stack.
- **Integration tests**: `tests/src/Integration/` — use `WebTestCase`, full Symfony kernel, tagged `#[Group('api-mocked-testing')]`. External API calls are intercepted by **Moco** (a mock server running in Docker).
- Mock responses are JSON files in `tests/resources/moco/Api/responses/` and `tests/resources/functional/controller/` (expected response snapshots).
- Debug trick: uncomment the `file_put_contents` block in `JsonResponseTrait` to dump actual response to a file for comparison.
- Coverage and mutation testing must both hit **100%**.

### Tools

Quality tools are isolated in `tools/*/` subdirectories with their own `composer.json`, installed separately from the main app. Never add quality tool dependencies to the main `composer.json`.
