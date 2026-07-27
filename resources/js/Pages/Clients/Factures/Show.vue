<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <el-tag :type="getStatutType(facture.statut)" size="large">
            {{ getStatutLabel(facture.statut) }}
          </el-tag>
          <div>
            <h1 class="page-title">{{ facture.reference }}</h1>
            <p class="page-subtitle">
              Client : {{ facture.client?.nom || '-' }} &bull;
              Date : {{ formatDate(facture.date_facture) }}
            </p>
          </div>
        </div>

        <div class="header-actions">
          <el-button @click="handleBack">
            <el-icon><ArrowLeft /></el-icon>
            Retour
          </el-button>
          <el-dropdown @command="handleAction" trigger="click">
            <el-button type="primary">
              Actions
              <el-icon class="el-icon--right"><ArrowDown /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-if="!estPayee && can('reglements-clients.creer')" command="regler" :icon="Money">
                  Enregistrer un r&#232;glement
                </el-dropdown-item>
                <el-dropdown-item v-if="estPayee && can('factures-clients.desolder')" command="annuler_solde" :icon="RefreshLeft" divided>
                  Annuler le solde
                </el-dropdown-item>
                <el-dropdown-item v-if="!estPayee && can('factures-clients.modifier')" command="edit" :icon="Edit" divided>
                  Modifier
                </el-dropdown-item>
                <!-- <el-dropdown-item command="print" :icon="Printer">
                  Imprimer
                </el-dropdown-item> -->
                <el-dropdown-item v-if="can('factures-clients.supprimer')" divided command="delete" :icon="Delete">
                  <span style="color: #f56c6c">Supprimer</span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>

      <el-row :gutter="20">
        <!-- Left Column: Invoice Details -->
        <el-col :span="16">
          <!-- Info Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Document /></el-icon>
                <span>Informations G&#233;n&#233;rales</span>
              </div>
            </template>

            <el-descriptions :column="2" border>
              <el-descriptions-item label="R&#233;f&#233;rence">
                <el-tag type="primary">{{ facture.reference }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Date">
                {{ formatDate(facture.date_facture) }}
              </el-descriptions-item>
              <el-descriptions-item label="Client" :span="2">
                <div class="client-info-detail">
                  <strong>{{ facture.client?.nom || '-' }}</strong>
                  <span v-if="facture.client?.telephone" class="client-tel">
                    <el-icon><Phone /></el-icon>
                    {{ facture.client.telephone }}
                  </span>
                </div>
              </el-descriptions-item>
              <el-descriptions-item label="Montant">
                <strong style="font-size: 16px;">{{ formatMontant(facture.montant) }}</strong>
              </el-descriptions-item>
              <el-descriptions-item v-if="facture.ristourne > 0" label="Ristourne">
                <span style="color: #e6a23c; font-weight: 600;">- {{ formatMontant(facture.ristourne) }}</span>
              </el-descriptions-item>
              <el-descriptions-item v-if="facture.ristourne > 0" label="Net &#224; payer" :span="2">
                <strong style="font-size: 16px; color: #059669;">{{ formatMontant(facture.net_a_payer) }}</strong>
              </el-descriptions-item>
              <el-descriptions-item label="Autres informations" :span="2">
                <span class="autres-informations">{{ facture.autres_informations || '-' }}</span>
              </el-descriptions-item>
              <el-descriptions-item label="Cr&#233;&#233;e le">
                {{ facture.created_at ? formatDateTime(facture.created_at) : '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="Date de solde">
                {{ facture.date_solde ? formatDate(facture.date_solde) : '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="Statut">
                <el-tag :type="getStatutType(facture.statut)" size="small">
                  {{ facture.soldee_manuellement ? 'Considérée comme soldée' : getStatutLabel(facture.statut) }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item v-if="facture.soldee_manuellement" label="Déficit constaté">
                <strong style="color: #e6a23c;">{{ formatMontant(facture.deficit_solde) }}</strong>
              </el-descriptions-item>
            </el-descriptions>
          </el-card>

          <!-- Historique des Règlements -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Clock /></el-icon>
                <span>Historique des R&#232;glements ({{ reglements.length }})</span>
              </div>
            </template>

            <el-timeline v-if="reglements.length > 0">
              <el-timeline-item
                v-for="r in reglements"
                :key="r.id"
                :timestamp="formatDate(r.date_reglement)"
                placement="top"
                color="#67c23a"
              >
                <el-card class="reglement-item" shadow="never">
                  <div class="reglement-header">
                    <el-tag size="small" :type="r.type_reglement_couleur || 'primary'">{{ r.type_reglement_libelle || 'Règlement' }}</el-tag>
                    <strong class="reglement-montant">{{ formatMontant(r.montant) }}</strong>
                  </div>
                  <div class="reglement-details">
                    <div v-if="Number(r.montant_rejet) > 0" class="montant-rejet">
                      <el-icon><WarningFilled /></el-icon>
                      Montant rejet&#233; : <strong>{{ formatMontant(r.montant_rejet) }}</strong>
                    </div>
                    <div v-if="r.institution"><el-icon><Document /></el-icon> {{ r.institution }}</div>
                    <div v-if="r.reference_cheque"><el-icon><DocumentCopy /></el-icon> R&#233;f : {{ r.reference_cheque }}</div>
                    <div v-if="r.banque_depot"><el-icon><CreditCard /></el-icon> D&#233;p&#244;t : {{ r.banque_depot.nom }}</div>
                    <div v-if="r.avance"><el-icon><Money /></el-icon> Avance : {{ r.avance.societe_emettrice }} — Chq {{ r.avance.numero_cheque }}</div>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>
            <el-empty v-else description="Aucun r&#232;glement enregistr&#233;" :image-size="70" />
          </el-card>
        </el-col>

        <!-- Right Column: Summary -->
        <el-col :span="8">
          <!-- Totals Card -->
          <el-card shadow="never" class="section-card totals-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Operation /></el-icon>
                <span>R&#233;capitulatif</span>
              </div>
            </template>

            <div class="totals-grid">
              <div class="total-row total-ttc-row">
                <span class="total-label"><strong>Montant :</strong></span>
                <span class="total-value total-ttc"><strong>{{ formatMontant(facture.montant) }}</strong></span>
              </div>

              <div v-if="facture.ristourne > 0" class="total-row">
                <span class="total-label">Ristourne :</span>
                <span class="total-value" style="color: #e6a23c;">- {{ formatMontant(facture.ristourne) }}</span>
              </div>

              <div v-if="facture.ristourne > 0" class="total-row">
                <span class="total-label"><strong>Net &#224; payer :</strong></span>
                <span class="total-value" style="color: #059669; font-size: 18px;"><strong>{{ formatMontant(facture.net_a_payer) }}</strong></span>
              </div>

              <el-divider style="margin: 12px 0" />

              <div class="total-row">
                <span class="total-label">Montant pay&#233; :</span>
                <span class="total-value total-paye">{{ formatMontant(facture.montant_paye) }}</span>
              </div>

              <div class="total-row reste-row">
                <span class="total-label"><strong>Solde :</strong></span>
                <span class="total-value total-reste" :class="{ 'soldee': estPayee && facture.reste_a_payer === 0, 'credit': facture.reste_a_payer < 0 }">
                  <strong>{{ formatMontant(facture.reste_a_payer) }}</strong>
                  <small v-if="facture.reste_a_payer < 0" style="display: block; font-size: 11px;">Trop-perçu</small>
                </span>
              </div>

              <el-progress
                :percentage="pourcentagePaye"
                :color="progressColor"
                :stroke-width="12"
                style="margin-top: 16px"
              />

              <div v-if="!estPayee" style="display: flex; gap: 8px; margin-top: 16px;">
                <el-button
                  v-if="can('reglements-clients.creer')"
                  type="primary"
                  size="default"
                  style="flex: 1;"
                  @click="handleAction('regler')"
                >
                  <el-icon><Money /></el-icon>
                  Enregistrer un règlement
                </el-button>

                <el-button
                  v-if="can('factures-clients.modifier')"
                  type="success"
                  size="default"
                  plain
                  style="flex: 1;"
                  @click="handleAction('solder')"
                >
                  <el-icon><CircleCheck /></el-icon>
                  Marquer comme soldée
                </el-button>
              </div>

              <el-alert
                v-if="estPayee"
                type="success"
                :closable="false"
                style="margin-top: 16px;"
              >
                <template #title>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <el-icon><CircleCheck /></el-icon>
                    Facture enti&#232;rement pay&#233;e
                  </div>
                </template>
              </el-alert>

              <el-button
                v-if="estPayee && can('factures-clients.desolder')"
                type="warning"
                size="default"
                plain
                style="width: 100%; margin-top: 12px;"
                @click="handleAction('annuler_solde')"
              >
                <el-icon><RefreshLeft /></el-icon>
                Annuler le solde et ajouter des règlements
              </el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Modal Facture -->
    <FactureClientModal
      v-model="showFactureModal"
      :facture="selectedFacture"
      :clients="clients"
      :prochaine-reference="prochaineReference"
      :loading="modalLoading"
      @success="handleFactureSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  ArrowLeft, ArrowDown, Document, Operation, Money,
  Edit, Delete, Printer, Phone, CircleCheck,
  Clock, CreditCard, DocumentCopy, RefreshLeft, WarningFilled
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FactureClientModal from '@/Components/Modals/FactureClientModal.vue';
import { fetchApi } from '@/Composables/useFetch';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
  facture: { type: Object, required: true },
  reglements: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  prochaineReference: { type: String, default: '' },
  user: { type: Object, default: () => null },
});

const showFactureModal = ref(false);
const selectedFacture = ref(null);
const modalLoading = ref(false);

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/factures-clients' },
  { title: props.facture.reference, path: '' }
];

const pourcentagePaye = computed(() => {
  const netAPayer = parseFloat(props.facture.net_a_payer) || parseFloat(props.facture.montant) || 0;
  if (netAPayer === 0) return 0;
  const paye = parseFloat(props.facture.montant_paye) || 0;
  return Math.min(Math.round((paye / netAPayer) * 100), 100);
});

const estPayee = computed(() => {
  return props.facture.statut === 'payee';
});

const progressColor = computed(() => {
  if (pourcentagePaye.value === 100) return '#67c23a';
  if (pourcentagePaye.value >= 50) return '#e6a23c';
  return '#f56c6c';
});

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

const formatDateTime = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

const getStatutType = (statut) => {
  const types = {
    non_payee: 'danger',
    partiellement_payee: 'warning',
    payee: 'success',
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    non_payee: 'Non pay\u00e9e',
    partiellement_payee: 'Partiellement pay\u00e9e',
    payee: 'Pay\u00e9e',
  };
  return labels[statut] || statut;
};

const handleBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/factures-clients');
  }
};

const handleAction = (command) => {
  switch (command) {
    case 'regler':
      router.visit(`/factures-clients/${props.facture.id}/regler`);
      break;
    case 'edit':
      selectedFacture.value = props.facture;
      showFactureModal.value = true;
      break;
    case 'print':
      ElMessage.info('Impression en cours de d\u00e9veloppement...');
      break;
    case 'solder': {
      const d = new Date();
      const aujourdhui = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
      ElMessageBox.prompt(
        'Date à laquelle la facture est marquée comme soldée (le reste à payer éventuel restera visible comme déficit). Aucun règlement n\'est créé.',
        'Marquer comme soldée',
        {
          confirmButtonText: 'Marquer comme soldée',
          cancelButtonText: 'Annuler',
          inputType: 'date',
          inputValue: aujourdhui,
          inputValidator: (val) => (val ? true : 'Veuillez saisir une date'),
          type: 'warning',
        }
      ).then(async ({ value }) => {
        try {
          const response = await fetchApi(`/api/factures-clients/${props.facture.id}/solder`, {
            method: 'POST',
            body: { date_solde: value },
          });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Facture marquée comme soldée');
            router.reload();
          } else {
            ElMessage.error(result.message || 'Erreur');
          }
        } catch (error) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
      break;
    }
    case 'annuler_solde': {
      ElMessageBox.confirm(
        "Le marquage « soldée » sera annulé et le statut recalculé d'après les règlements réels. Vous pourrez de nouveau enregistrer des règlements. Aucun règlement existant n'est supprimé.",
        'Annuler le solde',
        {
          confirmButtonText: 'Annuler le solde',
          cancelButtonText: 'Fermer',
          type: 'warning',
        }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/factures-clients/${props.facture.id}/desolder`, {
            method: 'POST',
          });
          const result = await response.json();
          if (result.success) {
            ElMessage.success(result.message || 'Solde annulé');
            router.reload();
          } else {
            ElMessage.error(result.message || "Erreur lors de l'annulation du solde");
          }
        } catch (error) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
      break;
    }
    case 'delete':
      ElMessageBox.confirm(
        '\u00cates-vous s\u00fbr de vouloir supprimer cette facture ?',
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/factures-clients/${props.facture.id}`, {
            method: 'DELETE',
          });
          const result = await response.json();
          if (result.success) {
            ElMessage.success('Facture supprim\u00e9e');
            router.visit('/factures-clients');
          } else {
            ElMessage.error(result.message || 'Erreur');
          }
        } catch (error) {
          ElMessage.error('Erreur de connexion');
        }
      }).catch(() => {});
      break;
  }
};

const handleFactureSuccess = async (data) => {
  modalLoading.value = true;

  try {
    const response = await fetchApi(`/api/factures-clients/${props.facture.id}`, {
      method: 'PUT',
      body: data,
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Facture modifi\u00e9e');
      showFactureModal.value = false;
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
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

.reglement-item { box-shadow: none; border: 1px solid #e5e7eb; }
.reglement-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.reglement-montant { font-size: 15px; color: #059669; }
.reglement-details { display: flex; flex-direction: column; gap: 4px; font-size: 12px; color: #6b7280; }
.reglement-details > div { display: flex; align-items: center; gap: 6px; }
.reglement-details .montant-rejet { color: #dc2626; }

.section-card {
  border-radius: 8px;
  margin-bottom: 20px;
}

.card-header-custom {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.client-info-detail {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.client-tel {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
}

.totals-card {
  background-color: #f9fafb;
}

.totals-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.total-label {
  font-size: 14px;
  color: #6b7280;
}

.total-value {
  font-size: 15px;
  color: #1f2937;
  font-family: 'Courier New', monospace;
}

.total-ttc-row,
.reste-row {
  padding: 8px 0;
}

.total-ttc {
  color: #059669;
  font-size: 20px;
}

.total-paye {
  color: #059669;
}

.total-reste {
  color: #dc2626;
  font-size: 18px;
}

.total-reste.soldee {
  color: #059669;
}

.total-reste.credit {
  color: #2563eb;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
}
</style>
