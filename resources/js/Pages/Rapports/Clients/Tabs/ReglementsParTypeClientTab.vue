<template>
  <div class="tab-content">
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Date début">
          <el-date-picker v-model="dateDebut" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
        </el-form-item>
        <el-form-item label="Date fin">
          <el-date-picker v-model="dateFin" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <div v-if="fetched" class="results-section">
      <div v-if="groupes.length === 0" class="empty-state">
        <el-empty description="Aucun règlement trouvé pour la période" />
      </div>
      <template v-else>
        <el-table :data="groupes" border size="small" stripe>
          <el-table-column prop="label" label="Type de client" min-width="200" />
          <el-table-column prop="nombre" label="Nombre de règlements" min-width="180" align="right" />
          <el-table-column label="Total encaissé" min-width="180" align="right">
            <template #default="{ row }">{{ formatMontant(row.total) }}</template>
          </el-table-column>
          <el-table-column label="Part" min-width="100" align="right">
            <template #default="{ row }">{{ percent(row.total) }}%</template>
          </el-table-column>
        </el-table>

        <div class="totaux">
          <strong>Total général : {{ formatMontant(totalGeneral) }}</strong>
        </div>

        <div class="actions-bar">
          <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { useMontant } from '@/Composables/useMontant';

const { formatMontant } = useMontant();

const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const groupes = ref([]);
const totalGeneral = ref(0);

const percent = (value) => {
  if (!totalGeneral.value) return 0;
  return ((value / totalGeneral.value) * 100).toFixed(1);
};

const buildParams = () => {
  const params = new URLSearchParams();
  if (dateDebut.value) params.append('date_debut', dateDebut.value);
  if (dateFin.value) params.append('date_fin', dateFin.value);
  return params;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await fetch(`/rapports/clients/api/reglements-par-type-client?${buildParams()}`);
    const json = await res.json();
    groupes.value = json.groupes || [];
    totalGeneral.value = json.total_general || 0;
    fetched.value = true;
  } catch {
    ElMessage.error('Erreur chargement');
  } finally {
    loading.value = false;
  }
};

const exportExcel = () => {
  window.open(`/rapports/clients/excel/reglements-par-type-client?${buildParams()}`, '_blank');
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.totaux { margin-top: 12px; padding: 10px 16px; background: #f5f7fa; border-radius: 4px; text-align: right; font-size: 14px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
