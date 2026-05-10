# Inventaire des dépendances — Pokenini Back

## Composer — dépendances runtime

Versions extraites du `composer.lock` (versions réellement installées).

| Dépendance | Version installée | Contrainte `composer.json` | Action recommandée |
|-----------|------------------|---------------------------|-------------------|
| php | `8.5.6` | `>=8.5.6` | Suivre les patches 8.5.x |
| guzzlehttp/guzzle | `7.10.0` | `^7.10.0` | À jour |
| knpuniversity/oauth2-client-bundle | `v2.20.2` | `^2.20.2` | Vérifier si 3.x disponible |
| league/oauth2-client | `2.9.0` | (transitive) | — |
| league/oauth2-google | `5.0.0` | `^5.0.0` | À jour |
| monolog/monolog | `3.10.0` | (via monolog-bundle) | — |
| phpdocumentor/reflection-docblock | `6.0.3` | `^6.0.3` | À jour |
| phpstan/phpdoc-parser | `2.3.2` | `^2.3.2` | À jour |
| symfony/asset | `v8.0.8` | `8.0.*` | À jour (patch 8.0) |
| symfony/cache | `v8.0.10` | (transitive) | — |
| symfony/console | `v8.0.9` | `8.0.*` | À jour |
| symfony/dotenv | `v8.0.9` | `8.0.*` | À jour |
| symfony/expression-language | `v8.0.8` | `8.0.*` | À jour |
| symfony/flex | `v2.10.0` | `^2.10.0` | À jour |
| symfony/framework-bundle | `v8.0.10` | `8.0.*` | À jour |
| symfony/http-client | `v8.0.9` | `8.0.*` | À jour |
| symfony/http-foundation | `v8.0.8` | `8.0.*` | À jour |
| symfony/http-kernel | `v8.0.10` | (transitive) | — |
| symfony/monolog-bundle | `v4.0.2` | `^4.0.2` | À jour |
| symfony/options-resolver | `v8.0.8` | `8.0.*` | À jour |
| symfony/property-access | `v8.0.8` | `8.0.*` | À jour |
| symfony/property-info | `v8.0.8` | `8.0.*` | À jour |
| symfony/routing | `v8.0.9` | (transitive) | — |
| symfony/runtime | `v8.0.8` | `8.0.*` | À jour |
| symfony/security-bundle | `v8.0.8` | `8.0.*` | À jour |
| symfony/security-core | `v8.0.8` | (transitive) | — |
| symfony/security-http | `v8.0.9` | (transitive) | — |
| symfony/serializer | `v8.0.10` | `8.0.*` | À jour |
| symfony/validator | `v8.0.10` | `8.0.*` | À jour |
| symfony/yaml | `v8.0.10` | `8.0.*` | À jour |
| twig/twig | `v3.24.0` | (transitive) | — |
| wohali/oauth2-discord-new | `1.2.1` | `^1.2.1` | À jour |

## Composer — dépendances dev

| Dépendance | Version installée | Contrainte `composer.json` | Action recommandée |
|-----------|------------------|---------------------------|-------------------|
| phpunit/phpunit | `13.1.8` | `^13.1.8` | À jour |
| symfony/browser-kit | `v8.0.8` | `8.0.*` | À jour |
| symfony/css-selector | `v8.0.9` | `8.0.*` | À jour |
| symfony/debug-bundle | `v8.0.8` | `8.0.*` | À jour |
| symfony/phpunit-bridge | `v8.0.8` | `8.0.*` | À jour |
| symfony/stopwatch | `v8.0.8` | `8.0.*` | À jour |
| symfony/var-dumper | `v8.0.8` | `8.0.*` | À jour |

---

## Outils qualité (`tools/*/composer.json`)

Versions extraites des `composer.lock` de chaque outil.

| Outil | Dépendance principale | Version installée | Contrainte | Action recommandée |
|-------|----------------------|------------------|-----------|-------------------|
| `tools/deptrac` | deptrac/deptrac | `4.6.0` | `^4.6.0` | À jour |
| `tools/phpstan` | phpstan/phpstan | `2.1.54` | `^2.1.54` | À jour |
| `tools/phpstan` | phpstan/phpstan-symfony | `2.0.17` | `^2.0.15` | À jour |
| `tools/phpstan` | phpstan/phpstan-phpunit | `2.0.16` | `^2.0.16` | À jour |
| `tools/phpmd` | phpmd/phpmd | `2.15.0` | `^2.15` | À jour |
| `tools/psalm` | vimeo/psalm | `6.16.1` | `6.16.1` **(épinglé exactement)** | **Passer à `^6.16.1`** |
| `tools/psalm` | psalm/plugin-symfony | `v5.3.0` | `^5.3.0` | À jour |
| `tools/psalm` | psalm/plugin-phpunit | `0.19.7` | `^0.19.7` | À jour |
| `tools/infection` | infection/infection | `0.32.7` | `^0.32.7` | À jour |
| `tools/infection` | infection/abstract-testframework-adapter | `0.5.0` | (transitive) | — |
| `tools/jsonlint` | seld/jsonlint | `1.11.0` | `^1.11` | À jour |
| `tools/php-cs-fixer` | friendsofphp/php-cs-fixer | `v3.95.1` | (voir `tools/php-cs-fixer/composer.json`) | À jour |

---

## Infrastructure Docker

| Service | Image | Version actuelle | Action recommandée |
|---------|-------|-----------------|-------------------|
| `php` (base) | `php` Alpine | `8.5.6-fpm-alpine3.23` | Suivre les patches PHP 8.5.x |
| `php` (symfony-cli, dev only) | `ghcr.io/symfony-cli/symfony-cli` | `5.17.1` | Vérifier les nouvelles versions |
| `web` | `nginx` Alpine | `1.29.8-alpine3.23` | Suivre les nouvelles versions nginx |
| `redis` | `redis` Alpine | `8.6.2-alpine3.23` | Suivre les nouvelles versions Redis |
| `moco.api` / `moco.oauth2` | Custom Moco (build arg) | `1.5.0` | Vérifier les nouvelles versions Moco |

---

## CI / GitHub Actions

| Workflow | Action | Version | Action recommandée |
|---------|--------|---------|-------------------|
| `ci_codequality.yml` | `actions/checkout` | `v6` | À jour |
| `ci_infraquality.yml` | `actions/checkout` | `v6` | À jour |
| `ci_infraquality.yml` | `dotenv-linter/action-dotenv-linter` | `v3` | À jour |
| `ci_infraquality.yml` | `hadolint/hadolint-action` | `v3.1.0` | À jour |
| `ci_tests.yml` | `actions/checkout` | `v6` | À jour |
| `security.yml` | `actions/checkout` | `v5` | **Mettre à jour vers `v6`** (incohérence) |
| `push.yml` | `actions/checkout` | `v6` | À jour |
| `push.yml` | `docker/login-action` | `v4` | À jour |
| `push.yml` | `docker/metadata-action` | `v5` | À jour |
| `push.yml` | `docker/setup-qemu-action` | `v3` | À jour |
| `push.yml` | `docker/setup-buildx-action` | `v3` | À jour |
| `push.yml` | `docker/build-push-action` | `v6` | À jour |

---

## Outils non gérés par Composer

| Outil | Version | Source dans le projet | Action recommandée |
|-------|---------|----------------------|-------------------|
| cachetool | `9.2.1` | `Makefile:360` (curl GitHub releases) | Surveiller les nouvelles releases |
| dclint (Docker Compose linter) | `3.1.0-alpine` | `Makefile:13` (image Docker) | À jour (confirmé par `ci_infraquality.yml`) |
| dotenv-linter | `4.0.0` | `Makefile:14` (image Docker) | CI utilise action `v3`, Makefile local hors-sync |
| hadolint | `v2.14.0-alpine` | `Makefile:15` (image Docker) | CI utilise action `v3.1.0`, Makefile local hors-sync |
| editorconfig-checker | `v3.6.0` | `Makefile:16` (image Docker) | Vérifier la dernière version |

---

## Variables d'environnement

| Variable | Valeur par défaut (dev) | Commentaire sécurité/prod |
|----------|------------------------|--------------------------|
| `APP_ENV` | `dev` | `prod` en production |
| `APP_SECRET` | `!ChangeMe!` (`.env.prod`) | **Valeur fictive — injecter un secret fort via CI/CD** |
| `SESSION_TTL` | `604800` | Durée de session en secondes (7 jours) |
| `TRUSTED_HOSTS` | `^localhost\|127.0.0.1\|web$` | Adapter au domaine prod |
| `TRUSTED_PROXIES` | `127.0.0.1` | Adapter si reverse proxy différent |
| `API_URL` | `http://moco.api` (dev) | URL réelle de pokenini-api en prod |
| `API_LOGIN` | `web` | Identifiant HTTP Basic vers l'API externe |
| `API_PASSWORD` | `douze` | **Mot de passe faible — renforcer et injecter via secret** |
| `API_CAFILE_PATH` | `./resources/certificates/cacert.pem` | CA cert pour les appels HTTPS |
| `LIST_ADMIN` | IDs OAuth numériques | Gérer via secret CI/CD, non via commit |
| `LIST_COLLECTOR` | IDs OAuth numériques | Gérer via secret CI/CD |
| `LIST_TRAINER` | IDs OAuth numériques | Gérer via secret CI/CD |
| `REQUIRE_INVITATION` | `false` | Si `true`, seuls `LIST_TRAINER` peuvent s'authentifier |
| `OAUTH_DISCORD_CLIENT_ID` | `1684654646846543616` | Public, OK dans le repo |
| `OAUTH_DISCORD_CLIENT_SECRET` | valeur réelle dans `.env.prod` | **CRITIQUE — secret exposé, rotation immédiate** |
| `OAUTH_GOOGLE_CLIENT_ID` | ID d'app Google | Public, OK dans le repo |
| `OAUTH_GOOGLE_CLIENT_SECRET` | valeur réelle dans `.env.prod` | **CRITIQUE — secret exposé, rotation immédiate** |
| `DEX_BANNER_URL` | URL avec `%1$s` | Template `sprintf` pour les bannières |
| `POKEMON_ICON_URL` | URL avec `%1$s/%2$s` | Template `sprintf` |
| `POKEMON_IMAGE_URL` | URL avec `%1$s/%2$s` | Template `sprintf` |
| `POKEMON_TYPE_ICON_URL` | URL avec `%1$s` | Template `sprintf` |
| `ELECTION_CANDIDATE_COUNT` | `12` | Nombre de candidats par round |
| `ELECTION_TOP_COUNT` | `5` | Nombre de Pokémon dans le top élection |
| `MATOMO_SITE_ID` | `3` (dev) / `1` (prod) | ID site Matomo analytics |
| `MATOMO_URL` | `http://moco.matomo.gbl` (dev) | URL instance Matomo |
| `CORS_ALLOW_ORIGIN` | Regex localhost | Restreindre au domaine frontend en prod |
| `OAUTH_PASSAGE_SUB_DOMAIN` | `pokeninidev` | Sous-domaine pour le provider Passage |

---

## Recommandations globales

1. **Rotation et suppression des secrets committés** — `OAUTH_DISCORD_CLIENT_SECRET` et `OAUTH_GOOGLE_CLIENT_SECRET` sont présents en clair dans `.env.prod` (commité). Rotation immédiate des secrets côté Google et Discord, puis remplacement par des placeholders et injection via secrets GitHub Actions.
2. **Remplacer `APP_SECRET=!ChangeMe!`** — injecter un secret fort via CI/CD pour chaque environnement (`APP_SECRET` est critique pour la sécurité des sessions Symfony).
3. **Désépingler Psalm** — passer `vimeo/psalm` de `6.16.1` (version exacte) à `^6.16.1` dans `tools/psalm/composer.json` pour récupérer les patches automatiquement avec `make updates`.
4. **Homogénéiser `actions/checkout`** — mettre `security.yml` à jour de `v5` vers `v6` (tous les autres workflows utilisent déjà `v6`).
5. **Mettre à jour le ruleset PHP CS Fixer** — passer de `@PHP83Migration` à `@PHP84Migration` dans `.php-cs-fixer.dist.php` (le projet cible PHP 8.5.6).
6. **Exécuter `make updates` régulièrement** — met à jour tous les `composer.json` (projet + 7 outils) en une seule commande.
7. **Étendre l'audit sécurité aux outils qualité** — `make security` n'audite que le `composer.json` racine. Ajouter une target pour auditer `tools/*/`.

---

## Commandes de vérification

```bash
# Dépendances Composer — projet principal
docker compose exec php composer outdated

# Dépendances — tous les outils qualité
for tool in deptrac infection jsonlint php-cs-fixer phpmd phpstan psalm; do
  echo "=== tools/$tool ===" && \
  docker compose exec php composer outdated --working-dir=/app/tools/$tool
done

# Audit de sécurité — projet principal
make security

# Audit de sécurité — outils qualité
for tool in deptrac infection jsonlint php-cs-fixer phpmd phpstan psalm; do
  echo "=== tools/$tool ===" && \
  docker compose exec php composer audit --working-dir=/app/tools/$tool
done

# Vérifier les images Docker disponibles
docker compose pull --dry-run

# Lancer tous les outils qualité
make quality

# Mettre à jour toutes les dépendances Composer
make updates
```
