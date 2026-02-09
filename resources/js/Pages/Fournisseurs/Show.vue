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
              <el-button type="primary" @click="handleNewFacture">
                <el-icon><Plus /></el-icon>
                Nouvelle Facture
              </el-button>
            </div>

            <!-- Table des factures -->
            <el-table
              v-if="factures.length > 0"
              :data="factures"
              stripe
              style="width: 100%"
              class="factures-table"
            >
              <el-table-column prop="numero_piece" label="N° Pièce" width="140">
                <template #default="{ row }">
                  <el-link type="primary" @click="handleViewFacture(row)">
                    <strong>{{ row.numero_piece || row.numero }}</strong>
                  </el-link>
                </template>
              </el-table-column>

              <el-table-column prop="date" label="Date" width="110">
                <template #default="{ row }">
                  {{ formatDate(row.date || row.date_facture) }}
                </template>
              </el-table-column>

              <el-table-column prop="date_facture_bc" label="Date Fact/B.C." width="120">
                <template #default="{ row }">
                  {{ row.date_facture_bc ? formatDate(row.date_facture_bc) : '-' }}
                </template>
              </el-table-column>

              <el-table-column prop="reference_facture" label="Référence" width="130" />

              <el-table-column prop="libelle" label="Libellé" min-width="200" />

              <el-table-column prop="montant_ttc" label="Montant TTC" width="140" align="right">
                <template #default="{ row }">
                  <strong>{{ formatMontant(row.montant_ttc || row.montant_net) }}</strong>
                </template>
              </el-table-column>

              <el-table-column prop="montant_paye" label="Payé" width="130" align="right">
                <template #default="{ row }">
                  <span class="text-success">{{ formatMontant(row.montant_paye) }}</span>
                </template>
              </el-table-column>

              <el-table-column prop="reste_a_payer" label="Reste" width="130" align="right">
                <template #default="{ row }">
                  <span :class="{ 'text-danger': (row.reste_a_payer || 0) > 0 }">
                    {{ formatMontant(row.reste_a_payer || (row.montant_ttc - row.montant_paye)) }}
                  </span>
                </template>
              </el-table-column>

              <el-table-column prop="statut" label="Statut" width="140" align="center">
                <template #default="{ row }">
                  <el-tag :type="getStatutType(row.statut || row.statut_paiement)" size="small">
                    {{ getStatutLabel(row.statut || row.statut_paiement) }}
                  </el-tag>
                </template>
              </el-table-column>

              <el-table-column label="Actions" width="120" align="center" fixed="right">
                <template #default="{ row }">
                  <el-button-group size="small">
                    <el-button :icon="View" @click="handleViewFacture(row)" />
                    <el-button :icon="Edit" @click="handleEditFacture(row)" />
                  </el-button-group>
                </template>
              </el-table-column>
            </el-table>

            <!-- État vide -->
            <el-empty v-else description="Aucune facture pour ce fournisseur" :image-size="120">
              <el-button type="primary" @click="handleNewFacture">
                <el-icon><Plus /></el-icon>
                Créer une facture
              </el-button>
            </el-empty>
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

            <!-- Table des règlements -->
            <el-table
              v-if="reglements.length > 0"
              :data="reglements"
              stripe
              style="width: 100%"
              class="reglements-table"
            >
              <el-table-column prop="date_reglement" label="Date" width="120">
                <template #default="{ row }">
                  {{ formatDate(row.date_reglement) }}
                </template>
              </el-table-column>

              <el-table-column prop="numero_reglement" label="N° Règlement" width="150">
                <template #default="{ row }">
                  <strong>{{ row.numero_reglement }}</strong>
                </template>
              </el-table-column>

              <el-table-column prop="facture" label="Facture" width="140">
                <template #default="{ row }">
                  <el-link v-if="row.facture" type="primary" @click="handleViewFacture(row.facture)">
                    {{ row.facture.numero_piece || row.facture.numero }}
                  </el-link>
                  <span v-else class="text-muted">-</span>
                </template>
              </el-table-column>

              <el-table-column prop="mode_paiement" label="Mode" width="130">
                <template #default="{ row }">
                  <el-tag :type="getModeTagType(row.mode_paiement)" size="small">
                    {{ row.mode_paiement_libelle || getModeLabel(row.mode_paiement) }}
                  </el-tag>
                </template>
              </el-table-column>

              <el-table-column prop="reference" label="Référence" width="140">
                <template #default="{ row }">
                  {{ row.reference || '-' }}
                </template>
              </el-table-column>

              <el-table-column prop="compte_bancaire" label="Banque" width="140">
                <template #default="{ row }">
                  <span v-if="row.banque">{{ row.banque }}</span>
                  <span v-else class="text-muted">-</span>
                </template>
              </el-table-column>

              <el-table-column prop="montant" label="Montant" width="140" align="right">
                <template #default="{ row }">
                  <strong class="text-success">{{ formatMontant(row.montant) }}</strong>
                </template>
              </el-table-column>

              <el-table-column prop="statut" label="Statut" width="100" align="center">
                <template #default="{ row }">
                  <el-tag :type="getReglementStatutType(row.statut)" size="small">
                    {{ row.statut_libelle || row.statut }}
                  </el-tag>
                </template>
              </el-table-column>

              <el-table-column label="Actions" width="100" align="center" fixed="right">
                <template #default="{ row }">
                  <el-button-group size="small">
                    <el-button :icon="View" @click="handleViewReglement(row)" />
                    <el-dropdown @command="(cmd) => handleReglementActions(cmd, row)">
                      <el-button :icon="More" size="small" />
                      <template #dropdown>
                        <el-dropdown-menu>
                          <el-dropdown-item command="recu" :icon="DocumentCopy">
                            Imprimer le reçu
                          </el-dropdown-item>
                          <el-dropdown-item command="facture" :icon="Document">
                            Voir la facture
                          </el-dropdown-item>
                          <el-dropdown-item v-if="row.est_modifiable" divided command="annuler" :icon="Delete">
                            <span style="color: #f56c6c">Annuler</span>
                          </el-dropdown-item>
                        </el-dropdown-menu>
                      </template>
                    </el-dropdown>
                  </el-button-group>
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
        :types-reduction="typesReduction"
        @success="handleFactureSuccess"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
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
  DocumentCopy
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FournisseurModal from '@/Components/Modals/FournisseurModal.vue';
import FactureFournisseurModal from '@/Components/Modals/FactureFournisseurModal.vue';

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
  typesReduction: {
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
    style: 'currency',
    currency: 'XOF',
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
  router.visit('/fournisseurs');
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

    const response = await fetch(`/api/fournisseurs/${props.fournisseur.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
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

const handleFournisseurSuccess = async (fournisseurData) => {
  serverErrors.value = null;
  modalLoading.value = true;

  try {
    const response = await fetch(`/api/fournisseurs/${props.fournisseur.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(fournisseurData)
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

const handleViewReglement = (reglement) => {
  ElMessage.info(`Règlement ${reglement.numero_reglement}`);
  // Peut ouvrir un modal avec les détails si nécessaire
};

const handleReglementActions = async (command, reglement) => {
  switch (command) {
    case 'recu':
      window.open(`/reglements-fournisseurs/${reglement.id}/recu`, '_blank');
      break;
    case 'facture':
      if (reglement.facture) {
        router.visit(`/factures-fournisseurs/${reglement.facture.id}`);
      }
      break;
    case 'annuler':
      try {
        await ElMessageBox.confirm(
          'Êtes-vous sûr de vouloir annuler ce règlement ? Le montant sera réintégré sur la facture.',
          'Confirmation',
          {
            confirmButtonText: 'Annuler le règlement',
            cancelButtonText: 'Non, garder',
            type: 'warning'
          }
        );

        const response = await fetch(`/api/reglements-fournisseurs/${reglement.id}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
          }
        });

        const result = await response.json();

        if (result.success) {
          ElMessage.success(result.message || 'Règlement annulé avec succès');
          router.reload({ only: ['reglements', 'statsReglements', 'factures', 'stats'] });
        } else {
          ElMessage.error(result.message || 'Erreur lors de l\'annulation');
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Erreur:', error);
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
    const response = await fetch(url, {
      method: isEdit ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(factureData)
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Facture modifiée' : 'Facture créée'));
      showFactureModal.value = false;
      router.reload({ only: ['factures', 'stats'] });
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
