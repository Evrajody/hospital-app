<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <div class="page-header">
        <div>
          <h1 class="page-title">Bordereau de transmission</h1>
          <p class="page-subtitle">Sélectionnez les règlements à transmettre, puis générez les documents</p>
        </div>
      </div>

      <!-- Filtres -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" class="filter-form">
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
      </el-card>

      <!-- Résultats -->
      <el-card v-if="fetched" class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">{{ total }} règlement(s) trouvé(s)</span>
          </div>
        </template>
        <div v-if="reglements.length === 0" class="empty-state">
          <el-empty description="Aucun règlement trouvé pour cette période" />
        </div>
        <el-table
          v-else
          ref="tableRef"
          style="width: 100%"
          :data="reglements"
          border
          size="small"
          :max-height="500"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="45" fixed="left" />
          <el-table-column label="Fournisseur" min-width="250" fixed="left">
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

        <!-- Pagination -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="total"
            layout="total, sizes, prev, pager, next, jumper"
            background
            @size-change="fetchData"
            @current-change="fetchData"
          />
        </div>
      </el-card>
    </div>

    <!-- Barre d'actions FLOTTANTE : visible dès qu'une ligne est cochée, accessible à tout moment -->
    <transition name="float-bar">
      <div v-if="selectedIds.length > 0" class="floating-actions">
        <span class="selection-count">{{ selectedIds.length }} règlement(s) sélectionné(s)</span>
        <div class="selection-actions">
          <el-button type="primary" @click="exportMandats">
            <el-icon><Document /></el-icon> Bordereaux de règlement
          </el-button>
          <el-button type="success" @click="exportBordereau">
            <el-icon><List /></el-icon> Bordereau de transmission
          </el-button>
          <el-button type="warning" @click="exportExcel">Exporter Excel</el-button>
        </div>
      </div>
    </transition>
  </AppLayout>
</template>

<script setup>
import { useAsyncExport } from '@/Composables/useAsyncExport';
const { startExport } = useAsyncExport();
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { Document, List } from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useMontant } from '@/Composables/useMontant';

defineProps({
  user: { type: Object, default: () => null },
});

const { formatMontant } = useMontant();

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Bordereau de transmission', path: '/factures-fournisseurs/bordereau-transmission' },
];

// Défaut : mois courant (évite de charger tous les règlements).
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
const currentPage = ref(1);
const pageSize = ref(20);
const total = ref(0);

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
    params.append('page', currentPage.value);
    params.append('per_page', pageSize.value);
    const res = await fetch(`/factures-fournisseurs/bordereau-transmission/data?${params}`);
    const json = await res.json();
    reglements.value = json.reglements || [];
    total.value = json.total || 0;
    selectedIds.value = [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const exportMandats = () => {
  startExport('rapports-fournisseurs.mandats', 'pdf', { ids: selectedIds.value.join(',') });
};
const exportBordereau = () => {
  startExport('rapports-fournisseurs.bordereau-transmission', 'pdf', { ids: selectedIds.value.join(',') });
};
const exportExcel = () => {
  startExport('rapports-fournisseurs.bordereau-transmission', 'excel', { ids: selectedIds.value.join(',') });
};
</script>

<style scoped>
.page-container { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title { font-size: 24px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #6b7280; margin: 0; }
.filter-card, .table-card { border-radius: 8px; }
.filter-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; }
.empty-state { padding: 40px 0; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-weight: 600; color: #374151; }
.pagination-container { display: flex; justify-content: flex-end; margin-top: 16px; }

/* Barre d'actions flottante, toujours accessible en bas de l'écran */
.floating-actions {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 3000;
  display: flex;
  align-items: center;
  gap: 20px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
  border-radius: 10px;
  padding: 12px 20px;
  max-width: 92vw;
  flex-wrap: wrap;
  justify-content: center;
}
.selection-count { font-size: 13px; font-weight: 600; color: var(--el-color-primary); white-space: nowrap; }
.selection-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.float-bar-enter-active, .float-bar-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.float-bar-enter-from, .float-bar-leave-to { opacity: 0; transform: translate(-50%, 12px); }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
</style>
