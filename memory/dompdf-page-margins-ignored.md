---
name: dompdf-page-margins-ignored
description: Les PDF — dompdf ignore les marges @page (avec setPaper) ; mettre les marges sur le body
metadata:
  type: project
---

Dans ce projet (barryvdh/laravel-dompdf, contrôleurs qui font `$pdf->setPaper('a4', 'landscape')`), **dompdf IGNORE complètement les marges `@page { margin: ... }`**. Vérifié en rendant un PDF réel : avec `@page { margin: 32mm }` le contenu reste collé au bord. Les marges latérales qui « semblaient » fonctionner venaient en réalité du `padding` du `body`.

**How to apply :** porter TOUTES les marges des PDF sur le `body` (`padding: haut côtés bas`), pas sur `@page`. Le layout maître des rapports `resources/views/pdf/rapports/_layout-rapport.blade.php` utilise `@page { margin: 0 }` + `body { padding: 26mm 20mm 22mm }`. Les docs autonomes (mandat, imputation, etat-reglement, mandats-multiples) idem (`padding: 20mm 18mm 18mm`). Limite connue : le `padding-top` du body ne s'applique qu'à la page 1 → les pages de continuation des rapports multi-pages n'ont pas de marge haute (pas de mécanisme dispo puisque @page est ignoré).

Pour vérifier un rendu : générer un PDF dans le conteneur `app` puis le lire (l'outil Read affiche les PDF en image). Le logo Ministère est en haut à gauche de tous les rapports.
