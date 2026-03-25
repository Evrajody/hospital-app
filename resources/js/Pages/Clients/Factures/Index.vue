<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="factures-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Factures Clients</h1>
          <p class="subtitle">Gestion des factures et cr&eacute;ances</p>
        </div>
        <el-button type="primary" :icon="Plus" @click="handleCreate">
          Nouvelle Facture
        </el-button>
      </div>

      <!-- Statistiques -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <el-icon :size="24"><Document /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ pagination.total }}</div>
                <div class="stat-label">Total Factures</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                <el-icon :size="24"><Money /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.total_facture) }}</div>
                <div class="stat-label">Total Factur&eacute;</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card success">
              <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)">
                <el-icon :size="24"><SuccessFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.total_paye) }}</div>
                <div class="stat-label">Total Pay&eacute;</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card danger">
              <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)">
                <el-icon :size="24"><Warning /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.total_reste) }}</div>
                <div class="stat-label">Cr&eacute;ances</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Recherche et filtres -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true">
          <el-form-item>
            <el-input
              v-model="localFilters.search"
              placeholder="Rechercher (r&eacute;f&eacute;rence, client...)"
              :prefix-icon="Search"
              style="width: 350px"
              clearable
              @input="debouncedSearch"
              @clear="handleSearch"
            />
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="localFilters.statut"
              placeholder="Tous les statuts"
              clearable
              style="width: 200px"
              @change="handleSearch"
            >
              <el-option label="Non pay&eacute;e" value="non_payee" />
              <el-option label="Partiellement pay&eacute;e" value="partiellement_payee" />
              <el-option label="Pay&eacute;e" value="payee" />
            </el-select>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Table -->
      <el-card class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">{{ pagination.total }} facture(s)</span>
          </div>
        </template>

        <el-table
          v-loading="loading"
          :data="factures"
          stripe
          border
          style="width: 100%"
          @sort-change="handleSortChange"
          class="factures-table"
        >
          <el-table-column label="Actions" width="200" fixed="left" align="center" resizable>
            <template #default="{ row }">
              <el-button-group>
                <el-button :icon="View" size="small" type="primary" @click="handleView(row)">
                  D&eacute;tails
                </el-button>
                <el-dropdown @command="(cmd) => handleAction(cmd, row)">
                  <el-button :icon="More" size="small" />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item v-if="row.statut !== 'payee'" command="regler" :icon="Money">
                        R&eacute;gler
                      </el-dropdown-item>
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

          <el-table-column prop="reference" label="R&eacute;f&eacute;rence" width="160" sortable resizable>
            <template #default="{ row }">
              <span class="nowrap-cell"><a class="ref-link" @click="handleView(row)">{{ row.reference }}</a></span>
            </template>
          </el-table-column>
          <el-table-column prop="date_facture" label="Date" width="120" sortable resizable>
            <template #default="{ row }">
              {{ formatDate(row.date_facture) }}
            </template>
          </el-table-column>
          <el-table-column prop="client.nom" label="Client" sortable resizable>
            <template #default="{ row }">
              <strong>{{ row.client?.nom || '-' }}</strong>
            </template>
          </el-table-column>
          <el-table-column label="Montant" width="160" align="right" sortable sort-by="montant" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell">
                <div>{{ formatMontant(row.montant) }}</div>
                <div v-if="row.ristourne > 0" style="font-size: 11px; color: #e6a23c;">
                  Rist. -{{ formatMontant(row.ristourne) }}
                </div>
              </span>
            </template>
          </el-table-column>
          <el-table-column label="Pay&eacute;" width="160" align="right" sortable sort-by="montant_paye" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell montant-paye">{{ formatMontant(row.montant_paye) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Reste" width="160" align="right" sortable sort-by="reste_a_payer" resizable>
            <template #default="{ row }">
              <span :class="['nowrap-cell', 'montant-reste', row.reste_a_payer > 0 ? 'has-reste' : '']">
                {{ formatMontant(row.reste_a_payer) }}
              </span>
            </template>
          </el-table-column>
          <el-table-column prop="statut" label="Statut" width="160" align="center" sortable resizable>
            <template #default="{ row }">
              <span class="nowrap-cell">
                <el-tag :type="getStatutType(row.statut)" size="small">
                  {{ getStatutLabel(row.statut) }}
                </el-tag>
              </span>
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

    <!-- Modal Facture -->
    <FactureClientModal
      ref="factureModalRef"
      v-model="showFactureModal"
      :facture="selectedFacture"
      :clients="clients"
      :prochaine-reference="prochaineReference"
      :loading="modalLoading"
      @success="handleFactureSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import FactureClientModal from '@/Components/Modals/FactureClientModal.vue';
import {
  Plus, Search, Edit, Delete, View, More,
  Document, Money, SuccessFilled, Warning
} from '@element-plus/icons-vue';
import { fetchApi } from '@/Composables/useFetch';

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
  factures: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  prochaineReference: { type: String, default: '' },
  pagination: {
    type: Object,
    default: () => ({ current_page: 1, per_page: 20, total: 0 })
  },
  filters: { type: Object, default: () => ({}) },
  stats: {
    type: Object,
    default: () => ({ total_facture: 0, total_paye: 0, total_reste: 0 })
  },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/factures-clients' }
];

const loading = ref(false);
const showFactureModal = ref(false);
const selectedFacture = ref(null);
const modalLoading = ref(false);
const factureModalRef = ref(null);

const localFilters = reactive({
  search: props.filters?.search || '',
  statut: props.filters?.statut || ''
});

const localPagination = reactive({
  current_page: props.pagination.current_page,
  per_page: props.pagination.per_page
});

const handleSearch = () => {
  loading.value = true;
  router.get('/factures-clients', {
    search: localFilters.search || undefined,
    statut: localFilters.statut || undefined,
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
  router.get('/factures-clients', {
    search: localFilters.search || undefined,
    statut: localFilters.statut || undefined,
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
  router.get('/factures-clients', {
    search: localFilters.search || undefined,
    statut: localFilters.statut || undefined,
    per_page: localPagination.per_page,
    page
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; }
  });
};

const getStatutLabel = (statut) => {
  const labels = {
    non_payee: 'Non pay\u00e9e',
    partiellement_payee: 'Partielle',
    payee: 'Pay\u00e9e',
  };
  return labels[statut] || statut;
};

const getStatutType = (statut) => {
  const types = {
    non_payee: 'danger',
    partiellement_payee: 'warning',
    payee: 'success',
  };
  return types[statut] || '';
};

const handleView = (facture) => {
  router.visit(`/factures-clients/${facture.id}`);
};

const handleRegler = (facture) => {
  router.visit(`/factures-clients/${facture.id}/regler`);
};

const handleCreate = () => {
  selectedFacture.value = null;
  showFactureModal.value = true;
};

const handleEdit = (facture) => {
  selectedFacture.value = facture;
  showFactureModal.value = true;
};

const handleAction = (command, facture) => {
  switch (command) {
    case 'regler':
      handleRegler(facture);
      break;
    case 'edit':
      handleEdit(facture);
      break;
    case 'delete':
      handleDelete(facture);
      break;
  }
};

const handleDelete = async (facture) => {
  try {
    const response = await fetchApi(`/api/factures-clients/${facture.id}`, {
      method: 'DELETE',
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Facture supprim\u00e9e');
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const handleFactureSuccess = async (data) => {
  modalLoading.value = true;

  const isEdit = !!selectedFacture.value;
  const url = isEdit ? `/api/factures-clients/${selectedFacture.value.id}` : '/api/factures-clients';

  try {
    const response = await fetchApi(url, {
      method: isEdit ? 'PUT' : 'POST',
      body: data,
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Facture modifi\u00e9e' : 'Facture cr\u00e9\u00e9e'));
      showFactureModal.value = false;
      selectedFacture.value = null;
      router.reload();
    } else if (result.saut_numero) {
      // Saut de numéro détecté - afficher le warning
      factureModalRef.value?.showSautWarning(result.numeros_sautes);
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
  }
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<style scoped>
.factures-container { max-width: 1600px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 28px; font-weight: 600; color: #333; margin: 0 0 8px 0; }
.subtitle { color: #666; font-size: 14px; margin: 0; }
.filter-card { margin-bottom: 20px; border: 1px solid #e8e8e8; }
.stats-row { margin-bottom: 20px; }
.stat-card { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; }
.stat-content { flex: 1; }
.stat-value { font-size: 20px; font-weight: bold; color: #333; line-height: 1.2; }
.stat-label { font-size: 13px; color: #666; margin-top: 4px; }
.table-card { border: 1px solid #e8e8e8; }
.table-card :deep(.el-card__body) { padding: 0; }
.factures-table :deep(.el-table__row) { cursor: default; }
.montant-paye { color: #059669; font-weight: 600; }
.montant-reste { font-weight: 600; color: #666; }
.montant-reste.has-reste { color: #dc2626; }
.ref-link { color: #409eff; cursor: pointer; font-weight: 600; text-decoration: none; }
.ref-link:hover { text-decoration: underline; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 16px; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
.pagination-container { margin-top: 16px; padding: 0 20px 16px; display: flex; justify-content: flex-end; }
.nowrap-cell { white-space: nowrap; }
</style>
