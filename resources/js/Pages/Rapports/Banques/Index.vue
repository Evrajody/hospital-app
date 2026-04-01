<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="rapports-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Rapports Banques</h1>
          <p class="subtitle">États et rapports de gestion des comptes bancaires</p>
        </div>
      </div>

      <!-- Tabs -->
      <el-tabs v-model="activeTab" type="border-card" class="rapports-tabs">
        <el-tab-pane name="situation-banques" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><OfficeBuilding /></el-icon>
              Situation banques
            </span>
          </template>
          <SituationBanquesTab :banques="banques" />
        </el-tab-pane>
      </el-tabs>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { OfficeBuilding } from '@element-plus/icons-vue';
import SituationBanquesTab from '../Fournisseurs/Tabs/SituationBanquesTab.vue';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  banques: { type: Array, default: () => [] },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rapports Banques', path: '/rapports/banques' },
];

const activeTab = ref('situation-banques');
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
