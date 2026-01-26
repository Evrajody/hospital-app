# Plan de Développement - Hôpital de Ménontin

## 📋 Vue d'ensemble du projet

**Objectif :** Refonte complète des applications métiers de l'administration de l'hôpital de Ménontin (migration depuis Microsoft Access vers une architecture web moderne)

**Stack Technique :**
- **Backend :** Laravel 11 + PHP 8.2
- **Frontend :** Vue.js 3 + Inertia.js + Element Plus
- **Build Tool :** Vite
- **Base de données :** PostgreSQL 16
- **Déploiement :** Docker (développement) + Serveur local (production)

**Durée estimée :** 9 semaines + 3 semaines de stabilisation

---

## 🎯 Stratégie de développement

### Phase 1 : Frontend First (Semaines 1-6)
Créer d'abord toutes les interfaces utilisateur avec une excellente UX/UI et des données mockées pour validation visuelle et fonctionnelle avant l'intégration backend.

### Phase 2 : Backend & Intégration (Semaines 7-9)
Une fois les interfaces validées, développer le backend et rendre tout dynamique.

---

## 📊 État d'avancement

### ✅ Étape 0 : Configuration initiale
- [x] Configuration Docker (PHP 8.2 + Nginx + PostgreSQL)
- [x] Installation Laravel 11
- [x] Configuration de base

### 🔄 Étape 1 : Installation Stack Frontend
- [ ] Installation Inertia.js (serveur Laravel)
- [ ] Installation Vue 3 + Vite
- [ ] Installation Element Plus
- [ ] Configuration Tailwind CSS (optionnel)
- [ ] Configuration des alias et imports
- [ ] Test de la configuration

### 🔄 Étape 2 : Structure de base de l'application
- [ ] Création du layout principal (AppLayout.vue)
- [ ] Création du layout d'authentification (AuthLayout.vue)
- [ ] Composant de navigation/sidebar
- [ ] Composant de header avec profil utilisateur
- [ ] Pages d'authentification (Login, Register - UI uniquement)
- [ ] Page Dashboard/Accueil
- [ ] Système de routing Inertia
- [ ] Gestion des menus et permissions (UI)

---

## 📦 Modules applicatifs

### Module 1 : Gestion des Factures Fournisseurs

#### Fonctionnalités UI à créer

**Pages principales :**
- [ ] Liste des fournisseurs (tableau avec recherche, filtres, pagination)
- [ ] Formulaire d'ajout/modification fournisseur
- [ ] Liste des factures fournisseurs (tableau avec recherche, filtres)
- [ ] Formulaire d'enregistrement de facture fournisseur
  - [ ] Sélection fournisseur
  - [ ] Saisie montant HT, TVA (18%), TTC
  - [ ] Gestion escomptes et AIB (1%, 3%, 5%)
  - [ ] Attribution imputation comptable (charges, immo, tiers, banque)
- [ ] Page de règlement de facture
  - [ ] **Règlements multiples** : possibilité de régler une facture en plusieurs fois
  - [ ] Affichage du solde restant après chaque règlement
  - [ ] Historique complet de tous les règlements pour une facture
  - [ ] Sélection mode de paiement (chèque, virement, espèces, etc.)
  - [ ] Sélection de la banque
  - [ ] Calcul automatique des montants (AIB, escompte, net à payer)
  - [ ] Génération mandat de paiement
  - [ ] Génération fiche d'imputation comptable
- [ ] **Marquer une facture comme soldée** (action manuelle indépendante des règlements)
- [ ] Gestion des statuts de facture :
  - [ ] 🔴 Non réglée (aucun paiement)
  - [ ] 🟡 Partiellement réglée (solde > 0)
  - [ ] 🟢 Réglée (solde = 0)
  - [ ] ⚪ Soldée manuellement
- [ ] Page détail facture avec :
  - [ ] Informations complètes de la facture
  - [ ] Tableau des règlements effectués
  - [ ] Solde restant bien visible
  - [ ] Actions : Régler, Marquer comme soldée
- [ ] Gestion du plan comptable
  - [ ] Liste des comptes (charges, immo, tiers, banques)
  - [ ] Ajout rapide de compte
- [ ] **Gestion des banques et mouvements** :
  - [ ] Liste des comptes bancaires avec soldes
  - [ ] Page d'approvisionnement (dépôt)
  - [ ] Historique des mouvements bancaires (entrées/sorties)
  - [ ] Décaissement automatique lors des règlements
  - [ ] Suivi du solde en temps réel

**Rapports et états (avec données mockées) :**
- [ ] Mandat de paiement (PDF)
- [ ] État de règlement d'une facture
- [ ] Mouvement périodique des factures d'un fournisseur
- [ ] Situation des fournisseurs et comptes fournisseurs (point des dettes)
- [ ] États périodiques des factures fournisseurs réglées
- [ ] Déclaration périodique des AIB
- [ ] Point périodique des pièces comptables
- [ ] Situation périodique des banques
- [ ] État récapitulatif périodique des charges (dépenses)
- [ ] État récapitulatif périodique des investissements (immo)
- [ ] État du plan comptable
- [ ] Bordereau de transmission des règlements
- [ ] Factures et soldes (détails des règlements par facture)
- [ ] Fiche d'imputation comptable (règlement)
- [ ] Fiche d'imputation comptable (enregistrement)
- [ ] **NOUVEAU :** État de déclaration de la TVA par période

**Améliorations demandées :**
- [ ] Gestion dynamique des taux AIB et TVA (paramétrable)
- [ ] Affichage automatique du montant TTC sur les bordereaux
- [ ] Insertion des valeurs AIB sur les états
- [ ] Ajout des comptes de trésorerie sur les bordereaux de règlement

---

### Module 2 : Gestion des Factures Clients

#### Fonctionnalités UI à créer

**Pages principales :**
- [ ] Liste des clients (tableau avec recherche, filtres, pagination)
- [ ] Formulaire d'ajout/modification client
- [ ] Liste des factures clients (tableau avec recherche, filtres)
- [ ] Formulaire d'enregistrement de facture client
  - [ ] Sélection client
  - [ ] Saisie montants
  - [ ] **NOUVEAU :** Champ "Observation"
- [ ] Page de règlement de facture client
  - [ ] Gestion de la banque de dépôt
- [ ] Marquer une facture comme soldée
- [ ] Gestion des dépôts de banques

**Rapports et états (avec données mockées) :**
- [ ] États périodiques des règlements client
- [ ] États périodiques des créances des clients (factures non soldées + nom client)
- [ ] État périodique du brouillard de chèques et imputations comptables
- [ ] Chiffre d'affaires global et par client réalisé
- [ ] **NOUVEAU :** État périodique des créances
- [ ] **NOUVEAU :** États sur les pertes, rejets et régularisations

---

### Module 3 : Plan Comptable

#### Fonctionnalités UI à créer

**Pages principales :**
- [ ] Liste complète du plan comptable (tableau avec recherche, filtres par type)
- [ ] Formulaire d'ajout de compte
  - [ ] Numéro de compte
  - [ ] Libellé
  - [ ] Type (Charge, Immobilisation, Tiers, Banque, Escompte, AIB)
- [ ] Formulaire de modification de compte
- [ ] Vue détaillée d'un compte avec historique

---

### Module 4 : Gestion Utilisateurs et Profils

#### Fonctionnalités UI à créer

**Pages principales :**
- [ ] Liste des utilisateurs (tableau avec recherche, filtres)
- [ ] Formulaire d'ajout/modification utilisateur
- [ ] Gestion des profils d'accès
  - [ ] Administrateur
  - [ ] Comptable
  - [ ] Caissier
  - [ ] Contrôleur
  - [ ] (Autres profils selon besoins)
- [ ] Attribution des permissions par profil
- [ ] Page de profil utilisateur

---

### Module 5 : Tableau de bord et rapports transversaux

#### Fonctionnalités UI à créer

**Pages principales :**
- [ ] Dashboard principal avec KPIs
  - [ ] Total dépenses (charges + immo)
  - [ ] Total créances clients
  - [ ] Total dettes fournisseurs
  - [ ] Situation des banques
  - [ ] Graphiques de tendances
- [ ] Page de rapports centralisée
  - [ ] Sélecteur de type de rapport
  - [ ] Filtres par période
  - [ ] Export PDF/Excel (UI)

---

## 🗄️ Phase Backend (après validation des interfaces)

### Étape 8 : Conception de la base de données

**Tables à créer :**

#### Gestion des comptes
- [ ] `comptes` (plan comptable)
- [ ] `types_compte` (charge, immo, tiers, banque, etc.)

#### Fournisseurs
- [ ] `fournisseurs`
- [ ] `factures_fournisseurs`
- [ ] `reglements_fournisseurs`
- [ ] `imputations_comptables_fournisseurs`

#### Clients
- [ ] `clients`
- [ ] `factures_clients`
- [ ] `reglements_clients`
- [ ] `depots_banques`
- [ ] `imputations_comptables_clients`

#### Paramètres
- [ ] `taux_fiscaux` (AIB, TVA - historisés)
- [ ] `banques`
- [ ] `mouvements_banques`

#### Utilisateurs et permissions
- [ ] `users` (déjà existant Laravel)
- [ ] `roles`
- [ ] `permissions`
- [ ] `role_permission`
- [ ] `user_role`

### Étape 9 : Développement Backend

**Contrôleurs à créer :**
- [ ] FournisseurController
- [ ] FactureFournisseurController
- [ ] ReglementFournisseurController
- [ ] ClientController
- [ ] FactureClientController
- [ ] ReglementClientController
- [ ] CompteController (plan comptable)
- [ ] BanqueController
- [ ] RapportController
- [ ] UserController
- [ ] RoleController

**Services métier :**
- [ ] CalculService (calculs TVA, AIB, escomptes)
- [ ] ImputationComptableService
- [ ] RapportGeneratorService
- [ ] PDFExportService

**Middleware et Policies :**
- [ ] RoleMiddleware
- [ ] PermissionMiddleware
- [ ] FactureFournisseurPolicy
- [ ] FactureClientPolicy
- [ ] UserPolicy

### Étape 10 : Intégration Frontend-Backend

- [ ] Connexion de toutes les pages au backend
- [ ] Gestion des états de chargement
- [ ] Gestion des erreurs
- [ ] Validation des formulaires côté serveur
- [ ] Génération réelle des PDFs
- [ ] Tests d'intégration

---

## 🎨 Composants réutilisables à créer

### Composants UI génériques
- [ ] DataTable.vue (tableau avec pagination, tri, recherche)
- [ ] FormInput.vue (input avec validation)
- [ ] FormSelect.vue (select avec recherche)
- [ ] DatePicker.vue (sélecteur de date/période)
- [ ] Modal.vue (modal générique)
- [ ] ConfirmDialog.vue (dialogue de confirmation)
- [ ] LoadingSpinner.vue
- [ ] EmptyState.vue
- [ ] Card.vue
- [ ] Badge.vue
- [ ] Button.vue (avec variantes)

### Composants métier
- [ ] FournisseurSelector.vue
- [ ] ClientSelector.vue
- [ ] CompteSelector.vue (sélection de compte comptable)
- [ ] BanqueSelector.vue
- [ ] MontantInput.vue (avec calcul auto TVA/AIB)
- [ ] FactureStatus.vue (badge de statut)
- [ ] ImputationComptablePreview.vue
- [ ] PeriodSelector.vue (sélection période pour rapports)

---

## 📅 Planning détaillé

### Semaine 1 : Installation et structure de base
- Installation stack frontend
- Création layouts et navigation
- Pages d'authentification (UI)
- Dashboard principal

### Semaines 2-3 : Module Factures Fournisseurs
- Toutes les pages et formulaires
- Tous les rapports (UI avec mock data)
- Composants réutilisables

### Semaine 4 : Module Factures Clients
- Toutes les pages et formulaires
- Tous les rapports (UI avec mock data)

### Semaine 5 : Plan Comptable + Gestion Utilisateurs
- Pages plan comptable
- Pages gestion utilisateurs et profils

### Semaine 6 : Rapports transversaux et polish UI
- Dashboard avec KPIs
- Page de rapports centralisée
- Amélioration UX/UI globale
- Tests utilisateurs

### Semaine 7 : Base de données et modèles
- Conception complète du schéma
- Création des migrations
- Création des modèles Eloquent
- Seeders pour données de test

### Semaine 8 : Backend - Logique métier
- Création de tous les contrôleurs
- Services métier
- Validation
- Policies et permissions

### Semaine 9 : Intégration et tests
- Connexion frontend-backend
- Génération réelle des PDFs
- Tests et corrections de bugs
- Documentation

### Semaines 10-12 : Stabilisation
- Tests en conditions réelles
- Corrections des anomalies
- Optimisations
- Formation utilisateurs

---

## 🚀 Prochaines étapes immédiates

1. **Installer la stack frontend** (Inertia + Vue 3 + Element Plus)
2. **Créer la structure de base** (layouts, navigation)
3. **Commencer le module Factures Fournisseurs**

---

## 📝 Notes importantes

- **Priorité UX/UI :** Interfaces intuitives, responsive, accessibles
- **Données mockées :** Utiliser des données réalistes pour tous les prototypes
- **Validation itérative :** Valider chaque module avant de passer au suivant
- **Documentation :** Documenter les composants et les flux métier
- **Accessibilité :** Respecter les standards WCAG
- **Performance :** Optimiser le chargement et la réactivité

---

## 🔗 Références

- [Cahier des charges](/Users/ulrich_justice/Downloads/Offre technique et financière pour la refonte des applications métiers_v4 (1).pdf)
- [Documentation Laravel 11](https://laravel.com/docs/11.x)
- [Documentation Inertia.js](https://inertiajs.com/)
- [Documentation Vue 3](https://vuejs.org/)
- [Documentation Element Plus](https://element-plus.org/)

---

**Dernière mise à jour :** 28 décembre 2025
