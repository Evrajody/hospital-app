<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Date début">
          <el-date-picker
            v-model="dateDebut"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date début"
          />
        </el-form-item>
        <el-form-item label="Date fin">
          <el-date-picker
            v-model="dateFin"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date fin"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="reglements.length === 0" class="empty-state">
        <el-empty description="Aucun règlement trouvé pour cette période" />
      </div>
      <template v-else>
        <!-- Actions (visible quand sélection) -->
        <div v-if="selectedIds.length > 0" class="selection-bar">
          <span>{{ selectedIds.length }} règlement(s) sélectionné(s)</span>
          <div class="selection-actions">
            <el-button type="primary" @click="exportMandats">
              <el-icon><Document /></el-icon>
              Bordereaux de règlement
            </el-button>
            <el-button type="success" @click="exportBordereau">
              <el-icon><List /></el-icon>
              Bordereau de transmission
            </el-button>
          </div>
        </div>

        <el-table
          ref="tableRef"
          style="width: 100%"
          :data="reglements"
          border
          size="small"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="45" />
          <el-table-column label="Fournisseur" min-width="250">
            <template #default="{ row }">{{ row.fournisseur_label }}</template>
          </el-table-column>
          <el-table-column prop="numero_piece" label="N° Pièce" min-width="120" />
          <el-table-column label="Mt Règlement" min-width="120" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
          <el-table-column prop="reference" label="Référence" min-width="150" />
          <el-table-column label="Date Règlement" min-width="110" align="center">
            <template #default="{ row }">{{ row.date_reglement_formatted }}</template>
          </el-table-column>
          <el-table-column prop="institution" label="Institution" min-width="180" />
          <el-table-column prop="beneficiaire" label="Bénéficiaire" min-width="180" />
        </el-table>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { Document, List } from '@element-plus/icons-vue';
import { useMontant } from '@/Composables/useMontant';

const { formatMontant } = useMontant();

const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const reglements = ref([]);
const selectedIds = ref([]);
const tableRef = ref(null);

onMounted(() => fetchData());

const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(r => r.id);
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (dateDebut.value) params.append('date_debut', dateDebut.value);
    if (dateFin.value) params.append('date_fin', dateFin.value);
    const res = await fetch(`/rapports/fournisseurs/api/bordereau-transmission?${params}`);
    const json = await res.json();
    reglements.value = json.reglements || [];
    selectedIds.value = [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const exportMandats = () => {
  const ids = selectedIds.value.join(',');
  window.open(`/rapports/fournisseurs/pdf/mandats?ids=${ids}`, '_blank');
};

const exportBordereau = () => {
  const ids = selectedIds.value.join(',');
  window.open(`/rapports/fournisseurs/pdf/bordereau-transmission?ids=${ids}`, '_blank');
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.selection-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #ecf5ff;
  border: 1px solid #b3d8ff;
  padding: 10px 16px;
  border-radius: 4px;
  margin-bottom: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #409eff;
}
.selection-actions { display: flex; gap: 8px; }
</style>
