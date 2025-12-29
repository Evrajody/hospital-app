<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion des Fournisseurs</h1>
          <p class="page-subtitle">Liste et gestion de tous les fournisseurs</p>
        </div>
        <el-button type="primary" size="large" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          Nouveau Fournisseur
        </el-button>
      </div>

      <!-- Filters Card -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" :model="filters" class="filter-form">
          <el-form-item label="Recherche">
            <el-input
              v-model="filters.search"
              placeholder="Code, nom, compte, email, téléphone..."
              :prefix-icon="Search"
              clearable
              style="width: 350px"
              @input="handleSearch"
            />
          </el-form-item>

          <el-form-item label="Statut">
            <el-select
              v-model="filters.status"
              placeholder="Tous"
              clearable
              style="width: 150px"
              @change="handleSearch"
            >
              <el-option label="Actif" value="actif" />
              <el-option label="Inactif" value="inactif" />
            </el-select>
          </el-form-item>

          <el-form-item label="Compte Comptable">
            <el-select
              v-model="filters.compte_id"
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
              />
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
          v-loading="loading"
          :data="fournisseurs"
          stripe
          style="width: 100%"
          @sort-change="handleSortChange"
        >
          <el-table-column prop="code" label="Code" width="120" sortable="custom">
            <template #default="{ row }">
              <el-tag type="info" size="small">{{ row.code }}</el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="nom" label="Nom du Fournisseur" min-width="200" sortable="custom" />

          <el-table-column prop="compte_comptable" label="Compte Comptable" width="250">
            <template #default="{ row }">
              <div v-if="row.compte_comptable" class="compte-cell">
                <el-tag size="small">{{ row.compte_comptable.numero }}</el-tag>
                <span class="compte-libelle">{{ row.compte_comptable.libelle }}</span>
              </div>
              <el-text v-else type="info" size="small">Non assigné</el-text>
            </template>
          </el-table-column>

          <el-table-column prop="contact" label="Contact" width="150" />

          <el-table-column prop="telephone" label="Téléphone" width="140">
            <template #default="{ row }">
              <el-link v-if="row.telephone" :href="`tel:${row.telephone}`" :icon="Phone">
                {{ row.telephone }}
              </el-link>
            </template>
          </el-table-column>

          <el-table-column prop="email" label="Email" width="200">
            <template #default="{ row }">
              <el-link v-if="row.email" :href="`mailto:${row.email}`" :icon="Message">
                {{ row.email }}
              </el-link>
            </template>
          </el-table-column>

          <el-table-column prop="status" label="Statut" width="100" align="center">
            <template #default="{ row }">
              <el-tag :type="row.status === 'actif' ? 'success' : 'info'" size="small">
                {{ row.status === 'actif' ? 'Actif' : 'Inactif' }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="180" fixed="right" align="center">
            <template #default="{ row }">
              <el-button-group>
                <el-button :icon="View" size="small" @click="handleView(row)">
                  Voir
                </el-button>
                <el-button :icon="Edit" size="small" @click="handleEdit(row)">
                  Modifier
                </el-button>
                <el-popconfirm
                  title="Êtes-vous sûr de vouloir supprimer ce fournisseur ?"
                  confirm-button-text="Oui"
                  cancel-button-text="Non"
                  @confirm="handleDelete(row.id)"
                >
                  <template #reference>
                    <el-button :icon="Delete" size="small" type="danger" />
                  </template>
                </el-popconfirm>
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
import { ref, reactive, computed } from 'vue';
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
  Message
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
  status: '',
  compte_id: ''
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Fournisseurs', path: '/fournisseurs' }
];

// Methods
const handleSearch = () => {
  // TODO: Implement server-side search when backend is ready
  console.log('Searching with filters:', filters);
};

const handleReset = () => {
  filters.search = '';
  filters.status = '';
  filters.compte_id = '';
  handleSearch();
};

const handleRefresh = () => {
  router.reload({ only: ['fournisseurs', 'pagination'] });
};

const handleSortChange = ({ prop, order }) => {
  // TODO: Implement server-side sorting
  console.log('Sort changed:', prop, order);
};

const handleSizeChange = (size) => {
  // TODO: Implement pagination when backend is ready
  console.log('Page size changed:', size);
};

const handlePageChange = (page) => {
  // TODO: Implement pagination when backend is ready
  console.log('Page changed:', page);
};

const handleCreate = () => {
  router.visit('/fournisseurs/create');
};

const handleView = (fournisseur) => {
  router.visit(`/fournisseurs/${fournisseur.id}`);
};

const handleEdit = (fournisseur) => {
  router.visit(`/fournisseurs/${fournisseur.id}/edit`);
};

const handleDelete = async (id) => {
  // TODO: Implement delete when backend is ready
  ElMessage.success('Fournisseur supprimé avec succès');
  handleRefresh();
};

const handleExport = () => {
  // TODO: Implement export functionality
  ElMessage.info('Export en cours de développement...');
};
</script>

<script>
export default {
  layout: null // We're using AppLayout explicitly
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
</style>
