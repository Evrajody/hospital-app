<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <div class="page-header">
        <h1 class="page-title">
          Journal d'Activité
          <span class="entry-count">{{ pagination.total }} entrée(s)</span>
        </h1>
      </div>

      <!-- Filtres -->
      <el-card shadow="never" class="filter-card">
        <el-row :gutter="12" align="middle">
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-input
              v-model="localFilters.search"
              placeholder="Rechercher..."
              :prefix-icon="Search"
              clearable
              @input="onSearchInput"
              @clear="onFilterChange"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-select
              v-model="localFilters.user_id"
              placeholder="Tous les utilisateurs"
              clearable
              filterable
              style="width: 100%"
              @change="onFilterChange"
            >
              <el-option
                v-for="user in users"
                :key="user.id"
                :label="user.name"
                :value="user.id"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-select
              v-model="localFilters.module"
              placeholder="Tous les modules"
              clearable
              style="width: 100%"
              @change="onFilterChange"
            >
              <el-option
                v-for="mod in modules"
                :key="mod.value"
                :label="mod.label"
                :value="mod.value"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="4">
            <el-select
              v-model="localFilters.action"
              placeholder="Toutes les actions"
              clearable
              style="width: 100%"
              @change="onFilterChange"
            >
              <el-option
                v-for="act in actions"
                :key="act.value"
                :label="act.label"
                :value="act.value"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="3">
            <el-date-picker
              v-model="localFilters.date_debut"
              type="date"
              placeholder="Date début"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              style="width: 100%"
              clearable
              @change="onFilterChange"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="3">
            <el-date-picker
              v-model="localFilters.date_fin"
              type="date"
              placeholder="Date fin"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              style="width: 100%"
              clearable
              @change="onFilterChange"
            />
          </el-col>
          <el-col :xs="24" :sm="12" :md="6" :lg="2">
            <el-button :icon="RefreshRight" @click="resetFilters" style="width: 100%">
              Réinitialiser
            </el-button>
          </el-col>
        </el-row>
      </el-card>

      <!-- Table -->
      <el-card shadow="never" class="table-card">
        <el-table ref="mainTableRef" :data="logs" stripe style="width: 100%" empty-text="Aucune activité trouvée" :height="tableHeight">
          <el-table-column label="Date/Heure" width="160">
            <template #default="{ row }">
              <el-tooltip :content="row.created_at_human" placement="top">
                <span class="date-text">{{ formatDate(row.created_at) }}</span>
              </el-tooltip>
            </template>
          </el-table-column>

          <el-table-column label="Utilisateur" width="160">
            <template #default="{ row }">
              <span>{{ row.user_name }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Action" width="140">
            <template #default="{ row }">
              <el-tag
                :type="mapTagType(row.action_couleur)"
                size="small"
                effect="light"
              >
                {{ row.action_libelle }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Module" width="160">
            <template #default="{ row }">
              <el-tag type="info" size="small" effect="plain">
                {{ row.module_libelle }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Description" min-width="250">
            <template #default="{ row }">
              <span class="description-text">{{ row.description }}</span>
            </template>
          </el-table-column>

          <el-table-column label="IP" width="130">
            <template #default="{ row }">
              <span class="ip-text">{{ row.ip_address }}</span>
            </template>
          </el-table-column>
        </el-table>

        <!-- Pagination -->
        <div class="pagination-wrapper" v-if="pagination.total > 0">
          <el-pagination
            background
            layout="prev, pager, next, total"
            :total="pagination.total"
            :page-size="pagination.per_page"
            :current-page="pagination.current_page"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Search, RefreshRight } from '@element-plus/icons-vue';
import { useTableHeight } from '@/Composables/useTableHeight';

const mainTableRef = ref(null);
const { tableHeight } = useTableHeight(mainTableRef, 84);

const props = defineProps({
  logs: Array,
  users: Array,
  modules: Array,
  pagination: Object,
  filters: Object,
});

const breadcrumbs = [
  { label: 'Administration' },
  { label: 'Journal d\'Activité' },
];

const actions = [
  { value: 'create', label: 'Création' },
  { value: 'update', label: 'Modification' },
  { value: 'delete', label: 'Suppression' },
  { value: 'validate', label: 'Validation' },
  { value: 'cancel', label: 'Annulation' },
  { value: 'pay', label: 'Paiement' },
  { value: 'settle', label: 'Solde' },
  { value: 'login', label: 'Connexion' },
  { value: 'logout', label: 'Déconnexion' },
];

const localFilters = ref({ ...props.filters });
let searchTimeout = null;

const tagTypeMap = {
  success: 'success',
  warning: 'warning',
  danger: 'danger',
  primary: 'primary',
  info: 'info',
};

const mapTagType = (couleur) => {
  return tagTypeMap[couleur] || 'info';
};

const loadData = () => {
  const params = Object.fromEntries(
    Object.entries(localFilters.value).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );
  router.get(window.location.pathname, params, { preserveState: true, preserveScroll: true });
};

const onSearchInput = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(loadData, 300);
};

const onFilterChange = () => loadData();

const resetFilters = () => {
  localFilters.value = { search: '', user_id: '', module: '', action: '', date_debut: '', date_fin: '' };
  loadData();
};

const handlePageChange = (page) => {
  const params = Object.fromEntries(
    Object.entries(localFilters.value).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );
  router.get(window.location.pathname, { ...params, page }, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.page-container {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  color: #303133;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.entry-count {
  font-size: 13px;
  font-weight: 400;
  color: #909399;
  background: #f4f4f5;
  padding: 2px 10px;
  border-radius: 12px;
}

.filter-card {
  margin-bottom: 16px;
}

.filter-card :deep(.el-card__body) {
  padding: 16px;
}

.filter-card .el-col {
  margin-bottom: 8px;
}

.table-card :deep(.el-card__body) {
  padding: 0;
}

.date-text {
  font-size: 13px;
  color: #606266;
  cursor: default;
}

.description-text {
  font-size: 13px;
  color: #303133;
  line-height: 1.5;
}

.ip-text {
  font-size: 12px;
  color: #909399;
  font-family: 'SF Mono', 'Menlo', 'Monaco', monospace;
}

.pagination-wrapper {
  display: flex;
  justify-content: flex-end;
  padding: 16px 20px;
  border-top: 1px solid #ebeef5;
}
</style>
