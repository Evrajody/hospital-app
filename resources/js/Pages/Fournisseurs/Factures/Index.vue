<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Factures Fournisseurs</h1>
          <p class="page-subtitle">Gestion et suivi des factures d'achat</p>
        </div>
        <el-button v-if="can('factures-fournisseurs.creer')" type="primary" size="large" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          Nouvelle Facture
        </el-button>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Document /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.total }}</div>
                <div class="stat-label">Total Factures</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-unpaid">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><WarningFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_impaye) }}</div>
                <div class="stat-label">Impayées</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-partial">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><Clock /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_partiel) }}</div>
                <div class="stat-label">Partiellement Payées</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-paid">
            <div class="stat-content">
              <div class="stat-icon">
                <el-icon :size="32"><SuccessFilled /></el-icon>
              </div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.montant_paye) }}</div>
                <div class="stat-label">Payées</div>
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
              placeholder="N° Pièce, référence..."
              :prefix-icon="Search"
              clearable
              style="width: 250px"
              @input="debouncedSearch"
              @clear="handleSearch"
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

          <el-form-item label="Statut Paiement">
            <el-select
              v-model="filters.statut_paiement"
              placeholder="Tous"
              clearable
              style="width: 150px"
              @change="handleSearch"
            >
              <el-option label="Impayée" value="impayee" />
              <el-option label="Partielle" value="partielle" />
              <el-option label="Payée" value="payee" />
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
              {{ pagination.total }} facture(s) trouvée(s)
            </span>
            <div class="card-actions">
              <el-button :icon="Download" size="small" @click="handleExport">
                Exporter
              </el-button>
              <el-button :icon="Refresh" size="small" circle @click="handleRefresh" />
            </div>
          </div>
        </template>

        <el-table
          v-loading="loading"
          :data="factures"
          stripe
          border
          style="width: 100%"
          @sort-change="handleSortChange"
        >
          <el-table-column label="Actions" width="100" fixed="left" align="center" resizable>
            <template #default="{ row }">
              <el-dropdown trigger="click" @command="(cmd) => handleMoreActions(cmd, row)">
                <el-button size="small" type="primary">
                  Actions <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="view" :icon="View">
                      Voir
                    </el-dropdown-item>
                    <el-dropdown-item
                      v-if="row.statut_paiement !== 'payee' && can('reglements-fournisseurs.creer')"
                      command="pay"
                      :icon="Money"
                    >
                      Régler
                    </el-dropdown-item>
                    <el-dropdown-item v-if="can('factures-fournisseurs.modifier')" command="edit" :icon="Edit">
                      Modifier
                    </el-dropdown-item>
                    <el-dropdown-item command="etat_reglement" :icon="Document">
                      État de Règlement
                    </el-dropdown-item>
                    <el-dropdown-item
                      v-if="row.imputation_id || row.compte_id || row.imputations_count > 0"
                      command="imputation"
                      :icon="CopyDocument"
                    >
                      Imputation Comptable
                    </el-dropdown-item>
                    <el-dropdown-item v-if="can('factures-fournisseurs.supprimer')" divided command="delete" :icon="Delete">
                      <span style="color: #f56c6c">Supprimer</span>
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>

          <el-table-column prop="numero" label="N° Pièce" width="140" sortable="custom" fixed="left" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell">
                <el-link type="primary" @click="handleView(row)">
                  <strong>{{ row.numero }}</strong>
                </el-link>
              </span>
            </template>
          </el-table-column>

          <el-table-column prop="date_facture" label="Date" width="110" sortable="custom" fixed="left" resizable>
            <template #default="{ row }">
              {{ formatDate(row.date_facture) }}
            </template>
          </el-table-column>

          <el-table-column prop="fournisseur" label="Fournisseur" min-width="200" fixed="left" sortable="custom" resizable>
            <template #default="{ row }">
              <div class="fournisseur-cell">
                <div class="fournisseur-nom">{{ row.fournisseur.nom }}</div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="date_facture_bc" label="Date Fact/B.C." width="120" sortable="custom" resizable>
            <template #default="{ row }">
              {{ row.date_facture_bc ? formatDate(row.date_facture_bc) : '-' }}
            </template>
          </el-table-column>

          <el-table-column prop="reference" label="Réf. Fact/B.C." width="140" sortable="custom" resizable />

          <el-table-column prop="libelle" label="Libellé" min-width="180" sortable="custom" resizable>
            <template #default="{ row }">
              {{ row.libelle || '-' }}
            </template>
          </el-table-column>

          <el-table-column prop="montant_ttc" label="Montant TTC" width="140" align="right" sortable="custom" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell"><strong class="montant-ttc">{{ formatMontant(row.montant_ttc) }}</strong></span>
            </template>
          </el-table-column>

          <el-table-column prop="montant_net" label="Net à payer" width="140" align="right" sortable="custom" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell"><strong>{{ formatMontant(row.montant_net) }}</strong></span>
            </template>
          </el-table-column>

          <el-table-column prop="montant_paye" label="Payé" width="130" align="right" sortable="custom" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell">
                <el-tag :type="getPaymentTagType(row)" size="small">
                  {{ formatMontant(row.montant_paye) }}
                </el-tag>
              </span>
            </template>
          </el-table-column>

          <el-table-column prop="statut_paiement" label="Statut" width="130" align="center" sortable="custom" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell">
                <el-tag :type="getStatutType(row.statut_paiement)" size="small">
                  {{ getStatutLabel(row.statut_paiement) }}
                </el-tag>
              </span>
            </template>
          </el-table-column>

          <el-table-column prop="date_reglement" label="Date Règlement" width="120" align="center" resizable>
            <template #default="{ row }">
              <span class="nowrap-cell">{{ row.date_reglement ? formatDate(row.date_reglement) : '-' }}</span>
            </template>
          </el-table-column>

        </el-table>

        <!-- Pagination -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="localPagination.current_page"
            v-model:page-size="localPagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>
    </div>

    <!-- Modal Facture Fournisseur -->
    <FactureFournisseurModal
      v-model="showFactureModal"
      :facture="selectedFacture"
      :fournisseurs="fournisseurs"
      :imputations="imputations"
      :comptes="comptes"
      :comptes-aib="comptesAib"
      :taux-aib-list="tauxAibList"
      :taux-tva-defaut="tauxTvaDefaut"
      @success="handleFactureSuccess"
    />

    <!-- Drawer Imputation Comptable -->
    <el-drawer
      v-model="showImputationDrawer"
      title="Imputation Comptable"
      direction="rtl"
      size="55%"
      :destroy-on-close="true"
    >
      <div v-if="imputationLoading" style="text-align: center; padding: 40px;">
        <el-icon class="is-loading" :size="30"><Refresh /></el-icon>
        <p style="margin-top: 10px; color: #909399;">Chargement...</p>
      </div>

      <div v-else-if="imputationData" class="imputation-content">
        <!-- En-tête identique au PDF -->
        <div class="imputation-header">
          <div class="imputation-hospital-name">{{ imputationData.etablissement.nom }}</div>
          <div class="imputation-hospital-info">
            {{ imputationData.etablissement.pays }}<br>
            {{ imputationData.etablissement.adresse }}{{ imputationData.etablissement.telephone ? ' - Tel: ' + imputationData.etablissement.telephone : '' }}
          </div>
          <div class="imputation-title-box">
            <span>IMPUTATION COMPTABLE</span>
          </div>
          <p class="imputation-numero-piece"><strong><u>Numero piece :</u></strong> {{ imputationData.facture.numero_piece }}</p>
        </div>

        <!-- Tableau des écritures -->
        <table class="imputation-table">
          <thead>
            <tr>
              <th style="width: 100px;">Date</th>
              <th style="width: 110px;">Compte</th>
              <th style="width: 140px; text-align: right;">Débit</th>
              <th style="width: 140px; text-align: right;">Crédit</th>
              <th>Libellé</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(group, gIndex) in imputationData.ecritures" :key="gIndex">
              <tr
                v-for="(ligne, lIndex) in group.lignes"
                :key="`${gIndex}-${lIndex}`"
                :class="lIndex === 0 && gIndex > 0 ? 'block-separator' : ''"
              >
                <td style="font-weight: 600;">
                  <template v-if="lIndex === 0">
                    {{ group.label || ligne.date }}
                    <div v-if="group.label" style="font-size: 11px; font-weight: normal;">{{ ligne.date }}</div>
                  </template>
                  <template v-else-if="ligne.date !== group.lignes[lIndex - 1].date">
                    <span style="font-weight: 500;">{{ ligne.date }}</span>
                  </template>
                </td>
                <td class="cell-compte-num">{{ ligne.numero_compte }}</td>
                <td class="cell-montant">{{ ligne.debit > 0 ? formatMontant(ligne.debit) : '' }}</td>
                <td class="cell-montant">{{ ligne.credit > 0 ? formatMontant(ligne.credit) : '' }}</td>
                <td>{{ ligne.libelle || '' }}</td>
              </tr>
            </template>
          </tbody>
        </table>

        <!-- Bouton télécharger PDF -->
        <div style="text-align: right; margin-top: 20px;">
          <el-button type="primary" :icon="Download" @click="downloadImputationPdf">
            Télécharger PDF
          </el-button>
        </div>
      </div>

      <div v-else style="text-align: center; padding: 40px; color: #909399;">
        Aucune écriture comptable trouvée pour cette facture.
      </div>
    </el-drawer>

    <!-- Drawer État de Règlement Facture -->
    <el-drawer v-model="showEtatReglementDrawer" title="État de Règlement Facture" direction="rtl" size="55%" :destroy-on-close="true">
      <div v-if="etatReglementLoading" style="text-align: center; padding: 40px;">
        <el-icon class="is-loading" :size="30"><Refresh /></el-icon>
        <p style="margin-top: 10px; color: #909399;">Chargement...</p>
      </div>
      <div v-else-if="etatReglementData" class="etat-reglement-content">
        <div class="etat-reglement-header">
          <div class="imputation-hospital-name">{{ etatReglementData.etablissement.nom }}</div>
          <div class="imputation-hospital-info">
            {{ etatReglementData.etablissement.adresse }}<br>
            {{ etatReglementData.etablissement.telephone ? 'Tél.: ' + etatReglementData.etablissement.telephone : '' }}
            {{ etatReglementData.etablissement.email ? ' - E-mail: ' + etatReglementData.etablissement.email : '' }}
          </div>
          <div class="imputation-title-box"><span>ÉTAT DE RÈGLEMENT FACTURE</span></div>
        </div>

        <div class="etat-reglement-fournisseur">
          <strong>Fournisseur :</strong> [{{ etatReglementData.fournisseur.code }}] {{ etatReglementData.fournisseur.nom }}
        </div>

        <div class="etat-reglement-info etat-reglement-info-framed">
          <span><strong>N° PC :</strong> {{ etatReglementData.facture.numero_piece }}</span>
          <span><strong>Date PC :</strong> {{ etatReglementData.facture.date }}</span>
          <span><strong>Réf. Facture :</strong> {{ etatReglementData.facture.reference_facture }}</span>
        </div>

        <div class="etat-reglement-objet">
          <strong>Objet :</strong> {{ etatReglementData.facture.libelle }}
        </div>

        <div class="etat-reglement-montants">
          <span><strong>Montant HT :</strong> {{ etatReglementData.facture.montant_facture }}</span>
          <span><strong>TVA{{ etatReglementData.facture.assujetti_tva && etatReglementData.facture.taux_tva > 0 ? ` (${etatReglementData.facture.taux_tva}%)` : '' }} :</strong> {{ etatReglementData.facture.montant_tva || '0' }}</span>
          <span><strong>Montant TTC :</strong> {{ etatReglementData.facture.montant_ttc }}</span>
        </div>
        <div class="etat-reglement-montants">
          <span><strong>Montant M.O. :</strong> {{ etatReglementData.facture.montant_mo }}</span>
          <span><strong>AIB{{ etatReglementData.facture.taux_aib > 0 ? ` (${etatReglementData.facture.taux_aib}%)` : '' }} :</strong> {{ etatReglementData.facture.montant_aib || '0' }}</span>
          <span><strong>Avoir :</strong> {{ etatReglementData.facture.avoir }}</span>
        </div>

        <table class="etat-reglement-table">
          <thead>
            <tr>
              <th>N° Ordre de règlement</th>
              <th>Date règlement</th>
              <th>Mode règlement</th>
              <th>Bénéficiaire</th>
              <th style="text-align: right;">Montant</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(reg, index) in etatReglementData.reglements" :key="index">
              <td>{{ reg.numero_ordre }}</td>
              <td>{{ reg.date_reglement }}</td>
              <td>{{ reg.mode_paiement }}</td>
              <td>{{ reg.beneficiaire }}</td>
              <td style="text-align: right;">{{ reg.montant }}</td>
            </tr>
            <tr v-if="!etatReglementData.reglements.length">
              <td colspan="5" style="text-align: center; font-style: italic;">Aucun règlement</td>
            </tr>
          </tbody>
        </table>

        <div class="etat-reglement-totaux">
          <div class="etat-reglement-total-row">
            <span class="etat-reglement-total-label">Total règlement :</span>
            <span class="etat-reglement-total-value">{{ etatReglementData.total_reglements }}</span>
          </div>
          <div class="etat-reglement-total-row">
            <span class="etat-reglement-total-label">Montant Dû (Net à payer) :</span>
            <span class="etat-reglement-total-value">{{ etatReglementData.montant_du }}</span>
          </div>
          <div class="etat-reglement-total-row">
            <span class="etat-reglement-total-label">Solde :</span>
            <span class="etat-reglement-total-value">{{ etatReglementData.solde }}</span>
          </div>
        </div>

        <div style="text-align: right; margin-top: 20px;">
          <el-button type="primary" :icon="Download" @click="downloadEtatReglementPdf">Télécharger PDF</el-button>
        </div>
      </div>
      <div v-else style="text-align: center; padding: 40px; color: #909399;">Aucune donnée trouvée.</div>
    </el-drawer>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus,
  Search,
  RefreshLeft,
  Download,
  Refresh,
  View,
  Edit,
  Delete,
  Money,
  More,
  Document,
  WarningFilled,
  Clock,
  SuccessFilled,
  CopyDocument,
  ArrowDown
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FactureFournisseurModal from '@/Components/Modals/FactureFournisseurModal.vue';
import { fetchApi } from '@/Composables/useFetch';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

// Props
const props = defineProps({
  factures: {
    type: Array,
    default: () => []
  },
  fournisseurs: {
    type: Array,
    default: () => []
  },
  imputations: {
    type: Array,
    default: () => []
  },
  comptes: {
    type: Array,
    default: () => []
  },
  comptesAib: {
    type: Array,
    default: () => []
  },
  tauxAibList: {
    type: Array,
    default: () => []
  },
  tauxTvaDefaut: {
    type: Number,
    default: 18
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      montant_impaye: 0,
      montant_partiel: 0,
      montant_paye: 0
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

// Simple debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

// State
const loading = ref(false);
const showFactureModal = ref(false);
const selectedFacture = ref(null);

// Drawer Imputation Comptable
const showImputationDrawer = ref(false);
const imputationLoading = ref(false);
const imputationData = ref(null);
const imputationFactureId = ref(null);

// Drawer État de Règlement
const showEtatReglementDrawer = ref(false);
const etatReglementLoading = ref(false);
const etatReglementData = ref(null);
const etatReglementFactureId = ref(null);

const filters = reactive({
  search: props.pagination?.search || '',
  fournisseur_id: props.pagination?.fournisseur_id || null,
  statut_paiement: props.pagination?.statut_paiement || '',
  date_range: null
});

const localPagination = reactive({
  current_page: props.pagination.current_page,
  per_page: props.pagination.per_page
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Fournisseurs', path: '/factures-fournisseurs' }
];

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits: 0,
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getStatutType = (statut) => {
  const types = {
    impayee: 'danger',
    partielle: 'warning',
    payee: 'success'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    impayee: 'Impayée',
    partielle: 'Partielle',
    payee: 'Payée'
  };
  return labels[statut] || statut;
};

const getPaymentTagType = (row) => {
  if (row.montant_paye === 0) return 'info';
  if (row.montant_paye >= row.montant_ttc) return 'success';
  return 'warning';
};

const buildParams = (overrides = {}) => {
  const params = {
    search: filters.search || undefined,
    fournisseur_id: filters.fournisseur_id || undefined,
    statut: filters.statut_paiement || undefined,
    per_page: localPagination.per_page,
    page: localPagination.current_page,
    ...overrides
  };

  if (filters.date_range && filters.date_range.length === 2) {
    params.date_debut = filters.date_range[0]?.toISOString?.()?.split('T')[0] || filters.date_range[0];
    params.date_fin = filters.date_range[1]?.toISOString?.()?.split('T')[0] || filters.date_range[1];
  }

  return params;
};

const handleSearch = () => {
  loading.value = true;
  router.get('/factures-fournisseurs', buildParams({ page: 1 }), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const debouncedSearch = debounce(handleSearch, 300);

const handleReset = () => {
  filters.search = '';
  filters.fournisseur_id = null;
  filters.statut_paiement = '';
  filters.date_range = null;
  handleSearch();
};

const handleRefresh = () => {
  loading.value = true;
  router.reload({
    only: ['factures', 'stats', 'pagination'],
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleSortChange = ({ prop, order }) => {
  loading.value = true;
  router.get('/factures-fournisseurs', buildParams({
    sort: prop,
    order: order === 'ascending' ? 'asc' : 'desc'
  }), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleSizeChange = (size) => {
  localPagination.per_page = size;
  localPagination.current_page = 1;
  handleSearch();
};

const handlePageChange = (page) => {
  loading.value = true;
  router.get('/factures-fournisseurs', buildParams({ page }), {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const handleCreate = () => {
  selectedFacture.value = null;
  showFactureModal.value = true;
};

const handleView = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}`);
};

const handlePay = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}/regler`);
};

const handleFactureSuccess = async (factureData) => {
  const isEdit = !!selectedFacture.value;
  const url = isEdit
    ? `/api/factures-fournisseurs/${selectedFacture.value.id}`
    : '/api/factures-fournisseurs';

  try {
    const response = await fetchApi(url, {
      method: isEdit ? 'PUT' : 'POST',
      body: factureData
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Facture modifiée avec succès' : 'Facture créée avec succès'));
      showFactureModal.value = false;
      const factureId = result.data?.id || selectedFacture.value?.id;
      const hasImputation = !!result.has_imputation;
      selectedFacture.value = null;
      handleRefresh();

      // Demander à l'utilisateur s'il autorise l'imputation comptable
      const confirmed = await ElMessageBox.confirm(
        'Autorisez-vous une imputation comptable à l\'enregistrement de cette pièce comptable ?',
        'Imputation comptable',
        {
          confirmButtonText: 'Oui',
          cancelButtonText: 'Non',
          type: 'info',
        }
      ).catch(() => false);

      if (confirmed && factureId) {
        try {
          const r = await fetchApi(`/api/factures-fournisseurs/${factureId}/imputation`, { method: 'POST' });
          const j = await r.json();
          if (j.success) {
            ElMessage.success(j.message || 'Écritures comptables générées');
            openImputationDrawer(factureId);
          } else if (j.reason === 'missing') {
            ElMessageBox.alert(j.message, 'Imputations manquantes', { type: 'warning', confirmButtonText: 'OK' });
          } else if (j.reason === 'incomplete') {
            ElMessageBox.alert(j.message, 'Imputations incomplètes', { type: 'warning', confirmButtonText: 'OK' });
          } else {
            ElMessage.error(j.message || 'Erreur lors de la génération des écritures');
          }
        } catch {
          ElMessage.error('Erreur de connexion');
        }
      }
    } else {
      ElMessage.error(result.message || 'Une erreur est survenue');
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    ElMessage.error('Erreur de connexion au serveur');
  }
};

const handleMoreActions = async (command, facture) => {
  switch (command) {
    case 'view':
      handleView(facture);
      break;
    case 'pay':
      handlePay(facture);
      break;
    case 'edit':
      selectedFacture.value = facture;
      showFactureModal.value = true;
      break;
    case 'print':
      ElMessage.info('Impression en cours de développement...');
      break;
    case 'imputation':
      openImputationDrawer(facture.id);
      break;
    case 'etat_reglement':
      openEtatReglementDrawer(facture.id);
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer cette facture ?',
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/factures-fournisseurs/${facture.id}`, {
            method: 'DELETE'
          });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Facture supprimée avec succès');
            handleRefresh();
          } else {
            ElMessage.error(result.message || 'Erreur lors de la suppression');
          }
        } catch (error) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
      break;
  }
};

const handleExport = () => {
  ElMessage.info('Export en cours de développement...');
};

// Imputation Comptable Drawer
const openImputationDrawer = async (factureId) => {
  imputationFactureId.value = factureId;
  imputationData.value = null;
  imputationLoading.value = true;
  showImputationDrawer.value = true;

  try {
    const response = await fetchApi(`/api/factures-fournisseurs/${factureId}/imputation-data`);
    const result = await response.json();
    if (result.success) {
      imputationData.value = result;
    } else {
      ElMessage.warning(result.message || 'Aucune écriture trouvée');
    }
  } catch (err) {
    ElMessage.error('Erreur lors du chargement des écritures');
  } finally {
    imputationLoading.value = false;
  }
};

const downloadImputationPdf = () => {
  if (imputationFactureId.value) {
    window.open(`/factures-fournisseurs/${imputationFactureId.value}/imputation-pdf`, '_blank');
  }
};

const openEtatReglementDrawer = async (factureId) => {
  etatReglementFactureId.value = factureId;
  etatReglementData.value = null;
  etatReglementLoading.value = true;
  showEtatReglementDrawer.value = true;

  try {
    const response = await fetchApi(`/api/factures-fournisseurs/${factureId}/etat-reglement-data`);
    const result = await response.json();
    if (result.success) {
      etatReglementData.value = result;
    } else {
      ElMessage.warning(result.message || 'Données non trouvées');
    }
  } catch (err) {
    ElMessage.error('Erreur lors du chargement de l\'état de règlement');
  } finally {
    etatReglementLoading.value = false;
  }
};

const downloadEtatReglementPdf = () => {
  if (etatReglementFactureId.value) {
    window.open(`/factures-fournisseurs/${etatReglementFactureId.value}/etat-reglement-pdf`, '_blank');
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
  background-color: #e3f2fd;
  color: #1976d2;
}

.stat-unpaid .stat-icon {
  background-color: #ffebee;
  color: #d32f2f;
}

.stat-partial .stat-icon {
  background-color: #fff3e0;
  color: #f57c00;
}

.stat-paid .stat-icon {
  background-color: #e8f5e9;
  color: #388e3c;
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

.montant-ttc {
  color: #059669;
  font-size: 14px;
}

.text-muted {
  color: #9ca3af;
}

.pagination-container {
  margin-top: 16px;
  padding: 0 20px 16px;
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

.table-card :deep(.el-card__body) {
  padding: 0;
}

:deep(.stat-card .el-card__body) {
  padding: 20px;
}

/* Imputation Comptable Drawer */
.imputation-content {
  padding: 0 15px;
  font-family: 'Times New Roman', serif;
}

.imputation-header {
  margin-bottom: 20px;
  text-align: center;
}

.imputation-hospital-name {
  font-size: 20px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.imputation-hospital-info {
  font-size: 12px;
  color: #444;
  line-height: 1.6;
  margin-top: 4px;
}

.imputation-title-box {
  margin: 20px auto 15px;
  display: inline-block;
  border: 2px solid #000;
  padding: 8px 30px;
  font-size: 18px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 2px;
}

.imputation-numero-piece {
  text-align: left;
  margin: 15px 0 0;
  font-size: 14px;
}

.imputation-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.imputation-table th {
  border: 1px solid #000;
  padding: 8px 10px;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  font-size: 12px;
  background-color: #fff;
}

.imputation-table td {
  border-left: 1px solid #000;
  border-right: 1px solid #000;
  border-bottom: 1px solid #ccc;
  padding: 6px 10px;
  vertical-align: top;
}

.imputation-table tbody tr:last-child td {
  border-bottom: 1px solid #000;
}

.imputation-table tfoot td {
  border: 1px solid #000;
  background-color: #fff;
  font-weight: bold;
}

.imputation-table .cell-compte-num {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  text-align: left;
  white-space: nowrap;
}

.imputation-table .cell-compte-lib {
  font-size: 12px;
  color: #333;
}

.imputation-table .cell-montant {
  font-family: 'Courier New', monospace;
  text-align: right;
  white-space: nowrap;
}

.imputation-table tr.block-separator td {
  border-top: 2px solid #000;
}

.imputation-table tr:not(.block-separator) td {
  border-top: none;
}

/* État de Règlement Drawer */
.etat-reglement-content { padding: 0 15px; font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.6; }
.etat-reglement-header { margin-bottom: 15px; text-align: center; }
.etat-reglement-fournisseur { margin: 10px 0; font-size: 13px; }
.etat-reglement-info { display: flex; gap: 25px; margin: 8px 0; font-size: 13px; }
.etat-reglement-info-framed { border: 1px solid #000; padding: 8px; }
.etat-reglement-objet { margin: 10px 0; font-size: 13px; }
.etat-reglement-montants { display: flex; gap: 40px; margin: 3px 0; font-size: 13px; }
.etat-reglement-table { width: 100%; border-collapse: collapse; font-size: 12px; margin: 15px 0; }
.etat-reglement-table th { border: 1px solid #000; padding: 6px 8px; font-weight: bold; text-align: center; font-size: 11px; background-color: #fff; }
.etat-reglement-table td { border: 1px solid #000; padding: 5px 8px; }
.etat-reglement-totaux { display: flex; flex-direction: column; align-items: flex-end; margin-top: 10px; }
.etat-reglement-total-row { display: flex; gap: 15px; padding: 3px 0; font-size: 13px; }
.etat-reglement-total-label { font-weight: bold; min-width: 140px; text-align: right; }
.etat-reglement-total-value { min-width: 100px; text-align: right; }

.nowrap-cell { white-space: nowrap; }
</style>
