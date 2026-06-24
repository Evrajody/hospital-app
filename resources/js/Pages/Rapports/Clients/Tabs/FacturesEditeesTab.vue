<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Année">
          <el-date-picker
            v-model="annee"
            type="year"
            format="YYYY"
            value-format="YYYY"
            placeholder="Sélectionner une année"
            style="width: 180px"
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
        <el-empty :description="`Aucune facture éditée pour l'année ${anneeAffichee}`" />
      </div>
      <template v-else>
        <div class="annee-header-box">
          <strong>Année {{ anneeAffichee }}</strong> — {{ lignes.length }} facture(s)
        </div>

        <PaginatedTable style="width: 100%" :data="lignes" border size="small" stripe show-summary :summary-method="getSummary">
          <el-table-column prop="numero" label="N°" min-width="50" align="center" />
          <el-table-column prop="reference" label="N° Facture" min-width="130" />
          <el-table-column prop="date_facture" label="Date Facture" min-width="110" />
          <el-table-column prop="client" label="Nom Client" min-width="220" />
          <el-table-column label="Montant" min-width="140" align="right">
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
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import PaginatedTable from '@/Components/PaginatedTable.vue';

const annee = ref(String(new Date().getFullYear()));
const anneeAffichee = ref('');
const loading = ref(false);
const fetched = ref(false);
const lignes = ref([]);
const total = ref(0);

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (!annee.value) {
    ElMessage.warning('Veuillez sélectionner une année');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ annee: annee.value });
    const res = await fetch(`/rapports/clients/api/factures-editees?${params}`);
    const json = await res.json();
    lignes.value = json.lignes || [];
    total.value = json.total || 0;
    anneeAffichee.value = json.annee || annee.value;
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
  startExport(REPORT_KEY, 'pdf', { annee: annee.value });
};

const exportExcel = () => {
  startExport(REPORT_KEY, 'excel', { annee: annee.value });
};

const printReport = () => {
  const w = window.open(`/rapports/clients/pdf/factures-editees?annee=${annee.value}&action=stream`, '_blank');
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
