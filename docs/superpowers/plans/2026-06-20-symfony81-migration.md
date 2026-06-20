# Symfony 8.1 Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Exploiter les nouvelles fonctionnalités de Symfony 8.1 pour simplifier le code existant, en commençant par remplacer `RateLimiterSubscriber` par `#[RateLimit]` puis en simplifiant `ElectionVoteController` avec `#[MapRequestPayload]`.

**Architecture:** Deux tâches indépendantes. La tâche 1 supprime un event subscriber entier en déplaçant la logique de rate limiting directement dans les attributs PHP des contrôleurs. La tâche 2 remplace un parsing JSON manuel par la désérialisation native de Symfony dans un DTO.

**Tech Stack:** Symfony 8.1, PHP 8.5, PHPUnit, Docker via `make`

## Global Constraints

- Toutes les commandes s'exécutent dans Docker via `make` : `make tests`, `make quality`, `make cq`
- `declare(strict_types=1)` dans tous les fichiers PHP
- Classes `final` pour les contrôleurs et les exceptions ; non-`final` pour les services
- `/** @internal */` + `#[CoversClass(TargetClass::class)]` sur toutes les classes de test
- 100% de couverture de code et 100% MSI (mutation score) requis — vérifier avec `make coverage` et `make infection`
- Jamais de dépendance de qualité dans le `composer.json` principal
- Layered architecture: Controller → Service ; ne pas appeler directement `ApiService` depuis un contrôleur

---

## Task 1 : Remplacer `RateLimiterSubscriber` par `#[RateLimit]`

`RateLimiterSubscriber` maintient une liste codée en dur de noms de routes et intercept toutes les requêtes. Symfony 8.1 introduit l'attribut `#[RateLimit]` qui s'applique directement sur l'action ou la classe contrôleur — le kernel retourne automatiquement un 429 avec `Retry-After` si la limite est dépassée.

**Point de vigilance — clé de rate limiting :** Le subscriber actuel utilise l'en-tête `Authorization` comme clé (fallback sur l'IP client). Vérifier que `#[RateLimit]` supporte un paramètre `key` ou un `KeyResolverInterface`. Si ce n'est pas le cas, le comportement changera (rate limit par IP, pas par token). Valider ce point en lisant la doc officielle avant de commencer.

**Files:**
- Modify: `src/Controller/Album/AlbumUpsertController.php`
- Modify: `src/Controller/Election/ElectionVoteController.php`
- Modify: `src/Controller/Trainer/TrainerUpsertController.php`
- Modify: `src/Controller/Admin/AdminActionCalculateController.php`
- Modify: `src/Controller/Admin/AdminActionUpdateController.php`
- Modify: `src/Controller/Admin/AdminActionInvalidateController.php`
- Delete: `src/EventSubscriber/RateLimiterSubscriber.php`
- Delete: `tests/src/Unit/EventSubscriber/RateLimiterSubscriberTest.php`
- Modify (optionnel): `config/packages/rate_limiter.yaml` si ajustement de clé nécessaire

**Interfaces:**
- Consumes: limiter `write_api` configuré dans `config/packages/rate_limiter.yaml`
- Produces: 429 automatique avec `Retry-After` quand la limite est dépassée, géré par le kernel

---

- [ ] **Étape 1 : Vérifier le comportement de la clé dans `#[RateLimit]`**

  Lire https://symfony.com/blog/new-in-symfony-8-1-ratelimiter-improvements pour confirmer si `#[RateLimit]` accepte un `key:` ou s'appuie sur un `KeyResolverInterface`. Le subscriber actuel fait :
  ```php
  $key = $request->headers->get('Authorization', $request->getClientIp() ?? 'unknown') ?? 'unknown';
  $limiter = $this->writeApiLimiter->create(hash('sha256', $key));
  ```
  Si `#[RateLimit]` ne supporte pas de clé personnalisée, la migration change le comportement (par IP au lieu de par token). Dans ce cas, documenter la décision dans le commit.

- [ ] **Étape 2 : Ajouter `#[RateLimit]` sur `AlbumUpsertController`**

  ```php
  // src/Controller/Album/AlbumUpsertController.php
  use Symfony\Component\RateLimiter\Attribute\RateLimit;
  
  #[Route('/album')]
  final class AlbumUpsertController extends AbstractController
  {
      // ...
  
      #[Route('/{dexSlug}/{pokemonSlug}', methods: ['PATCH', 'PUT'])]
      #[IsGranted('ROLE_TRAINER')]
      #[RateLimit(limiter: 'write_api')]
      public function upsert(
          string $dexSlug,
          string $pokemonSlug,
      ): Response {
          // corps inchangé
      }
  }
  ```

- [ ] **Étape 3 : Ajouter `#[RateLimit]` sur `ElectionVoteController`**

  ```php
  // src/Controller/Election/ElectionVoteController.php
  use Symfony\Component\RateLimiter\Attribute\RateLimit;
  
  #[Route('/election')]
  final class ElectionVoteController extends AbstractController
  {
      #[Route(
          '/{dexSlug}/{electionSlug}',
          requirements: [
              'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
              'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
          ],
          methods: ['POST']
      )]
      #[RateLimit(limiter: 'write_api')]
      public function vote(/* ... */): Response {
          // corps inchangé
      }
  }
  ```

- [ ] **Étape 4 : Ajouter `#[RateLimit]` sur `TrainerUpsertController`**

  ```php
  // src/Controller/Trainer/TrainerUpsertController.php
  use Symfony\Component\RateLimiter\Attribute\RateLimit;
  
  #[Route('/trainer')]
  final class TrainerUpsertController extends AbstractController
  {
      // ...
  
      #[Route('/dex/{dexSlug}', methods: ['PUT'])]
      #[IsGranted('ROLE_TRAINER')]
      #[RateLimit(limiter: 'write_api')]
      public function upsert(string $dexSlug): Response {
          // corps inchangé
      }
  }
  ```

- [ ] **Étape 5 : Ajouter `#[RateLimit]` sur les trois contrôleurs Admin**

  ```php
  // src/Controller/Admin/AdminActionCalculateController.php
  use Symfony\Component\RateLimiter\Attribute\RateLimit;
  
  #[Route('/istration/action')]
  final class AdminActionCalculateController extends AbstractAdminActionController
  {
      #[\Override()]
      #[Route('/calculate/{name}', methods: ['POST'], condition: "params['name'] in [...]")]
      #[RateLimit(limiter: 'write_api')]
      public function process(string $name): JsonResponse
      {
          return $this->execute($name, 'calculate');
      }
  }
  ```

  Appliquer le même changement sur `AdminActionUpdateController::process()` et `AdminActionInvalidateController::process()`.

- [ ] **Étape 6 : Supprimer `RateLimiterSubscriber` et son test**

  ```bash
  rm src/EventSubscriber/RateLimiterSubscriber.php
  rm tests/src/Unit/EventSubscriber/RateLimiterSubscriberTest.php
  ```

- [ ] **Étape 7 : Lancer les tests**

  ```bash
  docker compose exec php php vendor/bin/phpunit
  ```

  Attendu : tous les tests passent. Si des intégrations testent le comportement 429, vérifier qu'ils couvrent toujours ce cas via le kernel (pas via le subscriber).

- [ ] **Étape 8 : Vérifier la qualité**

  ```bash
  make quality
  ```

  Attendu : 0 erreur. Psalm et PHPStan ne doivent pas se plaindre du `use` supprimé.

- [ ] **Étape 9 : Vérifier la couverture**

  ```bash
  make coverage
  ```

  Attendu : 100%. Les lignes supprimées ne créent pas de trous — le subscriber est parti.

- [ ] **Étape 10 : Commit**

  ```bash
  git add src/Controller/Album/AlbumUpsertController.php \
          src/Controller/Election/ElectionVoteController.php \
          src/Controller/Trainer/TrainerUpsertController.php \
          src/Controller/Admin/AdminActionCalculateController.php \
          src/Controller/Admin/AdminActionUpdateController.php \
          src/Controller/Admin/AdminActionInvalidateController.php
  git rm src/EventSubscriber/RateLimiterSubscriber.php \
          tests/src/Unit/EventSubscriber/RateLimiterSubscriberTest.php
  git commit -m "refactor: replace RateLimiterSubscriber with #[RateLimit] attribute (Symfony 8.1)"
  ```

---

## Task 2 : Simplifier `ElectionVoteController` avec `#[MapRequestPayload]`

`ElectionVoteController` décode manuellement le JSON, valide l'absence de données, puis construit un `ElectionVote` via `OptionsResolver`. Symfony 8.1 améliore `#[MapRequestPayload]` (groupes de validation dynamiques, variadic, `mapWhenEmpty`). Cependant, `ElectionVote` utilise actuellement `OptionsResolver` dans son constructeur, ce qui est incompatible avec la désérialisation Symfony. Cette tâche refactorise `ElectionVote` pour le rendre sérialisable, puis branche `#[MapRequestPayload]`.

**Files:**
- Modify: `src/DTO/ElectionVote.php` — supprimer `OptionsResolver`, exposer des propriétés typées
- Modify: `src/Controller/Election/ElectionVoteController.php` — utiliser `#[MapRequestPayload]`
- Modify: `tests/src/Unit/DTO/ElectionVoteTest.php` — adapter les tests au nouveau constructeur
- Modify: `tests/src/Integration/Election/ElectionVoteTest.php` — vérifier le comportement 400 sur payload invalide

**Interfaces:**
- Consumes: JSON body `{"winners_slugs": ["slug1"], "losers_slugs": ["slug2", "slug3"]}`
- Produces: `ElectionVote` injecté directement dans l'action, 400 automatique si payload invalide

---

- [ ] **Étape 1 : Écrire le test du nouveau `ElectionVote`**

  Localiser ou créer `tests/src/Unit/DTO/ElectionVoteTest.php`. Le nouveau `ElectionVote` doit avoir des propriétés publiques (pas de `OptionsResolver` dans le constructeur) :

  ```php
  <?php
  declare(strict_types=1);
  namespace App\Tests\Unit\DTO;
  
  use App\DTO\ElectionVote;
  use PHPUnit\Framework\Attributes\CoversClass;
  use PHPUnit\Framework\TestCase;
  
  /** @internal */
  #[CoversClass(ElectionVote::class)]
  final class ElectionVoteTest extends TestCase
  {
      public function testWinnersAndLosersAreDeduplicatedAndFiltered(): void
      {
          $vote = new ElectionVote();
          $vote->dexSlug = 'kanto';
          $vote->electionSlug = 'round-1';
          $vote->winnersSlugs = ['bulbasaur', '', 'ivysaur'];
          $vote->losersSlugs = ['charmander', 'bulbasaur', ''];
  
          // losers_slugs ne doit pas contenir un winner ni les chaînes vides
          $this->assertSame(['bulbasaur', 'ivysaur'], $vote->filteredWinners());
          $this->assertSame(['charmander'], $vote->filteredLosers());
      }
  }
  ```

  **Note :** Adapter les noms de méthodes selon ce que `ModifyElectionVoteService` attend réellement. Lire `src/Service/ModifyElectionVoteService.php` avant d'écrire le test.

- [ ] **Étape 2 : Lancer ce test pour confirmer qu'il échoue**

  ```bash
  docker compose exec php php vendor/bin/phpunit tests/src/Unit/DTO/ElectionVoteTest.php -v
  ```

  Attendu : FAIL — `ElectionVote` n'a pas encore les méthodes/propriétés attendues.

- [ ] **Étape 3 : Refactoriser `ElectionVote` pour la désérialisation**

  ```php
  // src/DTO/ElectionVote.php
  <?php
  declare(strict_types=1);
  namespace App\DTO;
  
  final class ElectionVote
  {
      public string $dexSlug = '';
      public string $electionSlug = '';
  
      /** @var string[] */
      public array $winnersSlugs = [];
  
      /** @var string[] */
      public array $losersSlugs = [];
  
      /** @return string[] */
      public function filteredWinners(): array
      {
          return array_values(array_filter($this->winnersSlugs));
      }
  
      /** @return string[] */
      public function filteredLosers(): array
      {
          $winners = $this->filteredWinners();
          return array_values(array_diff(array_filter($this->losersSlugs), $winners));
      }
  }
  ```

  **Important :** Vérifier ce que `ModifyElectionVoteService` attend de `ElectionVote` (lire `src/Service/ModifyElectionVoteService.php`) et adapter les noms de méthodes en conséquence avant de valider ce code.

- [ ] **Étape 4 : Refactoriser `ElectionVoteController`**

  ```php
  // src/Controller/Election/ElectionVoteController.php
  use App\DTO\ElectionVote;
  use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
  
  #[Route('/election')]
  final class ElectionVoteController extends AbstractController
  {
      #[Route(
          '/{dexSlug}/{electionSlug}',
          requirements: [
              'dexSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
              'electionSlug' => '[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*',
          ],
          methods: ['POST']
      )]
      #[RateLimit(limiter: 'write_api')]
      public function vote(
          ModifyElectionVoteService $electionVoteService,
          #[MapRequestPayload] ElectionVote $electionVote,
          string $dexSlug,
          string $electionSlug = '',
      ): Response {
          $electionVote->dexSlug = $dexSlug;
          $electionVote->electionSlug = $electionSlug;
  
          $electionVoteService->vote($electionVote);
  
          return new JsonResponse([], Response::HTTP_OK);
      }
  }
  ```

  Les blocs `if (empty($data))` et `try/catch InvalidArgumentException` disparaissent : Symfony retourne 422 automatiquement si le payload ne peut pas être désérialisé.

- [ ] **Étape 5 : Lancer tous les tests**

  ```bash
  docker compose exec php php vendor/bin/phpunit
  ```

  Attendu : tous passent. Si un test d'intégration vérifie un 400 sur payload vide, le code HTTP peut changer (400 → 422) — adapter le test.

- [ ] **Étape 6 : Vérifier qualité et couverture**

  ```bash
  make quality && make coverage
  ```

  Attendu : 0 erreur, 100% couverture. `RequestedContentService` n'est plus utilisé par `ElectionVoteController` mais est toujours utilisé par `AlbumUpsertController` et `TrainerUpsertController` — il ne doit pas être supprimé.

- [ ] **Étape 7 : Commit**

  ```bash
  git add src/DTO/ElectionVote.php \
          src/Controller/Election/ElectionVoteController.php \
          tests/src/Unit/DTO/ElectionVoteTest.php \
          tests/src/Integration/Election/ElectionVoteTest.php
  git commit -m "refactor: use #[MapRequestPayload] in ElectionVoteController (Symfony 8.1)"
  ```

---

## Note : `#[Cache]` amélioré — non applicable au cache pool interne

L'attribut `#[Cache]` de Symfony configure les **en-têtes HTTP** de cache (Cache-Control, ETag, Last-Modified). Ce projet utilise `TagAwareCacheInterface` comme **cache pool interne** (via `$this->cache->get(...)` dans les `AbstractApiService`). Ce sont deux systèmes distincts — l'attribut `#[Cache]` n'a pas d'effet sur le cache pool. Cette piste est donc abandonnée.
