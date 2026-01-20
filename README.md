# Hospital Management System

Une application de gestion hospitalière construite avec Laravel 11 et Docker.

## Prérequis

- Docker
- Docker Compose

## Installation Rapide

### Avec Make (Recommandé)

```bash
make install
```

Cette commande unique va :
- Construire et démarrer les conteneurs Docker
- Installer les dépendances Composer
- Générer la clé de l'application
- Créer le lien symbolique pour le stockage
- Exécuter les migrations

### Installation Manuelle

Si vous préférez faire l'installation étape par étape :

```bash
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan storage:link
docker-compose exec app php artisan migrate
```

## Accès à l'application

L'application sera accessible à l'adresse: http://localhost:8080

## Services Docker

- **app**: Conteneur PHP-FPM (PHP 8.2)
- **nginx**: Serveur web Nginx
- **db**: Base de données PostgreSQL 16

## Commandes Make

Le projet inclut un Makefile complet pour faciliter la gestion. Voici les commandes principales :

### Commandes Essentielles
```bash
make help          # Voir toutes les commandes disponibles
make start         # Démarrer l'application
make stop          # Arrêter l'application
make restart       # Redémarrer l'application
make logs          # Voir les logs en temps réel
make status        # Voir le statut des conteneurs
```

### Développement
```bash
make shell         # Accéder au shell du conteneur
make artisan cmd="route:list"  # Exécuter une commande artisan
make migrate       # Exécuter les migrations
make test          # Lancer les tests
make clear-cache   # Nettoyer les caches
```

### Base de données
```bash
make db-fresh      # Réinitialiser la base avec les seeders
make db-backup     # Créer une sauvegarde
make shell-db      # Accéder à PostgreSQL
```

**Pour voir la liste complète des commandes**: `make help`

**Documentation complète**: Voir [COMMANDS.md](COMMANDS.md)

### Commandes Docker (alternative)

Si vous préférez utiliser docker-compose directement :

```bash
docker-compose down                              # Arrêter
docker-compose logs -f                           # Logs
docker-compose exec app bash                     # Shell
docker-compose exec app php artisan [commande]   # Artisan
docker-compose exec db psql -U hospital_user -d hospital_db  # PostgreSQL
```

## Configuration de la base de données

- **Hôte**: db
- **Port**: 5432
- **Base de données**: hospital_db
- **Utilisateur**: hospital_user
- **Mot de passe**: password

## Développement

Les fichiers du projet sont montés dans le conteneur, donc tous les changements sont reflétés en temps réel.

## Tests

```bash
docker-compose exec app php artisan test
```
