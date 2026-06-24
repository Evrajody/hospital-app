<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Règlements Fournisseurs</h1>
          <p class="page-subtitle">Historique complet des paiements effectués</p>
        </div>
        <el-button v-if="can('reglements-fournisseurs.creer')" type="primary" size="large" @click="handleNewPayment">
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
              format="DD/MM/YYYY"
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
              {{ groupedReglements.length }} facture(s) — {{ pagination.total }} règlement(s)
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
          :data="groupedReglements"
          stripe
          border
          style="width: 100%"
          row-key="key"
          @sort-change="handleSortChange"
        >
          <el-table-column type="expand" width="50">
            <template #default="{ row }">
              <div class="expand-reglements">
                <el-table :data="row.reglements" border size="small" class="inner-reglements-table">
                  <el-table-column label="N° Règlement" width="150">
                    <template #default="{ row: reg }">
                      <strong>{{ reg.numero_complet || '-' }}</strong>
                    </template>
                  </el-table-column>
                  <el-table-column label="Date" width="110">
                    <template #default="{ row: reg }">{{ formatDate(reg.date_reglement) }}</template>
                  </el-table-column>
                  <el-table-column label="Mode" width="120">
                    <template #default="{ row: reg }">
                      <el-tag :type="getModeTagType(reg.mode_paiement)" size="small">
                        {{ getModeLabel(reg.mode_paiement) }}
                      </el-tag>
                    </template>
                  </el-table-column>
                  <el-table-column label="Référence" width="140">
                    <template #default="{ row: reg }">
                      <span v-if="reg.reference">{{ reg.reference }}</span>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Bénéficiaire" min-width="160">
                    <template #default="{ row: reg }">
                      <span v-if="reg.beneficiaire">{{ reg.beneficiaire }}</span>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Compte bancaire" width="170">
                    <template #default="{ row: reg }">
                      <div v-if="reg.compte_bancaire" class="compte-cell">
                        <el-icon><CreditCard /></el-icon>
                        <span>{{ reg.compte_bancaire.banque }}</span>
                      </div>
                      <span v-else class="text-muted">-</span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Saisi par" width="140">
                    <template #default="{ row: reg }">
                      <div v-if="reg.user" class="user-cell">
                        <el-icon><User /></el-icon>
                        <span>{{ reg.user.name }}</span>
                      </div>
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
                          Détails
                        </el-button>
                        <el-dropdown @command="(cmd) => handleMoreActions(cmd, reg)">
                          <el-button :icon="More" size="small" />
                          <template #dropdown>
                            <el-dropdown-menu>
                              <el-dropdown-item command="mandat" :icon="DocumentCopy">
                                Bordereau de règlement
                              </el-dropdown-item>
                              <el-dropdown-item
                                v-if="reg.mode_paiement !== 'especes'"
                                command="imputation"
                                :icon="DocumentCopy"
                              >
                                Imputation comptable
                              </el-dropdown-item>
                              <el-dropdown-item v-if="can('reglements-fournisseurs.modifier')" command="edit" :icon="Edit">
                                Modifier
                              </el-dropdown-item>
                              <el-dropdown-item v-if="can('reglements-fournisseurs.supprimer')" divided command="delete" :icon="Delete">
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

          <el-table-column label="N° Pièce" width="140" sortable="custom" prop="facture">
            <template #default="{ row }">
              <span class="nowrap-cell">
                <el-link type="primary" @click="handleViewFacture(row.facture)">
                  <strong>{{ row.facture.numero }}</strong>
                </el-link>
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Fournisseur" min-width="200" sortable="custom" prop="fournisseur">
            <template #default="{ row }">
              <div class="fournisseur-nom">{{ row.fournisseur.nom }}</div>
            </template>
          </el-table-column>

          <el-table-column label="Date facture" width="120">
            <template #default="{ row }">{{ formatDate(row.facture.date) }}</template>
          </el-table-column>

          <el-table-column label="Montant TTC" width="150" align="right">
            <template #default="{ row }">
              <span class="nowrap-cell">{{ formatMontant(row.facture.montant_ttc) }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Total réglé" width="150" align="right">
            <template #default="{ row }">
              <span class="nowrap-cell"><strong class="montant-reglement">{{ formatMontant(row.total_montant_regle) }}</strong></span>
            </template>
          </el-table-column>

          <el-table-column label="Reste à payer" width="150" align="right">
            <template #default="{ row }">
              <span class="nowrap-cell" :class="row.facture.reste_a_payer > 0 ? 'reste-due' : 'reste-paid'">
                <strong>{{ formatMontant(row.facture.reste_a_payer) }}</strong>
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Nb règlements" width="130" align="center">
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

            <el-descriptions-item label="N° Pièce">
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
            <el-button type="primary" :icon="DocumentCopy" @click="handlePrintMandat">
              Bordereau de règlement
            </el-button>
            <el-button type="info" :icon="Document" @click="handleViewFactureFromModal">
              Voir la facture
            </el-button>
          </div>
        </template>
      </el-dialog>

      <!-- Drawer Imputation Comptable -->
      <el-drawer v-model="showImputationDrawer" title="Imputation Comptable" direction="rtl" size="55%" :destroy-on-close="true">
        <div v-if="imputationLoading" style="text-align: center; padding: 40px;">
          <el-icon class="is-loading" :size="30"><Clock /></el-icon>
          <p style="margin-top: 10px; color: #909399;">Chargement...</p>
        </div>
        <div v-else-if="imputationData" class="imputation-content">
          <div class="imputation-header">
            <div class="imputation-hospital-name">{{ imputationData.etablissement.nom }}</div>
            <div class="imputation-hospital-info">
              {{ imputationData.etablissement.pays }}<br>
              {{ imputationData.etablissement.adresse }}{{ imputationData.etablissement.telephone ? ' - Tel: ' + imputationData.etablissement.telephone : '' }}
            </div>
            <div class="imputation-title-box"><span>IMPUTATION COMPTABLE</span></div>
            <p class="imputation-numero-piece"><strong><u>N° Règlement :</u></strong> {{ imputationData.reglement.numero_complet || imputationData.reglement.numero_reglement }} - {{ imputationData.reglement.date_reglement }}</p>
            <p class="imputation-numero-piece"><strong><u>Facture :</u></strong> {{ imputationData.facture.numero_piece }}</p>
          </div>
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
                <tr v-for="(ligne, lIndex) in group.lignes" :key="`${gIndex}-${lIndex}`" :style="lIndex === 0 && gIndex > 0 ? 'border-top: 2px solid #000;' : ''">
                  <td style="font-weight: 600;">{{ lIndex === 0 ? group.date : '' }}</td>
                  <td class="cell-compte-num">{{ ligne.numero_compte }}</td>
                  <td class="cell-montant">{{ ligne.debit > 0 ? formatMontant(ligne.debit) : '' }}</td>
                  <td class="cell-montant">{{ ligne.credit > 0 ? formatMontant(ligne.credit) : '' }}</td>
                  <td>{{ ligne.libelle || '' }}</td>
                </tr>
              </template>
            </tbody>
          </table>
          <div style="text-align: right; margin-top: 20px;">
            <el-button type="primary" :icon="Printer" @click="downloadImputationPdf">Télécharger PDF</el-button>
          </div>
        </div>
        <div v-else style="text-align: center; padding: 40px; color: #909399;">Aucune écriture comptable trouvée.</div>
      </el-drawer>

      <!-- Modal Sélection Facture -->
      <el-dialog
        v-model="selectFactureDialogVisible"
        title="Sélectionner une Facture"
        width="600px"
        :close-on-click-modal="false"
      >
        <el-form label-position="top">
          <el-form-item label="Facture à régler (recherche par N° PC, libellé ou fournisseur)">
            <el-select
              v-model="selectedFactureId"
              placeholder="Tapez le N° PC (ex: 0023), le libellé ou le fournisseur…"
              filterable
              remote
              :remote-method="searchFactureImpayee"
              :loading="facturesLoading"
              remote-show-suffix
              style="width: 100%"
              size="large"
            >
              <el-option
                v-for="facture in factureOptions"
                :key="facture.id"
                :label="`${facture.numero} - ${facture.libelle}`"
                :value="facture.id"
              >
                <div style="display: flex; justify-content: space-between; width: 100%;">
                  <div>
                    <el-tag size="small" type="primary">{{ facture.numero }}</el-tag>
                    <span style="margin-left: 8px">{{ facture.libelle }}</span>
                  </div>
                  <small style="color: #909399">{{ facture.fournisseur_nom || '' }}</small>
                </div>
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
import { ref, reactive, computed } from 'vue';
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
  Edit,
  Clock
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchApi } from '@/Composables/useFetch';
import { usePdfViewer } from '@/Composables/usePdfViewer';
import { usePermissions } from '@/Composables/usePermissions';
import { toYmd } from '@/utils/date';

const { openPdf } = usePdfViewer();
const { can } = usePermissions();

// Simple debounce function
const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

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
// Recherche serveur des factures impayées (le sélecteur ne précharge plus toute
// la liste : sur gros volume elle saturait l'historique Inertia → erreur Firefox).
const factureOptions = ref([]);
const facturesLoading = ref(false);

// Drawer Imputation Comptable
const showImputationDrawer = ref(false);
const imputationLoading = ref(false);
const imputationData = ref(null);
const imputationReglementId = ref(null);

// Recherche serveur (N° PC, libellé, fournisseur) — 50 résultats max.
const searchFactureImpayee = async (query) => {
  facturesLoading.value = true;
  try {
    const params = new URLSearchParams({ non_payee: '1', per_page: '50', sort: 'date', order: 'desc' });
    if (query && query.trim()) params.set('search', query.trim());
    const res = await fetchApi(`/api/factures-fournisseurs?${params.toString()}`);
    const json = await res.json();
    factureOptions.value = (json.data || []).map(f => ({
      id: f.id,
      numero: f.numero_piece ?? f.numero,
      libelle: f.libelle,
      fournisseur_nom: f.fournisseur?.nom ?? f.fournisseur_nom ?? '',
    }));
  } catch (e) {
    factureOptions.value = [];
  } finally {
    facturesLoading.value = false;
  }
};

// Regrouper les règlements par facture pour affichage en lignes expansibles
const groupedReglements = computed(() => {
  const map = new Map();
  for (const r of props.reglements || []) {
    const factureId = r.facture?.id ?? `${r.facture?.numero || 'unknown'}-${r.fournisseur?.id || 0}`;
    if (!map.has(factureId)) {
      map.set(factureId, {
        key: `f-${factureId}`,
        facture: {
          id: r.facture?.id,
          numero: r.facture?.numero || '-',
          date: r.facture?.date || r.facture?.date_facture || null,
          montant_ttc: parseFloat(r.facture?.montant_ttc) || 0,
          montant_paye: parseFloat(r.facture?.montant_paye) || 0,
          reste_a_payer: parseFloat(r.facture?.reste_a_payer) || 0,
          libelle: r.facture?.libelle || '',
        },
        fournisseur: r.fournisseur || { nom: '-' },
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
    maximumFractionDigits: 0,
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
  const params = {};

  if (filters.search) {
    params.search = filters.search;
  }
  if (filters.fournisseur_id) {
    params.fournisseur_id = filters.fournisseur_id;
  }
  if (filters.mode_paiement) {
    params.mode_paiement = filters.mode_paiement;
  }
  if (filters.date_range && filters.date_range.length === 2) {
    const d0 = filters.date_range[0];
    const d1 = filters.date_range[1];
    params.date_debut = toYmd(d0);
    params.date_fin = toYmd(d1);
  }

  router.get('/reglements-fournisseurs', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['reglements', 'stats', 'pagination']
  });
};

const debouncedSearch = debounce(handleSearch, 300);

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
  if (order) {
    params.set('sort', prop);
    params.set('order', order === 'ascending' ? 'asc' : 'desc');
  } else {
    params.delete('sort');
    params.delete('order');
  }

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
  factureOptions.value = [];
  selectFactureDialogVisible.value = true;
  // Précharge les 50 factures impayées les plus récentes (recherche serveur ensuite).
  searchFactureImpayee('');
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

const handlePrintMandat = () => {
  if (selectedReglement.value) {
    openPdf(`/reglements-fournisseurs/${selectedReglement.value.id}/mandat`, 'Bordereau de règlement');
  }
};

const handleViewFacture = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}`);
};

const openImputationDrawer = async (reglement) => {
  imputationReglementId.value = reglement.id;
  imputationData.value = null;
  imputationLoading.value = true;
  showImputationDrawer.value = true;
  try {
    const response = await fetchApi(`/api/reglements-fournisseurs/${reglement.id}/imputation-data`);
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
  if (imputationReglementId.value) {
    window.open(`/reglements-fournisseurs/${imputationReglementId.value}/imputation-pdf`, '_blank');
  }
};

const handleMoreActions = async (command, reglement) => {
  switch (command) {
    case 'mandat':
      openPdf(`/reglements-fournisseurs/${reglement.id}/mandat`, 'Bordereau de règlement');
      break;
    case 'imputation':
      await openImputationDrawer(reglement);
      break;
    case 'edit':
      router.visit(`/factures-fournisseurs/${reglement.facture.id}/regler?edit=${reglement.id}`);
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
          const response = await fetchApi(`/api/reglements-fournisseurs/${reglement.id}`, {
            method: 'DELETE'
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

.reste-due {
  color: #dc2626;
}

.reste-paid {
  color: #6b7280;
}

.expand-reglements {
  padding: 12px 24px;
  background-color: #f9fafb;
}

.inner-reglements-table {
  background: #ffffff;
}

.inner-reglements-table :deep(.el-table th) {
  background-color: #eef2ff;
  font-size: 12px;
  font-weight: 600;
}

.text-muted {
  color: #d1d5db;
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
  flex-wrap: wrap;
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

.nowrap-cell { white-space: nowrap; }

/* Imputation Comptable Drawer */
.imputation-content { padding: 0 15px; font-family: 'Times New Roman', serif; }
.imputation-header { margin-bottom: 20px; text-align: center; }
.imputation-hospital-name { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
.imputation-hospital-info { font-size: 12px; color: #444; line-height: 1.6; margin-top: 4px; }
.imputation-title-box { margin: 20px auto 15px; display: inline-block; border: 2px solid #000; padding: 8px 30px; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
.imputation-numero-piece { text-align: left; margin: 8px 0 0; font-size: 14px; }
.imputation-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.imputation-table th { border: 1px solid #000; padding: 8px 10px; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 12px; background-color: #fff; }
.imputation-table td { border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #ccc; padding: 6px 10px; vertical-align: top; }
.imputation-table tbody tr:last-child td { border-bottom: 1px solid #000; }
.imputation-table .cell-compte-num { font-family: 'Courier New', monospace; font-weight: 600; text-align: left; white-space: nowrap; }
.imputation-table .cell-compte-lib { font-size: 12px; color: #333; }
.imputation-table .cell-montant { font-family: 'Courier New', monospace; text-align: right; white-space: nowrap; }
</style>
