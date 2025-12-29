<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Gestion des Banques</h1>
          <p class="page-subtitle">Comptes bancaires et soldes de trésorerie</p>
        </div>
        <div class="header-actions">
          <el-button type="success" size="large" @click="handleApprovisionner">
            <el-icon><Wallet /></el-icon>
            Approvisionner
          </el-button>
          <el-button type="primary" size="large" @click="handleCreate">
            <el-icon><Plus /></el-icon>
            Nouveau Compte
          </el-button>
        </div>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="8">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Money /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.solde_total) }}</div>
                <div class="stat-label">Solde Total</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="8">
          <el-card shadow="hover" class="stat-card stat-entrees">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><TopRight /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.entrees_mois) }}</div>
                <div class="stat-label">Entrées ce mois</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="8">
          <el-card shadow="hover" class="stat-card stat-sorties">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><BottomRight /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.sorties_mois) }}</div>
                <div class="stat-label">Sorties ce mois</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Banques List -->
      <el-row :gutter="20">
        <el-col
          v-for="banque in banques"
          :key="banque.id"
          :span="12"
        >
          <el-card class="banque-card" shadow="hover">
            <template #header>
              <div class="banque-header">
                <div class="banque-info">
                  <div class="banque-icon">
                    <el-icon :size="24" :color="getBanqueColor(banque.type)">
                      <component :is="getBanqueIcon(banque.type)" />
                    </el-icon>
                  </div>
                  <div>
                    <div class="banque-nom">{{ banque.nom }}</div>
                    <div class="banque-numero">{{ banque.numero }}</div>
                  </div>
                </div>
                <el-tag :type="getTypeTag(banque.type)" size="small">
                  {{ getTypeLabel(banque.type) }}
                </el-tag>
              </div>
            </template>

            <div class="banque-body">
              <!-- Solde principal -->
              <div class="solde-section">
                <div class="solde-label">Solde actuel</div>
                <div class="solde-value" :class="getSoldeClass(banque.solde)">
                  {{ formatMontant(banque.solde) }}
                </div>
              </div>

              <!-- Détails du compte -->
              <el-descriptions :column="2" size="small" class="banque-details">
                <el-descriptions-item label="Compte comptable">
                  <span class="compte-numero">{{ banque.compte_comptable }}</span>
                </el-descriptions-item>
                <el-descriptions-item label="Devise">
                  {{ banque.devise }}
                </el-descriptions-item>
                <el-descriptions-item label="Mouvements" :span="2">
                  <div class="mouvements-summary">
                    <span class="mouvement-in">
                      <el-icon><TopRight /></el-icon>
                      {{ banque.mouvements.entrees }}
                    </span>
                    <span class="mouvement-out">
                      <el-icon><BottomRight /></el-icon>
                      {{ banque.mouvements.sorties }}
                    </span>
                  </div>
                </el-descriptions-item>
              </el-descriptions>

              <!-- Remarques -->
              <div v-if="banque.remarques" class="banque-remarques">
                <el-icon><InfoFilled /></el-icon>
                {{ banque.remarques }}
              </div>

              <!-- Actions -->
              <div class="banque-actions">
                <el-button size="small" :icon="List" @click="handleViewMouvements(banque)">
                  Mouvements
                </el-button>
                <el-button size="small" type="success" :icon="Wallet" @click="handleApprovisionnerBanque(banque)">
                  Approvisionner
                </el-button>
                <el-dropdown @command="(cmd) => handleMoreActions(cmd, banque)">
                  <el-button size="small" :icon="More" />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="edit" :icon="Edit">
                        Modifier
                      </el-dropdown-item>
                      <el-dropdown-item command="rapprochement" :icon="DocumentChecked">
                        Rapprochement
                      </el-dropdown-item>
                      <el-dropdown-item divided command="delete" :icon="Delete">
                        <span style="color: #f56c6c">Supprimer</span>
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
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
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus,
  Money,
  Wallet,
  TopRight,
  BottomRight,
  List,
  More,
  Edit,
  Delete,
  CreditCard,
  Coin,
  DocumentChecked,
  InfoFilled
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  banques: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      solde_total: 0,
      entrees_mois: 0,
      sorties_mois: 0
    })
  },
  user: {
    type: Object,
    default: () => null
  }
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Banques', path: '/banques' }
];

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const getBanqueIcon = (type) => {
  return type === 'especes' ? Coin : CreditCard;
};

const getBanqueColor = (type) => {
  return type === 'especes' ? '#f59e0b' : '#2563eb';
};

const getTypeTag = (type) => {
  return type === 'especes' ? 'warning' : 'primary';
};

const getTypeLabel = (type) => {
  const labels = {
    banque: 'Compte Bancaire',
    especes: 'Caisse Espèces'
  };
  return labels[type] || type;
};

const getSoldeClass = (solde) => {
  if (solde > 0) return 'solde-positif';
  if (solde < 0) return 'solde-negatif';
  return 'solde-zero';
};

const handleCreate = () => {
  ElMessage.info('Création de compte en cours de développement...');
};

const handleApprovisionner = () => {
  router.visit('/banques/approvisionner');
};

const handleApprovisionnerBanque = (banque) => {
  router.visit(`/banques/approvisionner?banque_id=${banque.id}`);
};

const handleViewMouvements = (banque) => {
  router.visit(`/banques/${banque.id}/mouvements`);
};

const handleMoreActions = async (command, banque) => {
  switch (command) {
    case 'edit':
      ElMessage.info('Modification en cours de développement...');
      break;
    case 'rapprochement':
      ElMessage.info('Rapprochement bancaire en cours de développement...');
      break;
    case 'delete':
      ElMessageBox.confirm(
        `Êtes-vous sûr de vouloir supprimer le compte "${banque.nom}" ?`,
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(() => {
        ElMessage.success('Compte supprimé avec succès');
      });
      break;
  }
};
</script>

<script>
export default {
  layout: null
};
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 12px;
}

/* Stats Cards */
.stats-row {
  margin-bottom: 4px;
}

.stat-card {
  border-radius: 8px;
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  border-radius: 12px;
}

.stat-total .stat-icon {
  background-color: #dbeafe;
  color: #2563eb;
}

.stat-entrees .stat-icon {
  background-color: #dcfce7;
  color: #16a34a;
}

.stat-sorties .stat-icon {
  background-color: #fee2e2;
  color: #dc2626;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
}

/* Banque Cards */
.banque-card {
  border-radius: 8px;
  margin-bottom: 20px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.banque-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.banque-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.banque-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.banque-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 10px;
  background-color: #f3f4f6;
}

.banque-nom {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 2px;
}

.banque-numero {
  font-size: 12px;
  font-family: 'Courier New', monospace;
  color: #6b7280;
}

.banque-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.solde-section {
  text-align: center;
  padding: 20px;
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-radius: 8px;
}

.solde-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.solde-value {
  font-size: 28px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
}

.solde-positif {
  color: #059669;
}

.solde-negatif {
  color: #dc2626;
}

.solde-zero {
  color: #6b7280;
}

.banque-details {
  margin-top: 8px;
}

.compte-numero {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2563eb;
  font-size: 13px;
}

.mouvements-summary {
  display: flex;
  gap: 16px;
  font-size: 13px;
}

.mouvement-in {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #059669;
  font-weight: 600;
}

.mouvement-out {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #dc2626;
  font-weight: 600;
}

.banque-remarques {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px;
  background-color: #fef3c7;
  border-left: 3px solid #f59e0b;
  border-radius: 4px;
  font-size: 12px;
  color: #78350f;
  line-height: 1.5;
}

.banque-actions {
  display: flex;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.stat-card .el-card__body) {
  padding: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  width: 140px;
}
</style>
