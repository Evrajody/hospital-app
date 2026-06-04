---
title: "Manuel utilisateur — Profil Utilisateur"
subtitle: "Application de Gestion Comptable — Hôpital de Zone de Ménontin (République du Bénin)"
date: "Juin 2026"
lang: fr
---

# 1. Présentation du profil

Le profil **Utilisateur** est le profil le plus restreint. Il offre un accès **en lecture seule** aux données métier de base : fournisseurs, clients, factures et règlements (des deux côtés). Il **ne peut rien créer, modifier ni supprimer**, et **n'a pas accès aux rapports**, aux banques, au plan comptable, ni à l'administration.

C'est le profil destiné à un agent qui doit **consulter** l'information comptable (vérifier une facture, un règlement, une fiche tiers) sans intervenir ni accéder aux états de synthèse.

**Compte de démonstration**

| Élément | Valeur |
|---|---|
| E-mail | `user@hospital.bj` |
| Mot de passe | `password` |
| Nom | Pierre Utilisateur |
| Poste | Agent Comptable |

## 1.1 Connexion et session

- Connexion par e-mail + mot de passe sur la page de login.
- **Déconnexion automatique** après **15 minutes d'inactivité**, avec un avertissement **1 minute** avant (boutons *Rester connecté* / *Se déconnecter*).
- Changement de mot de passe possible via le menu **Mon Profil**.

# 2. Menus visibles

- **Tableau de bord**
- **Factures Fournisseurs** : Fournisseurs · Factures · Règlements *(consultation)*
- **Factures Clients** : Clients · Factures · Règlements · Avances *(consultation)*

Les menus **Autres** (Plan Comptable, Banques), **Rapports**, **Paramètres** et **Journal d'Activité** ne sont **pas** visibles pour ce profil.

# 3. Tableau de bord

L'Utilisateur accède au tableau de bord (indicateurs de chiffre d'affaires, factures en attente, dettes et créances, dernières factures, situation des banques et graphiques). C'est sa vue de synthèse principale, puisqu'il n'a pas accès au module Rapports.

# 4. Ce que l'Utilisateur peut consulter

## 4.1 Côté fournisseurs

- **Fournisseurs** : liste (recherche, filtres, tri) et **fiche détaillée** (statistiques + onglets Informations / Factures / Règlements).
- **Factures fournisseurs** : liste avec statuts (Brouillon, Validée, Partiellement payée, Payée, Annulée) et montants (TTC, Net à payer, Payé) ; détail d'une facture avec son imputation comptable et ses règlements.
- **Règlements fournisseurs** : liste et détail (mode, référence, bénéficiaire, compte), regroupés par facture.

## 4.2 Côté clients

- **Clients** : liste et fiche (statistiques Facturé / Encaissé / Solde, onglets Informations / Factures / Règlements).
- **Factures clients** : liste avec statuts (Non payée, Partielle, Payée) et détail.
- **Règlements clients** : historique groupé par facture.
- **Avances** : liste des avances et de leur solde (Disponible / Partiellement utilisée / Épuisée).

> **Limitation générale** : tous les boutons d'action (créer, modifier, supprimer, valider, régler) sont **indisponibles**. L'Utilisateur consulte uniquement.

# 5. Ce que l'Utilisateur ne peut pas faire

- **Aucune saisie ni modification** : pas de création/édition/suppression de fournisseurs, clients, factures, règlements ou avances.
- **Pas de rapports** : le module Rapports (Fournisseurs / Clients / Banques) n'est pas accessible — c'est la principale différence avec le profil *Gestionnaire*.
- **Pas d'accès** aux Banques, au Plan comptable, aux Taux fiscaux, à l'administration (Utilisateurs, Rôles, Établissement) ni au Journal d'activité.

# 6. Récapitulatif des droits de l'Utilisateur

| Domaine | Consulter | Créer / Modifier / Supprimer |
|---|:--:|:--:|
| Fournisseurs | ✔ | ✘ |
| Factures fournisseurs | ✔ | ✘ |
| Règlements fournisseurs | ✔ | ✘ |
| Clients | ✔ | ✘ |
| Factures clients | ✔ | ✘ |
| Règlements clients / Avances | ✔ | ✘ |
| Rapports | ✘ | ✘ |
| Banques / Plan comptable | ✘ | ✘ |
| Administration / Paramètres / Journal | ✘ | ✘ |

# 7. Mon profil

L'Utilisateur peut modifier ses informations personnelles, changer son mot de passe et gérer sa signature depuis le menu **Mon Profil**.

# 8. Comparaison rapide avec le profil Gestionnaire

| Capacité | Utilisateur | Gestionnaire |
|---|:--:|:--:|
| Consulter fournisseurs / clients / factures / règlements / avances | ✔ | ✔ |
| Tableau de bord | ✔ | ✔ |
| **Rapports (PDF / Excel)** | ✘ | ✔ |
| Saisie / modification / suppression | ✘ | ✘ |

Les deux profils sont en lecture seule ; le **Gestionnaire** se distingue uniquement par l'**accès aux rapports**.
