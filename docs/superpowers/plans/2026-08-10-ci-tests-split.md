# CI Tests Split + Docker Image Archive Cache Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split `pokenini-back`'s single-job `ci_tests.yml` into 4 parallel jobs (unit, integration, measures, security) and add a Docker image archive cache to the shared composite action so the now-4x image build isn't repeated on every run.

**Architecture:** Two independent, additive changes to GitHub Actions YAML — no application code touched. Task 1 replaces the one `allin` job in `ci_tests.yml` with 4 jobs that each call the existing `./.github/actions/docker-compose` composite action, splitting its 4 sequential steps 1-for-1 across jobs. Task 2 adds a `docker save`/`actions/cache`/`docker load` archive-cache step inside that composite action, keyed on the files that affect the built images, so a cache hit skips Docker Hub login, Buildx setup, bake, and pull.

**Tech Stack:** GitHub Actions YAML, Docker Compose, `actions/cache@v5`.

## Global Constraints

- No change to trigger conditions: `push` to `main`, `pull_request: ~`, `workflow_call` with `DOCKERHUB_USERNAME`/`DOCKERHUB_TOKEN` secrets — copied verbatim across all 4 new jobs.
- `PHP_CS_FIXER_IGNORE_ENV: 1` env var stays at workflow level (unused by these jobs but kept for parity with the other CI workflows in this repo).
- Neither `time-testing` nor `browser-testing` PHPUnit groups exist in `tests/src` today — keep both `--exclude-group` flags exactly as they are today; do not remove them.
- The `php_dev` Docker Compose build target does not `COPY` application source or `composer.lock` — only `.docker/php/conf.d/*.ini` and a pinned `symfony-cli` binary. `vendor/` is bind-mounted and installed fresh per job; it is untouched by this plan.
- Pushing the branch and watching the real Actions run is the final verification step in each task, but must not be done without checking with the user first — these are shared-state actions (push, and if applicable a PR).

---

### Task 1: Split `ci_tests.yml` into 4 parallel jobs

**Files:**
- Modify: `.github/workflows/ci_tests.yml` (full rewrite of the `jobs:` section — the `on:` and top-level `env:` blocks are unchanged)

**Interfaces:**
- Consumes: `./.github/actions/docker-compose` composite action (unchanged in this task — used as-is, one `uses:` per job).
- Produces: 4 job names (`unit-tests`, `integration-tests`, `measures`, `security`) that Task 2's change to the composite action will be exercised by.

- [ ] **Step 1: Read the current file to confirm no drift**

Run: `cat .github/workflows/ci_tests.yml`

Confirm it still matches this baseline `jobs:` section (if it doesn't, stop and re-check with the user before continuing — someone else may have changed it):

```yaml
jobs:
  allin:
    name: All tests
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec -T php php vendor/bin/phpunit tests/src/ --exclude-group="time-testing"
      - name: Measures / Run Coverage
        run: |
          docker compose exec \
            -e XDEBUG_MODE=coverage \
            -T php php vendor/bin/phpunit \
            --exclude-group="browser-testing" \
            --coverage-clover=coverage.xml \
            --coverage-xml=build/coverage/coverage-xml \
            --log-junit=build/coverage/junit.xml
      - name: Measures / Composer install Infection
        shell: bash
        run: docker compose exec -T php composer install --working-dir=tools/infection --prefer-dist --no-progress --no-interaction
      - name: Measures / Run Infection
        run: |
          docker compose exec -T php php tools/infection/vendor/bin/infection \
            --threads=4 --no-progress \
            --skip-initial-tests --coverage=build/coverage \
            --min-msi=100 --min-covered-msi=100 \
            --filter=src
      - name: Security / Symfony Security Check
        run: |
          docker compose exec -T php symfony security:check
```

- [ ] **Step 2: Replace the `jobs:` section**

Replace everything from `jobs:` to the end of the file with:

```yaml
jobs:
  unit-tests:
    name: Unit Tests
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec -T php php vendor/bin/phpunit tests/src/Unit --exclude-group="time-testing"

  integration-tests:
    name: Integration Tests
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: PHPUnit / Run
        run: |
          docker compose exec -T php php vendor/bin/phpunit tests/src/Integration --exclude-group="time-testing"

  measures:
    name: Measures (Coverage & Mutation Testing)
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: Measures / Run Coverage
        run: |
          docker compose exec \
            -e XDEBUG_MODE=coverage \
            -T php php vendor/bin/phpunit \
            --exclude-group="browser-testing" \
            --coverage-clover=coverage.xml \
            --coverage-xml=build/coverage/coverage-xml \
            --log-junit=build/coverage/junit.xml
      - name: Measures / Composer install Infection
        shell: bash
        run: docker compose exec -T php composer install --working-dir=tools/infection --prefer-dist --no-progress --no-interaction
      - name: Measures / Run Infection
        run: |
          docker compose exec -T php php tools/infection/vendor/bin/infection \
            --threads=4 --no-progress \
            --skip-initial-tests --coverage=build/coverage \
            --min-msi=100 --min-covered-msi=100 \
            --filter=src

  security:
    name: Security
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v6
      - name: Prepare
        uses: ./.github/actions/docker-compose
      - name: Security / Symfony Security Check
        run: |
          docker compose exec -T php symfony security:check
```

- [ ] **Step 3: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/ci_tests.yml'))" && echo OK`
Expected: `OK` (a syntax error will raise `yaml.scanner.ScannerError` or similar and print a traceback instead)

- [ ] **Step 4: Confirm every original step survived the split**

Run: `git diff .github/workflows/ci_tests.yml` and check by eye against the Step 1
baseline: every `run:` command block must be byte-identical to the original, with
exactly two intentional exceptions — `tests/src/` narrowed to `tests/src/Unit` in the
`unit-tests` job's PHPUnit command, and to `tests/src/Integration` in the
`integration-tests` job's. No other command text should differ.

- [ ] **Step 5: Commit**

```bash
git add .github/workflows/ci_tests.yml
git commit -m "ci: split ci_tests.yml into parallel unit/integration/measures/security jobs"
```

---

### Task 2: Add Docker image archive cache to the shared composite action

**Files:**
- Modify: `.github/actions/docker-compose/action.yml`

**Interfaces:**
- Consumes: nothing new — same composite action interface (`uses: ./.github/actions/docker-compose`, no inputs), called identically by all 4 jobs from Task 1.
- Produces: no new outputs; behavior change only (skips build on cache hit).

- [ ] **Step 1: Read the current file to confirm no drift**

Run: `cat .github/actions/docker-compose/action.yml`

Confirm it still matches this baseline (stop and re-check with the user if it doesn't):

```yaml
name: Docker Compose Pull Up And Check
description: Use to docker compose pull and up then checks if services are correctly running
author: Renaud Douze
runs:
  using: "composite"
  steps:
    - name: Copy .env file
      shell: bash
      run: cp .env.ci .env

    - name: Export host UID/GID for Docker build
      shell: bash
      run: |
        echo "APP_UID=$(id -u)" >> "$GITHUB_ENV"
        echo "APP_GID=$(id -g)" >> "$GITHUB_ENV"

    - name: Login to Docker Hub
      uses: docker/login-action@v4
      with:
        username: ${{ env.DOCKERHUB_USERNAME }}
        password: ${{ env.DOCKERHUB_TOKEN }}

    - name: Setup Docker Buildx
      uses: docker/setup-buildx-action@v4

    - name: Docker Buildx Bake
      uses: docker/bake-action@v7
      with:
        load: true
        set: |
          *.cache-to=type=gha,mode=max                                                                                
          *.cache-from=type=gha 
      
    - name: Pull images
      shell: bash
      run: docker compose pull --ignore-pull-failures || true

    - name: Start services
      shell: bash
      run: docker compose --verbose up --build --wait

    - name: Composer install
      shell: bash
      run: docker compose exec -T php composer install --prefer-dist --no-progress --no-interaction
```

- [ ] **Step 2: Replace the file contents**

```yaml
name: Docker Compose Pull Up And Check
description: Use to docker compose pull and up then checks if services are correctly running
author: Renaud Douze
runs:
  using: "composite"
  steps:
    - name: Copy .env file
      shell: bash
      run: cp .env.ci .env

    - name: Export host UID/GID for Docker build
      shell: bash
      run: |
        echo "APP_UID=$(id -u)" >> "$GITHUB_ENV"
        echo "APP_GID=$(id -g)" >> "$GITHUB_ENV"

    - name: Restore Docker images archive cache
      id: docker-images-cache
      uses: actions/cache@v5
      with:
        path: /tmp/docker-images-cache/images.tar
        key: docker-images-${{ runner.os }}-${{ hashFiles('.docker/php/Dockerfile', '.docker/php/conf.d/**', '.docker/moco/Dockerfile', 'docker-compose.yaml') }}-${{ env.APP_UID }}-${{ env.APP_GID }}

    - name: Load cached Docker images
      if: steps.docker-images-cache.outputs.cache-hit == 'true'
      shell: bash
      run: docker load -i /tmp/docker-images-cache/images.tar

    - name: Login to Docker Hub
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: docker/login-action@v4
      with:
        username: ${{ env.DOCKERHUB_USERNAME }}
        password: ${{ env.DOCKERHUB_TOKEN }}

    - name: Setup Docker Buildx
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: docker/setup-buildx-action@v4

    - name: Docker Buildx Bake
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      uses: docker/bake-action@v7
      with:
        load: true
        set: |
          *.cache-to=type=gha,mode=max                                                                                
          *.cache-from=type=gha 

    - name: Pull images
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      shell: bash
      run: docker compose pull --ignore-pull-failures || true

    - name: Save Docker images archive cache
      if: steps.docker-images-cache.outputs.cache-hit != 'true'
      shell: bash
      run: |
        mkdir -p /tmp/docker-images-cache
        docker save $(docker compose config --images) -o /tmp/docker-images-cache/images.tar

    - name: Start services
      shell: bash
      run: docker compose --verbose up --wait

    - name: Composer install
      shell: bash
      run: docker compose exec -T php composer install --prefer-dist --no-progress --no-interaction
```

Note the two intentional behavior changes beyond the added cache steps:
1. `docker compose up --build --wait` loses `--build` (now just `--wait`). The images are
   already present locally with the right tags either from the cache load or from the
   bake+pull steps that just ran — keeping `--build` risked a redundant non-buildx
   rebuild on the cache-hit path that would ignore the loaded archive.
2. `Login to Docker Hub`, `Setup Docker Buildx`, `Docker Buildx Bake`, and `Pull images`
   all gain `if: steps.docker-images-cache.outputs.cache-hit != 'true'` — skipped
   entirely when the archive cache hits.

- [ ] **Step 3: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/actions/docker-compose/action.yml'))" && echo OK`
Expected: `OK`

- [ ] **Step 4: Validate the cache key's `hashFiles` globs actually match real files**

Run: `ls .docker/php/Dockerfile .docker/php/conf.d/ .docker/moco/Dockerfile docker-compose.yaml`
Expected: all 4 paths exist (three files plus the `conf.d` directory listing — confirms the glob patterns in the cache key aren't typos that would silently hash to nothing).

- [ ] **Step 5: Commit**

```bash
git add .github/actions/docker-compose/action.yml
git commit -m "ci: cache Docker Compose images as an archive to skip rebuilds across parallel jobs"
```

---

### Task 3: Push and verify on a real CI run

**Files:** none (verification only).

**Interfaces:** none.

- [ ] **Step 1: Check in with the user before pushing**

This pushes a branch (and likely opens a PR) — confirm with the user first, per the
project's normal workflow for pushing/PR creation. Ask which branch name they want if
they haven't already specified one.

- [ ] **Step 2: Push the branch**

```bash
git push -u origin HEAD
```

- [ ] **Step 3: Open a PR (if the user wants one) and watch the run**

```bash
gh pr create --title "ci: split tests into parallel jobs, cache Docker images" --fill
gh run watch
```

Expected: 4 separate check runs appear (`Unit Tests`, `Integration Tests`,
`Measures (Coverage & Mutation Testing)`, `Security`) instead of the single `All tests`
check, all passing with the same results the current `allin` job would have produced.

- [ ] **Step 4: Push a second, no-op commit (e.g. `git commit --allow-empty -m "ci: retrigger"`) and watch again**

Expected: in the Actions log for the `Prepare` step of each of the 4 jobs, the
`Restore Docker images archive cache` step reports a cache hit, and the
`Login to Docker Hub` / `Setup Docker Buildx` / `Docker Buildx Bake` / `Pull images`
steps are skipped (shown greyed out / skipped in the GitHub UI).
