<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Règlements Fournisseurs</h1>
          <p class="page-subtitle">Historique complet des paiements effectués</p>
        </div>
        <el-button type="primary" size="large" @click="handleNewPayment">
          <el-icon><Plus /></el-icon>
          Nouveau Règlement
        </el-button>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Money /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.total_reglements) }}</div>
                <div class="stat-label">Total Règlements</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-today">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Calendar /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.reglements_mois) }}</div>
                <div class="stat-label">Ce Mois</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-count">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><DocumentChecked /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.nombre_reglements }}</div>
                <div class="stat-label">Nombre de Règlements</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-average">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><TrendCharts /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_moyen) }}</div>
                <div class="stat-label">Montant Moyen</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Filters Card -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" :model="filters" class="filter-form">
          <el-form-item label="Recherche">
            <el-input
              v-model="filters.search"
              placeholder="N° facture, référence..."
              :prefix-icon="Search"
              clearable
              style="width: 250px"
              @input="handleSearch"
            />
          </el-form-item>

          <el-form-item label="Fournisseur">
            <el-select
              v-model="filters.fournisseur_id"
              placeholder="Tous"
              clearable
              filterable
              style="width: 200px"
              @change="handleSearch"
            >
              <el-option
                v-for="fournisseur in fournisseurs"
                :key="fournisseur.id"
                :label="fournisseur.nom"
                :value="fournisseur.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Mode de Paiement">
            <el-select
              v-model="filters.mode_paiement"
              placeholder="Tous"
              clearable
              style="width: 150px"
              @change="handleSearch"
            >
              <el-option label="Espèces" value="especes" />
              <el-option label="Chèque" value="cheque" />
              <el-option label="Virement bancaire" value="virement" />
            </el-select>
          </el-form-item>

          <el-form-item label="Période">
            <el-date-picker
              v-model="filters.date_range"
              type="daterange"
              range-separator="à"
              start-placeholder="Date début"
              end-placeholder="Date fin"
              style="width: 280px"
              @change="handleSearch"
            />
          </el-form-item>

          <el-form-item>
            <el-button type="default" @click="handleReset">
              <el-icon><RefreshLeft /></el-icon>
              Réinitialiser
            </el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Table Card -->
      <el-card class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">
              {{ pagination.total }} règlement(s) trouvé(s)
            </span>
            <div class="card-actions">
              <el-button :icon="Download" size="small" @click="handleExport">
                Exporter
              </el-button>
              <el-button :icon="Printer" size="small" @click="handlePrint">
                Imprimer
              </el-button>
              <el-button :icon="Refresh" size="small" circle @click="handleRefresh" />
            </div>
          </div>
        </template>

        <el-table
          v-loading="loading"
          :data="reglements"
          stripe
          style="width: 100%"
          @sort-change="handleSortChange"
        >
          <el-table-column prop="date_reglement" label="Date" width="120" sortable="custom">
            <template #default="{ row }">
              {{ formatDate(row.date_reglement) }}
            </template>
          </el-table-column>

          <el-table-column prop="facture" label="N° Facture" width="140">
            <template #default="{ row }">
              <el-link type="primary" @click="handleViewFacture(row.facture)">
                <strong>{{ row.facture.numero }}</strong>
              </el-link>
            </template>
          </el-table-column>

          <el-table-column prop="fournisseur" label="Fournisseur" min-width="200">
            <template #default="{ row }">
              <div class="fournisseur-cell">
                <div class="fournisseur-nom">{{ row.fournisseur.nom }}</div>
                <!-- <div class="fournisseur-code">{{ row.fournisseur.code }}</div> -->
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="mode_paiement" label="Mode" width="140">
            <template #default="{ row }">
              <el-tag :type="getModeTagType(row.mode_paiement)" size="small">
                {{ getModeLabel(row.mode_paiement) }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column prop="reference" label="Référence" width="140">
            <template #default="{ row }">
              <span v-if="row.reference">{{ row.reference }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="beneficiaire" label="Bénéficiaire" width="160">
            <template #default="{ row }">
              <span v-if="row.beneficiaire">{{ row.beneficiaire }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="compte_bancaire" label="Compte Bancaire" width="180">
            <template #default="{ row }">
              <div v-if="row.compte_bancaire" class="compte-cell">
                <el-icon><CreditCard /></el-icon>
                <span>{{ row.compte_bancaire.banque }}</span>
              </div>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column prop="montant" label="Montant" width="140" align="right" sortable="custom">
            <template #default="{ row }">
              <strong class="montant-reglement">{{ formatMontant(row.montant) }}</strong>
            </template>
          </el-table-column>

          <el-table-column prop="user" label="Saisi par" width="140">
            <template #default="{ row }">
              <div v-if="row.user" class="user-cell">
                <el-icon><User /></el-icon>
                <span>{{ row.user.name }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="180" fixed="right" align="center">
            <template #default="{ row }">
              <el-button-group>
                <el-button :icon="View" size="small" @click="handleView(row)">
                  Détails
                </el-button>
                <el-dropdown @command="(cmd) => handleMoreActions(cmd, row)">
                  <el-button :icon="More" size="small" />
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="recu" :icon="Printer">
                        Imprimer le reçu
                      </el-dropdown-item>
                      <el-dropdown-item command="mandat" :icon="DocumentCopy">
                        Mandat de paiement
                      </el-dropdown-item>
                      <el-dropdown-item command="imputation" :icon="Notebook">
                        Fiche d'imputation
                      </el-dropdown-item>
                      <el-dropdown-item divided command="facture" :icon="Document">
                        Voir la facture
                      </el-dropdown-item>
                      <el-dropdown-item divided command="delete" :icon="Delete">
                        <span style="color: #f56c6c">Supprimer</span>
                      </el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </el-button-group>
            </template>
          </el-table-column>
        </el-table>

        <!-- Pagination -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="pagination.current_page"
            v-model:page-size="pagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>

      <!-- Detail Modal -->
      <el-dialog
        v-model="detailDialogVisible"
        title="Détails du Règlement"
        width="600px"
        :close-on-click-modal="false"
      >
        <div v-if="selectedReglement" class="detail-modal-content">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Date de Règlement">
              <strong>{{ formatDate(selectedReglement.date_reglement) }}</strong>
            </el-descriptions-item>

            <el-descriptions-item label="Montant">
              <el-tag type="success" size="large" style="font-size: 16px; padding: 8px 16px;">
                <strong>{{ formatMontant(selectedReglement.montant) }}</strong>
              </el-tag>
            </el-descriptions-item>

            <el-descriptions-item label="Mode de Paiement">
              <el-tag :type="getModeTagType(selectedReglement.mode_paiement)">
                {{ getModeLabel(selectedReglement.mode_paiement) }}
              </el-tag>
            </el-descriptions-item>

            <el-descriptions-item label="N° Facture">
              <el-link type="primary" @click="handleViewFacture(selectedReglement.facture)">
                <strong>{{ selectedReglement.facture.numero }}</strong>
              </el-link>
            </el-descriptions-item>

            <el-descriptions-item label="Fournisseur">
              <div>
                <div><strong>{{ selectedReglement.fournisseur.nom }}</strong></div>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                  {{ selectedReglement.fournisseur.code }}
                </div>
              </div>
            </el-descriptions-item>

            <el-descriptions-item label="Référence">
              {{ selectedReglement.reference || '-' }}
            </el-descriptions-item>

            <el-descriptions-item label="Bénéficiaire">
              {{ selectedReglement.beneficiaire || '-' }}
            </el-descriptions-item>

            <el-descriptions-item label="Compte Bancaire" v-if="selectedReglement.compte_bancaire">
              <div class="compte-bancaire-info">
                <el-icon><CreditCard /></el-icon>
                <div>
                  <div><strong>{{ selectedReglement.compte_bancaire.banque }}</strong></div>
                  <div style="font-size: 12px; color: #6b7280;">
                    {{ selectedReglement.compte_bancaire.numero }}
                  </div>
                </div>
              </div>
            </el-descriptions-item>

            <el-descriptions-item label="Saisi par" v-if="selectedReglement.user">
              <div class="user-info">
                <el-icon><User /></el-icon>
                <span>{{ selectedReglement.user.name }}</span>
              </div>
            </el-descriptions-item>
          </el-descriptions>
        </div>

        <template #footer>
          <div class="dialog-footer">
            <el-button @click="detailDialogVisible = false">Fermer</el-button>
            <el-button type="success" :icon="Printer" @click="handlePrintReceipt">
              Reçu
            </el-button>
            <el-button type="primary" :icon="DocumentCopy" @click="handlePrintMandat">
              Mandat de paiement
            </el-button>
            <el-button type="warning" :icon="Notebook" @click="handlePrintImputation">
              Fiche d'imputation
            </el-button>
            <el-button type="info" :icon="Document" @click="handleViewFactureFromModal">
              Voir la facture
            </el-button>
          </div>
        </template>
      </el-dialog>

      <!-- Modal Sélection Facture -->
      <el-dialog
        v-model="selectFactureDialogVisible"
        title="Sélectionner une Facture"
        width="600px"
        :close-on-click-modal="false"
      >
        <el-form label-position="top">
          <el-form-item label="Facture à régler">
            <el-select
              v-model="selectedFactureId"
              placeholder="Sélectionner une facture"
              filterable
              style="width: 100%"
              size="large"
            >
              <el-option
                v-for="facture in facturesImpayees"
                :key="facture.id"
                :label="`${facture.numero} - ${facture.libelle}`"
                :value="facture.id"
              >
                <span>{{ facture.numero }} - {{ facture.libelle }}</span>
              </el-option>
            </el-select>
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="selectFactureDialogVisible = false">Annuler</el-button>
          <el-button type="primary" @click="confirmSelectFacture" :disabled="!selectedFactureId">
            <el-icon><Money /></el-icon>
            Procéder au règlement
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
import {
  Plus,
  Search,
  RefreshLeft,
  Download,
  Printer,
  Refresh,
  View,
  More,
  Delete,
  Money,
  Calendar,
  DocumentChecked,
  TrendCharts,
  CreditCard,
  User,
  Document,
  DocumentCopy,
  Notebook
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  reglements: {
    type: Array,
    default: () => []
  },
  fournisseurs: {
    type: Array,
    default: () => []
  },
  facturesImpayees: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total_reglements: 0,
      reglements_mois: 0,
      nombre_reglements: 0,
      montant_moyen: 0
    })
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      per_page: 20,
      total: 0
    })
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State
const loading = ref(false);
const detailDialogVisible = ref(false);
const selectedReglement = ref(null);
const selectFactureDialogVisible = ref(false);
const selectedFactureId = ref(null);
const filters = reactive({
  search: '',
  fournisseur_id: null,
  mode_paiement: '',
  date_range: null
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Règlements Fournisseurs', path: '/reglements-fournisseurs' }
];

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getModeTagType = (mode) => {
  const types = {
    especes: 'success',
    cheque: 'primary',
    virement: 'info',
    carte: 'warning',
    mobile_money: 'success'
  };
  return types[mode] || 'info';
};

const getModeLabel = (mode) => {
  const labels = {
    especes: 'Espèces',
    cheque: 'Chèque',
    virement: 'Virement',
    carte: 'Carte',
    mobile_money: 'Mobile Money'
  };
  return labels[mode] || mode;
};

const handleSearch = () => {
  const params = new URLSearchParams();

  if (filters.search) {
    params.append('search', filters.search);
  }
  if (filters.fournisseur_id) {
    params.append('fournisseur_id', filters.fournisseur_id);
  }
  if (filters.mode_paiement) {
    params.append('mode_paiement', filters.mode_paiement);
  }
  if (filters.date_range && filters.date_range.length === 2) {
    params.append('date_debut', filters.date_range[0].toISOString().split('T')[0]);
    params.append('date_fin', filters.date_range[1].toISOString().split('T')[0]);
  }

  router.visit(`/reglements-fournisseurs?${params.toString()}`, {
    preserveState: true,
    only: ['reglements', 'stats', 'pagination']
  });
};

const handleReset = () => {
  filters.search = '';
  filters.fournisseur_id = null;
  filters.mode_paiement = '';
  filters.date_range = null;
  handleSearch();
};

const handleRefresh = () => {
  router.reload({ only: ['reglements', 'stats', 'pagination'] });
};

const handleSortChange = ({ prop, order }) => {
  const params = new URLSearchParams(window.location.search);
  params.set('sort', prop);
  params.set('order', order === 'ascending' ? 'asc' : 'desc');

  router.visit(`/reglements-fournisseurs?${params.toString()}`, {
    preserveState: true,
    only: ['reglements']
  });
};

const handleSizeChange = (size) => {
  const params = new URLSearchParams(window.location.search);
  params.set('per_page', size);
  params.set('page', '1');

  router.visit(`/reglements-fournisseurs?${params.toString()}`, {
    preserveState: true,
    only: ['reglements', 'pagination']
  });
};

const handlePageChange = (page) => {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);

  router.visit(`/reglements-fournisseurs?${params.toString()}`, {
    preserveState: true,
    only: ['reglements', 'pagination']
  });
};

const handleNewPayment = () => {
  selectedFactureId.value = null;
  selectFactureDialogVisible.value = true;
};

const confirmSelectFacture = () => {
  if (selectedFactureId.value) {
    selectFactureDialogVisible.value = false;
    router.visit(`/factures-fournisseurs/${selectedFactureId.value}/regler`);
  }
};

const handleView = (reglement) => {
  selectedReglement.value = reglement;
  detailDialogVisible.value = true;
};

const handleViewFactureFromModal = () => {
  if (selectedReglement.value) {
    detailDialogVisible.value = false;
    router.visit(`/factures-fournisseurs/${selectedReglement.value.facture.id}`);
  }
};

const handlePrintReceipt = () => {
  if (selectedReglement.value) {
    window.open(`/reglements-fournisseurs/${selectedReglement.value.id}/recu`, '_blank');
  }
};

const handlePrintMandat = () => {
  if (selectedReglement.value) {
    window.open(`/reglements-fournisseurs/${selectedReglement.value.id}/mandat`, '_blank');
  }
};

const handlePrintImputation = () => {
  if (selectedReglement.value) {
    window.open(`/reglements-fournisseurs/${selectedReglement.value.id}/imputation`, '_blank');
  }
};

const handleViewFacture = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}`);
};

const handleMoreActions = async (command, reglement) => {
  switch (command) {
    case 'recu':
      window.open(`/reglements-fournisseurs/${reglement.id}/recu`, '_blank');
      break;
    case 'mandat':
      window.open(`/reglements-fournisseurs/${reglement.id}/mandat`, '_blank');
      break;
    case 'imputation':
      window.open(`/reglements-fournisseurs/${reglement.id}/imputation`, '_blank');
      break;
    case 'facture':
      router.visit(`/factures-fournisseurs/${reglement.facture.id}`);
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir annuler ce règlement ? Le montant sera réintégré sur la facture.',
        'Confirmation',
        {
          confirmButtonText: 'Annuler le règlement',
          cancelButtonText: 'Non, garder',
          type: 'warning'
        }
      ).then(async () => {
        try {
          const response = await fetch(`/api/reglements-fournisseurs/${reglement.id}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
          });

          const data = await response.json();

          if (data.success) {
            ElMessage.success(data.message || 'Règlement annulé avec succès');
            handleRefresh();
          } else {
            ElMessage.error(data.message || 'Erreur lors de l\'annulation');
          }
        } catch (error) {
          console.error('Error:', error);
          ElMessage.error('Erreur lors de l\'annulation du règlement');
        }
      });
      break;
  }
};

const handleExport = () => {
  ElMessage.info('Export en cours de développement...');
};

const handlePrint = () => {
  ElMessage.info('Impression en cours de développement...');
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
  width: 56px;
  height: 56px;
  border-radius: 12px;
}

.stat-total .stat-icon {
  background-color: #dcfce7;
  color: #16a34a;
}

.stat-today .stat-icon {
  background-color: #dbeafe;
  color: #2563eb;
}

.stat-count .stat-icon {
  background-color: #fef3c7;
  color: #d97706;
}

.stat-average .stat-icon {
  background-color: #f3e8ff;
  color: #9333ea;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
}

/* Filters */
.filter-card {
  border-radius: 8px;
}

.filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

/* Table */
.table-card {
  border-radius: 8px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.card-actions {
  display: flex;
  gap: 8px;
}

.fournisseur-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.fournisseur-nom {
  font-size: 14px;
  color: #1f2937;
  font-weight: 500;
}

.fournisseur-code {
  font-size: 12px;
  color: #9ca3af;
}

.compte-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
}

.montant-reglement {
  color: #059669;
  font-size: 14px;
}

.text-muted {
  color: #d1d5db;
}

.pagination-container {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

:deep(.el-table) {
  font-size: 14px;
}

:deep(.el-table th) {
  background-color: #f9fafb;
  font-weight: 600;
  color: #374151;
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

/* Detail Modal */
.detail-modal-content {
  padding: 8px 0;
}

.compte-bancaire-info {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 6px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

:deep(.el-dialog__header) {
  border-bottom: 1px solid #e5e7eb;
  padding: 16px 20px;
}

:deep(.el-dialog__body) {
  padding: 20px;
}

:deep(.el-dialog__footer) {
  border-top: 1px solid #e5e7eb;
  padding: 12px 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  width: 180px;
}

/* Facture Selection Modal */
.facture-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding: 4px 0;
}

.facture-option-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.facture-numero {
  font-weight: 600;
  color: #1f2937;
}

.facture-libelle {
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 300px;
}

.facture-option-details {
  display: flex;
  align-items: center;
  gap: 8px;
}

.facture-montant {
  font-weight: 600;
  color: #059669;
}
</style>
