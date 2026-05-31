<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">R&egrave;glements Clients</h1>
          <p class="page-subtitle">Historique complet des paiements re&ccedil;us</p>
        </div>
        <el-button type="primary" size="large" @click="showNewReglementModal = true">
          <el-icon><Plus /></el-icon>
          Nouveau R&egrave;glement
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
                <div class="stat-label">Total R&egrave;glements</div>
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
                <div class="stat-label">Nombre de R&egrave;glements</div>
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
              placeholder="N° ligne, référence, institution..."
              :prefix-icon="Search"
              clearable
              style="width: 250px"
            />
          </el-form-item>

          <el-form-item label="Client">
            <el-select
              v-model="filters.client_id"
              placeholder="Tous"
              clearable
              filterable
              style="width: 200px"
            >
              <el-option
                v-for="client in clients"
                :key="client.id"
                :label="client.nom"
                :value="client.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Type">
            <el-select
              v-model="filters.type_reglement"
              placeholder="Tous"
              clearable
              style="width: 160px"
            >
              <el-option label="Règlement" value="reglement" />
              <el-option label="Perte" value="perte" />
            </el-select>
          </el-form-item>

          <el-form-item label="Période">
            <el-date-picker
              v-model="filters.date_range"
              type="daterange"
              range-separator="à"
              start-placeholder="Date début"
              end-placeholder="Date fin"
              format="DD/MM/YYYY"
              style="width: 280px"
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
              {{ groupedReglements.length }} facture(s) &mdash; {{ filteredReglements.length }} r&egrave;glement(s)
            </span>
          </div>
        </template>

        <el-table
          :data="groupedReglements"
          stripe
          border
          style="width: 100%"
          row-key="key"
        >
          <el-table-column type="expand" width="50">
            <template #default="{ row }">
              <div class="expand-reglements">
                <el-table :data="row.reglements" border size="small" class="inner-reglements-table">
                  <el-table-column label="Date" width="110">
                    <template #default="{ row: reg }">{{ formatDate(reg.date_reglement) }}</template>
                  </el-table-column>
                  <el-table-column label="Type" width="120">
                    <template #default="{ row: reg }">
                      <el-tag
                        :type="reg.type_reglement_couleur === 'danger' ? 'danger' : reg.type_reglement_couleur === 'warning' ? 'warning' : reg.type_reglement_couleur === 'success' ? 'success' : 'primary'"
                        size="small"
                      >
                        {{ reg.type_reglement_libelle || 'Règlement' }}
                      </el-tag>
                    </template>
                  </el-table-column>
                  <el-table-column label="N° Ligne" width="110">
                    <template #default="{ row: reg }">
                      <span v-if="reg.numero_ligne">{{ reg.numero_ligne }}</span>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Institution" min-width="160">
                    <template #default="{ row: reg }">
                      <span v-if="reg.institution">{{ reg.institution }}</span>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="R&eacute;f. Ch&egrave;que" width="140">
                    <template #default="{ row: reg }">
                      <span v-if="reg.reference_cheque">{{ reg.reference_cheque }}</span>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Banque D&eacute;p&ocirc;t" width="160">
                    <template #default="{ row: reg }">
                      <span v-if="reg.banque_depot">{{ reg.banque_depot.nom }}</span>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Montant" width="140" align="right">
                    <template #default="{ row: reg }">
                      <strong class="montant-reglement">{{ formatMontant(reg.montant) }}</strong>
                    </template>
                  </el-table-column>
                  <el-table-column label="Actions" width="170" align="center" fixed="right">
                    <template #default="{ row: reg }">
                      <el-button-group>
                        <el-button :icon="View" size="small" type="primary" @click="handleView(reg)">
                          D&eacute;tails
                        </el-button>
                        <el-dropdown @command="(cmd) => handleMoreActions(cmd, reg)">
                          <el-button :icon="More" size="small" />
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
                      </el-button-group>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="N&deg; Facture Client" width="170">
            <template #default="{ row }">
              <span class="nowrap-cell">
                <el-link type="primary" @click="handleViewFacture(row.facture)">
                  <strong>{{ row.facture?.reference || '-' }}</strong>
                </el-link>
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Client" min-width="200">
            <template #default="{ row }">
              <strong>{{ row.client?.nom || '-' }}</strong>
            </template>
          </el-table-column>

          <el-table-column label="Date facture" width="120">
            <template #default="{ row }">{{ formatDate(row.facture?.date_facture) }}</template>
          </el-table-column>

          <el-table-column label="Montant facture" width="160" align="right">
            <template #default="{ row }">
              <span class="nowrap-cell">{{ formatMontant(row.facture?.montant) }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Total r&eacute;gl&eacute;" width="150" align="right">
            <template #default="{ row }">
              <span class="nowrap-cell"><strong class="montant-reglement">{{ formatMontant(row.total_montant_regle) }}</strong></span>
            </template>
          </el-table-column>

          <el-table-column label="Reste &agrave; payer" width="150" align="right">
            <template #default="{ row }">
              <span class="nowrap-cell" :class="(row.facture?.reste_a_payer || 0) > 0 ? 'reste-due' : 'reste-paid'">
                <strong>{{ formatMontant(row.facture?.reste_a_payer || 0) }}</strong>
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Nb r&egrave;glements" width="130" align="center">
            <template #default="{ row }">
              <el-tag size="small" type="info">{{ row.count }}</el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="120" align="center" fixed="right">
            <template #default="{ row }">
              <el-button :icon="Document" size="small" plain @click="handleViewFacture(row.facture)">
                Facture
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- Detail Modal -->
      <el-dialog
        v-model="detailDialogVisible"
        title="D&eacute;tails du R&egrave;glement"
        width="600px"
        :close-on-click-modal="false"
      >
        <div v-if="selectedReglement" class="detail-modal-content">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Date">
              <strong>{{ formatDate(selectedReglement.date_reglement) }}</strong>
            </el-descriptions-item>
            <el-descriptions-item label="Montant">
              <el-tag type="success" size="large" style="font-size: 16px; padding: 8px 16px;">
                <strong>{{ formatMontant(selectedReglement.montant) }}</strong>
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="Facture">
              <el-link type="primary" @click="handleViewFacture(selectedReglement.facture); detailDialogVisible = false">
                <strong>{{ selectedReglement.facture?.reference }}</strong>
              </el-link>
            </el-descriptions-item>
            <el-descriptions-item label="Client">
              <strong>{{ selectedReglement.client?.nom }}</strong>
            </el-descriptions-item>
            <el-descriptions-item label="N&deg; Ligne">
              {{ selectedReglement.numero_ligne || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="Institution">
              {{ selectedReglement.institution || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="R&eacute;f. Ch&egrave;que">
              {{ selectedReglement.reference_cheque || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="Banque de d&eacute;p&ocirc;t">
              {{ selectedReglement.banque_depot?.nom || '-' }}
            </el-descriptions-item>
            <el-descriptions-item label="R&eacute;f. Bordereau">
              {{ selectedReglement.approvisionnement?.reference_bordereau || '-' }}
            </el-descriptions-item>
            <el-descriptions-item v-if="selectedReglement.observations" label="Observations">
              {{ selectedReglement.observations }}
            </el-descriptions-item>
          </el-descriptions>
        </div>
        <template #footer>
          <el-button @click="detailDialogVisible = false">Fermer</el-button>
        </template>
      </el-dialog>

      <!-- Edit Règlement Modal -->
      <el-dialog
        v-model="editDialogVisible"
        title="Modifier le R&egrave;glement"
        width="600px"
        :close-on-click-modal="false"
      >
        <el-form v-if="editForm" label-position="top" size="large">
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Date">
                <el-date-picker
                  v-model="editForm.date_reglement"
                  type="date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Montant">
                <el-input-number
                  v-model="editForm.montant"
                  :min="1"
                  :precision="0"
                  controls-position="right"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="N&deg; Ligne">
                <el-input v-model="editForm.numero_ligne" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Institution">
                <el-input v-model="editForm.institution" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="R&eacute;f. Ch&egrave;que">
                <el-input v-model="editForm.reference_cheque" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-form-item label="Observations">
            <el-input v-model="editForm.observations" type="textarea" :rows="3" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="editDialogVisible = false">Annuler</el-button>
          <el-button type="primary" :loading="editLoading" @click="handleEditSubmit">
            Enregistrer
          </el-button>
        </template>
      </el-dialog>

      <!-- Nouveau Règlement Modal (select facture) -->
      <el-dialog
        v-model="showNewReglementModal"
        title="Nouveau R&egrave;glement"
        width="500px"
        :close-on-click-modal="false"
      >
        <el-form label-position="top">
          <el-form-item label="Facture concern&eacute;e">
            <el-select
              v-model="selectedFactureId"
              filterable
              placeholder="S&eacute;lectionner une facture"
              style="width: 100%"
              size="large"
            >
              <el-option
                v-for="f in facturesImpayees"
                :key="f.id"
                :label="f.reference + ' - ' + f.client_nom"
                :value="f.id"
              >
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span><strong>{{ f.reference }}</strong> &mdash; {{ f.client_nom }}</span>
                  <span style="color: #dc2626; font-size: 12px; margin-left: 12px;">{{ formatMontant(f.reste_a_payer) }}</span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="showNewReglementModal = false">Annuler</el-button>
          <el-button
            type="primary"
            :disabled="!selectedFactureId"
            @click="goToReglement"
          >
            Continuer
          </el-button>
        </template>
      </el-dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  Plus, Search, Delete, Money, Calendar,
  DocumentChecked, TrendCharts, Edit, View, More,
  Document, RefreshLeft
} from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchApi } from '@/Composables/useFetch';

const props = defineProps({
  reglements: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  facturesImpayees: { type: Array, default: () => [] },
  stats: {
    type: Object,
    default: () => ({
      total_reglements: 0,
      reglements_mois: 0,
      nombre_reglements: 0,
      montant_moyen: 0
    })
  },
  user: { type: Object, default: () => null }
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'R\u00e8glements Clients', path: '/reglements-clients' }
];

const filters = ref({
  search: '',
  client_id: null,
  type_reglement: '',
  date_range: null,
});
const detailDialogVisible = ref(false);
const selectedReglement = ref(null);
const showNewReglementModal = ref(false);
const selectedFactureId = ref(null);
const editDialogVisible = ref(false);
const editForm = ref(null);
const editLoading = ref(false);
const editingReglementId = ref(null);

const filteredReglements = computed(() => {
  let result = props.reglements;

  if (filters.value.search) {
    const q = filters.value.search.toLowerCase();
    result = result.filter(r =>
      r.facture?.reference?.toLowerCase().includes(q) ||
      r.client?.nom?.toLowerCase().includes(q) ||
      r.institution?.toLowerCase().includes(q) ||
      r.reference_cheque?.toLowerCase().includes(q) ||
      r.numero_ligne?.toLowerCase().includes(q)
    );
  }

  if (filters.value.client_id) {
    result = result.filter(r => r.client_id === filters.value.client_id);
  }

  if (filters.value.type_reglement) {
    result = result.filter(r => r.type_reglement === filters.value.type_reglement);
  }

  if (filters.value.date_range && filters.value.date_range.length === 2) {
    const [start, end] = filters.value.date_range;
    const startDate = new Date(start).setHours(0, 0, 0, 0);
    const endDate = new Date(end).setHours(23, 59, 59, 999);
    result = result.filter(r => {
      const d = new Date(r.date_reglement).getTime();
      return d >= startDate && d <= endDate;
    });
  }

  return result;
});

// Regrouper les règlements par facture (pour expandable rows)
const groupedReglements = computed(() => {
  const map = new Map();
  for (const r of filteredReglements.value || []) {
    const factureId = r.facture?.id ?? `${r.facture?.reference || 'unknown'}-${r.client?.id || 0}`;
    if (!map.has(factureId)) {
      map.set(factureId, {
        key: `f-${factureId}`,
        facture: r.facture || { reference: '-' },
        client: r.client || { nom: '-' },
        reglements: [],
        total_montant_regle: 0,
        count: 0,
      });
    }
    const grp = map.get(factureId);
    grp.reglements.push(r);
    grp.total_montant_regle += parseFloat(r.montant) || 0;
    grp.count += 1;
  }
  return Array.from(map.values());
});

const handleReset = () => {
  filters.value = { search: '', client_id: null, type_reglement: '', date_range: null };
};

const handleView = (reglement) => {
  selectedReglement.value = reglement;
  detailDialogVisible.value = true;
};

const handleViewFacture = (facture) => {
  if (facture?.id) {
    router.visit(`/factures-clients/${facture.id}`);
  }
};

const handleMoreActions = (command, reglement) => {
  switch (command) {
    case 'edit':
      handleEdit(reglement);
      break;
    case 'facture':
      handleViewFacture(reglement.facture);
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer ce règlement ?',
        'Confirmation',
        { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
      ).then(() => handleDelete(reglement)).catch(() => {});
      break;
  }
};

const handleEdit = (reglement) => {
  editingReglementId.value = reglement.id;
  editForm.value = {
    date_reglement: reglement.date_reglement,
    montant: reglement.montant,
    numero_ligne: reglement.numero_ligne || '',
    institution: reglement.institution || '',
    reference_cheque: reglement.reference_cheque || '',
    banque_depot_id: reglement.banque_depot?.id || null,
    approvisionnement_id: reglement.approvisionnement?.id || null,
    observations: reglement.observations || '',
  };
  editDialogVisible.value = true;
};

const handleEditSubmit = async () => {
  editLoading.value = true;
  try {
    const response = await fetchApi(`/api/reglements-clients/${editingReglementId.value}`, {
      method: 'PUT',
      body: editForm.value,
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Règlement modifié avec succès');
      editDialogVisible.value = false;
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur lors de la modification');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  } finally {
    editLoading.value = false;
  }
};

const handleDelete = async (reglement) => {
  try {
    const response = await fetchApi(`/api/reglements-clients/${reglement.id}`, {
      method: 'DELETE',
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('R\u00e8glement supprim\u00e9');
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const goToReglement = () => {
  if (selectedFactureId.value) {
    showNewReglementModal.value = false;
    router.visit(`/factures-clients/${selectedFactureId.value}/regler`);
  }
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits: 0,
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<style scoped>
.page-container { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title { font-size: 24px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #6b7280; margin: 0; }
.stats-row { margin-bottom: 4px; }
.stat-card { border-radius: 8px; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-content { display: flex; align-items: center; gap: 16px; }
.stat-icon { display: flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 12px; }
.stat-total .stat-icon { background-color: #dcfce7; color: #16a34a; }
.stat-today .stat-icon { background-color: #dbeafe; color: #2563eb; }
.stat-count .stat-icon { background-color: #fef3c7; color: #d97706; }
.stat-average .stat-icon { background-color: #f3e8ff; color: #9333ea; }
.stat-info { flex: 1; }
.stat-value { font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
.stat-label { font-size: 13px; color: #6b7280; }
.filter-card { border-radius: 8px; }
.filter-form { display: flex; flex-wrap: wrap; gap: 8px; }
.table-card { border-radius: 8px; }
.table-card :deep(.el-card__body) { padding: 0; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 16px; font-weight: 600; color: #374151; }
.montant-reglement { color: #059669; font-size: 14px; }
.text-muted { color: #d1d5db; }
.reste-due { color: #dc2626; }
.reste-paid { color: #6b7280; }
.expand-reglements { padding: 12px 24px; background-color: #f9fafb; }
.inner-reglements-table { background: #ffffff; }
.inner-reglements-table :deep(.el-table th) { background-color: #eef2ff; font-size: 12px; font-weight: 600; }
.detail-modal-content { padding: 8px 0; }
:deep(.el-table th) { background-color: #f9fafb; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
:deep(.el-descriptions__label) { font-weight: 600; width: 180px; }
.nowrap-cell { white-space: nowrap; }
</style>
