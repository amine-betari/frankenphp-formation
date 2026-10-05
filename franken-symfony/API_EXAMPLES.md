# Exemples de l'API Produits

La même API est disponible avec deux cycles d'exécution :

- mode classique : `http://localhost:8384`
- mode Worker : `http://localhost:8385`

Les deux conteneurs utilisent la même base SQLite `var/products.db`.

## Lister les produits

```bash
curl http://localhost:8384/api/products
```

La propriété `count` indique le nombre de résultats et `items` contient les produits.

## Lire un produit

```bash
curl http://localhost:8385/api/products/1
```

## Créer un produit

Le prix est exprimé en centimes pour éviter les erreurs d'arrondi des nombres décimaux.

```bash
curl -i -X POST http://localhost:8384/api/products \
  -H 'Content-Type: application/json' \
  -d '{"name":"Casque audio","price_cents":6990,"stock":8}'
```

Une création réussie renvoie le statut HTTP `201 Created`.

## Modifier partiellement un produit

```bash
curl -i -X PATCH http://localhost:8385/api/products/1 \
  -H 'Content-Type: application/json' \
  -d '{"price_cents":8490,"stock":9}'
```

Seules les propriétés présentes dans le JSON sont modifiées.

## Rechercher

```bash
curl 'http://localhost:8384/api/products?q=clavier'
```

## Afficher uniquement les produits en stock

```bash
curl 'http://localhost:8385/api/products?in_stock=1'
```

## Supprimer un produit

```bash
curl -i -X DELETE http://localhost:8384/api/products/3
```

Une suppression réussie renvoie le statut HTTP `204 No Content`.

## Observer une erreur de validation

```bash
curl -i -X POST http://localhost:8384/api/products \
  -H 'Content-Type: application/json' \
  -d '{"name":"Produit invalide","price_cents":-1}'
```

La réponse utilise le statut `422 Unprocessable Content` et explique le problème dans `error`.

## Point important

Créer un produit sur le port `8384`, puis le lire sur `8385`, démontre que le mode classique et le mode Worker exposent la même application et les mêmes données. Le mode change le cycle de vie de PHP et Symfony, pas le contrat HTTP de l'API.
