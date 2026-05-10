# Inventaire des dépendances — Pokenini Back

## Composer — dépendances runtime

| Dépendance | Version actuelle | Action recommandée |
|-----------|-----------------|-------------------|
| php | `>=8.5.6` | Suivre les patches 8.5.x |
| guzzlehttp/guzzle | `^7.10.0` | Vérifier `composer outdated` |
| knpuniversity/oauth2-client-bundle | `^2.20.2` | Vérifier si 3.x disponible |
| league/oauth2-google | `^5.0.0` | Vérifier `composer outdated` |
| phpdocumentor/reflection-docblock | `^6.0.3` | Vérifier `composer outdated` |
| phpstan/phpdoc-parser | `^2.3.2` | Aligné sur phpstan tool |
| symfony/asset | `8.0.*` | Suivre les patches 8.0.x |
| symfony/console | `8.0.*` | Suivre les patches 8.0.x |
| symfony/dotenv | `8.0.*` | Suivre les patches 8.0.x |
| symfony/expression-language | `8.0.*` | Suivre les patches 8.0.x |
| symfony/flex | `^2.10.0` | Vérifier `composer outdated` |
| symfony/framework-bundle | `8.0.*` | Suivre les patches 8.0.x |
| symfony/http-client | `8.0.*` | Suivre les patches 8.0.x |
| symfony/http-foundation | `8.0.*` | Suivre les patches 8.0.x |
| symfony/monolog-bundle | `^4.0.2` | Vérifier `composer outdated` |
| symfony/options-resolver | `8.0.*` | Suivre les patches 8.0.x |
| symfony/property-access | `8.0.*` | Suivre les patches 8.0.x |
| symfony/property-info | `8.0.*` | Suivre les patches 8.0.x |
| symfony/runtime | `8.0.*` | Suivre les patches 8.0.x |
| symfony/security-bundle | `8.0.*` | Suivre les patches 8.0.x |
| symfony/serializer | `8.0.*` | Suivre les patches 8.0.x |
| symfony/validator | `8.0.*` | Suivre les patches 8.0.x |
| symfony/yaml | `8.0.*` | Suivre les patches 8.0.x |
| wohali/oauth2-discord-new | `^1.2.1` | Vérifier `composer outdated` |

## Composer — dépendances dev

| Dépendance | Version actuelle | Action recommandée |
|-----------|-----------------|-------------------|
| phpunit/phpunit | `^13.1.8` | Vérifier `composer outdated` |
| symfony/browser-kit | `8.0.*` | Suivre les patches 8.0.x |
| symfony/css-selector | `8.0.*` | Suivre les patches 8.0.x |
| symfony/debug-bundle | `8.0.*` | Suivre les patches 8.0.x |
| symfony/phpunit-bridge | `8.0.*` | Suivre les patches 8.0.x |
| symfony/stopwatch | `8.0.*` | Suivre les patches 8.0.x |
| symfony/var-dumper | `8.0.*` | Suivre les patches 8.0.x |

---

## Outils qualité (`tools/*/composer.json`)

| Outil | Dépendance | Version actuelle | Action recommandée |
|-------|-----------|-----------------|-------------------|
| deptrac | deptrac/deptrac | `^4.6.0` | Vérifier `composer outdated -d tools/deptrac` |
| phpstan | phpstan/phpstan | `^2.1.54` | Vérifier `composer outdated -d tools/phpstan` |
| phpstan | phpstan/phpstan-symfony | `^2.0.15` | Vérifier avec phpstan |
| phpstan | phpstan/phpstan-phpunit | `^2.0.16` | Vérifier avec phpstan |
| phpmd | phpmd/phpmd | `^2.15` | Vérifier `composer outdated -d tools/phpmd` |
| psalm | vimeo/psalm | `6.16.1` **(épinglé exactement)** | **Passer à `^6.16.1` pour récupérer les patches** |
| psalm | psalm/plugin-symfony | `^5.3.0` | Vérifier `composer outdated -d tools/psalm` |
| psalm | psalm/plugin-phpunit | `^0.19.7` | Vérifier `composer outdated -d tools/psalm` |
| infection | infection/infection | `^0.32.7` | Vérifier `composer outdated -d tools/infection` |
| jsonlint | seld/jsonlint | `^1.11` | Vérifier `composer outdated -d tools/jsonlint` |
| php-cs-fixer | friendsofphp/php-cs-fixer | voir `tools/php-cs-fixer/composer.json` | Vérifier `composer outdated -d tools/php-cs-fixer` |

---

## Infrastructure Docker

| Service | Image | Version actuelle | Action recommandée |
|---------|-------|-----------------|-------------------|
| php (base) | `php` (Alpine) | `8.5.6-fpm-alpine3.23` | Suivre les patches PHP 8.5.x |
| php (symfony-cli) | `ghcr.io/symfony-cli/symfony-cli` | `5.17.1` | Vérifier les nouvelles versions |
| web | `nginx` (Alpine) | `1.29.8-alpine3.23` | Suivre les nouvelles versions nginx |
| redis | `redis` (Alpine) | `8.6.2-alpine3.23` | Suivre les nouvelles versions Redis |
| moco (api + oauth2) | Custom Moco | `1.5.0` (build arg dans `.docker/moco/Dockerfile`) | Vérifier les nouvelles versions Moco |

---

## CI / GitHub Actions

| Fichier | Action | Version actuelle | Action recommandée |
|---------|--------|-----------------|-------------------|
| `ci_codequality.yml` | `actions/checkout` | `v6` | Stable |
| `ci_tests.yml` | `actions/checkout` | `v6` | Stable |
| `security.yml` | `actions/checkout` | `v5` | **Mettre à jour vers `v6`** (incohérence avec les autres workflows) |

---

## Outils non gérés par Composer

| Outil | Version actuelle | Source | Action recommandée |
|-------|-----------------|--------|-------------------|
| cachetool | `9.2.1` | Téléchargement GitHub (hardcodé `Makefile:360`) | Surveiller les nouvelles releases |
| dclint (Docker Compose) | `3.1.0-alpine` | Image Docker (hardcodé `Makefile:13`) | Surveiller les nouvelles versions |
| dotenv-linter | `4.0.0` | Image Docker (hardcodé `Makefile:14`) | Surveiller les nouvelles versions |
| hadolint | `v2.14.0-alpine` | Image Docker (hardcodé `Makefile:15`) | Surveiller les nouvelles versions |
| editorconfig-checker | `v3.6.0` | Image Docker (hardcodé `Makefile:16`) | Surveiller les nouvelles versions |

---

## Variables d'environnement

| Variable | Valeur par défaut (dev) | Commentaire sécurité/prod |
|----------|------------------------|--------------------------|
| `APP_ENV` | `dev` | `prod` en production |
| `APP_SECRET` | `!ChangeMe!` (prod) | **Remplacer impérativement — secret fort requis** |
| `SESSION_TTL` | `604800` (7 jours) | Durée de session en secondes |
| `TRUSTED_HOSTS` | `^localhost\|127.0.0.1\|web$` | Adapter au domaine prod |
| `TRUSTED_PROXIES` | `127.0.0.1` | Adapter si reverse proxy différent |
| `API_URL` | `http://moco.api` (dev) | URL réelle de pokenini-api en prod |
| `API_LOGIN` | `web` | Identifiant HTTP Basic vers l'API |
| `API_PASSWORD` | `douze` | **Mot de passe faible — renforcer en prod** |
| `API_CAFILE_PATH` | `./resources/certificates/cacert.pem` | CA cert pour les appels HTTPS |
| `LIST_ADMIN` | IDs numériques OAuth | Gérer via secret CI/CD, non via commit |
| `LIST_COLLECTOR` | IDs numériques OAuth | Gérer via secret CI/CD |
| `LIST_TRAINER` | IDs numériques OAuth | Gérer via secret CI/CD |
| `REQUIRE_INVITATION` | `false` | Si `true`, seuls les IDs de `LIST_TRAINER` sont autorisés |
| `OAUTH_DISCORD_CLIENT_ID` | ID d'app Discord | Public, OK dans le repo |
| `OAUTH_DISCORD_CLIENT_SECRET` | Secret Discord | **Secret — ne jamais commiter en prod** |
| `OAUTH_GOOGLE_CLIENT_ID` | ID d'app Google | Public, OK dans le repo |
| `OAUTH_GOOGLE_CLIENT_SECRET` | Secret Google | **Secret — ne jamais commiter en prod** |
| `DEX_BANNER_URL` | URL avec `%1$s` | Template `sprintf` pour les bannières |
| `POKEMON_ICON_URL` | URL avec `%1$s/%2$s` | Template `sprintf` |
| `POKEMON_IMAGE_URL` | URL avec `%1$s/%2$s` | Template `sprintf` |
| `POKEMON_TYPE_ICON_URL` | URL avec `%1$s` | Template `sprintf` |
| `ELECTION_CANDIDATE_COUNT` | `12` | Nombre de candidats par round d'élection |
| `ELECTION_TOP_COUNT` | `5` | Nombre de Pokémon dans le top élection |
| `MATOMO_SITE_ID` | `3` (dev) / `1` (prod) | ID site Matomo analytics |
| `MATOMO_URL` | `http://moco.matomo.gbl` (dev) | URL instance Matomo |
| `CORS_ALLOW_ORIGIN` | Regex localhost | Restreindre au domaine frontend en prod |

---

## Recommandations globales

1. **Exécuter `make updates` régulièrement** — met à jour tous les `composer.json` (projet + outils) en une commande.
2. **Supprimer les secrets de `.env.prod`** — les secrets OAuth (`OAUTH_*_CLIENT_SECRET`) et `APP_SECRET` doivent être injectés via secrets GitHub Actions, jamais committés.
3. **Audit de sécurité élargi** — étendre `make security` pour auditer les dépendances des outils qualité (`tools/*/`).
4. **Homogénéiser `actions/checkout`** — mettre `security.yml` à jour de `v5` vers `v6`.
5. **Désépingler Psalm** — passer `vimeo/psalm` de `6.16.1` à `^6.16.1` dans `tools/psalm/composer.json`.
6. **Mettre à jour le ruleset PHP CS Fixer** — passer de `@PHP83Migration` à `@PHP84Migration` dans `.php-cs-fixer.dist.php`.

---

## Commandes de vérification

```bash
# Dépendances Composer principale
docker compose exec php composer outdated

# Dépendances des outils qualité
for tool in tools/*/; do \
  echo "=== $tool ===" && \
  docker compose exec php composer outdated --working-dir=/app/$tool; \
done

# Audit de sécurité (projet principal)
make security

# Audit de sécurité (outils qualité)
for tool in tools/deptrac tools/phpstan tools/phpmd tools/psalm tools/infection tools/php-cs-fixer tools/jsonlint; do \
  echo "=== $tool ===" && \
  docker compose exec php composer audit --working-dir=/app/$tool; \
done

# Vérifier les images Docker disponibles
docker compose pull --dry-run
```
