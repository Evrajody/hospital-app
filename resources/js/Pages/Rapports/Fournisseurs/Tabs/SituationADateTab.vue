<template>
  <div class="tab-content">
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Date de référence">
          <el-date-picker v-model="dateRef" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
        </el-form-item>
        <el-form-item label="Type">
          <el-select v-model="typeSolde" style="width: 180px">
            <el-option value="impayes" label="Avec solde impayé" />
            <el-option value="tous" label="Tous les fournisseurs" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <div v-if="fetched" class="results-section">
      <div v-if="data.length === 0" class="empty-state">
        <el-empty description="Aucun fournisseur trouvé" />
      </div>
      <template v-else>
        <el-table :data="data" border size="small" stripe>
          <el-table-column prop="numero_compte" label="Compte" min-width="120" />
          <el-table-column prop="ifu" label="IFU" min-width="130" />
          <el-table-column prop="nom" label="Fournisseur" min-width="240" />
          <el-table-column label="Total factures" min-width="140" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_factures) }}</template>
          </el-table-column>
          <el-table-column label="Total payé" min-width="140" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_paye) }}</template>
          </el-table-column>
          <el-table-column label="Solde" min-width="140" align="right">
            <template #default="{ row }">
              <strong :style="{ color: row.solde > 0 ? '#f56c6c' : '#67c23a' }">{{ formatMontant(row.solde) }}</strong>
            </template>
          </el-table-column>
        </el-table>

        <div class="totaux">
          <span>Total factures : <strong>{{ formatMontant(totaux.total_factures) }}</strong></span>
          <span>Total payé : <strong>{{ formatMontant(totaux.total_paye) }}</strong></span>
          <span>Solde général : <strong style="color: #f56c6c">{{ formatMontant(totaux.solde) }}</strong></span>
        </div>

        <div class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { useMontant } from '@/Composables/useMontant';

const { formatMontant } = useMontant();

const dateRef = ref(new Date().toISOString().split('T')[0]);
const typeSolde = ref('impayes');
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);
const totaux = ref({ total_factures: 0, total_paye: 0, solde: 0 });

const buildParams = () => {
  const params = new URLSearchParams();
  if (dateRef.value) params.append('date', dateRef.value);
  params.append('type', typeSolde.value);
  return params;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await fetch(`/rapports/fournisseurs/api/situation-a-date?${buildParams()}`);
    const json = await res.json();
    data.value = json.data || [];
    totaux.value = json.totaux || { total_factures: 0, total_paye: 0, solde: 0 };
    fetched.value = true;
  } catch {
    ElMessage.error('Erreur chargement');
  } finally {
    loading.value = false;
  }
};

const exportPdf = () => {
  window.open(`/rapports/fournisseurs/pdf/situation-a-date?${buildParams()}`, '_blank');
};

const exportExcel = () => {
  window.open(`/rapports/fournisseurs/excel/situation-a-date?${buildParams()}`, '_blank');
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.totaux { display: flex; gap: 24px; margin-top: 12px; padding: 10px 16px; background: #f5f7fa; border-radius: 4px; font-size: 13px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
