<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion des Fournisseurs</h1>
          <p class="page-subtitle">Liste et gestion de tous les fournisseurs</p>
        </div>
        <el-button v-if="can('fournisseurs.creer')" type="primary" size="large" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          Nouveau Fournisseur
        </el-button>
      </div>

      <!-- Statistiques rapides -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: #dbeafe; color: #2563eb;">
                <el-icon :size="24"><User /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.total || fournisseurs.length }}</div>
                <div class="stat-label">Total Fournisseurs</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <!-- <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: #d1fae5; color: #059669;">
                <el-icon :size="24"><CircleCheck /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.actifs || 0 }}</div>
                <div class="stat-label">Fournisseurs Actifs</div>
              </div>
            </div>
          </el-card>
        </el-col> -->
      </el-row>

      <!-- Filters Card -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" :model="localFilters" class="filter-form">
          <el-form-item label="Recherche">
            <el-input
              v-model="localFilters.search"
              placeholder="Code, nom, compte, email, téléphone..."
              :prefix-icon="Search"
              clearable
              style="width: 350px"
              @input="debouncedSearch"
              @clear="handleSearch"
            />
          </el-form-item>

          <el-form-item label="Compte Comptable">
            <el-select
              v-model="localFilters.compte_id"
              placeholder="Tous les comptes"
              clearable
              filterable
              style="width: 250px"
              @change="handleSearch"
            >
              <el-option
                v-for="compte in comptesFournisseurs"
                :key="compte.id"
                :label="`${compte.numero} - ${compte.libelle}`"
                :value="compte.id"
              >
                <div class="compte-option">
                  <el-tag size="small" type="info">{{ compte.numero }}</el-tag>
                  <span class="compte-libelle">{{ compte.libelle }}</span>
                </div>
              </el-option>
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
              {{ pagination.total }} fournisseur(s) trouvé(s)
            </span>
            <div class="card-actions">
              <el-button :icon="Download" size="small" @click="handleExport">
                Exporter
              </el-button>
              <el-button :icon="Refresh" size="small" circle @click="handleRefresh" />
            </div>
          </div>
        </template>

        <el-table
          ref="mainTableRef"
          v-loading="loading"
          :data="fournisseurs"
          stripe
          border
          style="width: 100%"
          :height="tableHeight"
          @sort-change="handleSortChange"
        >
          <el-table-column label="Actions" width="100" fixed="left" align="center" resizable>
            <template #default="{ row }">
              <el-dropdown trigger="click" @command="(cmd) => handleAction(cmd, row)">
                <el-button size="small" type="primary">
                  Actions <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="view" :icon="View">
                      Voir
                    </el-dropdown-item>
                    <el-dropdown-item v-if="can('fournisseurs.modifier')" command="edit" :icon="Edit">
                      Modifier
                    </el-dropdown-item>
                    <el-dropdown-item v-if="can('fournisseurs.supprimer')" divided command="delete" :icon="Delete">
                      <span style="color: #f56c6c">Supprimer</span>
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>

          <el-table-column prop="nom" label="Raison Sociale" min-width="200" sortable="custom" show-overflow-tooltip resizable />

          <el-table-column prop="type_fournisseur" label="Type" min-width="140" sortable="custom" resizable>
            <template #default="{ row }">
              <el-tag v-if="row.type_fournisseur" size="small" type="info">
                {{ getTypeFournisseurLabel(row.type_fournisseur) }}
              </el-tag>
              <el-text v-else type="info" size="small">-</el-text>
            </template>
          </el-table-column>
          <el-table-column prop="ifu" label="IFU" min-width="150" show-overflow-tooltip sortable="custom" resizable />

          <el-table-column prop="compte_comptable" label="Compte Comptable" min-width="200" show-overflow-tooltip sortable="custom" resizable>
            <template #default="{ row }">
              <div v-if="row.compte_comptable" class="compte-cell">
                <el-tag size="small">{{ row.compte_comptable.numero }}</el-tag>
                <span class="compte-libelle">{{ row.compte_comptable.libelle }}</span>
              </div>
              <el-text v-else type="info" size="small">Non assigné</el-text>
            </template>
          </el-table-column>

          <el-table-column prop="contact" label="Contact" min-width="140" show-overflow-tooltip sortable="custom" resizable />

          <el-table-column prop="telephone" label="Téléphone" min-width="130" sortable="custom" resizable>
            <template #default="{ row }">
              <el-link v-if="row.telephone" :href="`tel:${row.telephone}`" :icon="Phone">
                {{ row.telephone }}
              </el-link>
              <span v-else>-</span>
            </template>
          </el-table-column>

          <el-table-column prop="email" label="Email" min-width="180" show-overflow-tooltip sortable="custom" resizable>
            <template #default="{ row }">
              <el-link v-if="row.email" :href="`mailto:${row.email}`" :icon="Message">
                {{ row.email }}
              </el-link>
              <span v-else>-</span>
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

    <!-- Modal Fournisseur -->
    <FournisseurModal
      v-model="showFournisseurModal"
      :fournisseur="selectedFournisseur"
      :comptes-fournisseurs="comptesFournisseurs"
      :comptes-parents="comptesParents"
      :server-errors="serverErrors"
      :loading="modalLoading"
      @success="handleFournisseurSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus,
  Search,
  RefreshLeft,
  Download,
  Refresh,
  View,
  Edit,
  Delete,
  Phone,
  Message,
  User,
  CircleCheck,
  ArrowDown
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FournisseurModal from '@/Components/Modals/FournisseurModal.vue';
import { fetchApi } from '@/Composables/useFetch';
import { usePermissions } from '@/Composables/usePermissions';
import { useTableHeight } from '@/Composables/useTableHeight';

const { can } = usePermissions();

// Simple debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

// Props
const props = defineProps({
  fournisseurs: {
    type: Array,
    default: () => []
  },
  comptesFournisseurs: {
    type: Array,
    default: () => []
  },
  comptesParents: {
    type: Array,
    default: () => []
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
    default: () => ({})
  },
  user: {
    type: Object,
    default: () => null
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      actifs: 0,
      inactifs: 0
    })
  }
});

// State
const loading = ref(false);
const showFournisseurModal = ref(false);
const selectedFournisseur = ref(null);
const serverErrors = ref(null);
const modalLoading = ref(false);

const mainTableRef = ref(null);
const { tableHeight } = useTableHeight(mainTableRef, 84);

const localFilters = reactive({
  search: props.filters?.search || '',
  compte_id: props.filters?.compte_id || ''
});

const localPagination = reactive({
  current_page: props.pagination.current_page,
  per_page: props.pagination.per_page
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Fournisseurs', path: '/fournisseurs' }
];

// Types de fournisseurs
const typesFournisseurs = {
  medicaments: 'Médicaments',
  equipements: 'Équipements',
  consommables: 'Consommables',
  services: 'Services',
  maintenance: 'Maintenance',
  autres: 'Autres'
};

// Methods
const getTypeFournisseurLabel = (type) => {
  return typesFournisseurs[type] || type;
};

const handleSearch = () => {
  loading.value = true;
  router.get('/fournisseurs', {
    search: localFilters.search || undefined,
    compte_id: localFilters.compte_id || undefined,
    per_page: localPagination.per_page,
    page: 1
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const debouncedSearch = debounce(handleSearch, 300);

const handleReset = () => {
  localFilters.search = '';
  localFilters.compte_id = '';
  handleSearch();
};

const handleRefresh = () => {
  loading.value = true;
  router.reload({
    only: ['fournisseurs', 'pagination', 'stats'],
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleSortChange = ({ prop, order }) => {
  loading.value = true;
  router.get('/fournisseurs', {
    ...localFilters,
    per_page: localPagination.per_page,
    page: localPagination.current_page,
    sort: prop,
    order: order === 'ascending' ? 'asc' : 'desc'
  }, {
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
  router.get('/fournisseurs', {
    ...localFilters,
    per_page: localPagination.per_page,
    page: page
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleCreate = () => {
  serverErrors.value = null;
  selectedFournisseur.value = null;
  showFournisseurModal.value = true;
};

const handleAction = (command, row) => {
  switch (command) {
    case 'view':
      handleView(row);
      break;
    case 'edit':
      handleEdit(row);
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer ce fournisseur ?',
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(() => {
        handleDelete(row.id);
      }).catch(() => {});
      break;
  }
};

const handleView = (fournisseur) => {
  router.visit(`/fournisseurs/${fournisseur.id}`);
};

const handleEdit = (fournisseur) => {
  serverErrors.value = null;
  selectedFournisseur.value = fournisseur;
  showFournisseurModal.value = true;
};

const handleFournisseurSuccess = async (fournisseurData) => {
  serverErrors.value = null;
  modalLoading.value = true;

  const isEdit = !!selectedFournisseur.value;
  const url = isEdit
    ? `/api/fournisseurs/${selectedFournisseur.value.id}`
    : '/api/fournisseurs';

  try {
    const response = await fetchApi(url, {
      method: isEdit ? 'PUT' : 'POST',
      body: fournisseurData
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message);
      showFournisseurModal.value = false;
      serverErrors.value = null;
      handleRefresh();
    } else {
      // Afficher les erreurs de validation dans le modal
      if (result.errors) {
        serverErrors.value = result.errors;
        // Construire un message d'erreur détaillé
        const errorMessages = [];
        for (const [field, messages] of Object.entries(result.errors)) {
          const fieldMessages = Array.isArray(messages) ? messages : [messages];
          errorMessages.push(...fieldMessages);
        }
        ElMessage.error({
          message: result.message || `${errorMessages.length} erreur(s) de validation`,
          duration: 5000
        });
      } else {
        ElMessage.error(result.message || 'Une erreur est survenue');
      }
    }
  } catch (error) {
    console.error('Erreur:', error);
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
  }
};

const handleDelete = async (id) => {
  loading.value = true;

  try {
    const response = await fetchApi(`/api/fournisseurs/${id}`, {
      method: 'DELETE'
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message);
      handleRefresh();
    } else {
      ElMessage.error(result.message || 'Erreur lors de la suppression');
    }
  } catch (error) {
    console.error('Erreur:', error);
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    loading.value = false;
  }
};

const handleExport = () => {
  ElMessage.info('Export en cours de développement...');
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
  margin-bottom: 4px;
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

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

.filter-card {
  border-radius: 8px;
}

.filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

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

.compte-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-option :deep(.el-tag) {
  min-width: 85px;
  text-align: center;
  font-family: 'Courier New', monospace;
}

.compte-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-libelle {
  font-size: 13px;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
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
</style>
