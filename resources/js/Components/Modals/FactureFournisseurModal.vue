<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier la Facture' : 'Nouvelle Facture Fournisseur'"
    width="950px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @closed="handleClosed"
    class="facture-modal"
  >
    <!-- Affichage des erreurs -->
    <el-alert
      v-if="validationErrors.length > 0"
      type="error"
      :closable="true"
      class="errors-alert"
      @close="clearErrors"
    >
      <template #title>
        <div class="errors-title">
          <el-icon><WarningFilled /></el-icon>
          Veuillez corriger les erreurs suivantes :
        </div>
      </template>
      <ul class="errors-list">
        <li v-for="(error, index) in validationErrors" :key="index">
          <strong>{{ error.field }}</strong>: {{ error.message }}
        </li>
      </ul>
    </el-alert>

    <!-- Info Fournisseur -->
    <el-alert
      v-if="fournisseur"
      type="info"
      :closable="false"
      class="fournisseur-info"
    >
      <template #title>
        <div class="fournisseur-title">
          <el-icon><OfficeBuilding /></el-icon>
          <span>Fournisseur : <strong>{{ fournisseur.nom }}</strong></span>
          <el-tag v-if="fournisseur.compte_comptable" size="small" type="info" class="ml-2">
            {{ fournisseur.compte_comptable.numero }}
          </el-tag>
        </div>
      </template>
    </el-alert>

    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      size="large"
      @submit.prevent="handleSubmit"
    >
      <el-tabs v-model="activeTab" type="border-card">
        <!-- Onglet 1: Informations Générales -->
        <el-tab-pane name="general" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Document /></el-icon>
              Informations
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- N° Pièce -->
              <el-col :span="8">
                <el-form-item label="N° Pièce" prop="numero_piece">
                  <el-input
                    v-model="form.numero_piece"
                    placeholder="Auto-généré"
                    :prefix-icon="Document"
                  >
                    <template #append>
                      <el-button :icon="Refresh" @click="genererNumeroPiece" title="Générer automatiquement" />
                    </template>
                  </el-input>
                  <div class="form-hint">Laissez vide pour génération automatique</div>
                </el-form-item>
              </el-col>

              <!-- Date -->
              <el-col :span="8">
                <el-form-item prop="date">
                  <template #label>
                    <span>Date <span class="required-star">*</span></span>
                  </template>
                  <el-date-picker
                    v-model="form.date"
                    type="date"
                    placeholder="Sélectionner"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>

              <!-- Référence Facture -->
              <el-col :span="8">
                <el-form-item label="Référence / N° B.C" prop="reference_facture">
                  <el-input
                    v-model="form.reference_facture"
                    placeholder="N° bon de commande"
                    :prefix-icon="DocumentCopy"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="20">
              <!-- Imputation -->
              <el-col :span="12">
                <el-form-item label="Imputation (Compte de charges)" prop="imputation_id">
                  <el-select
                    v-model="form.imputation_id"
                    placeholder="Sélectionner une imputation"
                    filterable
                    clearable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="imputation in imputations"
                      :key="imputation.id"
                      :label="`${imputation.code || imputation.numero} - ${imputation.libelle}`"
                      :value="imputation.id"
                    >
                      <div class="select-option">
                        <el-tag size="small" type="info">{{ imputation.code || imputation.numero }}</el-tag>
                        <span>{{ imputation.libelle }}</span>
                      </div>
                    </el-option>
                  </el-select>
                  <div class="form-hint">Compte de charges (classe 6)</div>
                </el-form-item>
              </el-col>

              <!-- Compte -->
              <el-col :span="12">
                <el-form-item label="Compte Comptable" prop="compte_id">
                  <el-select
                    v-model="form.compte_id"
                    placeholder="Sélectionner un compte"
                    filterable
                    clearable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="compte in comptes"
                      :key="compte.id"
                      :label="`${compte.numero || compte.code} - ${compte.libelle}`"
                      :value="compte.id"
                    >
                      <div class="select-option">
                        <el-tag size="small" type="info">{{ compte.numero || compte.code }}</el-tag>
                        <span>{{ compte.libelle }}</span>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="20">
              <!-- Libellé -->
              <el-col :span="24">
                <el-form-item prop="libelle">
                  <template #label>
                    <span>Libellé de la facture <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model="form.libelle"
                    placeholder="Description de la facture"
                    :prefix-icon="Edit"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="20">
              <!-- Date Échéance -->
              <el-col :span="12">
                <el-form-item label="Date d'échéance" prop="date_echeance">
                  <el-date-picker
                    v-model="form.date_echeance"
                    type="date"
                    placeholder="Sélectionner"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>

              <!-- TVA -->
              <el-col :span="12">
                <el-form-item label="Assujetti à la TVA" prop="assujetti_tva">
                  <div class="switch-container">
                    <el-switch
                      v-model="form.assujetti_tva"
                      active-text="Oui"
                      inactive-text="Non"
                      inline-prompt
                      style="--el-switch-on-color: #13ce66; --el-switch-off-color: #909399"
                    />
                    <el-input-number
                      v-if="form.assujetti_tva"
                      v-model="form.taux_tva"
                      :min="0"
                      :max="100"
                      :step="0.5"
                      size="small"
                      style="width: 100px; margin-left: 12px"
                    />
                    <span v-if="form.assujetti_tva" class="tva-label">%</span>
                  </div>
                </el-form-item>
              </el-col>
            </el-row>
          </div>
        </el-tab-pane>

        <!-- Onglet 2: Montants -->
        <el-tab-pane name="montants" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Money /></el-icon>
              Montants
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- Montant Facture -->
              <el-col :span="8">
                <el-form-item prop="montant_facture">
                  <template #label>
                    <span>Montant Facture <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model.number="form.montant_facture"
                    type="number"
                    placeholder="0"
                    :prefix-icon="Money"
                  >
                    <template #append>XOF</template>
                  </el-input>
                </el-form-item>
              </el-col>

              <!-- Montant M.O. -->
              <el-col :span="8">
                <el-form-item label="Montant M.O." prop="montant_mo">
                  <el-input
                    v-model.number="form.montant_mo"
                    type="number"
                    placeholder="0"
                  >
                    <template #append>XOF</template>
                  </el-input>
                  <div class="form-hint">Base de calcul du taux</div>
                </el-form-item>
              </el-col>

              <!-- Avoir -->
              <el-col :span="8">
                <el-form-item label="Avoir" prop="avoir">
                  <el-input
                    v-model.number="form.avoir"
                    type="number"
                    placeholder="0"
                  >
                    <template #append>XOF</template>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>

            <el-divider content-position="left">Réductions</el-divider>

            <el-row :gutter="20">
              <!-- Type de réduction -->
              <el-col :span="8">
                <el-form-item label="Type de réduction" prop="type_reduction">
                  <el-select
                    v-model="form.type_reduction"
                    placeholder="Sélectionner"
                    clearable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="type in typesReduction"
                      :key="type.value"
                      :label="type.label"
                      :value="type.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>

              <!-- Taux -->
              <el-col :span="8">
                <el-form-item label="Taux (%)" prop="taux">
                  <el-input-number
                    v-model="form.taux"
                    :min="0"
                    :max="100"
                    :step="0.5"
                    :precision="2"
                    style="width: 100%"
                  />
                  <div class="form-hint">Appliqué sur Montant M.O.</div>
                </el-form-item>
              </el-col>

              <!-- Montant calculé -->
              <el-col :span="8">
                <el-form-item label="Montant Réduction">
                  <el-input
                    :model-value="formatMontant(calculMontantReduction)"
                    disabled
                    readonly
                    class="calculated-field"
                  >
                    <template #append>XOF</template>
                  </el-input>
                  <div class="form-hint">Calculé automatiquement</div>
                </el-form-item>
              </el-col>
            </el-row>

            <!-- Récapitulatif -->
            <el-card class="recap-card" shadow="never">
              <template #header>
                <div class="recap-header">
                  <el-icon><TrendCharts /></el-icon>
                  <span>Récapitulatif des Montants</span>
                </div>
              </template>

              <el-row :gutter="20">
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">Montant HT</span>
                    <span class="recap-value">{{ formatMontant(calculMontantHT) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">TVA ({{ form.assujetti_tva ? form.taux_tva : 0 }}%)</span>
                    <span class="recap-value">{{ formatMontant(calculMontantTVA) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">Montant TTC</span>
                    <span class="recap-value recap-ttc">{{ formatMontant(calculMontantTTC) }}</span>
                  </div>
                </el-col>
              </el-row>

              <el-divider />

              <el-row :gutter="20">
                <el-col :span="12">
                  <div class="recap-item">
                    <span class="recap-label">Réduction</span>
                    <span class="recap-value text-warning">- {{ formatMontant(calculMontantReduction) }}</span>
                  </div>
                </el-col>
                <el-col :span="12">
                  <div class="recap-item recap-net">
                    <span class="recap-label">Net à Payer</span>
                    <span class="recap-value">{{ formatMontant(calculMontantNet) }}</span>
                  </div>
                </el-col>
              </el-row>
            </el-card>
          </div>
        </el-tab-pane>

        <!-- Onglet 3: Observations -->
        <el-tab-pane name="observations" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><ChatDotSquare /></el-icon>
              Observations
            </span>
          </template>

          <div class="tab-content">
            <el-form-item label="Observations / Notes" prop="observations">
              <el-input
                v-model="form.observations"
                type="textarea"
                :rows="6"
                placeholder="Notes ou informations complémentaires..."
              />
            </el-form-item>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-form>

    <!-- Navigation -->
    <div class="tab-navigation">
      <el-button v-if="activeTab !== 'general'" @click="previousTab">
        <el-icon><ArrowLeft /></el-icon>
        Précédent
      </el-button>
      <el-button v-if="activeTab !== 'observations'" type="primary" @click="nextTab">
        Suivant
        <el-icon><ArrowRight /></el-icon>
      </el-button>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleCancel" :disabled="loading">Annuler</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="loading">
          <el-icon v-if="!loading"><Check /></el-icon>
          {{ isEdit ? 'Mettre à jour' : 'Enregistrer' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import {
  Document,
  DocumentCopy,
  Refresh,
  Edit,
  Money,
  Check,
  WarningFilled,
  OfficeBuilding,
  TrendCharts,
  ChatDotSquare,
  ArrowLeft,
  ArrowRight
} from '@element-plus/icons-vue';

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  facture: {
    type: Object,
    default: () => null
  },
  fournisseur: {
    type: Object,
    default: () => null
  },
  fournisseurs: {
    type: Array,
    default: () => []
  },
  fournisseurId: {
    type: [Number, String],
    default: null
  },
  imputations: {
    type: Array,
    default: () => []
  },
  comptes: {
    type: Array,
    default: () => []
  },
  typesReduction: {
    type: Array,
    default: () => [
      { label: 'Contribution Nationale', value: 'contribution' },
      { label: 'Acomptes sur prestations', value: 'acomptes' },
      { label: 'Escomptes', value: 'escomptes' },
      { label: 'AIB', value: 'aib' }
    ]
  },
  serverErrors: {
    type: Object,
    default: () => null
  },
  loading: {
    type: Boolean,
    default: false
  }
});

// Emits
const emit = defineEmits(['update:modelValue', 'success']);

// State
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const isEdit = computed(() => !!props.facture);
const formRef = ref(null);
const activeTab = ref('general');
const validationErrors = ref([]);

const tabOrder = ['general', 'montants', 'observations'];

// Labels des champs
const fieldLabels = {
  numero_piece: 'N° Pièce',
  date: 'Date',
  reference_facture: 'Référence',
  fournisseur_id: 'Fournisseur',
  imputation_id: 'Imputation',
  compte_id: 'Compte',
  libelle: 'Libellé',
  montant_facture: 'Montant Facture',
  montant_mo: 'Montant M.O.',
  avoir: 'Avoir',
  type_reduction: 'Type de réduction',
  taux: 'Taux',
  assujetti_tva: 'TVA',
  taux_tva: 'Taux TVA',
  date_echeance: 'Date échéance',
  observations: 'Observations'
};

// Form data
const getInitialFormData = () => ({
  numero_piece: '',
  date: new Date().toISOString().split('T')[0],
  reference_facture: '',
  fournisseur_id: null,
  imputation_id: null,
  compte_id: null,
  libelle: '',
  montant_facture: 0,
  montant_mo: 0,
  avoir: 0,
  type_reduction: '',
  taux: 0,
  assujetti_tva: true,
  taux_tva: 18,
  date_echeance: null,
  observations: ''
});

const form = reactive(getInitialFormData());

// Computed - Calculs automatiques
const calculMontantHT = computed(() => {
  return (form.montant_facture || 0) - (form.avoir || 0);
});

const calculMontantTVA = computed(() => {
  if (!form.assujetti_tva || !form.taux_tva) return 0;
  return (calculMontantHT.value * form.taux_tva) / 100;
});

const calculMontantTTC = computed(() => {
  return calculMontantHT.value + calculMontantTVA.value;
});

const calculMontantReduction = computed(() => {
  if (!form.montant_mo || !form.taux) return 0;
  return (form.montant_mo * form.taux) / 100;
});

const calculMontantNet = computed(() => {
  return calculMontantTTC.value - calculMontantReduction.value;
});

// Validation rules
const rules = computed(() => ({
  date: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  libelle: [
    { required: true, message: 'Le libellé est obligatoire', trigger: 'blur' },
    { min: 3, message: 'Le libellé doit contenir au moins 3 caractères', trigger: 'blur' }
  ],
  montant_facture: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
    { type: 'number', min: 0, message: 'Le montant doit être positif', trigger: 'blur' }
  ]
}));

// Methods
const clearErrors = () => {
  validationErrors.value = [];
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR').format(montant || 0);
};

const genererNumeroPiece = () => {
  const date = new Date();
  const year = String(date.getFullYear()).slice(-3);
  const sequence = Math.floor(Math.random() * 9999) + 1;
  form.numero_piece = `PC/${year}/${String(sequence).padStart(4, '0')}`;
};

const previousTab = () => {
  const currentIndex = tabOrder.indexOf(activeTab.value);
  if (currentIndex > 0) {
    activeTab.value = tabOrder[currentIndex - 1];
  }
};

const nextTab = () => {
  const currentIndex = tabOrder.indexOf(activeTab.value);
  if (currentIndex < tabOrder.length - 1) {
    activeTab.value = tabOrder[currentIndex + 1];
  }
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const handleClosed = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  const initialData = getInitialFormData();
  Object.keys(initialData).forEach(key => {
    form[key] = initialData[key];
  });
  activeTab.value = 'general';
  validationErrors.value = [];
};

const loadFormData = () => {
  if (props.facture) {
    Object.keys(form).forEach(key => {
      if (key in props.facture && props.facture[key] !== null) {
        form[key] = props.facture[key];
      }
    });
  } else {
    const initialData = getInitialFormData();
    Object.keys(initialData).forEach(key => {
      form[key] = initialData[key];
    });

    // Pré-sélectionner le fournisseur
    if (props.fournisseurId) {
      form.fournisseur_id = props.fournisseurId;
    } else if (props.fournisseur?.id) {
      form.fournisseur_id = props.fournisseur.id;
    } else if (props.fournisseurs.length === 1) {
      form.fournisseur_id = props.fournisseurs[0].id;
    }
  }
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  validationErrors.value = [];

  try {
    await formRef.value.validate();

    // Préparer les données
    const data = {
      ...form,
      fournisseur_id: form.fournisseur_id || props.fournisseurId || props.fournisseur?.id
    };

    emit('success', data);

  } catch (error) {
    console.error('Erreurs de validation:', error);

    const errors = [];
    for (const fieldName of Object.keys(error || {})) {
      const fieldErrors = error[fieldName];
      if (Array.isArray(fieldErrors)) {
        for (const fieldError of fieldErrors) {
          errors.push({
            field: fieldLabels[fieldName] || fieldName,
            message: fieldError.message || fieldError
          });
        }
      }
    }

    validationErrors.value = errors;
    ElMessage.error({
      message: `${errors.length} erreur(s) de validation`,
      duration: 4000
    });
  }
};

// Watchers
watch(dialogVisible, (val) => {
  if (val) {
    loadFormData();
    if (!isEdit.value && !form.numero_piece) {
      genererNumeroPiece();
    }
  }
});

watch(() => props.serverErrors, (errors) => {
  if (errors && typeof errors === 'object') {
    const errorList = [];
    for (const [fieldName, messages] of Object.entries(errors)) {
      const fieldMessages = Array.isArray(messages) ? messages : [messages];
      for (const message of fieldMessages) {
        errorList.push({
          field: fieldLabels[fieldName] || fieldName,
          message: message
        });
      }
    }
    validationErrors.value = errorList;
  }
}, { immediate: true, deep: true });
</script>

<style scoped>
.facture-modal :deep(.el-dialog) {
  border-radius: 12px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.facture-modal :deep(.el-dialog__body) {
  padding: 0;
  overflow: hidden;
}

.errors-alert {
  margin: 16px 20px;
  border-radius: 8px;
}

.errors-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.errors-list {
  margin: 8px 0 0 0;
  padding-left: 20px;
}

.errors-list li {
  margin: 4px 0;
  font-size: 13px;
}

.fournisseur-info {
  margin: 16px 20px;
  border-radius: 8px;
}

.fournisseur-title {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ml-2 {
  margin-left: 8px;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.tab-content {
  min-height: 280px;
  padding: 20px;
}

.required-star {
  color: #f56c6c;
  margin-left: 2px;
}

.form-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}

.select-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.select-option span {
  font-size: 13px;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.switch-container {
  display: flex;
  align-items: center;
}

.tva-label {
  margin-left: 4px;
  color: #6b7280;
}

.calculated-field :deep(.el-input__inner) {
  font-weight: bold;
  color: #2563eb;
  background-color: #f0f9ff;
}

/* Recap Card */
.recap-card {
  margin-top: 20px;
  border-radius: 8px;
  background-color: #f9fafb;
}

.recap-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #374151;
}

.recap-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.recap-label {
  font-size: 14px;
  color: #6b7280;
}

.recap-value {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.recap-ttc {
  color: #2563eb;
  font-size: 18px;
}

.recap-net {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 12px 16px;
  border-radius: 8px;
  margin: -8px;
}

.recap-net .recap-label,
.recap-net .recap-value {
  color: white;
}

.recap-net .recap-value {
  font-size: 20px;
}

.text-warning {
  color: #f59e0b;
}

/* Navigation */
.tab-navigation {
  display: flex;
  justify-content: space-between;
  padding: 12px 20px;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

:deep(.el-tabs--border-card) {
  border-radius: 0;
  border: none;
  box-shadow: none;
}

:deep(.el-tabs__header) {
  background-color: #f3f4f6;
  margin: 0;
}

:deep(.el-tabs__content) {
  padding: 0;
  max-height: calc(90vh - 350px);
  overflow-y: auto;
}

:deep(.el-tabs__item) {
  padding: 12px 16px;
  height: auto;
}

:deep(.el-tabs__item.is-active) {
  background-color: white;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

:deep(.el-divider__text) {
  font-weight: 600;
  color: #6b7280;
  font-size: 13px;
}
</style>
