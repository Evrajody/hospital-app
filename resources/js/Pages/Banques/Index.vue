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
          <el-dropdown @command="handleNewCommand" trigger="click">
            <el-button type="primary" size="large">
              <el-icon><Plus /></el-icon>
              Nouveau
              <el-icon class="el-icon--right"><ArrowDown /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="banque" :icon="OfficeBuilding">
                  Nouvelle Banque
                </el-dropdown-item>
                <el-dropdown-item command="compte" :icon="CreditCard">
                  Nouveau Compte
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
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

      <!-- Main Content with Sidebar -->
      <el-row :gutter="20">
        <!-- Banques List - Left Sidebar -->
        <el-col :span="6">
          <el-card class="banques-sidebar" shadow="never">
            <template #header>
              <div class="sidebar-header">
                <span class="sidebar-title">
                  <el-icon><OfficeBuilding /></el-icon>
                  Banques
                </span>
                <el-button type="primary" size="small" :icon="Plus" circle @click="showBanqueModal = true" />
              </div>
            </template>

            <div class="banques-list" v-if="listeBanques.length > 0">
              <div
                v-for="banque in listeBanques"
                :key="banque.id"
                class="banque-item"
              >
                <div class="banque-item-icon">
                  <el-icon><OfficeBuilding /></el-icon>
                </div>
                <div class="banque-item-info">
                  <div class="banque-item-nom">{{ banque.nom }}</div>
                  <div class="banque-item-comptes">
                    {{ banque.comptes_count || 0 }} compte(s)
                  </div>
                </div>
                <el-dropdown trigger="click" @command="(cmd) => handleBanqueAction(cmd, banque)">
                  <el-button size="small" :icon="More" text />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="add-compte" :icon="Plus">
                        Ajouter un compte
                      </el-dropdown-item>
                      <el-dropdown-item command="edit" :icon="Edit">
                        Modifier
                      </el-dropdown-item>
                      <el-dropdown-item divided command="delete" :icon="Delete">
                        <span style="color: #f56c6c">Supprimer</span>
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>
            </div>

            <el-empty v-else description="Aucune banque" :image-size="60">
              <el-button type="primary" size="small" @click="showBanqueModal = true">
                <el-icon><Plus /></el-icon>
                Créer une banque
              </el-button>
            </el-empty>
          </el-card>
        </el-col>

        <!-- Comptes Bancaires - Right Side -->
        <el-col :span="18">
          <el-row :gutter="16">
            <el-col
              v-for="compte in banques"
              :key="compte.id"
              :span="12"
            >
              <el-card class="banque-card" shadow="hover">
                <template #header>
                  <div class="banque-header">
                    <div class="banque-info">
                      <div class="banque-icon">
                        <el-icon :size="24" :color="getBanqueColor(compte.type)">
                          <component :is="getBanqueIcon(compte.type)" />
                        </el-icon>
                      </div>
                      <div>
                        <div class="banque-nom">{{ compte.nom }}</div>
                        <div class="banque-numero">{{ compte.numero }}</div>
                      </div>
                    </div>
                    <el-tag :type="getTypeTag(compte.type)" size="small">
                      {{ getTypeLabel(compte.type) }}
                    </el-tag>
                  </div>
                </template>

                <div class="banque-body">
                  <!-- Solde principal -->
                  <div class="solde-section">
                    <div class="solde-label">Solde actuel</div>
                    <div class="solde-value" :class="getSoldeClass(compte.solde)">
                      {{ formatMontant(compte.solde) }}
                    </div>
                  </div>

                  <!-- Détails du compte -->
                  <el-descriptions :column="2" size="small" class="banque-details">
                    <el-descriptions-item label="Compte comptable">
                      <span class="compte-numero">{{ compte.compte_comptable }}</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Devise">
                      {{ compte.devise }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Mouvements" :span="2">
                      <div class="mouvements-summary">
                        <span class="mouvement-in">
                          <el-icon><TopRight /></el-icon>
                          {{ compte.mouvements?.entrees || 0 }}
                        </span>
                        <span class="mouvement-out">
                          <el-icon><BottomRight /></el-icon>
                          {{ compte.mouvements?.sorties || 0 }}
                        </span>
                      </div>
                    </el-descriptions-item>
                  </el-descriptions>

                  <!-- Remarques -->
                  <div v-if="compte.remarques" class="banque-remarques">
                    <el-icon><InfoFilled /></el-icon>
                    {{ compte.remarques }}
                  </div>

                  <!-- Actions -->
                  <div class="banque-actions">
                    <el-button size="small" :icon="List" @click="handleViewMouvements(compte)">
                      Mouvements
                    </el-button>
                    <el-button size="small" type="success" :icon="Wallet" @click="handleApprovisionnerBanque(compte)">
                      Approvisionner
                    </el-button>
                    <el-dropdown @command="(cmd) => handleMoreActions(cmd, compte)">
                      <el-button size="small" :icon="More" />
                      <template #dropdown>
                        <el-dropdown-menu>
                          <el-dropdown-item command="edit" :icon="Edit">
                            Modifier
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
        </el-col>
      </el-row>
    <!-- Modal Approvisionnement -->
    <ApprovisionnementBanqueModal
      v-model="showApprovisionnementModal"
      :comptes="banques"
      :compte-id="selectedCompteId"
      @success="handleApprovisionnementSuccess"
    />

    <!-- Modal Nouveau Compte -->
    <CompteBancaireModal
      v-model="showCompteModal"
      :banques="listeBanques"
      :comptes-ohada="comptesOhada"
      @success="handleCompteSuccess"
    />

    <!-- Modal Nouvelle Banque -->
    <BanqueModal
      v-model="showBanqueModal"
      @success="handleBanqueSuccess"
    />

    <!-- Modal Modifier Compte Bancaire -->
    <el-dialog
      v-model="showEditCompteModal"
      title="Modifier le Compte Bancaire"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form v-if="editingCompte" label-position="top" size="large">
        <el-form-item label="Numéro de compte">
          <el-input v-model="editForm.numero_compte" placeholder="Numéro de compte" />
        </el-form-item>
        <el-form-item label="Compte OHADA">
          <el-select v-model="editForm.compte_ohada_id" filterable placeholder="Sélectionner" style="width: 100%">
            <el-option
              v-for="c in comptesOhada"
              :key="c.id"
              :label="`${c.numero} - ${c.libelle}`"
              :value="c.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Observations">
          <el-input v-model="editForm.observations" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showEditCompteModal = false">Annuler</el-button>
        <el-button type="primary" :loading="editLoading" @click="submitEditCompte">
          Enregistrer
        </el-button>
      </template>
    </el-dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import ApprovisionnementBanqueModal from '@/Components/Modals/ApprovisionnementBanqueModal.vue';
import CompteBancaireModal from '@/Components/Modals/CompteBancaireModal.vue';
import BanqueModal from '@/Components/Modals/BanqueModal.vue';
import { fetchApi } from '@/Composables/useFetch';
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
  InfoFilled,
  ArrowDown,
  OfficeBuilding
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  banques: {
    type: Array,
    default: () => []
  },
  listeBanques: {
    type: Array,
    default: () => []
  },
  comptesOhada: {
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

// State
const showApprovisionnementModal = ref(false);
const showCompteModal = ref(false);
const showBanqueModal = ref(false);
const showEditCompteModal = ref(false);
const editingCompte = ref(null);
const editForm = reactive({ numero_compte: '', compte_ohada_id: null, observations: '' });
const editLoading = ref(false);
const selectedCompteId = ref(null);

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

const handleNewCommand = (command) => {
  if (command === 'banque') {
    showBanqueModal.value = true;
  } else if (command === 'compte') {
    showCompteModal.value = true;
  }
};

const handleApprovisionner = () => {
  selectedCompteId.value = null;
  showApprovisionnementModal.value = true;
};

const handleApprovisionnerBanque = (banque) => {
  selectedCompteId.value = banque.id;
  showApprovisionnementModal.value = true;
};

const handleApprovisionnementSuccess = () => {
  router.reload({ only: ['banques', 'stats'] });
};

const handleCompteSuccess = () => {
  router.reload({ only: ['banques', 'listeBanques', 'stats'] });
};

const handleBanqueSuccess = () => {
  router.reload({ only: ['listeBanques'] });
};

const handleViewMouvements = (banque) => {
  router.visit(`/banques/${banque.id}/mouvements`);
};

const handleMoreActions = async (command, compte) => {
  switch (command) {
    case 'edit':
      editingCompte.value = compte;
      editForm.numero_compte = compte.numero;
      editForm.compte_ohada_id = null;
      editForm.observations = compte.remarques || '';
      // Trouver le compte ohada correspondant
      if (compte.compte_comptable) {
        const found = props.comptesOhada.find(c => c.numero === compte.compte_comptable);
        if (found) editForm.compte_ohada_id = found.id;
      }
      showEditCompteModal.value = true;
      break;
    case 'delete':
      ElMessageBox.confirm(
        `Êtes-vous sûr de vouloir supprimer le compte "${compte.nom}" ?`,
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

const submitEditCompte = async () => {
  if (!editingCompte.value) return;
  editLoading.value = true;
  try {
    const response = await fetchApi(`/api/comptes-bancaires/${editingCompte.value.id}`, {
      method: 'PUT',
      body: editForm,
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success(result.message);
      showEditCompteModal.value = false;
      router.reload({ only: ['banques', 'stats'] });
    } else {
      ElMessage.error(result.message || 'Erreur lors de la modification');
    }
  } catch (error) {
    ElMessage.error('Erreur lors de la modification');
  } finally {
    editLoading.value = false;
  }
};

const handleBanqueAction = async (command, banque) => {
  switch (command) {
    case 'add-compte':
      showCompteModal.value = true;
      break;
    case 'edit':
      ElMessage.info('Modification en cours de développement...');
      break;
    case 'delete':
      ElMessageBox.confirm(
        `Êtes-vous sûr de vouloir supprimer la banque "${banque.nom}" ?`,
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/banques/${banque.id}`, {
            method: 'DELETE',
          });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Banque supprimée avec succès');
            router.reload({ only: ['listeBanques'] });
          } else {
            ElMessage.error(result.message || 'Erreur lors de la suppression');
          }
        } catch (error) {
          ElMessage.error('Erreur lors de la suppression');
        }
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

/* Banques Sidebar */
.banques-sidebar {
  border-radius: 8px;
  position: sticky;
  top: 20px;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sidebar-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.banques-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.banque-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background-color: #f9fafb;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.banque-item:hover {
  background-color: #f3f4f6;
}

.banque-item-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background-color: #dbeafe;
  color: #2563eb;
}

.banque-item-info {
  flex: 1;
  min-width: 0;
}

.banque-item-nom {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.banque-item-comptes {
  font-size: 12px;
  color: #6b7280;
}

:deep(.banques-sidebar .el-card__header) {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.banques-sidebar .el-card__body) {
  padding: 16px;
}
</style>
