.PHONY: help deploy deploy-init deploy-quick prod-init-dirs prod-backup rebuild rebuild-prod rollback maintenance-on maintenance-off build up start stop restart status ps logs shell composer artisan migrate migrate-fresh migrate-rollback seed db-fresh db-backup db-restore install setup test test-db test-coverage test-filter clear-cache clear-logs clean perm down destroy

# =============================================================================
# Variables
# =============================================================================
DOCKER_COMPOSE     = docker compose
# --env-file .env.production : compose lit ce fichier pour l'interpolation (DB_PASSWORD, APP_PORT…)
# au lieu du .env par défaut. Toutes les cibles prod en héritent.
DOCKER_COMPOSE_PROD = docker compose --env-file .env.production -f docker-compose.prod.yml
EXEC_APP           = $(DOCKER_COMPOSE) exec app
EXEC_APP_PROD      = $(DOCKER_COMPOSE_PROD) exec app
EXEC_DB            = $(DOCKER_COMPOSE) exec db
EXEC_DB_PROD       = $(DOCKER_COMPOSE_PROD) exec db
BACKUP_DIR         = backups
TIMESTAMP          = $(shell date +%Y%m%d_%H%M%S)

# Colors
GREEN  = \033[0;32m
YELLOW = \033[0;33m
RED    = \033[0;31m
BLUE   = \033[0;34m
BOLD   = \033[1m
NC     = \033[0m

##@ Aide

help: ## Afficher cette aide
	@echo "$(BOLD)$(GREEN)Sysgef - Commandes disponibles:$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf ""} /^[a-zA-Z_-]+:.*?##/ { printf "  $(YELLOW)%-22s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(BOLD)$(GREEN)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
	@echo ""
	@echo "$(BOLD)$(BLUE)>>> Commande principale : make deploy$(NC)"

# =============================================================================
##@ PRODUCTION — Déploiement
# =============================================================================

deploy: ## Déploiement complet en UNE commande (backup + build + migrate + cache)
	@echo ""
	@echo "$(BOLD)$(GREEN)╔══════════════════════════════════════════════════════╗$(NC)"
	@echo "$(BOLD)$(GREEN)║          DEPLOIEMENT SYSGEF                         ║$(NC)"
	@echo "$(BOLD)$(GREEN)╚══════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@# --- Étape 1 : Vérification .env.production ---
	@echo "$(BLUE)[1/7]$(NC) Vérification de la configuration..."
	@if [ ! -f .env.production ]; then \
		echo "$(RED)ERREUR: .env.production introuvable!$(NC)"; \
		echo "  → Copier et configurer : cp .env.production.example .env.production"; \
		exit 1; \
	fi
	@if grep -q "CHANGEZ_MOI" .env.production; then \
		echo "$(RED)ERREUR: Mot de passe par défaut détecté dans .env.production!$(NC)"; \
		echo "  → Modifier DB_PASSWORD dans .env.production"; \
		exit 1; \
	fi
	@if grep -q "^APP_KEY=$$" .env.production; then \
		echo "$(YELLOW)APP_KEY vide — génération automatique...$(NC)"; \
		APP_KEY=$$(php -r "echo 'base64:' . base64_encode(random_bytes(32));" 2>/dev/null || openssl rand -base64 32 | sed 's/^/base64:/'); \
		sed -i "s|^APP_KEY=$$|APP_KEY=$$APP_KEY|" .env.production; \
		echo "  $(GREEN)✓ APP_KEY générée$(NC)"; \
	fi
	@echo "  $(GREEN)✓ Configuration OK$(NC)"
	@echo ""
	@# --- Étape 2 : Backup BDD (si conteneur DB actif) ---
	@echo "$(BLUE)[2/7]$(NC) Sauvegarde de la base de données..."
	@mkdir -p $(BACKUP_DIR)
	@if docker ps --format '{{.Names}}' | grep -q hospital-db; then \
		$(EXEC_DB_PROD) pg_dump -U hospital_user hospital_db > $(BACKUP_DIR)/pre-deploy_$(TIMESTAMP).sql 2>/dev/null && \
		echo "  $(GREEN)✓ Backup créé : $(BACKUP_DIR)/pre-deploy_$(TIMESTAMP).sql$(NC)" || \
		echo "  $(YELLOW)⚠ Backup ignoré (première installation ou BDD vide)$(NC)"; \
	else \
		echo "  $(YELLOW)⚠ Pas de conteneur DB actif — backup ignoré$(NC)"; \
	fi
	@# Rotation : ne conserver que les 2 sauvegardes pré-déploiement les plus récentes
	@ls -t $(BACKUP_DIR)/pre-deploy_*.sql 2>/dev/null | tail -n +3 | xargs -r rm -f || true
	@echo ""
	@# --- Étape 3 : Mode maintenance (si app déjà active) ---
	@echo "$(BLUE)[3/7]$(NC) Activation du mode maintenance..."
	@if docker ps --format '{{.Names}}' | grep -q hospital-app; then \
		$(EXEC_APP_PROD) php artisan down --render="errors::503" 2>/dev/null && \
		echo "  $(GREEN)✓ Mode maintenance activé$(NC)" || \
		echo "  $(YELLOW)⚠ Pas de conteneur app actif — ignoré$(NC)"; \
	else \
		echo "  $(YELLOW)⚠ Première installation — pas de maintenance nécessaire$(NC)"; \
	fi
	@echo ""
	@# --- Étape 4 : Build des images Docker (multi-stage : assets + PHP) ---
	@echo "$(BLUE)[4/7]$(NC) Construction des images (assets Vite + PHP + Composer)..."
	$(DOCKER_COMPOSE_PROD) build
	@echo "  $(GREEN)✓ Images construites$(NC)"
	@echo ""
	@# --- Étape 5 : Démarrage des conteneurs ---
	@echo "$(BLUE)[5/7]$(NC) Démarrage des conteneurs..."
	@echo "  $(BLUE)→ Préparation des dossiers de données (bind mounts voisins)...$(NC)"
	@sh docker/prod-init-dirs.sh
	$(DOCKER_COMPOSE_PROD) up -d
	@echo "  $(GREEN)✓ Conteneurs démarrés$(NC)"
	@echo ""
	@# --- Étape 6 : Migrations + Optimisations Laravel ---
	@echo "$(BLUE)[6/7]$(NC) Migrations et optimisation Laravel..."
	@sleep 5
	$(EXEC_APP_PROD) php artisan migrate --force
	@# On VIDE d'abord tous les caches (config/route/view/app/compiled) pour ne rien
	@# garder de périmé du déploiement précédent, PUIS on reconstruit des caches neufs.
	$(EXEC_APP_PROD) php artisan optimize:clear
	$(EXEC_APP_PROD) php artisan config:cache
	$(EXEC_APP_PROD) php artisan route:cache
	$(EXEC_APP_PROD) php artisan view:cache
	$(EXEC_APP_PROD) php artisan storage:link 2>/dev/null || true
	@# Le worker est déjà recréé par `up -d` (nouvelle image) ; on force un redémarrage
	@# explicite pour garantir qu'aucun ancien process queue:work ne tourne avec du code périmé.
	$(DOCKER_COMPOSE_PROD) up -d --no-deps worker
	@echo "  $(GREEN)✓ Laravel optimisé (caches reconstruits à neuf)$(NC)"
	@echo ""
	@# --- Étape 7 : Désactivation du mode maintenance ---
	@echo "$(BLUE)[7/7]$(NC) Remise en ligne..."
	$(EXEC_APP_PROD) php artisan up
	@echo ""
	@echo "$(BOLD)$(GREEN)╔══════════════════════════════════════════════════════╗$(NC)"
	@echo "$(BOLD)$(GREEN)║  ✓ DEPLOIEMENT TERMINE AVEC SUCCES                  ║$(NC)"
	@echo "$(BOLD)$(GREEN)╚══════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@echo "  $(BLUE)→ Application :$(NC) http://localhost:$${APP_PORT:-8080}"
	@echo "  $(BLUE)→ Logs        :$(NC) make logs-prod"
	@echo "  $(BLUE)→ Statut      :$(NC) make status-prod"
	@echo ""

deploy-init: ## Premier déploiement (génère APP_KEY + deploy)
	@echo "$(GREEN)Initialisation du premier déploiement...$(NC)"
	@if [ ! -f .env.production ]; then \
		echo "$(YELLOW)Création de .env.production depuis le template...$(NC)"; \
		cp .env.production .env.production 2>/dev/null || true; \
	fi
	@# Générer APP_KEY si vide
	@if grep -q "APP_KEY=$$" .env.production; then \
		echo "$(GREEN)Génération de APP_KEY...$(NC)"; \
		APP_KEY=$$(php -r "echo 'base64:' . base64_encode(random_bytes(32));"); \
		sed -i "s|APP_KEY=|APP_KEY=$$APP_KEY|" .env.production; \
		echo "  $(GREEN)✓ APP_KEY générée$(NC)"; \
	fi
	$(MAKE) deploy

deploy-quick: ## Redéploiement rapide (rebuild + restart, sans backup)
	@echo "$(GREEN)Redéploiement rapide...$(NC)"
	$(DOCKER_COMPOSE_PROD) build
	@sh docker/prod-init-dirs.sh
	$(DOCKER_COMPOSE_PROD) up -d
	@sleep 5
	$(EXEC_APP_PROD) php artisan migrate --force
	@# Clear puis re-cache (au lieu de `optimize` seul) → aucun cache périmé conservé.
	$(EXEC_APP_PROD) php artisan optimize:clear
	$(EXEC_APP_PROD) php artisan config:cache
	$(EXEC_APP_PROD) php artisan route:cache
	$(EXEC_APP_PROD) php artisan view:cache
	$(DOCKER_COMPOSE_PROD) up -d --no-deps worker
	@echo "$(GREEN)✓ Redéploiement rapide terminé$(NC)"

rollback: ## Restaurer le dernier backup (après un deploy raté)
	@echo "$(YELLOW)Recherche du dernier backup...$(NC)"
	@LATEST=$$(ls -t $(BACKUP_DIR)/pre-deploy_*.sql 2>/dev/null | head -1); \
	if [ -z "$$LATEST" ]; then \
		echo "$(RED)Aucun backup trouvé dans $(BACKUP_DIR)/$(NC)"; \
		exit 1; \
	fi; \
	echo "$(YELLOW)Restauration de : $$LATEST$(NC)"; \
	docker cp $$LATEST hospital-db:/tmp/restore.sql; \
	$(EXEC_DB_PROD) psql -U hospital_user -d hospital_db -f /tmp/restore.sql; \
	echo "$(GREEN)✓ Base de données restaurée$(NC)"

maintenance-on: ## Activer le mode maintenance
	$(EXEC_APP_PROD) php artisan down --render="errors::503"
	@echo "$(YELLOW)✓ Mode maintenance activé$(NC)"

maintenance-off: ## Désactiver le mode maintenance
	$(EXEC_APP_PROD) php artisan up
	@echo "$(GREEN)✓ Application en ligne$(NC)"

##@ Production — Monitoring

status-prod: ## Statut des conteneurs de production
	$(DOCKER_COMPOSE_PROD) ps

logs-prod: ## Logs de production (tous les conteneurs)
	$(DOCKER_COMPOSE_PROD) logs -f

logs-prod-app: ## Logs du conteneur app (production)
	$(DOCKER_COMPOSE_PROD) logs -f app

logs-prod-nginx: ## Logs du conteneur nginx (production)
	$(DOCKER_COMPOSE_PROD) logs -f nginx

shell-prod: ## Shell dans le conteneur app de production
	$(EXEC_APP_PROD) sh

down-prod: ## Arrêter la production
	$(DOCKER_COMPOSE_PROD) down

prod-init-dirs: ## Créer les dossiers de données bind-mount (voisins du projet) avec les bons droits
	@sh docker/prod-init-dirs.sh

# =============================================================================
##@ DEVELOPPEMENT — Docker
# =============================================================================

build: ## Construire les images Docker (dev)
	@echo "$(GREEN)Construction des images Docker...$(NC)"
	$(DOCKER_COMPOSE) build

up: ## Démarrer les conteneurs en arrière-plan (dev)
	@echo "$(GREEN)Démarrage des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) up -d

start: up ## Alias pour 'up'

stop: ## Arrêter les conteneurs (dev)
	@echo "$(YELLOW)Arrêt des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) stop

down: ## Arrêter et supprimer les conteneurs (dev)
	@echo "$(YELLOW)Arrêt et suppression des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) down

restart: ## Redémarrer les conteneurs (dev)
	@echo "$(YELLOW)Redémarrage des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) restart

destroy: ## Détruire tout (conteneurs, volumes, images)
	@echo "$(RED)Destruction complète de l'environnement...$(NC)"
	$(DOCKER_COMPOSE) down -v --rmi all --remove-orphans

##@ Développement — Monitoring

status: ## Statut des conteneurs (dev)
	@echo "$(GREEN)Statut des conteneurs:$(NC)"
	$(DOCKER_COMPOSE) ps

ps: status ## Alias pour 'status'

logs: ## Logs de tous les conteneurs (dev)
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Logs du conteneur app (dev)
	$(DOCKER_COMPOSE) logs -f app

logs-nginx: ## Logs du conteneur nginx (dev)
	$(DOCKER_COMPOSE) logs -f nginx

logs-db: ## Logs du conteneur db (dev)
	$(DOCKER_COMPOSE) logs -f db

stats: ## Statistiques des conteneurs
	docker stats

##@ Installation et Configuration

install: ## Installation complète (dev)
	@echo "$(GREEN)Installation de l'application...$(NC)"
	$(DOCKER_COMPOSE) up -d --build
	$(EXEC_APP) composer install
	@if [ ! -f .env ]; then cp .env.example .env; fi
	$(EXEC_APP) php artisan key:generate
	$(EXEC_APP) php artisan storage:link
	$(EXEC_APP) php artisan migrate
	@echo "$(GREEN)✓ Installation terminée!$(NC)"
	@echo "$(GREEN)→ Application accessible sur: http://localhost:8080$(NC)"

setup: install ## Alias pour 'install'

init: install ## Alias pour 'install'

##@ Shell et accès

shell: ## Shell du conteneur app (dev)
	$(EXEC_APP) bash

shell-root: ## Shell root du conteneur app
	$(DOCKER_COMPOSE) exec -u root app bash

shell-db: ## Shell PostgreSQL
	$(EXEC_DB) psql -U hospital_user -d hospital_db

##@ Composer

composer: ## Commande composer (ex: make composer cmd="require package")
	$(EXEC_APP) composer $(cmd)

composer-install: ## Installer les dépendances composer
	$(EXEC_APP) composer install

composer-update: ## Mettre à jour les dépendances composer
	$(EXEC_APP) composer update

composer-dump: ## Regénérer l'autoloader
	$(EXEC_APP) composer dump-autoload

##@ Artisan

artisan: ## Commande artisan (ex: make artisan cmd="route:list")
	$(EXEC_APP) php artisan $(cmd)

key-generate: ## Générer une nouvelle clé d'application
	$(EXEC_APP) php artisan key:generate

optimize: ## Optimiser l'application
	$(EXEC_APP) php artisan optimize

route-list: ## Lister toutes les routes
	$(EXEC_APP) php artisan route:list

tinker: ## Ouvrir Laravel Tinker
	$(EXEC_APP) php artisan tinker

##@ Base de données

migrate: ## Exécuter les migrations
	@echo "$(GREEN)Exécution des migrations...$(NC)"
	$(EXEC_APP) php artisan migrate

migrate-fresh: ## Réinitialiser la BDD et exécuter les migrations
	@echo "$(YELLOW)Réinitialisation de la base de données...$(NC)"
	$(EXEC_APP) php artisan migrate:fresh

migrate-rollback: ## Annuler la dernière migration
	$(EXEC_APP) php artisan migrate:rollback

migrate-reset: ## Annuler toutes les migrations
	$(EXEC_APP) php artisan migrate:reset

migrate-status: ## Statut des migrations
	$(EXEC_APP) php artisan migrate:status

seed: ## Exécuter les seeders
	$(EXEC_APP) php artisan db:seed

db-fresh: ## Réinitialiser la base avec les seeders
	@echo "$(YELLOW)Réinitialisation complète de la base de données...$(NC)"
	$(EXEC_APP) php artisan migrate:fresh --seed

db-backup: ## Sauvegarder la base de données
	@echo "$(GREEN)Sauvegarde de la base de données...$(NC)"
	@mkdir -p $(BACKUP_DIR)
	$(EXEC_DB) pg_dump -U hospital_user hospital_db > $(BACKUP_DIR)/backup_$(TIMESTAMP).sql
	@# Rotation : ne conserver que les 2 sauvegardes les plus récentes
	@ls -t $(BACKUP_DIR)/backup_*.sql 2>/dev/null | tail -n +3 | xargs -r rm -f || true
	@echo "$(GREEN)✓ Sauvegarde créée : $(BACKUP_DIR)/backup_$(TIMESTAMP).sql (rotation : 2 conservées)$(NC)"

prod-backup: ## Sauvegarde locale de la base en production (rotation : 2 dernières conservées)
	@mkdir -p $(BACKUP_DIR)
	@$(EXEC_DB_PROD) pg_dump -U hospital_user hospital_db > $(BACKUP_DIR)/prod_$(TIMESTAMP).sql
	@ls -t $(BACKUP_DIR)/prod_*.sql 2>/dev/null | tail -n +3 | xargs -r rm -f || true
	@echo "$(GREEN)✓ Sauvegarde prod : $(BACKUP_DIR)/prod_$(TIMESTAMP).sql (rotation : 2 conservées)$(NC)"

db-restore: ## Restaurer un backup (ex: make db-restore file=backups/backup.sql)
	@echo "$(GREEN)Restauration de $(file)...$(NC)"
	@if [ -z "$(file)" ]; then echo "$(RED)Usage: make db-restore file=backups/backup.sql$(NC)"; exit 1; fi
	@if [ ! -f $(file) ]; then echo "$(RED)Fichier $(file) introuvable!$(NC)"; exit 1; fi
	docker cp $(file) hospital-db:/tmp/restore.sql
	$(EXEC_DB) psql -U hospital_user -d hospital_db -f /tmp/restore.sql
	@echo "$(GREEN)✓ Restauration terminée!$(NC)"

db-import: ## Importer database_export.sql dans PostgreSQL
	@echo "$(GREEN)Import du fichier database_export.sql...$(NC)"
	@if [ ! -f database_export.sql ]; then echo "$(RED)Erreur: database_export.sql introuvable!$(NC)"; exit 1; fi
	docker cp database_export.sql hospital-db:/tmp/database_export.sql
	$(EXEC_DB) psql -U hospital_user -d hospital_db -f /tmp/database_export.sql
	@echo "$(GREEN)✓ Import terminé!$(NC)"

db-import-ohada: ## Importer le plan comptable OHADA
	@echo "$(GREEN)Import du plan comptable OHADA...$(NC)"
	@if [ ! -f plan_comptable_ohada.sql ]; then echo "$(RED)Erreur: plan_comptable_ohada.sql introuvable!$(NC)"; exit 1; fi
	docker cp plan_comptable_ohada.sql hospital-db:/tmp/plan_comptable_ohada.sql
	$(EXEC_DB) psql -U hospital_user -d hospital_db -f /tmp/plan_comptable_ohada.sql
	@echo "$(GREEN)✓ Plan comptable OHADA importé avec succès!$(NC)"

##@ Tests

test-db: ## Créer la base de test (hospital_test) si absente
	@$(EXEC_DB) psql -U hospital_user -d hospital_db -tc "SELECT 1 FROM pg_database WHERE datname='hospital_test'" | grep -q 1 || \
		$(EXEC_DB) psql -U hospital_user -d hospital_db -c "CREATE DATABASE hospital_test;"

test: test-db ## Exécuter les tests (PostgreSQL : base hospital_test)
	$(EXEC_APP) php artisan test

test-coverage: test-db ## Tests avec couverture
	$(EXEC_APP) php artisan test --coverage

test-filter: test-db ## Test spécifique (ex: make test-filter name="TestName")
	$(EXEC_APP) php artisan test --filter=$(name)

##@ Cache et nettoyage

clear-cache: ## Nettoyer tous les caches
	@echo "$(GREEN)Nettoyage des caches...$(NC)"
	$(EXEC_APP) php artisan cache:clear
	$(EXEC_APP) php artisan config:clear
	$(EXEC_APP) php artisan route:clear
	$(EXEC_APP) php artisan view:clear
	@echo "$(GREEN)✓ Caches nettoyés!$(NC)"

clear-logs: ## Nettoyer les logs
	@echo "$(GREEN)Nettoyage des logs...$(NC)"
	$(EXEC_APP) rm -rf storage/logs/*.log
	@echo "$(GREEN)✓ Logs nettoyés!$(NC)"

clean: ## Nettoyage complet (cache, logs, compiled)
	@echo "$(GREEN)Nettoyage complet...$(NC)"
	$(EXEC_APP) php artisan optimize:clear
	$(EXEC_APP) rm -rf storage/logs/*.log
	$(EXEC_APP) rm -rf bootstrap/cache/*.php
	@echo "$(GREEN)✓ Nettoyage terminé!$(NC)"

##@ Permissions

perm: ## Corriger les permissions des fichiers
	@echo "$(GREEN)Correction des permissions...$(NC)"
	$(DOCKER_COMPOSE) exec -u root app chown -R hospital:hospital /var/www
	$(DOCKER_COMPOSE) exec -u root app chmod -R 775 /var/www/storage
	$(DOCKER_COMPOSE) exec -u root app chmod -R 775 /var/www/bootstrap/cache
	@echo "$(GREEN)✓ Permissions corrigées!$(NC)"

##@ Développement

dev: ## Démarrer l'environnement de développement
	@echo "$(GREEN)Démarrage de l'environnement de développement...$(NC)"
	$(MAKE) up
	$(MAKE) logs

watch: logs ## Surveiller les logs en temps réel

fresh: ## Redémarrage complet avec base de données fraîche
	@echo "$(YELLOW)Redémarrage complet...$(NC)"
	$(MAKE) down
	$(MAKE) up
	$(MAKE) migrate-fresh
	@echo "$(GREEN)✓ Redémarrage terminé!$(NC)"

rebuild: ## Reconstruire et redémarrer l'application (DEV / pprod — docker-compose.yml)
	@echo "$(GREEN)Reconstruction de l'application (dev)...$(NC)"
	$(MAKE) down
	$(MAKE) build
	$(MAKE) up
	@echo "$(GREEN)✓ Reconstruction terminée!$(NC)"

rebuild-prod: ## Reconstruire et redémarrer la PRODUCTION (docker-compose.prod.yml)
	@echo "$(GREEN)Reconstruction de l'application (PRODUCTION)...$(NC)"
	$(DOCKER_COMPOSE_PROD) down
	$(DOCKER_COMPOSE_PROD) build
	@sh docker/prod-init-dirs.sh
	$(DOCKER_COMPOSE_PROD) up -d
	@echo "$(GREEN)✓ Reconstruction prod terminée!$(NC)"
	@echo "$(YELLOW)→ Pensez aux migrations si nécessaire : make deploy (ou artisan migrate --force)$(NC)"

##@ Migration des données héritées (ancien système Access)

LEGACY_DIR          = olds/migrations
LEGACY_CLIENTS_DB   = $(LEGACY_DIR)/Base Factures Clients.accdb
LEGACY_FSR_DB       = $(LEGACY_DIR)/Base Factures des Fournisseurs.accdb

migrate-legacy-export: ## 1) Exporter les .accdb (olds/migrations) en SQL (nécessite mdbtools)
	@command -v mdb-export >/dev/null 2>&1 || { echo "$(RED)mdbtools requis : sudo pacman -S mdbtools$(NC)"; exit 1; }
	@mkdir -p olds/sql
	@for pair in "$(LEGACY_CLIENTS_DB)|olds/sql/legacy_clients.sql" "$(LEGACY_FSR_DB)|olds/sql/legacy_fournisseurs.sql"; do \
	  DBF="$${pair%%|*}"; OUT="$${pair##*|}"; \
	  if [ ! -f "$$DBF" ]; then echo "$(RED)Introuvable : $$DBF$(NC)"; continue; fi; \
	  echo "$(GREEN)Export $$DBF -> $$OUT$(NC)"; \
	  echo "SET client_encoding='UTF-8';" > "$$OUT"; \
	  mdb-schema "$$DBF" postgres >> "$$OUT" 2>/dev/null || true; \
	  mdb-tables -1 "$$DBF" | while IFS= read -r t; do [ -z "$$t" ] && continue; echo "  - $$t"; mdb-export -D '%Y-%m-%d %H:%M:%S' -I postgres "$$DBF" "$$t" >> "$$OUT"; done; \
	done
	@echo "$(GREEN)✓ Exports générés dans olds/sql/$(NC)"

migrate-legacy-load: ## 2) Charger les exports SQL dans les schémas de staging (legacy_clients / legacy_fsr)
	@CLI=olds/sql/legacy_clients.sql; [ -f "$$CLI" ] || CLI=olds/sql/database_export.sql; \
	echo "$(GREEN)Staging Clients ($$CLI) -> schéma legacy_clients...$(NC)"; \
	( printf "SET datestyle='ISO, MDY';\nDROP SCHEMA IF EXISTS legacy_clients CASCADE; CREATE SCHEMA legacy_clients; SET search_path TO legacy_clients;\n"; \
	  sed -e 's/\xEF\xBB\xBF//g' \
	      -e 's/BOOLEAN/INTEGER/g' \
	      -e 's/-00 00:00:00/-01 00:00:00/g' \
	      -e 's/); VALUES/); ON CONFLICT DO NOTHING VALUES/g' \
	      -e '/^INSERT INTO/{ /ON CONFLICT/!s/;$$/ ON CONFLICT DO NOTHING;/ }' \
	      "$$CLI" ) | $(DOCKER_COMPOSE) exec -T db psql -U hospital_user -d hospital_db -v ON_ERROR_STOP=0 >/dev/null
	@if [ -f olds/sql/legacy_fournisseurs.sql ]; then \
	  echo "$(GREEN)Staging Fournisseurs -> schéma legacy_fsr...$(NC)"; \
	  ( printf "SET datestyle='ISO, MDY';\nDROP SCHEMA IF EXISTS legacy_fsr CASCADE; CREATE SCHEMA legacy_fsr; SET search_path TO legacy_fsr;\n"; \
	    sed -e 's/\xEF\xBB\xBF//g' \
	        -e 's/BOOLEAN/INTEGER/g' \
	        -e 's/-00 00:00:00/-01 00:00:00/g' \
	        -e 's/); VALUES/); ON CONFLICT DO NOTHING VALUES/g' \
	        -e '/^INSERT INTO/{ /ON CONFLICT/!s/;$$/ ON CONFLICT DO NOTHING;/ }' \
	        olds/sql/legacy_fournisseurs.sql ) | $(DOCKER_COMPOSE) exec -T db psql -U hospital_user -d hospital_db -v ON_ERROR_STOP=0 >/dev/null; \
	else echo "$(YELLOW)olds/sql/legacy_fournisseurs.sql absent — lancez 'make migrate-legacy-export'.$(NC)"; fi
	@echo "$(GREEN)✓ Staging chargé.$(NC)"

migrate-legacy-dry: migrate-legacy-load ## 3a) Simulation (aucune écriture) : compte ce qui serait importé
	$(EXEC_APP) php artisan legacy:migrate --dry-run $(if $(only),--only=$(only),) $(if $(except),--except=$(except),)

migrate-legacy: migrate-legacy-load ## 3b) Migration RÉELLE (idempotente). Options : only=… ou except=… (ex. except=users)
	@echo "$(YELLOW)Sauvegarde de la base avant migration...$(NC)"
	$(MAKE) db-backup
	$(EXEC_APP) php artisan legacy:migrate $(if $(only),--only=$(only),) $(if $(except),--except=$(except),)
	@echo "$(GREEN)✓ Migration héritée terminée.$(NC)"
