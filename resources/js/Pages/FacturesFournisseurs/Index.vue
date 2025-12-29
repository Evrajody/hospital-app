<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Factures Fournisseurs</h1>
          <p class="page-subtitle">Gestion et suivi des factures d'achat</p>
        </div>
        <el-button type="primary" size="large" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          Nouvelle Facture
        </el-button>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Document /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.total }}</div>
                <div class="stat-label">Total Factures</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-unpaid">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><WarningFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_impaye) }}</div>
                <div class="stat-label">Impayées</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-partial">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Clock /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_partiel) }}</div>
                <div class="stat-label">Partiellement Payées</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-paid">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><SuccessFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_paye) }}</div>
                <div class="stat-label">Payées</div>
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
              placeholder="N° facture, référence..."
              :prefix-icon="Search"
              clearable
              style="width: 250px"
              @input="handleSearch"
            />
          </el-form-item>

          <el-form-item label="Fournisseur">
            <el-select
              v-model="filters.fournisseur_id"
              placeholder="Tous"
              clearable
              filterable
              style="width: 200px"
              @change="handleSearch"
            >
              <el-option
                v-for="fournisseur in fournisseurs"
                :key="fournisseur.id"
                :label="fournisseur.nom"
                :value="fournisseur.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Statut Paiement">
            <el-select
              v-model="filters.statut_paiement"
              placeholder="Tous"
              clearable
              style="width: 150px"
              @change="handleSearch"
            >
              <el-option label="Impayée" value="impayee" />
              <el-option label="Partielle" value="partielle" />
              <el-option label="Payée" value="payee" />
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
              {{ pagination.total }} facture(s) trouvée(s)
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
          :data="factures"
          stripe
          style="width: 100%"
          @sort-change="handleSortChange"
        >
          <el-table-column prop="numero" label="N° Facture" width="140" sortable="custom">
            <template #default="{ row }">
              <el-link type="primary" @click="handleView(row)">
                <strong>{{ row.numero }}</strong>
              </el-link>
            </template>
          </el-table-column>

          <el-table-column prop="date_facture" label="Date" width="120" sortable="custom">
            <template #default="{ row }">
              {{ formatDate(row.date_facture) }}
            </template>
          </el-table-column>

          <el-table-column prop="fournisseur" label="Fournisseur" min-width="200">
            <template #default="{ row }">
              <div class="fournisseur-cell">
                <div class="fournisseur-nom">{{ row.fournisseur.nom }}</div>
                <div class="fournisseur-code">{{ row.fournisseur.code }}</div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="reference" label="Référence" width="140" />

          <el-table-column prop="montant_ht" label="Montant HT" width="130" align="right" sortable="custom">
            <template #default="{ row }">
              {{ formatMontant(row.montant_ht) }}
            </template>
          </el-table-column>

          <el-table-column prop="montant_tva" label="TVA (18%)" width="120" align="right">
            <template #default="{ row }">
              <el-tag type="info" size="small">{{ formatMontant(row.montant_tva) }}</el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="montant_aib" label="AIB" width="100" align="right">
            <template #default="{ row }">
              <el-tag v-if="row.montant_aib > 0" type="warning" size="small">
                {{ formatMontant(row.montant_aib) }}
              </el-tag>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="montant_ttc" label="Total TTC" width="140" align="right" sortable="custom">
            <template #default="{ row }">
              <strong class="montant-ttc">{{ formatMontant(row.montant_ttc) }}</strong>
            </template>
          </el-table-column>

          <el-table-column prop="montant_paye" label="Payé" width="130" align="right">
            <template #default="{ row }">
              <el-tag :type="getPaymentTagType(row)" size="small">
                {{ formatMontant(row.montant_paye) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="statut_paiement" label="Statut" width="130" align="center">
            <template #default="{ row }">
              <el-tag :type="getStatutType(row.statut_paiement)" size="small">
                {{ getStatutLabel(row.statut_paiement) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="220" fixed="right" align="center">
            <template #default="{ row }">
              <el-button-group>
                <el-button :icon="View" size="small" @click="handleView(row)">
                  Voir
                </el-button>
                <el-button
                  v-if="row.statut_paiement !== 'payee'"
                  :icon="Money"
                  size="small"
                  type="success"
                  @click="handlePay(row)"
                >
                  Régler
                </el-button>
                <el-dropdown @command="(cmd) => handleMoreActions(cmd, row)">
                  <el-button :icon="More" size="small" />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="edit" :icon="Edit">
                        Modifier
                      </el-dropdown-item>
                      <el-dropdown-item command="duplicate" :icon="CopyDocument">
                        Dupliquer
                      </el-dropdown-item>
                      <el-dropdown-item command="print" :icon="Printer">
                        Imprimer
                      </el-dropdown-item>
                      <el-dropdown-item divided command="delete" :icon="Delete">
                        <span style="color: #f56c6c">Supprimer</span>
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </el-button-group>
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
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus,
  Search,
  RefreshLeft,
  Download,
  Printer,
  Refresh,
  View,
  Edit,
  Delete,
  Money,
  More,
  Document,
  WarningFilled,
  Clock,
  SuccessFilled,
  CopyDocument
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  factures: {
    type: Array,
    default: () => []
  },
  fournisseurs: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      montant_impaye: 0,
      montant_partiel: 0,
      montant_paye: 0
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
const filters = reactive({
  search: '',
  fournisseur_id: null,
  statut_paiement: '',
  date_range: null
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Fournisseurs', path: '/factures-fournisseurs' }
];

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getStatutType = (statut) => {
  const types = {
    impayee: 'danger',
    partielle: 'warning',
    payee: 'success'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    impayee: 'Impayée',
    partielle: 'Partielle',
    payee: 'Payée'
  };
  return labels[statut] || statut;
};

const getPaymentTagType = (row) => {
  if (row.montant_paye === 0) return 'info';
  if (row.montant_paye >= row.montant_ttc) return 'success';
  return 'warning';
};

const handleSearch = () => {
  console.log('Searching with filters:', filters);
};

const handleReset = () => {
  filters.search = '';
  filters.fournisseur_id = null;
  filters.statut_paiement = '';
  filters.date_range = null;
  handleSearch();
};

const handleRefresh = () => {
  router.reload({ only: ['factures', 'stats', 'pagination'] });
};

const handleSortChange = ({ prop, order }) => {
  console.log('Sort changed:', prop, order);
};

const handleSizeChange = (size) => {
  console.log('Page size changed:', size);
};

const handlePageChange = (page) => {
  console.log('Page changed:', page);
};

const handleCreate = () => {
  router.visit('/factures-fournisseurs/create');
};

const handleView = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}`);
};

const handlePay = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}/regler`);
};

const handleMoreActions = async (command, facture) => {
  switch (command) {
    case 'edit':
      router.visit(`/factures-fournisseurs/${facture.id}/edit`);
      break;
    case 'duplicate':
      ElMessage.info('Duplication en cours de développement...');
      break;
    case 'print':
      ElMessage.info('Impression en cours de développement...');
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer cette facture ?',
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(() => {
        ElMessage.success('Facture supprimée avec succès');
        handleRefresh();
      });
      break;
  }
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
  gap: 16px;
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 12px;
}

.stat-total .stat-icon {
  background-color: #e3f2fd;
  color: #1976d2;
}

.stat-unpaid .stat-icon {
  background-color: #ffebee;
  color: #d32f2f;
}

.stat-partial .stat-icon {
  background-color: #fff3e0;
  color: #f57c00;
}

.stat-paid .stat-icon {
  background-color: #e8f5e9;
  color: #388e3c;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
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

.fournisseur-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.fournisseur-nom {
  font-size: 14px;
  color: #1f2937;
  font-weight: 500;
}

.fournisseur-code {
  font-size: 12px;
  color: #9ca3af;
}

.montant-ttc {
  color: #059669;
  font-size: 14px;
}

.text-muted {
  color: #9ca3af;
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

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.stat-card .el-card__body) {
  padding: 20px;
}
</style>
