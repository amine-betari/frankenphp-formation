# Feuille de route — Maîtriser FrankenPHP avec Symfony

Chaque étape doit produire du code reproductible, des captures, des mesures et un article associé.

## Étape 1 — Découverte et premiers exemples

- [x] PHP minimal servi directement par FrankenPHP
- [x] Installation de Symfony 7.4
- [x] Comparaison classique / Worker
- [x] Démonstration de la mémoire avec un compteur
- [x] API REST Doctrine et SQLite
- [x] Interface Twig et appels `fetch()`
- [x] API externe Open-Meteo et graphiques
- [x] Premier benchmark
- [x] Article de présentation

## Étape 2 — Docker et Caddy

- [ ] Construire une image Docker dédiée
- [x] Ajouter un `Caddyfile` explicite
- [x] Comprendre les directives `php_server` et `worker`
- [ ] Séparer développement et production
- [x] Ajouter un healthcheck applicatif

## Étape 3 — Worker mode en profondeur

- [ ] Cycle de vie du Kernel Symfony
- [ ] Services avec état et `ResetInterface`
- [ ] Connexions Doctrine persistantes
- [ ] Redémarrage automatique et `MAX_REQUESTS`
- [ ] Audit de compatibilité Worker

## Étape 4 — Mesures de performance

- [ ] Benchmark reproductible avec plusieurs niveaux de concurrence
- [ ] Comparer classique, Worker et PHP-FPM
- [ ] Mesurer latence moyenne, p95 et p99
- [ ] Observer CPU et mémoire
- [ ] Tester un endpoint simple, Doctrine et une API externe

## Étape 5 — HTTPS et protocoles modernes

- [ ] HTTPS local et certificats
- [ ] HTTP/2
- [ ] HTTP/3 et QUIC
- [ ] Compression et en-têtes de cache
- [ ] Sécurité Caddy

## Étape 6 — Services applicatifs

- [ ] PostgreSQL
- [ ] Redis et cache Symfony
- [ ] Messenger et traitements asynchrones
- [ ] Mercure et mises à jour temps réel
- [ ] Emails avec Mailpit

## Étape 7 — Observabilité

- [ ] Logs structurés
- [ ] Métriques FrankenPHP et Caddy
- [ ] Profilage Symfony
- [ ] Alertes et tableaux de bord
- [ ] Diagnostic des fuites mémoire

## Étape 8 — Production

- [ ] Image multi-stage optimisée
- [ ] Variables et secrets de production
- [ ] Déploiement sans interruption
- [ ] Sauvegardes et stratégie de retour arrière
- [ ] Checklist de sécurité et de performance
