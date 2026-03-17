<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">R&egrave;glement de Facture Client</h1>
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
                <span class="info-label">R&eacute;f&eacute;rence :</span>
                <el-tag type="primary" size="large">{{ facture.reference }}</el-tag>
              </div>
              <div class="info-row">
                <span class="info-label">Client :</span>
                <strong>{{ facture.client?.nom || '-' }}</strong>
              </div>
              <div class="info-row">
                <span class="info-label">Date Facture :</span>
                <span>{{ formatDate(facture.date_facture) }}</span>
              </div>

              <el-divider />

              <div class="montants-grid">
                <div class="montant-row total-ttc">
                  <span class="montant-label"><strong>Montant :</strong></span>
                  <span class="montant-value"><strong>{{ formatMontant(facture.montant) }}</strong></span>
                </div>

                <div v-if="facture.ristourne > 0" class="montant-row">
                  <span class="montant-label">Ristourne :</span>
                  <span class="montant-value" style="color: #e6a23c;">- {{ formatMontant(facture.ristourne) }}</span>
                </div>
                <div v-if="facture.ristourne > 0" class="montant-row">
                  <span class="montant-label"><strong>Net &#224; payer :</strong></span>
                  <span class="montant-value" style="color: #059669; font-size: 16px;"><strong>{{ formatMontant(facture.net_a_payer) }}</strong></span>
                </div>

                <el-divider style="margin: 12px 0" />

                <div class="montant-row montant-paye">
                  <span class="montant-label">D&eacute;j&agrave; pay&eacute; :</span>
                  <span class="montant-value paye">{{ formatMontant(facture.montant_paye) }}</span>
                </div>
                <div class="montant-row reste-a-payer">
                  <span class="montant-label"><strong>Reste &agrave; payer :</strong></span>
                  <span class="montant-value reste">
                    <strong>{{ formatMontant(resteAPayer) }}</strong>
                  </span>
                </div>
              </div>

              <el-button
                v-if="resteAPayer > 0"
                type="success"
                plain
                style="width: 100%; margin-top: 16px;"
                @click="handleSolder"
              >
                <el-icon><CircleCheck /></el-icon>
                Marquer comme sold&eacute;e
              </el-button>
            </div>
          </el-card>

          <!-- Payment History Card -->
          <el-card shadow="never" class="history-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Clock /></el-icon>
                <span>Historique des R&egrave;glements ({{ reglements.length }})</span>
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
                    <el-tag size="small" type="primary">Ch&egrave;que</el-tag>
                    <strong class="reglement-montant">{{ formatMontant(reglement.montant) }}</strong>
                  </div>
                  <div class="reglement-details">
                    <div v-if="reglement.institution">
                      <el-icon><OfficeBuilding /></el-icon>
                      {{ reglement.institution }}
                    </div>
                    <div v-if="reglement.reference_cheque">
                      <el-icon><DocumentCopy /></el-icon>
                      R&eacute;f: {{ reglement.reference_cheque }}
                    </div>
                    <div v-if="reglement.banque_depot">
                      <el-icon><CreditCard /></el-icon>
                      D&eacute;p&ocirc;t: {{ reglement.banque_depot.nom }}
                    </div>
                  </div>
                  <div class="reglement-actions">
                    <el-button size="small" text type="primary" @click="showDetail(reglement)">
                      D&eacute;tails
                    </el-button>
                    <el-button size="small" text type="warning" @click="handleEditReglement(reglement)">
                      Modifier
                    </el-button>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>

            <el-empty v-else description="Aucun r&egrave;glement enregistr&eacute;" :image-size="80" />
          </el-card>
        </el-col>

        <!-- Right Column: New Payment Form -->
        <el-col :span="14">
          <el-card shadow="never" class="form-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Money /></el-icon>
                <span>Nouveau R&egrave;glement</span>
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
                <el-col :span="8">
                  <el-form-item label="N&deg; de ligne" prop="numero_ligne">
                    <el-input
                      v-model="form.numero_ligne"
                      placeholder="001"
                      readonly
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="8">
                  <el-form-item prop="date_reglement">
                    <template #label>
                      <span>Date R&egrave;glement <span class="required-star">*</span></span>
                    </template>
                    <el-date-picker
                      v-model="form.date_reglement"
                      type="date"
                      placeholder="S&eacute;lectionner"
                      style="width: 100%"
                      format="DD/MM/YYYY"
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="8">
                  <el-form-item prop="montant">
                    <template #label>
                      <span>Montant <span class="required-star">*</span></span>
                    </template>
                    <el-input
                      :model-value="formatInputMontant(form.montant)"
                      @input="val => form.montant = parseInputMontant(val)"
                      placeholder="0"
                      :prefix-icon="Money"
                    >
                      <template #append>XOF</template>
                    </el-input>
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Institution">
                    <el-select
                      v-model="form.institution"
                      filterable
                      allow-create
                      default-first-option
                      placeholder="S&eacute;lectionner ou saisir"
                      style="width: 100%"
                      clearable
                    >
                      <el-option
                        v-for="inst in institutions"
                        :key="inst"
                        :label="inst"
                        :value="inst"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="R&eacute;f&eacute;rence ch&egrave;que">
                    <el-input
                      v-model="form.reference_cheque"
                      placeholder="N&deg; du ch&egrave;que"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-divider>D&eacute;p&ocirc;t bancaire</el-divider>

              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="Banque de d&eacute;p&ocirc;t">
                    <el-select
                      v-model="form.banque_depot_id"
                      filterable
                      placeholder="S&eacute;lectionner une banque"
                      style="width: 100%"
                      clearable
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
                <el-col :span="12">
                  <el-form-item label="R&eacute;f&eacute;rence bordereau">
                    <el-select
                      v-model="form.compte_bancaire_id"
                      filterable
                      placeholder="S&eacute;lectionner un compte"
                      style="width: 100%"
                      clearable
                      :disabled="!form.banque_depot_id"
                    >
                      <el-option
                        v-for="compte in filteredComptes"
                        :key="compte.id"
                        :label="compte.numero_compte"
                        :value="compte.id"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item label="Notes / Remarques">
                <el-input
                  v-model="form.observations"
                  type="textarea"
                  :rows="2"
                  placeholder="Informations compl&eacute;mentaires..."
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
                      <span>Montant du r&egrave;glement :</span>
                      <strong>{{ formatMontant(form.montant) }}</strong>
                    </div>
                    <div class="summary-row">
                      <span>Nouveau reste &agrave; payer :</span>
                      <strong :style="{ color: newReste === 0 ? '#67c23a' : '#f56c6c' }">
                        {{ formatMontant(newReste) }}
                      </strong>
                    </div>
                    <div v-if="newReste === 0" class="summary-row">
                      <el-icon color="#67c23a" :size="16"><SuccessFilled /></el-icon>
                      <span style="color: #67c23a; font-weight: 600;">
                        Facture totalement r&eacute;gl&eacute;e
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
                  Enregistrer le R&egrave;glement
                </el-button>
              </div>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Edit Règlement Modal -->
    <el-dialog
      v-model="editVisible"
      title="Modifier le R&egrave;glement"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form v-if="editForm" label-position="top" size="large">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Date">
              <el-date-picker
                v-model="editForm.date_reglement"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Montant">
              <el-input-number
                v-model="editForm.montant"
                :min="1"
                :precision="0"
                controls-position="right"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="N&deg; Ligne">
              <el-input v-model="editForm.numero_ligne" readonly />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Institution">
              <el-input v-model="editForm.institution" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="R&eacute;f. Ch&egrave;que">
              <el-input v-model="editForm.reference_cheque" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="Observations">
          <el-input v-model="editForm.observations" type="textarea" :rows="3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editVisible = false">Annuler</el-button>
        <el-button type="primary" :loading="editLoading" @click="handleEditSubmit">
          Enregistrer
        </el-button>
      </template>
    </el-dialog>

    <!-- Detail Modal -->
    <el-dialog
      v-model="detailVisible"
      title="D&eacute;tails du R&egrave;glement"
      width="550px"
      :close-on-click-modal="false"
    >
      <div v-if="selectedReglement">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="Date">
            <strong>{{ formatDate(selectedReglement.date_reglement) }}</strong>
          </el-descriptions-item>
          <el-descriptions-item label="Montant">
            <el-tag type="success" size="large" style="font-size: 16px; padding: 8px 16px;">
              <strong>{{ formatMontant(selectedReglement.montant) }}</strong>
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="N&deg; Ligne">
            {{ selectedReglement.numero_ligne || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Institution">
            {{ selectedReglement.institution || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="R&eacute;f. Ch&egrave;que">
            {{ selectedReglement.reference_cheque || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Banque de d&eacute;p&ocirc;t">
            {{ selectedReglement.banque_depot?.nom || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="R&eacute;f. Bordereau">
            {{ selectedReglement.compte_bancaire?.numero_compte || '-' }}
          </el-descriptions-item>
          <el-descriptions-item v-if="selectedReglement.observations" label="Observations">
            {{ selectedReglement.observations }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
      <template #footer>
        <el-button @click="detailVisible = false">Fermer</el-button>
      </template>
    </el-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  ArrowLeft, Document, Clock, Money, Check,
  DocumentCopy, CreditCard, SuccessFilled, CircleCheck
} from '@element-plus/icons-vue';
import { ElMessageBox } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useMontant } from '@/Composables/useMontant';

// OfficeBuilding might not exist in all versions, use a fallback
const OfficeBuilding = DocumentCopy;

const props = defineProps({
  facture: { type: Object, required: true },
  reglements: { type: Array, default: () => [] },
  banques: { type: Array, default: () => [] },
  institutions: { type: Array, default: () => [] },
  user: { type: Object, default: () => null },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/factures-clients' },
  { title: props.facture.reference, path: `/factures-clients/${props.facture.id}` },
  { title: 'R\u00e8glement', path: '' }
];

const formRef = ref(null);
const submitting = ref(false);
const detailVisible = ref(false);
const selectedReglement = ref(null);
const editVisible = ref(false);
const editForm = ref(null);
const editLoading = ref(false);
const editingReglementId = ref(null);

const showDetail = (reglement) => {
  selectedReglement.value = reglement;
  detailVisible.value = true;
};

const form = ref({
  numero_ligne: String(props.reglements.length + 1).padStart(3, '0'),
  date_reglement: new Date(),
  montant: null,
  institution: '',
  reference_cheque: '',
  banque_depot_id: null,
  compte_bancaire_id: null,
  observations: '',
});

const rules = {
  date_reglement: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  montant: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
  ],
};

const resteAPayer = computed(() => {
  return parseFloat(props.facture.reste_a_payer) || 0;
});

const newReste = computed(() => {
  const montant = form.value.montant || 0;
  return Math.max(0, resteAPayer.value - montant);
});

const filteredComptes = computed(() => {
  if (!form.value.banque_depot_id) return [];
  const banque = props.banques.find(b => b.id === form.value.banque_depot_id);
  return banque?.comptes || [];
});

const handleBanqueChange = () => {
  form.value.compte_bancaire_id = null;
};

const handleCancel = () => {
  router.visit(`/factures-clients/${props.facture.id}`);
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
  } catch {
    ElMessage.error('Veuillez corriger les erreurs');
    return;
  }

  if (!form.value.montant || form.value.montant <= 0) {
    ElMessage.error('Le montant doit \u00eatre sup\u00e9rieur \u00e0 0');
    return;
  }

  submitting.value = true;

  const dateReglement = form.value.date_reglement instanceof Date
    ? form.value.date_reglement.toISOString().split('T')[0]
    : form.value.date_reglement;

  try {
    const response = await fetch('/api/reglements-clients', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({
        facture_id: props.facture.id,
        numero_ligne: form.value.numero_ligne,
        date_reglement: dateReglement,
        montant: form.value.montant,
        institution: form.value.institution || null,
        reference_cheque: form.value.reference_cheque || null,
        banque_depot_id: form.value.banque_depot_id || null,
        compte_bancaire_id: form.value.compte_bancaire_id || null,
        observations: form.value.observations || null,
      })
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'R\u00e8glement enregistr\u00e9');
      router.visit('/reglements-clients');
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    submitting.value = false;
  }
};

const handleEditReglement = (reglement) => {
  editingReglementId.value = reglement.id;
  editForm.value = {
    date_reglement: reglement.date_reglement,
    montant: reglement.montant,
    numero_ligne: reglement.numero_ligne || '',
    institution: reglement.institution || '',
    reference_cheque: reglement.reference_cheque || '',
    banque_depot_id: reglement.banque_depot?.id || null,
    compte_bancaire_id: reglement.compte_bancaire?.id || null,
    observations: reglement.observations || '',
  };
  editVisible.value = true;
};

const handleEditSubmit = async () => {
  editLoading.value = true;
  try {
    const response = await fetch(`/api/reglements-clients/${editingReglementId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify(editForm.value)
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Règlement modifié avec succès');
      editVisible.value = false;
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur lors de la modification');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  } finally {
    editLoading.value = false;
  }
};

const handleSolder = async () => {
  try {
    await ElMessageBox.confirm(
      'Voulez-vous marquer cette facture comme soldée ?',
      'Confirmation',
      { confirmButtonText: 'Oui, solder', cancelButtonText: 'Annuler', type: 'warning' }
    );
  } catch { return; }

  try {
    const response = await fetch(`/api/factures-clients/${props.facture.id}/solder`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      }
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Facture marquée comme soldée');
      router.visit(`/factures-clients/${props.facture.id}`);
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const { formatMontant, formatInputMontant, parseInputMontant } = useMontant();

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};

const getModeLabel = (mode) => {
  const labels = { especes: 'Esp\u00e8ces', cheque: 'Ch\u00e8que', virement: 'Virement' };
  return labels[mode] || mode;
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
  align-items: center;
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-label {
  font-size: 14px;
  color: #6b7280;
}

.montants-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.montant-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.montant-label {
  font-size: 14px;
  color: #6b7280;
}

.montant-value {
  font-size: 15px;
  color: #1f2937;
  font-family: 'Courier New', monospace;
}

.total-ttc .montant-value {
  color: #059669;
  font-size: 18px;
}

.montant-value.paye {
  color: #059669;
}

.montant-value.reste {
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

.reglement-actions {
  margin-top: 8px;
  text-align: right;
}

.required-star {
  color: #f56c6c;
  margin-left: 2px;
}

.payment-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
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
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-timeline-item__timestamp) {
  font-weight: 600;
  color: #6b7280;
}
</style>
