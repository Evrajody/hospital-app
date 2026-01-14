# Configuration des Polices - DM Sans

## Police Principale : DM Sans

**DM Sans** est maintenant la police par défaut de l'application.

### Caractéristiques

- **Type** : Sans-serif géométrique
- **Créateur** : Colophon Foundry pour Google
- **Licence** : Open Font License (gratuite)
- **Optimisée pour** : UI/UX, écrans numériques
- **Source** : Google Fonts

### Poids disponibles

Tous les poids de 100 à 1000 sont disponibles :

```css
font-weight: 100;  /* Thin - Extra léger */
font-weight: 200;  /* Extra Light */
font-weight: 300;  /* Light - Léger */
font-weight: 400;  /* Regular - Normal (défaut) */
font-weight: 500;  /* Medium - Moyen */
font-weight: 600;  /* Semi Bold - Demi-gras */
font-weight: 700;  /* Bold - Gras */
font-weight: 800;  /* Extra Bold */
font-weight: 900;  /* Black - Très gras */
```

### Utilisation dans le code

#### Via les variables CSS

```css
/* Police principale */
font-family: var(--font-primary);

/* Police pour titres */
font-family: var(--font-headings);

/* Police monospace */
font-family: var(--font-mono);

/* Poids */
font-weight: var(--font-weight-regular);
font-weight: var(--font-weight-medium);
font-weight: var(--font-weight-semibold);
font-weight: var(--font-weight-bold);
```

#### Usage direct

```css
/* Appliquer DM Sans directement */
.mon-element {
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
}

/* Titre avec DM Sans */
.titre {
    font-family: var(--font-headings);
    font-weight: var(--font-weight-bold);
}
```

#### Dans les composants Vue

```vue
<template>
  <div class="content">
    <h1>Titre</h1>
    <p>Texte normal</p>
  </div>
</template>

<style scoped>
.content {
  font-family: var(--font-primary); /* DM Sans automatiquement */
}

h1 {
  font-weight: var(--font-weight-bold);
}
</style>
```

### Poids recommandés par usage

| Usage | Poids | Variable CSS |
|-------|-------|--------------|
| Texte léger, subtitles | 300 | `--font-weight-light` |
| Corps du texte | 400 | `--font-weight-regular` |
| Texte important | 500 | `--font-weight-medium` |
| Sous-titres | 600 | `--font-weight-semibold` |
| Titres | 700 | `--font-weight-bold` |
| Titres importants | 800 | `--font-weight-extrabold` |

### Configuration Element Plus

DM Sans est automatiquement appliqué à tous les composants Element Plus via :

```css
:root {
    --el-font-family: var(--font-primary);
}
```

### Performance

- ✅ Chargement optimisé avec `display=swap`
- ✅ Format variable (1 fichier pour tous les poids)
- ✅ CDN Google Fonts (mise en cache globale)
- ✅ Anti-aliasing activé pour un rendu optimal

### Support des navigateurs

DM Sans est compatible avec tous les navigateurs modernes :
- Chrome/Edge 88+
- Firefox 89+
- Safari 14+

## Fichiers CSS

- **`fonts.css`** : Configuration de DM Sans et variables
- **`app.css`** : Styles globaux appliquant DM Sans à l'application

## Changer de police

Si vous souhaitez utiliser une autre police :

1. Modifier `resources/css/fonts.css`
2. Mettre à jour les variables `--font-primary` et `--font-headings`
3. L'application utilisera automatiquement la nouvelle police

## Ressources

- [DM Sans sur Google Fonts](https://fonts.google.com/specimen/DM+Sans)
- [Documentation complète](https://fonts.google.com/specimen/DM+Sans/about)
