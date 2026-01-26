<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">
            {{ isEdit ? 'Modifier la Facture' : 'Nouvelle Facture Fournisseur' }}
          </h1>
          <p class="page-subtitle">
            {{ isEdit ? 'Mettre à jour les informations de la facture' : 'Enregistrer une nouvelle facture d\'achat' }}
          </p>
        </div>
        <el-button @click="handleCancel">
          <el-icon><ArrowLeft /></el-icon>
          Retour
        </el-button>
      </div>

      <!-- Form Card -->
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-position="top"
        size="large"
        @submit.prevent="handleSubmit"
      >
        <!-- Basic Information Card -->
        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="card-header-custom">
              <el-icon :size="20"><Document /></el-icon>
              <span>Informations de la Facture</span>
            </div>
          </template>

          <el-row :gutter="24">
            <el-col :span="6">
              <el-form-item label="N° Facture" prop="numero">
                <el-input
                  v-model="form.numero"
                  placeholder="PC/025/0001"
                  :prefix-icon="Key"
                >
                  <template #append>
                    <el-button :icon="Refresh" @click="generateNumero">Auto</el-button>
                  </template>
                </el-input>
              </el-form-item>
            </el-col>

            <el-col :span="6">
              <el-form-item label="Date Facture" prop="date_facture">
                <el-date-picker
                  v-model="form.date_facture"
                  type="date"
                  placeholder="Sélectionner"
                  style="width: 100%"
                  format="DD/MM/YYYY"
                />
              </el-form-item>
            </el-col>

            <el-col :span="6">
              <el-form-item label="Date d'Échéance" prop="date_echeance">
                <el-date-picker
                  v-model="form.date_echeance"
                  type="date"
                  placeholder="Sélectionner"
                  style="width: 100%"
                  format="DD/MM/YYYY"
                />
              </el-form-item>
            </el-col>

            <el-col :span="6">
              <el-form-item label="Référence Fournisseur" prop="reference">
                <el-input
                  v-model="form.reference"
                  placeholder="Référence externe"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="24">
            <el-col :span="12">
              <el-form-item label="Fournisseur" prop="fournisseur_id">
                <el-select
                  v-model="form.fournisseur_id"
                  filterable
                  placeholder="Rechercher par code, nom ou compte..."
                  style="width: 100%"
                  @change="handleFournisseurChange"
                >
                  <el-option
                    v-for="fournisseur in fournisseurs"
                    :key="fournisseur.id"
                    :label="`${fournisseur.code} - ${fournisseur.nom}${fournisseur.compte_numero ? ' - ' + fournisseur.compte_numero : ''}`"
                    :value="fournisseur.id"
                  >
                    <div class="fournisseur-option">
                      <div class="fournisseur-main">
                        <strong>{{ fournisseur.nom }}</strong>
                        <el-tag v-if="fournisseur.compte_numero" size="small" type="info">
                          {{ fournisseur.compte_numero }}
                        </el-tag>
                      </div>
                      <span class="fournisseur-code">{{ fournisseur.code }}</span>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :span="12">
              <el-form-item label="Remarques">
                <el-input
                  v-model="form.remarques"
                  placeholder="Notes ou observations..."
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>

        <!-- Line Items Card -->
        <el-card shadow="never" class="section-card">
          <template #header>
            <div class="card-header-custom">
              <div>
                <el-icon :size="20"><List /></el-icon>
                <span>Lignes de Facture</span>
              </div>
              <el-button type="primary" size="small" :icon="Plus" @click="addLine">
                Ajouter une ligne
              </el-button>
            </div>
          </template>

          <!-- Lines Table -->
          <div class="lines-table">
            <el-table :data="form.lignes" border style="width: 100%">
              <el-table-column type="index" label="#" width="50" align="center" />

              <el-table-column label="Description" min-width="200">
                <template #default="{ row, $index }">
                  <el-input
                    v-model="row.description"
                    placeholder="Description de l'article..."
                    @input="calculateLine($index)"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Imputation Comptable" width="240">
                <template #default="{ row, $index }">
                  <el-select
                    v-model="row.compte_imputation_id"
                    filterable
                    placeholder="Compte"
                    style="width: 100%"
                    @change="handleImputationChange($index)"
                  >
                    <el-option
                      v-for="compte in comptesImputation"
                      :key="compte.id"
                      :label="`${compte.numero} - ${compte.libelle}`"
                      :value="compte.id"
                    />
                  </el-select>
                </template>
              </el-table-column>

              <el-table-column label="Quantité" width="110">
                <template #default="{ row, $index }">
                  <el-input-number
                    v-model="row.quantite"
                    :min="0"
                    :precision="2"
                    controls-position="right"
                    style="width: 100%"
                    @change="calculateLine($index)"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Prix Unitaire" width="140">
                <template #default="{ row, $index }">
                  <el-input-number
                    v-model="row.prix_unitaire"
                    :min="0"
                    :precision="0"
                    controls-position="right"
                    style="width: 100%"
                    @change="calculateLine($index)"
                  />
                </template>
              </el-table-column>

              <el-table-column label="TVA %" width="100">
                <template #default="{ row, $index }">
                  <el-select
                    v-model="row.taux_tva"
                    style="width: 100%"
                    @change="calculateLine($index)"
                  >
                    <el-option label="0%" :value="0" />
                    <el-option label="18%" :value="18" />
                  </el-select>
                </template>
              </el-table-column>

              <el-table-column label="AIB %" width="100">
                <template #default="{ row, $index }">
                  <el-select
                    v-model="row.taux_aib"
                    style="width: 100%"
                    @change="calculateLine($index)"
                  >
                    <el-option label="0%" :value="0" />
                    <el-option label="1%" :value="1" />
                    <el-option label="3%" :value="3" />
                    <el-option label="5%" :value="5" />
                  </el-select>
                </template>
              </el-table-column>

              <el-table-column label="Escompte %" width="110">
                <template #default="{ row, $index }">
                  <el-input-number
                    v-model="row.taux_escompte"
                    :min="0"
                    :max="100"
                    :precision="2"
                    controls-position="right"
                    style="width: 100%"
                    @change="calculateLine($index)"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Total HT" width="130" align="right">
                <template #default="{ row }">
                  <strong>{{ formatMontant(row.montant_ht) }}</strong>
                </template>
              </el-table-column>

              <el-table-column label="Actions" width="80" align="center" fixed="right">
                <template #default="{ $index }">
                  <el-popconfirm
                    title="Supprimer cette ligne ?"
                    confirm-button-text="Oui"
                    cancel-button-text="Non"
                    @confirm="removeLine($index)"
                  >
                    <template #reference>
                      <el-button :icon="Delete" type="danger" size="small" circle />
                    </template>
                  </el-popconfirm>
                </template>
              </el-table-column>
            </el-table>

            <div v-if="form.lignes.length === 0" class="empty-lines">
              <el-empty description="Aucune ligne ajoutée">
                <el-button type="primary" :icon="Plus" @click="addLine">
                  Ajouter la première ligne
                </el-button>
              </el-empty>
            </div>
          </div>
        </el-card>

        <!-- Totals Card -->
        <el-card shadow="never" class="section-card totals-card">
          <el-row :gutter="24">
            <el-col :span="12">
              <el-alert
                type="info"
                :closable="false"
              >
                <template #title>
                  <div style="font-size: 13px;">
                    <strong>{{ form.lignes.length }}</strong> ligne(s) •
                    Fournisseur: <strong>{{ selectedFournisseurName || 'Non sélectionné' }}</strong>
                  </div>
                </template>
              </el-alert>
            </el-col>

            <el-col :span="12">
              <div class="totals-grid">
                <div class="total-row">
                  <span class="total-label">Total HT :</span>
                  <span class="total-value">{{ formatMontant(totals.montant_ht) }}</span>
                </div>
                <div class="total-row">
                  <span class="total-label">TVA (18%) :</span>
                  <span class="total-value total-tva">{{ formatMontant(totals.montant_tva) }}</span>
                </div>
                <div class="total-row">
                  <span class="total-label">AIB :</span>
                  <span class="total-value total-aib">{{ formatMontant(totals.montant_aib) }}</span>
                </div>
                <div class="total-row" v-if="totals.montant_escompte > 0">
                  <span class="total-label">Escompte :</span>
                  <span class="total-value total-escompte">- {{ formatMontant(totals.montant_escompte) }}</span>
                </div>
                <el-divider style="margin: 8px 0" />
                <div class="total-row total-ttc-row">
                  <span class="total-label"><strong>Total TTC :</strong></span>
                  <span class="total-value total-ttc"><strong>{{ formatMontant(totals.montant_ttc) }}</strong></span>
                </div>
              </div>
            </el-col>
          </el-row>
        </el-card>

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
            {{ isEdit ? 'Mettre à jour' : 'Enregistrer la Facture' }}
          </el-button>
        </div>
      </el-form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  ArrowLeft,
  Document,
  Key,
  Refresh,
  Plus,
  List,
  Delete,
  Check
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  facture: {
    type: Object,
    default: () => null
  },
  fournisseurs: {
    type: Array,
    default: () => []
  },
  comptesImputation: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: () => null
  }
});

// Computed
const isEdit = computed(() => !!props.facture);

const breadcrumbs = computed(() => [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Fournisseurs', path: '/factures-fournisseurs' },
  { title: isEdit.value ? 'Modifier' : 'Nouvelle', path: '' }
]);

const selectedFournisseurName = computed(() => {
  const fournisseur = props.fournisseurs.find(f => f.id === form.fournisseur_id);
  return fournisseur?.nom || '';
});

const totals = computed(() => {
  const montant_ht = form.lignes.reduce((sum, ligne) => sum + (ligne.montant_ht || 0), 0);
  const montant_tva = form.lignes.reduce((sum, ligne) => sum + (ligne.montant_tva || 0), 0);
  const montant_aib = form.lignes.reduce((sum, ligne) => sum + (ligne.montant_aib || 0), 0);
  const montant_escompte = form.lignes.reduce((sum, ligne) => sum + (ligne.montant_escompte || 0), 0);
  const montant_ttc = montant_ht + montant_tva + montant_aib - montant_escompte;

  return {
    montant_ht,
    montant_tva,
    montant_aib,
    montant_escompte,
    montant_ttc
  };
});

// State
const formRef = ref(null);
const submitting = ref(false);

const form = reactive({
  numero: props.facture?.numero || '',
  date_facture: props.facture?.date_facture || new Date(),
  date_echeance: props.facture?.date_echeance || null,
  reference: props.facture?.reference || '',
  fournisseur_id: props.facture?.fournisseur_id || null,
  remarques: props.facture?.remarques || '',
  lignes: props.facture?.lignes || []
});

// Validation rules
const rules = {
  numero: [
    { required: true, message: 'Le numéro est obligatoire', trigger: 'blur' }
  ],
  date_facture: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  fournisseur_id: [
    { required: true, message: 'Le fournisseur est obligatoire', trigger: 'change' }
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

const generateNumero = () => {
  const year = new Date().getFullYear().toString().slice(-3); // 3 derniers chiffres
  const increment = Math.floor(Math.random() * 9999) + 1; // Random 1-9999
  const incrementPadded = increment.toString().padStart(4, '0'); // Sur 4 chiffres
  form.numero = `PC/${year}/${incrementPadded}`;
  // TODO: Replace random with actual DB increment when backend is ready
};

const handleFournisseurChange = (fournisseurId) => {
  const fournisseur = props.fournisseurs.find(f => f.id === fournisseurId);
  console.log('Fournisseur sélectionné:', fournisseur);
};

const handleImputationChange = (index) => {
  const ligne = form.lignes[index];
  const compte = props.comptesImputation.find(c => c.id === ligne.compte_imputation_id);
  console.log('Compte d\'imputation:', compte);
};

const addLine = () => {
  form.lignes.push({
    description: '',
    compte_imputation_id: null,
    quantite: 1,
    prix_unitaire: 0,
    taux_tva: 18,
    taux_aib: 0,
    taux_escompte: 0,
    montant_ht: 0,
    montant_tva: 0,
    montant_aib: 0,
    montant_escompte: 0,
    montant_ttc: 0
  });
};

const removeLine = (index) => {
  form.lignes.splice(index, 1);
};

const calculateLine = (index) => {
  const ligne = form.lignes[index];

  // Calcul du montant HT brut
  const montant_brut = (ligne.quantite || 0) * (ligne.prix_unitaire || 0);

  // Calcul de l'escompte
  ligne.montant_escompte = montant_brut * ((ligne.taux_escompte || 0) / 100);

  // Montant HT après escompte
  ligne.montant_ht = montant_brut - ligne.montant_escompte;

  // Calcul TVA
  ligne.montant_tva = ligne.montant_ht * ((ligne.taux_tva || 0) / 100);

  // Calcul AIB
  ligne.montant_aib = ligne.montant_ht * ((ligne.taux_aib || 0) / 100);

  // Montant TTC de la ligne
  ligne.montant_ttc = ligne.montant_ht + ligne.montant_tva + ligne.montant_aib;
};

const handleCancel = () => {
  router.visit('/factures-fournisseurs');
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate((valid) => {
    if (valid) {
      if (form.lignes.length === 0) {
        ElMessage.warning('Veuillez ajouter au moins une ligne à la facture');
        return;
      }

      submitting.value = true;

      // Prepare data with totals
      const data = {
        ...form,
        montant_ht: totals.value.montant_ht,
        montant_tva: totals.value.montant_tva,
        montant_aib: totals.value.montant_aib,
        montant_escompte: totals.value.montant_escompte,
        montant_ttc: totals.value.montant_ttc
      };

      // TODO: Replace with actual API call when backend is ready
      setTimeout(() => {
        ElMessage.success(
          isEdit.value
            ? 'Facture modifiée avec succès'
            : 'Facture créée avec succès'
        );
        router.visit('/factures-fournisseurs');
      }, 1000);
    }
  });
};

// Auto-generate numero on mount if new facture
if (!isEdit.value && !form.numero) {
  generateNumero();
}
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

.section-card {
  border-radius: 8px;
  margin-bottom: 20px;
}

.card-header-custom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.card-header-custom > div {
  display: flex;
  align-items: center;
  gap: 8px;
}

.fournisseur-option {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.fournisseur-main {
  display: flex;
  align-items: center;
  gap: 8px;
}

.fournisseur-code {
  font-size: 12px;
  color: #9ca3af;
}

.lines-table {
  min-height: 200px;
}

.empty-lines {
  padding: 40px;
  text-align: center;
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

.total-ttc-row {
  padding: 12px 0;
  font-size: 18px;
}

.total-ttc {
  color: #059669;
  font-size: 20px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
  position: sticky;
  bottom: 0;
  z-index: 10;
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

:deep(.el-table) {
  font-size: 13px;
}

:deep(.el-table th) {
  background-color: #f3f4f6;
  font-weight: 600;
  color: #374151;
  font-size: 13px;
}

:deep(.el-table td) {
  padding: 8px 0;
}

:deep(.el-input-number) {
  width: 100%;
}

:deep(.el-input-number .el-input__inner) {
  text-align: left;
}
</style>
