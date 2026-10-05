# FrankenPHP avec Symfony : configurer Caddy et ajouter des healthchecks

Dans la première étape de notre laboratoire, nous avons lancé Symfony avec l’image Docker officielle de FrankenPHP. Tout fonctionnait sans créer de serveur Nginx, Apache ou PHP-FPM.

Cette simplicité cachait toutefois une partie importante du fonctionnement : FrankenPHP utilisait le `Caddyfile` fourni par son image Docker. Dans cette deuxième étape, nous allons rendre cette configuration explicite, ajouter des en-têtes de sécurité et créer de vrais contrôles de santé pour Docker.

L’objectif reste volontairement simple : comprendre précisément quelle configuration sert notre application, sans construire immédiatement une architecture de production complexe.

## Architecture du laboratoire

Nous conservons trois serveurs :

| Service | Port | Description |
|---|---:|---|
| `php-demo` | `8282` | Application PHP minimale |
| `symfony-classic` | `8384` | Symfony en mode classique |
| `symfony-worker` | `8385` | Symfony avec dix workers |

Les trois services utilisent la même image :

```text
dunglas/frankenphp:1-php8.5-bookworm
```

Le projet complet est disponible sur GitHub :

<https://github.com/amine-betari/frankenphp-formation>

## Où FrankenPHP cherche-t-il le Caddyfile ?

Dans l’image Docker officielle, le fichier principal est situé ici :

```text
/etc/frankenphp/Caddyfile
```

FrankenPHP démarre Caddy avec cette configuration. Dans notre première version, nous utilisions implicitement le fichier fourni par l’image. Cela fonctionnait, mais la configuration importante n’apparaissait pas dans notre dépôt Git.

Nous avons donc créé le fichier suivant :

```text
docker/frankenphp/Caddyfile
```

## Un Caddyfile minimal et explicite

Voici la configuration utilisée par le laboratoire :

```caddyfile
{
    skip_install_trust

    frankenphp {
        {$FRANKENPHP_CONFIG}
    }
}

{$SERVER_NAME:localhost} {
    root * /app/public

    log {
        output stdout
        format console
    }

    encode zstd br gzip

    header {
        X-Content-Type-Options nosniff
        X-Frame-Options SAMEORIGIN
        Referrer-Policy strict-origin-when-cross-origin
    }

    php_server
}
```

Examinons chaque partie.

## Le bloc global `frankenphp`

```caddyfile
{
    skip_install_trust

    frankenphp {
        {$FRANKENPHP_CONFIG}
    }
}
```

Le bloc placé au début contient les options globales de Caddy et FrankenPHP.

`skip_install_trust` empêche Caddy d’essayer d’installer automatiquement son autorité de certification locale dans la machine. Notre laboratoire utilise uniquement HTTP sur les ports locaux, cette installation n’est donc pas nécessaire à ce stade.

La variable suivante reçoit la configuration propre à chaque conteneur :

```caddyfile
{$FRANKENPHP_CONFIG}
```

Elle est vide pour le mode classique. Dans le service Worker, Docker lui transmet :

```text
worker {
    file ./public/index.php
    num 10
    watch
}
```

Nous pouvons ainsi utiliser exactement le même Caddyfile dans les deux modes.

## Déclarer le serveur HTTP

```caddyfile
{$SERVER_NAME:localhost} {
```

La variable `SERVER_NAME` indique à Caddy l’adresse sur laquelle le serveur doit répondre. Dans Docker Compose, nous utilisons :

```yaml
environment:
  SERVER_NAME: :80
```

Le conteneur écoute donc sur son port HTTP `80`. Docker publie ensuite ce port sur `8384` ou `8385` selon le service.

## Définir la racine publique

```caddyfile
root * /app/public
```

Le projet Symfony complet est monté dans `/app`, mais seuls les fichiers placés dans `/app/public` doivent être accessibles depuis le navigateur.

Cette séparation est essentielle. Les répertoires `src`, `config`, `vendor` et les fichiers `.env` ne doivent jamais être servis directement.

## Activer la compression

```caddyfile
encode zstd br gzip
```

Caddy choisit automatiquement un format supporté par le navigateur :

- Zstandard ;
- Brotli ;
- Gzip.

Nous avons vérifié la compression avec une requête annonçant le support de Gzip :

```bash
curl -H 'Accept-Encoding: gzip' -D - -o /dev/null \
  http://localhost:8385/products
```

La réponse contient :

```http
HTTP/1.1 200 OK
Content-Encoding: gzip
Vary: Accept-Encoding
```

## Ajouter quelques en-têtes de sécurité

```caddyfile
header {
    X-Content-Type-Options nosniff
    X-Frame-Options SAMEORIGIN
    Referrer-Policy strict-origin-when-cross-origin
}
```

Ces en-têtes apportent une première protection côté navigateur :

- `X-Content-Type-Options: nosniff` limite la détection incorrecte du type d’un fichier ;
- `X-Frame-Options: SAMEORIGIN` empêche un autre site d’intégrer librement les pages dans une iframe ;
- `Referrer-Policy` limite les informations transmises lors d’une navigation vers un autre domaine.

Ce n’est pas une configuration de sécurité exhaustive, mais c’est une base claire et facile à vérifier.

## Comprendre `php_server`

```caddyfile
php_server
```

Cette directive relie Caddy au moteur PHP intégré à FrankenPHP. Elle permet notamment :

- d’exécuter `public/index.php` ;
- de servir les fichiers statiques ;
- d’utiliser le front controller Symfony lorsqu’aucun fichier statique ne correspond à l’URL.

Il n’y a toujours aucun échange FastCGI et aucun processus PHP-FPM externe.

```text
Navigateur
   ↓
Caddy intégré
   ↓
php_server
   ↓
PHP intégré à FrankenPHP
   ↓
public/index.php
   ↓
Kernel Symfony
```

## Fichier statique ou exécution PHP ?

La directive `php_server` ne transmet pas inutilement tous les fichiers à PHP.

Lorsque nous demandons :

```text
http://localhost:8282/test.txt
```

Caddy trouve le fichier dans `/app/public` et le renvoie directement :

```text
Navigateur → Caddy → test.txt
```

Lorsque nous demandons `/api`, aucun fichier portant ce nom n’existe. La requête passe alors par le front controller :

```text
Navigateur → Caddy → FrankenPHP → public/index.php → réponse JSON
```

Avec Symfony, une URL comme `/products` suit le même principe :

```text
Navigateur → Caddy → FrankenPHP → public/index.php → Router Symfony → contrôleur
```

Les fichiers CSS, JavaScript et images peuvent donc être servis rapidement par Caddy, tandis que seules les pages dynamiques démarrent la logique PHP.

## Monter le Caddyfile dans Docker Compose

Chaque service utilise le même montage en lecture seule :

```yaml
volumes:
  - ./franken-symfony:/app
  - ./docker/frankenphp/Caddyfile:/etc/frankenphp/Caddyfile:ro
```

Le suffixe `:ro` empêche le conteneur de modifier la configuration présente sur la machine hôte.

Le service Worker ajoute uniquement cette variable :

```yaml
environment:
  FRANKENPHP_CONFIG: worker ./public/index.php 10
```

Au démarrage, les logs confirment le nombre de workers :

```text
FrankenPHP started
php_version: 8.5.11
worker_threads: 10
```

## Pourquoi ajouter une route `/health` ?

Savoir que le processus du conteneur existe ne suffit pas. Le serveur peut être démarré alors que Symfony ne parvient plus à répondre.

Nous avons ajouté une route très légère :

```php
#[Route('/health', name: 'app_health', methods: ['GET'])]
public function health(): JsonResponse
{
    return new JsonResponse([
        'status' => 'ok',
        'application' => 'franken-symfony',
        'mode' => getenv('DEMO_MODE') ?: 'inconnu',
    ]);
}
```

Les deux modes renvoient une réponse différente :

```json
{
  "status": "ok",
  "application": "franken-symfony",
  "mode": "classique"
}
```

```json
{
  "status": "ok",
  "application": "franken-symfony",
  "mode": "worker"
}
```

Les URLs sont :

- <http://localhost:8384/health> pour le mode classique ;
- <http://localhost:8385/health> pour le mode Worker.

## Configurer le healthcheck Docker

Pour éviter de répéter la configuration, Docker Compose utilise une ancre YAML :

```yaml
x-healthcheck: &healthcheck
  test:
    - CMD
    - php
    - -r
    - "exit(false === @file_get_contents('http://localhost/health') ? 1 : 0);"
  interval: 10s
  timeout: 3s
  retries: 3
  start_period: 10s
```

Chaque service réutilise ensuite ce bloc :

```yaml
healthcheck: *healthcheck
```

Docker appelle la route toutes les dix secondes. Si trois contrôles consécutifs échouent après la période de démarrage, le conteneur passe dans l’état `unhealthy`.

## Vérifier l’état des services

```bash
docker compose ps
```

Résultat obtenu :

```text
php-demo          Up (healthy)   0.0.0.0:8282->80/tcp
symfony-classic   Up (healthy)   0.0.0.0:8384->80/tcp
symfony-worker    Up (healthy)   0.0.0.0:8385->80/tcp
```

Nous pouvons aussi valider directement la syntaxe du Caddyfile :

```bash
docker compose exec symfony-classic \
  frankenphp validate \
  --config /etc/frankenphp/Caddyfile \
  --adapter caddyfile
```

Résultat :

```text
Valid configuration
```

## Observer FrankenPHP avec les logs d’accès

Pour voir les requêtes reçues, nous avons ajouté ce bloc dans le site Caddy :

```caddyfile
log {
    output stdout
    format console
}
```

Les logs sont ensuite visibles avec :

```bash
docker compose logs -f symfony-worker
```

Une requête vers `/products` affiche notamment :

```text
method: GET
uri: /products
duration: 0.018
size: 52773
status: 200
```

Nous pouvons ainsi connaître l’URL appelée, la méthode HTTP, le code de réponse, la durée et la taille du contenu. L’en-tête `X-Powered-By: PHP/8.5.11` permet également de confirmer qu’une réponse est passée par PHP, alors qu’un fichier statique comme `test.txt` est servi directement par Caddy.

## Le problème du code conservé en mémoire

Le mode Worker charge Symfony une fois, puis le conserve en mémoire. Cette optimisation a une conséquence importante pendant le développement.

Nous avons créé une route renvoyant une version du code :

```php
#[Route('/demo/code-version', methods: ['GET'])]
public function codeVersion(): JsonResponse
{
    return new JsonResponse([
        'version' => 1,
        'mode' => getenv('DEMO_MODE'),
    ]);
}
```

Après avoir remplacé `1` par `2` sans redémarrer les conteneurs, nous avons obtenu :

```text
Mode classique → version 2
Mode Worker    → version 1
```

Le mode classique relit le fichier lors de la nouvelle requête. Le Worker continue d’utiliser la classe PHP déjà chargée en mémoire.

## Recharger les Workers avec `watch`

Pour le développement, FrankenPHP peut surveiller les fichiers et renouveler automatiquement ses threads PHP :

```yaml
FRANKENPHP_CONFIG: |
  worker {
    file ./public/index.php
    num 10
    watch
  }
```

Après une nouvelle modification du contrôleur, FrankenPHP a écrit :

```text
filesystem changes detected
rebooting all PHP threads
thread reboot finished
```

Le renouvellement des threads a pris environ 68 millisecondes. Le conteneur et Caddy sont restés actifs ; seuls les threads PHP et les Kernels Symfony ont été recréés.

```text
Conteneur Docker → reste actif
Caddy             → reste actif
FrankenPHP        → reste actif
Threads PHP       → renouvelés
Symfony           → rechargé
```

`watch` recharge le code côté serveur. Il ne rafraîchit pas automatiquement la page du navigateur : il faut encore utiliser F5. Le rechargement automatique du navigateur est une fonctionnalité distincte.

## Ce que cette étape nous apporte

Nous avons maintenant une configuration :

- visible dans Git ;
- identique pour les trois services ;
- validable automatiquement ;
- capable d’activer le Worker avec une variable ;
- compressée ;
- accompagnée de contrôles de santé applicatifs ;
- observable grâce aux logs d’accès ;
- capable de recharger automatiquement les Workers en développement.

Le laboratoire reste simple, mais son fonctionnement n’est plus caché dans l’image Docker.

## Limites actuelles

Nous sommes encore dans un environnement de développement :

- le code est monté depuis la machine hôte ;
- `APP_ENV` vaut `dev` ;
- le profiler Symfony est actif ;
- le trafic local utilise HTTP ;
- aucune image applicative immuable n’est encore construite.

Ces limites sont volontaires. La prochaine étape consistera à construire une image Docker dédiée contenant le code, les dépendances Composer et la configuration Caddy.

## Ressources

- Code source : <https://github.com/amine-betari/frankenphp-formation>
- Configuration FrankenPHP : <https://frankenphp.dev/docs/config/>
- Symfony avec FrankenPHP : <https://frankenphp.dev/docs/symfony/>
- Caddyfile : <https://caddyserver.com/docs/caddyfile>
