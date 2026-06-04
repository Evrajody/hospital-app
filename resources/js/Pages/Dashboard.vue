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
                <h2 class="kpi-value">{{ formatCurrency(kpis.chiffre_affaires) }}</h2>
                <span v-if="kpis.trend_ca !== null" :class="['kpi-trend', kpis.trend_ca >= 0 ? 'trend-up' : 'trend-down']">
                  <el-icon><component :is="kpis.trend_ca >= 0 ? CaretTop : CaretBottom" /></el-icon>
                  {{ kpis.trend_ca >= 0 ? '+' : '' }}{{ kpis.trend_ca }}% ce mois
                </span>
                <span v-else class="kpi-trend trend-neutral">Ce mois</span>
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
                <h2 class="kpi-value">{{ kpis.factures_en_attente }}</h2>
                <span class="kpi-trend trend-neutral">
                  {{ kpis.factures_clients_en_attente ?? 0 }} client{{ (kpis.factures_clients_en_attente ?? 0) > 1 ? 's' : '' }} &middot; {{ kpis.factures_fournisseurs_en_attente ?? 0 }} fournisseur{{ (kpis.factures_fournisseurs_en_attente ?? 0) > 1 ? 's' : '' }}
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
                <h2 class="kpi-value">{{ formatCurrency(kpis.dettes_fournisseurs) }}</h2>
                <span v-if="kpis.trend_dettes !== null" :class="['kpi-trend', kpis.trend_dettes <= 0 ? 'trend-up' : 'trend-down']">
                  <el-icon><component :is="kpis.trend_dettes <= 0 ? CaretBottom : CaretTop" /></el-icon>
                  {{ kpis.trend_dettes >= 0 ? '+' : '' }}{{ kpis.trend_dettes }}% ce mois
                </span>
                <span v-else class="kpi-trend trend-neutral">Total</span>
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
                <h2 class="kpi-value">{{ formatCurrency(kpis.creances_clients) }}</h2>
                <span v-if="kpis.trend_creances !== null" :class="['kpi-trend', kpis.trend_creances <= 0 ? 'trend-up' : 'trend-down']">
                  <el-icon><component :is="kpis.trend_creances <= 0 ? CaretBottom : CaretTop" /></el-icon>
                  {{ kpis.trend_creances >= 0 ? '+' : '' }}{{ kpis.trend_creances }}% ce mois
                </span>
                <span v-else class="kpi-trend trend-neutral">Total</span>
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

            <el-table :data="dernieres_factures" style="width: 100%">
              <el-table-column prop="numero" label="N° Facture" width="120" sortable />
              <el-table-column prop="fournisseur" label="Fournisseur" sortable />
              <el-table-column prop="montant" label="Montant" width="150" sortable>
                <template #default="{ row }">
                  {{ formatCurrency(row.montant) }}
                </template>
              </el-table-column>
              <el-table-column prop="date" label="Date" width="120" sortable />
              <el-table-column prop="statut" label="Statut" width="150" sortable>
                <template #default="{ row }">
                  <el-tag :type="row.statut_type">
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
                <el-button type="primary" size="small" @click="navigate('/banques')">
                  Voir tout
                </el-button>
              </div>
            </template>

            <div class="bank-list">
              <div v-for="bank in banques" :key="bank.id" class="bank-item">
                <div class="bank-info">
                  <el-icon :size="24" color="var(--el-color-primary)"><CreditCard /></el-icon>
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
              <span class="total-amount">{{ formatCurrency(solde_total_banques) }}</span>
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
            <v-chart :option="evolutionChartOption" autoresize style="height: 350px;" />
          </el-card>
        </el-col>

        <el-col :xs="24" :lg="12">
          <el-card shadow="hover">
            <template #header>
              <h3>Répartition des charges ({{ new Date().getFullYear() }})</h3>
            </template>
            <v-chart v-if="repartition_charges.length > 0" :option="repartitionChartOption" autoresize style="height: 350px;" />
            <div v-else class="chart-placeholder">
              <el-icon :size="60" color="#ccc"><PieChart /></el-icon>
              <p>Aucune donnée pour cette année</p>
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
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { BarChart, PieChart as EchartsPie } from 'echarts/charts';
import {
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
} from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import {
  TrendCharts,
  DocumentCopy,
  Wallet,
  Money,
  CreditCard,
  CaretTop,
  CaretBottom,
  PieChart
} from '@element-plus/icons-vue';

use([BarChart, EchartsPie, TitleComponent, TooltipComponent, LegendComponent, GridComponent, CanvasRenderer]);

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({ name: 'Utilisateur' })
  },
  kpis: {
    type: Object,
    default: () => ({})
  },
  dernieres_factures: {
    type: Array,
    default: () => []
  },
  banques: {
    type: Array,
    default: () => []
  },
  solde_total_banques: {
    type: Number,
    default: 0
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  evolution_mensuelle: {
    type: Array,
    default: () => []
  },
  repartition_charges: {
    type: Array,
    default: () => []
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' }
];

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits: 0,
    minimumFractionDigits: 0
  }).format(amount || 0);
};

const formatCompact = (value) => {
  if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
  if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
  return value.toString();
};

const navigate = (path) => {
  router.visit(path);
};

// --- Charts ---

const evolutionChartOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    formatter: (params) => {
      let html = `<strong>${params[0].axisValue}</strong><br/>`;
      params.forEach(p => {
        html += `${p.marker} ${p.seriesName}: ${formatCurrency(p.value)}<br/>`;
      });
      return html;
    },
  },
  legend: {
    data: ['Recettes', 'Dépenses'],
    bottom: 0,
  },
  grid: {
    left: '3%',
    right: '4%',
    bottom: '12%',
    top: '8%',
    containLabel: true,
  },
  xAxis: {
    type: 'category',
    data: props.evolution_mensuelle.map(e => e.mois),
    axisLabel: {
      rotate: 30,
      fontSize: 11,
    },
  },
  yAxis: {
    type: 'value',
    axisLabel: {
      formatter: (v) => formatCompact(v),
    },
  },
  series: [
    {
      name: 'Recettes',
      type: 'bar',
      data: props.evolution_mensuelle.map(e => e.recettes),
      itemStyle: { color: '#22c55e', borderRadius: [4, 4, 0, 0] },
      barMaxWidth: 30,
    },
    {
      name: 'Dépenses',
      type: 'bar',
      data: props.evolution_mensuelle.map(e => e.depenses),
      itemStyle: { color: '#ef4444', borderRadius: [4, 4, 0, 0] },
      barMaxWidth: 30,
    },
  ],
}));

const repartitionChartOption = computed(() => ({
  tooltip: {
    trigger: 'item',
    formatter: (params) => {
      return `${params.name}<br/>${formatCurrency(params.value)} (${params.percent}%)`;
    },
  },
  legend: {
    orient: 'vertical',
    right: '5%',
    top: 'center',
    textStyle: { fontSize: 12 },
  },
  series: [
    {
      type: 'pie',
      radius: ['40%', '70%'],
      center: ['35%', '50%'],
      avoidLabelOverlap: true,
      itemStyle: {
        borderRadius: 6,
        borderColor: '#fff',
        borderWidth: 2,
      },
      label: { show: false },
      emphasis: {
        label: {
          show: true,
          fontSize: 14,
          fontWeight: 'bold',
        },
      },
      data: props.repartition_charges,
    },
  ],
}));
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
  color: var(--el-color-primary);
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

</style>
