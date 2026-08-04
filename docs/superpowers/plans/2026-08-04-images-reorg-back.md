# Images Reorg — pokenini-back URL Updates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Update `pokenini-back`'s image URL env vars (`DEX_BANNER_URL`, `POKEMON_ICON_URL`, `POKEMON_IMAGE_URL`) to match `pokenini-icon`'s reorganized `images/` layout (`banner/large/`, `pokemon/big/`, `pokemon/small/`).

**Architecture:** Config-only change. These three vars only exist in `.env.dev.local` and `.env.prod` in this repo; no PHP code references them directly (they flow through to `pokenini-web`'s Twig globals, updated separately).

**Tech Stack:** Symfony 8 env files (`.env*`).

## Global Constraints

- This repo is independent from `pokenini-icon` and `pokenini-web` — coordinate deploy ordering yourself: production points at `icon.pokenini.fr` (served by `pokenini-resources`), so this change should not reach production before `pokenini-resources` has re-synced from the reorganized `pokenini-icon` `images/`. See `docs/superpowers/specs/2026-08-04-images-reorg-design.md` in `pokenini-icon` for the full cross-repo design.
- No automated test exercises these vars directly in this repo — a post-edit `grep` sanity check is the verification step, not a test run.

---

### Task 1: Update `.env.dev.local` and `.env.prod`

**Files:**
- Modify: `.env.dev.local`
- Modify: `.env.prod`

**Interfaces:** none — config-only, no code consumes these paths in this repo beyond the env vars themselves.

- [ ] **Step 1: Update `.env.dev.local` (line 29-31)**

Change:
```
DEX_BANNER_URL='http://localhost:8083/banner/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='http://localhost:8083/banner/large/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 2: Update `.env.prod` (line 36-38)**

Change:
```
DEX_BANNER_URL='http://localhost:8083/banner/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/big/%1$s/%2$s.png'
```
to:
```
DEX_BANNER_URL='http://localhost:8083/banner/large/%1$s.png'
POKEMON_ICON_URL='http://localhost:8083/pokemon/small/%1$s/%2$s.png'
POKEMON_IMAGE_URL='http://localhost:8083/pokemon/big/%1$s/%2$s.png'
```

- [ ] **Step 3: Verify no old-path references remain**

Run: `grep -n "DEX_BANNER_URL\|POKEMON_ICON_URL\|POKEMON_IMAGE_URL" .env.dev.local .env.prod`
Expected: all three lines in both files show `/banner/large/`, `/pokemon/small/`, `/pokemon/big/`.

- [ ] **Step 4: Commit**

```bash
git add .env.dev.local .env.prod
git commit -m "$(cat <<'EOF'
Update image URL env vars for pokenini-icon's images/ reorg

EOF
)"
```
