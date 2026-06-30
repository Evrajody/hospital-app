<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">R&egrave;glements Clients</h1>
          <p class="page-subtitle">Historique complet des paiements re&ccedil;us</p>
        </div>
        <el-button v-if="can('reglements-clients.creer')" type="primary" size="large" @click="openNewReglement">
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
              @input="debouncedSearch"
            />
          </el-form-item>

          <el-form-item label="Client">
            <el-select
              v-model="filters.client_id"
              placeholder="Tous"
              clearable
              filterable
              style="width: 200px"
              @change="handleSearch"
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
              @change="handleSearch"
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
              value-format="YYYY-MM-DD"
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
              {{ props.groupedReglements.length }} facture(s) sur cette page — {{ pagination.total }} facture(s) au total
            </span>
          </div>
        </template>

        <el-table
          ref="mainTableRef"
          :data="props.groupedReglements"
          :height="tableHeight"
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
                              <el-dropdown-item v-if="can('reglements-clients.modifier')" command="edit" :icon="Edit">
                                Modifier
                              </el-dropdown-item>
                              <el-dropdown-item v-if="can('reglements-clients.supprimer')" divided command="delete" :icon="Delete">
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

        <!-- Pagination (par facture) -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="pagination.current_page"
            v-model:page-size="pagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            background
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
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
            <el-col :span="8">
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
            <el-col :span="8">
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
            <el-col :span="8">
              <el-form-item label="Type de r&egrave;glement">
                <el-select v-model="editForm.type_reglement" style="width: 100%">
                  <el-option label="R&egrave;glement" value="reglement" />
                  <el-option label="Perte" value="perte" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="N&deg; Ligne">
                <el-input v-model="editForm.numero_ligne" />
              </el-form-item>
            </el-col>
            <el-col v-if="editForm.type_reglement === 'reglement'" :span="12">
              <el-form-item label="Montant rejet&eacute; (XOF)">
                <el-input-number
                  v-model="editForm.montant_rejet"
                  :min="0"
                  :precision="0"
                  controls-position="right"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <template v-if="editForm.type_reglement !== 'perte'">
            <el-divider>Source du règlement</el-divider>

            <el-form-item>
              <el-radio-group v-model="editForm.source_paiement" @change="handleEditSourceChange">
                <el-radio-button label="direct">Paiement direct (chèque / espèces)</el-radio-button>
                <el-radio-button label="virement">Virement bancaire</el-radio-button>
                <el-radio-button label="avance" :disabled="editAvancesOptions.length === 0">
                  Imputer sur une avance
                  <span v-if="editAvancesOptions.length === 0" style="font-size: 11px; opacity: 0.7;">(aucune dispo)</span>
                </el-radio-button>
              </el-radio-group>
            </el-form-item>

            <!-- BLOC IMPUTATION SUR AVANCE -->
            <template v-if="editForm.source_paiement === 'avance'">
              <el-form-item label="Avance à imputer">
                <el-select
                  v-model="editForm.avance_id"
                  filterable
                  clearable
                  :loading="editAvancesLoading"
                  placeholder="Sélectionner une avance"
                  style="width: 100%"
                >
                  <el-option
                    v-for="a in editAvancesOptions"
                    :key="a.id"
                    :label="`${a.societe_emettrice} — Chq ${a.numero_cheque}${a.montant_restant != null ? ' — Solde ' + formatMontant(a.montant_restant) : ''}`"
                    :value="a.id"
                  />
                </el-select>
              </el-form-item>
            </template>

            <!-- BLOC VIREMENT BANCAIRE -->
            <template v-else-if="editForm.source_paiement === 'virement'">
              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Banque">
                    <el-select
                      v-model="editForm.banque_depot_id"
                      clearable
                      filterable
                      placeholder="Sélectionner une banque"
                      style="width: 100%"
                      @change="handleEditBanqueChange"
                    >
                      <el-option v-for="b in banques" :key="b.id" :label="b.nom" :value="b.id" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="Référence virement">
                    <el-input v-model="editForm.reference_cheque" placeholder="N° / référence du virement" />
                  </el-form-item>
                </el-col>
              </el-row>
            </template>

            <!-- BLOC PAIEMENT DIRECT (chèque/espèces) -->
            <template v-else>
              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Institution">
                    <el-input v-model="editForm.institution" />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="R&eacute;f. Ch&egrave;que">
                    <el-input v-model="editForm.reference_cheque" placeholder="N° du chèque (vide si espèces)" />
                  </el-form-item>
                </el-col>
              </el-row>
              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Banque de d&eacute;p&ocirc;t">
                    <el-select
                      v-model="editForm.banque_depot_id"
                      clearable
                      filterable
                      placeholder="Aucune (esp&egrave;ces)"
                      style="width: 100%"
                      @change="handleEditBanqueChange"
                    >
                      <el-option v-for="b in banques" :key="b.id" :label="b.nom" :value="b.id" />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="R&eacute;f&eacute;rence bordereau">
                    <el-select
                      v-model="editForm.approvisionnement_id"
                      clearable
                      filterable
                      placeholder="S&eacute;lectionner un bordereau"
                      :disabled="!editForm.banque_depot_id"
                      style="width: 100%"
                    >
                      <el-option v-for="a in editApprovisionnements" :key="a.id" :label="a.reference_bordereau" :value="a.id" />
                    </el-select>
                  </el-form-item>
                </el-col>
              </el-row>
            </template>
          </template>

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
              remote
              :remote-method="searchFactureImpayee"
              :loading="facturesLoading"
              remote-show-suffix
              placeholder="Tapez la r&eacute;f&eacute;rence ou le client&hellip;"
              style="width: 100%"
              size="large"
            >
              <el-option
                v-for="f in factureOptions"
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
import { usePermissions } from '@/Composables/usePermissions';
import { useTableHeight } from '@/Composables/useTableHeight';
import { debounce } from '@/utils/debounce';
import { toYmd } from '@/utils/date';

const { can } = usePermissions();

const mainTableRef = ref(null);
const { tableHeight } = useTableHeight(mainTableRef, 84);

const props = defineProps({
  groupedReglements: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  banques: { type: Array, default: () => [] },
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
    default: () => ({ current_page: 1, per_page: 20, total: 0, last_page: 1 })
  },
  filters: { type: Object, default: () => ({}) },
  user: { type: Object, default: () => null }
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'R\u00e8glements Clients', path: '/reglements-clients' }
];

const filters = ref({
  search: props.filters?.search || '',
  client_id: props.filters?.client_id || null,
  type_reglement: props.filters?.type_reglement || '',
  date_range: null,
});

// --- Filtres 100 % serveur ---
const handleSearch = () => {
  const params = {};
  if (filters.value.search) params.search = filters.value.search;
  if (filters.value.client_id) params.client_id = filters.value.client_id;
  if (filters.value.type_reglement) params.type_reglement = filters.value.type_reglement;
  if (filters.value.date_range && filters.value.date_range.length === 2) {
    params.date_debut = toYmd(filters.value.date_range[0]);
    params.date_fin = toYmd(filters.value.date_range[1]);
  }
  router.get('/reglements-clients', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['groupedReglements', 'pagination', 'stats', 'filters'],
  });
};
const debouncedSearch = debounce(handleSearch, 300);

const handleSizeChange = (size) => {
  const params = new URLSearchParams(window.location.search);
  params.set('per_page', size);
  params.set('page', '1');
  router.visit(`/reglements-clients?${params.toString()}`, {
    preserveState: true,
    only: ['groupedReglements', 'pagination'],
  });
};

const handlePageChange = (page) => {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  router.visit(`/reglements-clients?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
    only: ['groupedReglements', 'pagination'],
  });
};

const detailDialogVisible = ref(false);
const selectedReglement = ref(null);
const showNewReglementModal = ref(false);
const selectedFactureId = ref(null);
// Recherche serveur des factures impayées (ne précharge plus toute la liste).
const factureOptions = ref([]);
const facturesLoading = ref(false);

const searchFactureImpayee = async (query) => {
  facturesLoading.value = true;
  try {
    const params = new URLSearchParams({ per_page: '50' });
    if (query && query.trim()) params.set('search', query.trim());
    const res = await fetchApi(`/api/factures-clients/impayees?${params.toString()}`);
    const json = await res.json();
    factureOptions.value = json.data || [];
  } catch (e) {
    factureOptions.value = [];
  } finally {
    facturesLoading.value = false;
  }
};

const openNewReglement = () => {
  selectedFactureId.value = null;
  factureOptions.value = [];
  showNewReglementModal.value = true;
  searchFactureImpayee(''); // précharge les 50 factures impayées les plus récentes
};
const editDialogVisible = ref(false);
const editForm = ref(null);
const editLoading = ref(false);
const editingReglementId = ref(null);

// Les données groupedReglements viennent directement du serveur (triées par numero_ligne).

const handleReset = () => {
  filters.value = { search: '', client_id: null, type_reglement: '', date_range: null };
  handleSearch();
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

const editAvancesList = ref([]);
const editCurrentAvance = ref(null);
const editAvancesLoading = ref(false);

const handleEdit = async (reglement) => {
  editingReglementId.value = reglement.id;
  editCurrentAvance.value = reglement.avance || null;
  editForm.value = {
    type_reglement: reglement.type_reglement || 'reglement',
    date_reglement: reglement.date_reglement,
    montant: reglement.montant,
    montant_rejet: reglement.montant_rejet || 0,
    numero_ligne: reglement.numero_ligne || '',
    source_paiement: reglement.avance_id ? 'avance' : 'direct',
    avance_id: reglement.avance_id || null,
    institution: reglement.institution || '',
    reference_cheque: reglement.reference_cheque || '',
    banque_depot_id: reglement.banque_depot?.id || null,
    approvisionnement_id: reglement.approvisionnement?.id || null,
    observations: reglement.observations || '',
  };
  editDialogVisible.value = true;

  // Charge les avances disponibles du client (pour ré-imputation).
  editAvancesList.value = [];
  const clientId = reglement.client?.id || reglement.client_id;
  if (clientId) {
    editAvancesLoading.value = true;
    try {
      const res = await fetchApi(`/api/avances-clients/client/${clientId}`);
      const json = await res.json();
      editAvancesList.value = json.data || [];
    } catch {
      editAvancesList.value = [];
    } finally {
      editAvancesLoading.value = false;
    }
  }
};

const editApprovisionnements = computed(() => {
  if (!editForm.value || !editForm.value.banque_depot_id) return [];
  const banque = props.banques.find(b => b.id === editForm.value.banque_depot_id);
  return banque?.approvisionnements || [];
});

// Avances proposées : disponibles du client + l'avance déjà imputée si elle n'y figure plus.
const editAvancesOptions = computed(() => {
  const list = [...editAvancesList.value];
  const cur = editCurrentAvance.value;
  if (editForm.value?.avance_id && cur && !list.some(a => a.id === editForm.value.avance_id)) {
    list.unshift({
      id: cur.id,
      societe_emettrice: cur.societe_emettrice,
      numero_cheque: cur.numero_cheque,
      montant_restant: null,
    });
  }
  return list;
});

const handleEditBanqueChange = () => {
  if (editForm.value) editForm.value.approvisionnement_id = null;
};

const handleEditSourceChange = () => {
  if (!editForm.value) return;
  if (editForm.value.source_paiement === 'avance') {
    editForm.value.institution = '';
    editForm.value.reference_cheque = '';
    editForm.value.banque_depot_id = null;
    editForm.value.approvisionnement_id = null;
  } else if (editForm.value.source_paiement === 'virement') {
    editForm.value.avance_id = null;
    editForm.value.institution = '';
    editForm.value.approvisionnement_id = null;
  } else {
    editForm.value.avance_id = null;
  }
};

const handleEditSubmit = async () => {
  editLoading.value = true;
  try {
    // eslint-disable-next-line no-unused-vars
    const { source_paiement, ...payload } = editForm.value;
    const response = await fetchApi(`/api/reglements-clients/${editingReglementId.value}`, {
      method: 'PUT',
      body: payload,
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
.pagination-container { display: flex; justify-content: flex-end; margin-top: 16px; }
</style>
