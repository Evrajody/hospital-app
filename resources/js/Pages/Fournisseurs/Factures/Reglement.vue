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
                <span class="info-label">N° Pièce :</span>
                <el-tag type="primary" size="large">{{ facture.numero }}</el-tag>
              </div>

              <div class="info-row">
                <span class="info-label">Fournisseur :</span>
                <div class="fournisseur-info">
                  <strong>{{ facture.fournisseur.nom }}</strong>
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
                  <span class="montant-label">Mt AIB :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_aib) }}</span>
                </div>
                <div class="montant-row total-ttc">
                  <span class="montant-label"><strong>Total TTC :</strong></span>
                  <span class="montant-value"><strong>{{ formatMontant(facture.montant_ttc) }}</strong></span>
                </div>
                <div class="montant-row" v-if="facture.montant_reduction > 0">
                  <span class="montant-label">{{ facture.type_reduction_libelle || 'AIB' }} ({{ facture.taux }}%) :</span>
                  <span class="montant-value" style="color: #f56c6c;">- {{ formatMontant(facture.montant_reduction) }}</span>
                </div>
                <div class="montant-row" v-if="facture.montant_net && facture.montant_net !== facture.montant_ttc">
                  <span class="montant-label"><strong>Net à payer :</strong></span>
                  <span class="montant-value"><strong>{{ formatMontant(facture.montant_net) }}</strong></span>
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
                    <div v-if="reglement.beneficiaire">
                      <el-icon><User /></el-icon>
                      {{ reglement.beneficiaire }}
                    </div>
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
                <el-col :span="6">
                  <el-form-item label="Année d'exercice" prop="annee_exercice">
                    <el-input
                      v-model="form.annee_exercice"
                      placeholder="2025"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6">
                  <el-form-item label="N° de ligne" prop="numero_ligne">
                    <el-input
                      v-model="form.numero_ligne"
                      placeholder="001"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6">
                  <el-form-item label="Date Règlement" prop="date_reglement">
                    <el-date-picker
                      v-model="form.date_reglement"
                      type="date"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      format="DD/MM/YYYY"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6">
                  <el-form-item label="Montant" prop="montant">
                    <el-input-number
                      v-model="form.montant"
                      :min="0"
                      :max="resteAPayer"
                      :precision="0"
                      controls-position="right"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :span="6">
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
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField">
                  <el-form-item label="Banque" prop="banque_id">
                    <el-select
                      v-model="form.banque_id"
                      filterable
                      placeholder="Sélectionner une banque"
                      style="width: 100%"
                      @change="handleBanqueChange"
                    >
                      <el-option
                        v-for="banque in banques"
                        :key="banque.id"
                        :label="banque.nom"
                        :value="banque.id"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="form.mode_paiement === 'virement' && form.banque_id">
                  <el-form-item label="Compte bancaire" prop="compte_bancaire_id">
                    <el-select
                      v-model="form.compte_bancaire_id"
                      filterable
                      placeholder="Sélectionner un compte"
                      style="width: 100%"
                    >
                      <el-option
                        v-for="compte in filteredComptes"
                        :key="compte.id"
                        :label="compte.numero_compte"
                        :value="compte.id"
                      >
                        <div class="compte-option">
                          <span>{{ compte.numero_compte }}</span>
                          <span class="compte-solde">Solde: {{ formatMontant(compte.solde) }}</span>
                        </div>
                      </el-option>
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField">
                  <el-form-item label="Référence" prop="reference">
                    <el-input
                      v-model="form.reference"
                      :placeholder="form.mode_paiement === 'cheque' ? 'N° du chèque' : 'Réf. virement'"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField">
                  <el-form-item label="Date Référence" prop="date_reference">
                    <el-date-picker
                      v-model="form.date_reference"
                      type="date"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      format="DD/MM/YYYY"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="B&eacute;n&eacute;ficiaire">
                    <el-input
                      v-model="form.beneficiaire"
                      placeholder="Nom du b&eacute;n&eacute;ficiaire du ch&egrave;que"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- Solde du compte sélectionné -->
              <el-alert
                v-if="selectedCompte"
                :type="selectedCompte.solde >= (form.montant || 0) ? 'success' : 'warning'"
                :closable="false"
                style="margin-bottom: 20px"
              >
                <template #title>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Solde du compte {{ selectedCompte.numero_compte }} :</span>
                    <strong>{{ formatMontant(selectedCompte.solde) }}</strong>
                  </div>
                </template>
              </el-alert>

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

    <!-- Modal Solde Insuffisant -->
    <el-dialog
      v-model="showInsufficientModal"
      title="Solde insuffisant"
      width="450px"
      :close-on-click-modal="false"
    >
      <div style="text-align: center; padding: 10px 0;">
        <el-icon :size="48" color="#E6A23C" style="margin-bottom: 16px;"><WarningFilled /></el-icon>
        <p style="font-size: 15px; margin-bottom: 12px;">
          Le solde du compte est insuffisant pour ce règlement.
        </p>
        <div style="background: #f5f7fa; padding: 16px; border-radius: 8px; margin-bottom: 12px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span>Solde actuel :</span>
            <strong>{{ formatMontant(insufficientData.solde) }}</strong>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span>Montant demandé :</span>
            <strong style="color: #F56C6C;">{{ formatMontant(insufficientData.montant) }}</strong>
          </div>
          <el-divider style="margin: 8px 0" />
          <div style="display: flex; justify-content: space-between;">
            <span>Nouveau solde :</span>
            <strong style="color: #F56C6C;">{{ formatMontant(insufficientData.solde - insufficientData.montant) }}</strong>
          </div>
        </div>
        <p style="font-size: 13px; color: #909399;">
          Voulez-vous quand même valider ce règlement ?
        </p>
      </div>
      <template #footer>
        <el-button @click="showInsufficientModal = false">Annuler</el-button>
        <el-button type="warning" :loading="submitting" @click="forceSubmit">
          Confirmer le règlement
        </el-button>
      </template>
    </el-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  ArrowLeft,
  Document,
  Clock,
  Money,
  CreditCard,
  DocumentCopy,
  User,
  Check,
  SuccessFilled,
  WarningFilled
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
  banques: {
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
  if (props.facture.reste_a_payer !== undefined && props.facture.reste_a_payer !== null) {
    return parseFloat(props.facture.reste_a_payer) || 0;
  }
  const montantNet = parseFloat(props.facture.montant_net) || parseFloat(props.facture.montant_ttc) || 0;
  const montantPaye = parseFloat(props.facture.montant_paye) || 0;
  return montantNet - montantPaye;
});

const newReste = computed(() => {
  return Math.max(0, resteAPayer.value - (form.montant || 0));
});

const showBankField = computed(() => {
  return ['cheque', 'virement'].includes(form.mode_paiement);
});

const filteredComptes = computed(() => {
  if (!form.banque_id) return [];
  const banque = props.banques.find(b => b.id === form.banque_id);
  return banque ? banque.comptes : [];
});

const selectedCompte = computed(() => {
  if (!form.compte_bancaire_id) return null;
  for (const banque of props.banques) {
    const compte = banque.comptes.find(c => c.id === form.compte_bancaire_id);
    if (compte) return compte;
  }
  return null;
});

// State
const formRef = ref(null);
const submitting = ref(false);
const showInsufficientModal = ref(false);
const insufficientData = reactive({ solde: 0, montant: 0 });

const form = reactive({
  annee_exercice: new Date().getFullYear().toString(),
  numero_ligne: String(props.reglements.length + 1).padStart(3, '0'),
  date_reglement: new Date(),
  montant: resteAPayer.value,
  mode_paiement: '',
  banque_id: null,
  compte_bancaire_id: null,
  reference: '',
  date_reference: null,
  beneficiaire: '',
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
  banque_id: [
    {
      validator: (rule, value, callback) => {
        if (showBankField.value && !value) {
          callback(new Error('La banque est obligatoire'));
        } else {
          callback();
        }
      },
      trigger: 'change'
    }
  ],
  compte_bancaire_id: [
    {
      validator: (rule, value, callback) => {
        if (form.mode_paiement === 'virement' && !value) {
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

const handleModeChange = () => {
  form.banque_id = null;
  form.compte_bancaire_id = null;
  form.reference = '';
  form.date_reference = null;
};

const handleBanqueChange = () => {
  form.compte_bancaire_id = null;
};

const handleCancel = () => {
  router.visit('/factures-fournisseurs');
};

const buildPayload = (forceInsufficient = false) => {
  const dateReglement = form.date_reglement instanceof Date
    ? form.date_reglement.toISOString().split('T')[0]
    : form.date_reglement;

  const selectedBanque = form.banque_id
    ? props.banques.find(b => b.id === form.banque_id)
    : null;

  return {
    facture_id: props.facture.id,
    annee_exercice: form.annee_exercice,
    numero_ligne: form.numero_ligne || null,
    date_reglement: dateReglement,
    montant: form.montant,
    mode_paiement: form.mode_paiement,
    reference: form.reference || null,
    date_reference: form.date_reference instanceof Date
      ? form.date_reference.toISOString().split('T')[0]
      : form.date_reference,
    beneficiaire: form.beneficiaire || null,
    banque: selectedBanque ? selectedBanque.nom : null,
    compte_bancaire_id: form.compte_bancaire_id || null,
    numero_compte_bancaire: selectedCompte.value ? selectedCompte.value.numero_compte : null,
    observations: form.remarques || null,
    force_insufficient_balance: forceInsufficient
  };
};

const submitPayment = async (forceInsufficient = false) => {
  submitting.value = true;

  try {
    const response = await fetch('/api/reglements-fournisseurs', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify(buildPayload(forceInsufficient))
    });

    const data = await response.json();

    if (data.success) {
      ElMessage.success(data.message || 'Règlement enregistré avec succès');
      router.visit(`/factures-fournisseurs/${props.facture.id}/regler`);
    } else if (data.insufficient_balance) {
      insufficientData.solde = data.solde_actuel;
      insufficientData.montant = data.montant_demande;
      showInsufficientModal.value = true;
    } else {
      ElMessage.error(data.message || 'Erreur lors de l\'enregistrement');
      if (data.errors) {
        Object.values(data.errors).flat().forEach(error => {
          ElMessage.warning(error);
        });
      }
    }
  } catch (error) {
    console.error('Error:', error);
    ElMessage.error('Erreur lors de l\'enregistrement du règlement');
  } finally {
    submitting.value = false;
  }
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (valid) {
      await submitPayment(false);
    }
  });
};

const forceSubmit = async () => {
  showInsufficientModal.value = false;
  await submitPayment(true);
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

.compte-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.compte-solde {
  font-size: 12px;
  color: #909399;
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
