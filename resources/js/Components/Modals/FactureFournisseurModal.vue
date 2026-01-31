<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier la Facture Fournisseur' : 'Nouvelle Facture Fournisseur'"
    width="90%"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @closed="handleClosed"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      size="large"
    >
      <!-- Première ligne: N°Pièce, Date, Référence -->
      <el-row :gutter="20">
        <el-col :xs="24" :sm="8">
          <el-form-item label="N° Pièce" prop="numero_piece">
            <el-input
              v-model="form.numero_piece"
              placeholder="Auto-généré ou saisir manuellement"
              :prefix-icon="Document"
            >
              <template #append>
                <el-button :icon="Refresh" @click="genererNumeroPiece">
                  Auto
                </el-button>
              </template>
            </el-input>
            <div class="form-hint">Le numéro sera auto-généré si laissé vide</div>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Date" prop="date">
            <el-date-picker
              v-model="form.date"
              type="date"
              placeholder="Sélectionner la date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Référence Facture / N° B.C" prop="reference_facture">
            <el-input
              v-model="form.reference_facture"
              placeholder="N° de bon de commande ou référence"
              :prefix-icon="DocumentCopy"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <!-- Deuxième ligne: Imputation, Compte, Fournisseur -->
      <el-row :gutter="20">
        <el-col :xs="24" :sm="8">
          <el-form-item label="Imputation" prop="imputation_id">
            <el-select
              v-model="form.imputation_id"
              placeholder="Sélectionner une imputation"
              filterable
              style="width: 100%"
              @change="handleImputationChange"
            >
              <el-option
                v-for="imputation in imputations"
                :key="imputation.id"
                :label="`${imputation.code} - ${imputation.libelle}`"
                :value="imputation.id"
              />
            </el-select>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Compte" prop="compte_id">
            <el-select
              v-model="form.compte_id"
              placeholder="Sélectionner un compte"
              filterable
              style="width: 100%"
              :disabled="!form.imputation_id"
            >
              <el-option
                v-for="compte in comptesFiltered"
                :key="compte.id"
                :label="`${compte.numero} - ${compte.libelle}`"
                :value="compte.id"
              >
                <div class="compte-option">
                  <el-tag size="small" type="info">{{ compte.numero }}</el-tag>
                  <span class="compte-libelle">{{ compte.libelle }}</span>
                </div>
              </el-option>
            </el-select>
            <div class="form-hint">Le compte est filtré selon l'imputation choisie</div>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Fournisseur" prop="fournisseur_id">
            <el-select
              v-model="form.fournisseur_id"
              placeholder="Sélectionner un fournisseur"
              filterable
              style="width: 100%"
            >
              <el-option
                v-for="fournisseur in fournisseurs"
                :key="fournisseur.id"
                :label="`${fournisseur.code} - ${fournisseur.nom}`"
                :value="fournisseur.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <!-- Troisième ligne: Libellé facture -->
      <el-row :gutter="20">
        <el-col :xs="24">
          <el-form-item label="Libellé facture" prop="libelle">
            <el-input
              v-model="form.libelle"
              placeholder="Description de la facture"
              :prefix-icon="Edit"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-divider content-position="left">Montants et Calculs</el-divider>

      <!-- Quatrième ligne: Montant Facture, Montant M.O., Avoir -->
      <el-row :gutter="20">
        <el-col :xs="24" :sm="8">
          <el-form-item label="Montant Facture" prop="montant_facture">
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

        <el-col :xs="24" :sm="8">
          <el-form-item label="Montant M.O." prop="montant_mo">
            <el-input
              v-model.number="form.montant_mo"
              type="number"
              placeholder="0"
              :prefix-icon="Money"
              @input="calculerMontantTaux"
            >
              <template #append>XOF</template>
            </el-input>
            <div class="form-hint">Main d'œuvre - Base de calcul du taux</div>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Avoir" prop="avoir">
            <el-input
              v-model.number="form.avoir"
              type="number"
              placeholder="0"
              :prefix-icon="Money"
            >
              <template #append>XOF</template>
            </el-input>
          </el-form-item>
        </el-col>
      </el-row>

      <!-- Cinquième ligne: Escompte/AIB, Taux, Montant calculé -->
      <el-row :gutter="20">
        <el-col :xs="24" :sm="8">
          <el-form-item label="Type de réduction" prop="type_reduction">
            <el-select
              v-model="form.type_reduction"
              placeholder="Escompte ou AIB"
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
            <div class="form-hint">Paramétrable - Sélectionner le type de réduction</div>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Taux (%)" prop="taux">
            <el-input
              v-model.number="form.taux"
              type="number"
              placeholder="0"
              step="0.01"
              @input="calculerMontantTaux"
            >
              <template #append>%</template>
            </el-input>
            <div class="form-hint">Taux appliqué sur le Montant M.O.</div>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="8">
          <el-form-item label="Montant (Taux × M.O.)" prop="montant_taux">
            <el-input
              v-model="montantTauxDisplay"
              disabled
              readonly
              class="montant-calculated"
            >
              <template #append>XOF</template>
            </el-input>
            <div class="form-hint">Calculé automatiquement</div>
          </el-form-item>
        </el-col>
      </el-row>

      <el-divider content-position="left">Observations</el-divider>

      <!-- Observations -->
      <el-row :gutter="20">
        <el-col :xs="24">
          <el-form-item label="Observations" prop="observations">
            <el-input
              v-model="form.observations"
              type="textarea"
              :rows="3"
              placeholder="Notes ou observations complémentaires..."
            />
          </el-form-item>
        </el-col>
      </el-row>

      <!-- Récapitulatif des montants -->
      <el-alert
        v-if="form.montant_facture || form.montant_mo"
        type="info"
        :closable="false"
        style="margin-top: 20px"
      >
        <template #title>
          <div style="font-size: 14px; font-weight: 600;">Récapitulatif</div>
        </template>
        <div style="font-size: 13px; line-height: 2;">
          <div>
            <strong>Montant Facture :</strong> {{ formatMontant(form.montant_facture) }}
          </div>
          <div v-if="form.avoir > 0">
            <strong>Avoir :</strong> {{ formatMontant(form.avoir) }}
          </div>
          <div v-if="form.montant_mo > 0">
            <strong>Montant M.O. :</strong> {{ formatMontant(form.montant_mo) }}
          </div>
          <div v-if="form.taux > 0 && form.montant_mo > 0">
            <strong>{{ form.type_reduction || 'Réduction' }} ({{ form.taux }}%) :</strong> {{ formatMontant(calculMontantTaux) }}
          </div>
        </div>
      </el-alert>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleCancel">Annuler</el-button>
        <el-button
          type="primary"
          :loading="submitting"
          @click="handleSubmit"
        >
          <el-icon v-if="!submitting"><Check /></el-icon>
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
  Check
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
  fournisseurs: {
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
  typesReduction: {
    type: Array,
    default: () => [
      { label: 'Contribution Nationale', value: 'contribution' },
      { label: 'Acomptes sur prestations', value: 'acomptes' },
      { label: 'Escomptes', value: 'escomptes' }
    ]
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
const submitting = ref(false);

// Form data
const form = reactive({
  numero_piece: props.facture?.numero_piece || '',
  date: props.facture?.date || new Date().toISOString().split('T')[0],
  reference_facture: props.facture?.reference_facture || '',
  imputation_id: props.facture?.imputation_id || null,
  compte_id: props.facture?.compte_id || null,
  fournisseur_id: props.facture?.fournisseur_id || null,
  libelle: props.facture?.libelle || '',
  montant_facture: props.facture?.montant_facture || 0,
  montant_mo: props.facture?.montant_mo || 0,
  avoir: props.facture?.avoir || 0,
  type_reduction: props.facture?.type_reduction || '',
  taux: props.facture?.taux || 0,
  observations: props.facture?.observations || ''
});

// Computed
const comptesFiltered = computed(() => {
  if (!form.imputation_id) return [];

  // Filtrer les comptes selon l'imputation sélectionnée
  const imputationSelectionnee = props.imputations.find(i => i.id === form.imputation_id);
  if (!imputationSelectionnee) return props.comptes;

  // TODO: Implémenter la logique de filtrage selon les règles métier
  // Pour l'instant, retourner tous les comptes
  return props.comptes;
});

const calculMontantTaux = computed(() => {
  if (!form.montant_mo || !form.taux) return 0;
  return (form.montant_mo * form.taux) / 100;
});

const montantTauxDisplay = computed(() => {
  return formatMontant(calculMontantTaux.value);
});

// Validation rules
const rules = {
  numero_piece: [
    { required: true, message: 'Le N° Pièce est obligatoire', trigger: 'blur' }
  ],
  date: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  reference_facture: [
    { required: true, message: 'La référence facture/BC est obligatoire', trigger: 'blur' }
  ],
  imputation_id: [
    { required: true, message: 'L\'imputation est obligatoire', trigger: 'change' }
  ],
  compte_id: [
    { required: true, message: 'Le compte est obligatoire', trigger: 'change' }
  ],
  fournisseur_id: [
    { required: true, message: 'Le fournisseur est obligatoire', trigger: 'change' }
  ],
  libelle: [
    { required: true, message: 'Le libellé est obligatoire', trigger: 'blur' }
  ],
  montant_facture: [
    { required: true, message: 'Le montant facture est obligatoire', trigger: 'blur' },
    { type: 'number', min: 0, message: 'Le montant doit être positif', trigger: 'blur' }
  ]
};

// Methods
const genererNumeroPiece = () => {
  // Générer un numéro de pièce au format PC/025/0001
  // PC = Pièce Comptable
  // 025 = Année (3 derniers chiffres de l'année)
  // 0001 = Numéro séquentiel
  const date = new Date();
  const year = String(date.getFullYear()).slice(-3); // Derniers 3 chiffres de l'année (ex: 025 pour 2025)

  // TODO: Récupérer le prochain numéro séquentiel depuis le backend
  // Pour l'instant, générer un numéro aléatoire
  const sequence = Math.floor(Math.random() * 9999) + 1;

  form.numero_piece = `PC/${year}/${String(sequence).padStart(4, '0')}`;
};

const handleImputationChange = () => {
  // Réinitialiser le compte quand l'imputation change
  form.compte_id = null;
};

const calculerMontantTaux = () => {
  // Le calcul est fait automatiquement via computed property
  // Cette fonction est appelée pour déclencher la réactivité
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const loadFormData = () => {
  if (props.facture) {
    // Charger les données de la facture dans le formulaire (mode édition)
    Object.assign(form, {
      numero_piece: props.facture.numero_piece || '',
      date: props.facture.date || new Date().toISOString().split('T')[0],
      reference_facture: props.facture.reference_facture || '',
      imputation_id: props.facture.imputation_id || null,
      compte_id: props.facture.compte_id || null,
      fournisseur_id: props.facture.fournisseur_id || null,
      libelle: props.facture.libelle || '',
      montant_facture: props.facture.montant_facture || 0,
      montant_mo: props.facture.montant_mo || 0,
      avoir: props.facture.avoir || 0,
      type_reduction: props.facture.type_reduction || '',
      taux: props.facture.taux || 0,
      observations: props.facture.observations || ''
    });
  } else {
    // Réinitialiser le formulaire (mode création)
    Object.assign(form, {
      numero_piece: '',
      date: new Date().toISOString().split('T')[0],
      reference_facture: '',
      imputation_id: null,
      compte_id: null,
      fournisseur_id: null,
      libelle: '',
      montant_facture: 0,
      montant_mo: 0,
      avoir: 0,
      type_reduction: '',
      taux: 0,
      observations: ''
    });
  }
};

const handleClosed = () => {
  // Réinitialiser le formulaire après fermeture
  if (formRef.value) {
    formRef.value.resetFields();
  }
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (valid) {
      // Si le numéro de pièce a été modifié manuellement, demander confirmation
      if (props.facture && form.numero_piece !== props.facture.numero_piece) {
        try {
          await ElMessageBox.confirm(
            `Vous avez modifié le N° Pièce de "${props.facture.numero_piece}" à "${form.numero_piece}". Cette modification affectera l'incrémentation automatique. Voulez-vous continuer ?`,
            'Confirmation',
            {
              confirmButtonText: 'Oui, valider',
              cancelButtonText: 'Annuler',
              type: 'warning'
            }
          );
        } catch {
          return; // Annulé
        }
      }

      submitting.value = true;

      // TODO: Remplacer par l'appel API réel
      setTimeout(() => {
        ElMessage.success(
          isEdit.value
            ? 'Facture modifiée avec succès'
            : 'Facture créée avec succès'
        );
        emit('success', form);
        dialogVisible.value = false;
        submitting.value = false;
      }, 1000);
    } else {
      ElMessage.error('Veuillez corriger les erreurs du formulaire');
    }
  });
};

// Charger les données quand le modal s'ouvre
watch(dialogVisible, (val) => {
  if (val) {
    // Charger les données de la facture (édition) ou réinitialiser (création)
    loadFormData();

    // Auto-générer le numéro de pièce si vide en mode création
    if (!isEdit.value && !form.numero_piece) {
      genererNumeroPiece();
    }
  }
});
</script>

<style scoped>
.form-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}

.compte-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-libelle {
  font-size: 13px;
  color: #6b7280;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.montant-calculated :deep(.el-input__inner) {
  font-weight: bold;
  font-size: 15px;
  color: #2563eb;
  background-color: #f0f9ff;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

:deep(.el-dialog) {
  border-radius: 8px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

:deep(.el-dialog__body) {
  overflow-y: auto;
  max-height: calc(90vh - 150px);
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

:deep(.el-divider__text) {
  font-weight: 600;
  color: #1f2937;
  font-size: 15px;
}
</style>
