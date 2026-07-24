<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Période">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="à"
            start-placeholder="Date début"
            end-placeholder="Date fin"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            unlink-panels
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="lignes.length === 0" class="empty-state">
        <el-empty description="Aucune facture éditée pour la période sélectionnée" />
      </div>
      <template v-else>
        <div class="annee-header-box">
          <strong>{{ periodeLabel }}</strong> — {{ lignes.length }} facture(s)
        </div>

        <PaginatedTable style="width: 100%" :data="lignes" border size="small" stripe show-summary :summary-method="getSummary">
          <el-table-column prop="numero" label="N°" min-width="50" align="center" />
          <el-table-column prop="reference" label="N° Facture" min-width="130" />
          <el-table-column prop="date_facture" label="Date Facture" min-width="110" />
          <el-table-column prop="client" label="Nom Client" min-width="320" />
          <el-table-column label="Montant" width="145" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
        </PaginatedTable>

        <div class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
          <el-button @click="printReport">Imprimer</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { usePdfViewer } from '@/Composables/usePdfViewer';
const { openPdf } = usePdfViewer();
import { useAsyncExport } from '@/Composables/useAsyncExport';
const { startExport } = useAsyncExport();
const REPORT_KEY = 'rapports-clients.factures-editees';
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
import PaginatedTable from '@/Components/PaginatedTable.vue';

const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const lignes = ref([]);
const total = ref(0);
const periodeLabel = ref('');

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '';

const periodeParams = computed(() => {
  const [debut, fin] = dateRange.value || [];
  const p = {};
  if (debut) p.date_debut = debut;
  if (fin) p.date_fin = fin;
  return p;
});

const fetchData = async () => {
  const [debut, fin] = dateRange.value || [];
  if (!debut || !fin) {
    ElMessage.warning('Veuillez sélectionner une période (date début et date fin)');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams(periodeParams.value);
    const res = await fetch(`/rapports/clients/api/factures-editees?${params}`);
    const json = await res.json();
    lignes.value = json.lignes || [];
    total.value = json.total || 0;
    periodeLabel.value = `Période du ${formatDate(debut)} au ${formatDate(fin)}`;
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummary = ({ columns }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    if (i === 4) { sums[i] = formatMontant(total.value); return; }
    sums[i] = '';
  });
  return sums;
};

const exportPdf = () => {
  startExport(REPORT_KEY, 'pdf', periodeParams.value);
};

const exportExcel = () => {
  startExport(REPORT_KEY, 'excel', periodeParams.value);
};

const printReport = () => {
  const params = new URLSearchParams({ ...periodeParams.value, action: 'stream' });
  const w = window.open(`/rapports/clients/pdf/factures-editees?${params}`, '_blank');
  if (w) w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.annee-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
