# Sécurité — Pokenini Back

## Posture générale

L'application est une API JSON exposée à un frontend SPA. La posture sécurité est **solide** : OAuth2 multi-provider, contrôle d'accès par rôle, rate limiting sur les endpoints d'écriture, en-têtes HTTP de sécurité (nelmio), audit CI des dépendances. Le seul secret committé est le mot de passe Redis de développement (`douze`), intentionnellement laissé comme valeur par défaut surchargeable via `.env.local`.

---

## Authentification & Autorisation

### Mécanisme en place

Trois modes coexistent selon le contexte :

**1. Bearer token (API)** — `AccessTokenHandler` valide un token Bearer + header `X-Provider`. Chaque requête API est validée via `ClientRegistry::getClient($provider)->fetchUserFromToken()`. Mécanisme principal en production. Référence : `src/Security/AccessTokenHandler.php`

**2. OAuth2 redirect (login web)** — `GoogleAuthenticator`, `DiscordAuthenticator` via `knpuniversity/oauth2-client-bundle`. Le frontend récupère un token OAuth et l'envoie en Bearer sur chaque appel API. Référence : `src/Security/AbstractAuthenticator.php`

**3. FakeAuthenticator (dev uniquement)** — `/fr/connect/f/c?t=admin|collector|trainer` crée une session sans secret. Inaccessible en prod via `security.yaml`.

**4. MockAuthenticator (tests uniquement)** — token `Bearer this-is-{role}-token` + `X-Provider: mock`.

Les rôles (`admin`, `collector`, `trainer`) sont définis par des listes CSV d'IDs dans les variables d'env (`LIST_ADMIN`, `LIST_COLLECTOR`, `LIST_TRAINER`). Attribution dans `AccessTokenHandler::loadUserFromLists()` — `src/Security/AccessTokenHandler.php:68`.

Les succès et échecs d'authentification sont loggés en `info` — `src/Security/AccessTokenHandler.php:34-72`.

Référence config : `config/packages/security.yaml`

### Points de vigilance

- [ ] Aucun mécanisme de révocation de token — si un token OAuth est compromis, il reste valide jusqu'à expiration. Le provider externe (Google, Discord) gère la durée de vie.
- [ ] Le header `X-Provider` est transmis par le client — `AccessTokenHandler` fait confiance à ce header pour choisir le provider OAuth2 (`src/Security/AccessTokenHandler.php:39`). Un attaquant contrôlant les deux headers pourrait tenter une authentification croisée si les providers partagent des IDs utilisateur. Les tokens OAuth2 sont normalement isolés par provider.
- [x] `REQUIRE_INVITATION=false` — tout utilisateur authentifié Google/Discord reçoit `ROLE_USER` sans invitation. **Choix intentionnel** : l'application est ouverte à tous les utilisateurs OAuth.

---

## Validation des entrées & Injection

### État actuel

- **DTOs** : `OptionsResolver` avec types stricts et normaliseurs — validation exhaustive à la construction (`src/DTO/DexFilters.php:26`)
- **CatchStates** : contrainte Symfony `CatchStates` + `CatchStatesValidator` validant les états de capture autorisés
- **AlbumFilters** : whitelist explicite des 13 paramètres de requête autorisés (`src/AlbumFilters/FromRequest.php:11-24`)
- **JsonDecoder** : profondeur max 5, `JSON_THROW_ON_ERROR` — `src/Utils/JsonDecoder.php:11`
- **Pas de base de données** : aucun risque d'injection SQL

### Points de vigilance

- [ ] Les filtres de `AlbumFilters\FromRequest` sont transmis sans transformation à l'API externe comme paramètres de requête (`src/Service/Api/GetPokedexApiService.php:37`). Si l'API externe est vulnérable à des injections de paramètres, ce BFF amplifie le vecteur. Vérifier que pokenini-api valide ses entrées.
- [ ] `JsonDecoder::decode()` utilise une profondeur de 5 — `src/Utils/JsonDecoder.php:11`. Suffisant pour les structures actuelles ; à revoir si des structures JSON imbriquées sont ajoutées.

---

## Données sensibles & Secrets

### Variables d'environnement sensibles

| Variable | Exposition | Recommandation |
|----------|-----------|----------------|
| `APP_SECRET` | `.env.dev` : `$ecretf0rt3st` | Valeur dev distincte de prod — OK. Injecter la valeur prod via secret CI/CD. |
| `OAUTH_GOOGLE_CLIENT_SECRET` | `.env.dev/.env.ci/.env.int` (valeurs réelles) | **Apps de développement distinctes de la prod** — choix intentionnel documenté. |
| `OAUTH_DISCORD_CLIENT_SECRET` | `.env.dev/.env.ci/.env.int` (valeurs réelles) | Idem Google. |
| `API_PASSWORD` | `.env.dev` : `douze` | Mot de passe faible — acceptable en dev/test, renforcer en prod via secret CI/CD. |
| `REDIS_PASSWORD` | `.env` : `douze` (défaut dev) | Valeur par défaut surchargeable via `.env.local`. Renforcer en prod. |

### Points de vigilance

- [x] `.env.prod` supprimé du dépôt (2026-08-13) — le build de l'image de prod copie désormais `.env.dev` (déjà factice, déjà maintenu) pour compiler le container Symfony ; les valeurs sont jetées avant la fin du build, comme avant. Voir `docs/superpowers/specs/2026-08-13-env-prod-build-source-design.md`.
- [x] Credentials OAuth dans `.env.dev/.env.ci/.env.int` : **apps de développement distinctes de la production** — choix intentionnel.
- [x] Mot de passe Redis en variable d'env `${REDIS_PASSWORD}` — `docker-compose.yaml:37`. Valeur par défaut dans `.env`, surchargeable via `.env.local`.
- [ ] `.env.dev.local` est dans `.gitignore` (non commité) — bon. Vérifier que les secrets prod ne transitent jamais par ce fichier en dev.

---

## Sécurité HTTP

### En-têtes de sécurité

Configurés via `nelmio/security-bundle` — `config/packages/nelmio_security.yaml` :

| En-tête | Valeur | Contexte |
|---------|--------|---------|
| `X-Frame-Options` | `DENY` | Tous environnements |
| `X-Content-Type-Options` | `nosniff` | Tous environnements |
| `Referrer-Policy` | `no-referrer, strict-origin-when-cross-origin` | Tous environnements |
| `Strict-Transport-Security` | `max-age=31536000` | Production uniquement |

### CSRF

Non applicable — API JSON stateless. Le frontend consomme l'API avec un Bearer token, pas de formulaire HTML. Les cookies de session utilisent `SameSite: lax` (`config/packages/framework.yaml:17`).

### CORS

Configuré via `CORS_ALLOW_ORIGIN` en variable d'env. En dev : `localhost|apache|127.0.0.1`. En prod : à vérifier que `CORS_ALLOW_ORIGIN` est correctement restreint au domaine du frontend.

### Rate limiting

Configuré sur les **endpoints d'écriture** via `App\EventSubscriber\RateLimiterSubscriber` — `src/EventSubscriber/RateLimiterSubscriber.php` :

| Route | Méthode | Limite |
|-------|---------|--------|
| `app_album_albumupsert_upsert` | PATCH/PUT | 60 req/min |
| `app_election_electionvote_vote` | POST | 60 req/min |
| `app_trainer_trainerupsert_upsert` | PUT | 60 req/min |
| `app_admin_adminactioncalculate_process` | POST | 60 req/min |
| `app_admin_adminactionupdate_process` | POST | 60 req/min |
| `app_admin_adminactioninvalidate_process` | DELETE | 60 req/min |

Clé de limite : SHA-256(Authorization header) ou SHA-256(IP) si pas de header. Policy : `sliding_window`. Config : `config/packages/rate_limiter.yaml`.

### Points de vigilance

- [ ] Rate limiting des endpoints OAuth (callbacks) non géré au niveau Symfony — les routes OAuth sont à `/` et ne sont pas identifiables par nom de route depuis un subscriber. À traiter au niveau **Nginx/infrastructure**.
- [ ] `TRUSTED_PROXIES=127.0.0.1` — à s'assurer que le reverse proxy Nginx en prod transmet correctement `X-Forwarded-For` et que cette valeur est adaptée à la topologie de déploiement — `config/packages/framework.yaml:8`.

---

## Dépendances vulnérables

Audit automatique via `make security` (`composer audit`) et workflow CI dédié (`.github/workflows/security.yml`). Le workflow couvre maintenant **à la fois les dépendances racine et les outils qualité** (`tools/*/`) via le job `tools-composer-audit`.

| Dépendance | Version | Vulnérabilité | Action |
|-----------|---------|--------------|--------|
| — | — | Aucune vulnérabilité connue au moment de l'analyse | Maintenir `make security` en CI |

---

## Configuration & Infrastructure

### Points de vigilance

- [x] Mot de passe Redis en variable d'env `${REDIS_PASSWORD}` — `docker-compose.yaml:37`.
- [x] Port Redis non exposé à l'hôte (pas de `ports:` dans `docker-compose.yaml`) — communication interne uniquement.
- [x] Port Nginx publié uniquement sur `127.0.0.1:8081` en dev — `docker-compose.yaml:47`.
- [ ] Image PHP non épinglée sur un digest SHA — `php:8.5.6-fpm-alpine3.23` dans `.docker/php/Dockerfile:1`. La version de tag est fixée (bon), mais pas le digest. Acceptable pour ce type de projet.
- [ ] Symfony CLI épinglée à `5.17.1` dans le Dockerfile dev — bien épinglée, surveiller les nouvelles versions.

---

## Logging & Audit

### Ce qui est loggé

- **Authentifications** (réussies et échouées) en niveau `info` — `src/Security/AccessTokenHandler.php:34-72`
  - Échec : no request, header manquant/vide, token invalide
  - Succès : provider utilisé
- **Toutes les requêtes HTTP sortantes** (méthode, URL) en niveau `info` — `src/Service/Api/AbstractApiService.php:52`
- **Body des réponses HTTP** en niveau `debug` (désactivé en prod) — `src/Service/Api/AbstractApiService.php:39`
- **Code HTTP des réponses** en niveau `info` — `src/Service/Api/AbstractApiService.php:75`
- **Actions admin réussies** en niveau `info` — `src/Controller/Admin/AbstractAdminActionController.php:35`
- **Erreurs admin** en niveau `critical` avec contexte (name, action) — `src/Controller/Admin/AbstractAdminActionController.php:41`

### Points de vigilance

- [x] `auth_basic` non exposé dans les logs — le logger logue `$options` du caller (`AbstractApiService.php:52`), pas les options fusionnées internes qui contiennent les credentials. Faux positif confirmé.
- [x] Rejets RBAC (`AccessDeniedException`) loggés en `warning` avec la route — `src/EventSubscriber/AccessDeniedSubscriber.php`.

---

## Recommandations

### Haute priorité

— Aucune. Les items critiques ont été traités (suppression `.env.prod`, rate limiting, en-têtes HTTP).

### Priorité moyenne

- [ ] [moyenne] Vérifier que `CORS_ALLOW_ORIGIN` est correctement restreint en production au domaine frontend.
- [ ] [moyenne] Adapter `TRUSTED_PROXIES` à la topologie de déploiement prod — `config/packages/framework.yaml:8`.
- [ ] [moyenne] Rate limiting des endpoints OAuth à configurer au niveau Nginx/infrastructure.

### Basse priorité

- [ ] [basse] Renforcer `API_PASSWORD` et `REDIS_PASSWORD` en production via secrets CI/CD (valeur `douze` trop faible).
- [x] [basse] Rejets RBAC loggés en `warning` — `src/EventSubscriber/AccessDeniedSubscriber.php`.
- [ ] [basse] `sha1` dans `UserTokenService.php:25` — conservé intentionnellement (clé OAuth déjà publique, usage cache uniquement, pas cryptographique).
