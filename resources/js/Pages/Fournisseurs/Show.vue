<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <div>
            <h1 class="page-title">{{ fournisseur.nom }}</h1>
            <p class="page-subtitle" v-if="fournisseur.type_fournisseur_libelle">
              {{ fournisseur.type_fournisseur_libelle }}
            </p>
          </div>
        </div>

        <div class="header-actions">
          <el-button @click="handleBack">
            <el-icon><ArrowLeft /></el-icon>
            Retour
          </el-button>
          <el-button type="primary" @click="handleEdit">
            <el-icon><Edit /></el-icon>
            Modifier
          </el-button>
          <el-button type="danger" @click="handleDelete">
            <el-icon><Delete /></el-icon>
            Supprimer
          </el-button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover" class="stat-card-item">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <el-icon :size="24"><Document /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.nombre_factures }}</div>
                <div class="stat-label">Factures</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover" class="stat-card-item">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%)">
                <el-icon :size="24"><Money /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.montant_total) }}</div>
                <div class="stat-label">Total Facturé</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover" class="stat-card-item">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                <el-icon :size="24"><SuccessFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.montant_paye) }}</div>
                <div class="stat-label">Payé</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover" class="stat-card-item">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)">
                <el-icon :size="24"><WarningFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.montant_reste) }}</div>
                <div class="stat-label">Reste à Payer</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Main Content Tabs -->
      <el-card shadow="never" class="main-card">
        <el-tabs v-model="activeTab" type="border-card">
          <!-- Onglet Informations -->
          <el-tab-pane name="informations">
            <template #label>
              <span class="tab-label">
                <el-icon><InfoFilled /></el-icon>
                Informations
              </span>
            </template>

            <el-row :gutter="24">
              <!-- Colonne Gauche -->
              <el-col :xs="24" :lg="12">
                <!-- Informations Générales -->
                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><OfficeBuilding /></el-icon>
                    Informations Générales
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Raison Sociale">
                      <strong>{{ fournisseur.nom }}</strong>
                    </el-descriptions-item>
                    <el-descriptions-item label="Type de Fournisseur">
                      <el-tag v-if="fournisseur.type_fournisseur" type="info">
                        {{ fournisseur.type_fournisseur_libelle || fournisseur.type_fournisseur }}
                      </el-tag>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Compte Comptable">
                      <div v-if="fournisseur.compte_comptable" class="compte-info">
                        <el-tag size="small">{{ fournisseur.compte_comptable.numero }}</el-tag>
                        <span>{{ fournisseur.compte_comptable.libelle }}</span>
                      </div>
                      <span v-else class="text-muted">Non assigné</span>
                    </el-descriptions-item>
                  </el-descriptions>
                </div>

                <!-- Coordonnées -->
                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><Phone /></el-icon>
                    Coordonnées
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Personne de Contact">
                      {{ fournisseur.contact || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Fonction">
                      {{ fournisseur.fonction_contact || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Téléphone Principal">
                      <el-link v-if="fournisseur.telephone" :href="`tel:${fournisseur.telephone}`" type="primary">
                        <el-icon><Phone /></el-icon>
                        {{ fournisseur.telephone }}
                      </el-link>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Téléphone Secondaire">
                      <el-link v-if="fournisseur.telephone_secondaire" :href="`tel:${fournisseur.telephone_secondaire}`" type="primary">
                        <el-icon><Phone /></el-icon>
                        {{ fournisseur.telephone_secondaire }}
                      </el-link>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Email">
                      <el-link v-if="fournisseur.email" :href="`mailto:${fournisseur.email}`" type="primary">
                        <el-icon><Message /></el-icon>
                        {{ fournisseur.email }}
                      </el-link>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Site Web">
                      <el-link v-if="fournisseur.site_web" :href="fournisseur.site_web" target="_blank" type="primary">
                        {{ fournisseur.site_web }}
                      </el-link>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Adresse">
                      {{ fournisseur.adresse || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Ville">
                      {{ fournisseur.ville || '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Pays">
                      {{ fournisseur.pays_nom || fournisseur.pays || '-' }}
                    </el-descriptions-item>
                  </el-descriptions>
                </div>
              </el-col>

              <!-- Colonne Droite -->
              <el-col :xs="24" :lg="12">
                <!-- Informations Fiscales -->
                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><Wallet /></el-icon>
                    Informations Fiscales
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="IFU">
                      <el-tag v-if="fournisseur.ifu" type="warning">{{ fournisseur.ifu }}</el-tag>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="RCCM">
                      {{ fournisseur.rccm || '-' }}
                    </el-descriptions-item>
                  </el-descriptions>
                </div>

                <!-- Observations -->
                <div class="info-section" v-if="fournisseur.observations">
                  <h3 class="section-title">
                    <el-icon><More /></el-icon>
                    Observations
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Notes">
                      <div class="observations-text">{{ fournisseur.observations }}</div>
                    </el-descriptions-item>
                  </el-descriptions>
                </div>

                <!-- Dates système -->
                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><Calendar /></el-icon>
                    Historique
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Créé le">
                      {{ fournisseur.created_at ? formatDateTime(fournisseur.created_at) : '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Modifié le">
                      {{ fournisseur.updated_at ? formatDateTime(fournisseur.updated_at) : '-' }}
                    </el-descriptions-item>
                  </el-descriptions>
                </div>
              </el-col>
            </el-row>
          </el-tab-pane>

          <!-- Onglet Factures -->
          <el-tab-pane name="factures">
            <template #label>
              <span class="tab-label">
                <el-icon><Tickets /></el-icon>
                Factures
                <el-badge v-if="factures.length > 0" :value="factures.length" class="tab-badge" />
              </span>
            </template>

            <div class="factures-header">
              <div class="factures-title">
                <h3>Liste des Factures</h3>
                <span class="factures-count">{{ factures.length }} facture(s)</span>
              </div>
            </div>

            <!-- Table des factures -->
            <el-table
              v-if="factures.length > 0"
              :data="factures"
              stripe
              border
              style="width: 100%"
              class="factures-table"
            >
              <el-table-column label="Actions" width="100" fixed="left" align="center" resizable>
                <template #default="{ row }">
                  <el-dropdown trigger="click" @command="(cmd) => handleFactureAction(cmd, row)">
                    <el-button size="small" type="primary">
                      Actions <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                    </el-button>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item command="view" :icon="View">
                          Voir
                        </el-dropdown-item>
                        <el-dropdown-item
                          v-if="row.statut_paiement !== 'payee'"
                          command="pay"
                          :icon="Money"
                        >
                          Régler
                        </el-dropdown-item>
                        <el-dropdown-item command="edit" :icon="Edit">
                          Modifier
                        </el-dropdown-item>
                        <el-dropdown-item command="etat_reglement" :icon="Document">
                          État de Règlement
                        </el-dropdown-item>
                        <el-dropdown-item
                          v-if="row.imputation_id || row.compte_id"
                          command="imputation"
                          :icon="CopyDocument"
                        >
                          Imputation Comptable
                        </el-dropdown-item>
                        <el-dropdown-item divided command="delete" :icon="Delete">
                          <span style="color: #f56c6c">Supprimer</span>
                        </el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </template>
              </el-table-column>

              <el-table-column prop="numero" label="N° Pièce" width="140" sortable fixed="left" resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <el-link type="primary" @click="handleViewFacture(row)">
                      <strong>{{ row.numero }}</strong>
                    </el-link>
                  </span>
                </template>
              </el-table-column>

              <el-table-column prop="date_facture" label="Date" width="110" sortable fixed="left" resizable>
                <template #default="{ row }">
                  {{ formatDate(row.date_facture) }}
                </template>
              </el-table-column>

              <el-table-column prop="date_facture_bc" label="Date Fact/B.C." width="120" sortable resizable>
                <template #default="{ row }">
                  {{ row.date_facture_bc ? formatDate(row.date_facture_bc) : '-' }}
                </template>
              </el-table-column>

              <el-table-column prop="reference_facture" label="Réf. Fact/B.C." width="140" sortable resizable />

              <el-table-column prop="libelle" label="Libellé" min-width="180" sortable resizable>
                <template #default="{ row }">
                  {{ row.libelle || '-' }}
                </template>
              </el-table-column>

              <el-table-column prop="montant_ttc" label="Montant TTC" width="140" align="right" sortable resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell"><strong class="montant-ttc">{{ formatMontant(row.montant_ttc) }}</strong></span>
                </template>
              </el-table-column>

              <el-table-column prop="montant_net" label="Net à payer" width="140" align="right" sortable resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell"><strong>{{ formatMontant(row.montant_net) }}</strong></span>
                </template>
              </el-table-column>

              <el-table-column prop="montant_paye" label="Payé" width="130" align="right" sortable resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <el-tag :type="getPaymentTagType(row)" size="small">
                      {{ formatMontant(row.montant_paye) }}
                    </el-tag>
                  </span>
                </template>
              </el-table-column>

              <el-table-column prop="statut_paiement" label="Statut" width="130" align="center" sortable resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <el-tag :type="getStatutType(row.statut_paiement)" size="small">
                      {{ getStatutLabel(row.statut_paiement) }}
                    </el-tag>
                  </span>
                </template>
              </el-table-column>
            </el-table>

            <!-- État vide -->
            <el-empty v-else description="Aucune facture pour ce fournisseur" :image-size="120" />
          </el-tab-pane>

          <!-- Onglet Règlements -->
          <el-tab-pane name="reglements">
            <template #label>
              <span class="tab-label">
                <el-icon><CreditCard /></el-icon>
                Règlements
                <el-badge v-if="reglements.length > 0" :value="reglements.length" class="tab-badge" />
              </span>
            </template>

            <div class="reglements-header">
              <div class="reglements-title">
                <h3>Historique des Règlements</h3>
                <span class="reglements-count">{{ reglements.length }} règlement(s) - Total: {{ formatMontant(statsReglements.total_reglements) }}</span>
              </div>
            </div>

            <!-- Table des règlements (groupés par facture, comme le tableau principal) -->
            <el-table
              v-if="reglements.length > 0"
              :data="groupedReglements"
              stripe
              border
              style="width: 100%"
              row-key="key"
              class="reglements-table"
            >
              <el-table-column type="expand" width="50">
                <template #default="{ row }">
                  <div class="expand-reglements">
                    <el-table :data="row.reglements" border size="small" class="inner-reglements-table">
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
                            <el-button :icon="View" size="small" type="primary" @click="handleViewReglement(reg)">
                              Détails
                            </el-button>
                            <el-dropdown @command="(cmd) => handleReglementActions(cmd, reg)">
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

              <el-table-column label="N° Pièce" width="140" prop="facture">
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <el-link type="primary" @click="handleViewFacture(row.facture)">
                      <strong>{{ row.facture.numero }}</strong>
                    </el-link>
                  </span>
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

            <!-- État vide -->
            <el-empty v-else description="Aucun règlement pour ce fournisseur" :image-size="120" />
          </el-tab-pane>
        </el-tabs>
      </el-card>

      <!-- Modals -->
      <FournisseurModal
        v-model="showFournisseurModal"
        :fournisseur="selectedFournisseur"
        :comptes-fournisseurs="comptesFournisseurs"
        :comptes-parents="comptesParents"
        :server-errors="serverErrors"
        :loading="modalLoading"
        @success="handleFournisseurSuccess"
      />

      <FactureFournisseurModal
        v-model="showFactureModal"
        :facture="selectedFacture"
        :fournisseur="fournisseur"
        :fournisseurs="[fournisseur]"
        :fournisseur-id="fournisseur.id"
        :imputations="imputations"
        :comptes="comptes"
        :comptes-aib="comptesAib"
        @success="handleFactureSuccess"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  ArrowLeft,
  Edit,
  Delete,
  InfoFilled,
  Phone,
  Message,
  Document,
  Money,
  SuccessFilled,
  WarningFilled,
  Tickets,
  Plus,
  View,
  OfficeBuilding,
  Wallet,
  More,
  Calendar,
  CreditCard,
  DocumentCopy,
  Printer,
  Notebook,
  ArrowDown,
  CopyDocument,
  User
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FournisseurModal from '@/Components/Modals/FournisseurModal.vue';
import FactureFournisseurModal from '@/Components/Modals/FactureFournisseurModal.vue';
import { fetchApi } from '@/Composables/useFetch';
import { usePdfViewer } from '@/Composables/usePdfViewer';

const { openPdf } = usePdfViewer();

// Props
const props = defineProps({
  fournisseur: {
    type: Object,
    required: true
  },
  factures: {
    type: Array,
    default: () => []
  },
  reglements: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      nombre_factures: 0,
      montant_total: 0,
      montant_paye: 0,
      montant_reste: 0
    })
  },
  statsReglements: {
    type: Object,
    default: () => ({
      total_reglements: 0,
      reglements_mois: 0,
      nombre_reglements: 0,
      montant_moyen: 0
    })
  },
  comptesFournisseurs: {
    type: Array,
    default: () => []
  },
  comptesParents: {
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
  comptesTresorerie: {
    type: Array,
    default: () => []
  },
  comptesAib: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State
const activeTab = ref('informations');
const showFournisseurModal = ref(false);
const selectedFournisseur = ref(null);
const showFactureModal = ref(false);
const selectedFacture = ref(null);
const serverErrors = ref(null);
const modalLoading = ref(false);

// Computed
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Fournisseurs', path: '/fournisseurs' },
  { title: props.fournisseur.nom, path: '' }
];

// Methods
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

const formatDateTime = (datetime) => {
  if (!datetime) return '-';
  return new Date(datetime).toLocaleString('fr-FR');
};

const getStatutType = (statut) => {
  const types = {
    brouillon: 'info',
    validee: 'warning',
    partiellement_payee: 'primary',
    payee: 'success',
    annulee: 'danger',
    impayee: 'danger',
    partielle: 'warning'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    brouillon: 'Brouillon',
    validee: 'Validée',
    partiellement_payee: 'Partielle',
    payee: 'Payée',
    annulee: 'Annulée',
    impayee: 'Impayée',
    partielle: 'Partielle'
  };
  return labels[statut] || statut;
};

const handleBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/fournisseurs');
  }
};

const handleEdit = () => {
  serverErrors.value = null;
  selectedFournisseur.value = props.fournisseur;
  showFournisseurModal.value = true;
};

const handleDelete = async () => {
  try {
    await ElMessageBox.confirm(
      'Êtes-vous sûr de vouloir supprimer ce fournisseur ? Cette action est irréversible.',
      'Confirmation de suppression',
      {
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        type: 'warning'
      }
    );

    const response = await fetchApi(`/api/fournisseurs/${props.fournisseur.id}`, {
      method: 'DELETE'
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Fournisseur supprimé avec succès');
      router.visit('/fournisseurs');
    } else {
      ElMessage.error(result.message || 'Erreur lors de la suppression');
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Erreur:', error);
      ElMessage.error('Erreur de connexion au serveur');
    }
  }
};

const handleNewFacture = () => {
  selectedFacture.value = null;
  showFactureModal.value = true;
};

const handleViewFacture = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}`);
};

const handleEditFacture = (facture) => {
  selectedFacture.value = facture;
  showFactureModal.value = true;
};

const handlePayFacture = (facture) => {
  router.visit(`/factures-fournisseurs/${facture.id}/regler`);
};

const getPaymentTagType = (row) => {
  if (row.montant_paye === 0) return 'info';
  if (row.montant_paye >= row.montant_ttc) return 'success';
  return 'warning';
};

const handleFactureAction = (command, facture) => {
  switch (command) {
    case 'view':
      handleViewFacture(facture);
      break;
    case 'pay':
      handlePayFacture(facture);
      break;
    case 'edit':
      handleEditFacture(facture);
      break;
    case 'etat_reglement':
      router.visit(`/factures-fournisseurs/${facture.id}`);
      break;
    case 'imputation':
      router.visit(`/factures-fournisseurs/${facture.id}?show_imputation=1`);
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer cette facture ?',
        'Confirmation',
        { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/factures-fournisseurs/${facture.id}`, { method: 'DELETE' });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Facture supprimée avec succès');
            router.reload({ only: ['factures', 'stats'] });
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

const handleFournisseurSuccess = async (fournisseurData) => {
  serverErrors.value = null;
  modalLoading.value = true;

  try {
    const response = await fetchApi(`/api/fournisseurs/${props.fournisseur.id}`, {
      method: 'PUT',
      body: fournisseurData
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Fournisseur modifié avec succès');
      showFournisseurModal.value = false;
      serverErrors.value = null;
      router.reload({ only: ['fournisseur', 'stats'] });
    } else {
      if (result.errors) {
        serverErrors.value = result.errors;
        const errorCount = Object.keys(result.errors).length;
        ElMessage.error({
          message: result.message || `${errorCount} erreur(s) de validation`,
          duration: 5000
        });
      } else {
        ElMessage.error(result.message || 'Une erreur est survenue');
      }
    }
  } catch (error) {
    console.error('Erreur:', error);
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
  }
};

// Règlements methods
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

const getReglementStatutType = (statut) => {
  const types = {
    en_attente: 'warning',
    valide: 'success',
    annule: 'danger'
  };
  return types[statut] || 'info';
};

// Règlements groupés par facture (même structure que le tableau principal)
const groupedReglements = computed(() => {
  const map = new Map();
  for (const r of props.reglements || []) {
    const factureId = r.facture?.id ?? `${r.facture?.numero || 'unknown'}`;
    if (!map.has(factureId)) {
      map.set(factureId, {
        key: `f-${factureId}`,
        facture: {
          id: r.facture?.id,
          numero: r.facture?.numero || r.facture?.numero_piece || '-',
          date: r.facture?.date || r.facture?.date_facture || null,
          montant_ttc: parseFloat(r.facture?.montant_ttc) || 0,
          reste_a_payer: parseFloat(r.facture?.reste_a_payer) || 0,
        },
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

const handleViewReglement = (reglement) => {
  if (reglement.facture?.id) {
    router.visit(`/factures-fournisseurs/${reglement.facture.id}`);
  }
};

const handleReglementActions = async (command, reglement) => {
  switch (command) {
    case 'mandat':
      openPdf(`/reglements-fournisseurs/${reglement.id}/mandat`, 'Bordereau de règlement');
      break;
    case 'imputation':
      openPdf(`/reglements-fournisseurs/${reglement.id}/imputation`, 'Imputation comptable');
      break;
    case 'edit':
      if (reglement.facture?.id) {
        router.visit(`/factures-fournisseurs/${reglement.facture.id}/regler?edit=${reglement.id}`);
      }
      break;
    case 'delete':
      try {
        await ElMessageBox.confirm(
          'Êtes-vous sûr de vouloir annuler ce règlement ? Le montant sera réintégré sur la facture.',
          'Confirmation',
          { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
        );
        const response = await fetchApi(`/api/reglements-fournisseurs/${reglement.id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
          ElMessage.success(result.message || 'Règlement annulé avec succès');
          router.reload({ only: ['reglements', 'statsReglements', 'factures', 'stats'] });
        } else {
          ElMessage.error(result.message || 'Erreur lors de l\'annulation');
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Erreur de connexion au serveur');
        }
      }
      break;
  }
};

const handleFactureSuccess = async (factureData) => {
  factureData.fournisseur_id = props.fournisseur.id;

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
      ElMessage.success(result.message || (isEdit ? 'Facture modifiée' : 'Facture créée'));
      showFactureModal.value = false;
      const factureId = result.data?.id || selectedFacture.value?.id;
      const hasImputation = !!result.has_imputation;
      router.reload({ only: ['factures', 'stats'] });

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
            ElMessage.success(j.message || 'Écritures générées');
            // Naviguer vers la fiche facture pour afficher l'offcanvas
            router.visit(`/factures-fournisseurs/${factureId}?show_imputation=1`);
            return;
          } else if (j.reason === 'missing') {
            ElMessageBox.alert(j.message, 'Imputations manquantes', { type: 'warning', confirmButtonText: 'OK' });
          } else if (j.reason === 'incomplete') {
            ElMessageBox.alert(j.message, 'Imputations incomplètes', { type: 'warning', confirmButtonText: 'OK' });
          } else {
            ElMessage.error(j.message || 'Erreur');
          }
        } catch {
          ElMessage.error('Erreur de connexion');
        }
      }
    } else {
      ElMessage.error(result.message || 'Une erreur est survenue');
    }
  } catch (error) {
    console.error('Erreur:', error);
    ElMessage.error('Erreur de connexion au serveur');
  }
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
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
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

/* Stats Row */
.stats-row {
  margin-bottom: 0;
}

.stat-card-item {
  border-radius: 8px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 20px;
  font-weight: bold;
  color: #1f2937;
  line-height: 1.2;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
  margin-top: 4px;
}

/* Main Card */
.main-card {
  border-radius: 8px;
}

.main-card :deep(.el-card__body) {
  padding: 0;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
}

.tab-badge {
  margin-left: 8px;
}

/* Info Sections */
.info-section {
  margin-bottom: 24px;
}

.info-section:last-child {
  margin-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

.compte-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.text-muted {
  color: #9ca3af;
}

.text-success {
  color: #10b981;
}

.text-danger {
  color: #ef4444;
}

.observations-text {
  white-space: pre-wrap;
  line-height: 1.6;
}

/* Factures Tab */
.factures-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.factures-title h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.factures-count {
  font-size: 14px;
  color: #6b7280;
}

.factures-table,
.reglements-table {
  border-radius: 8px;
  overflow: hidden;
}

.nowrap-cell {
  white-space: nowrap;
}

.expand-reglements {
  padding: 12px 16px;
  background: #fafafa;
}

.compte-cell,
.user-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.montant-reglement {
  color: var(--el-color-primary);
}

.reste-due {
  color: #f56c6c;
}

.reste-paid {
  color: #67c23a;
}

/* Reglements Tab */
.reglements-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.reglements-title h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.reglements-count {
  font-size: 14px;
  color: #6b7280;
}

:deep(.el-tabs--border-card) {
  border-radius: 8px;
  border: none;
  box-shadow: none;
}

:deep(.el-tabs__header) {
  background-color: #f9fafb;
  border-radius: 8px 8px 0 0;
  margin: 0;
}

:deep(.el-tabs__content) {
  padding: 24px;
}

:deep(.el-tabs__item) {
  padding: 12px 20px;
  height: auto;
}

:deep(.el-tabs__item.is-active) {
  background-color: white;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  width: 180px;
}

:deep(.el-table th) {
  background-color: #f9fafb;
  font-weight: 600;
}
</style>
