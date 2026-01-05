<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="facture-show-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Facture {{ facture.numero }}</h1>
          <p class="subtitle">{{ facture.client.nom }}</p>
        </div>
        <div class="header-actions">
          <el-tag :type="getStatutType()" size="large">{{ getStatutLabel() }}</el-tag>
        </div>
      </div>

      <el-row :gutter="20">
        <!-- Informations facture -->
        <el-col :xs="24" :lg="14">
          <el-card shadow="never" class="facture-details">
            <template #header>
              <div class="card-header">
                <h3>Détails de la Facture</h3>
              </div>
            </template>

            <div class="details-grid">
              <div class="detail-item">
                <span class="label">Client :</span>
                <span class="value">{{ facture.client.code }} - {{ facture.client.nom }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Numéro :</span>
                <span class="value"><strong>{{ facture.numero }}</strong></span>
              </div>
              <div class="detail-item">
                <span class="label">Date de facture :</span>
                <span class="value">{{ formatDate(facture.date_facture) }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Date d'échéance :</span>
                <span class="value">{{ formatDate(facture.date_echeance) }}</span>
              </div>
              <div class="detail-item full-width">
                <span class="label">Description :</span>
                <span class="value">{{ facture.description }}</span>
              </div>
              <div v-if="facture.observation" class="detail-item full-width">
                <span class="label">Observation :</span>
                <span class="value observation">{{ facture.observation }}</span>
              </div>
            </div>

            <el-divider />

            <div class="montants-section">
              <div class="montant-row">
                <span class="montant-label">Montant HT :</span>
                <span class="montant-value">{{ formatMontant(facture.montant_ht) }}</span>
              </div>
              <div class="montant-row">
                <span class="montant-label">TVA (18%) :</span>
                <span class="montant-value">{{ formatMontant(facture.montant_tva) }}</span>
              </div>
              <div class="montant-row total">
                <span class="montant-label">Montant TTC :</span>
                <span class="montant-value">{{ formatMontant(facture.montant_ttc) }}</span>
              </div>
              <div class="montant-row paye">
                <span class="montant-label">Total payé :</span>
                <span class="montant-value">{{ formatMontant(totalPaye) }}</span>
              </div>
              <div class="montant-row reste" :class="reste > 0 ? 'has-reste' : ''">
                <span class="montant-label">Reste à payer :</span>
                <span class="montant-value">{{ formatMontant(reste) }}</span>
              </div>
            </div>
          </el-card>

          <!-- Historique des règlements -->
          <el-card shadow="never" class="reglements-card">
            <template #header>
              <div class="card-header">
                <h3>Historique des Règlements</h3>
              </div>
            </template>

            <el-table
              :data="facture.reglements"
              stripe
              style="width: 100%"
              :default-sort="{ prop: 'date_reglement', order: 'descending' }"
            >
              <el-table-column prop="date_reglement" label="Date" width="120">
                <template #default="{ row }">
                  {{ formatDate(row.date_reglement) }}
                </template>
              </el-table-column>
              <el-table-column prop="mode_paiement" label="Mode" width="150">
                <template #default="{ row }">
                  {{ getModeLabel(row.mode_paiement) }}
                </template>
              </el-table-column>
              <el-table-column prop="reference" label="Référence" />
              <el-table-column prop="montant" label="Montant" align="right" width="150">
                <template #default="{ row }">
                  <span class="montant-paye">{{ formatMontant(row.montant) }}</span>
                </template>
              </el-table-column>
            </el-table>

            <div v-if="facture.reglements.length === 0" class="no-reglements">
              <el-empty description="Aucun règlement enregistré pour cette facture" />
            </div>
          </el-card>
        </el-col>

        <!-- Actions -->
        <el-col :xs="24" :lg="10">
          <el-card shadow="never" class="actions-card" v-if="reste > 0">
            <template #header>
              <div class="card-header">
                <h3>Enregistrer un Règlement</h3>
              </div>
            </template>

            <el-form
              ref="reglementFormRef"
              :model="reglementForm"
              :rules="reglementRules"
              label-position="top"
            >
              <el-form-item label="Date de règlement" prop="date_reglement">
                <el-date-picker
                  v-model="reglementForm.date_reglement"
                  type="date"
                  placeholder="Sélectionner la date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                  ref="dateReglementInput"
                />
              </el-form-item>

              <el-form-item label="Montant" prop="montant">
                <el-input
                  v-model.number="reglementForm.montant"
                  type="number"
                  placeholder="0"
                  :max="reste"
                >
                  <template #append>XOF</template>
                </el-input>
                <div class="form-hint">Maximum : {{ formatMontant(reste) }}</div>
              </el-form-item>

              <el-form-item label="Mode de paiement" prop="mode_paiement">
                <el-select
                  v-model="reglementForm.mode_paiement"
                  placeholder="Sélectionner le mode"
                  style="width: 100%"
                >
                  <el-option label="Espèces" value="especes" />
                  <el-option label="Chèque" value="cheque" />
                  <el-option label="Virement bancaire" value="virement" />
                  <el-option label="Carte bancaire" value="carte" />
                  <el-option label="Mobile Money" value="mobile_money" />
                </el-select>
              </el-form-item>

              <el-form-item v-if="showBanqueField" label="Banque" prop="banque">
                <el-select
                  v-model="reglementForm.banque"
                  placeholder="Sélectionner la banque"
                  style="width: 100%"
                >
                  <el-option label="BIBE - Banque Internationale du Bénin" value="BIBE" />
                  <el-option label="BOA - Bank of Africa" value="BOA" />
                  <el-option label="Ecobank Bénin" value="ECOBANK" />
                  <el-option label="SGBBE - Société Générale" value="SGBBE" />
                  <el-option label="CBAO - Compagnie Bancaire" value="CBAO" />
                </el-select>
              </el-form-item>

              <el-form-item v-if="showReferenceField" label="Référence" prop="reference">
                <el-input
                  v-model="reglementForm.reference"
                  placeholder="Numéro de chèque ou référence"
                />
              </el-form-item>

              <el-form-item label="Notes" prop="notes">
                <el-input
                  v-model="reglementForm.notes"
                  type="textarea"
                  :rows="3"
                  placeholder="Notes complémentaires"
                />
              </el-form-item>

              <el-button
                type="primary"
                :loading="loading"
                @click="handleReglement"
                style="width: 100%"
                size="large"
              >
                Enregistrer le règlement
              </el-button>
            </el-form>
          </el-card>

          <el-card shadow="never" class="actions-card" v-if="reste > 0 && totalPaye > 0">
            <template #header>
              <div class="card-header">
                <h3>Actions</h3>
              </div>
            </template>

            <el-button
              type="warning"
              :icon="CircleCheck"
              @click="handleMarquerSoldee"
              style="width: 100%"
            >
              Marquer comme soldée
            </el-button>
            <div class="form-hint">
              Cette action fermera la facture même s'il reste un solde impayé
            </div>
          </el-card>

          <el-card shadow="never" class="solde-success" v-if="reste === 0 && !facture.soldee">
            <el-result
              icon="success"
              title="Facture réglée"
              sub-title="Cette facture a été entièrement payée"
            />
          </el-card>

          <el-card shadow="never" class="solde-info" v-if="facture.soldee">
            <el-result
              icon="info"
              title="Facture soldée"
              sub-title="Cette facture a été marquée comme soldée manuellement"
            />
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { CircleCheck } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  facture: {
    type: Object,
    required: true
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/clients/factures' },
  { title: `Facture ${props.facture.numero}`, path: `/clients/factures/${props.facture.id}` }
];

// Form
const reglementFormRef = ref(null);
const loading = ref(false);

const reglementForm = ref({
  date_reglement: '',
  montant: 0,
  mode_paiement: '',
  banque: '',
  reference: '',
  notes: ''
});

// Computed
const totalPaye = computed(() => {
  return props.facture.reglements?.reduce((sum, r) => sum + r.montant, 0) || 0;
});

const reste = computed(() => {
  return props.facture.montant_ttc - totalPaye.value;
});

const showBanqueField = computed(() => {
  return ['cheque', 'virement', 'carte'].includes(reglementForm.value.mode_paiement);
});

const showReferenceField = computed(() => {
  return ['cheque', 'virement'].includes(reglementForm.value.mode_paiement);
});

// Validation rules
const reglementRules = {
  date_reglement: [
    { required: true, message: 'La date de règlement est obligatoire', trigger: 'change' }
  ],
  montant: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
    { type: 'number', min: 0, message: 'Le montant doit être positif', trigger: 'blur' }
  ],
  mode_paiement: [
    { required: true, message: 'Le mode de paiement est obligatoire', trigger: 'change' }
  ]
};

// Methods
const getStatutLabel = () => {
  if (props.facture.soldee) return 'Soldée';
  if (reste.value === 0) return 'Réglée';
  if (totalPaye.value > 0) return 'Partiellement réglée';
  return 'Non réglée';
};

const getStatutType = () => {
  if (props.facture.soldee) return 'info';
  if (reste.value === 0) return 'success';
  if (totalPaye.value > 0) return 'warning';
  return 'danger';
};

const getModeLabel = (mode) => {
  const labels = {
    especes: 'Espèces',
    cheque: 'Chèque',
    virement: 'Virement',
    carte: 'Carte bancaire',
    mobile_money: 'Mobile Money'
  };
  return labels[mode] || mode;
};

const handleReglement = () => {
  reglementFormRef.value.validate((valid) => {
    if (valid) {
      if (reglementForm.value.montant > reste.value) {
        ElMessage.error('Le montant ne peut pas dépasser le reste à payer');
        return;
      }

      loading.value = true;

      // TODO: Remplacer par l'envoi réel au backend
      setTimeout(() => {
        loading.value = false;
        ElMessage.success('Règlement enregistré avec succès');
        router.reload();
      }, 1000);
    } else {
      ElMessage.error('Veuillez corriger les erreurs du formulaire');
    }
  });
};

const handleMarquerSoldee = () => {
  ElMessageBox.confirm(
    `Cette action marquera la facture comme soldée. Le reste impayé (${formatMontant(reste.value)}) sera considéré comme abandonné. Continuer ?`,
    'Confirmation',
    {
      confirmButtonText: 'Confirmer',
      cancelButtonText: 'Annuler',
      type: 'warning'
    }
  ).then(() => {
    // TODO: Implémenter l'API
    ElMessage.success('Facture marquée comme soldée');
    router.reload();
  }).catch(() => {
    // Annulé
  });
};

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
</script>

<style scoped>
.facture-show-container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
}

.subtitle {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.facture-details {
  margin-bottom: 20px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-item .label {
  font-size: 12px;
  color: #909399;
  font-weight: 500;
}

.detail-item .value {
  font-size: 14px;
  color: #333;
}

.detail-item .value.observation {
  font-style: italic;
  color: #606266;
}

.montants-section {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
}

.montant-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}

.montant-row:last-child {
  border-bottom: none;
}

.montant-row.total {
  margin-top: 8px;
  padding-top: 16px;
  border-top: 2px solid #d1d5db;
  font-size: 16px;
  font-weight: bold;
  color: #2563eb;
}

.montant-row.paye {
  color: #059669;
  font-weight: 600;
}

.montant-row.reste {
  color: #666;
  font-weight: 700;
  font-size: 16px;
}

.montant-row.reste.has-reste {
  color: #dc2626;
}

.reglements-card {
  margin-bottom: 20px;
}

.no-reglements {
  padding: 20px;
  text-align: center;
}

.montant-paye {
  color: #059669;
  font-weight: 600;
}

.actions-card {
  margin-bottom: 20px;
}

.form-hint {
  font-size: 12px;
  color: #909399;
  margin-top: 8px;
}

.solde-success,
.solde-info {
  margin-bottom: 20px;
}

@media (max-width: 991px) {
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>
