<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="rapports-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Rapports Clients</h1>
          <p class="subtitle">États et rapports de gestion des clients et créances</p>
        </div>
      </div>

      <!-- Filtres de période -->
      <el-card class="filter-card" shadow="never">
        <div class="filter-section">
          <el-form :inline="true" :model="filters">
            <el-form-item label="Période">
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
            <el-form-item label="Client">
              <el-select
                v-model="filters.client_id"
                placeholder="Tous"
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
          </el-form>
        </div>
      </el-card>

      <!-- Liste des rapports -->
      <el-row :gutter="20" class="rapports-grid">
        <!-- Rapports de règlement -->
        <el-col :xs="24" :sm="12" :lg="8" v-for="rapport in rapports" :key="rapport.id">
          <el-card shadow="hover" class="rapport-card" :body-style="{ padding: '0px' }">
            <div class="rapport-icon" :style="{ background: rapport.color }">
              <el-icon :size="40">
                <component :is="rapport.icon" />
              </el-icon>
            </div>
            <div class="rapport-content">
              <h3>{{ rapport.titre }}</h3>
              <p class="rapport-description">{{ rapport.description }}</p>
              <div class="rapport-actions">
                <el-button type="primary" size="small" @click="genererRapport(rapport)">
                  <el-icon><View /></el-icon>
                  Générer
                </el-button>
                <el-button size="small" @click="exporterRapport(rapport)">
                  <el-icon><Download /></el-icon>
                  Exporter
                </el-button>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Document,
  DocumentCopy,
  List,
  Wallet,
  CreditCard,
  TrendCharts,
  Tickets,
  View,
  Download,
  Money
} from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  clients: {
    type: Array,
    default: () => []
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rapports Clients', path: '/rapports/clients' }
];

// Filters
const filters = ref({
  periode: null,
  client_id: null
});

// Liste des rapports disponibles
const rapports = ref([
  {
    id: 'etat-reglements',
    titre: 'États périodiques des règlements',
    description: 'Suivi détaillé des paiements reçus des clients',
    icon: 'Money',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    route: '/rapports/clients/etat-reglements'
  },
  {
    id: 'etat-creances',
    titre: 'États périodiques des créances',
    description: 'Point détaillé des factures non soldées par client',
    icon: 'Wallet',
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    route: '/rapports/clients/etat-creances'
  },
  {
    id: 'brouillard-cheques',
    titre: 'Brouillard de chèques',
    description: 'Registre des chèques reçus et imputations comptables',
    icon: 'CreditCard',
    color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    route: '/rapports/clients/brouillard-cheques'
  },
  {
    id: 'chiffre-affaires',
    titre: 'Chiffre d\'affaires',
    description: 'CA global et par client réalisé sur la période',
    icon: 'TrendCharts',
    color: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    route: '/rapports/clients/chiffre-affaires'
  },
  {
    id: 'pertes-rejets',
    titre: 'Pertes, rejets et régularisations',
    description: 'État des impayés, rejets et opérations de régularisation',
    icon: 'Document',
    color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
    route: '/rapports/clients/pertes-rejets'
  }
]);

// Methods
const genererRapport = (rapport) => {
  router.visit(rapport.route, {
    data: {
      periode: filters.value.periode,
      client_id: filters.value.client_id
    }
  });
};

const exporterRapport = (rapport) => {
  ElMessage.info('Fonction d\'export en développement');
};
</script>

<style scoped>
.rapports-container {
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
  margin-bottom: 24px;
  border: 1px solid #e8e8e8;
}

.filter-section {
  display: flex;
  align-items: center;
  gap: 16px;
}

.rapports-grid {
  margin-top: 24px;
}

.rapport-card {
  margin-bottom: 20px;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s;
  cursor: pointer;
}

.rapport-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.rapport-icon {
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.rapport-content {
  padding: 20px;
}

.rapport-content h3 {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
  min-height: 40px;
}

.rapport-description {
  font-size: 13px;
  color: #666;
  margin: 0 0 16px 0;
  min-height: 40px;
  line-height: 1.5;
}

.rapport-actions {
  display: flex;
  gap: 8px;
}

.rapport-actions .el-button {
  flex: 1;
}
</style>
