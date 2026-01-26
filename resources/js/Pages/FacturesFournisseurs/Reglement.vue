<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Règlement de Facture</h1>
          <p class="page-subtitle">Enregistrer un nouveau paiement</p>
        </div>
        <el-button @click="handleCancel">
          <el-icon><ArrowLeft /></el-icon>
          Retour
        </el-button>
      </div>

      <el-row :gutter="20">
        <!-- Left Column: Invoice Summary & Payment History -->
        <el-col :span="10">
          <!-- Invoice Summary Card -->
          <el-card shadow="never" class="info-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Document /></el-icon>
                <span>Informations Facture</span>
              </div>
            </template>

            <div class="info-grid">
              <div class="info-row">
                <span class="info-label">N° Facture :</span>
                <el-tag type="primary" size="large">{{ facture.numero }}</el-tag>
              </div>

              <div class="info-row">
                <span class="info-label">Fournisseur :</span>
                <div class="fournisseur-info">
                  <strong>{{ facture.fournisseur.nom }}</strong>
                  <span class="fournisseur-code">{{ facture.fournisseur.code }}</span>
                </div>
              </div>

              <div class="info-row">
                <span class="info-label">Date Facture :</span>
                <span>{{ formatDate(facture.date_facture) }}</span>
              </div>

              <div class="info-row">
                <span class="info-label">Référence :</span>
                <span>{{ facture.reference || '-' }}</span>
              </div>

              <el-divider />

              <div class="montants-grid">
                <div class="montant-row">
                  <span class="montant-label">Montant HT :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_ht) }}</span>
                </div>
                <div class="montant-row">
                  <span class="montant-label">TVA (18%) :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_tva) }}</span>
                </div>
                <div class="montant-row" v-if="facture.montant_aib > 0">
                  <span class="montant-label">AIB :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_aib) }}</span>
                </div>
                <div class="montant-row total-ttc">
                  <span class="montant-label"><strong>Total TTC :</strong></span>
                  <span class="montant-value"><strong>{{ formatMontant(facture.montant_ttc) }}</strong></span>
                </div>

                <el-divider style="margin: 12px 0" />

                <div class="montant-row montant-paye">
                  <span class="montant-label">Déjà payé :</span>
                  <span class="montant-value paye">{{ formatMontant(facture.montant_paye) }}</span>
                </div>

                <div class="montant-row reste-a-payer">
                  <span class="montant-label"><strong>Reste à payer :</strong></span>
                  <span class="montant-value reste">
                    <strong>{{ formatMontant(resteAPayer) }}</strong>
                  </span>
                </div>
              </div>
            </div>
          </el-card>

          <!-- Payment History Card -->
          <el-card shadow="never" class="history-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Clock /></el-icon>
                <span>Historique des Règlements</span>
              </div>
            </template>

            <el-timeline v-if="reglements.length > 0">
              <el-timeline-item
                v-for="reglement in reglements"
                :key="reglement.id"
                :timestamp="formatDate(reglement.date_reglement)"
                placement="top"
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
                      Réf: {{ reglement.reference }}
                    </div>
                    <div v-if="reglement.compte_bancaire">
                      <el-icon><CreditCard /></el-icon>
                      {{ reglement.compte_bancaire.libelle }}
                    </div>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>

            <el-empty v-else description="Aucun règlement enregistré" :image-size="80" />
          </el-card>
        </el-col>

        <!-- Right Column: New Payment Form -->
        <el-col :span="14">
          <el-card shadow="never" class="form-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Money /></el-icon>
                <span>Nouveau Règlement</span>
              </div>
            </template>

            <el-form
              ref="formRef"
              :model="form"
              :rules="rules"
              label-position="top"
              size="large"
              @submit.prevent="handleSubmit"
            >
              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Date de Règlement" prop="date_reglement">
                    <el-date-picker
                      v-model="form.date_reglement"
                      type="date"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      format="DD/MM/YYYY"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="Mode de Paiement" prop="mode_paiement">
                    <el-select
                      v-model="form.mode_paiement"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      @change="handleModeChange"
                    >
                      <el-option label="Espèces" value="especes" />
                      <el-option label="Chèque" value="cheque" />
                      <el-option label="Virement bancaire" value="virement" />
                      <el-option label="Carte bancaire" value="carte" />
                      <el-option label="Mobile Money" value="mobile_money" />
                    </el-select>
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Montant du Règlement" prop="montant">
                    <el-input-number
                      v-model="form.montant"
                      :min="0"
                      :max="resteAPayer"
                      :precision="0"
                      controls-position="right"
                      style="width: 100%"
                    >
                      <template #prefix>
                        <span style="color: #909399;">XOF</span>
                      </template>
                    </el-input-number>
                    <div class="form-hint">
                      Montant maximum : {{ formatMontant(resteAPayer) }}
                    </div>
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item>
                    <template #label>
                      <div style="display: flex; justify-content: space-between; width: 100%;">
                        <span>Montant Prédéfini</span>
                      </div>
                    </template>
                    <el-button-group style="width: 100%;">
                      <el-button
                        style="flex: 1"
                        @click="form.montant = resteAPayer / 2"
                      >
                        50%
                      </el-button>
                      <el-button
                        style="flex: 1"
                        type="success"
                        @click="form.montant = resteAPayer"
                      >
                        Solde total
                      </el-button>
                    </el-button-group>
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item
                v-if="showBankField"
                label="Compte Bancaire"
                prop="compte_bancaire_id"
              >
                <el-select
                  v-model="form.compte_bancaire_id"
                  filterable
                  placeholder="Sélectionner le compte"
                  style="width: 100%"
                >
                  <el-option
                    v-for="compte in comptesBancaires"
                    :key="compte.id"
                    :label="`${compte.banque} - ${compte.numero}`"
                    :value="compte.id"
                  >
                    <div class="compte-option">
                      <strong>{{ compte.banque }}</strong>
                      <span class="compte-numero">{{ compte.numero }}</span>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>

              <el-form-item label="Référence / N° Pièce" prop="reference">
                <el-input
                  v-model="form.reference"
                  :placeholder="getReferencePlaceholder()"
                  :prefix-icon="DocumentCopy"
                />
                <div class="form-hint">
                  {{ getReferenceHint() }}
                </div>
              </el-form-item>

              <el-form-item label="Notes / Remarques">
                <el-input
                  v-model="form.remarques"
                  type="textarea"
                  :rows="3"
                  placeholder="Informations complémentaires..."
                />
              </el-form-item>

              <!-- Payment Summary -->
              <el-alert
                type="success"
                :closable="false"
                style="margin-bottom: 20px"
              >
                <template #title>
                  <div class="payment-summary">
                    <div class="summary-row">
                      <span>Montant du règlement :</span>
                      <strong>{{ formatMontant(form.montant) }}</strong>
                    </div>
                    <div class="summary-row">
                      <span>Nouveau reste à payer :</span>
                      <strong :style="{ color: newReste === 0 ? '#67c23a' : '#f56c6c' }">
                        {{ formatMontant(newReste) }}
                      </strong>
                    </div>
                    <div v-if="newReste === 0" class="summary-row">
                      <el-icon color="#67c23a" :size="16"><SuccessFilled /></el-icon>
                      <span style="color: #67c23a; font-weight: 600;">
                        Facture totalement réglée
                      </span>
                    </div>
                  </div>
                </template>
              </el-alert>

              <!-- Action Buttons -->
              <div class="form-actions">
                <el-button size="large" @click="handleCancel">
                  Annuler
                </el-button>
                <el-button
                  type="primary"
                  size="large"
                  :loading="submitting"
                  native-type="submit"
                >
                  <el-icon v-if="!submitting"><Check /></el-icon>
                  Enregistrer le Règlement
                </el-button>
              </div>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  ArrowLeft,
  Document,
  Clock,
  Money,
  CreditCard,
  DocumentCopy,
  Check,
  SuccessFilled
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
  comptesBancaires: {
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
  { title: props.facture.numero, path: `/factures-fournisseurs/${props.facture.id}` },
  { title: 'Règlement', path: '' }
];

const resteAPayer = computed(() => {
  return props.facture.montant_ttc - props.facture.montant_paye;
});

const newReste = computed(() => {
  return Math.max(0, resteAPayer.value - (form.montant || 0));
});

const showBankField = computed(() => {
  return ['cheque', 'virement', 'carte'].includes(form.mode_paiement);
});

// State
const formRef = ref(null);
const submitting = ref(false);

const form = reactive({
  date_reglement: new Date(),
  mode_paiement: '',
  montant: resteAPayer.value,
  compte_bancaire_id: null,
  reference: '',
  remarques: ''
});

// Validation rules
const rules = {
  date_reglement: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  mode_paiement: [
    { required: true, message: 'Le mode de paiement est obligatoire', trigger: 'change' }
  ],
  montant: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value <= 0) {
          callback(new Error('Le montant doit être supérieur à 0'));
        } else if (value > resteAPayer.value) {
          callback(new Error('Le montant ne peut pas dépasser le reste à payer'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ],
  compte_bancaire_id: [
    {
      validator: (rule, value, callback) => {
        if (showBankField.value && !value) {
          callback(new Error('Le compte bancaire est obligatoire'));
        } else {
          callback();
        }
      },
      trigger: 'change'
    }
  ]
};

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
    carte: 'Carte bancaire',
    mobile_money: 'Mobile Money'
  };
  return labels[mode] || mode;
};

const getReferencePlaceholder = () => {
  const placeholders = {
    especes: 'Numéro de reçu (optionnel)',
    cheque: 'Numéro de chèque',
    virement: 'Référence du virement',
    carte: 'Numéro de transaction',
    mobile_money: 'Référence de la transaction'
  };
  return placeholders[form.mode_paiement] || 'Référence';
};

const getReferenceHint = () => {
  const hints = {
    especes: 'Numéro du reçu de caisse',
    cheque: 'Indiquez le numéro du chèque',
    virement: 'Code ou référence du virement bancaire',
    carte: 'Numéro de transaction',
    mobile_money: 'Référence de la transaction mobile'
  };
  return hints[form.mode_paiement] || '';
};

const handleModeChange = () => {
  if (!showBankField.value) {
    form.compte_bancaire_id = null;
  }
};

const handleCancel = () => {
  router.visit('/factures-fournisseurs');
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate((valid) => {
    if (valid) {
      submitting.value = true;

      // TODO: Replace with actual API call when backend is ready
      setTimeout(() => {
        ElMessage.success('Règlement enregistré avec succès');
        router.visit('/factures-fournisseurs');
      }, 1000);
    }
  });
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

.info-card,
.history-card,
.form-card {
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

.info-grid,
.montants-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row,
.montant-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-label,
.montant-label {
  font-size: 14px;
  color: #6b7280;
}

.fournisseur-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.fournisseur-code {
  font-size: 12px;
  color: #9ca3af;
}

.montant-value {
  font-family: 'Courier New', monospace;
  font-size: 15px;
  color: #1f2937;
}

.total-ttc .montant-value,
.reste-a-payer .montant-value {
  font-size: 18px;
  font-weight: 700;
}

.montant-paye .montant-value.paye {
  color: #059669;
}

.reste-a-payer .montant-value.reste {
  color: #dc2626;
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
  font-size: 16px;
  color: #059669;
}

.reglement-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
}

.reglement-details > div {
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}

.compte-option {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.compte-numero {
  font-size: 12px;
  color: #9ca3af;
}

.payment-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 14px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

:deep(.el-timeline-item__timestamp) {
  font-weight: 600;
  color: #6b7280;
}
</style>
