<template>
  <el-drawer
    v-model="state.visible"
    :title="state.title"
    direction="rtl"
    size="72%"
    :destroy-on-close="true"
    class="pdf-viewer-drawer"
  >
    <div class="pdf-viewer-body">
      <iframe
        v-if="streamUrl"
        :src="streamUrl"
        class="pdf-frame"
        title="Aperçu du document PDF"
      ></iframe>
    </div>

    <template #footer>
      <div class="pdf-viewer-actions">
        <el-button @click="close">Fermer</el-button>
        <el-button :icon="Printer" @click="printPdf">Imprimer</el-button>
        <el-button type="primary" :icon="Download" @click="downloadPdf">
          Télécharger
        </el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { computed } from 'vue';
import { Download, Printer } from '@element-plus/icons-vue';
import { usePdfViewer } from '@/Composables/usePdfViewer';

const { state, close } = usePdfViewer();

const withParam = (url, kv) => url + (url.includes('?') ? '&' : '?') + kv;

// Aperçu : on stream le PDF en inline dans l'iframe
const streamUrl = computed(() => (state.url ? withParam(state.url, 'action=stream') : ''));

// Téléchargement explicite : URL sans action => Content-Disposition: attachment
const downloadPdf = () => {
  if (!state.url) return;
  const a = document.createElement('a');
  a.href = state.url;
  a.rel = 'noopener';
  document.body.appendChild(a);
  a.click();
  a.remove();
};

const printPdf = () => {
  const frame = document.querySelector('.pdf-viewer-drawer iframe');
  if (frame && frame.contentWindow) {
    frame.contentWindow.focus();
    frame.contentWindow.print();
  }
};
</script>

<style scoped>
.pdf-viewer-body {
  height: 100%;
  background: #525659;
}

.pdf-frame {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
}

.pdf-viewer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>

<style>
/* Le corps du drawer doit remplir toute la hauteur pour l'iframe */
.pdf-viewer-drawer .el-drawer__body {
  padding: 0;
  height: 100%;
  overflow: hidden;
}
</style>
