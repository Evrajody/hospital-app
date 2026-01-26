<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="factures-container">
      <!-- Header avec bouton d'ajout -->
      <div class="page-header">
        <div>
          <h1>Factures Clients</h1>
          <p class="subtitle">Gestion des factures et créances</p>
        </div>
        <el-button type="primary" :icon="Plus" @click="handleCreate">
          Nouvelle Facture
        </el-button>
      </div>

      <!-- Statistiques rapides -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <el-icon :size="24"><Document /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ filteredFactures.length }}</div>
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
                <div class="stat-label">Total Facturé</div>
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
                <div class="stat-value">{{ formatMontant(stats.total_regle) }}</div>
                <div class="stat-label">Total Réglé</div>
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
                <div class="stat-label">Créances</div>
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
              placeholder="Rechercher (N° facture, client...)"
              :prefix-icon="Search"
              style="width: 350px"
              clearable
            />
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="filters.client_id"
              placeholder="Tous les clients"
              clearable
              filterable
              style="width: 250px"
            >
              <el-option
                v-for="client in clients"
                :key="client.id"
                :label="`${client.code} - ${client.nom}`"
                :value="client.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="filters.statut"
              placeholder="Statut"
              clearable
              style="width: 180px"
            >
              <el-option label="Tous" value="" />
              <el-option label="Non réglée" value="non_reglee" />
              <el-option label="Partiellement réglée" value="partielle" />
              <el-option label="Réglée" value="reglee" />
              <el-option label="Soldée" value="soldee" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-date-picker
              v-model="filters.periode"
              type="daterange"
              range-separator="-"
              start-placeholder="Date début"
              end-placeholder="Date fin"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
            />
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Table des factures -->
      <el-card class="table-card" shadow="never">
        <el-table
          :data="filteredFactures"
          stripe
          style="width: 100%"
          :default-sort="{ prop: 'date_facture', order: 'descending' }"
          @row-click="handleView"
          class="factures-table"
        >
          <el-table-column prop="numero" label="N° Facture" width="140" sortable />
          <el-table-column prop="date_facture" label="Date" width="120" sortable>
            <template #default="{ row }">
              {{ formatDate(row.date_facture) }}
            </template>
          </el-table-column>
          <el-table-column prop="client.nom" label="Client" sortable>
            <template #default="{ row }">
              <div class="client-info">
                <strong>{{ row.client.nom }}</strong>
                <span class="client-code">{{ row.client.code }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="Montant TTC" width="150" align="right" sortable>
            <template #default="{ row }">
              {{ formatMontant(row.montant_ttc) }}
            </template>
          </el-table-column>
          <el-table-column label="Payé" width="150" align="right">
            <template #default="{ row }">
              <span class="montant-paye">{{ formatMontant(getTotalPaye(row)) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Reste" width="150" align="right">
            <template #default="{ row }">
              <span :class="['montant-reste', getReste(row) > 0 ? 'has-reste' : '']">
                {{ formatMontant(getReste(row)) }}
              </span>
            </template>
          </el-table-column>
          <el-table-column label="Statut" width="150" align="center">
            <template #default="{ row }">
              <el-tag :type="getStatutType(row)" size="small">
                {{ getStatutLabel(row) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="200" fixed="right">
            <template #default="{ row }">
              <el-button size="small" :icon="View" @click.stop="handleView(row)">Voir</el-button>
              <el-button v-if="getReste(row) > 0" size="small" type="success" @click.stop="handleRegler(row)">
                Régler
              </el-button>
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
import {
  Plus,
  Search,
  Edit,
  View,
  Document,
  Money,
  SuccessFilled,
  Warning
} from '@element-plus/icons-vue';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  factures: {
    type: Array,
    default: () => []
  },
  clients: {
    type: Array,
    default: () => []
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/clients/factures' }
];

// Filters
const filters = ref({
  search: '',
  client_id: null,
  statut: '',
  periode: null
});

// Computed
const filteredFactures = computed(() => {
  let result = props.factures;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    result = result.filter(f =>
      f.numero.toLowerCase().includes(search) ||
      f.client.nom.toLowerCase().includes(search) ||
      f.client.code.toLowerCase().includes(search)
    );
  }

  if (filters.value.client_id) {
    result = result.filter(f => f.client.id === filters.value.client_id);
  }

  if (filters.value.statut) {
    result = result.filter(f => getStatutKey(f) === filters.value.statut);
  }

  if (filters.value.periode && filters.value.periode.length === 2) {
    result = result.filter(f => {
      const date = new Date(f.date_facture);
      const debut = new Date(filters.value.periode[0]);
      const fin = new Date(filters.value.periode[1]);
      return date >= debut && date <= fin;
    });
  }

  return result;
});

const stats = computed(() => ({
  total_facture: filteredFactures.value.reduce((sum, f) => sum + f.montant_ttc, 0),
  total_regle: filteredFactures.value.reduce((sum, f) => sum + getTotalPaye(f), 0),
  total_reste: filteredFactures.value.reduce((sum, f) => sum + getReste(f), 0)
}));

// Methods
const getTotalPaye = (facture) => {
  return facture.reglements?.reduce((sum, r) => sum + r.montant, 0) || 0;
};

const getReste = (facture) => {
  return facture.montant_ttc - getTotalPaye(facture);
};

const getStatutKey = (facture) => {
  if (facture.soldee) return 'soldee';
  const reste = getReste(facture);
  if (reste === 0) return 'reglee';
  if (getTotalPaye(facture) > 0) return 'partielle';
  return 'non_reglee';
};

const getStatutLabel = (facture) => {
  const key = getStatutKey(facture);
  const labels = {
    non_reglee: 'Non réglée',
    partielle: 'Partielle',
    reglee: 'Réglée',
    soldee: 'Soldée'
  };
  return labels[key] || '';
};

const getStatutType = (facture) => {
  const key = getStatutKey(facture);
  const types = {
    non_reglee: 'danger',
    partielle: 'warning',
    reglee: 'success',
    soldee: 'info'
  };
  return types[key] || '';
};

const handleCreate = () => {
  router.visit('/clients/factures/create');
};

const handleView = (facture) => {
  router.visit(`/factures-clients/${facture.id}`);
};

const handleRegler = (facture) => {
  router.visit(`/factures-clients/${facture.id}?action=regler`);
};

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
</script>

<style scoped>
.factures-container {
  max-width: 1600px;
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
  font-size: 20px;
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

.factures-table :deep(.el-table__row) {
  cursor: pointer;
}

.factures-table :deep(.el-table__row:hover) {
  background-color: #f5f7fa;
}

.client-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.client-code {
  font-size: 12px;
  color: #909399;
}

.montant-paye {
  color: #059669;
  font-weight: 600;
}

.montant-reste {
  font-weight: 600;
  color: #666;
}

.montant-reste.has-reste {
  color: #dc2626;
}
</style>
