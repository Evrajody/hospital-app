<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="clients-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Gestion des Clients</h1>
          <p class="subtitle">Liste complète des clients</p>
        </div>
        <el-button type="primary" :icon="Plus" @click="handleCreate">
          Nouveau Client
        </el-button>
      </div>

      <!-- Statistiques -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <el-icon :size="24"><User /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.total }}</div>
                <div class="stat-label">Total Clients</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Recherche -->
      <el-card class="filter-card" shadow="never">
        <el-input
          v-model="localFilters.search"
          placeholder="Rechercher un client (nom, téléphone, compte...)"
          :prefix-icon="Search"
          style="width: 400px"
          clearable
          @input="debouncedSearch"
          @clear="handleSearch"
        />
      </el-card>

      <!-- Table -->
      <el-card class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">{{ pagination.total }} client(s)</span>
          </div>
        </template>

        <el-table
          v-loading="loading"
          :data="clients"
          stripe
          style="width: 100%"
          @sort-change="handleSortChange"
          class="clients-table"
        >
          <el-table-column label="Actions" width="200" fixed="left">
            <template #default="{ row }">
              <el-button size="small" :icon="Edit" type="warning" @click="handleEdit(row)">Modifier</el-button>
              <el-popconfirm
                title="Supprimer ce client ?"
                confirm-button-text="Oui"
                cancel-button-text="Non"
                @confirm="handleDelete(row)"
              >
                <template #reference>
                  <el-button size="small" type="danger" :icon="Delete" />
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>

          <el-table-column prop="code" label="N° Compte" width="160" sortable>
            <template #default="{ row }">
              <el-tag v-if="row.code !== '-'" size="small" type="info">{{ row.code }}</el-tag>
              <span v-else style="color: #999">-</span>
            </template>
          </el-table-column>
          <el-table-column prop="nom" label="Raison Sociale" sortable>
            <template #default="{ row }">
              <strong>{{ row.nom }}</strong>
            </template>
          </el-table-column>
          <el-table-column prop="telephone" label="Téléphone" width="180" sortable />
          <el-table-column prop="adresse" label="Adresse" sortable />
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

    <!-- Modal -->
    <ClientModal
      v-model="showClientModal"
      :client="selectedClient"
      :comptes-clients="comptesClients"
      :comptes-parents="comptesParents"
      :loading="modalLoading"
      :server-errors="serverErrors"
      @success="handleClientSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientModal from '@/Components/Modals/ClientModal.vue';
import { Plus, Search, Edit, Delete, User } from '@element-plus/icons-vue';

// Simple debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  clients: { type: Array, default: () => [] },
  comptesClients: { type: Array, default: () => [] },
  comptesParents: { type: Array, default: () => [] },
  pagination: {
    type: Object,
    default: () => ({ current_page: 1, per_page: 20, total: 0 })
  },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({ total: 0 }) }
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Clients', path: '/clients' }
];

const loading = ref(false);
const showClientModal = ref(false);
const selectedClient = ref(null);
const modalLoading = ref(false);
const serverErrors = ref(null);

const localFilters = reactive({
  search: props.filters?.search || ''
});

const localPagination = reactive({
  current_page: props.pagination.current_page,
  per_page: props.pagination.per_page
});

const handleSearch = () => {
  loading.value = true;
  router.get('/clients', {
    search: localFilters.search || undefined,
    per_page: localPagination.per_page,
    page: 1
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; }
  });
};

const debouncedSearch = debounce(handleSearch, 300);

const handleSortChange = ({ prop, order }) => {
  loading.value = true;
  router.get('/clients', {
    search: localFilters.search || undefined,
    per_page: localPagination.per_page,
    page: localPagination.current_page,
    sort: prop,
    order: order === 'ascending' ? 'asc' : 'desc'
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; }
  });
};

const handleSizeChange = (size) => {
  localPagination.per_page = size;
  localPagination.current_page = 1;
  handleSearch();
};

const handlePageChange = (page) => {
  loading.value = true;
  router.get('/clients', {
    search: localFilters.search || undefined,
    per_page: localPagination.per_page,
    page
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; }
  });
};

const handleCreate = () => {
  selectedClient.value = null;
  serverErrors.value = null;
  showClientModal.value = true;
};

const handleEdit = (client) => {
  selectedClient.value = client;
  serverErrors.value = null;
  showClientModal.value = true;
};

const handleDelete = async (client) => {
  try {
    const response = await fetch(`/api/clients/${client.id}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Client supprimé');
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const handleClientSuccess = async (data) => {
  modalLoading.value = true;
  serverErrors.value = null;

  const isEdit = !!selectedClient.value;
  const url = isEdit ? `/api/clients/${selectedClient.value.id}` : '/api/clients';

  try {
    const response = await fetch(url, {
      method: isEdit ? 'PUT' : 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Client modifié' : 'Client créé'));
      showClientModal.value = false;
      selectedClient.value = null;
      router.visit(window.location.pathname, { preserveScroll: true });
    } else {
      if (result.errors) {
        serverErrors.value = result.errors;
      }
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
  }
};
</script>

<style scoped>
.clients-container { max-width: 1400px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 28px; font-weight: 600; color: #333; margin: 0 0 8px 0; }
.subtitle { color: #666; font-size: 14px; margin: 0; }
.filter-card { margin-bottom: 20px; border: 1px solid #e8e8e8; }
.stats-row { margin-bottom: 20px; }
.stat-card { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; }
.stat-content { flex: 1; }
.stat-value { font-size: 24px; font-weight: bold; color: #333; line-height: 1.2; }
.stat-label { font-size: 13px; color: #666; margin-top: 4px; }
.table-card { border: 1px solid #e8e8e8; }
.table-card :deep(.el-card__body) { padding: 0; }
.clients-table :deep(.el-table__row) { cursor: pointer; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 16px; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.pagination-container { margin-top: 16px; padding: 0 20px 16px; display: flex; justify-content: flex-end; }
</style>
