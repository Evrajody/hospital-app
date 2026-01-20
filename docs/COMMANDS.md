# Guide des Commandes Make

## Commandes Rapides (Quick Start)

```bash
make help          # Afficher toutes les commandes disponibles
make install       # Installation complète (première fois)
make start         # Démarrer l'application
make stop          # Arrêter l'application
make restart       # Redémarrer l'application
make logs          # Voir les logs en temps réel
```

---

## Gestion Docker

| Commande | Description |
|----------|-------------|
| `make build` | Construire les images Docker |
| `make up` ou `make start` | Démarrer les conteneurs |
| `make stop` | Arrêter les conteneurs |
| `make down` | Arrêter et supprimer les conteneurs |
| `make restart` | Redémarrer les conteneurs |
| `make destroy` | Tout détruire (conteneurs, volumes, images) |

---

## Monitoring

| Commande | Description |
|----------|-------------|
| `make status` ou `make ps` | Voir le statut des conteneurs |
| `make logs` | Voir tous les logs |
| `make logs-app` | Logs du conteneur app uniquement |
| `make logs-nginx` | Logs du conteneur nginx uniquement |
| `make logs-db` | Logs de PostgreSQL uniquement |
| `make stats` | Statistiques des conteneurs (CPU, RAM) |
| `make watch` | Surveiller les logs en temps réel |

---

## Installation et Configuration

| Commande | Description |
|----------|-------------|
| `make install` | Installation complète |
| `make setup` | Alias pour install |
| `make init` | Alias pour install |

---

## Accès Shell

| Commande | Description |
|----------|-------------|
| `make shell` | Accéder au conteneur app (bash) |
| `make shell-root` | Accéder au conteneur app en tant que root |
| `make shell-db` | Accéder à PostgreSQL (psql) |
| `make tinker` | Ouvrir Laravel Tinker |

---

## Composer

| Commande | Description |
|----------|-------------|
| `make composer-install` | Installer les dépendances |
| `make composer-update` | Mettre à jour les dépendances |
| `make composer-dump` | Regénérer l'autoloader |
| `make composer cmd="require package/name"` | Exécuter une commande composer personnalisée |

**Exemples:**
```bash
make composer cmd="require laravel/breeze"
make composer cmd="require --dev phpunit/phpunit"
make composer cmd="remove package/name"
```

---

## Artisan

| Commande | Description |
|----------|-------------|
| `make key-generate` | Générer une nouvelle clé APP_KEY |
| `make optimize` | Optimiser l'application |
| `make route-list` | Lister toutes les routes |
| `make artisan cmd="make:controller NomController"` | Exécuter une commande artisan personnalisée |

**Exemples:**
```bash
make artisan cmd="make:controller UserController"
make artisan cmd="make:model Patient -m"
make artisan cmd="make:migration create_appointments_table"
make artisan cmd="make:seeder UserSeeder"
make artisan cmd="queue:work"
```

---

## Base de Données

| Commande | Description |
|----------|-------------|
| `make migrate` | Exécuter les migrations |
| `make migrate-fresh` | Réinitialiser et re-migrer |
| `make migrate-rollback` | Annuler la dernière migration |
| `make migrate-reset` | Annuler toutes les migrations |
| `make migrate-status` | Voir le statut des migrations |
| `make seed` | Exécuter les seeders |
| `make db-fresh` | Reset + migrations + seeders |
| `make db-backup` | Créer une sauvegarde SQL |
| `make db-import` | Importer database_export.sql dans PostgreSQL |

**Workflow typique:**
```bash
# Créer une nouvelle migration
make artisan cmd="make:migration create_patients_table"

# Exécuter la migration
make migrate

# Si besoin de recommencer
make migrate-fresh

# Avec des données de test
make db-fresh
```

---

## Tests

| Commande | Description |
|----------|-------------|
| `make test` | Exécuter tous les tests |
| `make test-coverage` | Tests avec rapport de couverture |
| `make test-filter name="NomDuTest"` | Exécuter un test spécifique |

**Exemples:**
```bash
make test
make test-filter name="UserTest"
make test-coverage
```

---

## Cache et Nettoyage

| Commande | Description |
|----------|-------------|
| `make clear-cache` | Nettoyer tous les caches Laravel |
| `make clear-logs` | Supprimer les fichiers de logs |
| `make clean` | Nettoyage complet (cache + logs + compiled) |

---

## Permissions

| Commande | Description |
|----------|-------------|
| `make perm` | Corriger les permissions des fichiers |

Utilisez cette commande si vous rencontrez des erreurs de permissions.

---

## Développement

| Commande | Description |
|----------|-------------|
| `make dev` | Démarrer l'environnement de dev + logs |
| `make fresh` | Redémarrage complet avec DB fraîche |
| `make rebuild` | Reconstruire et redémarrer |

**Workflow de développement quotidien:**
```bash
# Le matin
make start
make logs

# Créer un nouveau modèle
make artisan cmd="make:model Appointment -mcr"

# Tester
make migrate
make test

# Voir les routes
make route-list

# Le soir
make stop
```

---

## Production

| Commande | Description |
|----------|-------------|
| `make prod-build` | Build optimisé pour la production |
| `make prod-deploy` | Déploiement complet en production |

---

## Scénarios Courants

### Premier démarrage
```bash
make install
# L'app sera disponible sur http://localhost:8080
```

### Développement quotidien
```bash
make start          # Démarrer
make shell          # Travailler dans le conteneur
make migrate        # Après création de migrations
make test           # Tester
make stop           # Arrêter
```

### Problèmes de permissions
```bash
make perm
```

### Réinitialiser la base de données
```bash
make db-fresh
```

### Créer un backup avant des changements importants
```bash
make db-backup
```

### Voir ce qui ne va pas
```bash
make status         # État des conteneurs
make logs           # Voir les erreurs
make logs-app       # Logs spécifiques à l'app
```

### Tout reconstruire de zéro
```bash
make destroy        # Attention: supprime tout!
make install        # Réinstaller
```

---

## Astuces

1. **Toujours commencer par `make help`** pour voir toutes les commandes disponibles

2. **Les logs sont vos amis**: `make logs` pour déboguer

3. **Migrations**: Toujours créer une migration pour les changements de schéma
   ```bash
   make artisan cmd="make:migration nom_de_la_migration"
   ```

4. **Tests**: Tester régulièrement
   ```bash
   make test
   ```

5. **Cache**: Si quelque chose ne fonctionne pas comme prévu, essayez:
   ```bash
   make clear-cache
   ```

6. **Base de données**: Pour repartir de zéro rapidement:
   ```bash
   make db-fresh
   ```
