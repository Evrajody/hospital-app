---
title: "Manuel utilisateur — Profil Comptable"
subtitle: "Application de Gestion Comptable — Hôpital de Zone de Ménontin (République du Bénin)"
date: "Juin 2026"
lang: fr
---

# 1. Présentation du profil

Le profil **Comptable** est le profil **opérationnel** de l'application. Il réalise au quotidien la saisie et le suivi comptable : il **consulte, crée et modifie** les fournisseurs, les clients, les factures (fournisseurs et clients), les règlements et les avances ; il **valide** les factures fournisseurs et génère les imputations et écritures comptables ; il consulte le plan comptable et les banques ; il produit tous les rapports.

En revanche, le Comptable **ne supprime pas** les données métier, **n'approvisionne pas** les comptes bancaires, **ne modifie pas** le plan comptable ni les taux fiscaux, et **n'accède pas** à l'administration (utilisateurs, rôles, paramètres de l'établissement, journal d'activité).

**Compte de démonstration**

| Élément | Valeur |
|---|---|
| E-mail | `comptable@hospital.bj` |
| Mot de passe | `password` |
| Nom | Jean Comptable |
| Poste | Chef Comptable |

## 1.1 Connexion et session

- Connexion par e-mail + mot de passe sur la page de login.
- **Déconnexion automatique** après **15 minutes d'inactivité**, avec un avertissement **1 minute** avant (boutons *Rester connecté* / *Se déconnecter*).
- Pensez à changer votre mot de passe (menu **Mon Profil**) et à téléverser votre **signature**, qui figurera sur les mandats/bordereaux de règlement.

# 2. Menus visibles

- **Tableau de bord**
- **Factures Fournisseurs** : Fournisseurs · Factures · Règlements
- **Factures Clients** : Clients · Factures · Règlements · Avances
- **Autres** : Plan Comptable · Banques *(consultation seule)*
- **Rapports** : Rapports Fournisseurs · Rapports Clients · Rapports Banques

Les menus **Paramètres** et **Journal d'Activité** ne sont **pas** visibles pour ce profil.

# 3. Tableau de bord

Mêmes indicateurs que pour les autres profils ayant accès : 4 cartes KPI (CA du mois, factures en attente, dettes fournisseurs, créances clients), dernières factures fournisseurs, situation des banques, et graphiques Recettes/Dépenses + répartition des charges.

# 4. Flux fournisseurs (cœur du métier)

## 4.1 Gérer un fournisseur

1. Menu **Fournisseurs** → bouton de création.
2. Saisir : raison sociale, type, contact, coordonnées, **IFU (13 chiffres, obligatoire)**, RCCM, et le **compte comptable** (401 fournisseurs d'exploitation ou 4812 fournisseurs d'investissements). Vous pouvez **créer le compte à la volée** depuis ce formulaire.
3. Enregistrer. *Modification possible à tout moment.*

> **Limitation** : le Comptable **ne peut pas supprimer** un fournisseur. (Quoi qu'il arrive, la suppression est de toute façon refusée si le fournisseur a des factures.)

La **fiche fournisseur** affiche 4 statistiques (Nb factures, Total facturé, Payé, Reste à payer) et trois onglets : Informations, Factures, Règlements.

## 4.2 Saisir et traiter une facture fournisseur

Le cycle complet — que le Comptable maîtrise de bout en bout — est :

**Brouillon → (Imputation) → Validée → Règlement(s) → Partiellement payée → Payée.**

1. **Créer la facture** (statut *Brouillon*) :
   - Numéro de pièce proposé automatiquement (`PC/AAA/NNNN`, ex. `PC/026/0042`), modifiable ; un contrôle signale les doublons ou un format inhabituel.
   - Saisir : montant facture, **montant M.O.** (base de l'AIB), avoir, assujettissement TVA + taux, **taux AIB**, références (n° B.C., dates), libellé.
   - Calculs automatiques : TVA (informative), TTC, **montant AIB = M.O. × taux**, et **Net à payer = montant − avoir − AIB**.
2. **Saisir l'imputation comptable** (onglet Imputations du formulaire) : lignes Débit (charges classe 6, immobilisations classe 2, personnel 42) et Crédit (comptes fournisseurs 401/481). Le système ajoute automatiquement la TVA déductible (445). L'équilibre **Débits = HT** et **Crédits = TTC** est contrôlé. La validation génère les écritures et permet d'éditer le **PDF d'imputation**.
3. **Valider la facture** : la facture passe en *Validée* ; elle n'est alors plus modifiable.
4. **Enregistrer un ou plusieurs règlements** (voir 4.3).
5. Au besoin, **Marquer comme soldée** : clôture administrative (statut *Payée*, reste à payer = 0, date de solde) **sans** créer de règlement — utile pour clôturer une facture sans décaissement.

> **Limitations** : le Comptable **ne peut pas supprimer** une facture ni **l'annuler après paiement**. La modification n'est possible qu'en *Brouillon*.

## 4.3 Enregistrer un règlement fournisseur

1. Depuis la facture (« Enregistrer un règlement ») ou le menu **Règlements**.
2. Saisir : date, **montant** (≤ reste à payer), **mode de paiement** (virement, chèque, espèces, mobile money, carte), référence (n° chèque/virement), bénéficiaire, compte de trésorerie/banque, compte fournisseur soldé. Possibilité de **lignes multi-fournisseurs**.
3. Cocher éventuellement **« Déclarer l'AIB »** pour retenir l'acompte sur ce règlement.
4. Enregistrer. Numéro auto `REG/AA/NNNNN`.

**Effets** : si le paiement est bancaire, le solde du compte est débité (avec contrôle de solde ; possibilité de forcer en cas d'insuffisance). La facture est mise à jour (montant payé, reste à payer, statut).

**Comptabilisation** : cliquer sur **« Générer les écritures comptables »** (débit fournisseur, crédit banque/caisse, crédit AIB le cas échéant). *Aucune écriture n'est générée pour les règlements en espèces.*

**Documents PDF** : Bordereau / Mandat de règlement (avec montants **en lettres** et **votre signature**), Imputation comptable du règlement, État de règlement de la facture.

> **Limitation** : le Comptable **ne peut pas supprimer** un règlement (mais peut le **modifier** tant qu'il n'est pas annulé).

# 5. Flux clients

## 5.1 Clients et factures clients

1. **Client** : nom, téléphone, **IFU (13 chiffres si renseigné)**, type (Société, Divers, Personnel, Autre), adresse, compte comptable (41… / 424100…, création à la volée possible). *Création et modification autorisées ; suppression non.*
2. **Facture client** : référence auto `NNNN/MM/AA`, montant, **ristourne** → **Net à payer = montant − ristourne**. Statuts : *Non payée → Partielle → Payée*.
3. **Marquer comme soldée** : clôture administrative (date de solde) sans règlement.
4. **PDF État de règlement** : reprend l'en-tête de l'établissement, le client, le détail de la facture et la liste des règlements.

## 5.2 Règlements clients

Sur une facture, montant ≤ reste à payer. Trois sources :

- **Paiement direct** (chèque / espèces) : institution + référence chèque, dépôt bancaire optionnel (banque + bordereau, justificatif joignable).
- **Virement bancaire** : banque + référence.
- **Imputer sur une avance** : choisir une avance disponible du **même client** (montant ≤ solde de l'avance).

## 5.3 Avances clients

- Enregistrer une avance : société émettrice, **n° de chèque**, montant, n° proforma éventuel, dépôt bancaire éventuel.
- Statuts : *Disponible / Partiellement utilisée / Épuisée*. Le **solde restant** est consommé au fur et à mesure des règlements.
- *Création et modification autorisées* (sans descendre sous le montant déjà utilisé) ; suppression non.

# 6. Plan comptable et Banques (consultation)

- **Plan comptable** : consultation et **recherche** par numéro/libellé, filtres par classe et par source. *Le Comptable ne crée/ne modifie pas de compte ici* — mais peut créer un compte « à la volée » lors de la création d'un fournisseur ou d'un client.
- **Banques** : consultation des comptes, soldes et **mouvements** (relevés, bordereaux, règlements imputés). *Le Comptable ne peut pas approvisionner un compte ni créer/supprimer une banque ou un compte* (réservé à l'Administrateur).

# 7. Rapports

Le Comptable produit **tous les rapports** (affichage, PDF, Excel, impression) :

- **Fournisseurs** : Mouvement des factures, Situation des fournisseurs, État des factures réglées, Factures et Soldes, **Déclaration AIB**, **Déclaration TVA**, Point des pièces comptables, Bordereau de transmission (+ mandats multiples), Point des charges, Point des investissements.
- **Clients** : État des règlements, État des créances, Brouillard de chèques & Imputations comptables, Chiffre d'affaires, Pertes & Rejets.
- **Banques** : Situation des banques.

# 8. Récapitulatif des droits du Comptable

| Domaine | Consulter | Créer | Modifier | Supprimer | Autres |
|---|:--:|:--:|:--:|:--:|---|
| Fournisseurs | ✔ | ✔ | ✔ | ✘ | |
| Factures fournisseurs | ✔ | ✔ | ✔ | ✘ | **Valider ✔** |
| Règlements fournisseurs | ✔ | ✔ | ✔ | ✘ | écritures ✔ |
| Clients | ✔ | ✔ | ✔ | ✘ | |
| Factures clients | ✔ | ✔ | ✔ | ✘ | |
| Règlements clients / Avances | ✔ | ✔ | ✔ | ✘ | |
| Plan comptable | ✔ | ✘ | ✘ | ✘ | création « à la volée » via fiches tiers |
| Banques | ✔ | ✘ | ✘ | ✘ | **pas d'approvisionnement** |
| Rapports | ✔ | — | — | — | PDF / Excel |
| Administration (utilisateurs, rôles, établissement) | ✘ | ✘ | ✘ | ✘ | non visible |
| Taux fiscaux | ✘ | ✘ | ✘ | ✘ | non visible |
| Journal d'activité | ✘ | — | — | — | non visible |

# 9. Mon profil

Modifier ses informations personnelles, changer son mot de passe, et **téléverser sa signature** (PNG/JPG, max 2 Mo) qui apparaîtra sur les mandats/bordereaux de règlement.
