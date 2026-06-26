# Corrections — Différences Back vs Api

Le Back **ne doit pas** modifier les données ni les formats des réponses de l'Api. Ce document liste toutes les divergences identifiées entre ce que l'Api retourne et ce que le Back expose.

---

## ~~1. `GET /election/{dexSlug}/{electionSlug}` — champ `metrics`~~ ✅ Corrigé

Le champ `metrics` est désormais un passthrough direct du format retourné par l'API.
`GetElectionMetricsApiService` retourne le JSON brut, `ElectionMetrics` stocke `raw` sans transformation.
`TotalRoundCountHelper` et les champs calculés (`round_count`, `winner_average`, `total_round_count`) ont été supprimés.

---
