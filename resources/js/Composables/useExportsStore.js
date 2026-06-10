/**
 * Store global (réactif, partagé) des exports en cours.
 *
 * Alimente le bandeau « Exports » toujours visible (ExportsTray.vue) : chaque
 * export y reste affiché avec une barre de progression animée en temps réel,
 * jusqu'à ce que l'utilisateur le ferme.
 */
import { reactive } from 'vue';

const state = reactive({
  items: [], // { id, label, format, status, targetProgress, displayProgress, step, error, download_url, startedAt }
  now: Date.now(),
});

let ticker = null;

function ensureTicker() {
  if (ticker) return;
  ticker = setInterval(() => {
    state.now = Date.now();
    for (const it of state.items) {
      // Animation « creep » pendant le traitement : la barre avance régulièrement
      // (ressenti temps réel) sans dépasser 92 % tant que le serveur n'a pas fini.
      if ((it.status === 'processing' || it.status === 'pending') && it.displayProgress < 92) {
        it.displayProgress = Math.min(92, it.displayProgress + 0.5);
      }
      // On colle au minimum à la progression réelle remontée par le serveur.
      if (it.displayProgress < it.targetProgress) {
        it.displayProgress = it.targetProgress;
      }
    }
  }, 250);
}

function stopTickerIfIdle() {
  const active = state.items.some((i) => i.status === 'processing' || i.status === 'pending');
  if (!active && ticker) {
    clearInterval(ticker);
    ticker = null;
  }
}

function add(item) {
  state.items.unshift({
    targetProgress: 0,
    displayProgress: 0,
    step: 'En file d\'attente…',
    status: 'pending',
    error: null,
    download_url: null,
    startedAt: Date.now(),
    ...item,
  });
  ensureTicker();
}

function patch(id, data) {
  const it = state.items.find((i) => i.id === id);
  if (!it) return;
  if (typeof data.progress === 'number') {
    it.targetProgress = data.progress;
    if (it.displayProgress < data.progress) it.displayProgress = data.progress;
  }
  Object.assign(it, data);
  if (data.status === 'completed') it.displayProgress = 100;
  stopTickerIfIdle();
}

function remove(id) {
  const i = state.items.findIndex((x) => x.id === id);
  if (i >= 0) state.items.splice(i, 1);
  stopTickerIfIdle();
}

function clearFinished() {
  state.items = state.items.filter((i) => i.status !== 'completed' && i.status !== 'failed');
}

export function useExportsStore() {
  return { state, add, patch, remove, clearFinished };
}
