<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Période" required>
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
          <el-button type="primary" @click="fetchData" :loading="loading" :disabled="!dateRange || dateRange.length < 2">Afficher</el-button>
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
            <el-button type="warning" @click="exportExcel">
              Exporter Excel
            </el-button>
          </div>
        </div>

        <el-table
          ref="tableRef"
          style="width: 100%"
          :data="reglements"
          max-height="calc(100vh - 300px)"
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
import { usePdfViewer } from '@/Composables/usePdfViewer';
const { openPdf } = usePdfViewer();
import { useAsyncExport } from '@/Composables/useAsyncExport';
const { startExport } = useAsyncExport();
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { Document, List } from '@element-plus/icons-vue';
import { useMontant } from '@/Composables/useMontant';

const { formatMontant } = useMontant();

// Défaut : mois courant — évite de charger TOUS les règlements au montage (chargement long).
const _now = new Date();
const _pad = (n) => String(n).padStart(2, '0');
const _firstDay = `${_now.getFullYear()}-${_pad(_now.getMonth() + 1)}-01`;
const _lastDay = `${_now.getFullYear()}-${_pad(_now.getMonth() + 1)}-${_pad(new Date(_now.getFullYear(), _now.getMonth() + 1, 0).getDate())}`;

const dateRange = ref([_firstDay, _lastDay]);
const loading = ref(false);
const fetched = ref(false);
const reglements = ref([]);
const selectedIds = ref([]);
const tableRef = ref(null);

const handleSelectionChange = (selection) => {
  selectedIds.value = selection.map(r => r.id);
};

const fetchData = async () => {
  if (!dateRange.value || dateRange.value.length < 2) {
    ElMessage.warning('Veuillez sélectionner une période');
    return;
  }
  loading.value = true;
  try {
    const [debut, fin] = dateRange.value;
    const params = new URLSearchParams();
    params.append('date_debut', debut);
    params.append('date_fin', fin);
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
  startExport('rapports-fournisseurs.mandats', 'pdf', { ids });
};

const exportBordereau = () => {
  const ids = selectedIds.value.join(',');
  startExport('rapports-fournisseurs.bordereau-transmission', 'pdf', { ids });
};

const exportExcel = () => {
  const ids = selectedIds.value.join(',');
  startExport('rapports-fournisseurs.bordereau-transmission', 'excel', { ids });
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
  color: var(--el-color-primary);
}
.selection-actions { display: flex; gap: 8px; }
</style>
