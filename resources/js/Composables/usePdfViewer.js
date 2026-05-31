import { reactive } from 'vue';

/**
 * État global (singleton) du visualiseur PDF.
 * Le drawer est monté une seule fois dans AppLayout ; n'importe quel composant
 * peut appeler openPdf(url) pour prévisualiser un PDF au lieu de le télécharger.
 */
const state = reactive({
  visible: false,
  url: '',
  title: 'Aperçu du document',
});

export function usePdfViewer() {
  /**
   * Ouvre le drawer de prévisualisation.
   * @param {string} url  URL du PDF (sans paramètre action) — le téléchargement
   *                      utilisera cette URL, l'aperçu y ajoutera action=stream.
   * @param {string} [title]
   */
  const openPdf = (url, title = 'Aperçu du document') => {
    state.url = url;
    state.title = title;
    state.visible = true;
  };

  const close = () => {
    state.visible = false;
  };

  return { state, openPdf, close };
}
