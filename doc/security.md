# Sécurité — Pokenini Back

## Posture générale

L'application est une API JSON exposée à un frontend web. Le niveau de sécurité est globalement solide (OAuth2 multi-provider, contrôle d'accès par rôle, cache isolé par dresseur), avec un point critique : des secrets de production réels sont committés dans le dépôt.

---

## Authentification & Autorisation

### Mécanisme en place

Deux modes coexistent selon le contexte :

**1. OAuth2 redirect (login web)** — `GoogleAuthenticator`, `DiscordAuthenticator` via `knpuniversity/oauth2-client-bundle`. L'utilisateur est redirigé vers le provider OAuth2, revient avec un code, et reçoit une session.

**2. Bearer token (API)** — `AccessTokenHandler` valide un token Bearer + header `X-Provider`. Utilisé par le frontend pour les appels API après login. La validation du token se fait via `ClientRegistry::getClient($provider)->fetchUserFromToken()`.

**3. FakeAuthenticator (dev uniquement)** — `/fr/connect/f/c?t=admin|collector|trainer` crée une session sans secret. Non accessible en prod.

**4. MockAuthenticator (tests uniquement)** — token `Bearer this-is-{role}-token` + `X-Provider: mock`.

Les rôles sont définis par des listes d'IDs dans les variables d'env (`LIST_ADMIN`, `LIST_COLLECTOR`, `LIST_TRAINER`). L'attribution se fait dans `AccessTokenHandler::loadUserFromLists()`.

Référence : `config/packages/security.yaml`, `src/Security/AccessTokenHandler.php`

### Points de vigilance

- [ ] Aucun mécanisme de révocation de token — si un token OAuth est compromis, il reste valide jusqu'à expiration. Le provider externe gère la durée de vie.
- [ ] `REQUIRE_INVITATION=false` en prod (`.env.prod:26`) — tout utilisateur authentifié Google/Discord reçoit le rôle `ROLE_USER` sans invitation. À documenter comme choix intentionnel.
- [ ] Le header `X-Provider` est un paramètre libre transmis par le client — `AccessTokenHandler` fait confiance à ce header pour choisir le provider OAuth2. Un attaquant contrôlant les deux headers (`Authorization` + `X-Provider`) pourrait tenter une authentification croisée si les providers partagent des IDs utilisateur. Vérifier que les tokens des différents providers ne sont pas interchangeables.

---

## Validation des entrées & Injection

### État actuel

- **DTOs** : `OptionsResolver` avec types stricts et normaliseurs — validation exhaustive à la construction (`src/DTO/DexFilters.php:26`)
- **CatchStates** : contrainte Symfony `CatchStates` + `CatchStatesValidator` validant les états de capture autorisés
- **AlbumFilters** : whitelist explicite des paramètres de requête autorisés (`src/AlbumFilters/FromRequest.php:11-24`)
- **Pas de base de données** : aucun risque d'injection SQL

### Points de vigilance

- [ ] Les filtres de `AlbumFilters\FromRequest` sont transmis sans transformation à l'API externe comme paramètres de requête (`src/Service/Api/GetPokedexApiService.php:37`). Si l'API externe est vulnérable à des injections de paramètres, ce BFF amplifie le vecteur. Vérifier que pokenini-api valide ses entrées.
- [ ] `JsonDecoder::decode()` utilise une profondeur de 5 — `src/Utils/JsonDecoder.php:11`. Suffisant pour les structures actuelles, mais à surveiller si des structures imbriquées sont ajoutées.

---

## Données sensibles & Secrets

### Variables d'environnement sensibles

| Variable | Exposition | Recommandation |
|----------|-----------|----------------|
| `APP_SECRET` | `.env.prod` commité (`!ChangeMe!`) | **Injecter via secret CI/CD — valeur actuelle invalide en prod** |
| `OAUTH_GOOGLE_CLIENT_SECRET` | `.env.prod` commité (valeur réelle) | **Rotation immédiate + injection via secret CI/CD** |
| `OAUTH_DISCORD_CLIENT_SECRET` | `.env.prod` commité (valeur réelle) | **Rotation immédiate + injection via secret CI/CD** |
| `API_PASSWORD` | `.env.prod` commité (`douze`) | Mot de passe faible — renforcer et injecter via secret |
| `REDIS_PASSWORD` | `docker-compose.yaml:35` hardcodé (`douze`) | Passer via `${REDIS_PASSWORD}` en variable d'env |
| `LIST_ADMIN` | `.env.prod` commité (IDs numériques) | Acceptable si les IDs OAuth ne sont pas considérés secrets |

### Points de vigilance

- [x] **CRITIQUE** `.env.prod` supprimé — secrets OAuth et `APP_SECRET` prod plus committés (commit `06e0c95`).
- [x] `.env.dev` contient des credentials OAuth d'apps de développement distinctes de la prod — choix intentionnel documenté.
- [ ] `.env.dev.local` est dans `.gitignore` (non commité) — bon. Vérifier que les secrets prod ne transitent jamais par ce fichier en dev.

---

## Sécurité HTTP

### En-têtes de sécurité
Non configurés au niveau applicatif Symfony. Les en-têtes de sécurité (CSP, HSTS, X-Frame-Options, X-Content-Type-Options) dépendent de la configuration Nginx (`config/packages/` ne contient pas `nelmio/security-bundle`).

### CSRF
Non applicable — API JSON stateless. Le frontend consomme l'API avec un Bearer token, pas de formulaire HTML.

### CORS
Configuré via `CORS_ALLOW_ORIGIN` en variable d'env. En dev/test : `localhost|apache|127.0.0.1`. En prod : à vérifier que `CORS_ALLOW_ORIGIN` est correctement restreint au domaine du frontend.

### Rate limiting

- [ ] **Aucun rate limiting configuré** — ni au niveau Symfony, ni visible dans la config Nginx. Les endpoints d'authentification OAuth et les endpoints d'écriture (`PATCH /album`, `POST /election/vote`) sont exposés à des abus.

### Points de vigilance

- [ ] Pas de `nelmio/security-bundle` — aucun en-têtes HTTP de sécurité ajoutés automatiquement — `composer.json`. Envisager l'ajout pour CSP, HSTS, X-Frame-Options.
- [ ] `TRUSTED_PROXIES=127.0.0.1` — à s'assurer que le reverse proxy Nginx en prod transmet correctement `X-Forwarded-For` et que cette valeur est adaptée à la topologie de déploiement.

---

## Dépendances vulnérables

Audit automatique via `make security` (`composer audit` + `symfony security:check`) et workflow CI dédié (`.github/workflows/security.yml`). Les outils qualité (`tools/*/`) ne sont pas audités par ce workflow.

| Dépendance | Version | Vulnérabilité | Action |
|-----------|---------|--------------|--------|
| — | — | Aucune vulnérabilité connue au moment de l'analyse | Maintenir `make security` en CI |

**Note** : les dépendances des outils qualité (`tools/deptrac/`, `tools/phpstan/`, etc.) ne sont pas couvertes par `composer audit` racine.

---

## Configuration & Infrastructure

### Points de vigilance

- [x] Mot de passe Redis passé en variable d'env `${REDIS_PASSWORD}` — valeur par défaut dev dans `.env`, surchargeable via `.env.local`.
- [ ] Image PHP non épinglée sur un digest SHA — `php:8.5.6-fpm-alpine3.23` dans `.docker/php/Dockerfile:1`. La version de tag est fixée (bon), mais pas le digest. Acceptable pour ce type de projet.
- [ ] Symfony CLI épinglée à `5.17.1` dans le Dockerfile dev (`--from=ghcr.io/symfony-cli/symfony-cli:5.17.1`) — bien épinglée, surveiller les nouvelles versions.
- [ ] Port Redis non exposé à l'hôte (pas de `ports:` dans `docker-compose.yaml`) — correct.
- [ ] Port PHP-FPM 9000 exposé via `EXPOSE 9000` dans le Dockerfile mais non publié dans `docker-compose.yaml` — correct (communication interne uniquement).

---

## Logging & Audit

### Ce qui est loggé

- **Toutes les requêtes HTTP sortantes** (méthode, URL, options) en niveau `info` — `src/Service/Api/AbstractApiService.php:33`
- **Toutes les réponses HTTP** (code + body complet) en niveau `info` — `src/Service/Api/AbstractApiService.php:55`
- **Erreurs admin** en niveau `critical` avec contexte (name, action) — `src/Controller/Admin/AbstractAdminActionController.php:40`

### Points de vigilance

- [x] Le body des réponses API est loggé en `debug` — `src/Service/Api/AbstractApiService.php:39` (commit `99a85ce`).
- [ ] Aucun log des événements d'authentification (login, échec, token invalide) au niveau applicatif. Les logs de rejet (BadCredentialsException) dépendent du niveau configuré pour le logger `security` de Symfony.
- [ ] Les actions admin (update, calculate, invalidate) sont loggées uniquement en cas d'erreur. Un log `info` des actions réussies améliorerait la traçabilité.

---

## Recommandations

### Haute priorité

- [ ] [haute] Rotation et suppression des secrets OAuth committés dans `.env.prod` — `.env.prod:43-46`
- [ ] [haute] Remplacer `APP_SECRET=!ChangeMe!` par un secret fort injecté via CI/CD — `.env.prod:12`
- [ ] [haute] Renforcer `API_PASSWORD` en production et l'injecter via secret — `.env.prod:23`

### Priorité moyenne

- [ ] [moyenne] Configurer un rate limiter Symfony sur les endpoints OAuth et d'écriture
- [x] [moyenne] Passer le mot de passe Redis en variable d'env — `docker-compose.yaml:35`
- [ ] [moyenne] Ajouter `nelmio/security-bundle` pour les en-têtes HTTP de sécurité
- [x] [moyenne] Log du body des réponses API déjà en niveau `debug` — `src/Service/Api/AbstractApiService.php:39`
- [x] [moyenne] `composer audit` étendu aux outils qualité — job `tools-composer-audit` dans `.github/workflows/security.yml`

### Basse priorité

- [ ] [basse] Documenter le choix `REQUIRE_INVITATION=false` comme intentionnel — `.env.prod:26`
- [~] [basse] `sha1` conservé intentionnellement — clé OAuth déjà publique, hash sert uniquement à éviter la valeur en clair. Pas de gain sécurité réel à migrer.
- [ ] [basse] Logger les authentifications réussies et échouées en niveau `info`
- [ ] [basse] Logger les actions admin réussies pour la traçabilité — `src/Controller/Admin/AbstractAdminActionController.php`
