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
              <el-col :span="6">
                <el-form-item label="N° Pièce" prop="numero_piece">
                  <el-input
                    v-model="form.numero_piece"
                    placeholder="Auto-généré"
                    :prefix-icon="Document"
                    @blur="verifierNumeroPiece"
                    :class="{ 'numero-warning': numeroWarning, 'numero-error': numeroError }"
                  >
                    <template #append>
                      <el-button :icon="Refresh" @click="genererNumeroPiece" title="Générer automatiquement" :loading="loadingNumero" />
                    </template>
                  </el-input>
                  <div v-if="numeroWarning" class="numero-warning-text">
                    <el-icon><WarningFilled /></el-icon>
                    {{ numeroWarning }}
                  </div>
                  <div v-else-if="numeroError" class="numero-error-text">
                    <el-icon><CircleCloseFilled /></el-icon>
                    {{ numeroError }}
                  </div>
                  <div v-else class="form-hint">
                    Prochain N°: <strong>{{ prochainNumero || '...' }}</strong>
                  </div>
                </el-form-item>
              </el-col>

              <!-- Date -->
              <el-col :span="6">
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

              <!-- Date Fact / B.C. -->
              <el-col :span="6">
                <el-form-item label="Date Fact / B.C." prop="date_facture_bc">
                  <el-date-picker
                    v-model="form.date_facture_bc"
                    type="date"
                    placeholder="Sélectionner"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>

              <!-- Référence Facture -->
              <el-col :span="6">
                <el-form-item label="Référence Fact / N° B.C" prop="reference_facture">
                  <el-input
                    v-model="form.reference_facture"
                    placeholder="N° bon de commande"
                    :prefix-icon="DocumentCopy"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="20">
              <!-- Fournisseur -->
              <el-col :span="12">
                <el-form-item prop="fournisseur_id">
                  <template #label>
                    <span>Fournisseur <span class="required-star">*</span></span>
                  </template>
                  <el-select
                    v-model="form.fournisseur_id"
                    placeholder="Sélectionner un fournisseur"
                    filterable
                    clearable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="f in fournisseurs"
                      :key="f.id"
                      :label="f.nom"
                      :value="f.id"
                    >
                      <div class="select-option">
                        <span>{{ f.nom }}</span>
                        <el-tag v-if="f.ifu" size="small" type="info" class="ml-2">{{ f.ifu }}</el-tag>
                      </div>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>

              <!-- Libellé -->
              <el-col :span="12">
                <el-form-item prop="libelle">
                  <template #label>
                    <span>Libellé <span class="required-star">*</span></span>
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
              <!-- Imputation -->
              <el-col :span="12">
                <el-form-item label="Imputation" prop="imputation_id">
                  <el-select
                    v-model="form.imputation_id"
                    placeholder="Sélectionner une classe"
                    clearable
                    style="width: 100%"
                    @change="handleImputationChange"
                  >
                    <el-option
                      v-for="imputation in imputations"
                      :key="imputation.id"
                      :label="`${imputation.code || imputation.numero} - ${imputation.libelle}`"
                      :value="imputation.id"
                    >
                      <div class="select-option">
                        <el-tag size="small" :type="getClasseTagType(imputation.classe)">{{ imputation.code || imputation.numero }}</el-tag>
                        <span>{{ imputation.libelle }}</span>
                      </div>
                    </el-option>
                  </el-select>
                  <div class="form-hint">Sélectionner la classe comptable</div>
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
                    :disabled="!form.imputation_id"
                  >
                    <el-option
                      v-for="compte in comptesFiltres"
                      :key="compte.id"
                      :label="`${compte.numero || compte.code} - ${compte.libelle}`"
                      :value="compte.id"
                    >
                      <div class="select-option">
                        <el-tag size="small" :type="getClasseTagType(compte.classe)">{{ compte.numero || compte.code }}</el-tag>
                        <span>{{ compte.libelle }}</span>
                      </div>
                    </el-option>
                  </el-select>
                  <div class="form-hint" v-if="selectedImputation">
                    Comptes de la classe {{ selectedImputation.prefixe_compte || selectedImputation.code }} ({{ comptesFiltres.length }} disponibles)
                  </div>
                  <div class="form-hint" v-else>
                    Sélectionnez d'abord une imputation
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
            <!-- TVA -->
            <el-row :gutter="20" class="tva-row">
              <el-col :span="24">
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

            <el-divider />

            <el-row :gutter="20">
              <!-- Montant Facture -->
              <el-col :span="12">
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

              <!-- Avoir -->
              <el-col :span="12">
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

            <el-divider content-position="left"></el-divider>

            <el-row :gutter="20">
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
                  <div class="form-hint">Base de calcul AIB</div>
                </el-form-item>
              </el-col>

              <!-- AIB -->
              <el-col :span="8">
                <el-form-item label="AIB" prop="type_reduction">
                  <el-select
                    v-model="form.type_reduction"
                    placeholder="Sélectionner un compte AIB"
                    clearable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="compte in comptesAib"
                      :key="compte.code"
                      :label="`${compte.code} - ${compte.libelle}`"
                      :value="compte.code"
                    />
                  </el-select>
                </el-form-item>
              </el-col>

              <!-- Taux AIB -->
              <el-col :span="8">
                <el-form-item label="Taux AIB (%)" prop="taux">
                  <el-input-number
                    v-model="form.taux"
                    :min="0"
                    :max="100"
                    :step="0.5"
                    :precision="2"
                    style="width: 100%"
                  />
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
                  <div class="recap-item" v-if="form.avoir > 0">
                    <span class="recap-label">Avoir</span>
                    <span class="recap-value text-warning">- {{ formatMontant(form.avoir) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item" v-if="calculMontantReduction > 0">
                    <span class="recap-label">AIB ({{ form.taux }}%)</span>
                    <span class="recap-value text-warning">- {{ formatMontant(calculMontantReduction) }}</span>
                  </div>
                </el-col>
              </el-row>

              <el-divider />

              <el-row :gutter="20">
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">TVA ({{ form.assujetti_tva ? form.taux_tva : 0 }}%)</span>
                    <span class="recap-value">{{ formatMontant(calculMontantTVA) }}</span>
                    <div class="form-hint">Versée par l'entreprise</div>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">Montant TTC</span>
                    <span class="recap-value recap-ttc">{{ formatMontant(calculMontantTTC) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
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
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Document,
  DocumentCopy,
  Refresh,
  Edit,
  Money,
  Check,
  WarningFilled,
  CircleCloseFilled,
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
  comptesAib: {
    type: Array,
    default: () => []
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
const loadingNumero = ref(false);
const prochainNumero = ref('');
const numeroWarning = ref('');
const numeroError = ref('');

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
  type_reduction: 'AIB',
  taux: 'Taux',
  assujetti_tva: 'TVA',
  taux_tva: 'Taux TVA',
  date_facture_bc: 'Date Fact / B.C.',
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
  date_facture_bc: null,
  observations: ''
});

const form = reactive(getInitialFormData());

// Computed - Calculs automatiques
// Montant HT = Montant Facture (pas de soustraction de l'avoir)
const calculMontantHT = computed(() => {
  return form.montant_facture || 0;
});

// TVA calculée sur le HT (informative, versée par l'entreprise)
const calculMontantTVA = computed(() => {
  if (!form.assujetti_tva || !form.taux_tva) return 0;
  return (calculMontantHT.value * form.taux_tva) / 100;
});

// TTC = HT + TVA
const calculMontantTTC = computed(() => {
  return calculMontantHT.value + calculMontantTVA.value;
});

// AIB calculé sur le Montant M.O.
const calculMontantReduction = computed(() => {
  if (!form.taux || !form.type_reduction) return 0;
  const base = form.montant_mo || 0;
  if (!base) return 0;
  return (base * form.taux) / 100;
});

// Net à Payer = Montant Facture - Avoir - AIB (pas de TVA)
const calculMontantNet = computed(() => {
  return (form.montant_facture || 0) - (form.avoir || 0) - calculMontantReduction.value;
});

// Computed - Imputation sélectionnée
const selectedImputation = computed(() => {
  if (!form.imputation_id) return null;
  return props.imputations.find(i => i.id === form.imputation_id);
});

// Computed - Comptes filtrés selon l'imputation sélectionnée
const comptesFiltres = computed(() => {
  if (!selectedImputation.value) return [];

  const prefixe = selectedImputation.value.prefixe_compte || selectedImputation.value.code || '';

  return props.comptes.filter(c => {
    const compteNumero = c.code || c.numero || '';
    return compteNumero.startsWith(prefixe);
  });
});

// Helper - Type de tag selon la classe
const getClasseTagType = (classe) => {
  switch (classe) {
    case '6': return 'danger';
    case '2': return 'warning';
    case '4': return 'success';
    default: return 'info';
  }
};

// Handler - Changement d'imputation
const handleImputationChange = () => {
  // Réinitialiser le compte si l'imputation change
  form.compte_id = null;
};

// Validation rules
const rules = computed(() => ({
  numero_piece: [
    {
      validator: (rule, value, callback) => {
        if (numeroError.value) {
          callback(new Error(numeroError.value));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ],
  fournisseur_id: [
    { required: true, message: 'Le fournisseur est obligatoire', trigger: 'change' }
  ],
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

const genererNumeroPiece = async () => {
  loadingNumero.value = true;
  numeroWarning.value = '';
  numeroError.value = '';

  try {
    const response = await fetch('/api/factures-fournisseurs/generer-numero', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });

    const result = await response.json();

    if (result.success) {
      form.numero_piece = result.numero_piece;
      prochainNumero.value = result.prochain_numero || result.numero_piece;
    } else {
      // Fallback: génération locale
      const date = new Date();
      const year = String(date.getFullYear()).slice(-3);
      const sequence = Math.floor(Math.random() * 9999) + 1;
      form.numero_piece = `PC/${year}/${String(sequence).padStart(4, '0')}`;
    }
  } catch (error) {
    console.error('Erreur lors de la génération du numéro:', error);
    // Fallback
    const date = new Date();
    const year = String(date.getFullYear()).slice(-3);
    const sequence = Math.floor(Math.random() * 9999) + 1;
    form.numero_piece = `PC/${year}/${String(sequence).padStart(4, '0')}`;
  } finally {
    loadingNumero.value = false;
  }
};

const verifierNumeroPiece = async () => {
  if (!form.numero_piece || form.numero_piece === prochainNumero.value) {
    numeroWarning.value = '';
    numeroError.value = '';
    return;
  }

  try {
    const response = await fetch('/api/factures-fournisseurs/verifier-numero', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ numero_piece: form.numero_piece })
    });

    const result = await response.json();

    if (!result.valide) {
      numeroError.value = result.message;
      numeroWarning.value = '';
    } else if (result.avertissement) {
      numeroWarning.value = result.avertissement;
      numeroError.value = '';
    } else {
      numeroWarning.value = '';
      numeroError.value = '';
    }

    // Mettre à jour le prochain numéro suggéré
    if (result.prochain_numero) {
      prochainNumero.value = result.prochain_numero;
    }
  } catch (error) {
    console.error('Erreur lors de la vérification:', error);
  }
};

const chargerProchainNumero = async () => {
  try {
    const response = await fetch('/api/factures-fournisseurs/generer-numero', {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    });

    const result = await response.json();
    if (result.success) {
      prochainNumero.value = result.prochain_numero || result.numero_piece;
    }
  } catch (error) {
    console.error('Erreur:', error);
  }
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
  numeroWarning.value = '';
  numeroError.value = '';
  prochainNumero.value = '';
};

const loadFormData = () => {
  if (props.facture) {
    Object.keys(form).forEach(key => {
      if (key in props.facture && props.facture[key] !== null) {
        form[key] = props.facture[key];
      }
    });
    // S'assurer que le fournisseur_id est bien chargé
    if (!form.fournisseur_id && props.facture.fournisseur?.id) {
      form.fournisseur_id = props.facture.fournisseur.id;
    }
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

  // Vérifier d'abord le numéro de pièce si modifié
  if (form.numero_piece && form.numero_piece !== prochainNumero.value) {
    await verifierNumeroPiece();

    // Si erreur (doublon), ne pas continuer
    if (numeroError.value) {
      ElMessage.error({
        message: numeroError.value,
        duration: 4000
      });
      return;
    }

    // Si avertissement, demander confirmation
    if (numeroWarning.value) {
      const confirmed = await ElMessageBox.confirm(
        `${numeroWarning.value}\n\nVoulez-vous continuer avec ce numéro ?`,
        'Avertissement',
        {
          confirmButtonText: 'Continuer',
          cancelButtonText: 'Annuler',
          type: 'warning',
        }
      ).catch(() => false);

      if (!confirmed) {
        return;
      }
    }
  }

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
watch(dialogVisible, async (val) => {
  if (val) {
    loadFormData();
    // Charger le prochain numéro attendu
    await chargerProchainNumero();
    // Si nouvelle facture et pas de numéro, générer automatiquement
    if (!isEdit.value && !form.numero_piece) {
      await genererNumeroPiece();
    }
    // Réinitialiser les avertissements
    numeroWarning.value = '';
    numeroError.value = '';
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

.form-hint strong {
  color: #2563eb;
}

.numero-warning :deep(.el-input__wrapper) {
  box-shadow: 0 0 0 1px #e6a23c inset !important;
}

.numero-error :deep(.el-input__wrapper) {
  box-shadow: 0 0 0 1px #f56c6c inset !important;
}

.numero-warning-text {
  display: flex;
  align-items: flex-start;
  gap: 4px;
  font-size: 12px;
  color: #e6a23c;
  margin-top: 4px;
  line-height: 1.4;
}

.numero-warning-text .el-icon {
  margin-top: 2px;
  flex-shrink: 0;
}

.numero-error-text {
  display: flex;
  align-items: flex-start;
  gap: 4px;
  font-size: 12px;
  color: #f56c6c;
  margin-top: 4px;
  line-height: 1.4;
}

.numero-error-text .el-icon {
  margin-top: 2px;
  flex-shrink: 0;
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
