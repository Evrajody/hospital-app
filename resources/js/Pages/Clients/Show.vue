<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <div>
            <h1 class="page-title">{{ client.nom }}</h1>
            <p class="page-subtitle" v-if="client.type">{{ client.type }}</p>
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
              <div class="stat-icon" style="background: #dbeafe; color: #2563eb;">
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
              <div class="stat-icon" style="background: #e0e7ff; color: #4f46e5;">
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
              <div class="stat-icon" style="background: #d1fae5; color: #059669;">
                <el-icon :size="24"><SuccessFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.montant_paye) }}</div>
                <div class="stat-label">Encaissé</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover" class="stat-card-item">
            <div class="stat-card">
              <div class="stat-icon" style="background: #fee2e2; color: #ef4444;">
                <el-icon :size="24"><WarningFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.solde) }}</div>
                <div class="stat-label">Solde Créance</div>
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
                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><InfoFilled /></el-icon>
                    Informations Générales
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Code">
                      <el-tag type="info">{{ client.code || '-' }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="Nom">
                      <strong>{{ client.nom }}</strong>
                    </el-descriptions-item>
                    <el-descriptions-item label="Type de client">
                      <el-tag size="small">{{ client.type || '-' }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item label="Compte Comptable">
                      <div v-if="client.compte_comptable" class="compte-info">
                        <el-tag size="small">{{ client.compte_comptable.numero }}</el-tag>
                        <span>{{ client.compte_comptable.libelle }}</span>
                      </div>
                      <span v-else class="text-muted">Non assigné</span>
                    </el-descriptions-item>
                  </el-descriptions>
                </div>

                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><Phone /></el-icon>
                    Coordonnées
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Téléphone">
                      <el-link v-if="client.telephone" :href="`tel:${client.telephone}`" type="primary">
                        <el-icon><Phone /></el-icon>
                        {{ client.telephone }}
                      </el-link>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Email">
                      <el-link v-if="client.email" :href="`mailto:${client.email}`" type="primary">
                        <el-icon><Message /></el-icon>
                        {{ client.email }}
                      </el-link>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="Adresse">
                      {{ client.adresse || '-' }}
                    </el-descriptions-item>
                  </el-descriptions>
                </div>
              </el-col>

              <!-- Colonne Droite -->
              <el-col :xs="24" :lg="12">
                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><Wallet /></el-icon>
                    Informations Fiscales
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="IFU">
                      <el-tag v-if="client.ifu" type="warning">{{ client.ifu }}</el-tag>
                      <span v-else class="text-muted">-</span>
                    </el-descriptions-item>
                    <el-descriptions-item label="N° Assurance/Mutuelle">
                      {{ client.numero_assurance || '-' }}
                    </el-descriptions-item>
                  </el-descriptions>
                </div>

                <div class="info-section" v-if="client.notes">
                  <h3 class="section-title">
                    <el-icon><More /></el-icon>
                    Observations
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Notes">
                      <div class="observations-text">{{ client.notes }}</div>
                    </el-descriptions-item>
                  </el-descriptions>
                </div>

                <div class="info-section">
                  <h3 class="section-title">
                    <el-icon><Calendar /></el-icon>
                    Historique
                  </h3>
                  <el-descriptions :column="1" border>
                    <el-descriptions-item label="Créé le">
                      {{ client.created_at ? formatDateTime(client.created_at) : '-' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Modifié le">
                      {{ client.updated_at ? formatDateTime(client.updated_at) : '-' }}
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
              <el-button size="small" type="primary" @click="handleNewFacture">
                <el-icon><Document /></el-icon>
                Nouvelle Facture
              </el-button>
            </div>

            <el-table v-if="factures.length > 0" :data="pagedFactures" stripe border style="width: 100%" class="factures-table">
              <el-table-column label="Actions" width="100" fixed="left" align="center" resizable>
                <template #default="{ row }">
                  <el-dropdown trigger="click" @command="(cmd) => handleFactureAction(cmd, row)">
                    <el-button size="small" type="primary">
                      Actions <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                    </el-button>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item command="view" :icon="View">Voir</el-dropdown-item>
                        <el-dropdown-item v-if="row.statut !== 'payee'" command="regler" :icon="Money">Régler</el-dropdown-item>
                        <el-dropdown-item command="edit" :icon="Edit">Modifier</el-dropdown-item>
                        <el-dropdown-item divided command="delete" :icon="Delete">
                          <span style="color: #f56c6c">Supprimer</span>
                        </el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </template>
              </el-table-column>
              <el-table-column prop="reference" label="Référence" width="160" sortable resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell"><a class="ref-link" @click="handleViewFacture(row)">{{ row.reference }}</a></span>
                </template>
              </el-table-column>
              <el-table-column prop="date_facture" label="Date" width="120" sortable resizable>
                <template #default="{ row }">{{ formatDate(row.date_facture) }}</template>
              </el-table-column>
              <el-table-column label="Montant" width="160" align="right" sortable sort-by="montant" resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <div>{{ formatMontant(row.montant) }}</div>
                    <div v-if="row.ristourne > 0" style="font-size: 11px; color: #e6a23c;">Rist. -{{ formatMontant(row.ristourne) }}</div>
                  </span>
                </template>
              </el-table-column>
              <el-table-column label="Payé" width="160" align="right" sortable sort-by="montant_paye" resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell montant-paye">{{ formatMontant(row.montant_paye) }}</span>
                </template>
              </el-table-column>
              <el-table-column label="Reste" width="160" align="right" sortable sort-by="reste_a_payer" resizable>
                <template #default="{ row }">
                  <span :class="['nowrap-cell', 'montant-reste', row.reste_a_payer > 0 ? 'has-reste' : '']">{{ formatMontant(row.reste_a_payer) }}</span>
                </template>
              </el-table-column>
              <el-table-column prop="statut" label="Statut" width="160" align="center" sortable resizable>
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <el-tag :type="getStatutType(row.statut)" size="small">{{ getStatutLabel(row.statut) }}</el-tag>
                  </span>
                </template>
              </el-table-column>
            </el-table>
            <div v-if="factures.length > 0" class="pagination-bar">
              <el-pagination
                v-model:current-page="facturesPage"
                v-model:page-size="facturesPageSize"
                :page-sizes="[10, 20, 50, 100]"
                :total="factures.length"
                layout="total, sizes, prev, pager, next, jumper"
                background
              />
            </div>
            <el-empty v-else description="Aucune facture pour ce client" :image-size="100" />
          </el-tab-pane>

          <!-- Onglet Règlements -->
          <el-tab-pane name="reglements">
            <template #label>
              <span class="tab-label">
                <el-icon><Money /></el-icon>
                Règlements
                <el-badge v-if="reglements.length > 0" :value="reglements.length" class="tab-badge" />
              </span>
            </template>

            <div class="reglements-header">
              <div class="reglements-title">
                <h3>Historique des Règlements</h3>
                <span class="reglements-count">{{ reglements.length }} règlement(s) — Total: {{ formatMontant(statsReglements.total_reglements) }}</span>
              </div>
            </div>

            <el-table v-if="reglements.length > 0" :data="pagedReglements" stripe border style="width: 100%" row-key="key" class="reglements-table">
              <el-table-column type="expand" width="50">
                <template #default="{ row }">
                  <div class="expand-reglements">
                    <el-table :data="row.reglements" border size="small" class="inner-reglements-table">
                      <el-table-column label="Date" width="110">
                        <template #default="{ row: reg }">{{ formatDate(reg.date_reglement) }}</template>
                      </el-table-column>
                      <el-table-column label="Type" width="120">
                        <template #default="{ row: reg }">
                          <el-tag :type="reg.type_reglement_couleur === 'danger' ? 'danger' : reg.type_reglement_couleur === 'warning' ? 'warning' : reg.type_reglement_couleur === 'success' ? 'success' : 'primary'" size="small">
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
                      <el-table-column label="Réf. Chèque" width="140">
                        <template #default="{ row: reg }">
                          <span v-if="reg.reference_cheque">{{ reg.reference_cheque }}</span>
                          <span v-else class="text-muted">-</span>
                        </template>
                      </el-table-column>
                      <el-table-column label="Banque Dépôt" width="160">
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
                            <el-button :icon="View" size="small" type="primary" @click="handleViewReglement(reg)">Détails</el-button>
                            <el-dropdown @command="(cmd) => handleReglementActions(cmd, reg)">
                              <el-button :icon="More" size="small" />
                              <template #dropdown>
                                <el-dropdown-menu>
                                  <el-dropdown-item command="edit" :icon="Edit">Modifier</el-dropdown-item>
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
              <el-table-column label="N° Facture Client" width="170">
                <template #default="{ row }">
                  <span class="nowrap-cell">
                    <el-link type="primary" @click="handleViewFacture(row.facture)"><strong>{{ row.facture?.reference || '-' }}</strong></el-link>
                  </span>
                </template>
              </el-table-column>
              <el-table-column label="Date facture" width="120">
                <template #default="{ row }">{{ formatDate(row.facture?.date_facture) }}</template>
              </el-table-column>
              <el-table-column label="Montant facture" width="160" align="right">
                <template #default="{ row }"><span class="nowrap-cell">{{ formatMontant(row.facture?.montant) }}</span></template>
              </el-table-column>
              <el-table-column label="Total réglé" width="150" align="right">
                <template #default="{ row }"><span class="nowrap-cell"><strong class="montant-reglement">{{ formatMontant(row.total_montant_regle) }}</strong></span></template>
              </el-table-column>
              <el-table-column label="Reste à payer" width="150" align="right">
                <template #default="{ row }">
                  <span class="nowrap-cell" :class="(row.facture?.reste_a_payer || 0) > 0 ? 'reste-due' : 'reste-paid'">
                    <strong>{{ formatMontant(row.facture?.reste_a_payer || 0) }}</strong>
                  </span>
                </template>
              </el-table-column>
              <el-table-column label="Nb règlements" width="130" align="center">
                <template #default="{ row }"><el-tag size="small" type="info">{{ row.count }}</el-tag></template>
              </el-table-column>
              <el-table-column label="Actions" width="120" align="center" fixed="right">
                <template #default="{ row }">
                  <el-button :icon="Document" size="small" plain @click="handleViewFacture(row.facture)">Facture</el-button>
                </template>
              </el-table-column>
            </el-table>
            <div v-if="reglements.length > 0" class="pagination-bar">
              <el-pagination
                v-model:current-page="reglementsPage"
                v-model:page-size="reglementsPageSize"
                :page-sizes="[10, 20, 50, 100]"
                :total="groupedReglements.length"
                layout="total, sizes, prev, pager, next, jumper"
                background
              />
            </div>
            <el-empty v-else description="Aucun règlement pour ce client" :image-size="100" />
          </el-tab-pane>
        </el-tabs>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
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
  ArrowDown,
  View,
  More,
  Wallet,
  Calendar
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchApi } from '@/Composables/useFetch';

const props = defineProps({
  client: {
    type: Object,
    required: true
  },
  factures: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({ nombre_factures: 0, montant_total: 0, montant_paye: 0, solde: 0 })
  },
  reglements: {
    type: Array,
    default: () => []
  },
  statsReglements: {
    type: Object,
    default: () => ({ total_reglements: 0, nombre_reglements: 0 })
  },
  user: {
    type: Object,
    default: () => null
  }
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Clients', path: '/clients' },
  { title: props.client.nom, path: '' }
];

const activeTab = ref('informations');

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(montant || 0);
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
    non_payee: 'danger',
    partiellement_payee: 'warning',
    payee: 'success',
    impayee: 'danger',
    partielle: 'warning'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    non_payee: 'Non payée',
    partiellement_payee: 'Partielle',
    payee: 'Payée',
    impayee: 'Impayée',
    partielle: 'Partielle'
  };
  return labels[statut] || statut;
};

// Règlements groupés par facture
const groupedReglements = computed(() => {
  const map = new Map();
  for (const r of props.reglements || []) {
    const factureId = r.facture?.id ?? `${r.facture?.reference || 'unknown'}`;
    if (!map.has(factureId)) {
      map.set(factureId, {
        key: `f-${factureId}`,
        facture: r.facture || { reference: '-' },
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

// Pagination côté client (sans impact sur les données chargées / la génération)
const facturesPage = ref(1);
const facturesPageSize = ref(20);
const pagedFactures = computed(() =>
  props.factures.slice((facturesPage.value - 1) * facturesPageSize.value, facturesPage.value * facturesPageSize.value)
);

const reglementsPage = ref(1);
const reglementsPageSize = ref(20);
const pagedReglements = computed(() =>
  groupedReglements.value.slice((reglementsPage.value - 1) * reglementsPageSize.value, reglementsPage.value * reglementsPageSize.value)
);

// Si la liste rétrécit (suppression, changement de taille de page), on borne la page courante
watch(() => props.factures.length, () => {
  const maxPage = Math.max(1, Math.ceil(props.factures.length / facturesPageSize.value));
  if (facturesPage.value > maxPage) facturesPage.value = maxPage;
});
watch(() => groupedReglements.value.length, () => {
  const maxPage = Math.max(1, Math.ceil(groupedReglements.value.length / reglementsPageSize.value));
  if (reglementsPage.value > maxPage) reglementsPage.value = maxPage;
});

const handleBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/clients');
  }
};

const handleEdit = () => {
  router.visit('/clients');
};

const handleDelete = () => {
  ElMessageBox.confirm(
    'Êtes-vous sûr de vouloir supprimer ce client ?',
    'Confirmation',
    { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
  ).then(async () => {
    try {
      const response = await fetchApi(`/api/clients/${props.client.id}`, { method: 'DELETE' });
      const result = await response.json();
      if (result.success) {
        ElMessage.success('Client supprimé');
        router.visit('/clients');
      } else {
        ElMessage.error(result.message || 'Erreur');
      }
    } catch (e) {
      ElMessage.error('Erreur de connexion');
    }
  }).catch(() => {});
};

const handleNewFacture = () => {
  router.visit(`/factures-clients/create?client_id=${props.client.id}`);
};

const handleViewFacture = (facture) => {
  if (facture?.id) {
    router.visit(`/factures-clients/${facture.id}`);
  }
};

const handleRegler = (facture) => {
  router.visit(`/factures-clients/${facture.id}/regler`);
};

const handleFactureAction = (command, facture) => {
  switch (command) {
    case 'view':
      handleViewFacture(facture);
      break;
    case 'regler':
      handleRegler(facture);
      break;
    case 'edit':
      router.visit(`/factures-clients/${facture.id}`);
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer cette facture ?',
        'Confirmation',
        { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/factures-clients/${facture.id}`, { method: 'DELETE' });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Facture supprimée');
            router.reload({ only: ['factures', 'reglements', 'stats', 'statsReglements'] });
          } else {
            ElMessage.error(result.message || 'Erreur');
          }
        } catch (e) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
      break;
  }
};

const handleViewReglement = (reglement) => {
  if (reglement.facture?.id) {
    router.visit(`/factures-clients/${reglement.facture.id}`);
  }
};

const handleReglementActions = (command, reglement) => {
  switch (command) {
    case 'edit':
      if (reglement.facture?.id) {
        router.visit(`/factures-clients/${reglement.facture.id}`);
      }
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer ce règlement ?',
        'Confirmation',
        { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/reglements-clients/${reglement.id}`, { method: 'DELETE' });
          const result = await response.json();
          if (result.success) {
            ElMessage.success(result.message || 'Règlement supprimé');
            router.reload({ only: ['factures', 'reglements', 'stats', 'statsReglements'] });
          } else {
            ElMessage.error(result.message || 'Erreur');
          }
        } catch (e) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
      break;
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

/* Espace entre l'icône et le libellé des boutons d'action (icône trop collée au texte) */
.header-actions :deep(.el-button .el-icon),
.factures-header :deep(.el-button .el-icon) {
  margin-right: 6px;
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

.observations-text {
  white-space: pre-wrap;
  line-height: 1.6;
}

/* Factures / Règlements Tabs */
.factures-header,
.reglements-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.factures-title h3,
.reglements-title h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.factures-count,
.reglements-count {
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

.ref-link {
  color: var(--el-color-primary);
  cursor: pointer;
  font-weight: 600;
}

.montant-paye {
  color: #67c23a;
}

.montant-reste {
  color: #909399;
}

.montant-reste.has-reste {
  color: #f56c6c;
  font-weight: 600;
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

.expand-reglements {
  padding: 12px 16px;
  background: #fafafa;
}

.pagination-bar {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
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
