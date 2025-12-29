<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Plan Comptable</h1>
          <p class="page-subtitle">Gestion des comptes comptables OHADA</p>
        </div>
        <el-button type="primary" size="large" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          Nouveau Compte
        </el-button>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="4">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><Notebook /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.total }}</div>
                <div class="stat-label">Total Comptes</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card shadow="hover" class="stat-card stat-charges">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><Money /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.charges }}</div>
                <div class="stat-label">Charges</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card shadow="hover" class="stat-card stat-immo">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><Box /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.immobilisations }}</div>
                <div class="stat-label">Immobilisations</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card shadow="hover" class="stat-card stat-tiers">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><UserFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.tiers }}</div>
                <div class="stat-label">Tiers</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card shadow="hover" class="stat-card stat-banques">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><CreditCard /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.banques }}</div>
                <div class="stat-label">Banques</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="4">
          <el-card shadow="hover" class="stat-card stat-autres">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="28"><Grid /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.autres }}</div>
                <div class="stat-label">Autres</div>
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
              @input="handleSearch"
            />
          </el-form-item>

          <el-form-item label="Type de compte">
            <el-select
              v-model="filters.type"
              placeholder="Tous"
              clearable
              style="width: 200px"
              @change="handleSearch"
            >
              <el-option label="Charges" value="charge" />
              <el-option label="Immobilisations" value="immobilisation" />
              <el-option label="Tiers" value="tiers" />
              <el-option label="Banques" value="banque" />
              <el-option label="Escompte" value="escompte" />
              <el-option label="AIB" value="aib" />
              <el-option label="TVA" value="tva" />
              <el-option label="Autres" value="autre" />
            </el-select>
          </el-form-item>

          <el-form-item label="Classe">
            <el-select
              v-model="filters.classe"
              placeholder="Toutes"
              clearable
              style="width: 150px"
              @change="handleSearch"
            >
              <el-option label="Classe 1 - Capitaux" value="1" />
              <el-option label="Classe 2 - Immobilisations" value="2" />
              <el-option label="Classe 3 - Stocks" value="3" />
              <el-option label="Classe 4 - Tiers" value="4" />
              <el-option label="Classe 5 - Trésorerie" value="5" />
              <el-option label="Classe 6 - Charges" value="6" />
              <el-option label="Classe 7 - Produits" value="7" />
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
          :data="comptes"
          stripe
          style="width: 100%"
          @sort-change="handleSortChange"
        >
          <el-table-column prop="numero" label="N° Compte" width="140" sortable="custom">
            <template #default="{ row }">
              <span class="compte-numero">{{ row.numero }}</span>
            </template>
          </el-table-column>

          <el-table-column prop="libelle" label="Libellé" min-width="300">
            <template #default="{ row }">
              <div class="compte-libelle">{{ row.libelle }}</div>
            </template>
          </el-table-column>

          <el-table-column prop="type" label="Type" width="180">
            <template #default="{ row }">
              <el-tag :type="getTypeTagType(row.type)" size="small">
                {{ getTypeLabel(row.type) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="parent" label="Compte parent" width="140">
            <template #default="{ row }">
              <span v-if="row.parent" class="compte-parent">{{ row.parent.numero }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="utilisations" label="Utilisations" width="120" align="center">
            <template #default="{ row }">
              <el-tag v-if="row.utilisations > 0" type="info" size="small">
                {{ row.utilisations }}
              </el-tag>
              <span v-else class="text-muted">0</span>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="180" fixed="right" align="center">
            <template #default="{ row }">
              <el-button-group>
                <el-button :icon="View" size="small" @click="handleView(row)">
                  Détails
                </el-button>
                <el-dropdown @command="(cmd) => handleMoreActions(cmd, row)">
                  <el-button :icon="More" size="small" />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="edit" :icon="Edit">
                        Modifier
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
  More,
  Edit,
  Delete,
  Notebook,
  Money,
  Box,
  UserFilled,
  CreditCard,
  Grid
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  comptes: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      charges: 0,
      immobilisations: 0,
      tiers: 0,
      banques: 0,
      autres: 0
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
  type: '',
  classe: ''
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Plan Comptable', path: '/plan-comptable' }
];

// Methods
const getTypeTagType = (type) => {
  const types = {
    charge: 'danger',
    immobilisation: 'warning',
    tiers: 'primary',
    banque: 'success',
    escompte: 'info',
    aib: '',
    tva: 'info',
    autre: ''
  };
  return types[type] || '';
};

const getTypeLabel = (type) => {
  const labels = {
    charge: 'Charge',
    immobilisation: 'Immobilisation',
    tiers: 'Tiers',
    banque: 'Banque',
    escompte: 'Escompte',
    aib: 'AIB',
    tva: 'TVA',
    autre: 'Autre'
  };
  return labels[type] || type;
};

const handleSearch = () => {
  console.log('Searching with filters:', filters);
};

const handleReset = () => {
  filters.search = '';
  filters.type = '';
  filters.classe = '';
  handleSearch();
};

const handleRefresh = () => {
  router.reload({ only: ['comptes', 'stats', 'pagination'] });
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
  router.visit('/plan-comptable/create');
};

const handleView = (compte) => {
  router.visit(`/plan-comptable/${compte.id}`);
};

const handleMoreActions = async (command, compte) => {
  switch (command) {
    case 'edit':
      router.visit(`/plan-comptable/${compte.id}/edit`);
      break;
    case 'delete':
      if (compte.utilisations > 0) {
        ElMessageBox.alert(
          `Ce compte est utilisé ${compte.utilisations} fois dans l'application. Vous ne pouvez pas le supprimer.`,
          'Suppression impossible',
          {
            confirmButtonText: 'OK',
            type: 'warning'
          }
        );
      } else {
        ElMessageBox.confirm(
          'Êtes-vous sûr de vouloir supprimer ce compte ?',
          'Confirmation',
          {
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            type: 'warning'
          }
        ).then(() => {
          ElMessage.success('Compte supprimé avec succès');
          handleRefresh();
        });
      }
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

.stat-charges .stat-icon {
  background-color: #fee2e2;
  color: #dc2626;
}

.stat-immo .stat-icon {
  background-color: #fef3c7;
  color: #d97706;
}

.stat-tiers .stat-icon {
  background-color: #e0e7ff;
  color: #6366f1;
}

.stat-banques .stat-icon {
  background-color: #dcfce7;
  color: #16a34a;
}

.stat-autres .stat-icon {
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
}

.compte-parent {
  font-family: 'Courier New', monospace;
  font-size: 12px;
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
