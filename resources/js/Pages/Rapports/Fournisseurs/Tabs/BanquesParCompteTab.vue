<template>
  <div class="tab-content">
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

    <div v-if="fetched" class="results-section">
      <div v-if="data.length === 0" class="empty-state">
        <el-empty description="Aucun compte bancaire trouvé" />
      </div>
      <template v-else>
        <PaginatedTable :data="data" border size="small" stripe>
          <el-table-column prop="banque" label="Banque" min-width="180" />
          <el-table-column prop="numero_compte" label="N° Compte" min-width="160" />
          <el-table-column label="Approvisionnements" min-width="170" align="right">
            <template #default="{ row }">{{ formatMontant(row.approvisionnements) }}</template>
          </el-table-column>
          <el-table-column label="Débits (règlements)" min-width="170" align="right">
            <template #default="{ row }">{{ formatMontant(row.debits) }}</template>
          </el-table-column>
          <el-table-column label="Solde actuel" min-width="150" align="right">
            <template #default="{ row }">
              <strong :style="{ color: row.solde < 0 ? '#f56c6c' : '#67c23a' }">{{ formatMontant(row.solde) }}</strong>
            </template>
          </el-table-column>
        </PaginatedTable>

        <div class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { usePdfViewer } from '@/Composables/usePdfViewer';
const { openPdf } = usePdfViewer();
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { useMontant } from '@/Composables/useMontant';
import PaginatedTable from '@/Components/PaginatedTable.vue';

const { formatMontant } = useMontant();

const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);

const buildParams = () => {
  const params = new URLSearchParams();
  const [debut, fin] = dateRange.value || [];
  if (debut) params.append('date_debut', debut);
  if (fin) params.append('date_fin', fin);
  return params;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await fetch(`/rapports/fournisseurs/api/banques-par-compte?${buildParams()}`);
    const json = await res.json();
    data.value = json.data || [];
    fetched.value = true;
  } catch {
    ElMessage.error('Erreur chargement');
  } finally {
    loading.value = false;
  }
};

const exportPdf = () => {
  openPdf(`/rapports/fournisseurs/pdf/banques-par-compte?${buildParams()}`, 'Aperçu du rapport');
};

const exportExcel = () => {
  window.open(`/rapports/fournisseurs/excel/banques-par-compte?${buildParams()}`, '_blank');
};

onMounted(() => fetchData());
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
