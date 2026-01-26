<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="clients-container">
      <!-- Header avec bouton d'ajout -->
      <div class="page-header">
        <div>
          <h1>Gestion des Clients</h1>
          <p class="subtitle">Liste complète des clients</p>
        </div>
        <el-button type="primary" :icon="Plus" @click="handleCreate">
          Nouveau Client
        </el-button>
      </div>

      <!-- Statistiques rapides -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <el-icon :size="24"><User /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ clients.length }}</div>
                <div class="stat-label">Total Clients</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                <el-icon :size="24"><TrendCharts /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.factures_en_cours }}</div>
                <div class="stat-label">Factures en cours</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)">
                <el-icon :size="24"><Money /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.creances_total) }}</div>
                <div class="stat-label">Créances totales</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Barre de recherche et filtres -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" :model="filters">
          <el-form-item>
            <el-input
              v-model="filters.search"
              placeholder="Rechercher un client (code, nom, contact...)"
              :prefix-icon="Search"
              style="width: 400px"
              clearable
              @input="handleSearch"
            />
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="filters.type"
              placeholder="Type de client"
              clearable
              style="width: 200px"
            >
              <el-option label="Tous" value="" />
              <el-option label="Particulier" value="particulier" />
              <el-option label="Entreprise" value="entreprise" />
              <el-option label="Assurance" value="assurance" />
              <el-option label="Mutuelle" value="mutuelle" />
            </el-select>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Table des clients -->
      <el-card class="table-card" shadow="never">
        <el-table
          :data="filteredClients"
          stripe
          style="width: 100%"
          :default-sort="{ prop: 'code', order: 'ascending' }"
          @row-click="handleView"
          class="clients-table"
        >
          <el-table-column prop="code" label="Code" width="120" sortable />
          <el-table-column prop="nom" label="Nom du client" sortable>
            <template #default="{ row }">
              <div class="client-name">
                <strong>{{ row.nom }}</strong>
                <span v-if="row.type" class="client-type">{{ getTypeLabel(row.type) }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="telephone" label="Téléphone" width="150" />
          <el-table-column prop="email" label="Email" width="200" />
          <el-table-column label="Solde" width="150" align="right">
            <template #default="{ row }">
              <span :class="['montant', row.solde > 0 ? 'montant-positif' : '']">
                {{ formatMontant(row.solde) }}
              </span>
            </template>
          </el-table-column>
          <el-table-column prop="nb_factures" label="Nb Factures" width="120" align="center" />
          <el-table-column label="Actions" width="200" fixed="right">
            <template #default="{ row }">
              <el-button size="small" :icon="View" @click.stop="handleView(row)">Voir</el-button>
              <el-button size="small" :icon="Edit" @click.stop="handleEdit(row)">Modifier</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Plus, Search, View, Edit, User, TrendCharts, Money } from '@element-plus/icons-vue';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  clients: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      factures_en_cours: 0,
      creances_total: 0
    })
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Clients', path: '/clients' }
];

// Filters
const filters = ref({
  search: '',
  type: ''
});

// Computed
const filteredClients = computed(() => {
  let result = props.clients;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(c =>
      c.code.toLowerCase().includes(search) ||
      c.nom.toLowerCase().includes(search) ||
      c.telephone?.toLowerCase().includes(search) ||
      c.email?.toLowerCase().includes(search)
    );
  }

  if (filters.value.type) {
    result = result.filter(c => c.type === filters.value.type);
  }

  return result;
});

// Methods
const handleCreate = () => {
  router.visit('/clients/create');
};

const handleEdit = (client) => {
  router.visit(`/clients/${client.id}/edit`);
};

const handleView = (client) => {
  router.visit(`/clients/${client.id}`);
};

const handleSearch = () => {
  // Search is reactive via computed
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const getTypeLabel = (type) => {
  const labels = {
    particulier: 'Particulier',
    entreprise: 'Entreprise',
    assurance: 'Assurance',
    mutuelle: 'Mutuelle'
  };
  return labels[type] || type;
};
</script>

<style scoped>
.clients-container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
}

.subtitle {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.filter-card {
  margin-bottom: 20px;
  border: 1px solid #e8e8e8;
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

.table-card {
  border: 1px solid #e8e8e8;
}

.clients-table :deep(.el-table__row) {
  cursor: pointer;
}

.clients-table :deep(.el-table__row:hover) {
  background-color: #f5f7fa;
}

.client-name {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.client-type {
  font-size: 12px;
  color: #909399;
  font-style: italic;
}

.montant {
  font-weight: 600;
  color: #666;
}

.montant-positif {
  color: #dc2626;
}
</style>
