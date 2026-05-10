# Inventaire des dépendances — pokenini-back

## Composer — dépendances runtime

| Dépendance | Version contrainte | Action recommandée |
|-----------|------------------|-------------------|
| `php` | `>=8.5.6` | OK (8.5.6 en prod Docker) |
| `guzzlehttp/guzzle` | `^7.10.0` | Surveiller — Guzzle 8 non encore stable |
| `knpuniversity/oauth2-client-bundle` | `^2.20.2` | OK |
| `league/oauth2-google` | `^5.0.0` | OK |
| `phpdocumentor/reflection-docblock` | `^6.0.3` | OK (dépendance transitoire de Symfony) |
| `phpstan/phpdoc-parser` | `^2.3.2` | OK |
| `symfony/asset` | `8.0.*` | OK |
| `symfony/console` | `8.0.*` | OK |
| `symfony/dotenv` | `8.0.*` | OK |
| `symfony/expression-language` | `8.0.*` | OK |
| `symfony/flex` | `^2.10.0` | OK |
| `symfony/framework-bundle` | `8.0.*` | OK |
| `symfony/http-client` | `8.0.*` | OK |
| `symfony/http-foundation` | `8.0.*` | OK |
| `symfony/monolog-bundle` | `^4.0.2` | OK |
| `symfony/options-resolver` | `8.0.*` | OK |
| `symfony/property-access` | `8.0.*` | OK |
| `symfony/property-info` | `8.0.*` | OK |
| `symfony/runtime` | `8.0.*` | OK |
| `symfony/security-bundle` | `8.0.*` | OK |
| `symfony/serializer` | `8.0.*` | OK |
| `symfony/validator` | `8.0.*` | OK |
| `symfony/yaml` | `8.0.*` | OK |
| `wohali/oauth2-discord-new` | `^1.2.1` | Surveiller — maintenance communautaire |

## Composer — dépendances dev

| Dépendance | Version contrainte | Action recommandée |
|-----------|------------------|-------------------|
| `phpunit/phpunit` | `^13.1.8` | OK |
| `symfony/browser-kit` | `8.0.*` | OK |
| `symfony/css-selector` | `8.0.*` | OK |
| `symfony/debug-bundle` | `8.0.*` | OK |
| `symfony/phpunit-bridge` | `8.0.*` | OK |
| `symfony/stopwatch` | `8.0.*` | OK |
| `symfony/var-dumper` | `8.0.*` | OK |

## Outils qualité (`tools/*/composer.json`)

| Outil | Dépendance | Version contrainte | Action recommandée |
|-------|-----------|------------------|-------------------|
| `deptrac` | `deptrac/deptrac` | `^4.6.0` | OK |
| `infection` | `infection/infection` | `^0.32.7` | OK |
| `jsonlint` | `seld/jsonlint` | `^1.11` | OK |
| `php-cs-fixer` | `friendsofphp/php-cs-fixer` | `^3.95.1` | OK |
| `phpmd` | `phpmd/phpmd` | `^2.15` | OK |
| `phpstan` | `phpstan/phpstan` | `^2.1.54` | OK |
| `phpstan` | `phpstan/phpstan-symfony` | `^2.0.15` | OK |
| `phpstan` | `phpstan/phpstan-phpunit` | `^2.0.16` | OK |
| `psalm` | `vimeo/psalm` | `6.16.1` | OK — version épinglée exacte |
| `psalm` | `psalm/plugin-symfony` | `^5.3.0` | OK |
| `psalm` | `psalm/plugin-phpunit` | `^0.19.7` | OK |

## Docker / Infrastructure

| Service | Image | Version actuelle | Action recommandée |
|---------|-------|-----------------|-------------------|
| PHP | `php:8.5.5-fpm-alpine3.23` | 8.5.5 | OK — correspond à la contrainte composer |
| Nginx | `nginx:1.29.8-alpine3.23` | 1.29.8 | Surveiller les releases de sécurité |
| Redis | `redis:8.6.2-alpine3.23` | 8.6.2 | OK — mais non utilisé par le cache app |
| Moco (mock) | Image custom | 1.5.0 | Surveiller les nouvelles versions |
| Symfony CLI | `ghcr.io/symfony-cli/symfony-cli:5.17.1` | 5.17.1 | OK |

## CI / GitHub Actions

| Action | Version | Action recommandée |
|--------|---------|-------------------|
| `actions/checkout` | `v6` | OK |
| `.github/actions/docker-compose` | custom | OK |
| `.github/actions/tools-composer` | custom | OK |

## Variables d'environnement

| Variable | Valeur par défaut (prod) | Commentaire sécurité/prod |
|----------|------------------------|--------------------------|
| `APP_SECRET` | `!ChangeMe!` | **Critique** — doit être remplacé par une valeur aléatoire forte avant déploiement |
| `API_URL` | `http://moco.api` | **Critique** — pointe vers le mock Moco, doit être l'URL réelle de pokenini-api |
| `API_PASSWORD` | `douze` | Credential en clair dans git — injecter via secret manager |
| `OAUTH_GOOGLE_CLIENT_SECRET` | valeur visible | Injecter via secret manager, retirer de `.env.prod` |
| `OAUTH_DISCORD_CLIENT_SECRET` | valeur visible | Injecter via secret manager, retirer de `.env.prod` |
| `REDIS_URL` | non définie | Ajouter pour configurer Redis comme cache adapter en prod |
| `LIST_ADMIN` | IDs OAuth hardcodés | OK — piloté par env, pas de DB |
| `REQUIRE_INVITATION` | `false` | Mettre à `true` en prod si accès sur invitation uniquement |

## Recommandations globales

1. **Configurer Redis comme adaptateur de cache en production** : Redis est dans le stack mais non branché à Symfony Cache. Ajouter `when@prod` dans `cache.yaml`.
2. **Retirer `APP_SECRET=!ChangeMe!` et `API_URL=http://moco.api` de `.env.prod`** : ces valeurs par défaut dangereuses doivent être injectées via CI/CD secrets.
3. **Déplacer les secrets OAuth dans des variables d'env injectées** : les client secrets Google/Discord dans `.env.prod` présentent un risque si le dépôt est compromis.
4. **Épingler la version de `vimeo/psalm`** : `6.16.1` est déjà exact, mais le reste des outils utilise `^x.y.z` — surveillance via Dependabot (`.github/dependabot.yml` présent).
5. **Surveiller `wohali/oauth2-discord-new`** : package communautaire peu maintenu, vérifier s'il existe une alternative officielle.

## Commandes de vérification

```bash
# Dépendances Composer runtime
composer outdated

# Audit de sécurité
composer audit

# Outils qualité
for tool in tools/*/; do
  echo "=== $tool ===" && composer outdated -d "$tool"
done
```
