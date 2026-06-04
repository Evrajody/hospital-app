---
title: "Manuel utilisateur — Profil Administrateur"
subtitle: "Application de Gestion Comptable — Hôpital de Zone de Ménontin (République du Bénin)"
date: "Juin 2026"
lang: fr
---

# 1. Présentation du profil

Le profil **Administrateur** est le profil le plus complet de l'application. Il dispose de **toutes les permissions** : il peut consulter, créer, modifier, supprimer et valider dans tous les modules métier (fournisseurs, clients, factures, règlements, banques, plan comptable), produire tous les rapports, **et** administrer l'application (utilisateurs, rôles & permissions, paramètres de l'établissement, taux fiscaux, journal d'activité).

**Compte de démonstration**

| Élément | Valeur |
|---|---|
| E-mail | `admin@hospital.bj` |
| Mot de passe | `password` |
| Poste | Administrateur Système |

> Par sécurité, changez ce mot de passe dès la première connexion (menu **Mon Profil → Changer le mot de passe**).

## 1.1 Connexion et session

- L'accès se fait via la page de connexion (logo du Ministère de la Santé). On saisit l'e-mail et le mot de passe.
- **Déconnexion automatique** : après **15 minutes d'inactivité**, une fenêtre « Session bientôt expirée » s'affiche **1 minute** avant la coupure. Deux boutons : *Rester connecté* (relance la session) ou *Se déconnecter*. Sans réponse, la déconnexion est automatique.
- La déconnexion manuelle se fait par le menu en haut à droite (nom de l'utilisateur → **Déconnexion**).

# 2. Menus visibles

L'Administrateur voit l'intégralité de la barre latérale :

- **Tableau de bord**
- **Factures Fournisseurs** : Fournisseurs · Factures · Règlements
- **Factures Clients** : Clients · Factures · Règlements · Avances
- **Autres** : Plan Comptable · Banques
- **Rapports** : Rapports Fournisseurs · Rapports Clients · Rapports Banques
- **Paramètres** : Utilisateurs · Rôles & Permissions · Taux Fiscaux · Établissement
- **Journal d'Activité**

# 3. Tableau de bord

Le tableau de bord présente la situation financière en un coup d'œil :

- **4 cartes KPI** : Chiffre d'affaires du mois (avec tendance % vs mois précédent), Factures en attente (réparties client / fournisseur), Dettes fournisseurs (reste à payer, avec tendance), Créances clients (reste à payer, avec tendance).
- **Dernières factures fournisseurs** : les 10 dernières (N° pièce, fournisseur, net à payer, date, statut).
- **Situation des banques** : solde de chaque compte + solde total.
- **Graphiques** : évolution mensuelle Recettes vs Dépenses (12 derniers mois) ; répartition des charges par fournisseur (camembert, top 10 + « Autres »).

# 4. Flux métier (vue d'ensemble)

> L'Administrateur peut exécuter **tous** les flux décrits ci-dessous. Les profils Comptable / Gestionnaire / Utilisateur n'en réalisent qu'une partie selon leurs droits.

## 4.1 Fournisseurs

- **Champs** : raison sociale, type (Médicaments, Équipements, Consommables, Services, Maintenance, Autres), contact, téléphones, e-mail, adresse, ville, pays (défaut Bénin), **IFU (13 chiffres, obligatoire)**, RCCM, et un ou plusieurs **comptes comptables** (classe 401 « Fournisseurs » ou 4812 « Fournisseurs d'investissements », un principal + des supplémentaires).
- **Création / modification** : possibilité de **créer un nouveau compte comptable à la volée** (le numéro doit commencer par 401 ou 4812).
- **Suppression** : refusée si le fournisseur possède au moins une facture (sinon suppression logique).
- **Fiche fournisseur** : 4 statistiques (Nb factures, Total facturé, Payé, Reste à payer) + onglets Informations / Factures / Règlements.

## 4.2 Cycle de vie d'une facture fournisseur

Statuts : **Brouillon → Validée → Partiellement payée → Payée**, plus **Annulée**.

1. **Créer** : la facture naît en *Brouillon*. Numéro de pièce auto au format `PC/AAA/NNNN` (ex. `PC/026/0001`), modifiable.
   - Saisie : montant facture, montant M.O. (base de l'AIB), avoir, assujettissement TVA + taux, taux AIB, références.
   - Calculs automatiques : TVA (informative, « pour le compte de l'État »), TTC, montant AIB = M.O. × taux, **Net à payer = montant − avoir − AIB**. *La TVA n'est jamais versée au fournisseur.*
2. **Imputation comptable** : ventilation en lignes Débit / Crédit (plan OHADA). Débits = charges/immobilisations/personnel (= HT) ; Crédit TVA déductible (445) auto ; Crédits = comptes fournisseurs 401/481 (= TTC). Contrôle d'équilibre (Débits = HT, Crédits = TTC) avant génération des écritures. Un **PDF d'imputation** (paysage) est produit.
3. **Valider** : passe de Brouillon à *Validée* (enregistre le validateur et la date). Une facture validée n'est plus modifiable.
4. **Régler** : voir 4.3.
5. **Marquer comme soldée** : clôture administrative (statut *Payée*, reste à payer = 0, date de solde) **sans** créer de règlement ni de mouvement bancaire.
6. **Annuler** / **Supprimer** : annulation interdite si payée ; suppression interdite s'il existe des règlements ou si la facture n'est plus en brouillon.

## 4.3 Règlement fournisseur (paiement)

- Saisie : date, montant (≤ reste à payer), **mode de paiement** (virement, chèque, espèces, mobile money, carte), référence (n° de chèque / virement), bénéficiaire, compte de trésorerie/banque, compte fournisseur soldé, et possibilité de lignes multi-fournisseurs.
- Numéro de règlement auto au format `REG/AA/NNNNN`.
- **Effets** : si paiement bancaire, le solde du compte est débité (contrôle de solde suffisant, avec possibilité de forcer) ; la facture est mise à jour (montant payé, reste à payer, statut).
- **AIB** : la case « Déclarer l'AIB » retient l'acompte au moment du règlement.
- **Écritures comptables** : générées **explicitement** (« Générer les écritures comptables ») — jamais pour les règlements en espèces.
- **PDF disponibles** : Bordereau / Mandat de règlement (portrait, avec montants en lettres et signatures), Imputation comptable du règlement, État de règlement de la facture.

## 4.4 Clients, factures clients, règlements et avances

- **Client** : nom, téléphone, **IFU (13 chiffres si renseigné)**, type (Société, Divers, Personnel, Autre), adresse, observations, compte comptable (41… ou 424100…, création à la volée possible).
- **Facture client** : référence auto `NNNN/MM/AA`, montant, ristourne → **Net à payer = montant − ristourne**. Statuts : *Non payée → Partielle → Payée*. Action « Marquer comme soldée » disponible. PDF **État de règlement**.
- **Règlement client** : sur une facture, montant ≤ reste à payer ; sources : paiement direct (chèque/espèces, avec dépôt bancaire possible), virement, ou **imputation sur une avance**.
- **Avances clients** : somme reçue d'avance (société émettrice, n° chèque, montant). Statuts *Disponible / Partiellement utilisée / Épuisée*. Consommée lors d'un règlement (même client, montant ≤ solde de l'avance).

## 4.5 Banques

- **Structure** : une **Banque** (nom) contient un ou plusieurs **comptes bancaires** (numéro, compte OHADA de trésorerie classe 5, solde).
- **Approvisionner** : crée un bordereau (référence, date, montant) et **augmente le solde** du compte.
- **Mouvements** : relevé fusionnant entrées (approvisionnements) et sorties (règlements fournisseurs), avec solde reconstitué après chaque opération + détail des bordereaux et des règlements clients imputés.
- **Soldes** : modifiés uniquement par les approvisionnements ; les règlements clients consomment un bordereau sans changer le solde.
- Suppressions protégées (banque/compte/approvisionnement avec mouvements).

## 4.6 Plan comptable OHADA

- Nomenclature par classes (1 Capitaux, 2 Immobilisations, 3 Stocks, 4 Tiers, 5 Trésorerie, 6 Charges, 7 Produits, 8 H.A.O., 9 Analytique).
- Recherche par numéro/libellé, filtres par classe et par source (standard / personnalisé).
- **Création / modification** : numéro unique, libellé, compte parent (le numéro doit commencer par celui du parent) ; classe et niveau déduits automatiquement ; comptes créés marqués « personnalisés ».
- Suppression bloquée si le compte a des sous-comptes.

# 5. Rapports

Tous les rapports proposent généralement : affichage à l'écran, export **PDF**, export **Excel** et **Impression** (aperçu PDF).

- **Rapports Fournisseurs** : Mouvement des factures · Situation des fournisseurs (point des dettes) · État des factures réglées · Factures et Soldes · Déclaration AIB · Déclaration TVA · Point des pièces comptables (PC) · Bordereau de transmission (+ mandats multiples) · Point des charges · Point des investissements.
- **Rapports Clients** : État des règlements · État des créances · Brouillard de chèques & Imputations comptables · Chiffre d'affaires (théorique / physique / écart) · Pertes & Rejets.
- **Rapports Banques** : Situation des banques (trésorerie sur période, résumé ou détail par banque).

# 6. Administration (réservé à l'Administrateur)

## 6.1 Utilisateurs

- Liste : nom, e-mail, poste, téléphone, rôles, statut (actif/inactif).
- **Créer / modifier** : nom, e-mail unique, mot de passe robuste (8 caractères min., majuscule + minuscule + chiffre + caractère spécial), téléphone, poste, **rôles** (multi-sélection), statut.
- **Activer / désactiver** et **supprimer** : impossible sur son **propre** compte.
- Toute opération est journalisée.

## 6.2 Rôles & Permissions

- **4 rôles par défaut** : Administrateur, Comptable, Gestionnaire, Utilisateur.
- Interface en **matrice** (permissions × rôles) ; clic sur une cellule pour accorder/retirer une permission ; boutons « Tout activer / Tout désactiver » par rôle et par module.
- Création / renommage / suppression de rôle (suppression bloquée si le rôle est attribué).

## 6.3 Établissement

- Champs : nom, pays/entité, adresse, téléphone, e-mail, **IFU**, nom du **Directeur**.
- Ces informations alimentent les **en-têtes des PDF** (l'IFU sur les déclarations fiscales ; le Directeur en bas des mandats/bordereaux).
- **La mise à jour est réservée à l'Administrateur.**

## 6.4 Taux fiscaux

- Gestion des taux **TVA** et **AIB** : libellé, taux (0–100 %), actif/inactif, **un seul « par défaut » par type**.
- La TVA par défaut est de **18 %** si rien n'est configuré.

## 6.5 Journal d'activité

- Trace : action, module, description, utilisateur, **adresse IP**, date/heure.
- Filtres : recherche, utilisateur, module, action, période. Pagination 50/page.

# 7. Mon profil

- Modifier nom, e-mail, téléphone, poste.
- Changer le mot de passe (vérification de l'ancien, mêmes règles de robustesse).
- **Ma Signature** (PNG/JPG, max 2 Mo) : utilisée pour signer les **mandats/bordereaux de règlement** générés en PDF.

# 8. Ce que l'Administrateur peut faire (récapitulatif)

| Domaine | Consulter | Créer | Modifier | Supprimer | Particularités |
|---|:--:|:--:|:--:|:--:|---|
| Fournisseurs | ✔ | ✔ | ✔ | ✔ | |
| Factures fournisseurs | ✔ | ✔ | ✔ | ✔ | + Valider |
| Règlements fournisseurs | ✔ | ✔ | ✔ | ✔ | |
| Clients / Factures / Règlements / Avances | ✔ | ✔ | ✔ | ✔ | |
| Banques | ✔ | ✔ | ✔ | ✔ | + Approvisionner |
| Plan comptable | ✔ | ✔ | ✔ | ✔ | |
| Rapports | ✔ | — | — | — | PDF / Excel |
| Utilisateurs | ✔ | ✔ | ✔ | ✔ | sauf soi-même |
| Rôles & Permissions | ✔ | ✔ | ✔ | ✔ | |
| Établissement | ✔ | — | ✔ | — | exclusif Admin |
| Taux fiscaux | ✔ | ✔ | ✔ | ✔ | |
| Journal d'activité | ✔ | — | — | — | |
