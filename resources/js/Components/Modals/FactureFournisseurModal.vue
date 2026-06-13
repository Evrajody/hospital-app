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
      v-loading="loadingForm"
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
              Informations <span class="required-star">*</span>
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- N° Pièce -->
              <el-col :span="6">
                <el-form-item prop="numero_piece">
                  <template #label>
                    <span>N° Pièce <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model="form.numero_piece"
                    placeholder="Auto-généré"
                    :prefix-icon="Document"
                    @blur="!isEdit && verifierNumeroPiece()"
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
                    <span>Date Enregistrement <span class="required-star">*</span></span>
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
              <el-col :span="6">
                <el-form-item label="Référence Fact / N° B.C" prop="reference_facture">
                  <el-input
                    v-model="form.reference_facture"
                    placeholder="N° bon de commande"
                    :prefix-icon="DocumentCopy"
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
                    <span>Libellé Facture<span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model="form.libelle"
                    placeholder="Description de la facture"
                    :prefix-icon="Edit"
                  />
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
              Montants <span class="required-star">*</span>
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
                      v-model="form.taux_tva"
                      :min="0"
                      :max="100"
                      :step="0.5"
                      size="small"
                      style="width: 100px; margin-left: 12px"
                      :disabled="!tvaCustomisable || !form.assujetti_tva"
                      @change="onTauxTvaChange"
                    />
                    <span class="tva-label" :style="{ opacity: form.assujetti_tva ? 1 : 0.4 }">%</span>
                    <span v-if="!tvaCustomisable && form.assujetti_tva" class="tva-disabled-hint">Taux fixé à {{ tvaConfig.taux }}% par les paramètres</span>
                    <span v-else-if="!form.assujetti_tva" class="tva-disabled-hint">Facture non assujettie — pas de TVA</span>
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
                    <span>Montant Facture HT <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    :model-value="formatInputMontant(form.montant_facture)"
                    @input="val => form.montant_facture = parseInputMontant(val)"
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
                    :model-value="formatInputMontant(form.avoir)"
                    @input="val => form.avoir = parseInputMontant(val)"
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
                    :model-value="formatInputMontant(form.montant_mo)"
                    @input="val => form.montant_mo = parseInputMontant(val)"
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
                    :disabled="!form.montant_mo"
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
                  <el-select
                    v-model="form.taux"
                    placeholder="Sélectionner un taux"
                    clearable
                    :disabled="!form.montant_mo"
                    style="width: 100%"
                    @change="handleTauxAibChange"
                  >
                    <el-option
                      v-for="t in tauxAibList"
                      :key="t.id"
                      :label="`${t.taux}%`"
                      :value="parseFloat(t.taux)"
                    >
                      {{ t.libelle }} ({{ t.taux }}%)
                    </el-option>
                  </el-select>
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
                    <span class="recap-label">Montant HT :</span>
                    <span class="recap-value">{{ formatMontant(calculMontantHT) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item" v-if="form.avoir > 0">
                    <span class="recap-label">Avoir :</span>
                    <span class="recap-value text-warning">- {{ formatMontant(form.avoir) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item" v-if="calculMontantReduction > 0">
                    <span class="recap-label">AIB ({{ form.taux }}%) :</span>
                    <span class="recap-value text-warning">- {{ formatMontant(calculMontantReduction) }}</span>
                  </div>
                </el-col>
              </el-row>

              <el-divider />

              <el-row :gutter="20">
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">TVA ({{ form.assujetti_tva ? form.taux_tva : 0 }}%) :</span>
                    <span class="recap-value">{{ formatMontant(calculMontantTVA) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item">
                    <span class="recap-label">Montant TTC :</span>
                    <span class="recap-value recap-ttc">{{ formatMontant(calculMontantTTC) }}</span>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="recap-item recap-net">
                    <span class="recap-label">Net à Payer :</span>
                    <span class="recap-value">{{ formatMontant(calculMontantNet) }}</span>
                  </div>
                </el-col>
              </el-row>
            </el-card>
          </div>
        </el-tab-pane>

        <!-- Onglet 3: Imputations comptables -->
        <el-tab-pane name="imputations" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Notebook /></el-icon>
              Imputations comptables <span class="required-star">*</span>
              <el-tag v-if="form.imputationsDebits.length + form.imputationsCredits.length > 0" size="small" type="info" style="margin-left: 6px">
                {{ form.imputationsDebits.length + form.imputationsCredits.length }}
              </el-tag>
            </span>
          </template>

          <div class="tab-content">
            <el-alert type="info" :closable="false" class="mb-4">
              <template #title>
                <span style="font-weight: 600">Imputations comptables — règle d'équilibre</span>
              </template>
              <div style="font-size: 13px; line-height: 1.6;">
                Saisissez les <strong>débits</strong> (charges, immo, personnel) totalisant <strong>{{ formatMontant(calculMontantHT) }}</strong> HT,
                et les <strong>crédits fournisseurs</strong> (401/481) totalisant <strong>{{ formatMontant(calculMontantTTC) }}</strong> TTC (la dette totale envers le fournisseur).
                <span v-if="form.assujetti_tva && form.taux_tva > 0">
                  La TVA ({{ form.taux_tva }}% = {{ formatMontant(calculMontantTVA) }}) sera auto-générée au débit pour équilibrer.
                </span>
                <span v-if="calculMontantReduction > 0">
                  L'AIB ({{ form.taux }}% = {{ formatMontant(calculMontantReduction) }}) sera retenue au moment du règlement si tu coches "Déclarer l'AIB".
                </span>
              </div>
            </el-alert>

            <!-- BLOC 1 : DÉBITS (Charges / Immobilisations / Personnel) -->
            <div class="imputation-block">
              <div class="block-header">
                <div class="block-title">
                  <el-icon><ArrowDown /></el-icon>
                  Bloc 1 — Charges / Immobilisations / Personnel (Débits HT)
                </div>
                <div class="block-target">
                  Total exigé : <strong>{{ formatMontant(calculMontantHT) }}</strong>
                </div>
              </div>

              <el-table :data="form.imputationsDebits" border style="width: 100%" class="imputations-table">
                <el-table-column label="Imputation" width="230">
                  <template #default="{ row }">
                    <el-select
                      v-model="row.imputation_code"
                      placeholder="Catégorie"
                      style="width: 100%"
                      @change="(v) => onImputationCategoryChange(row, v)"
                    >
                      <el-option
                        v-for="imp in imputationsDebitOptions"
                        :key="imp.code"
                        :label="`${imp.code} - ${imp.libelle}`"
                        :value="imp.code"
                      >
                        <div class="select-option">
                          <el-tag size="small" :type="getClasseTagType(imp.classe)">{{ imp.code }}</el-tag>
                          <span>{{ imp.libelle }}</span>
                        </div>
                      </el-option>
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="Compte comptable" min-width="320">
                  <template #default="{ row }">
                    <el-select
                      v-model="row.compte_id"
                      filterable
                      placeholder="Sélectionner un compte"
                      style="width: 100%"
                      :disabled="!row.imputation_code"
                    >
                      <el-option
                        v-for="compte in comptesPourImputation(row.imputation_code)"
                        :key="compte.id"
                        :label="`${compte.numero || compte.code} - ${compte.libelle}`"
                        :value="compte.id"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="Libellé" min-width="220">
                  <template #default="{ row }">
                    <el-input v-model="row.libelle" placeholder="Description" />
                  </template>
                </el-table-column>
                <el-table-column label="Montant" width="180" align="right">
                  <template #default="{ row }">
                    <el-input
                      :model-value="formatInputMontant(row.montant)"
                      @input="val => row.montant = parseInputMontant(val)"
                      placeholder="0"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="" width="60" align="center">
                  <template #default="{ $index }">
                    <el-button type="danger" link :icon="Delete" @click="removeImputationLine('debit', $index)" />
                  </template>
                </el-table-column>
              </el-table>

              <div class="block-footer">
                <el-button :icon="Plus" @click="addImputationLine('debit')" type="primary" size="small">
                  Ajouter une ligne
                </el-button>
                <div class="block-total">
                  <span>Total saisi :</span>
                  <strong :class="totalDebitsMatch ? 'total-ok' : 'total-warn'">
                    {{ formatMontant(totalImputationsDebits) }}
                  </strong>
                  <span v-if="!totalDebitsMatch" class="total-diff">
                    (écart : {{ formatMontant(ecartDebits) }})
                  </span>
                </div>
              </div>
            </div>

            <!-- BLOC 2 : CRÉDITS (Fournisseurs 401/481) -->
            <div class="imputation-block">
              <div class="block-header">
                <div class="block-title">
                  <el-icon><ArrowUp /></el-icon>
                  Bloc 2 — Fournisseurs (Crédits TTC)
                </div>
                <div class="block-target">
                  Total exigé : <strong>{{ formatMontant(calculMontantTTC) }}</strong>
                  <span style="font-size: 11px; color: #9ca3af; margin-left: 6px;">(TTC = dette totale)</span>
                </div>
              </div>

              <el-table :data="form.imputationsCredits" border style="width: 100%" class="imputations-table">
                <el-table-column label="Imputation" width="230">
                  <template #default="{ row }">
                    <el-select
                      v-model="row.imputation_code"
                      placeholder="Catégorie"
                      style="width: 100%"
                      @change="(v) => onImputationCategoryChange(row, v)"
                    >
                      <el-option
                        v-for="imp in imputationsCreditOptions"
                        :key="imp.code"
                        :label="`${imp.code} - ${imp.libelle}`"
                        :value="imp.code"
                      >
                        <div class="select-option">
                          <el-tag size="small" :type="getClasseTagType(imp.classe)">{{ imp.code }}</el-tag>
                          <span>{{ imp.libelle }}</span>
                        </div>
                      </el-option>
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="Compte comptable" min-width="320">
                  <template #default="{ row }">
                    <el-select
                      v-model="row.compte_id"
                      filterable
                      placeholder="Sélectionner un compte"
                      style="width: 100%"
                      :disabled="!row.imputation_code"
                    >
                      <el-option
                        v-for="compte in comptesPourImputation(row.imputation_code)"
                        :key="compte.id"
                        :label="`${compte.numero || compte.code} - ${compte.libelle}`"
                        :value="compte.id"
                      />
                    </el-select>
                  </template>
                </el-table-column>
                <el-table-column label="Libellé" min-width="220">
                  <template #default="{ row }">
                    <el-input v-model="row.libelle" placeholder="Description" />
                  </template>
                </el-table-column>
                <el-table-column label="Montant" width="180" align="right">
                  <template #default="{ row }">
                    <el-input
                      :model-value="formatInputMontant(row.montant)"
                      @input="val => row.montant = parseInputMontant(val)"
                      placeholder="0"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="" width="60" align="center">
                  <template #default="{ $index }">
                    <el-button type="danger" link :icon="Delete" @click="removeImputationLine('credit', $index)" />
                  </template>
                </el-table-column>
              </el-table>

              <div class="block-footer">
                <el-button :icon="Plus" @click="addImputationLine('credit')" type="primary" size="small">
                  Ajouter une ligne
                </el-button>
                <div class="block-total">
                  <span>Total saisi :</span>
                  <strong :class="totalCreditsMatch ? 'total-ok' : 'total-warn'">
                    {{ formatMontant(totalImputationsCredits) }}
                  </strong>
                  <span v-if="!totalCreditsMatch" class="total-diff">
                    (écart : {{ formatMontant(ecartCredits) }})
                  </span>
                </div>
              </div>
            </div>
          </div>
        </el-tab-pane>

        <!-- Onglet 4: Observations -->
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
import { usePage } from '@inertiajs/vue3';
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
  ArrowRight,
  ArrowDown,
  ArrowUp,
  Notebook,
  Delete,
  Plus,
} from '@element-plus/icons-vue';
import { useMontant } from '@/Composables/useMontant';
import { fetchApi } from '@/Composables/useFetch';
import { todayYmd } from '@/utils/date';

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
  tauxAibList: {
    type: Array,
    default: () => []
  },
  tauxTvaDefaut: {
    type: Number,
    default: 18
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

// Configuration TVA partagée via Inertia
const page = usePage();
const tvaConfig = computed(() => {
  const shared = page.props?.tva;
  return {
    actif: shared?.actif ?? true,
    taux: parseFloat(shared?.taux ?? props.tauxTvaDefaut ?? 18) || 18,
  };
});
const tvaCustomisable = computed(() => tvaConfig.value.actif === true);

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

const tabOrder = ['general', 'montants', 'imputations', 'observations'];

// Labels des champs
const fieldLabels = {
  numero_piece: 'N° Pièce',
  date: 'Date',
  reference_facture: 'Référence',
  fournisseur_id: 'Fournisseur',
  libelle: 'Libellé',
  montant_facture: 'Montant Facture HT',
  montant_mo: 'Montant M.O.',
  avoir: 'Avoir',
  type_reduction: 'AIB',
  taux: 'Taux',
  assujetti_tva: 'TVA',
  taux_tva: 'Taux TVA',
  date_facture_bc: 'Date Fact / B.C.',
  observations: 'Observations',
  imputations: 'Imputations comptables'
};

// Form data
const getInitialFormData = () => ({
  numero_piece: '',
  date: todayYmd(),
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
  taux_tva: tvaConfig.value.taux,
  date_facture_bc: null,
  observations: '',
  imputationsDebits: [],
  imputationsCredits: [],
});

const form = reactive(getInitialFormData());

// Computed - Calculs automatiques
// Montant HT = Montant Facture (pas de soustraction de l'avoir)
const calculMontantHT = computed(() => {
  return form.montant_facture || 0;
});

// Quand on désactive l'assujettissement → taux à 0 ; quand on l'active → taux par défaut
watch(() => form.assujetti_tva, (val) => {
  if (!val) {
    form.taux_tva = 0;
  } else if (!form.taux_tva || form.taux_tva === 0) {
    form.taux_tva = tvaConfig.value.taux || 18;
  }
});

// TVA verrouillée par les paramètres : on force le taux quand assujettie (mais pas le switch)
watch([tvaCustomisable, () => tvaConfig.value.taux, () => form.assujetti_tva],
  ([customisable, taux, assujetti]) => {
    if (!customisable && assujetti) {
      form.taux_tva = taux;
    }
  },
  { immediate: true }
);

const onTauxTvaChange = () => {
  // no-op: listener pour garder la compatibilité avec @change
};

// TVA calculée sur le HT (informative, versée par l'entreprise)
const calculMontantTVA = computed(() => {
  if (!form.assujetti_tva || !form.taux_tva) return 0;
  return (calculMontantHT.value * form.taux_tva) / 100;
});

// TTC = HT + TVA
const calculMontantTTC = computed(() => {
  return calculMontantHT.value + calculMontantTVA.value;
});

// AIB = Montant M.O. × taux / 100 (aligné sur le backend FactureFournisseur::calculerMontants)
const calculMontantReduction = computed(() => {
  const base = parseFloat(form.montant_mo) || 0;
  const taux = parseFloat(form.taux) || 0;
  if (base <= 0 || taux <= 0) return 0;
  return (base * taux) / 100;
});

// NAP = HT − Avoir − AIB (la TVA n'est pas remise au fournisseur — modèle "TVA pour compte")
const calculMontantNet = computed(() => {
  return calculMontantHT.value - (form.avoir || 0) - calculMontantReduction.value;
});

// Imputations disponibles : séparées en débits (2/42/6) et crédits (401/481)
const imputationsDebitOptions = computed(() =>
  (props.imputations || []).filter(i => (i.nature || 'debit') === 'debit')
);
const imputationsCreditOptions = computed(() =>
  (props.imputations || []).filter(i => i.nature === 'credit')
);

// Filtrer les comptes selon l'imputation sélectionnée
const comptesPourImputation = (imputationCode) => {
  if (!imputationCode) return [];
  const imp = (props.imputations || []).find(i => i.code === imputationCode);
  if (!imp) return [];
  const prefixe = imp.prefixe_compte || imp.code;
  return (props.comptes || []).filter(c => {
    const num = String(c.numero || c.code || '');
    return num.startsWith(prefixe);
  });
};

const onImputationCategoryChange = (row, code) => {
  row.compte_id = null;
  // Suggérer le libellé selon catégorie
  if (!row.libelle) {
    const imp = props.imputations.find(i => i.code === code);
    row.libelle = imp ? imp.libelle : '';
  }
};

// Helpers totaux par bloc
const totalImputationsDebits = computed(() =>
  (form.imputationsDebits || []).reduce((sum, l) => sum + (parseFloat(l.montant) || 0), 0)
);
const totalImputationsCredits = computed(() =>
  (form.imputationsCredits || []).reduce((sum, l) => sum + (parseFloat(l.montant) || 0), 0)
);

const ecartDebits = computed(() => Math.abs(calculMontantHT.value - totalImputationsDebits.value));
// Bloc 2 (Crédits Fournisseurs) doit égaler le TTC (dette totale envers le fournisseur)
const ecartCredits = computed(() => Math.abs(calculMontantTTC.value - totalImputationsCredits.value));

const totalDebitsMatch = computed(() =>
  form.imputationsDebits.length === 0 || ecartDebits.value < 0.5
);
const totalCreditsMatch = computed(() =>
  form.imputationsCredits.length === 0 || ecartCredits.value < 0.5
);

const addImputationLine = (nature) => {
  const target = nature === 'credit' ? 'imputationsCredits' : 'imputationsDebits';
  form[target].push({
    imputation_code: null,
    compte_id: null,
    libelle: '',
    montant: 0,
  });
};

const removeImputationLine = (nature, index) => {
  const target = nature === 'credit' ? 'imputationsCredits' : 'imputationsDebits';
  form[target].splice(index, 1);
};

// Helper - Type de tag selon la classe
const getClasseTagType = (classe) => {
  switch (classe) {
    case '6': return 'danger';
    case '2': return 'warning';
    case '4': return 'success';
    default: return 'info';
  }
};

// Validation rules
const rules = computed(() => ({
  numero_piece: [
    { required: true, message: 'Le N° Pièce est obligatoire', trigger: 'blur' },
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
  ],
}));

// Methods
const clearErrors = () => {
  validationErrors.value = [];
};

const { formatMontant, formatInputMontant, parseInputMontant, castNumericFields } = useMontant();

const genererNumeroPiece = async () => {
  loadingNumero.value = true;
  numeroWarning.value = '';
  numeroError.value = '';

  try {
    const response = await fetchApi('/api/factures-fournisseurs/generer-numero');

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
    const response = await fetchApi('/api/factures-fournisseurs/verifier-numero', {
      method: 'POST',
      body: { numero_piece: form.numero_piece }
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
    const response = await fetchApi('/api/factures-fournisseurs/generer-numero');

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

const loadingForm = ref(false);

const applyFactureData = (data) => {
  Object.keys(form).forEach(key => {
    if (key in data && data[key] !== null) {
      form[key] = data[key];
    }
  });
  // Convertir les champs numériques (arrivent en string depuis les casts decimal)
  castNumericFields(form, ['taux', 'montant_facture', 'montant_mo', 'avoir', 'taux_tva']);
  // Charger les imputations existantes — séparer en débits et crédits selon la nature
  form.imputationsDebits = [];
  form.imputationsCredits = [];

  if (Array.isArray(data.imputations) && data.imputations.length > 0) {
    data.imputations.forEach(i => {
      const nature = i.nature || (
        (() => {
          const code = deriveImputationCode(i.compte_id);
          return (code === '401' || code === '481') ? 'credit' : 'debit';
        })()
      );
      const ligne = {
        imputation_code: i.imputation_code || deriveImputationCode(i.compte_id),
        compte_id: i.compte_id,
        libelle: i.libelle || '',
        montant: parseFloat(i.montant) || 0,
      };
      if (nature === 'credit') {
        form.imputationsCredits.push(ligne);
      } else {
        form.imputationsDebits.push(ligne);
      }
    });
  } else if (data.compte_id) {
    // Legacy : facture créée avant la refonte
    form.imputationsDebits = [{
      imputation_code: deriveImputationCode(data.compte_id),
      compte_id: data.compte_id,
      libelle: data.libelle || '',
      montant: parseFloat(data.montant_facture) || 0,
    }];
  }
  // S'assurer que le fournisseur_id est bien chargé
  if (!form.fournisseur_id && data.fournisseur?.id) {
    form.fournisseur_id = data.fournisseur.id;
  }
};

const loadFormData = async () => {
  if (props.facture) {
    // En mode édition, charger les données complètes depuis l'API
    // car les données du listing ne contiennent pas tous les champs du formulaire
    loadingForm.value = true;
    try {
      const response = await fetchApi(`/api/factures-fournisseurs/${props.facture.id}`);
      const result = await response.json();
      if (result.success && result.data) {
        applyFactureData(result.data);
      } else {
        // Fallback: utiliser les données partielles du listing
        applyFactureData(props.facture);
      }
    } catch (error) {
      console.error('Erreur lors du chargement de la facture:', error);
      // Fallback: utiliser les données partielles du listing
      applyFactureData(props.facture);
    } finally {
      loadingForm.value = false;
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

const handleTauxAibChange = (val) => {
  if (!val && val !== 0) {
    form.taux = 0;
  }
};

// Déduit le code d'imputation (2/42/6/401/481/445) depuis le numero d'un compte
const deriveImputationCode = (compteId) => {
  if (!compteId) return null;
  const compte = (props.comptes || []).find(c => c.id === compteId);
  if (!compte) return null;
  const num = String(compte.numero || compte.code || '');
  if (num.startsWith('445')) return '445';
  if (num.startsWith('481')) return '481';
  if (num.startsWith('401')) return '401';
  if (num.startsWith('42')) return '42';
  if (num.startsWith('2')) return '2';
  if (num.startsWith('6')) return '6';
  return null;
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  validationErrors.value = [];

  // Vérifier le numéro de pièce uniquement en création
  if (!isEdit.value && form.numero_piece && form.numero_piece !== prochainNumero.value) {
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

    // === Imputations optionnelles ===
    // L'utilisateur peut sauvegarder sans imputations.
    // En revanche, s'il commence à saisir, on exige un état cohérent (les 2 blocs équilibrés).
    const hasAnyImputation = form.imputationsDebits.length > 0 || form.imputationsCredits.length > 0;

    if (hasAnyImputation) {
      if (form.imputationsDebits.length === 0) {
        activeTab.value = 'imputations';
        validationErrors.value = [{ field: 'Imputations Débits', message: 'Vous avez commencé à saisir l\'imputation : ajoutez aussi au moins une ligne débit, ou supprimez les lignes crédit pour rester sans imputation.' }];
        ElMessage.error('Bloc 1 (Débits) vide alors que des lignes crédit sont saisies.');
        return;
      }
      if (form.imputationsCredits.length === 0) {
        activeTab.value = 'imputations';
        validationErrors.value = [{ field: 'Imputations Crédits', message: 'Vous avez commencé à saisir l\'imputation : ajoutez aussi au moins une ligne crédit, ou supprimez les lignes débit pour rester sans imputation.' }];
        ElMessage.error('Bloc 2 (Crédits) vide alors que des lignes débit sont saisies.');
        return;
      }
      const allLines = [...form.imputationsDebits, ...form.imputationsCredits];
      if (allLines.some(l => !l.compte_id || !l.montant)) {
        activeTab.value = 'imputations';
        validationErrors.value = [{ field: 'Imputations', message: 'Chaque ligne doit avoir un compte et un montant.' }];
        ElMessage.error('Chaque ligne d\'imputation doit avoir un compte et un montant.');
        return;
      }
      if (!totalDebitsMatch.value) {
        activeTab.value = 'imputations';
        const ecart = formatMontant(ecartDebits.value);
        validationErrors.value = [{ field: 'Bloc Débits', message: `Le total débits doit égaler le Montant HT (écart : ${ecart}).` }];
        ElMessage.error(`Bloc 1 : total débits ≠ HT (écart : ${ecart}).`);
        return;
      }
      if (!totalCreditsMatch.value) {
        activeTab.value = 'imputations';
        const ecart = formatMontant(ecartCredits.value);
        validationErrors.value = [{ field: 'Bloc Crédits', message: `Le total crédits doit égaler le Montant TTC (écart : ${ecart}).` }];
        ElMessage.error(`Bloc 2 : total crédits ≠ TTC (écart : ${ecart}).`);
        return;
      }
    }

    // === Construire le payload imputations[] avec nature ===
    const imputationsPayload = [
      ...form.imputationsDebits.map(l => ({
        compte_id: l.compte_id,
        nature: 'debit',
        libelle: l.libelle,
        montant: l.montant,
        imputation_code: l.imputation_code,
      })),
      ...form.imputationsCredits.map(l => ({
        compte_id: l.compte_id,
        nature: 'credit',
        libelle: l.libelle,
        montant: l.montant,
        imputation_code: l.imputation_code,
      })),
    ];

    const data = {
      ...form,
      imputations: imputationsPayload,
      fournisseur_id: form.fournisseur_id || props.fournisseurId || props.fournisseur?.id,
    };
    delete data.imputationsDebits;
    delete data.imputationsCredits;

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

// Réinitialiser AIB si montant_mo est vidé
watch(() => form.montant_mo, (val) => {
  if (!val) {
    form.type_reduction = '';
    form.taux = '';
  }
});

// Watchers
watch(dialogVisible, async (val) => {
  if (val) {
    await loadFormData();
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

.tab-required-dot {
  color: #f56c6c;
  font-size: 16px;
  font-weight: bold;
  margin-left: 4px;
  vertical-align: middle;
}

.tab-content {
  min-height: 200px;
  padding: 12px 20px;
}

/* Imputations comptables — taille des inputs augmentée */
.imputations-table :deep(.el-input__wrapper),
.imputations-table :deep(.el-select__wrapper) {
  min-height: 36px;
  padding: 4px 12px;
}

.imputations-table :deep(.el-input__inner),
.imputations-table :deep(.el-select__placeholder) {
  font-size: 14px;
}

.imputations-table :deep(.cell) {
  padding: 8px 6px;
}

/* === Blocs Débits/Crédits === */
.imputation-block {
  margin-bottom: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}

.block-debit {
  border-left: 4px solid #2563eb;
}

.block-credit {
  border-left: 4px solid #16a34a;
}

.block-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.block-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 13px;
  color: #1f2937;
}

.block-debit .block-title .el-icon { color: #2563eb; }
.block-credit .block-title .el-icon { color: #16a34a; }

.block-target {
  font-size: 12px;
  color: #6b7280;
}

.block-target strong {
  color: #1f2937;
  font-family: 'Courier New', monospace;
  margin-left: 4px;
}

.block-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  background: #fafafa;
  border-top: 1px solid #f1f5f9;
  gap: 12px;
}

.block-total {
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.block-total strong {
  font-family: 'Courier New', monospace;
  font-size: 14px;
}

.total-ok {
  color: #16a34a;
}

.total-warn {
  color: #e6a23c;
}

.total-diff {
  color: #ef4444;
  font-size: 12px;
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

.select-option :deep(.el-tag) {
  min-width: 70px;
  text-align: center;
  font-family: 'Courier New', monospace;
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

.tva-disabled-hint {
  margin-left: 12px;
  font-size: 12px;
  color: #9ca3af;
  font-style: italic;
}

.calculated-field :deep(.el-input__inner) {
  font-weight: bold;
  color: #2563eb;
  background-color: #f0f9ff;
}

/* Recap Card */
.recap-card {
  margin-top: 8px;
  border-radius: 8px;
  background-color: #f9fafb;
}

.recap-card :deep(.el-card__header) {
  padding: 8px 16px;
}

.recap-card :deep(.el-card__body) {
  padding: 8px 16px;
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
  padding: 4px 0;
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
  max-height: calc(90vh - 280px);
  overflow-y: auto;
}

:deep(.el-divider) {
  margin: 8px 0;
}

:deep(.el-form-item) {
  margin-bottom: 12px;
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
