<template>
  <el-container class="app-container">
    <!-- Sidebar -->
    <el-aside :width="isCollapse ? '64px' : '250px'" class="app-sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-logo">
          <img
            v-if="!isCollapse"
            src="/images/ministere_sante_without_republic.png"
            alt="Ministère de la Santé — République du Bénin"
            class="sidebar-logo-img"
          />
          <el-icon v-else :size="28" color="#14532D">
            <OfficeBuilding />
          </el-icon>
        </div>
        <!-- <div class="gov-liserai"></div> -->
      </div>
      <div v-if="!isCollapse" class="sidebar-header">
        <h2>Sysgef</h2>
      </div>

      <el-menu
        :default-active="currentRoute"
        :collapse="isCollapse"
        class="sidebar-menu"
        background-color="#14532D"
        text-color="#fff"
        active-text-color="#fcd116"
      >
        <!-- Dashboard -->
        <el-menu-item index="/dashboard" @click="navigate('/dashboard')">
          <el-icon><HomeFilled /></el-icon>
          <template #title>Tableau de bord</template>
        </el-menu-item>

        <div class="menu-separator"></div>

        <!-- Factures Fournisseurs -->
        <el-menu-item-group v-if="can('fournisseurs.voir') || can('factures-fournisseurs.voir') || can('reglements-fournisseurs.voir')">
          <template #title><span v-if="!isCollapse" class="menu-group-title"><el-icon><Document /></el-icon> Factures Fournisseurs</span></template>
          <el-menu-item v-if="can('fournisseurs.voir')" index="/fournisseurs" @click="navigate('/fournisseurs')">
            <el-icon><User /></el-icon>
            <template #title>Fournisseurs</template>
          </el-menu-item>
          <el-menu-item v-if="can('factures-fournisseurs.voir')" index="/factures-fournisseurs" @click="navigate('/factures-fournisseurs')">
            <el-icon><Document /></el-icon>
            <template #title>Factures</template>
          </el-menu-item>
          <el-menu-item v-if="can('reglements-fournisseurs.voir')" index="/reglements-fournisseurs" @click="navigate('/reglements-fournisseurs')">
            <el-icon><Money /></el-icon>
            <template #title>Règlements</template>
          </el-menu-item>
        </el-menu-item-group>

        <div class="menu-separator"></div>

        <!-- Factures Clients -->
        <el-menu-item-group v-if="can('clients.voir') || can('factures-clients.voir') || can('reglements-clients.voir')">
          <template #title><span v-if="!isCollapse" class="menu-group-title"><el-icon><Money /></el-icon> Factures Clients</span></template>
          <el-menu-item v-if="can('clients.voir')" index="/clients" @click="navigate('/clients')">
            <el-icon><User /></el-icon>
            <template #title>Clients</template>
          </el-menu-item>
          <el-menu-item v-if="can('factures-clients.voir')" index="/factures-clients" @click="navigate('/factures-clients')">
            <el-icon><Document /></el-icon>
            <template #title>Factures</template>
          </el-menu-item>
          <el-menu-item v-if="can('reglements-clients.voir')" index="/reglements-clients" @click="navigate('/reglements-clients')">
            <el-icon><Money /></el-icon>
            <template #title>Règlements</template>
          </el-menu-item>
          <el-menu-item v-if="can('reglements-clients.voir')" index="/avances-clients" @click="navigate('/avances-clients')">
            <el-icon><Wallet /></el-icon>
            <template #title>Avances</template>
          </el-menu-item>
        </el-menu-item-group>

        <div class="menu-separator"></div>

        <!-- Autres -->
        <el-menu-item-group v-if="can('plan-comptable.voir') || can('banques.voir')">
          <template #title><span v-if="!isCollapse" class="menu-group-title"><el-icon><More /></el-icon> Autres</span></template>
          <el-menu-item v-if="can('plan-comptable.voir')" index="/plan-comptable" @click="navigate('/plan-comptable')">
            <el-icon><Notebook /></el-icon>
            <template #title>Plan Comptable</template>
          </el-menu-item>
          <el-menu-item v-if="can('banques.voir')" index="/banques" @click="navigate('/banques')">
            <el-icon><CreditCard /></el-icon>
            <template #title>Banques</template>
          </el-menu-item>
        </el-menu-item-group>

        <div class="menu-separator"></div>

        <!-- Rapports -->
        <el-menu-item-group v-if="can('rapports-clients.voir') || can('rapports-fournisseurs.voir') || can('rapports-banques.voir')">
          <template #title><span v-if="!isCollapse" class="menu-group-title"><el-icon><Printer /></el-icon> Rapports</span></template>
          <el-menu-item v-if="can('rapports-fournisseurs.voir')" index="/rapports/fournisseurs" @click="navigate('/rapports/fournisseurs')">
            <template #title>Rapports Fournisseurs</template>
          </el-menu-item>
          <el-menu-item v-if="can('rapports-clients.voir')" index="/rapports/clients" @click="navigate('/rapports/clients')">
            <template #title>Rapports   Clients</template>
          </el-menu-item>
          <el-menu-item v-if="can('rapports-banques.voir')" index="/rapports/banques" @click="navigate('/rapports/banques')">
            <template #title>Rapports Banques</template>
          </el-menu-item>
        </el-menu-item-group>

        <div class="menu-separator"></div>

        <!-- Paramètres -->
        <el-menu-item-group v-if="can('utilisateurs.voir') || can('roles.voir') || can('parametres.voir')">
          <template #title><span v-if="!isCollapse" class="menu-group-title"><el-icon><Setting /></el-icon> Paramètres</span></template>
          <el-menu-item v-if="can('utilisateurs.voir')" index="/utilisateurs" @click="navigate('/utilisateurs')">
            <el-icon><User /></el-icon>
            <template #title>Utilisateurs</template>
          </el-menu-item>
          <el-menu-item v-if="can('roles.voir')" index="/roles" @click="navigate('/roles')">
            <el-icon><Key /></el-icon>
            <template #title>Rôles & Permissions</template>
          </el-menu-item>
          <el-menu-item v-if="can('parametres.voir')" index="/taux-fiscaux" @click="navigate('/taux-fiscaux')">
            <el-icon><List /></el-icon>
            <template #title>Taux Fiscaux</template>
          </el-menu-item>
          <el-menu-item v-if="can('parametres.voir')" index="/parametres/etablissement" @click="navigate('/parametres/etablissement')">
            <el-icon><OfficeBuilding /></el-icon>
            <template #title>Établissement</template>
          </el-menu-item>
        </el-menu-item-group>

        <div class="menu-separator"></div>

        <!-- Journal d'Activité -->
        <el-menu-item v-if="can('journal.voir')" index="/journal-activite" @click="navigate('/journal-activite')">
          <el-icon><List /></el-icon>
          <template #title>Journal d'Activité</template>
        </el-menu-item>
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
          <!-- User dropdown -->
          <el-dropdown @command="handleCommand">
            <div class="user-profile">
              <el-avatar :size="32" :icon="UserFilled" />
              <span class="username">{{ authUser?.name || 'Utilisateur' }}</span>
              <el-icon><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="profile">
                  <el-icon><User /></el-icon>
                  Mon Profil
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

    <!-- Visualiseur PDF global (aperçu + téléchargement explicite) -->
    <PdfViewerDrawer />
  </el-container>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import { useInactivityLogout } from '@/Composables/useInactivityLogout';
import PdfViewerDrawer from '@/Components/PdfViewerDrawer.vue';
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
  ArrowDown,
  SwitchButton,
  Expand,
  Fold,
  List,
  Key,
  More,
  Wallet
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

// Auth partagé via Inertia
const page = usePage();
const authUser = computed(() => page.props.auth?.user || props.user);

// Permissions
const { can } = usePermissions();

// Auto-logout on inactivity
useInactivityLogout();

// State
const isCollapse = ref(false);
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
  background-color: #14532D;
  transition: width 0.3s;
  display: flex;
  flex-direction: column;
  position: relative;
}

.sidebar-brand {
  background: #fff;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px 14px;
  min-height: 56px;
}

.sidebar-logo-img {
  max-width: 100%;
  height: auto;
  max-height: 46px;
  display: block;
}

.gov-liserai {
  height: 4px;
  width: 100%;
  background: linear-gradient(
    to right,
    #008751 0 33.33%,
    #fcd116 33.33% 66.66%,
    #e8112d 66.66% 100%
  );
}

.sidebar-header {
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 12px 20px;
}

.sidebar-header h2 {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
  white-space: normal;
  word-break: break-word;
  line-height: 1.3;
  flex: 1;
  text-align: center;
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

.menu-group-title {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: rgba(255, 255, 255, 0.6);
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.menu-separator {
  height: 1px;
  margin: 6px 12px;
  background: linear-gradient(
    to right,
    transparent,
    rgba(255, 255, 255, 0.18) 15%,
    rgba(255, 255, 255, 0.18) 85%,
    transparent
  );
}

:deep(.el-menu-item-group) {
  margin-top: 8px;
}

:deep(.el-menu-item-group__title) {
  padding-bottom: 4px;
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
