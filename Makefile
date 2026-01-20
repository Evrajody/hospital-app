.PHONY: help build up start stop restart status ps logs shell composer artisan migrate migrate-fresh migrate-rollback seed db-fresh install setup test test-coverage clear-cache clear-logs clean perm down destroy

# Variables
DOCKER_COMPOSE = docker-compose
EXEC_APP = $(DOCKER_COMPOSE) exec app
EXEC_DB = $(DOCKER_COMPOSE) exec db

# Colors for output
GREEN = \033[0;32m
YELLOW = \033[0;33m
RED = \033[0;31m
NC = \033[0m # No Color

##@ Aide

help: ## Afficher cette aide
	@echo "$(GREEN)Hospital App - Commandes disponibles:$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf ""} /^[a-zA-Z_-]+:.*?##/ { printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(GREEN)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Docker - Gestion des conteneurs

build: ## Construire les images Docker
	@echo "$(GREEN)Construction des images Docker...$(NC)"
	$(DOCKER_COMPOSE) build --no-cache

up: ## Démarrer les conteneurs en arrière-plan
	@echo "$(GREEN)Démarrage des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) up -d

start: up ## Alias pour 'up' - Démarrer l'application

stop: ## Arrêter les conteneurs
	@echo "$(YELLOW)Arrêt des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) stop

down: ## Arrêter et supprimer les conteneurs
	@echo "$(YELLOW)Arrêt et suppression des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) down

restart: ## Redémarrer les conteneurs
	@echo "$(YELLOW)Redémarrage des conteneurs...$(NC)"
	$(DOCKER_COMPOSE) restart

destroy: ## Détruire tout (conteneurs, volumes, images)
	@echo "$(RED)Destruction complète de l'environnement...$(NC)"
	$(DOCKER_COMPOSE) down -v --rmi all --remove-orphans

##@ Monitoring

status: ## Afficher le statut des conteneurs
	@echo "$(GREEN)Statut des conteneurs:$(NC)"
	$(DOCKER_COMPOSE) ps

ps: status ## Alias pour 'status'

logs: ## Afficher les logs de tous les conteneurs
	$(DOCKER_COMPOSE) logs -f

logs-app: ## Afficher les logs du conteneur app
	$(DOCKER_COMPOSE) logs -f app

logs-nginx: ## Afficher les logs du conteneur nginx
	$(DOCKER_COMPOSE) logs -f nginx

logs-db: ## Afficher les logs du conteneur db
	$(DOCKER_COMPOSE) logs -f db

stats: ## Afficher les statistiques des conteneurs
	docker stats

##@ Installation et Configuration

install: ## Installation complète de l'application
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

shell: ## Accéder au shell du conteneur app
	$(EXEC_APP) bash

shell-root: ## Accéder au shell du conteneur app en tant que root
	$(DOCKER_COMPOSE) exec -u root app bash

shell-db: ## Accéder au shell PostgreSQL
	$(EXEC_DB) psql -U hospital_user -d hospital_db

##@ Composer

composer: ## Exécuter une commande composer (ex: make composer cmd="require package")
	$(EXEC_APP) composer $(cmd)

composer-install: ## Installer les dépendances composer
	$(EXEC_APP) composer install

composer-update: ## Mettre à jour les dépendances composer
	$(EXEC_APP) composer update

composer-dump: ## Regénérer l'autoloader
	$(EXEC_APP) composer dump-autoload

##@ Artisan

artisan: ## Exécuter une commande artisan (ex: make artisan cmd="route:list")
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

migrate-fresh: ## Réinitialiser la base de données et exécuter les migrations
	@echo "$(YELLOW)Réinitialisation de la base de données...$(NC)"
	$(EXEC_APP) php artisan migrate:fresh

migrate-rollback: ## Annuler la dernière migration
	$(EXEC_APP) php artisan migrate:rollback

migrate-reset: ## Annuler toutes les migrations
	$(EXEC_APP) php artisan migrate:reset

migrate-status: ## Afficher le statut des migrations
	$(EXEC_APP) php artisan migrate:status

seed: ## Exécuter les seeders
	$(EXEC_APP) php artisan db:seed

db-fresh: ## Réinitialiser la base avec les seeders
	@echo "$(YELLOW)Réinitialisation complète de la base de données...$(NC)"
	$(EXEC_APP) php artisan migrate:fresh --seed

db-backup: ## Créer une sauvegarde de la base de données
	@echo "$(GREEN)Sauvegarde de la base de données...$(NC)"
	$(EXEC_DB) pg_dump -U hospital_user hospital_db > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Sauvegarde créée!$(NC)"

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

test: ## Exécuter les tests
	$(EXEC_APP) php artisan test

test-coverage: ## Exécuter les tests avec couverture
	$(EXEC_APP) php artisan test --coverage

test-filter: ## Exécuter un test spécifique (ex: make test-filter name="TestName")
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

rebuild: ## Reconstruire et redémarrer l'application
	@echo "$(GREEN)Reconstruction de l'application...$(NC)"
	$(MAKE) down
	$(MAKE) build
	$(MAKE) up
	@echo "$(GREEN)✓ Reconstruction terminée!$(NC)"

##@ Production

prod-build: ## Build pour la production
	@echo "$(GREEN)Build de production...$(NC)"
	$(EXEC_APP) composer install --no-dev --optimize-autoloader
	$(EXEC_APP) php artisan config:cache
	$(EXEC_APP) php artisan route:cache
	$(EXEC_APP) php artisan view:cache
	@echo "$(GREEN)✓ Build de production terminé!$(NC)"

prod-deploy: ## Déployer en production
	@echo "$(GREEN)Déploiement en production...$(NC)"
	$(MAKE) down
	$(MAKE) build
	$(MAKE) up
	$(MAKE) prod-build
	$(MAKE) migrate
	@echo "$(GREEN)✓ Déploiement terminé!$(NC)"
