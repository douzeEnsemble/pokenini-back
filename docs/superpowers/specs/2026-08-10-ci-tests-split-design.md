# Design: split `ci_tests.yml` into parallel jobs + Docker image archive cache (pokenini-back)

## Purpose

`ci_tests.yml` currently runs everything in a single job (`allin`), sequentially, on one
runner: full test suite, then coverage, then Infection (mutation testing), then
`symfony security:check`. A failure anywhere is only visible after reading the whole log,
and nothing runs in parallel. `ci_codequality.yml` and `ci_infraquality.yml` already use
independent parallel jobs for this reason; `ci_tests.yml` is the odd one out.

Goals: parallelize (shorter wall-clock CI time) and isolate failures (know immediately
whether it's unit, integration, measures, or security that broke).

## Current state (baseline)

Single job `allin`:
1. `phpunit tests/src/ --exclude-group="time-testing"` (full suite)
2. `phpunit --coverage-clover=coverage.xml --coverage-xml=build/coverage/coverage-xml --log-junit=build/coverage/junit.xml -e XDEBUG_MODE=coverage --exclude-group="browser-testing"` (full suite again, instrumented)
3. `composer install --working-dir=tools/infection`, then Infection using the coverage from step 2
4. `symfony security:check`

Neither `time-testing` nor `browser-testing` groups exist anywhere in `tests/src` today
(verified via grep) — only `api-mocked-testing` is used, on Integration tests. The two
`--exclude-group` flags are kept as-is (no behavior change), in case they're forward-looking.

The `Makefile` already exposes the unit/integration split locally: `tests-unit` runs
`tests/src/Unit`, `tests-integration` runs `tests/src/Integration`. Per this project's
`CLAUDE.md`, Unit tests are pure PHPUnit (no HTTP stack); Integration tests use the full
Symfony kernel against Moco-mocked HTTP.

## Job split

`ci_tests.yml` becomes 4 independent jobs, each using
`./.github/actions/docker-compose` (unchanged trigger conditions: `push` to `main`,
`pull_request`, `workflow_call`):

| Job | Runs | Notes |
|---|---|---|
| `unit-tests` | `phpunit tests/src/Unit --exclude-group="time-testing"` | |
| `integration-tests` | `phpunit tests/src/Integration --exclude-group="time-testing"` | needs Moco, already started by the shared action |
| `measures` | coverage step then Infection, in that order, in the same job | unchanged from current steps 2–3; Infection consumes the coverage the previous step in *this job* just produced, so these two stay sequential and can't be split across jobs without adding artifact upload/download — out of scope |
| `security` | `symfony security:check` | unchanged from current step 4 |

Each job still spins up the full `docker-compose.yaml` stack (`php`, `moco.api`,
`moco.oauth2`, `web`) via the shared composite action, even `unit-tests` and `security`
which don't need Moco/nginx. Scoping which services start per job is a separate,
follow-up optimization (would require parameterizing the composite action) — not done
here to keep this change focused.

## Docker image archive cache

With 4 parallel jobs now each invoking `./.github/actions/docker-compose`, the image
build (`php` dev target + `moco.api` + `moco.oauth2`) would otherwise happen up to 4×
per workflow run. Add an archive-based cache to the composite action so most runs skip
the build entirely.

### Cache key

Hash of the files that actually affect the built images:
`.docker/php/Dockerfile`, `.docker/php/conf.d/**`, `.docker/moco/Dockerfile`,
`docker-compose.yaml`, plus the host `UID`/`GID` build args.

Confirmed by reading `.docker/php/Dockerfile`: the `php_dev` target (the one CI uses)
does not `COPY` application source or `composer.lock` — only `conf.d/*.ini` files and a
`symfony-cli` binary pulled from a pinned image tag. So the image is invalidated only by
infra changes, never by application code changes. `vendor/` is populated separately via
bind-mount + `composer install` inside each job (unaffected by, and out of scope for,
this cache).

### Flow (inside `.github/actions/docker-compose/action.yml`)

- **Cache hit** (`actions/cache@v5` restore succeeds): `docker load` the archive.
  Skip Docker Hub login, Buildx setup, bake, and pull entirely.
- **Cache miss**: existing steps unchanged (login → setup buildx → bake → pull), plus a
  new final step: `docker save $(docker compose config --images) -o
  /tmp/docker-images-cache/images.tar`, saved via `actions/cache` under the computed key.
  This captures the built `php`/`moco.*` images *and* the pulled `web` (nginx) image
  together, so a subsequent cache hit needs no pull at all.
- In both paths, drop `--build` from the final `docker compose up --wait` (was
  `--build --wait`). The wanted images are already present locally with the right tags
  either way; keeping `--build` risked triggering a second, non-buildx rebuild on the
  cache-hit path that would ignore the freshly loaded archive.
- Expected benign race: the first run after a Dockerfile change misses the cache in all
  4 jobs simultaneously; each builds and attempts to save under the same key.
  `actions/cache` accepts only the first write for a given key per run and silently
  skips the rest — not an error.

### Non-goals

- No caching of `vendor/` (bind-mounted, installed fresh per job today — untouched).
- No dedicated `build-images` job with `needs:` fan-out; the shared best-effort cache
  keeps the change contained to the composite action instead of restructuring the
  workflow's job graph.

## Testing / validation

YAML syntax check. Push the branch and confirm in the Actions UI: 4 jobs run in
parallel, each finishes with the same pass/fail result the current single job produces
for the corresponding step, and the second CI run on the same branch (no Dockerfile
change) shows a cache hit with no build steps executed.
