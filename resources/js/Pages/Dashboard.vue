<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="dashboard">
      <!-- Page Header -->
      <div class="page-header">
        <h1>Tableau de bord</h1>
        <p class="subtitle">Vue d'ensemble de votre activité</p>
      </div>

      <!-- KPI Cards -->
      <el-row :gutter="20" class="kpi-row">
        <el-col :xs="24" :sm="12" :lg="6">
          <el-card shadow="hover" class="kpi-card kpi-success">
            <div class="kpi-content">
              <div class="kpi-icon">
                <el-icon :size="40"><TrendCharts /></el-icon>
              </div>
              <div class="kpi-info">
                <p class="kpi-label">Chiffre d'affaires</p>
                <h2 class="kpi-value">{{ formatCurrency(mockData.chiffreAffaires) }}</h2>
                <span class="kpi-trend trend-up">
                  <el-icon><CaretTop /></el-icon>
                  +12.5% ce mois
                </span>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :lg="6">
          <el-card shadow="hover" class="kpi-card kpi-warning">
            <div class="kpi-content">
              <div class="kpi-icon">
                <el-icon :size="40"><DocumentCopy /></el-icon>
              </div>
              <div class="kpi-info">
                <p class="kpi-label">Factures en attente</p>
                <h2 class="kpi-value">{{ mockData.facturesEnAttente }}</h2>
                <span class="kpi-trend trend-neutral">
                  À traiter
                </span>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :lg="6">
          <el-card shadow="hover" class="kpi-card kpi-danger">
            <div class="kpi-content">
              <div class="kpi-icon">
                <el-icon :size="40"><Wallet /></el-icon>
              </div>
              <div class="kpi-info">
                <p class="kpi-label">Dettes fournisseurs</p>
                <h2 class="kpi-value">{{ formatCurrency(mockData.dettesFournisseurs) }}</h2>
                <span class="kpi-trend trend-down">
                  <el-icon><CaretBottom /></el-icon>
                  -5.3% ce mois
                </span>
              </div>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :lg="6">
          <el-card shadow="hover" class="kpi-card kpi-info">
            <div class="kpi-content">
              <div class="kpi-icon">
                <el-icon :size="40"><Money /></el-icon>
              </div>
              <div class="kpi-info">
                <p class="kpi-label">Créances clients</p>
                <h2 class="kpi-value">{{ formatCurrency(mockData.creancesClients) }}</h2>
                <span class="kpi-trend trend-up">
                  <el-icon><CaretTop /></el-icon>
                  +8.2% ce mois
                </span>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Charts and Tables Row -->
      <el-row :gutter="20" class="content-row">
        <!-- Recent Invoices -->
        <el-col :xs="24" :lg="16">
          <el-card shadow="hover">
            <template #header>
              <div class="card-header">
                <h3>Dernières factures fournisseurs</h3>
                <el-button type="primary" size="small" @click="navigate('/factures-fournisseurs')">
                  Voir tout
                </el-button>
              </div>
            </template>

            <el-table :data="mockData.recentInvoices" style="width: 100%">
              <el-table-column prop="numero" label="N° Facture" width="120" />
              <el-table-column prop="fournisseur" label="Fournisseur" />
              <el-table-column prop="montant" label="Montant" width="150">
                <template #default="{ row }">
                  {{ formatCurrency(row.montant) }}
                </template>
              </el-table-column>
              <el-table-column prop="date" label="Date" width="120" />
              <el-table-column label="Statut" width="150">
                <template #default="{ row }">
                  <el-tag :type="getStatusType(row.statut)">
                    {{ row.statut }}
                  </el-tag>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>

        <!-- Banking Overview -->
        <el-col :xs="24" :lg="8">
          <el-card shadow="hover">
            <template #header>
              <div class="card-header">
                <h3>Situation des banques</h3>
              </div>
            </template>

            <div class="bank-list">
              <div v-for="bank in mockData.banks" :key="bank.id" class="bank-item">
                <div class="bank-info">
                  <el-icon :size="24" color="#409EFF"><CreditCard /></el-icon>
                  <div>
                    <p class="bank-name">{{ bank.name }}</p>
                    <p class="bank-account">{{ bank.account }}</p>
                  </div>
                </div>
                <div class="bank-balance">
                  <p :class="['balance', bank.balance >= 0 ? 'positive' : 'negative']">
                    {{ formatCurrency(bank.balance) }}
                  </p>
                </div>
              </div>
            </div>

            <div class="total-balance">
              <strong>Solde total:</strong>
              <span class="total-amount">{{ formatCurrency(totalBankBalance) }}</span>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Charts Row -->
      <el-row :gutter="20" class="content-row">
        <el-col :xs="24" :lg="12">
          <el-card shadow="hover">
            <template #header>
              <h3>Évolution mensuelle</h3>
            </template>
            <div class="chart-placeholder">
              <el-icon :size="60" color="#ccc"><TrendCharts /></el-icon>
              <p>Graphique à venir (intégrer Chart.js ou ECharts)</p>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :lg="12">
          <el-card shadow="hover">
            <template #header>
              <h3>Répartition des charges</h3>
            </template>
            <div class="chart-placeholder">
              <el-icon :size="60" color="#ccc"><PieChart /></el-icon>
              <p>Graphique à venir (intégrer Chart.js ou ECharts)</p>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Quick Actions -->
      <el-row :gutter="20" class="content-row">
        <el-col :span="24">
          <el-card shadow="hover">
            <template #header>
              <h3>Actions rapides</h3>
            </template>

            <div class="quick-actions">
              <el-button type="primary" :icon="Plus" @click="navigate('/factures-fournisseurs/create')">
                Nouvelle facture fournisseur
              </el-button>
              <el-button type="success" :icon="Plus" @click="navigate('/factures-clients/create')">
                Nouvelle facture client
              </el-button>
              <el-button type="warning" :icon="DocumentCopy" @click="navigate('/rapports')">
                Générer un rapport
              </el-button>
              <el-button :icon="CreditCard" @click="navigate('/banques/approvisionner')">
                Approvisionner une banque
              </el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  TrendCharts,
  DocumentCopy,
  Wallet,
  Money,
  CreditCard,
  CaretTop,
  CaretBottom,
  Plus,
  PieChart
} from '@element-plus/icons-vue';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({ name: 'Utilisateur' })
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' }
];

// Mock Data
const mockData = {
  chiffreAffaires: 45000000,
  facturesEnAttente: 23,
  dettesFournisseurs: 12500000,
  creancesClients: 8750000,
  recentInvoices: [
    {
      numero: 'F-2025-001',
      fournisseur: 'SOBEBRA',
      montant: 1250000,
      date: '2025-12-28',
      statut: 'Non réglée'
    },
    {
      numero: 'F-2025-002',
      fournisseur: 'IBEDC',
      montant: 450000,
      date: '2025-12-27',
      statut: 'Partiellement réglée'
    },
    {
      numero: 'F-2025-003',
      fournisseur: 'SONEB',
      montant: 320000,
      date: '2025-12-26',
      statut: 'Réglée'
    },
    {
      numero: 'F-2025-004',
      fournisseur: 'Pharmacie Centrale',
      montant: 2800000,
      date: '2025-12-25',
      statut: 'Non réglée'
    },
    {
      numero: 'F-2025-005',
      fournisseur: 'MTN Bénin',
      montant: 180000,
      date: '2025-12-24',
      statut: 'Réglée'
    }
  ],
  banks: [
    { id: 1, name: 'BOA Bénin', account: '0012345678', balance: 15500000 },
    { id: 2, name: 'Ecobank', account: '0087654321', balance: 8200000 },
    { id: 3, name: 'SGBB', account: '0054321098', balance: 3450000 },
    { id: 4, name: 'Caisse', account: 'CAISSE-01', balance: 850000 }
  ]
};

// Computed
const totalBankBalance = computed(() => {
  return mockData.banks.reduce((sum, bank) => sum + bank.balance, 0);
});

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(amount);
};

const getStatusType = (status) => {
  const types = {
    'Non réglée': 'danger',
    'Partiellement réglée': 'warning',
    'Réglée': 'success',
    'Soldée': 'info'
  };
  return types[status] || 'info';
};

const navigate = (path) => {
  router.visit(path);
};
</script>

<style scoped>
.dashboard {
  max-width: 1600px;
  margin: 0 auto;
}

.page-header {
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

.kpi-row {
  margin-bottom: 24px;
}

.kpi-card {
  border-radius: 8px;
  margin-bottom: 20px;
}

.kpi-content {
  display: flex;
  gap: 16px;
  align-items: center;
}

.kpi-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-success .kpi-icon {
  background-color: #f0f9ff;
  color: #0ea5e9;
}

.kpi-warning .kpi-icon {
  background-color: #fef3c7;
  color: #f59e0b;
}

.kpi-danger .kpi-icon {
  background-color: #fee2e2;
  color: #ef4444;
}

.kpi-info .kpi-icon {
  background-color: #f0fdf4;
  color: #22c55e;
}

.kpi-info {
  flex: 1;
}

.kpi-label {
  font-size: 13px;
  color: #666;
  margin: 0 0 4px 0;
}

.kpi-value {
  font-size: 24px;
  font-weight: 700;
  color: #333;
  margin: 0 0 4px 0;
}

.kpi-trend {
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.trend-up {
  color: #22c55e;
}

.trend-down {
  color: #ef4444;
}

.trend-neutral {
  color: #6b7280;
}

.content-row {
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.bank-list {
  margin-bottom: 16px;
}

.bank-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.bank-item:last-child {
  border-bottom: none;
}

.bank-info {
  display: flex;
  gap: 12px;
  align-items: center;
}

.bank-name {
  font-weight: 600;
  margin: 0 0 4px 0;
  font-size: 14px;
}

.bank-account {
  color: #666;
  font-size: 12px;
  margin: 0;
}

.bank-balance {
  text-align: right;
}

.balance {
  font-weight: 600;
  margin: 0;
  font-size: 15px;
}

.balance.positive {
  color: #22c55e;
}

.balance.negative {
  color: #ef4444;
}

.total-balance {
  padding-top: 16px;
  border-top: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 15px;
}

.total-amount {
  font-weight: 700;
  color: #409EFF;
  font-size: 18px;
}

.chart-placeholder {
  height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #999;
  background-color: #fafafa;
  border-radius: 8px;
}

.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
</style>
