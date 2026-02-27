<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="rapports-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Rapports Fournisseurs</h1>
          <p class="subtitle">États et rapports de gestion des fournisseurs</p>
        </div>
      </div>

      <!-- Tabs -->
      <el-tabs v-model="activeTab" type="border-card" class="rapports-tabs">
        <el-tab-pane name="mouvement-factures" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><TrendCharts /></el-icon>
              Mouvement Factures
            </span>
          </template>
          <MouvementFacturesTab :fournisseurs="fournisseurs" />
        </el-tab-pane>

        <el-tab-pane name="etat-reglement-facture" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Document /></el-icon>
              État règlement facture
            </span>
          </template>
          <PlaceholderTab description="État de règlement d'une facture — En cours de développement" />
        </el-tab-pane>

        <el-tab-pane name="situation-fournisseurs" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Wallet /></el-icon>
              Situation fournisseurs
            </span>
          </template>
          <SituationFournisseursTab :fournisseurs="fournisseurs" :comptes="comptes" />
        </el-tab-pane>

        <el-tab-pane name="factures-reglees" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><DocumentCopy /></el-icon>
              Factures réglées
            </span>
          </template>
          <FacturesRegleesTab />
        </el-tab-pane>

        <el-tab-pane name="declaration-aib" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><List /></el-icon>
              Déclaration AIB
            </span>
          </template>
          <DeclarationAibTab />
        </el-tab-pane>

        <el-tab-pane name="point-periodique" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Money /></el-icon>
              Point périodique PC
            </span>
          </template>
          <PointPeriodiqueTab />
        </el-tab-pane>

        <el-tab-pane name="situation-banques" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><OfficeBuilding /></el-icon>
              Situation banques
            </span>
          </template>
          <SituationBanquesTab :banques="banques" />
        </el-tab-pane>

        <el-tab-pane name="recap-charges" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Tickets /></el-icon>
              Récap. charges
            </span>
          </template>
          <PlaceholderTab description="Récapitulatif des charges — En cours de développement" />
        </el-tab-pane>

        <el-tab-pane name="autres" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><More /></el-icon>
              Autres rapports
            </span>
          </template>
          <PlaceholderTab description="Déclaration TVA, Bordereau de transmission, Récap. investissements, etc. — En cours de développement" />
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
  DocumentCopy,
  List,
  Wallet,
  TrendCharts,
  Tickets,
  Money,
  More,
  OfficeBuilding,
} from '@element-plus/icons-vue';

import MouvementFacturesTab from './Tabs/MouvementFacturesTab.vue';
import SituationFournisseursTab from './Tabs/SituationFournisseursTab.vue';
import FacturesRegleesTab from './Tabs/FacturesRegleesTab.vue';
import DeclarationAibTab from './Tabs/DeclarationAibTab.vue';
import PointPeriodiqueTab from './Tabs/PointPeriodiqueTab.vue';
import SituationBanquesTab from './Tabs/SituationBanquesTab.vue';
import PlaceholderTab from './Tabs/PlaceholderTab.vue';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  fournisseurs: { type: Array, default: () => [] },
  comptes: { type: Array, default: () => [] },
  banques: { type: Array, default: () => [] },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rapports Fournisseurs', path: '/rapports/fournisseurs' },
];

const activeTab = ref('mouvement-factures');
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
