/**
 * Export asynchrone de rapports.
 *
 * Met la génération en file d'attente côté serveur, puis suit l'avancement par
 * polling. L'export apparaît dans le bandeau « Exports » (ExportsTray.vue),
 * toujours visible, avec une barre de progression en temps réel. Quand le
 * fichier est prêt, l'utilisateur le télécharge depuis le bandeau.
 */
import { api } from '@/Composables/useFetch';
import { ElMessage } from 'element-plus';
import { useExportsStore } from '@/Composables/useExportsStore';

export function useAsyncExport() {
  const store = useExportsStore();

  /**
   * @param {string} report  clé de rapport (cf. ReportExportService::REPORTS)
   * @param {'pdf'|'excel'} format
   * @param {object} params  filtres du rapport
   * @param {string} [label] libellé affiché dans le bandeau
   * @param {{open?: 'print'|'view'}} [options] ouvre un onglet de suivi qui affiche
   *        (ou imprime) le fichier dès qu'il est prêt, en plus du bandeau
   */
  const startExport = async (report, format, params = {}, label = '', options = {}) => {
    // L'onglet doit être ouvert DANS le geste utilisateur, sinon le navigateur le
    // bloque : on l'ouvre vide, puis on l'envoie sur la page de suivi.
    const onglet = options.open ? window.open('', '_blank') : null;

    let json;
    try {
      const res = await api.post('/rapports/exports', { report, format, params });
      json = await res.json();
      if (!res.ok || !json.success) {
        if (onglet) onglet.close();
        ElMessage.error(json?.message || "Impossible de lancer l'export.");
        return;
      }
    } catch (e) {
      if (onglet) onglet.close();
      ElMessage.error("Impossible de lancer l'export.");
      return;
    }

    const exp = json.export;
    store.add({
      id: exp.id,
      label: label || exp.label,
      format,
      status: exp.status,
      progress: exp.progress || 0,
      step: exp.step || 'En file d\'attente…',
    });

    if (onglet) {
      onglet.location.href = waitUrl(exp.id, options.open === 'print');
    }

    pollStatus(exp.id);
  };

  /** URL de l'onglet de suivi (progression puis affichage/impression du PDF). */
  const waitUrl = (id, print = false) =>
    `/rapports/exports/${id}/wait${print ? '?print=1' : ''}`;

  /**
   * Génère le rapport puis l'ouvre pour impression dans un onglet de suivi.
   * Remplace l'ancien window.open() direct sur les routes /pdf/... : le rendu des
   * gros rapports (dompdf) ne tient pas dans une requête web.
   */
  const printExport = (report, format, params = {}, label = '') =>
    startExport(report, format, params, label, { open: 'print' });

  /** Idem, mais simple aperçu (sans lancer l'impression). */
  const viewExport = (report, format, params = {}, label = '') =>
    startExport(report, format, params, label, { open: 'view' });

  const TERMINAL = ['completed', 'failed', 'cancelled'];

  const pollStatus = (id) => {
    let attempts = 0;
    // Fenêtre alignée sur le timeout du job (1800 s). Backoff : 2 s pendant ~2 min,
    // puis 4 s → couvre ~36 min sans marteler le serveur.
    const maxAttempts = 600;

    const tick = async () => {
      attempts += 1;

      // L'item a été retiré du bandeau, ou annulé localement → on arrête de sonder.
      const local = store.state.items.find((i) => i.id === id);
      if (!local || local.status === 'cancelled') {
        return;
      }

      try {
        const res = await api.get(`/rapports/exports/${id}/status`);
        const { export: exp } = await res.json();

        store.patch(id, {
          status: exp.status,
          progress: exp.progress,
          step: exp.step,
          error: exp.error,
          download_url: exp.download_url,
          view_url: exp.view_url,
        });

        if (TERMINAL.includes(exp.status)) {
          return; // terminé/échoué/annulé : le bandeau gère l'affichage
        }
      } catch (e) {
        // erreur transitoire : on retente
      }

      if (attempts < maxAttempts) {
        setTimeout(tick, attempts < 60 ? 2000 : 4000);
      } else {
        store.patch(id, { status: 'failed', step: 'Délai dépassé', error: 'La génération prend trop de temps.' });
      }
    };

    setTimeout(tick, 1200);
  };

  /**
   * Interrompt un export en cours. Marque l'item annulé immédiatement (retour visuel
   * instantané), puis demande l'annulation côté serveur (arrêt du rendu / mise au rebut).
   */
  const cancelExport = async (id) => {
    store.patch(id, { status: 'cancelled', step: 'Annulé' });
    try {
      await api.post(`/rapports/exports/${id}/cancel`);
    } catch (e) {
      // Le job finira par voir le statut ; on n'échoue pas côté UI.
    }
  };

  return { startExport, printExport, viewExport, cancelExport, waitUrl };
}
