# Corrections — Différences Back vs Api

Le Back **ne doit pas** modifier les données ni les formats des réponses de l'Api. Ce document liste toutes les divergences identifiées entre ce que l'Api retourne et ce que le Back expose.

---

## ~~1. `GET /election/{dexSlug}/{electionSlug}` — champ `metrics`~~ ✅ Corrigé

Le champ `metrics` est un passthrough du format retourné par l'API (`view_count`, `win_count`,
`completion`, `dex_total_count`), enrichi de trois champs calculés par le Back : `round_count`,
`winner_average` et `total_round_count`.

Ces trois champs ne font **pas** partie du domaine de l'Api : ils dérivent du découpage en tours
d'affichage (`ELECTION_CANDIDATE_COUNT`), une notion propre au Back/Web. Ils avaient été retirés
une première fois au nom du principe "le Back ne doit pas transformer les données de l'Api", mais
cette lecture était trop large — ce n'est pas une transformation du format Api, c'est un calcul
métier que l'Api ne fournira jamais. Leur suppression a cassé le Web en production
(`MissingOptionsException` sur `round_count`/`total_round_count`/`winner_average`), d'où leur
réintroduction via `TotalRoundCountHelper` + `ElectionMetrics`.

---
