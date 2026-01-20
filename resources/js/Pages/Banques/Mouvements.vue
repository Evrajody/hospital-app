<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <div class="banque-icon-large">
            <el-icon :size="32" :color="banque.type === 'especes' ? '#f59e0b' : '#2563eb'">
              <component :is="banque.type === 'especes' ? Coin : CreditCard" />
            </el-icon>
          </div>
          <div>
            <h1 class="page-title">{{ banque.nom }}</h1>
            <p class="page-subtitle">{{ banque.numero }} • Solde actuel: {{ formatMontant(banque.solde) }}</p>
          </div>
        </div>
        <el-button @click="handleBack">
          <el-icon><ArrowLeft /></el-icon>
          Retour aux comptes
        </el-button>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-solde">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><Wallet /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(banque.solde) }}</div>
                <div class="stat-label">Solde Actuel</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-entrees">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><TopRight /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.total_entrees) }}</div>
                <div class="stat-label">Total Entrées</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-sorties">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><BottomRight /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.total_sorties) }}</div>
                <div class="stat-label">Total Sorties</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-count">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><DocumentChecked /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.nombre_mouvements }}</div>
                <div class="stat-label">Mouvements</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Filters Card -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" :model="filters" class="filter-form">
          <el-form-item label="Recherche">
            <el-input
              v-model="filters.search"
              placeholder="Référence, description..."
              :prefix-icon="Search"
              clearable
              style="width: 250px"
              @input="handleSearch"
            />
          </el-form-item>

          <el-form-item label="Type">
            <el-select
              v-model="filters.type"
              placeholder="Tous"
              clearable
              style="width: 150px"
              @change="handleSearch"
            >
              <el-option label="Entrées" value="entree" />
              <el-option label="Sorties" value="sortie" />
            </el-select>
          </el-form-item>

          <el-form-item label="Période">
            <el-date-picker
              v-model="filters.date_range"
              type="daterange"
              range-separator="à"
              start-placeholder="Date début"
              end-placeholder="Date fin"
              style="width: 280px"
              @change="handleSearch"
            />
          </el-form-item>

          <el-form-item>
            <el-button type="default" @click="handleReset">
              <el-icon><RefreshLeft /></el-icon>
              Réinitialiser
            </el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Table Card -->
      <el-card class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">
              {{ pagination.total }} mouvement(s) trouvé(s)
            </span>
            <div class="card-actions">
              <el-button :icon="Download" size="small" @click="handleExport">
                Exporter
              </el-button>
              <el-button :icon="Printer" size="small" @click="handlePrint">
                Imprimer
              </el-button>
              <el-button :icon="Refresh" size="small" circle @click="handleRefresh" />
            </div>
          </div>
        </template>

        <el-table
          v-loading="loading"
          :data="mouvements"
          stripe
          style="width: 100%"
          :row-class-name="getRowClassName"
        >
          <el-table-column prop="date" label="Date" width="120" sortable>
            <template #default="{ row }">
              {{ formatDate(row.date) }}
            </template>
          </el-table-column>

          <el-table-column prop="type" label="Type" width="100" align="center">
            <template #default="{ row }">
              <el-tag :type="row.type === 'entree' ? 'success' : 'danger'" size="small">
                <el-icon>
                  <component :is="row.type === 'entree' ? TopRight : BottomRight" />
                </el-icon>
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="reference" label="Référence" width="150">
            <template #default="{ row }">
              <span v-if="row.reference" class="reference">{{ row.reference }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="description" label="Description" min-width="250">
            <template #default="{ row }">
              <div class="description">{{ row.description }}</div>
              <div v-if="row.origine" class="origine">
                <el-icon><InfoFilled /></el-icon>
                {{ getOrigineLabel(row.origine) }}
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="montant" label="Montant" width="150" align="right">
            <template #default="{ row }">
              <span :class="getMontantClass(row.type)">
                {{ row.type === 'entree' ? '+' : '-' }}{{ formatMontant(row.montant) }}
              </span>
            </template>
          </el-table-column>

          <el-table-column prop="solde_apres" label="Solde après" width="150" align="right">
            <template #default="{ row }">
              <strong class="solde-apres">{{ formatMontant(row.solde_apres) }}</strong>
            </template>
          </el-table-column>

          <el-table-column prop="user" label="Saisi par" width="140">
            <template #default="{ row }">
              <div v-if="row.user" class="user-cell">
                <el-icon><User /></el-icon>
                <span>{{ row.user.name }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="120" fixed="right" align="center">
            <template #default="{ row }">
              <el-button :icon="View" size="small" @click="handleView(row)">
                Détails
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- Pagination -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="pagination.current_page"
            v-model:page-size="pagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>

      <!-- Detail Modal -->
      <el-dialog
        v-model="detailDialogVisible"
        title="Détails du mouvement"
        width="700px"
        :close-on-click-modal="false"
      >
        <div v-if="selectedMouvement" class="detail-modal-content">
          <!-- Type Badge -->
          <div class="mouvement-type-badge">
            <el-tag :type="selectedMouvement.type === 'entree' ? 'success' : 'danger'" size="large">
              <el-icon :size="20">
                <component :is="selectedMouvement.type === 'entree' ? TopRight : BottomRight" />
              </el-icon>
              <span style="margin-left: 8px; font-size: 16px;">
                {{ selectedMouvement.type === 'entree' ? 'ENTRÉE' : 'SORTIE' }}
              </span>
            </el-tag>
          </div>

          <!-- Main Info -->
          <el-descriptions :column="2" border style="margin-top: 20px">
            <el-descriptions-item label="Date">
              <strong>{{ formatDate(selectedMouvement.date) }}</strong>
            </el-descriptions-item>
            <el-descriptions-item label="Référence">
              <span v-if="selectedMouvement.reference" class="reference-text">{{ selectedMouvement.reference }}</span>
              <span v-else class="text-muted">-</span>
            </el-descriptions-item>

            <el-descriptions-item label="Montant" :span="2">
              <div class="montant-display">
                <span :class="selectedMouvement.type === 'entree' ? 'montant-entree-large' : 'montant-sortie-large'">
                  {{ selectedMouvement.type === 'entree' ? '+' : '-' }}{{ formatMontant(selectedMouvement.montant) }}
                </span>
              </div>
            </el-descriptions-item>

            <el-descriptions-item label="Origine" :span="2" v-if="selectedMouvement.origine">
              <el-tag type="info" size="small">
                {{ getOrigineLabel(selectedMouvement.origine) }}
              </el-tag>
            </el-descriptions-item>

            <el-descriptions-item label="Description" :span="2">
              <div class="description-text">{{ selectedMouvement.description }}</div>
            </el-descriptions-item>
          </el-descriptions>

          <!-- Soldes -->
          <div class="soldes-section">
            <div class="solde-item">
              <div class="solde-item-label">Solde avant</div>
              <div class="solde-item-value solde-avant">
                {{ formatMontant(getSoldeAvant(selectedMouvement)) }}
              </div>
            </div>
            <div class="solde-arrow">
              <el-icon :size="24" :color="selectedMouvement.type === 'entree' ? '#059669' : '#dc2626'">
                <component :is="selectedMouvement.type === 'entree' ? TopRight : BottomRight" />
              </el-icon>
            </div>
            <div class="solde-item">
              <div class="solde-item-label">Solde après</div>
              <div class="solde-item-value solde-apres-large">
                {{ formatMontant(selectedMouvement.solde_apres) }}
              </div>
            </div>
          </div>

          <!-- Additional Info -->
          <el-descriptions :column="2" border style="margin-top: 20px">
            <el-descriptions-item label="Compte bancaire">
              <div class="compte-info">
                <el-icon :color="banque.type === 'especes' ? '#f59e0b' : '#2563eb'">
                  <component :is="banque.type === 'especes' ? Coin : CreditCard" />
                </el-icon>
                <div>
                  <div class="compte-nom">{{ banque.nom }}</div>
                  <div class="compte-numero-small">{{ banque.numero }}</div>
                </div>
              </div>
            </el-descriptions-item>

            <el-descriptions-item label="Saisi par" v-if="selectedMouvement.user">
              <div class="user-info-modal">
                <el-icon><User /></el-icon>
                <span>{{ selectedMouvement.user.name }}</span>
              </div>
            </el-descriptions-item>

            <el-descriptions-item label="Compte comptable" :span="2">
              <span class="compte-numero-text">{{ banque.compte_comptable }}</span>
            </el-descriptions-item>
          </el-descriptions>

          <!-- Pièce jointe (si existe) -->
          <el-alert
            v-if="selectedMouvement.piece_jointe"
            type="info"
            :closable="false"
            show-icon
            style="margin-top: 20px"
          >
            <template #title>
              <strong>Pièce jointe</strong>
            </template>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
              <el-icon :size="20"><Document /></el-icon>
              <span>{{ selectedMouvement.piece_jointe }}</span>
              <el-button size="small" type="primary" link>
                <el-icon><Download /></el-icon>
                Télécharger
              </el-button>
            </div>
          </el-alert>
        </div>

        <template #footer>
          <div class="dialog-footer">
            <el-button @click="detailDialogVisible = false">Fermer</el-button>
            <el-button type="primary" :icon="Printer" @click="handlePrintMouvement">
              Imprimer le reçu
            </el-button>
          </div>
        </template>
      </el-dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  ArrowLeft,
  Search,
  RefreshLeft,
  Download,
  Printer,
  Refresh,
  View,
  Wallet,
  TopRight,
  BottomRight,
  DocumentChecked,
  CreditCard,
  Coin,
  InfoFilled,
  User,
  Document
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  banque: {
    type: Object,
    required: true
  },
  mouvements: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total_entrees: 0,
      total_sorties: 0,
      nombre_mouvements: 0
    })
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      per_page: 20,
      total: 0
    })
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State
const loading = ref(false);
const detailDialogVisible = ref(false);
const selectedMouvement = ref(null);
const filters = reactive({
  search: '',
  type: '',
  date_range: null
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Banques', path: '/banques' },
  { title: props.banque.nom, path: '' }
];

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(Math.abs(montant) || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getOrigineLabel = (origine) => {
  const labels = {
    recette: 'Recette journalière',
    subvention: 'Subvention',
    donation: 'Donation',
    virement_interne: 'Virement interne',
    remboursement: 'Remboursement',
    reglement_fournisseur: 'Règlement fournisseur',
    autre: 'Autre'
  };
  return labels[origine] || origine;
};

const getMontantClass = (type) => {
  return type === 'entree' ? 'montant-entree' : 'montant-sortie';
};

const getRowClassName = ({ row }) => {
  return row.type === 'entree' ? 'row-entree' : 'row-sortie';
};

const handleSearch = () => {
  console.log('Searching with filters:', filters);
};

const handleReset = () => {
  filters.search = '';
  filters.type = '';
  filters.date_range = null;
  handleSearch();
};

const handleRefresh = () => {
  router.reload({ only: ['mouvements', 'stats', 'pagination'] });
};

const handleSizeChange = (size) => {
  console.log('Page size changed:', size);
};

const handlePageChange = (page) => {
  console.log('Page changed:', page);
};

const handleBack = () => {
  router.visit('/banques');
};

const getSoldeAvant = (mouvement) => {
  if (mouvement.type === 'entree') {
    return mouvement.solde_apres - mouvement.montant;
  } else {
    return mouvement.solde_apres + mouvement.montant;
  }
};

const handleView = (mouvement) => {
  selectedMouvement.value = mouvement;
  detailDialogVisible.value = true;
};

const handlePrintMouvement = () => {
  ElMessage.info('Impression du reçu de mouvement en cours de développement...');
};

const handleExport = () => {
  ElMessage.info('Export en cours de développement...');
};

const handlePrint = () => {
  ElMessage.info('Impression en cours de développement...');
};
</script>

<script>
export default {
  layout: null
};
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.banque-icon-large {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: #f3f4f6;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* Stats Cards */
.stats-row {
  margin-bottom: 4px;
}

.stat-card {
  border-radius: 8px;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 10px;
}

.stat-solde .stat-icon {
  background-color: #dbeafe;
  color: #2563eb;
}

.stat-entrees .stat-icon {
  background-color: #dcfce7;
  color: #16a34a;
}

.stat-sorties .stat-icon {
  background-color: #fee2e2;
  color: #dc2626;
}

.stat-count .stat-icon {
  background-color: #f3e8ff;
  color: #9333ea;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 2px;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
}

/* Filters */
.filter-card {
  border-radius: 8px;
}

.filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

/* Table */
.table-card {
  border-radius: 8px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.card-actions {
  display: flex;
  gap: 8px;
}

.reference {
  font-family: 'Courier New', monospace;
  font-size: 13px;
  color: #6b7280;
}

.description {
  font-size: 14px;
  color: #1f2937;
  margin-bottom: 4px;
}

.origine {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
  font-style: italic;
}

.montant-entree {
  color: #059669;
  font-weight: 600;
  font-size: 14px;
}

.montant-sortie {
  color: #dc2626;
  font-weight: 600;
  font-size: 14px;
}

.solde-apres {
  font-family: 'Courier New', monospace;
  color: #2563eb;
  font-size: 14px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
}

.text-muted {
  color: #d1d5db;
}

.pagination-container {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table th) {
  background-color: #f9fafb;
  font-weight: 600;
  color: #374151;
}

:deep(.row-entree) {
  background-color: #f0fdf4 !important;
}

:deep(.row-sortie) {
  background-color: #fef2f2 !important;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.stat-card .el-card__body) {
  padding: 16px;
}

/* Detail Modal */
.detail-modal-content {
  padding: 8px 0;
}

.mouvement-type-badge {
  text-align: center;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.montant-display {
  text-align: center;
  padding: 12px;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  border-radius: 6px;
}

.montant-entree-large {
  font-size: 32px;
  font-weight: 700;
  color: #059669;
  font-family: 'Courier New', monospace;
}

.montant-sortie-large {
  font-size: 32px;
  font-weight: 700;
  color: #dc2626;
  font-family: 'Courier New', monospace;
}

.reference-text {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2563eb;
}

.description-text {
  line-height: 1.6;
  color: #374151;
}

.soldes-section {
  display: flex;
  align-items: center;
  justify-content: space-around;
  margin: 24px 0;
  padding: 20px;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-radius: 8px;
  border: 2px solid #059669;
}

.solde-item {
  text-align: center;
}

.solde-item-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.solde-item-value {
  font-size: 24px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}

.solde-avant {
  color: #6b7280;
}

.solde-apres-large {
  color: #059669;
}

.solde-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
}

.compte-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.compte-nom {
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.compte-numero-small {
  font-size: 12px;
  font-family: 'Courier New', monospace;
  color: #6b7280;
}

.compte-numero-text {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2563eb;
  font-size: 14px;
}

.user-info-modal {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #6b7280;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

:deep(.el-dialog__header) {
  border-bottom: 1px solid #e5e7eb;
  padding: 16px 20px;
}

:deep(.el-dialog__body) {
  padding: 20px;
}

:deep(.el-dialog__footer) {
  border-top: 1px solid #e5e7eb;
  padding: 12px 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
}
</style>
