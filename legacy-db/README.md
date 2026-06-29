# Legacy Database Architecture

Architecture Docker complète pour l'ancienne base de données hospitalière.

## Composants

| Service | Port | Description |
|---------|------|-------------|
| **PostgreSQL** | 5433 | Base de données legacy |
| **Adminer** | 37800 | Interface d'administration web |
| **PostgREST** | 3001 | API REST automatique |
| **Swagger UI** | 37801 | Documentation API |

## Démarrage

```bash
cd legacy-db
docker compose up -d
```

## Accès

- **Adminer**: http://localhost:37800
  - Server: `legacy-db`
  - Username: `legacy_user`
  - Password: `legacy_password`
  - Database: `legacy_hospital`

- **API REST**: http://localhost:3001
  - Clients: GET /api_clients
  - Factures: GET /api_factures
  - Fournisseurs: GET /api_fournisseurs
  - Plan comptable: GET /api_plan_comptable

- **Swagger**: http://localhost:37801

## Tables Sources

### Base Factures Clients (database_export.sql)
- `banque` - Banques
- `bordereau` - Bordereaux de dépôt
- `client` - Clients
- `facture` - Factures
- `imputation` - Imputations comptables
- `reglement` - Règlements

### Base Fournisseurs (legacy_fournisseurs.sql)
- `fournis` - Fournisseurs
- `facfournis` - Factures fournisseurs
- `classecompte` - Classes comptables
- `compteniv1` - Comptes niveau 1
- `compteniv2` - Comptes niveau 2
- `compteniv3` - Comptes niveau 3

### Plan Comptable OHADA (plan_comptable_ohada.sql)
- `plan_comptable_ohada` - Plan comptable complet

## Vues API (PostgREST)

Les vues suivantes sont créées automatiquement pour l'API:

- `api_clients` - Clients avec statistiques facturation
- `api_factures` - Factures avec info client
- `api_fournisseurs` - Fournisseurs avec stats
- `api_plan_comptable` - Plan comptable avec hiérarchie

## Comparaison avec la nouvelle app

Utilisez le script de comparaison:

```bash
./compare-databases.sh
```

## Connexion PostgREST

L'API utilise un rôle anonyme par défaut. Pour des opérations d'écriture, vous devez configurer l'authentification JWT.

### Exemples de requêtes

```bash
# Lister tous les clients
curl http://localhost:3001/api_clients

# Filtrer les factures impayées
curl "http://localhost:3001/api_factures?etatfac=eq.false"

# Rechercher un client par nom
curl "http://localhost:3001/api_clients?rscli=like.*HOPITAL*"

# Plan comptable classe 6
curl "http://localhost:3001/api_plan_comptable?classe=eq.6"

# Pagination
curl "http://localhost:3001/api_clients?limit=10&offset=0"
```

## Arrêt

```bash
docker compose down
```

Pour supprimer les données:
```bash
docker compose down -v
```
