<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <div class="page-header">
        <div>
          <h1 class="page-title">Factures et Soldes</h1>
          <p class="page-subtitle">Solde de chaque facture fournisseur — cliquez sur une ligne pour voir l'état de règlement</p>
        </div>
      </div>

      <!-- Filtres (identiques à la liste des factures) -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" class="filter-form">
          <el-form-item label="Recherche">
            <el-input v-model="filters.search" placeholder="N° Pièce, référence..." :prefix-icon="Search" clearable style="width: 250px" @input="debouncedSearch" @clear="handleSearch" />
          </el-form-item>
          <el-form-item label="Fournisseur">
            <el-select v-model="filters.fournisseur_id" placeholder="Tous" clearable filterable style="width: 200px" @change="handleSearch">
              <el-option v-for="f in fournisseurs" :key="f.id" :label="f.nom" :value="f.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Statut Paiement">
            <el-select v-model="filters.statut" placeholder="Tous" clearable style="width: 150px" @change="handleSearch">
              <el-option label="Impayée" value="impayee" />
              <el-option label="Partielle" value="partielle" />
              <el-option label="Payée" value="payee" />
            </el-select>
          </el-form-item>
          <el-form-item label="Période">
            <el-date-picker v-model="filters.date_range" type="daterange" range-separator="à" start-placeholder="Date début" end-placeholder="Date fin" value-format="YYYY-MM-DD" style="width: 280px" @change="handleSearch" />
          </el-form-item>
          <el-form-item>
            <el-button @click="handleReset"><el-icon><RefreshLeft /></el-icon> Réinitialiser</el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <el-card class="table-card" shadow="never">
        <template #header>
          <span class="card-title">{{ pagination.total }} facture(s)</span>
        </template>

        <el-table ref="tableRef" :data="factures" :height="tableHeight" stripe border style="width: 100%" show-summary :summary-method="getSummary" class="clickable-rows" @row-click="handleRowClick">
          <el-table-column label="Fournisseur" min-width="280">
            <template #default="{ row }">{{ row.fournisseur_label }}</template>
          </el-table-column>
          <el-table-column prop="numero" label="N° Pièce" min-width="120" />
          <el-table-column label="Date Enregistrement" min-width="130" align="center">
            <template #default="{ row }">{{ formatDate(row.date_facture) }}</template>
          </el-table-column>
          <el-table-column label="Montant Dû" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_ttc) }}</template>
          </el-table-column>
          <el-table-column label="Total Règlement" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_paye) }}</template>
          </el-table-column>
          <el-table-column label="Solde" min-width="130" align="right">
            <template #default="{ row }">
              <span :style="{ color: row.reste_a_payer > 0 ? '#cc0000' : 'inherit', fontWeight: row.reste_a_payer > 0 ? 'bold' : 'normal' }">{{ formatMontant(row.reste_a_payer) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center" fixed="right">
            <template #default="{ row }">
              <el-button size="small" type="primary" :icon="View" @click.stop="openEtat(row)">Détails</el-button>
            </template>
          </el-table-column>
        </el-table>

        <div style="display:flex; justify-content:flex-end; margin-top:16px;">
          <el-pagination
            :current-page="pagination.current_page"
            :page-size="pagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            background
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search, RefreshLeft, View } from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePdfViewer } from '@/Composables/usePdfViewer';
import { useMontant } from '@/Composables/useMontant';
import { useTableHeight } from '@/Composables/useTableHeight';
import { debounce } from '@/utils/debounce';

const { openPdf } = usePdfViewer();

const tableRef = ref(null);
const { tableHeight } = useTableHeight(tableRef, 84);

const props = defineProps({
  factures: { type: Array, default: () => [] },
  totaux: { type: Object, default: () => ({}) },
  fournisseurs: { type: Array, default: () => [] },
  pagination: { type: Object, default: () => ({ current_page: 1, per_page: 20, total: 0, last_page: 1 }) },
  filters: { type: Object, default: () => ({}) },
  user: { type: Object, default: () => null },
});

const { formatMontant } = useMontant();

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures et Soldes', path: '/factures-fournisseurs/soldes' },
];

const filters = ref({
  search: props.filters?.search || '',
  fournisseur_id: props.filters?.fournisseur_id || null,
  statut: props.filters?.statut || '',
  date_range: (props.filters?.date_debut && props.filters?.date_fin) ? [props.filters.date_debut, props.filters.date_fin] : null,
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '-';

const buildParams = () => {
  const p = {};
  if (filters.value.search) p.search = filters.value.search;
  if (filters.value.fournisseur_id) p.fournisseur_id = filters.value.fournisseur_id;
  if (filters.value.statut) p.statut = filters.value.statut;
  if (filters.value.date_range && filters.value.date_range.length === 2) {
    p.date_debut = filters.value.date_range[0];
    p.date_fin = filters.value.date_range[1];
  }
  return p;
};

const handleSearch = () => {
  router.get('/factures-fournisseurs/soldes', buildParams(), { preserveState: true, preserveScroll: true });
};
const debouncedSearch = debounce(handleSearch, 350);

const handleReset = () => {
  filters.value = { search: '', fournisseur_id: null, statut: '', date_range: null };
  handleSearch();
};

const handleSizeChange = (size) => {
  router.get('/factures-fournisseurs/soldes', { ...buildParams(), per_page: size, page: 1 }, { preserveState: true });
};
const handlePageChange = (page) => {
  router.get('/factures-fournisseurs/soldes', { ...buildParams(), page }, { preserveState: true, preserveScroll: true });
};

// Ouvre l'état de règlement en PDF dans l'offcanvas (PdfViewerDrawer global) : aperçu inline
// + téléchargement. Le drawer ajoute action=stream pour l'affichage, garde l'URL nue pour le DL.
const openEtat = (row) => {
  openPdf(`/factures-fournisseurs/${row.id}/etat-reglement-pdf`, `État de Règlement — ${row.numero}`);
};
const handleRowClick = (row) => openEtat(row);

const getSummary = ({ columns }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAUX'; return; }
    if (i === 1 || i === 2) { sums[i] = ''; return; }
    const keys = { 3: 'montant_ttc', 4: 'montant_paye', 5: 'reste_a_payer' };
    sums[i] = keys[i] ? formatMontant(props.totaux[keys[i]] || 0) : '';
  });
  return sums;
};
</script>

<style scoped>
.page-container { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title { font-size: 24px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #6b7280; margin: 0; }
.filter-card, .table-card { border-radius: 8px; }
.filter-form { display: flex; flex-wrap: wrap; gap: 8px; }
.card-title { font-size: 16px; font-weight: 600; color: #374151; }
.clickable-rows :deep(.el-table__row) { cursor: pointer; }
:deep(.el-table th) { background-color: #f9fafb; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
</style>
