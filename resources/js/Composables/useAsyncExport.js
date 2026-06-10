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
   */
  const startExport = async (report, format, params = {}, label = '') => {
    let json;
    try {
      const res = await api.post('/rapports/exports', { report, format, params });
      json = await res.json();
      if (!res.ok || !json.success) {
        ElMessage.error(json?.message || "Impossible de lancer l'export.");
        return;
      }
    } catch (e) {
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

    pollStatus(exp.id);
  };

  const pollStatus = (id) => {
    let attempts = 0;
    const maxAttempts = 300; // ~10 min à 2 s

    const tick = async () => {
      attempts += 1;
      try {
        const res = await api.get(`/rapports/exports/${id}/status`);
        const { export: exp } = await res.json();

        store.patch(id, {
          status: exp.status,
          progress: exp.progress,
          step: exp.step,
          error: exp.error,
          download_url: exp.download_url,
        });

        if (exp.status === 'completed' || exp.status === 'failed') {
          return; // terminé : le bandeau gère l'affichage / le téléchargement
        }
      } catch (e) {
        // erreur transitoire : on retente
      }

      if (attempts < maxAttempts) {
        setTimeout(tick, 2000);
      } else {
        store.patch(id, { status: 'failed', step: 'Délai dépassé', error: 'La génération prend trop de temps.' });
      }
    };

    setTimeout(tick, 1200);
  };

  return { startExport };
}
