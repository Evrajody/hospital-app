<template>
  <transition name="tray-fade">
    <div v-if="state.items.length" class="exports-tray">
      <div class="tray-header">
        <span><el-icon><Download /></el-icon> Exports<template v-if="activeCount"> · {{ activeCount }} en cours</template></span>
        <el-button text size="small" :disabled="!hasFinished" @click="clearFinished">Effacer terminés</el-button>
      </div>
      <div class="tray-body">
        <div v-for="it in state.items" :key="it.id" class="export-item">
          <div class="item-top">
            <span class="item-label" :title="it.label">{{ it.label }}</span>
            <el-tag size="small" effect="plain">{{ (it.format || '').toUpperCase() }}</el-tag>
            <el-button
              v-if="it.status === 'pending' || it.status === 'processing'"
              text size="small" type="warning" class="item-close" @click="cancel(it.id)"
            >Annuler</el-button>
            <el-button
              v-else
              text size="small" class="item-close" @click="remove(it.id)"
            >✕</el-button>
          </div>
          <el-progress
            :percentage="Math.round(it.displayProgress)"
            :status="progressStatus(it)"
            :stroke-width="8"
            :indeterminate="it.status === 'pending'"
            :duration="2"
          />
          <div class="item-bottom">
            <span class="item-step">{{ stepText(it) }}</span>
            <span class="item-time">{{ elapsed(it) }}</span>
          </div>
          <div v-if="it.status === 'completed'" class="item-actions">
            <el-button type="primary" size="small" :icon="Download" @click="download(it)">Télécharger</el-button>
          </div>
          <div v-else-if="it.status === 'failed'" class="item-error">{{ it.error || 'Échec de la génération.' }}</div>
          <div v-else-if="it.status === 'cancelled'" class="item-cancelled">Export annulé.</div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';
import { Download } from '@element-plus/icons-vue';
import { useExportsStore } from '@/Composables/useExportsStore';
import { useAsyncExport } from '@/Composables/useAsyncExport';

const { state, remove, clearFinished } = useExportsStore();
const { cancelExport } = useAsyncExport();

const FINISHED = ['completed', 'failed', 'cancelled'];
const activeCount = computed(() => state.items.filter((i) => i.status === 'processing' || i.status === 'pending').length);
const hasFinished = computed(() => state.items.some((i) => FINISHED.includes(i.status)));

const cancel = (id) => cancelExport(id);

const progressStatus = (it) =>
  it.status === 'completed' ? 'success'
    : it.status === 'failed' ? 'exception'
    : it.status === 'cancelled' ? 'warning'
    : '';
const stepText = (it) =>
  it.status === 'completed' ? 'Prêt à télécharger'
    : it.status === 'failed' ? 'Échec'
    : it.status === 'cancelled' ? 'Annulé'
    : (it.step || '…');

const elapsed = (it) => {
  const s = Math.max(0, Math.round((state.now - it.startedAt) / 1000));
  return s < 60 ? `${s}s` : `${Math.floor(s / 60)}m${String(s % 60).padStart(2, '0')}`;
};

const download = (it) => {
  if (!it.download_url) return;
  const a = document.createElement('a');
  a.href = it.download_url;
  document.body.appendChild(a);
  a.click();
  a.remove();
};
</script>

<style scoped>
.exports-tray {
  position: fixed; right: 20px; bottom: 20px; width: 360px; max-height: 70vh; overflow-y: auto;
  background: #fff; border: 1px solid #e4e7ed; border-radius: 10px;
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.16); z-index: 3000;
}
.tray-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; border-bottom: 1px solid #eef0f3; font-weight: 600; font-size: 14px;
  color: #1f2937; background: #f9fafb; border-radius: 10px 10px 0 0; position: sticky; top: 0; z-index: 1;
}
.tray-header > span { display: flex; align-items: center; gap: 6px; }
.tray-body { padding: 6px 10px 10px; display: flex; flex-direction: column; gap: 8px; }
.export-item { border: 1px solid #eef0f3; border-radius: 8px; padding: 10px 12px; background: #fff; }
.item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.item-label { flex: 1; font-size: 13px; font-weight: 600; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-close { margin-left: auto; color: #9ca3af; }
.item-bottom { display: flex; justify-content: space-between; margin-top: 4px; font-size: 11px; color: #6b7280; }
.item-actions { margin-top: 8px; }
.item-error { margin-top: 6px; font-size: 12px; color: #f56c6c; }
.item-cancelled { margin-top: 6px; font-size: 12px; color: #e6a23c; }
.tray-fade-enter-active, .tray-fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.tray-fade-enter-from, .tray-fade-leave-to { opacity: 0; transform: translateY(10px); }
</style>
