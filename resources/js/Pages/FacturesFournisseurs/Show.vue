<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <el-tag :type="getStatutType(facture.statut_paiement)" size="large">
            {{ getStatutLabel(facture.statut_paiement) }}
          </el-tag>
          <div>
            <h1 class="page-title">{{ facture.numero }}</h1>
            <p class="page-subtitle">
              Fournisseur: {{ facture.fournisseur.nom }} •
              Date: {{ formatDate(facture.date_facture) }}
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
                <el-dropdown-item
                  v-if="facture.statut_paiement !== 'payee' && facture.statut_paiement !== 'soldee'"
                  command="pay"
                  :icon="Money"
                >
                  Enregistrer un règlement
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="facture.statut_paiement !== 'soldee'"
                  command="marquer_soldee"
                  :icon="CircleCheck"
                  divided
                >
                  Marquer comme soldée
                </el-dropdown-item>
                <el-dropdown-item command="edit" :icon="Edit">
                  Modifier
                </el-dropdown-item>
                <el-dropdown-item command="duplicate" :icon="CopyDocument">
                  Dupliquer
                </el-dropdown-item>
                <el-dropdown-item command="print" :icon="Printer">
                  Imprimer
                </el-dropdown-item>
                <el-dropdown-item divided command="delete" :icon="Delete">
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
          <!-- Invoice Info Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Document /></el-icon>
                <span>Informations Générales</span>
              </div>
            </template>

            <el-descriptions :column="2" border>
              <el-descriptions-item label="N° Facture">
                <el-tag type="primary">{{ facture.numero }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Référence">
                {{ facture.reference || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="Date Facture">
                {{ formatDate(facture.date_facture) }}
              </el-descriptions-item>
              <el-descriptions-item label="Date Échéance">
                {{ facture.date_echeance ? formatDate(facture.date_echeance) : '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="Fournisseur" :span="2">
                <div class="fournisseur-info">
                  <div>
                    <strong>{{ facture.fournisseur.nom }}</strong>
                    <el-tag size="small" type="info" style="margin-left: 8px;">
                      {{ facture.fournisseur.code }}
                    </el-tag>
                  </div>
                  <div class="fournisseur-details">
                    <span v-if="facture.fournisseur.contact">
                      <el-icon><User /></el-icon>
                      {{ facture.fournisseur.contact }}
                    </span>
                    <span v-if="facture.fournisseur.telephone">
                      <el-icon><Phone /></el-icon>
                      {{ facture.fournisseur.telephone }}
                    </span>
                    <span v-if="facture.fournisseur.email">
                      <el-icon><Message /></el-icon>
                      {{ facture.fournisseur.email }}
                    </span>
                  </div>
                </div>
              </el-descriptions-item>
              <el-descriptions-item v-if="facture.remarques" label="Remarques" :span="2">
                {{ facture.remarques }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>

          <!-- Invoice Lines Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><List /></el-icon>
                <span>Lignes de Facture ({{ facture.lignes.length }})</span>
              </div>
            </template>

            <el-table :data="facture.lignes" border style="width: 100%">
              <el-table-column type="index" label="#" width="50" align="center" />

              <el-table-column label="Description" min-width="200">
                <template #default="{ row }">
                  <div>
                    <div class="ligne-description">{{ row.description }}</div>
                    <div v-if="row.compte_imputation" class="ligne-compte">
                      <el-icon><Notebook /></el-icon>
                      {{ row.compte_imputation.numero }} - {{ row.compte_imputation.libelle }}
                    </div>
                  </div>
                </template>
              </el-table-column>

              <el-table-column label="Qté" width="80" align="center">
                <template #default="{ row }">
                  {{ row.quantite }}
                </template>
              </el-table-column>

              <el-table-column label="P.U." width="130" align="right">
                <template #default="{ row }">
                  {{ formatMontant(row.prix_unitaire) }}
                </template>
              </el-table-column>

              <el-table-column label="TVA" width="80" align="center">
                <template #default="{ row }">
                  <el-tag v-if="row.taux_tva > 0" size="small" type="info">
                    {{ row.taux_tva }}%
                  </el-tag>
                  <span v-else class="text-muted">-</span>
                </template>
              </el-table-column>

              <el-table-column label="AIB" width="80" align="center">
                <template #default="{ row }">
                  <el-tag v-if="row.taux_aib > 0" size="small" type="warning">
                    {{ row.taux_aib }}%
                  </el-tag>
                  <span v-else class="text-muted">-</span>
                </template>
              </el-table-column>

              <el-table-column label="Escompte" width="100" align="center">
                <template #default="{ row }">
                  <el-tag v-if="row.taux_escompte > 0" size="small" type="success">
                    {{ row.taux_escompte }}%
                  </el-tag>
                  <span v-else class="text-muted">-</span>
                </template>
              </el-table-column>

              <el-table-column label="Montant HT" width="140" align="right">
                <template #default="{ row }">
                  <strong>{{ formatMontant(row.montant_ht) }}</strong>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>

        <!-- Right Column: Totals & Payments -->
        <el-col :span="8">
          <!-- Totals Card -->
          <el-card shadow="never" class="section-card totals-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Operation /></el-icon>
                <span>Récapitulatif</span>
              </div>
            </template>

            <div class="totals-grid">
              <div class="total-row">
                <span class="total-label">Total HT :</span>
                <span class="total-value">{{ formatMontant(facture.montant_ht) }}</span>
              </div>
              <div class="total-row">
                <span class="total-label">TVA (18%) :</span>
                <span class="total-value total-tva">{{ formatMontant(facture.montant_tva) }}</span>
              </div>
              <div class="total-row" v-if="facture.montant_aib > 0">
                <span class="total-label">AIB :</span>
                <span class="total-value total-aib">{{ formatMontant(facture.montant_aib) }}</span>
              </div>
              <div class="total-row" v-if="facture.montant_escompte > 0">
                <span class="total-label">Escompte :</span>
                <span class="total-value total-escompte">- {{ formatMontant(facture.montant_escompte) }}</span>
              </div>
              <el-divider style="margin: 8px 0" />
              <div class="total-row total-ttc-row">
                <span class="total-label"><strong>Total TTC :</strong></span>
                <span class="total-value total-ttc"><strong>{{ formatMontant(facture.montant_ttc) }}</strong></span>
              </div>

              <el-divider style="margin: 12px 0" />

              <div class="total-row">
                <span class="total-label">Montant payé :</span>
                <span class="total-value total-paye">{{ formatMontant(facture.montant_paye) }}</span>
              </div>

              <div class="total-row reste-row">
                <span class="total-label"><strong>Reste à payer :</strong></span>
                <span class="total-value total-reste">
                  <strong>{{ formatMontant(resteAPayer) }}</strong>
                </span>
              </div>

              <el-progress
                :percentage="pourcentagePaye"
                :color="progressColor"
                :stroke-width="12"
                style="margin-top: 16px"
              />

              <el-button
                v-if="facture.statut_paiement !== 'payee' && facture.statut_paiement !== 'soldee'"
                type="primary"
                size="large"
                style="width: 100%; margin-top: 16px;"
                @click="handleAction('pay')"
              >
                <el-icon><Money /></el-icon>
                Enregistrer un règlement
              </el-button>

              <el-button
                v-if="facture.statut_paiement !== 'soldee'"
                type="success"
                size="large"
                plain
                style="width: 100%; margin-top: 8px;"
                @click="handleAction('marquer_soldee')"
              >
                <el-icon><CircleCheck /></el-icon>
                Marquer comme soldée
              </el-button>
            </div>
          </el-card>

          <!-- Payment History Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Clock /></el-icon>
                <span>Historique Règlements ({{ reglements.length }})</span>
              </div>
            </template>

            <el-timeline v-if="reglements.length > 0">
              <el-timeline-item
                v-for="reglement in reglements"
                :key="reglement.id"
                :timestamp="formatDate(reglement.date_reglement)"
                placement="top"
                color="#67c23a"
              >
                <el-card class="reglement-item">
                  <div class="reglement-header">
                    <el-tag :type="getModeTagType(reglement.mode_paiement)" size="small">
                      {{ getModeLabel(reglement.mode_paiement) }}
                    </el-tag>
                    <strong class="reglement-montant">{{ formatMontant(reglement.montant) }}</strong>
                  </div>
                  <div class="reglement-details">
                    <div v-if="reglement.reference">
                      <el-icon><DocumentCopy /></el-icon>
                      {{ reglement.reference }}
                    </div>
                    <div v-if="reglement.compte_bancaire">
                      <el-icon><CreditCard /></el-icon>
                      {{ reglement.compte_bancaire.banque }}
                    </div>
                    <div v-if="reglement.remarques" class="reglement-remarques">
                      {{ reglement.remarques }}
                    </div>
                  </div>
                  <el-divider style="margin: 12px 0" />
                  <div class="reglement-actions">
                    <el-button size="small" :icon="Printer" @click="handlePrintRecu(reglement)">
                      Reçu
                    </el-button>
                    <el-button size="small" type="primary" :icon="DocumentCopy" @click="handlePrintMandat(reglement)">
                      Mandat
                    </el-button>
                    <el-button size="small" type="warning" :icon="Notebook" @click="handlePrintImputation(reglement)">
                      Imputation
                    </el-button>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>

            <el-empty v-else description="Aucun règlement" :image-size="60">
              <el-button type="primary" size="small" @click="handleAction('pay')">
                Ajouter un règlement
              </el-button>
            </el-empty>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  ArrowLeft,
  ArrowDown,
  Document,
  List,
  Operation,
  Clock,
  Money,
  Edit,
  Delete,
  Printer,
  CopyDocument,
  User,
  Phone,
  Message,
  Notebook,
  DocumentCopy,
  CreditCard,
  CircleCheck
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  facture: {
    type: Object,
    required: true
  },
  reglements: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: () => null
  }
});

// Computed
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Fournisseurs', path: '/factures-fournisseurs' },
  { title: props.facture.numero, path: '' }
];

const resteAPayer = computed(() => {
  return props.facture.montant_ttc - props.facture.montant_paye;
});

const pourcentagePaye = computed(() => {
  if (props.facture.montant_ttc === 0) return 0;
  return Math.round((props.facture.montant_paye / props.facture.montant_ttc) * 100);
});

const progressColor = computed(() => {
  if (pourcentagePaye.value === 100) return '#67c23a';
  if (pourcentagePaye.value >= 50) return '#e6a23c';
  return '#f56c6c';
});

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

const getStatutType = (statut) => {
  const types = {
    impayee: 'danger',
    partielle: 'warning',
    payee: 'success',
    soldee: 'info'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    impayee: 'Impayée',
    partielle: 'Partiellement Payée',
    payee: 'Payée',
    soldee: 'Soldée'
  };
  return labels[statut] || statut;
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

const handleBack = () => {
  router.visit('/factures-fournisseurs');
};

const handleAction = (command) => {
  switch (command) {
    case 'pay':
      router.visit(`/factures-fournisseurs/${props.facture.id}/regler`);
      break;
    case 'marquer_soldee':
      ElMessageBox.confirm(
        `Êtes-vous sûr de vouloir marquer cette facture comme soldée ?

Cette action clôturera définitivement la facture, même si le montant payé (${formatMontant(props.facture.montant_paye)}) ne correspond pas exactement au montant total (${formatMontant(props.facture.montant_ttc)}).`,
        'Marquer comme soldée',
        {
          confirmButtonText: 'Oui, solder la facture',
          cancelButtonText: 'Annuler',
          type: 'warning',
          dangerouslyUseHTMLString: false
        }
      ).then(() => {
        // TODO: Appel API pour marquer la facture comme soldée
        ElMessage.success('Facture marquée comme soldée avec succès');
        router.reload({ only: ['facture'] });
      }).catch(() => {
        // User cancelled
      });
      break;
    case 'edit':
      router.visit(`/factures-fournisseurs/${props.facture.id}/edit`);
      break;
    case 'duplicate':
      ElMessage.info('Duplication en cours de développement...');
      break;
    case 'print':
      ElMessage.info('Impression en cours de développement...');
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
      ).then(() => {
        ElMessage.success('Facture supprimée avec succès');
        router.visit('/factures-fournisseurs');
      });
      break;
  }
};

const handlePrintRecu = (reglement) => {
  window.open(`/reglements-fournisseurs/${reglement.id}/recu`, '_blank');
};

const handlePrintMandat = (reglement) => {
  window.open(`/reglements-fournisseurs/${reglement.id}/mandat`, '_blank');
};

const handlePrintImputation = (reglement) => {
  window.open(`/reglements-fournisseurs/${reglement.id}/imputation`, '_blank');
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

.fournisseur-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.fournisseur-details {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #6b7280;
}

.fournisseur-details span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.ligne-description {
  font-size: 14px;
  color: #1f2937;
  margin-bottom: 4px;
}

.ligne-compte {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}

.text-muted {
  color: #d1d5db;
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

.total-tva {
  color: #2563eb;
}

.total-aib {
  color: #d97706;
}

.total-escompte {
  color: #059669;
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

.reglement-item {
  box-shadow: none;
  border: 1px solid #e5e7eb;
}

.reglement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.reglement-montant {
  font-size: 15px;
  color: #059669;
}

.reglement-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}

.reglement-details > div {
  display: flex;
  align-items: center;
  gap: 6px;
}

.reglement-remarques {
  font-style: italic;
  color: #9ca3af;
}

.reglement-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-start;
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

:deep(.el-table) {
  font-size: 13px;
}

:deep(.el-table th) {
  background-color: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

:deep(.el-timeline-item__timestamp) {
  font-weight: 600;
  color: #6b7280;
}
</style>
