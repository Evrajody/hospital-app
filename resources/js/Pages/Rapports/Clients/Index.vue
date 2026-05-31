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

      <!-- Disposition parallèle : rail des rapports à gauche, contenu à droite -->
      <div class="rapports-layout">
        <aside class="rapports-rail">
          <button
            v-for="r in reports"
            :key="r.name"
            type="button"
            class="rail-item"
            :class="{ active: activeTab === r.name }"
            @click="activeTab = r.name"
          >
            <el-icon class="rail-icon"><component :is="r.icon" /></el-icon>
            <span class="rail-label">{{ r.label }}</span>
          </button>
        </aside>

        <section class="rapports-content">
          <transition name="report-slide" mode="out-in">
            <keep-alive>
              <component
                :is="activeReport.component"
                v-bind="activeReport.props"
                :key="activeReport.name"
              />
            </keep-alive>
          </transition>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, markRaw } from 'vue';
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

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  clients: { type: Array, default: () => [] },
  banques: { type: Array, default: () => [] },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rapports Clients', path: '/rapports/clients' },
];

const reports = [
  { name: 'etat-reglements', label: 'État des règlements', icon: markRaw(Money), component: markRaw(EtatReglementsTab), props: { clients: props.clients } },
  { name: 'etat-creances', label: 'État des créances', icon: markRaw(Wallet), component: markRaw(EtatCreancesTab), props: { clients: props.clients } },
  { name: 'brouillard-cheques', label: 'Brouillard de chèques', icon: markRaw(CreditCard), component: markRaw(BrouillardChequesTab), props: { banques: props.banques } },
  { name: 'chiffre-affaires', label: "Chiffre d'affaires", icon: markRaw(TrendCharts), component: markRaw(ChiffreAffairesTab), props: { clients: props.clients } },
  { name: 'pertes-rejets', label: 'Pertes & Rejets', icon: markRaw(Document), component: markRaw(PertesRejetsTab), props: { clients: props.clients } },
];

const activeTab = ref('etat-reglements');
const activeReport = computed(() => reports.find((r) => r.name === activeTab.value) || reports[0]);
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

/* Disposition parallèle */
.rapports-layout {
  display: flex;
  align-items: stretch;
  border: 1px solid #ddd;
  background: #fff;
  min-height: 620px;
}

.rapports-rail {
  width: 248px;
  flex-shrink: 0;
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow-y: auto;
}

.rail-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 11px 12px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #374151;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s, border-color 0.2s;
}

.rail-item:hover {
  background: #eef2f7;
}

.rail-item.active {
  background: var(--gov-green, #008751);
  color: #ffffff;
  font-weight: 600;
}

.rail-icon {
  font-size: 16px;
  flex-shrink: 0;
}

.rail-label {
  line-height: 1.3;
}

.rapports-content {
  flex: 1;
  min-width: 0;
  padding: 20px;
  overflow-x: auto;
}

/* Animation de transition entre rapports */
.report-slide-enter-active,
.report-slide-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.report-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.report-slide-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Responsive : rail horizontal sur petit écran */
@media (max-width: 900px) {
  .rapports-layout {
    flex-direction: column;
    min-height: 0;
  }

  .rapports-rail {
    width: 100%;
    flex-direction: row;
    flex-wrap: nowrap;
    overflow-x: auto;
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
  }

  .rail-item {
    width: auto;
    white-space: nowrap;
    border-left: none;
    border-bottom: 3px solid transparent;
  }

  .rail-item {
    border-bottom: none;
  }
}
</style>
