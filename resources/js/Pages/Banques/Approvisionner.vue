<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Approvisionner un compte</h1>
          <p class="page-subtitle">Enregistrer un dépôt d'argent (entrée de fonds)</p>
        </div>
        <el-button @click="handleCancel">
          <el-icon><ArrowLeft /></el-icon>
          Retour
        </el-button>
      </div>

      <el-row :gutter="20">
        <!-- Left Column: Form -->
        <el-col :span="16">
          <el-card class="form-card" shadow="never">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Wallet /></el-icon>
                <span>Détails de l'approvisionnement</span>
              </div>
            </template>

            <el-form
              ref="formRef"
              :model="form"
              :rules="rules"
              label-width="200px"
              label-position="left"
              size="large"
            >
              <el-form-item label="Compte à approvisionner" prop="banque_id" required>
                <el-select
                  v-model="form.banque_id"
                  placeholder="Sélectionnez un compte"
                  filterable
                  style="width: 100%"
                  @change="handleBanqueChange"
                >
                  <el-option
                    v-for="banque in banques"
                    :key="banque.id"
                    :label="banque.nom"
                    :value="banque.id"
                  >
                    <div class="banque-option">
                      <div class="banque-option-header">
                        <el-icon :color="banque.type === 'especes' ? '#f59e0b' : '#2563eb'">
                          <component :is="banque.type === 'especes' ? Coin : CreditCard" />
                        </el-icon>
                        <span class="banque-option-nom">{{ banque.nom }}</span>
                      </div>
                      <div class="banque-option-details">
                        <span class="banque-option-numero">{{ banque.numero }}</span>
                        <span class="banque-option-solde">Solde: {{ formatMontant(banque.solde) }}</span>
                      </div>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>

              <el-form-item label="Date d'approvisionnement" prop="date" required>
                <el-date-picker
                  v-model="form.date"
                  type="date"
                  placeholder="Sélectionnez une date"
                  style="width: 100%"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                />
              </el-form-item>

              <el-form-item label="Montant" prop="montant" required>
                <el-input
                  v-model.number="form.montant"
                  type="number"
                  placeholder="0"
                  :min="0"
                >
                  <template #prepend>
                    <el-icon><Money /></el-icon>
                  </template>
                  <template #append>FCFA</template>
                </el-input>
                <div class="form-help">
                  Montant en Francs CFA (FCFA)
                </div>
              </el-form-item>

              <el-form-item label="Origine des fonds" prop="origine" required>
                <el-select
                  v-model="form.origine"
                  placeholder="Sélectionnez l'origine"
                  style="width: 100%"
                >
                  <el-option label="Recette journalière" value="recette" />
                  <el-option label="Subvention" value="subvention" />
                  <el-option label="Donation" value="donation" />
                  <el-option label="Virement interne" value="virement_interne" />
                  <el-option label="Remboursement" value="remboursement" />
                  <el-option label="Autre" value="autre" />
                </el-select>
              </el-form-item>

              <el-form-item label="Référence document" prop="reference">
                <el-input
                  v-model="form.reference"
                  placeholder="Ex: DEPOT-2025-001, Bordereau N°..."
                  maxlength="50"
                >
                  <template #prepend>
                    <el-icon><DocumentCopy /></el-icon>
                  </template>
                </el-input>
              </el-form-item>

              <el-form-item label="Description" prop="description" required>
                <el-input
                  v-model="form.description"
                  type="textarea"
                  :rows="3"
                  placeholder="Décrivez l'origine de cet approvisionnement..."
                  maxlength="500"
                  show-word-limit
                />
              </el-form-item>

              <el-form-item label="Pièce jointe" prop="piece_jointe">
                <el-upload
                  drag
                  :limit="1"
                  accept=".pdf,.jpg,.jpeg,.png"
                >
                  <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
                  <div class="el-upload__text">
                    Glissez un fichier ici ou <em>cliquez pour parcourir</em>
                  </div>
                  <template #tip>
                    <div class="el-upload__tip">
                      PDF, JPG ou PNG, max 5 Mo
                    </div>
                  </template>
                </el-upload>
              </el-form-item>

              <el-form-item>
                <div class="form-actions">
                  <el-button @click="handleCancel" size="large">
                    Annuler
                  </el-button>
                  <el-button
                    type="primary"
                    @click="handleSubmit"
                    size="large"
                    :loading="submitting"
                  >
                    <el-icon><Check /></el-icon>
                    Enregistrer l'approvisionnement
                  </el-button>
                </div>
              </el-form-item>
            </el-form>
          </el-card>
        </el-col>

        <!-- Right Column: Summary -->
        <el-col :span="8">
          <!-- Selected Banque Info -->
          <el-card v-if="selectedBanque" class="summary-card" shadow="never">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><InfoFilled /></el-icon>
                <span>Informations du compte</span>
              </div>
            </template>

            <div class="banque-summary">
              <div class="banque-summary-header">
                <el-icon :size="32" :color="selectedBanque.type === 'especes' ? '#f59e0b' : '#2563eb'">
                  <component :is="selectedBanque.type === 'especes' ? Coin : CreditCard" />
                </el-icon>
                <div>
                  <div class="banque-summary-nom">{{ selectedBanque.nom }}</div>
                  <div class="banque-summary-numero">{{ selectedBanque.numero }}</div>
                </div>
              </div>

              <el-divider />

              <div class="solde-info">
                <div class="solde-label">Solde actuel</div>
                <div class="solde-value">{{ formatMontant(selectedBanque.solde) }}</div>
              </div>

              <div v-if="form.montant > 0" class="nouveau-solde-info">
                <div class="nouveau-solde-label">
                  <el-icon><TopRight /></el-icon>
                  Nouveau solde après dépôt
                </div>
                <div class="nouveau-solde-value">
                  {{ formatMontant(selectedBanque.solde + form.montant) }}
                </div>
                <div class="difference">
                  <el-tag type="success" size="small">
                    +{{ formatMontant(form.montant) }}
                  </el-tag>
                </div>
              </div>

              <el-descriptions :column="1" size="small" style="margin-top: 16px">
                <el-descriptions-item label="Type">
                  <el-tag :type="selectedBanque.type === 'especes' ? 'warning' : 'primary'" size="small">
                    {{ selectedBanque.type === 'especes' ? 'Caisse Espèces' : 'Compte Bancaire' }}
                  </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="Compte comptable">
                  <span class="compte-numero">{{ selectedBanque.compte_comptable }}</span>
                </el-descriptions-item>
              </el-descriptions>
            </div>
          </el-card>

          <!-- Alert Info -->
          <el-alert
            type="info"
            :closable="false"
            show-icon
            style="margin-top: 20px"
          >
            <template #title>
              <strong>Note importante</strong>
            </template>
            <div style="font-size: 13px; line-height: 1.6;">
              L'approvisionnement augmentera le solde du compte sélectionné. Assurez-vous que tous les documents justificatifs sont disponibles.
            </div>
          </el-alert>
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
  Check,
  Wallet,
  Money,
  CreditCard,
  Coin,
  DocumentCopy,
  UploadFilled,
  InfoFilled,
  TopRight
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  banques: {
    type: Array,
    default: () => []
  },
  banque_preselection_id: {
    type: Number,
    default: null
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State
const formRef = ref(null);
const submitting = ref(false);

const form = reactive({
  banque_id: props.banque_preselection_id || null,
  date: new Date().toISOString().split('T')[0],
  montant: 0,
  origine: '',
  reference: '',
  description: '',
  piece_jointe: null
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Banques', path: '/banques' },
  { title: 'Approvisionner', path: '' }
];

// Computed
const selectedBanque = computed(() => {
  if (!form.banque_id) return null;
  return props.banques.find(b => b.id === form.banque_id);
});

// Validation rules
const rules = {
  banque_id: [
    { required: true, message: 'Veuillez sélectionner un compte', trigger: 'change' }
  ],
  date: [
    { required: true, message: 'La date est requise', trigger: 'blur' }
  ],
  montant: [
    { required: true, message: 'Le montant est requis', trigger: 'blur' },
    { type: 'number', min: 1, message: 'Le montant doit être supérieur à 0', trigger: 'blur' }
  ],
  origine: [
    { required: true, message: 'L\'origine des fonds est requise', trigger: 'change' }
  ],
  description: [
    { required: true, message: 'La description est requise', trigger: 'blur' },
    { min: 10, message: 'La description doit contenir au moins 10 caractères', trigger: 'blur' }
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

const handleBanqueChange = () => {
  // Trigger reactivity
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    const valid = await formRef.value.validate();
    if (!valid) return;

    submitting.value = true;

    // TODO: Appel API pour enregistrer l'approvisionnement
    setTimeout(() => {
      ElMessage.success('Approvisionnement enregistré avec succès');
      router.visit('/banques');
    }, 1000);
  } catch (error) {
    console.error('Validation failed:', error);
  } finally {
    submitting.value = false;
  }
};

const handleCancel = () => {
  router.visit('/banques');
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

.form-card,
.summary-card {
  border-radius: 8px;
}

.card-header-custom {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.form-help {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

.banque-option {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.banque-option-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.banque-option-nom {
  font-weight: 600;
  color: #1f2937;
}

.banque-option-details {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #6b7280;
  padding-left: 28px;
}

.banque-option-numero {
  font-family: 'Courier New', monospace;
}

.banque-option-solde {
  color: #059669;
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
  width: 100%;
}

/* Summary Card */
.banque-summary {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.banque-summary-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.banque-summary-nom {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 4px;
}

.banque-summary-numero {
  font-size: 12px;
  font-family: 'Courier New', monospace;
  color: #6b7280;
}

.solde-info {
  text-align: center;
  padding: 16px;
  background: #f3f4f6;
  border-radius: 8px;
}

.solde-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.solde-value {
  font-size: 24px;
  font-weight: 700;
  color: #2563eb;
  font-family: 'Courier New', monospace;
}

.nouveau-solde-info {
  text-align: center;
  padding: 16px;
  background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%);
  border-radius: 8px;
  border: 2px solid #059669;
}

.nouveau-solde-label {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 12px;
  color: #065f46;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
  font-weight: 600;
}

.nouveau-solde-value {
  font-size: 28px;
  font-weight: 700;
  color: #059669;
  font-family: 'Courier New', monospace;
  margin-bottom: 8px;
}

.difference {
  display: flex;
  justify-content: center;
}

.compte-numero {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2563eb;
  font-size: 13px;
}

:deep(.el-card__body) {
  padding: 24px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
}

:deep(.el-upload-dragger) {
  padding: 20px;
}
</style>
