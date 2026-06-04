---
title: "Manuel utilisateur — Profil Gestionnaire"
subtitle: "Application de Gestion Comptable — Hôpital de Zone de Ménontin (République du Bénin)"
date: "Juin 2026"
lang: fr
---

# 1. Présentation du profil

Le profil **Gestionnaire** est un profil de **consultation et de reporting**. Il peut **tout voir** dans les modules métier (fournisseurs, clients, factures et règlements des deux côtés) et **produire tous les rapports** (PDF / Excel), mais il **ne saisit, ne modifie ni ne supprime aucune donnée**.

C'est le profil idéal pour un responsable qui suit l'activité (dettes, créances, trésorerie, déclarations) sans intervenir dans la saisie comptable.

**Compte de démonstration**

| Élément | Valeur |
|---|---|
| E-mail | `gestionnaire@hospital.bj` |
| Mot de passe | `password` |
| Nom | Marie Gestionnaire |
| Poste | Gestionnaire des Achats |

## 1.1 Connexion et session

- Connexion par e-mail + mot de passe.
- **Déconnexion automatique** après **15 minutes d'inactivité** (avertissement 1 minute avant : *Rester connecté* / *Se déconnecter*).
- Changement de mot de passe possible via **Mon Profil**.

# 2. Menus visibles

- **Tableau de bord**
- **Factures Fournisseurs** : Fournisseurs · Factures · Règlements *(consultation)*
- **Factures Clients** : Clients · Factures · Règlements · Avances *(consultation)*
- **Rapports** : Rapports Fournisseurs · Rapports Clients · Rapports Banques

Les menus **Autres** (Plan Comptable, Banques), **Paramètres** et **Journal d'Activité** ne sont **pas** visibles pour ce profil.

# 3. Tableau de bord

Le Gestionnaire dispose du tableau de bord complet :

- **4 cartes KPI** : Chiffre d'affaires du mois (tendance), Factures en attente (client / fournisseur), Dettes fournisseurs, Créances clients.
- **Dernières factures fournisseurs** et **Situation des banques** (soldes + total).
- **Graphiques** : évolution mensuelle Recettes vs Dépenses (12 mois), répartition des charges par fournisseur.

# 4. Ce que le Gestionnaire peut consulter

## 4.1 Côté fournisseurs

- **Fournisseurs** : liste (recherche, filtres, tri) et **fiche détaillée** (statistiques + onglets Informations / Factures / Règlements).
- **Factures fournisseurs** : liste avec statuts (Brouillon, Validée, Partiellement payée, Payée, Annulée), montants (TTC, Net à payer, Payé), et le détail de chaque facture, y compris son **imputation comptable** (Débits / Crédits) et ses règlements.
- **Règlements fournisseurs** : liste et détail (mode de paiement, référence, bénéficiaire, compte), regroupés par facture.

## 4.2 Côté clients

- **Clients** : liste et fiche (statistiques Facturé / Encaissé / Solde, onglets Informations / Factures / Règlements).
- **Factures clients** : liste avec statuts (Non payée, Partielle, Payée) et détail.
- **Règlements clients** : historique groupé par facture.
- **Avances** : liste des avances et de leur solde (Disponible / Partiellement utilisée / Épuisée).

> **Limitation générale** : tous les boutons d'action (créer, modifier, supprimer, valider, régler, approvisionner) sont **indisponibles** pour ce profil. Le Gestionnaire ne peut ni saisir ni modifier de données.

# 5. Rapports (cœur de l'usage du Gestionnaire)

Le Gestionnaire produit **tous les rapports**, avec affichage à l'écran, export **PDF**, export **Excel** et **impression**.

## 5.1 Rapports Fournisseurs

- **Mouvement des factures** : toutes les pièces d'un fournisseur (TTC, avoir, M.O., AIB, dû, réglé, solde).
- **Situation des fournisseurs** : point des dettes restant dues (tous, par compte, ou par fournisseur ; à une date ou sur une période).
- **État des factures réglées** : factures réglées sur une date/période (résumé ou détail par fournisseur).
- **Factures et Soldes** : TTC, payé, reste à payer (export Excel).
- **Déclaration AIB** : acompte retenu sur les règlements (déclaration ou bordereau de versement, montant en lettres).
- **Déclaration TVA** : TVA des factures assujetties sur une période.
- **Point des pièces comptables (PC)** : pièces groupées par jour.
- **Bordereau de transmission** : sélection de règlements → bordereau et/ou mandats multiples.
- **Point des charges** / **Point des investissements** : dépenses imputées par compte (classes 6/42 ou classe 2).

## 5.2 Rapports Clients

- **État des règlements** : factures et niveau de règlement (payé, pertes, rejets, solde).
- **État des créances** : factures avec reste à payer.
- **Brouillard de chèques & Imputations comptables** : chèques déposés par bordereau et écritures associées.
- **Chiffre d'affaires** : théorique vs physique, écart, ou CA par client.
- **Pertes & Rejets** : pertes et rejets de chèques.

## 5.3 Rapports Banques

- **Situation des banques** : trésorerie des comptes sur une période (résumé par compte ou détail par banque, avec soldes initiaux et courants).

# 6. Récapitulatif des droits du Gestionnaire

| Domaine | Consulter | Créer / Modifier / Supprimer |
|---|:--:|:--:|
| Fournisseurs | ✔ | ✘ |
| Factures fournisseurs | ✔ | ✘ |
| Règlements fournisseurs | ✔ | ✘ |
| Clients | ✔ | ✘ |
| Factures clients | ✔ | ✘ |
| Règlements clients / Avances | ✔ | ✘ |
| Rapports (Fournisseurs / Clients / Banques) | ✔ | export PDF / Excel |
| Plan comptable | ✘ | ✘ |
| Banques (écrans dédiés) | ✘ | ✘ |
| Administration / Paramètres / Journal | ✘ | ✘ |

# 7. Mon profil

Le Gestionnaire peut modifier ses informations personnelles, changer son mot de passe et gérer sa signature depuis le menu **Mon Profil**.
