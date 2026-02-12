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
        <el-form :inline="true" class="filter-form">
          <el-form-item>
            <el-input
              v-model="searchQuery"
              placeholder="Rechercher (r&eacute;f&eacute;rence, institution, client...)"
              :prefix-icon="Search"
              clearable
              style="width: 350px"
            />
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="filterClientId"
              placeholder="Tous les clients"
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
        </el-form>
      </el-card>

      <!-- Table Card -->
      <el-card class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">
              {{ filteredReglements.length }} r&egrave;glement(s)
            </span>
          </div>
        </template>

        <el-table
          :data="filteredReglements"
          stripe
          style="width: 100%"
          :default-sort="{ prop: 'date_reglement', order: 'descending' }"
        >
          <el-table-column label="Actions" width="180" fixed="left" align="center">
            <template #default="{ row }">
              <el-button size="small" type="primary" @click="handleView(row)">
                D&eacute;tails
              </el-button>
              <el-popconfirm
                title="Supprimer ce r&egrave;glement ?"
                confirm-button-text="Oui"
                cancel-button-text="Non"
                @confirm="handleDelete(row)"
              >
                <template #reference>
                  <el-button size="small" type="danger" :icon="Delete" />
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>

          <el-table-column prop="date_reglement" label="Date" width="120" sortable>
            <template #default="{ row }">
              {{ formatDate(row.date_reglement) }}
            </template>
          </el-table-column>

          <el-table-column label="N&deg; Facture" width="140">
            <template #default="{ row }">
              <el-link type="primary" @click="handleViewFacture(row.facture)">
                <strong>{{ row.facture?.reference || '-' }}</strong>
              </el-link>
            </template>
          </el-table-column>

          <el-table-column label="Client" min-width="180">
            <template #default="{ row }">
              <strong>{{ row.client?.nom || '-' }}</strong>
            </template>
          </el-table-column>

          <el-table-column label="Institution" width="180">
            <template #default="{ row }">
              <span v-if="row.institution">{{ row.institution }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column label="R&eacute;f. Ch&egrave;que" width="140">
            <template #default="{ row }">
              <span v-if="row.reference_cheque">{{ row.reference_cheque }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column label="Banque D&eacute;p&ocirc;t" width="160">
            <template #default="{ row }">
              <span v-if="row.banque_depot">{{ row.banque_depot.nom }}</span>
              <span v-else class="text-muted">-</span>
            </template>
          </el-table-column>

          <el-table-column label="Montant" width="140" align="right" sortable sort-by="montant">
            <template #default="{ row }">
              <strong class="montant-reglement">{{ formatMontant(row.montant) }}</strong>
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
              {{ selectedReglement.compte_bancaire?.numero_compte || '-' }}
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
  DocumentChecked, TrendCharts
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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

const searchQuery = ref('');
const filterClientId = ref(null);
const detailDialogVisible = ref(false);
const selectedReglement = ref(null);
const showNewReglementModal = ref(false);
const selectedFactureId = ref(null);

const filteredReglements = computed(() => {
  let result = props.reglements;

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(r =>
      r.facture?.reference?.toLowerCase().includes(q) ||
      r.client?.nom?.toLowerCase().includes(q) ||
      r.institution?.toLowerCase().includes(q) ||
      r.reference_cheque?.toLowerCase().includes(q) ||
      r.numero_ligne?.toLowerCase().includes(q)
    );
  }

  if (filterClientId.value) {
    result = result.filter(r => r.client_id === filterClientId.value);
  }

  return result;
});

const handleView = (reglement) => {
  selectedReglement.value = reglement;
  detailDialogVisible.value = true;
};

const handleViewFacture = (facture) => {
  if (facture?.id) {
    router.visit(`/factures-clients/${facture.id}`);
  }
};

const handleDelete = async (reglement) => {
  try {
    const response = await fetch(`/api/reglements-clients/${reglement.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      }
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
    style: 'currency',
    currency: 'XOF',
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
.detail-modal-content { padding: 8px 0; }
:deep(.el-table th) { background-color: #f9fafb; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
:deep(.el-descriptions__label) { font-weight: 600; width: 180px; }
</style>
