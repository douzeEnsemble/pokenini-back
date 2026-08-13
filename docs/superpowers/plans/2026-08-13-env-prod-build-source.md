# Replace `.env.prod` with `.env.dev` as Docker build source — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop shipping a fake-secrets `.env.prod` file whose only purpose is being renamed to `.env` during the Docker build; use the already-maintained `.env.dev` for that instead, and delete `.env.prod`.

**Architecture:** One-line change in the `php_prod` stage of `.docker/php/Dockerfile` (`mv .env.prod .env` → `mv .env.dev .env`), followed by removing `.env.prod` from the repo and correcting the doc mentions that assume it still exists (or that it was already removed once — it was, then reintroduced). No application code, no runtime behavior change — the file is still deleted before the image is finalized (`rm .env && touch .env`), and the container still receives real config from the deploying orchestration at runtime.

**Tech Stack:** Docker multi-stage build, Symfony 8 Dotenv component.

## Global Constraints

- Do not commit anything. Leave all changes in the working tree for the user to review and commit themselves. Skip every "Commit" step that would normally appear in this plan.
- No git history rewriting, no force-push, no destructive git commands.
- Verification is a `docker build`, not a PHPUnit run — there is no test suite step here.
- `.env.prod` was already deleted once in this exact repo (commit `06e0c95`, "Remove .env.prod") without a working replacement, and had to be reintroduced 13 days later (commit `601118f`, "Add missing .env.prod (use for image building)"). Do not repeat that mistake: Task 1 Step 3 (build verification) is not optional.

See `docs/superpowers/specs/2026-08-13-env-prod-build-source-design.md` for the full rationale.

---

### Task 1: Switch the `php_prod` build source and remove `.env.prod`

**Files:**
- Modify: `.docker/php/Dockerfile:86`
- Delete: `.env.prod`
- Modify: `.dockerignore` (`!.env.prod` → `!.env.dev`, required or the build fails)

**Interfaces:** None — this is a standalone infrastructure change with no code consumers.

- [ ] **Step 1: Edit the Dockerfile**

In `.docker/php/Dockerfile`, line 86, change:

```dockerfile
RUN mv .env.prod .env && chown www-data:www-data .env
```

to:

```dockerfile
RUN mv .env.dev .env && chown www-data:www-data .env
```

(`.env.dev` is already present in the build context via the preceding `COPY . ./`, so no other Dockerfile line needs to change.)

- [ ] **Step 2: Delete `.env.prod`**

Run: `rm .env.prod`

This leaves the deletion unstaged in the working tree — do not `git add` or `git rm` it; the user will handle staging/commit themselves.

Note: `.env.prod` in this repo carried 7 keys not present in `.env.dev` (`KERNEL_CLASS`, `MATOMO_SITE_ID`, `MATOMO_URL`, `OAUTH_PASSAGE_SUB_DOMAIN`, `PANTHER_APP_ENV`, `PANTHER_ERROR_SCREENSHOT_DIR`, `SYMFONY_DEPRECATIONS_HELPER`). None of them are referenced by an `%env(...)%` processor in `config/`, so their disappearance from the build-time `.env` does not affect container compilation. No action needed beyond deleting the file.

- [ ] **Step 3: Build the prod image and check the build log**

Run: `make img-build`

Expected: build succeeds. In the build log, find the `cache:clear` step (part of `composer run-script --no-dev post-install-cmd`) and confirm its output explicitly names the **"prod"** environment, e.g. a line containing `Cache for the "prod" environment`. This confirms Docker's `ENV APP_ENV=prod` (set earlier in the Dockerfile) still wins over the `APP_ENV=dev` line now present in the copied `.env.dev`, i.e. Symfony's Dotenv did not override the already-set environment variable.

If the log instead shows the "dev" environment, or the build fails while resolving an `%env(...)%` value, STOP — this would mean the override assumption in the design doc is wrong and the change needs rethinking; do not proceed to Step 4.

- [ ] **Step 4: Verify the final image ships an empty `.env`**

Run: `docker run --rm ghcr.io/douzeensemble/pokenini-back:latest cat .env`

Expected: empty output (0 bytes). This confirms the pre-existing `RUN rm .env && touch .env` cleanup step still runs after the copied `.env.dev` content was consumed for cache warmup, exactly as it did before with `.env.prod`.

---

### Task 2: Correct doc mentions of `.env.prod`

**Files:**
- Modify: `doc/security.md:68`
- Modify: `doc/security.md:164`
- Modify: `doc/update.md:127`
- Modify: `doc/update.md:140`
- Modify: `doc/update.md:142`
- Modify: `doc/fix.md:7`

**Interfaces:** None — documentation-only edits.

- [ ] **Step 1: Fix the stale "already removed" claim in `doc/security.md`**

Line 68 currently reads:

```markdown
- [x] `.env.prod` supprimé du dépôt (commit `06e0c95`) — secrets OAuth prod ne sont plus committés.
```

This is inaccurate: `.env.prod` was reintroduced 13 days later in commit `601118f` and has existed ever since, until this plan's Task 1. Change it to:

```markdown
- [x] `.env.prod` supprimé du dépôt (2026-08-13) — le build de l'image de prod copie désormais `.env.dev` (déjà factice, déjà maintenu) pour compiler le container Symfony ; les valeurs sont jetées avant la fin du build, comme avant. Voir `docs/superpowers/specs/2026-08-13-env-prod-build-source-design.md`.
```

- [ ] **Step 2: Fix the "Haute priorité" recommendation note in `doc/security.md`**

Line 164 currently reads:

```markdown
— Aucune. Les items critiques ont été traités (suppression `.env.prod`, rate limiting, en-têtes HTTP).
```

This line's parenthetical is fine as prose (it doesn't claim a specific commit), but it now correctly refers to the actual final removal. Leave the wording as-is — no change needed here since it doesn't reference a specific (wrong) commit and the claim "`.env.prod` supprimé" is true again after Task 1.

- [ ] **Step 3: Update the `APP_SECRET` row in `doc/update.md`**

Line 127 currently reads:

```markdown
| `APP_SECRET` | `!ChangeMe!` (`.env.prod`) | **Valeur fictive — injecter un secret fort via CI/CD** |
```

Change it to:

```markdown
| `APP_SECRET` | `!ChangeMe!` (`.env.dev`, copié en `.env` au build prod) | **Valeur fictive — injecter un secret fort via CI/CD** |
```

- [ ] **Step 4: Update the OAuth secret rows in `doc/update.md`**

Lines 140 and 142 currently read:

```markdown
| `OAUTH_DISCORD_CLIENT_SECRET` | `.env.dev/.env.ci/.env.int` (apps dev distinctes) | Apps de développement distinctes de la prod — intentionnel. `.env.prod` supprimé. |
| `OAUTH_GOOGLE_CLIENT_SECRET` | `.env.dev/.env.ci/.env.int` (apps dev distinctes) | Apps de développement distinctes de la prod — intentionnel. `.env.prod` supprimé. |
```

Change the trailing sentence on both lines from `` `.env.prod` supprimé. `` to `` `.env.prod` n'existe plus — le build de prod copie `.env.dev`. `` (keep the rest of each line identical).

- [ ] **Step 5: Update `doc/fix.md`**

Line 7 currently reads:

```markdown
- **Credentials OAuth dans les `.env`** : les valeurs de `OAUTH_DISCORD_CLIENT_SECRET` et `OAUTH_GOOGLE_CLIENT_SECRET` ressemblent intentionnellement à des credentials réels (format `GOCSPX-`…) mais n'en sont pas — choix délibéré pour tester des formats valides en dev/test. Tous les fichiers commit (`.env`, `.env.ci`, `.env.dev`, `.env.int`, `.env.prod`, `.env.test`) contiennent les mêmes valeurs factices ; les vrais secrets de production sont injectés au runtime via variables d'environnement Clever Cloud.
```

Change the file list from `` (`.env`, `.env.ci`, `.env.dev`, `.env.int`, `.env.prod`, `.env.test`) `` to `` (`.env`, `.env.ci`, `.env.dev`, `.env.int`, `.env.test`) `` — `.env.prod` no longer exists; the build now derives its placeholder `.env` from `.env.dev`. Leave the rest of the sentence unchanged.

- [ ] **Step 6: Leave changes uncommitted**

Do not run `git add` or `git commit`. Run `git status` to show the user the pending changes (modified `Dockerfile`, deleted `.env.prod`, modified `doc/security.md`, `doc/update.md`, `doc/fix.md`) and stop here.
