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
              placeholder="N° PC, référence..."
              :prefix-icon="Search"
              clearable
              style="width: 250px"
              @input="debouncedSearch"
              @clear="handleSearch"
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
          <el-table-column label="Actions" width="100" fixed="left" align="center">
            <template #default="{ row }">
              <el-dropdown trigger="click" @command="(cmd) => handleMoreActions(cmd, row)">
                <el-button size="small" type="primary">
                  Actions <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="view" :icon="View">
                      Voir
                    </el-dropdown-item>
                    <el-dropdown-item
                      v-if="row.statut_paiement !== 'payee'"
                      command="pay"
                      :icon="Money"
                    >
                      Régler
                    </el-dropdown-item>
                    <el-dropdown-item command="edit" :icon="Edit">
                      Modifier
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
            </template>
          </el-table-column>

          <el-table-column prop="numero" label="N° PC" width="140" sortable="custom" fixed="left">
            <template #default="{ row }">
              <el-link type="primary" @click="handleView(row)">
                <strong>{{ row.numero }}</strong>
              </el-link>
            </template>
          </el-table-column>

          <el-table-column prop="date_facture" label="Date" width="110" sortable="custom" fixed="left">
            <template #default="{ row }">
              {{ formatDate(row.date_facture) }}
            </template>
          </el-table-column>

          <el-table-column prop="fournisseur" label="Fournisseur" min-width="200" fixed="left">
            <template #default="{ row }">
              <div class="fournisseur-cell">
                <div class="fournisseur-nom">{{ row.fournisseur.nom }}</div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="date_facture_bc" label="Date Fact/B.C." width="120">
            <template #default="{ row }">
              {{ row.date_facture_bc ? formatDate(row.date_facture_bc) : '-' }}
            </template>
          </el-table-column>

          <el-table-column prop="reference" label="Réf. Fact/B.C." width="140" />

          <el-table-column prop="libelle" label="Libellé" min-width="180">
            <template #default="{ row }">
              {{ row.libelle || '-' }}
            </template>
          </el-table-column>

          <el-table-column prop="montant_ttc" label="Montant TTC" width="140" align="right" sortable="custom">
            <template #default="{ row }">
              <strong class="montant-ttc">{{ formatMontant(row.montant_ttc) }}</strong>
            </template>
          </el-table-column>

          <el-table-column prop="montant_net" label="Net à payer" width="140" align="right">
            <template #default="{ row }">
              <strong>{{ formatMontant(row.montant_net) }}</strong>
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

        </el-table>

        <!-- Pagination -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="localPagination.current_page"
            v-model:page-size="localPagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>
    </div>

    <!-- Modal Facture Fournisseur -->
    <FactureFournisseurModal
      v-model="showFactureModal"
      :facture="selectedFacture"
      :fournisseurs="fournisseurs"
      :imputations="imputations"
      :comptes="comptes"
      :comptes-aib="comptesAib"
      :taux-aib-list="tauxAibList"
      :taux-tva-defaut="tauxTvaDefaut"
      @success="handleFactureSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
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
  CopyDocument,
  ArrowDown
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FactureFournisseurModal from '@/Components/Modals/FactureFournisseurModal.vue';

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
  imputations: {
    type: Array,
    default: () => []
  },
  comptes: {
    type: Array,
    default: () => []
  },
  comptesAib: {
    type: Array,
    default: () => []
  },
  tauxAibList: {
    type: Array,
    default: () => []
  },
  tauxTvaDefaut: {
    type: Number,
    default: 18
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

// Simple debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

// State
const loading = ref(false);
const showFactureModal = ref(false);
const selectedFacture = ref(null);

const filters = reactive({
  search: props.pagination?.search || '',
  fournisseur_id: props.pagination?.fournisseur_id || null,
  statut_paiement: props.pagination?.statut_paiement || '',
  date_range: null
});

const localPagination = reactive({
  current_page: props.pagination.current_page,
  per_page: props.pagination.per_page
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

const buildParams = (overrides = {}) => {
  const params = {
    search: filters.search || undefined,
    fournisseur_id: filters.fournisseur_id || undefined,
    statut: filters.statut_paiement || undefined,
    per_page: localPagination.per_page,
    page: localPagination.current_page,
    ...overrides
  };

  if (filters.date_range && filters.date_range.length === 2) {
    params.date_debut = filters.date_range[0]?.toISOString?.()?.split('T')[0] || filters.date_range[0];
    params.date_fin = filters.date_range[1]?.toISOString?.()?.split('T')[0] || filters.date_range[1];
  }

  return params;
};

const handleSearch = () => {
  loading.value = true;
  router.get('/factures-fournisseurs', buildParams({ page: 1 }), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const debouncedSearch = debounce(handleSearch, 300);

const handleReset = () => {
  filters.search = '';
  filters.fournisseur_id = null;
  filters.statut_paiement = '';
  filters.date_range = null;
  handleSearch();
};

const handleRefresh = () => {
  loading.value = true;
  router.reload({
    only: ['factures', 'stats', 'pagination'],
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleSortChange = ({ prop, order }) => {
  loading.value = true;
  router.get('/factures-fournisseurs', buildParams({
    sort: prop,
    order: order === 'ascending' ? 'asc' : 'desc'
  }), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleSizeChange = (size) => {
  localPagination.per_page = size;
  localPagination.current_page = 1;
  handleSearch();
};

const handlePageChange = (page) => {
  loading.value = true;
  router.get('/factures-fournisseurs', buildParams({ page }), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleCreate = () => {
  selectedFacture.value = null;
  showFactureModal.value = true;
};

const handleView = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}`);
};

const handlePay = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}/regler`);
};

const handleFactureSuccess = async (factureData) => {
  const isEdit = !!selectedFacture.value;
  const url = isEdit
    ? `/api/factures-fournisseurs/${selectedFacture.value.id}`
    : '/api/factures-fournisseurs';

  try {
    const response = await fetch(url, {
      method: isEdit ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(factureData)
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Facture modifiée avec succès' : 'Facture créée avec succès'));
      showFactureModal.value = false;
      selectedFacture.value = null;
      handleRefresh();
    } else {
      ElMessage.error(result.message || 'Une erreur est survenue');
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    ElMessage.error('Erreur de connexion au serveur');
  }
};

const handleMoreActions = async (command, facture) => {
  switch (command) {
    case 'view':
      handleView(facture);
      break;
    case 'pay':
      handlePay(facture);
      break;
    case 'edit':
      selectedFacture.value = facture;
      showFactureModal.value = true;
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
      ).then(async () => {
        try {
          const response = await fetch(`/api/factures-fournisseurs/${facture.id}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
          });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Facture supprimée avec succès');
            handleRefresh();
          } else {
            ElMessage.error(result.message || 'Erreur lors de la suppression');
          }
        } catch (error) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
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
  padding: 0 20px 16px;
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

.table-card :deep(.el-card__body) {
  padding: 0;
}

:deep(.stat-card .el-card__body) {
  padding: 20px;
}
</style>
