<template>
  <el-container class="app-container">
    <!-- Sidebar -->
    <el-aside :width="isCollapse ? '64px' : '250px'" class="app-sidebar">
      <div class="sidebar-header">
        <el-icon v-if="!isCollapse" :size="32" color="#409EFF">
          <OfficeBuilding />
        </el-icon>
        <h2 v-if="!isCollapse">Hôpital Ménontin</h2>
      </div>

      <el-menu
        :default-active="currentRoute"
        :collapse="isCollapse"
        class="sidebar-menu"
        background-color="#001529"
        text-color="#fff"
        active-text-color="#409EFF"
      >
        <!-- Dashboard -->
        <el-menu-item index="/dashboard" @click="navigate('/dashboard')">
          <el-icon><HomeFilled /></el-icon>
          <template #title>Tableau de bord</template>
        </el-menu-item>

        <!-- Factures Fournisseurs -->
        <el-sub-menu index="fournisseurs">
          <template #title>
            <el-icon><Document /></el-icon>
            <span>Factures Fournisseurs</span>
          </template>
          <el-menu-item index="/fournisseurs" @click="navigate('/fournisseurs')">
            <el-icon><User /></el-icon>
            <template #title>Fournisseurs</template>
          </el-menu-item>
          <el-menu-item index="/factures-fournisseurs" @click="navigate('/factures-fournisseurs')">
            <el-icon><Document /></el-icon>
            <template #title>Factures</template>
          </el-menu-item>
          <el-menu-item index="/reglements-fournisseurs" @click="navigate('/reglements-fournisseurs')">
            <el-icon><Money /></el-icon>
            <template #title>Règlements</template>
          </el-menu-item>
        </el-sub-menu>

        <!-- Factures Clients -->
        <el-sub-menu index="clients">
          <template #title>
            <el-icon><Money /></el-icon>
            <span>Factures Clients</span>
          </template>
          <el-menu-item index="/clients" @click="navigate('/clients')">
            <el-icon><User /></el-icon>
            <template #title>Clients</template>
          </el-menu-item>
          <el-menu-item index="/factures-clients" @click="navigate('/factures-clients')">
            <el-icon><Document /></el-icon>
            <template #title>Factures</template>
          </el-menu-item>
          <el-menu-item index="/reglements-clients" @click="navigate('/reglements-clients')">
            <el-icon><Money /></el-icon>
            <template #title>Règlements</template>
          </el-menu-item>
        </el-sub-menu>

        <!-- Plan Comptable -->
        <el-menu-item index="/plan-comptable" @click="navigate('/plan-comptable')">
          <el-icon><Notebook /></el-icon>
          <template #title>Plan Comptable</template>
        </el-menu-item>

        <!-- Banques -->
        <el-menu-item index="/banques" @click="navigate('/banques')">
          <el-icon><CreditCard /></el-icon>
          <template #title>Banques</template>
        </el-menu-item>

        <!-- Rapports -->
        <el-sub-menu index="rapports">
          <template #title>
            <el-icon><Printer /></el-icon>
            <span>Rapports</span>
          </template>
          <el-menu-item index="/rapports/fournisseurs" @click="navigate('/rapports/fournisseurs')">
            Fournisseurs
          </el-menu-item>
          <el-menu-item index="/rapports/clients" @click="navigate('/rapports/clients')">
            Clients
          </el-menu-item>
          <el-menu-item index="/rapports/comptables" @click="navigate('/rapports/comptables')">
            Comptables
          </el-menu-item>
        </el-sub-menu>

        <!-- Paramètres -->
        <el-sub-menu index="parametres">
          <template #title>
            <el-icon><Setting /></el-icon>
            <span>Paramètres</span>
          </template>
          <el-menu-item index="/utilisateurs" @click="navigate('/utilisateurs')">
            <el-icon><User /></el-icon>
            <template #title>Utilisateurs</template>
          </el-menu-item>
          <el-menu-item index="/taux-fiscaux" @click="navigate('/taux-fiscaux')">
            <el-icon><List /></el-icon>
            <template #title>Taux Fiscaux</template>
          </el-menu-item>
        </el-sub-menu>
      </el-menu>

      <!-- Toggle button -->
      <div class="sidebar-toggle" @click="toggleCollapse">
        <el-icon>
          <component :is="isCollapse ? 'Expand' : 'Fold'" />
        </el-icon>
      </div>
    </el-aside>

    <!-- Main Content -->
    <el-container>
      <!-- Header -->
      <el-header class="app-header">
        <div class="header-left">
          <el-breadcrumb separator="/">
            <el-breadcrumb-item v-for="item in breadcrumbs" :key="item.path">
              {{ item.title }}
            </el-breadcrumb-item>
          </el-breadcrumb>
        </div>

        <div class="header-right">
          <!-- Notifications -->
          <el-badge :value="notifications" class="notification-badge">
            <el-button :icon="Bell" circle />
          </el-badge>

          <!-- User dropdown -->
          <el-dropdown @command="handleCommand">
            <div class="user-profile">
              <el-avatar :size="32" :icon="UserFilled" />
              <span class="username">{{ user?.name || 'Utilisateur' }}</span>
              <el-icon><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">
                  <el-icon><User /></el-icon>
                  Mon Profil
                </el-dropdown-item>
                <el-dropdown-item command="settings">
                  <el-icon><Setting /></el-icon>
                  Paramètres
                </el-dropdown-item>
                <el-dropdown-item divided command="logout">
                  <el-icon><SwitchButton /></el-icon>
                  Déconnexion
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </el-header>

      <!-- Main Content Area -->
      <el-main class="app-main">
        <slot />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  OfficeBuilding,
  HomeFilled,
  Document,
  Money,
  Notebook,
  CreditCard,
  Printer,
  Setting,
  User,
  UserFilled,
  Bell,
  ArrowDown,
  SwitchButton,
  Expand,
  Fold,
  List
} from '@element-plus/icons-vue';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => null
  },
  breadcrumbs: {
    type: Array,
    default: () => []
  }
});

// State
const isCollapse = ref(false);
const notifications = ref(3); // Mock notifications
const currentRoute = ref(window.location.pathname);

// Methods
const toggleCollapse = () => {
  isCollapse.value = !isCollapse.value;
};

const navigate = (path) => {
  router.visit(path);
};

const handleCommand = (command) => {
  switch (command) {
    case 'profile':
      navigate('/profile');
      break;
    case 'settings':
      navigate('/settings');
      break;
    case 'logout':
      router.post('/logout');
      break;
  }
};
</script>

<style scoped>
.app-container {
  height: 100vh;
  overflow: hidden;
}

.app-sidebar {
  background-color: #001529;
  transition: width 0.3s;
  display: flex;
  flex-direction: column;
  position: relative;
}

.sidebar-header {
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0 20px;
}

.sidebar-header h2 {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  white-space: nowrap;
}

.sidebar-menu {
  flex: 1;
  border-right: none;
  overflow-y: auto;
}

.sidebar-menu::-webkit-scrollbar {
  width: 6px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.sidebar-toggle {
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  cursor: pointer;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  transition: background-color 0.3s;
}

.sidebar-toggle:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.app-header {
  background-color: white;
  border-bottom: 1px solid #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
}

.header-left {
  flex: 1;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.notification-badge {
  cursor: pointer;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.user-profile:hover {
  background-color: #f5f5f5;
}

.username {
  font-size: 14px;
  color: #333;
}

.app-main {
  background-color: #f0f2f5;
  overflow-y: auto;
  padding: 24px;
}
</style>
