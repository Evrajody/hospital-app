# XCROW API Documentation

> **Version** : 1.0.0
> **Base URL** : `https://xcrow.ekponte.com/api`
> **Authentification** : Bearer JWT (`Authorization: Bearer <accessToken>`)

---

## Table des matieres

1. [Authentification](#1-authentification)
2. [Utilisateur / Profil](#2-utilisateur--profil)
3. [Roles](#3-roles)
4. [Categories de produits](#4-categories-de-produits)
5. [Produits](#5-produits)
6. [Liens de paiement](#6-liens-de-paiement)
7. [Types de fichiers](#7-types-de-fichiers)
8. [Commandes](#8-commandes)
9. [Configuration systeme](#9-configuration-systeme)
10. [Entites generiques](#10-entites-generiques)
11. [Transactions](#11-transactions)
12. [Validation de commande (OTP)](#12-validation-de-commande-otp)
13. [Admin - Escrow Payouts](#13-admin---escrow-payouts)
14. [Schemas de donnees](#14-schemas-de-donnees)

---

## Format des reponses

### Reponse de succes
```json
{
  "status": "success",
  "data": { ... }
}
```

### Reponse d'erreur
```json
{
  "status": "error",
  "code": 400,
  "message": "Donnees invalides",
  "errors": [
    {
      "path": "name",
      "message": "Le nom est requis"
    }
  ]
}
```

---

## 1. Authentification

### POST `/auth/login`

Authentifie un utilisateur et retourne un token JWT.

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `emailOrPhone` | string | Oui | `"augustbonou@gmail.com"` |
| `password` | string | Oui | `"password"` |

**Exemple de requete** :
```bash
curl -X POST https://xcrow.ekponte.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "emailOrPhone": "augustbonou@gmail.com",
    "password": "password"
  }'
```

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Authentification reussie |
| 400 | Donnees d'entree invalides |
| 401 | Identifiants invalides ou compte inactif |

**Reponse 200** :
```json
{
  "user": {
    "id": "a1b2c3d4-e5f6-7890-abcd-1234567890ef",
    "phoneNumber": "+33612345678",
    "phoneCode": "+33",
    "email": "test@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "roleId": "123e4567-e89b-12d3-a456-426614174000",
    "role": { "id": "...", "code": "buyer", "label": "Acheteur" },
    "roleCode": "buyer",
    "profilePictureId": "profile-pic-123",
    "isVerified": true,
    "isActive": true,
    "languagePreference": "fr",
    "createdAt": "2025-09-13T10:51:00Z",
    "updatedAt": "2025-09-13T10:51:00Z",
    "lastLogin": "2025-09-13T10:50:00Z",
    "failedLoginAttempts": 0,
    "lockoutUntil": null,
    "lastFailedLogin": null
  },
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": {
    "id": "b2c3d4e5-f6a7-8901-bcde-234567890abc",
    "userId": "a1b2c3d4-e5f6-7890-abcd-1234567890ef",
    "token": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
    "expiresAt": "2025-09-20T10:51:00Z",
    "createdAt": "2025-09-13T10:51:00Z"
  },
  "loginAttempt": {
    "id": "c3d4e5f6-7890-1234-cdef-34567890abcd",
    "emailOrPhone": "test@example.com",
    "ipAddress": "192.168.1.1",
    "success": true,
    "createdAt": "2025-09-13T10:51:00Z"
  }
}
```

---

### POST `/auth/register`

Cree un nouvel utilisateur. Soit `email`, soit `phoneNumber` doit etre fourni.

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `email` | string (email) | Non* | Adresse email |
| `phoneNumber` | string | Non* | Numero de telephone (ex: `"+22991234567"`) |
| `phoneCode` | string | Non | Indicatif telephonique (ex: `"+229"`) |
| `password` | string | Oui | Minimum 8 caracteres |
| `firstName` | string | Non | Prenom |
| `lastName` | string | Non | Nom de famille |
| `roleId` | string (uuid) | Non | ID du role attribue |
| `languagePreference` | string | Non | Preference de langue (ex: `"fr"`) |

> *Au moins un des deux (`email` ou `phoneNumber`) doit etre fourni.

**Exemple de requete** :
```bash
curl -X POST https://xcrow.ekponte.com/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "Password123!",
    "firstName": "Jean",
    "lastName": "Doe",
    "phoneNumber": "+22991234567",
    "phoneCode": "+229",
    "roleId": "123e4567-e89b-12d3-a456-426614174000",
    "languagePreference": "fr"
  }'
```

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Utilisateur cree |
| 400 | Donnees d'entree invalides |
| 401 | Identifiants invalides ou compte inactif |

---

### GET `/auth/confirm-account`

Confirme le compte d'un utilisateur via un jeton envoye par email.

**Parametres Query** :
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| `token` | string | Oui | Jeton de confirmation envoye par email |

**Exemple** :
```bash
curl -X GET "https://xcrow.ekponte.com/api/auth/confirm-account?token=abc123"
```

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Compte verifie avec succes |
| 400 | Token manquant, invalide ou expire |
| 500 | Erreur interne du serveur |

---

### POST `/auth/send-confirm-account-token`

Renvoie le jeton de confirmation de compte.

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `emailOrPhone` | string | Oui | Email ou numero de telephone |

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Jeton de confirmation envoye |
| 400 | Donnees invalides |
| 500 | Erreur interne du serveur |

---

### POST `/auth/request-password-reset`

Envoie un jeton de reinitialisation de mot de passe.

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `emailOrPhone` | string | Oui | Email ou numero de telephone |

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Jeton de reinitialisation envoye |
| 400 | Donnees invalides |
| 500 | Erreur interne du serveur |

---

### POST `/auth/reset-password`

Reinitialise le mot de passe d'un utilisateur.

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `userId` | string | Oui | ID de l'utilisateur |
| `token` | string | Oui | Jeton de confirmation envoye par email |
| `newPassword` | string | Oui | Nouveau mot de passe |

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Mot de passe mis a jour |
| 400 | Donnees invalides |
| 500 | Erreur interne du serveur |

---

### PUT `/auth/password` (Auth requise)

Met a jour le mot de passe de l'utilisateur connecte.

**Headers** : `Authorization: Bearer <accessToken>`

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `currentPassword` | string | Oui | Mot de passe actuel |
| `newPassword` | string | Oui | Nouveau mot de passe |

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Mot de passe mis a jour |
| 400 | Donnees invalides |
| 500 | Erreur interne du serveur |

---

### GET `/auth/login-attempts` (Auth requise)

Recupere les tentatives de connexion de l'utilisateur.

**Headers** : `Authorization: Bearer <accessToken>`

**Parametres Query** :
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| `emailOrPhone` | string | Non | Email ou numero de telephone |
| `ipAddress` | string | Non | Adresse IP |
| `limit` | integer | Non | Limite du nombre de resultats |

**Reponse 200** : Liste des tentatives de connexion.

---

### PUT `/auth/update-seller-status` (Auth requise)

Met a jour le statut d'un vendeur.

**Headers** : `Authorization: Bearer <accessToken>`

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `userId` | string (uuid) | Oui | ID de l'utilisateur |
| `status` | string | Oui | Nouveau statut |

**Reponses** :

| Code | Description |
|------|-------------|
| 200 | Utilisateur mis a jour |
| 400 | Donnees d'entree invalides |
| 401 | Non authentifie |
| 500 | Erreur serveur |

---

## 2. Utilisateur / Profil

### GET `/auth/profile` (Auth requise)

Recupere les informations de l'utilisateur connecte.

**Headers** : `Authorization: Bearer <accessToken>`

**Exemple** :
```bash
curl -X GET https://xcrow.ekponte.com/api/auth/profile \
  -H "Authorization: Bearer eyJhbGciOi..."
```

**Reponse 200** : Objet `User` complet.

| Code | Description |
|------|-------------|
| 200 | Informations de l'utilisateur |
| 401 | Non authentifie |
| 500 | Erreur interne du serveur |

---

### PUT `/auth/profile` (Auth requise)

Met a jour les informations de l'utilisateur connecte.

**Headers** : `Authorization: Bearer <accessToken>`

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `firstName` | string | Non | Prenom |
| `lastName` | string | Non | Nom |
| `languagePreference` | string | Non | Langue preferee |
| `profilePictureId` | string | Non | ID de la photo de profil |

---

### PUT `/auth/seller-profile` (Auth requise)

Met a jour ou cree le profil de vendeur.

**Headers** : `Authorization: Bearer <accessToken>`

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `businessName` | string | Oui | `"Boutique Dupont"` |
| `businessType` | string | Oui | `"Retail"` |
| `businessRegistration` | string | Non | `"123456789"` |
| `businessAddress` | string | Non | `"123 Rue de Paris, 75001"` |
| `businessDescription` | string | Non | `"Vente de produits artisanaux"` |
| `businessLogoUrl` | string | Non | `"https://example.com/logo.png"` |
| `deliveryZones` | string[] | Non | `["75001", "75002"]` |
| `freeDeliveryThreshold` | number | Non | `50` |
| `isPremium` | boolean | Oui | `false` |
| `bankAccount` | string | Non | `"FR7612345678901234567890123"` |
| `mobileMoneyAccount` | string | Non | `"+33612345678"` |

---

### PUT `/auth/delivery-profile` (Auth requise)

Met a jour ou cree le profil de livreur.

**Headers** : `Authorization: Bearer <accessToken>`

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `vehicleType` | string | Oui | `"Scooter"` |
| `licenseNumber` | string | Oui | `"ABC123"` |
| `vehicleRegistration` | string | Non | `"XX-123-YY"` |
| `serviceZones` | string[] | Oui | `["75001", "75002"]` |
| `baseLocation` | object `{x, y}` | Oui | `{"x": 48.8566, "y": 2.3522}` |
| `hourlyRate` | number | Oui | `15` |
| `perKmRate` | number | Oui | `1` |
| `isAvailable` | boolean | Non | `true` |
| `currentLocation` | object `{x, y}` | Non | `{"x": 48.86, "y": 2.35}` |
| `insuranceNumber` | string | Non | `"INS123456"` |
| `emergencyContactName` | string | Non | `"Marie Dupont"` |
| `emergencyContactPhone` | string | Non | `"+33698765432"` |

---

### POST `/auth/users/search` (Auth requise)

Recherche des utilisateurs avec filtres.

**Headers** : `Authorization: Bearer <accessToken>`

**Request Body** : Objet `CategoryFilters` (voir schemas).

---

### GET `/users/{id}` (Auth requise)

Recupere les details d'un utilisateur par son ID.

**Headers** : `Authorization: Bearer <accessToken>`

**Parametres Path** :
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| `id` | string (uuid) | Oui | ID de l'utilisateur |

---

## 3. Roles

### GET `/roles`

Recupere tous les roles.

**Reponse 200** :
```json
{
  "status": "success",
  "data": [
    {
      "id": "123e4567-e89b-12d3-a456-426614174000",
      "code": "buyer",
      "label": "Acheteur",
      "description": "Utilisateur qui achete des produits"
    }
  ]
}
```

---

### POST `/roles`

Cree un nouveau role.

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `code` | string | Oui | `"seller"` |
| `label` | string | Oui | `"Vendeur"` |
| `description` | string | Non | `"Utilisateur qui vend des produits"` |

---

### GET `/roles/{id}`

Recupere un role par son ID.

### PUT `/roles/{id}`

Met a jour un role existant. Body identique a POST `/roles`.

### DELETE `/roles/{id}`

Supprime un role. Retourne `204 No Content`.

---

## 4. Categories de produits

### POST `/product-categories/search`

Recherche des categories avec filtres.

**Request Body** (`CategoryFilters`) :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | string | Filtrer par ID |
| `ids` | string[] | Filtrer par liste d'IDs |
| `name` | string | Filtrer par nom |
| `parentId` | string | Filtrer par categorie parente |
| `isActive` | boolean | Filtrer par statut actif |
| `includeChildren` | boolean | Inclure les sous-categories |
| `includeParent` | boolean | Inclure la categorie parente |
| `excludeId` | string | Exclure une categorie |

---

### POST `/product-categories` (Auth requise)

Cree une nouvelle categorie.

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `name` | string | Oui | `"Electronique"` |
| `description` | string | Oui | `"Categorie pour appareils electroniques"` |
| `parentId` | string (uuid) | Non | `null` |
| `iconUrl` | string | Non | `"https://example.com/icons/electronics.png"` |
| `isActive` | boolean | Non | `true` |

---

### GET `/product-categories/{id}`

Recupere une categorie par son ID.

### PUT `/product-categories/{id}` (Auth requise)

Met a jour une categorie. Body identique a POST.

---

## 5. Produits

### GET `/products` (Auth requise)

Recherche des produits avec filtres via query params.

**Parametres Query** :
| Param | Type | Description |
|-------|------|-------------|
| `ids` | string | IDs separes par virgules |
| `sellerId` | string (uuid) | ID du vendeur |
| `categoryId` | string (uuid) | ID de la categorie |
| `searchTerm` | string | Recherche textuelle |
| `isActive` | boolean | Statut actif |
| `isFeatured` | boolean | Produit mis en avant |
| `minPrice` | number | Prix minimum |
| `maxPrice` | number | Prix maximum |
| `includeSeller` | boolean | Inclure infos vendeur |
| `includeCategory` | boolean | Inclure infos categorie |
| `excludeId` | string (uuid) | Exclure un produit |
| `page` | integer | Numero de page |
| `limit` | integer | Resultats par page |
| `field` | string | Champ de tri |
| `order` | `asc` / `desc` | Ordre de tri |

**Reponse 200** :
```json
{
  "status": "success",
  "data": {
    "products": [...],
    "total": 42,
    "totalPages": 5,
    "page": 1
  }
}
```

---

### POST `/products` (Auth requise)

Cree un nouveau produit.

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `sellerId` | string (uuid) | Oui | ID du vendeur |
| `categoryId` | string (uuid) | Non | ID de la categorie |
| `name` | string (max 200) | Oui | Nom du produit |
| `description` | string | Non | Description |
| `price` | number (min 0) | Oui | Prix |
| `stockQuantity` | integer (min 0) | Oui | Quantite en stock |
| `minOrderQuantity` | integer (min 1) | Non | Quantite min (defaut: 1) |
| `maxOrderQuantity` | integer (min 1) | Non | Quantite max |
| `weight` | number | Non | Poids |
| `dimensions` | object | Non | Dimensions |
| `isActive` | boolean | Non | Actif (defaut: true) |
| `isFeatured` | boolean | Non | Vedette (defaut: false) |
| `tags` | string[] | Non | Tags (ex: `["smartphone"]`) |
| `variants` | object | Non | Variantes |

---

### PUT `/products/{id}` (Auth requise)

Met a jour un produit. Body identique a POST.

### GET `/products/{id}`

Recupere un produit par son ID.

### DELETE `/products/{id}` (Auth requise)

Supprime un produit. Retourne `204 No Content`.

---

### POST `/products/search`

Recherche des produits avec filtres et pagination via body.

**Request Body** (`GetAllProductsFilters`) :
| Champ | Type | Description |
|-------|------|-------------|
| `sellerId` | string (uuid) | ID du vendeur |
| `categoryId` | string (uuid) | ID de la categorie |
| `isActive` | boolean | Defaut: `true` |
| `isFeatured` | boolean | Defaut: `false` |
| `minPrice` | number | Prix minimum |
| `maxPrice` | number | Prix maximum |
| `inStock` | boolean | En stock (defaut: `true`) |
| `outOfStock` | boolean | Rupture de stock |
| `lowStockThreshold` | integer | Seuil de stock bas |
| `searchTerm` | string | Recherche textuelle |
| `tags` | string[] | Tags a rechercher |
| `excludeProductId` | string (uuid) | Exclure un produit |
| `ids` | string[] (uuid) | Filtrer par IDs |
| `sortField` | `name` / `price` / `createdAt` / `updatedAt` / `stockQuantity` | Tri |
| `sortOrder` | `asc` / `desc` | Ordre |
| `page` | integer (min 1) | Page |
| `limit` | integer (1-100) | Limite |

---

### PATCH `/products/{id}/stock` (Auth requise)

Met a jour la quantite en stock.

**Request Body** :
```json
{ "quantity": 25 }
```

---

### PATCH `/products/{id}/active` (Auth requise)

Active ou desactive un produit.

**Request Body** :
```json
{ "isActive": true }
```

---

### PATCH `/products/{id}/featured` (Auth requise)

Marque ou demarque un produit comme vedette.

**Request Body** :
```json
{ "isFeatured": false }
```

---

### PATCH `/products/{id}/price` (Auth requise)

Met a jour le prix d'un produit.

**Request Body** :
```json
{ "price": 19.99 }
```

---

### PATCH `/products/prices` (Auth requise)

Met a jour le prix de plusieurs produits en masse.

**Request Body** :
```json
[
  { "id": "b0c34d5b-7f6d-4df4-bc54-73bca8e6f6ef", "price": 14.99 },
  { "id": "a1b2c3d4-e5f6-7890-abcd-1234567890ef", "price": 29.99 }
]
```

**Reponse 200** :
```json
{ "updatedCount": 2 }
```

---

## 6. Liens de paiement

### POST `/payment-links/search` (Auth requise)

Recherche des liens de paiement avec filtres, tri et pagination.

**Request Body** : Objet `SearchPaymentLinksInput` avec les filtres.

---

### GET `/payment-links` (Auth requise)

Recupere tous les liens de paiement avec filtres via query params.

**Parametres Query** :
| Param | Type | Description |
|-------|------|-------------|
| `code` | string | Filtrer par code |
| `sellerId` | string (uuid) | ID du vendeur |
| `productId` | string (uuid) | ID du produit |
| `isActive` | boolean | Statut actif |
| `linkCode` | string | Code du lien |
| `expiresAfter` | date-time | Expire apres cette date |
| `expiresBefore` | date-time | Expire avant cette date |
| `hasMaxUses` | boolean | A un max d'utilisations |
| `searchTerm` | string | Recherche textuelle |
| `ids` | string[] (uuid) | Liste d'IDs |
| `sortField` | `linkCode` / `createdAt` / `updatedAt` / `currentUses` | Tri |
| `sortOrder` | `asc` / `desc` | Ordre |
| `page` | integer (min 1) | Page |
| `limit` | integer (1-100) | Limite |

---

### POST `/payment-links` (Auth requise)

Cree un nouveau lien de paiement.

**Request Body** :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `sellerId` | string (uuid) | Oui | ID du vendeur |
| `products` | array | Oui | Liste de produits |
| `products[].productId` | string (uuid) | Oui | ID du produit |
| `products[].quantity` | integer | Non | Quantite (defaut: 1) |
| `products[].updateQuantity` | boolean | Non | Permettre MAJ quantite (defaut: true) |
| `linkCode` | string | Oui | Code unique du lien |
| `updateProductQuantity` | boolean | Non | Permettre MAJ quantite produit |
| `isActive` | boolean | Non | Statut actif (defaut: true) |

---

### GET `/payment-links/code-details/{code}` (Auth requise)

Recupere un lien de paiement par son code unique.

### GET `/payment-links/{id}` (Auth requise)

Recupere un lien de paiement par son ID.

### PUT `/payment-links/{id}` (Auth requise)

Met a jour un lien de paiement. Body identique a POST.

### DELETE `/payment-links/{id}` (Auth requise)

Supprime un lien de paiement.

### PATCH `/payment-links/{id}/uses` (Auth requise)

Incremente le nombre d'utilisations d'un lien.

### PATCH `/payment-links/{id}/active` (Auth requise)

Active ou desactive un lien de paiement.

**Request Body** :
```json
{ "isActive": true }
```

---

## 7. Types de fichiers

### GET `/file-types`

Recupere tous les types de fichiers.

### POST `/file-types`

Cree un nouveau type de fichier.

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `code` | string | Oui | `"FILE_1"` |
| `name` | string | Oui | `"Fiche d'identite"` |
| `useFor` | string | Oui | `"seller"` |
| `status` | string | Non | `"active"` |

### GET `/file-types/{id}`

Recupere un type de fichier par son ID.

### PUT `/file-types/{id}`

Met a jour un type de fichier. Body identique a POST.

### DELETE `/file-types/{id}`

Supprime un type de fichier.

---

## 8. Commandes

### POST `/orders` (Auth requise)

Cree une nouvelle commande.

**Request Body** (`OrderInput`) :
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| `order.paymentLinkId` | string (uuid) | Oui | ID du lien de paiement |
| `order.deliveryMethodId` | string (uuid) | Non | ID du mode de livraison |
| `order.deliveryAddress` | object | Non | Adresse de livraison |
| `order.deliveryNotes` | string | Non | Notes de livraison |
| `order.specialInstructions` | string | Non | Instructions speciales |
| `order.estimatedDelivery` | string | Non | Date estimee de livraison |
| `products` | array | Non | Liste des produits |
| `products[].productId` | string (uuid) | Non | ID du produit |
| `products[].quantity` | integer | Non | Quantite (defaut: 2) |
| `products[].selectedVariants` | object | Non | Variantes choisies |
| `products[].specialInstructions` | string | Non | Instructions speciales |

**Exemple de requete** :
```bash
curl -X POST https://xcrow.ekponte.com/api/orders \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "order": {
      "paymentLinkId": "3a40708b-8c0d-4b9b-93a8-b86d2cdca2bb",
      "deliveryMethodId": "c41551f8-1da1-40e6-9ff7-e33178447fb2"
    },
    "products": [
      {
        "productId": "1476a7db-0bbe-453e-87ff-4abdf287e33c",
        "quantity": 2
      }
    ]
  }'
```

---

### GET `/orders` (Auth requise)

Recherche des commandes avec filtres.

**Parametres Query** (`OrderFilters`) :
| Param | Type | Description |
|-------|------|-------------|
| `id` | string | Filtrer par ID |
| `orderNumber` | string | Filtrer par numero de commande |
| `buyerId` | string | Filtrer par acheteur |
| `sellerId` | string | Filtrer par vendeur |
| `productId` | string | Filtrer par produit |
| `status` | string | Filtrer par statut |
| `includeRelations` | boolean | Inclure les relations (buyer, seller, product) |
| `fromDate` | date-time | Date de debut |
| `toDate` | date-time | Date de fin |
| `page` | integer | Page |
| `limit` | integer | Limite par page |

---

### PUT `/orders/{id}` (Auth requise)

Met a jour une commande. Body identique a `OrderInput`.

### GET `/orders/{id}` (Auth requise)

Recupere une commande par son ID.

### DELETE `/orders/{id}` (Auth requise)

Supprime une commande (soft delete).

### POST `/orders/{id}/cancel` (Auth requise)

Annule une commande.

### POST `/orders/{orderId}/retry-payment` (Auth requise)

Retente le paiement d'une commande existante.

---

## 9. Configuration systeme

### GET `/system-configs/key/{key}`

Recupere une configuration par sa cle.

**Parametres Path** :
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| `key` | string | Oui | Cle de configuration (ex: `PLATFORM_FEE_PERCENTAGE`) |

---

### GET `/system-configs`

Recupere toutes les configurations.

---

### POST `/system-configs`

Cree une nouvelle configuration.

**Request Body** (`SystemConfigInput`) :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `configKey` | string | Oui | `"PLATFORM_FEE_PERCENTAGE"` |
| `configValue` | string | Oui | `"5"` |
| `dataType` | `string` / `number` / `boolean` / `json` | Oui | `"number"` |
| `description` | string | Non | `"Pourcentage des frais de plateforme"` |
| `isPublic` | boolean | Non | `false` |

---

### PUT `/system-configs/{id}`

Met a jour une configuration. Body identique a POST.

### GET `/system-configs/{id}`

Recupere une configuration par son ID.

### DELETE `/system-configs/{id}`

Supprime une configuration (soft delete).

---

## 10. Entites generiques

### GET `/generic-entities`

Recupere toutes les entites generiques.

### POST `/generic-entities`

Cree une nouvelle entite generique.

**Request Body** (`GenericEntityInput`) :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `code` | string | Oui | Code unique |
| `name` | string | Oui | Nom de l'entite |
| `type` | string | Oui | Type d'entite |
| `description` | string | Non | Description |
| `status` | string | Non | Statut (defaut: `"active"`) |
| `parentId` | string | Non | ID du parent |

**Exemple de requete** :
```bash
curl -X POST https://xcrow.ekponte.com/api/generic-entities \
  -H "Content-Type: application/json" \
  -d '{
    "code": "DELIVERY_MODE_1",
    "name": "Livraison standard",
    "type": "delivery_mode",
    "description": "Livraison en 3-5 jours",
    "status": "active"
  }'
```

---

### GET `/generic-entities/{id}`

Recupere une entite par son ID.

### PUT `/generic-entities/{id}`

Met a jour une entite. Body identique a `GenericEntityInput` (POST).

### DELETE `/generic-entities/{id}`

Supprime une entite.

---

## 11. Transactions

### GET `/transactions` (Auth requise)

Liste toutes les transactions.

### GET `/transactions/{escrowTransactionId}/status` (Auth requise)

Recupere le statut d'une transaction par son ID escrow.

---

## 12. Validation de commande (OTP)

### POST `/validations/{orderId}/init-otp` (Auth requise)

Initialise la validation d'une commande par OTP.

**Parametres Path** :
| Param | Type | Requis |
|-------|------|--------|
| `orderId` | string (uuid) | Oui |

### POST `/validations/{validationId}/confirm-otp` (Auth requise)

Confirme la validation d'une commande par OTP.

**Parametres Path** :
| Param | Type | Requis |
|-------|------|--------|
| `validationId` | string (uuid) | Oui |

**Request Body** :
| Champ | Type | Requis | Exemple |
|-------|------|--------|---------|
| `otpCode` | string | Oui | `"123456"` (code a 6 chiffres) |

---

## 13. Admin - Escrow Payouts

### GET `/admin/payouts` (Auth requise)

Liste tous les payouts avec filtres avances.

### GET `/admin/payouts/stats` (Auth requise)

Obtient les statistiques des payouts.

### GET `/admin/payouts/{id}` (Auth requise)

Obtient un payout par ID.

### GET `/admin/payouts/order/{orderId}` (Auth requise)

Obtient tous les payouts d'une commande.

---

## 14. Schemas de donnees

### User
```json
{
  "id": "uuid",
  "phoneNumber": "string | null",
  "phoneCode": "string | null",
  "email": "string | null",
  "firstName": "string | null",
  "lastName": "string | null",
  "roleId": "uuid | null",
  "role": "Role | null",
  "roleCode": "string | null",
  "profilePictureId": "string | null",
  "isVerified": "boolean",
  "isActive": "boolean",
  "languagePreference": "string | null",
  "createdAt": "date-time",
  "updatedAt": "date-time",
  "lastLogin": "date-time | null",
  "failedLoginAttempts": "integer",
  "lockoutUntil": "date-time | null",
  "lastFailedLogin": "date-time | null"
}
```

### Role
```json
{
  "id": "uuid",
  "code": "string",
  "label": "string",
  "description": "string"
}
```

### RefreshToken
```json
{
  "id": "uuid",
  "userId": "uuid",
  "token": "string",
  "expiresAt": "date-time",
  "createdAt": "date-time"
}
```

### LoginAttempt
```json
{
  "id": "uuid",
  "emailOrPhone": "string",
  "ipAddress": "string",
  "success": "boolean",
  "createdAt": "date-time"
}
```

### Product
```json
{
  "id": "uuid",
  "sellerId": "uuid",
  "categoryId": "uuid",
  "name": "string",
  "description": "string | null",
  "price": "number",
  "stockQuantity": "integer",
  "minOrderQuantity": "integer",
  "maxOrderQuantity": "integer | null",
  "weight": "number | null",
  "dimensions": "object | null",
  "isActive": "boolean",
  "isFeatured": "boolean",
  "tags": "object | null",
  "variants": "object | null",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### ProductCategory
```json
{
  "id": "uuid",
  "name": "string",
  "parentId": "uuid | null",
  "description": "string | null",
  "iconUrl": "string | null",
  "isActive": "boolean",
  "sortOrder": "integer",
  "parent": "ProductCategory | null",
  "children": "ProductCategory[]",
  "products": "Product[]"
}
```

### SellerProfile
```json
{
  "id": "uuid",
  "userId": "uuid",
  "businessName": "string",
  "businessType": "string",
  "businessRegistration": "string | null",
  "businessAddress": "string | null",
  "businessDescription": "string | null",
  "businessLogoUrl": "string | null",
  "deliveryZones": "string[]",
  "freeDeliveryThreshold": "number | null",
  "isPremium": "boolean",
  "premiumExpiresAt": "date-time | null",
  "bankAccount": "string | null",
  "mobileMoneyAccount": "string | null",
  "totalSales": "number",
  "totalOrders": "integer",
  "averageRating": "number | null"
}
```

### DeliveryProfile
```json
{
  "vehicleType": "string",
  "licenseNumber": "string | null",
  "vehicleRegistration": "string | null",
  "serviceZones": "string[] | null",
  "baseLocation": "{ x: number, y: number } | null",
  "hourlyRate": "number",
  "perKmRate": "number",
  "isAvailable": "boolean",
  "currentLocation": "{ x: number, y: number } | null",
  "lastLocationUpdate": "date-time | null",
  "totalDeliveries": "integer",
  "successfulDeliveries": "integer",
  "averageRating": "number | null",
  "insuranceNumber": "string | null",
  "emergencyContactName": "string | null",
  "emergencyContactPhone": "string | null"
}
```

### FileType
```json
{
  "id": "uuid",
  "code": "string",
  "name": "string",
  "useFor": "string",
  "status": "string"
}
```

### GenericEntity
```json
{
  "id": "uuid",
  "code": "string",
  "name": "string",
  "description": "string | null",
  "status": "string",
  "type": "string",
  "parentId": "string | null",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### PaymentLink
```json
{
  "id": "uuid",                          // Requis
  "sellerId": "uuid",                    // Requis
  "products": [                          // Requis
    { "productId": "uuid", "quantity": "integer | null" }
  ],
  "updateProductQuantity": "boolean",
  "linkCode": "string",                  // Requis - ex: "PAY123456"
  "qrCodeUrl": "string | null",         // URL du QR code
  "isActive": "boolean",                 // Requis
  "maxUses": "integer | null",          // Nombre max d'utilisations
  "currentUses": "integer",             // Requis - Nombre actuel d'utilisations
  "expiresAt": "date-time | null",      // Date d'expiration
  "createdAt": "date-time",             // Requis
  "updatedAt": "date-time"              // Requis
}
```

### SearchPaymentLinksInput
```json
{
  "sellerId": "uuid",                   // Filtrer par vendeur
  "productId": "uuid",                  // Filtrer par produit
  "isActive": "boolean",                // Filtrer par statut actif
  "linkCode": "string",                 // Filtrer par code
  "expiresAfter": "date-time",          // Expire apres cette date
  "expiresBefore": "date-time",         // Expire avant cette date
  "hasMaxUses": "boolean",              // Filtre max utilisations
  "searchTerm": "string",               // Recherche textuelle
  "ids": ["uuid"],                      // Filtrer par liste d'IDs
  "sortField": "linkCode | createdAt | updatedAt | currentUses",
  "sortOrder": "asc | desc",
  "page": "integer (min 1)",
  "limit": "integer (1-100)"
}
```

### PaymentLinkSearchResult
```json
{
  "paymentLinks": [PaymentLink],        // Requis
  "total": "integer",                    // Requis - Total de liens
  "page": "integer",                     // Requis - Page actuelle
  "totalPages": "integer"               // Requis - Total de pages
}
```

### ToggleActiveStatusInput
```json
{
  "id": "uuid",                          // Requis - ID du lien
  "isActive": "boolean"                  // Requis - Nouveau statut
}
```

### Order
```json
{
  "id": "uuid",                          // Requis
  "orderNumber": "string",              // Requis - ex: "ORD-ABC123"
  "buyerId": "uuid",                    // Requis
  "sellerId": "uuid",                   // Requis
  "productId": "uuid",                  // Requis
  "paymentLinkId": "uuid",             // Requis
  "productName": "string",              // Requis - ex: "Smartphone XYZ"
  "productPrice": "float",              // Requis - ex: 499.99
  "quantity": "integer",                 // Requis - ex: 2
  "selectedVariants": "object | null",  // ex: {"color": "black", "size": "128GB"}
  "specialInstructions": "string | null",// ex: "Livrer avant 18h"
  "subtotal": "float",                  // Requis - ex: 999.98
  "deliveryFee": "float",               // Requis - ex: 10
  "platformFee": "float",               // Requis - ex: 5
  "totalAmount": "float",               // Requis - ex: 1014.98
  "status": "string",                   // Requis - ex: "pending"
  "deliveryMethod": "string",           // Requis - ex: "delivery"
  "deliveryAddress": "object | null",   // ex: {"street":"123 Main St","city":"Paris","zip":"75001"}
  "deliveryNotes": "string | null",     // ex: "Sonner a la porte"
  "estimatedDelivery": "date-time | null",
  "createdAt": "date-time",             // Requis
  "confirmedAt": "date-time | null",
  "deliveredAt": "date-time | null",
  "validatedAt": "date-time | null",
  "buyer": "User | null",
  "seller": "SellerProfile | null",
  "product": "Product | null",
  "paymentLink": "PaymentLink | null"
}
```

### OrderInput
```json
{
  "order": {
    "paymentLinkId": "uuid",            // Requis
    "deliveryMethodId": "uuid",
    "deliveryAddress": "object | null",
    "deliveryNotes": "string | null",
    "specialInstructions": "string | null",
    "estimatedDelivery": "string | null"
  },
  "products": [
    {
      "productId": "uuid",
      "quantity": "integer",             // ex: 2
      "selectedVariants": "object",
      "specialInstructions": "string | null"
    }
  ]
}
```

### OrderFilters
```json
{
  "id": "string",
  "orderNumber": "string",
  "buyerId": "string",
  "sellerId": "string",
  "productId": "string",
  "status": "string",
  "includeRelations": "boolean",
  "fromDate": "date-time",
  "toDate": "date-time",
  "page": "integer",
  "limit": "integer"
}
```

### EscrowTransaction
```json
{
  "id": "uuid",                          // Requis
  "orderId": "uuid",                    // Requis
  "totalAmount": "float",               // Requis - ex: 1000
  "sellerAmount": "float",              // Requis - ex: 850
  "deliveryAmount": "float",            // Requis - ex: 100
  "platformFee": "float",               // Requis - ex: 50
  "statusId": "uuid",                   // Requis
  "paymentMethodId": "uuid",            // Requis
  "paymentProvider": "string",          // Requis - ex: "stripe"
  "paymentReference": "string",         // Requis - ex: "txn_123456789"
  "paymentConfirmedAt": "date-time | null",
  "fundsReleasedAt": "date-time | null",
  "sellerPaidAt": "date-time | null",
  "deliveryPaidAt": "date-time | null",
  "refundProcessedAt": "date-time | null",
  "externalTransactionId": "string",    // Requis - ex: "ch_123456789"
  "gatewayResponse": "object | null",
  "deletedAt": "date-time | null",
  "createdAt": "date-time | null"
}
```

### SystemConfig
```json
{
  "id": "uuid",                          // Requis
  "configKey": "string",                 // Requis - ex: "PLATFORM_FEE_PERCENTAGE"
  "configValue": "string",              // Requis - ex: "5"
  "dataType": "string",                 // Requis - ex: "number"
  "description": "string | null",       // ex: "Pourcentage des frais de plateforme"
  "isPublic": "boolean",                // Requis
  "updatedBy": "uuid | null",
  "updatedAt": "date-time",             // Requis
  "createdAt": "date-time",             // Requis
  "updatedByUser": "{ id: uuid, email: string } | null"
}
```

### SystemConfigInput
```json
{
  "configKey": "string",                 // Requis - ex: "PLATFORM_FEE_PERCENTAGE"
  "configValue": "string",              // Requis - ex: "5"
  "dataType": "string | number | boolean | json",  // Requis
  "description": "string | null",
  "isPublic": "boolean"
}
```

### SystemConfigFilters
```json
{
  "id": "uuid",
  "configKey": "string",
  "isPublic": "boolean",
  "dataType": "string",
  "includeUser": "boolean",
  "page": "integer",
  "limit": "integer"
}
```

### DeliveryMode
```json
{
  "id": "uuid",
  "title": "string",
  "code": "string",
  "description": "string | null",
  "status": "string",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### DeliveryModeInput
```json
{
  "title": "string",                    // Requis
  "code": "string",                     // Requis
  "description": "string | null"
}
```

### PaymentMethod
```json
{
  "id": "uuid",
  "title": "string",
  "code": "string",
  "description": "string | null",
  "status": "string",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### PaymentMethodInput
```json
{
  "title": "string",                    // Requis
  "code": "string",                     // Requis
  "description": "string | null"
}
```

### DisputeType
```json
{
  "id": "uuid",
  "title": "string",
  "code": "string",
  "description": "string | null",
  "status": "string",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### DisputeTypeInput
```json
{
  "title": "string",                    // Requis
  "code": "string",                     // Requis
  "description": "string | null"
}
```

### DisputeClaimType
```json
{
  "id": "uuid",
  "disputeTypeId": "uuid",
  "title": "string",
  "code": "string",
  "description": "string | null",
  "status": "string",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### DisputeClaimTypeInput
```json
{
  "disputeTypeId": "uuid",              // Requis
  "title": "string",                    // Requis
  "code": "string",                     // Requis
  "description": "string | null"
}
```

### EscrowPayout
```json
{
  "id": "string",                        // ID unique du payout
  "orderId": "string | null",           // ID de la commande
  "escrowTransactionId": "string | null",// ID de la transaction escrow
  "recipientType": "seller | platform | delivery",
  "recipientId": "string",              // ID du beneficiaire
  "amount": "number",                   // ex: 50000
  "currency": "string",                 // ex: "XOF"
  "statusId": "string",
  "payoutType": "string | null",        // ex: "sale_commission"
  "paymentProvider": "string | null",   // ex: "fedapay"
  "providerTransactionId": "string | null",
  "providerReference": "string | null",
  "retryCount": "integer",              // ex: 0
  "nextRetryAt": "date-time | null",
  "lastAttemptAt": "date-time | null",
  "completedAt": "date-time | null",
  "errorMessage": "string | null",      // ex: "Insufficient funds"
  "metadata": "object | null",
  "createdAt": "date-time",
  "updatedAt": "date-time"
}
```

### EscrowPayoutStatus
```json
{
  "id": "string",
  "code": "pending | processing | completed | failed",
  "name": "string",                     // ex: "Completed"
  "description": "string"               // ex: "Payout has been successfully completed"
}
```

### PayoutAttempt
```json
{
  "id": "string",
  "escrowPayoutId": "string",           // ID du payout associe
  "attemptNumber": "integer",           // Numero de la tentative
  "status": "success | failed | pending",
  "amount": "number",                   // ex: 50000
  "paymentProvider": "string | null",   // ex: "fedapay"
  "providerTransactionId": "string | null",
  "providerReference": "string | null",
  "errorCode": "string | null",         // ex: "INSUFFICIENT_FUNDS"
  "errorMessage": "string | null",
  "responseData": "object | null",      // Donnees brutes du fournisseur
  "createdAt": "date-time"
}
```

### EscrowPayoutWithRelations
Etend `EscrowPayout` avec :
```json
{
  "...EscrowPayout",
  "status": "EscrowPayoutStatus",
  "order": "{ id: string, orderNumber: string, totalAmount: number } | null",
  "attempts": ["PayoutAttempt"]
}
```

### PaginatedPayouts
```json
{
  "payouts": ["EscrowPayoutWithRelations"],
  "pagination": {
    "total": "integer",                  // ex: 150
    "page": "integer",                   // ex: 1
    "limit": "integer",                  // ex: 20
    "totalPages": "integer"              // ex: 8
  }
}
```

### PayoutStats
```json
{
  "totalPayouts": "integer",            // ex: 150
  "completedPayouts": "integer",        // ex: 120
  "failedPayouts": "integer",           // ex: 10
  "pendingPayouts": "integer",          // ex: 20
  "totalAmount": "number",              // ex: 1500000
  "totalCompletedAmount": "number"      // ex: 1200000
}
```

### ErrorResponse
```json
{
  "status": "error",
  "code": 400,
  "message": "Donnees invalides",
  "errors": [
    { "path": "name", "message": "Le nom est requis" }
  ]
}
```
