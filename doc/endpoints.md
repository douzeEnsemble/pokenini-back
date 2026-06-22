# Endpoints — Pokenini Back

Documentation des endpoints exposés par pokenini-back et des structures JSON associées.

---

## GET /election/{dexSlug}/{electionSlug}

Retourne les données nécessaires à l'affichage d'une page d'élection :
- la liste des Pokémon à voter (`pokemons`)
- le classement Elo (`election_top`)
- les métriques de l'élection (`metrics`)
- le pokédex du dresseur (`pokedex`)

### Réponse

```json
{
  "type": "pick",
  "pokemons": [ ... ],
  "pokedex": { ... },
  "election_top": [ ... ],
  "metrics": { ... },
  "detached_count": 1,
  "is_the_last_one": false,
  "is_the_last_page": false
}
```

### Structure d'un item `election_top`

```json
{
  "pokemon": {
    "slug": "venusaur-mega",
    "pokemon_icon": "venusaur-mega",
    "labels": {
      "name": "Mega Venusaur",
      "simplified_name": "Venusaur",
      "french_name": "Mega Florizarre",
      "simplified_french_name": "Florizarre"
    },
    "national_dex_number": 3
  },
  "forms": {
    "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" }
  },
  "types": {
    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
  },
  "score": {
    "elo": 1040,
    "significance": false
  }
}
```

**Notes :**
- `pokemon.pokemon_icon` : ajouté par `GetElectionTopService` (= `pokemon.slug`) — utilisé par le web pour charger l'image
- `pokemon.labels.simplified_name` / `simplified_french_name` : nom sans suffixe de forme, fourni par pokenini-api
- `forms` : `null` si le Pokémon n'a aucune forme spéciale ; sinon objet avec les clés `variant`, `regional`, `special` selon le cas
- `score.significance` : `true` si l'Elo est statistiquement significatif (contribue à `detached_count`)

### Structure d'un item `pokemons`

Voir les fixtures `tests/resources/functional/controller/ElectionIndex/demolite.json` pour la structure complète.
Champs clés : `pokemon_slug`, `pokemon_icon`, `pokemon_simplified_name`, `pokemon_simplified_french_name`, `catch_state_slug`, `primary_type_slug`.
