<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Plan Comptable OHADA</h1>
          <p class="page-subtitle">{{ stats.total }} comptes dont {{ stats.custom || 0 }} personnalisé(s)</p>
        </div>
        <el-button type="primary" size="large" @click="selectedCompte = null; showCompteModal = true">
          <el-icon><Plus /></el-icon>
          Nouveau Compte
        </el-button>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="12" class="stats-row">
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><Notebook /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.total }}</div>
                <div class="stat-label">Total</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-capitaux">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><Coin /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.capitaux }}</div>
                <div class="stat-label">Capitaux</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-immo">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><Box /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.immobilisations }}</div>
                <div class="stat-label">Immob.</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-stocks">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><Goods /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.stocks }}</div>
                <div class="stat-label">Stocks</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-tiers">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><UserFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.tiers }}</div>
                <div class="stat-label">Tiers</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-tresorerie">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><CreditCard /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.tresorerie }}</div>
                <div class="stat-label">Trésorerie</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-charges">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><Money /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.charges }}</div>
                <div class="stat-label">Charges</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="3">
          <el-card shadow="hover" class="stat-card stat-produits">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="24"><TrendCharts /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.produits }}</div>
                <div class="stat-label">Produits</div>
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
              placeholder="N° compte, libellé..."
              :prefix-icon="Search"
              clearable
              style="width: 300px"
              @input="debouncedSearch"
            />
          </el-form-item>

          <el-form-item label="Classe">
            <el-select
              v-model="filters.classe"
              placeholder="Toutes"
              clearable
              style="width: 200px"
              @change="handleSearch"
            >
              <el-option label="1 - Capitaux" value="1" />
              <el-option label="2 - Immobilisations" value="2" />
              <el-option label="3 - Stocks" value="3" />
              <el-option label="4 - Tiers" value="4" />
              <el-option label="5 - Trésorerie" value="5" />
              <el-option label="6 - Charges" value="6" />
              <el-option label="7 - Produits" value="7" />
              <el-option label="8 - Autres" value="8" />
            </el-select>
          </el-form-item>

          <el-form-item label="Source">
            <el-select
              v-model="filters.source"
              placeholder="Tous"
              clearable
              style="width: 160px"
              @change="handleSearch"
            >
              <el-option label="OHADA Standard" value="standard" />
              <el-option label="Personnalisé" value="custom" />
            </el-select>
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
              {{ pagination.total }} compte(s) trouvé(s)
            </span>
            <div class="card-actions">
              <el-dropdown trigger="click" @command="handleExport">
                <el-button :icon="Download" size="small">
                  Exporter<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="pdf">Exporter en PDF</el-dropdown-item>
                    <el-dropdown-item command="excel">Exporter en Excel</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              <el-button :icon="Printer" size="small" @click="handlePrint">
                Imprimer
              </el-button>
              <el-button :icon="Refresh" size="small" circle @click="handleRefresh" />
            </div>
          </div>
        </template>

        <el-table
          v-loading="loading"
          :data="comptes"
          stripe
          border
          style="width: 100%"
          @sort-change="handleSortChange"
        >
          <el-table-column prop="numero" label="N° Compte" width="140" sortable="custom" resizable>
            <template #default="{ row }">
              <span class="compte-numero">{{ row.numero }}</span>
            </template>
          </el-table-column>

          <el-table-column prop="libelle" label="Libellé" min-width="300" sortable="custom" resizable>
            <template #default="{ row }">
              <div class="compte-libelle">
                {{ row.libelle }}
                <el-tag v-if="row.is_custom" type="warning" size="small" class="custom-badge">
                  Personnalisé
                </el-tag>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="classe" label="Classe" width="100" align="center" sortable="custom" resizable>
            <template #default="{ row }">
              <el-tag size="small" effect="plain">{{ row.classe }}</el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="120" align="center" resizable>
            <template #default="{ row }">
              <el-button
                :icon="Edit"
                size="small"
                circle
                plain
                type="primary"
                @click="handleEdit(row)"
              />
              <el-button
                type="danger"
                :icon="Delete"
                size="small"
                circle
                plain
                @click="handleDelete(row)"
              />
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

      <!-- Modal Compte -->
      <CompteComptableModal
        v-model="showCompteModal"
        :comptes-parents="comptesParents"
        :compte="selectedCompte"
        @success="handleCompteSuccess"
      />
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
  Delete,
  Edit,
  Notebook,
  Money,
  Box,
  UserFilled,
  CreditCard,
  Coin,
  Goods,
  TrendCharts,
  ArrowDown,
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CompteComptableModal from '@/Components/Modals/CompteComptableModal.vue';
import { fetchApi } from '@/Composables/useFetch';
import { useAsyncExport } from '@/Composables/useAsyncExport';
import { debounce } from '@/utils/debounce';
const { startExport } = useAsyncExport();

// Props
const props = defineProps({
  comptes: {
    type: Array,
    default: () => []
  },
  comptesParents: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      custom: 0,
      capitaux: 0,
      immobilisations: 0,
      stocks: 0,
      tiers: 0,
      tresorerie: 0,
      charges: 0,
      produits: 0
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
  filters: {
    type: Object,
    default: () => ({
      search: '',
      classe: '',
      source: ''
    })
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State
const loading = ref(false);
const showCompteModal = ref(false);
const selectedCompte = ref(null);
const filters = reactive({
  search: props.filters.search || '',
  classe: props.filters.classe || '',
  source: props.filters.source || ''
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Plan Comptable', path: '/plan-comptable' }
];

// Methods
const handleSearch = () => {
  const params = new URLSearchParams();
  if (filters.search) params.append('search', filters.search);
  if (filters.classe) params.append('classe', filters.classe);
  if (filters.source) params.append('source', filters.source);

  router.get('/plan-comptable', Object.fromEntries(params), {
    preserveState: true,
    preserveScroll: true,
  });
};
const debouncedSearch = debounce(handleSearch, 300);

const handleReset = () => {
  filters.search = '';
  filters.classe = '';
  filters.source = '';
  router.get('/plan-comptable', {}, {
    preserveState: false,
  });
};


const handleRefresh = () => {
  router.reload({ only: ['comptes', 'stats', 'pagination'] });
};

const handleSortChange = ({ prop, order }) => {
  const params = new URLSearchParams(window.location.search);
  if (prop) {
    params.set('sort_by', prop === 'numero' ? 'numero_compte' : prop);
    params.set('sort_order', order === 'ascending' ? 'asc' : 'desc');
  } else {
    params.delete('sort_by');
    params.delete('sort_order');
  }
  router.get('/plan-comptable', Object.fromEntries(params), {
    preserveState: true,
    preserveScroll: true,
  });
};

const handleSizeChange = (size) => {
  const params = new URLSearchParams(window.location.search);
  params.set('per_page', size);
  params.set('page', '1');
  router.get('/plan-comptable', Object.fromEntries(params), {
    preserveState: true,
    preserveScroll: true,
  });
};

const handlePageChange = (page) => {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  router.get('/plan-comptable', Object.fromEntries(params), {
    preserveState: true,
    preserveScroll: true,
  });
};

const buildExportQuery = (extra = {}) => {
  const params = new URLSearchParams();
  if (filters.search) params.set('search', filters.search);
  if (filters.classe) params.set('classe', filters.classe);
  if (filters.source) params.set('source', filters.source);
  Object.entries(extra).forEach(([k, v]) => params.set(k, v));
  const qs = params.toString();
  return qs ? `?${qs}` : '';
};

const exportParams = () => {
  const p = {};
  if (filters.search) p.search = filters.search;
  if (filters.classe) p.classe = filters.classe;
  if (filters.source) p.source = filters.source;
  return p;
};

const handleExport = (command) => {
  // Génération asynchrone (file d'attente) puis notification + téléchargement.
  startExport('plan-comptable', command === 'excel' ? 'excel' : 'pdf', exportParams(), 'Plan comptable OHADA');
};

const handlePrint = () => {
  // Ouvre le PDF en ligne dans un nouvel onglet (l'utilisateur imprime depuis le visualiseur)
  window.open('/plan-comptable/export/pdf' + buildExportQuery(), '_blank');
};

const handleEdit = (compte) => {
  selectedCompte.value = compte;
  showCompteModal.value = true;
};

const handleCompteSuccess = () => {
  selectedCompte.value = null;
  router.reload({ only: ['comptes', 'comptesParents', 'stats'] });
};

const handleDelete = async (compte) => {
  try {
    await ElMessageBox.confirm(
      `Êtes-vous sûr de vouloir supprimer le compte "${compte.numero} - ${compte.libelle}" ?`,
      'Confirmation de suppression',
      {
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        type: 'warning'
      }
    );

    const response = await fetchApi(`/api/plan-comptable/${compte.id}`, {
      method: 'DELETE',
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success('Compte supprimé avec succès');
      router.reload({ only: ['comptes', 'stats'] });
    } else {
      ElMessage.error(result.message || 'Erreur lors de la suppression');
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Erreur lors de la suppression');
    }
  }
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

.stat-total .stat-icon {
  background-color: #dbeafe;
  color: #2563eb;
}

.stat-capitaux .stat-icon {
  background-color: #fef3c7;
  color: #d97706;
}

.stat-immo .stat-icon {
  background-color: #e0e7ff;
  color: #6366f1;
}

.stat-stocks .stat-icon {
  background-color: #fed7aa;
  color: #ea580c;
}

.stat-tiers .stat-icon {
  background-color: #d1fae5;
  color: #059669;
}

.stat-tresorerie .stat-icon {
  background-color: #dcfce7;
  color: #16a34a;
}

.stat-charges .stat-icon {
  background-color: #fee2e2;
  color: #dc2626;
}

.stat-produits .stat-icon {
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

.compte-numero {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2563eb;
  font-size: 13px;
}

.compte-libelle {
  font-size: 14px;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 8px;
}

.custom-badge {
  flex-shrink: 0;
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
  padding: 16px;
}
</style>
