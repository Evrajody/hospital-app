{{--
    Onglet d'attente d'un export asynchrone.

    Ouvert par le bouton « Imprimer » / « Aperçu » d'un rapport : la génération se
    fait dans le worker (le rendu dompdf des gros rapports ne tient pas dans une
    requête web), cette page suit l'avancement du même ExportJob que le bandeau
    « Exports », puis affiche le PDF — et déclenche l'impression si demandé.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $job['label'] }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f5f6f8; color: #1f2937;
        }
        .wrap { height: 100%; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card {
            width: 100%; max-width: 460px; background: #fff; border: 1px solid #e4e7ed;
            border-radius: 10px; padding: 24px; box-shadow: 0 8px 28px rgba(0,0,0,.08);
        }
        .label { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .step { font-size: 13px; color: #6b7280; margin-bottom: 16px; }
        .bar { height: 8px; background: #eef0f3; border-radius: 4px; overflow: hidden; }
        .bar > i { display: block; height: 100%; width: 0; background: #409eff; transition: width .3s ease; }
        .meta { display: flex; justify-content: space-between; margin-top: 8px; font-size: 11px; color: #9ca3af; }
        .error { margin-top: 14px; font-size: 13px; color: #f56c6c; }
        .actions { margin-top: 18px; display: flex; gap: 8px; }
        button {
            font: inherit; font-size: 13px; padding: 8px 14px; border-radius: 6px; cursor: pointer;
            border: 1px solid #dcdfe6; background: #fff; color: #374151;
        }
        button.primary { background: #409eff; border-color: #409eff; color: #fff; }
        iframe { display: block; width: 100%; height: 100%; border: 0; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="wrap" id="attente">
        <div class="card">
            <div class="label">{{ $job['label'] }}</div>
            <div class="step" id="step">Préparation…</div>
            <div class="bar"><i id="bar"></i></div>
            <div class="meta"><span id="pct">0 %</span><span id="chrono">0s</span></div>
            <div class="error hidden" id="erreur"></div>
            <div class="actions hidden" id="actions">
                <button class="primary" onclick="window.close()">Fermer</button>
            </div>
        </div>
    </div>

    <iframe id="visionneuse" class="hidden" title="{{ $job['label'] }}"></iframe>

    <script>
        const EXPORT_ID = @json($job['id']);
        const IMPRIMER = @json($print);
        const TERMINES = ['completed', 'failed', 'cancelled'];

        const el = (id) => document.getElementById(id);
        const debut = Date.now();
        let progression = 0;
        let tentatives = 0;
        // Fenêtre alignée sur le timeout du job (1800 s), avec de la marge.
        const MAX_TENTATIVES = 1200;

        // Barre « creep » : elle avance doucement jusqu'à 92 % tant que le serveur
        // n'a pas rendu la main (même ressenti que le bandeau Exports).
        setInterval(() => {
            if (progression < 92) {
                progression += 0.5;
                el('bar').style.width = progression + '%';
                el('pct').textContent = Math.round(progression) + ' %';
            }
            const s = Math.round((Date.now() - debut) / 1000);
            el('chrono').textContent = s < 60 ? s + 's' : Math.floor(s / 60) + 'm' + String(s % 60).padStart(2, '0');
        }, 250);

        const echec = (message) => {
            el('step').textContent = 'Génération interrompue';
            el('erreur').textContent = message;
            el('erreur').classList.remove('hidden');
            el('actions').classList.remove('hidden');
        };

        const afficher = (url) => {
            const frame = el('visionneuse');
            frame.onload = () => {
                if (!IMPRIMER) return;
                // Le viewer PDF intégré accepte print() une fois chargé ; en cas de
                // blocage (navigateur ou extension), le PDF reste affiché.
                try { frame.contentWindow.print(); } catch (e) { /* aperçu seul */ }
            };
            frame.src = url;
            el('attente').classList.add('hidden');
            frame.classList.remove('hidden');
        };

        const sonder = async () => {
            try {
                const res = await fetch(`/rapports/exports/${EXPORT_ID}/status`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('statut indisponible');

                const { export: exp } = await res.json();

                if (typeof exp.progress === 'number' && exp.progress > progression) {
                    progression = exp.progress;
                    el('bar').style.width = progression + '%';
                    el('pct').textContent = Math.round(progression) + ' %';
                }
                if (exp.step) el('step').textContent = exp.step;

                if (exp.status === 'completed' && exp.view_url) {
                    progression = 100;
                    el('bar').style.width = '100%';
                    return afficher(exp.view_url);
                }
                if (exp.status === 'failed') return echec(exp.error || 'La génération a échoué.');
                if (exp.status === 'cancelled') return echec('Export annulé.');
            } catch (e) {
                // Erreur transitoire : on retentera au prochain tour.
            }

            tentatives += 1;
            if (tentatives >= MAX_TENTATIVES) {
                return echec('La génération prend trop de temps.');
            }
            setTimeout(sonder, 2000);
        };

        setTimeout(sonder, 800);
    </script>
</body>
</html>
