/**
 * Export asynchrone de rapports volumineux.
 *
 * Au lieu de générer le PDF/Excel dans la requête (risque de crash/timeout sur
 * de gros volumes), on met la génération en file d'attente côté serveur, puis on
 * suit l'avancement par polling. Quand le fichier est prêt, l'utilisateur est
 * notifié et déclenche le téléchargement en cliquant sur le lien.
 */
import { api } from '@/Composables/useFetch';
import { ElNotification, ElMessage } from 'element-plus';

export function useAsyncExport() {
  /**
   * @param {string} report  clé de rapport (cf. ReportExportService::REPORTS)
   * @param {'pdf'|'excel'} format
   * @param {object} params  filtres du rapport (mêmes clés que l'URL de l'export synchrone)
   * @param {string} [label] libellé affiché dans les notifications
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
    const name = label || exp.label;

    ElNotification({
      title: 'Génération en cours',
      message: `${name} : le fichier est en cours de génération. Vous serez notifié dès qu'il est prêt à télécharger.`,
      type: 'info',
      duration: 4500,
    });

    pollStatus(exp.id, name);
  };

  const pollStatus = (id, name) => {
    let attempts = 0;
    const maxAttempts = 150; // ~5 min à 2 s

    const tick = async () => {
      attempts += 1;
      try {
        const res = await api.get(`/rapports/exports/${id}/status`);
        const { export: exp } = await res.json();

        if (exp.status === 'completed') {
          notifyReady(exp, name);
          return;
        }
        if (exp.status === 'failed') {
          ElNotification({
            title: 'Export échoué',
            message: `${name} : ${exp.error || 'erreur lors de la génération.'}`,
            type: 'error',
            duration: 0,
          });
          return;
        }
      } catch (e) {
        // erreur transitoire : on retente
      }

      if (attempts < maxAttempts) {
        setTimeout(tick, 2000);
      } else {
        ElNotification({
          title: 'Génération longue',
          message: `${name} : la génération prend plus de temps que prévu. Réessayez dans quelques minutes.`,
          type: 'warning',
          duration: 0,
        });
      }
    };

    setTimeout(tick, 1500);
  };

  const notifyReady = (exp, name) => {
    ElNotification({
      title: 'Export prêt',
      dangerouslyUseHTMLString: true,
      message:
        `${name} est prêt.<br>` +
        `<a href="${exp.download_url}" style="color:#1C6FB8;font-weight:600;text-decoration:underline">` +
        `Télécharger le fichier</a>`,
      type: 'success',
      duration: 0,
    });
  };

  return { startExport };
}
