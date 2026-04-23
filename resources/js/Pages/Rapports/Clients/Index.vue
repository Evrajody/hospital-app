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

      <!-- Tabs -->
      <el-tabs v-model="activeTab" type="border-card" class="rapports-tabs">
        <el-tab-pane name="etat-reglements" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Money /></el-icon>
              État des règlements
            </span>
          </template>
          <EtatReglementsTab :clients="clients" />
        </el-tab-pane>

        <el-tab-pane name="etat-creances" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Wallet /></el-icon>
              État des créances
            </span>
          </template>
          <EtatCreancesTab :clients="clients" />
        </el-tab-pane>

        <el-tab-pane name="brouillard-cheques" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><CreditCard /></el-icon>
              Brouillard de chèques
            </span>
          </template>
          <BrouillardChequesTab :banques="banques" />
        </el-tab-pane>

        <el-tab-pane name="chiffre-affaires" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><TrendCharts /></el-icon>
              Chiffre d'affaires
            </span>
          </template>
          <ChiffreAffairesTab :clients="clients" />
        </el-tab-pane>

        <el-tab-pane name="pertes-rejets" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Document /></el-icon>
              Pertes & Rejets
            </span>
          </template>
          <PertesRejetsTab :clients="clients" />
        </el-tab-pane>

        <el-tab-pane name="reglements-par-type" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><TrendCharts /></el-icon>
              Règlements par type client
            </span>
          </template>
          <ReglementsParTypeClientTab />
        </el-tab-pane>
      </el-tabs>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Document,
  Wallet,
  CreditCard,
  TrendCharts,
  Money,
} from '@element-plus/icons-vue';

import EtatReglementsTab from './Tabs/EtatReglementsTab.vue';
import EtatCreancesTab from './Tabs/EtatCreancesTab.vue';
import BrouillardChequesTab from './Tabs/BrouillardChequesTab.vue';
import ChiffreAffairesTab from './Tabs/ChiffreAffairesTab.vue';
import PertesRejetsTab from './Tabs/PertesRejetsTab.vue';
import ReglementsParTypeClientTab from './Tabs/ReglementsParTypeClientTab.vue';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  clients: { type: Array, default: () => [] },
  banques: { type: Array, default: () => [] },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rapports Clients', path: '/rapports/clients' },
];

const activeTab = ref('etat-reglements');
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
  color: #333333;
  margin: 0 0 8px 0;
}

.subtitle {
  color: #666666;
  font-size: 14px;
  margin: 0;
}

/* Tabs styling */
.rapports-tabs {
  border-radius: 0;
}

:deep(.el-tabs--border-card) {
  border: 1px solid #ddd;
  border-radius: 0;
  box-shadow: none;
}

:deep(.el-tabs__header) {
  background-color: #f9fafb;
  border-bottom: 2px solid #333;
}

:deep(.el-tabs__item) {
  height: 44px;
  line-height: 44px;
  font-size: 13px;
}

:deep(.el-tabs__item.is-active) {
  background-color: #ffffff;
  font-weight: 600;
}

:deep(.el-tabs__content) {
  padding: 20px;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
}
</style>
