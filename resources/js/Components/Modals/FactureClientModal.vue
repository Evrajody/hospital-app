<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier la Facture' : 'Nouvelle Facture Client'"
    width="600px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @closed="handleClosed"
    class="facture-client-modal"
  >
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
          <el-form-item prop="reference">
            <template #label>
              <span>Référence <span class="required-star">*</span></span>
            </template>
            <el-input
              v-model="form.reference"
              placeholder="0001/02/26"
            >
              <template #append>
                <el-button :icon="MagicStick" @click="autoReference">
                  Auto
                </el-button>
              </template>
            </el-input>
            <div class="form-hint">Format : xxxx/mm/yy</div>
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item prop="date_facture">
            <template #label>
              <span>Date <span class="required-star">*</span></span>
            </template>
            <el-date-picker
              v-model="form.date_facture"
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
          <el-form-item prop="client_id">
            <template #label>
              <span>Client <span class="required-star">*</span></span>
            </template>
            <el-select
              v-model="form.client_id"
              filterable
              placeholder="Sélectionner un client"
              style="width: 100%"
            >
              <el-option
                v-for="client in clients"
                :key="client.id"
                :label="client.nom"
                :value="client.id"
              >
                <span>{{ client.nom }}</span>
                <span v-if="client.code && client.code !== '-'" style="float: right; color: #999; font-size: 12px;">{{ client.code }}</span>
              </el-option>
            </el-select>
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item prop="montant">
            <template #label>
              <span>Montant <span class="required-star">*</span></span>
            </template>
            <el-input-number
              v-model="form.montant"
              :min="0"
              :precision="0"
              controls-position="right"
              style="width: 100%"
              placeholder="0"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Ristourne">
            <el-input-number
              v-model="form.ristourne"
              :min="0"
              :max="form.montant || 0"
              :precision="0"
              controls-position="right"
              style="width: 100%"
              placeholder="0"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Net à payer">
            <div class="net-a-payer">{{ formatMontant(netAPayer) }}</div>
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <!-- Modal saut de numéro -->
    <el-dialog
      v-model="showSautModal"
      title="Saut de numéro détecté"
      width="400px"
      :close-on-click-modal="false"
      append-to-body
    >
      <div style="text-align: center; padding: 10px 0;">
        <el-icon :size="48" color="#E6A23C" style="margin-bottom: 16px;"><WarningFilled /></el-icon>
        <p style="font-size: 15px;">
          Vous sautez <strong>{{ sautData.numeros }}</strong> numéro(s) de référence.
        </p>
        <p style="font-size: 13px; color: #909399;">
          Voulez-vous continuer avec la référence <strong>{{ form.reference }}</strong> ?
        </p>
      </div>
      <template #footer>
        <el-button @click="showSautModal = false">Annuler</el-button>
        <el-button type="warning" :loading="props.loading" @click="forceSubmit">
          Confirmer
        </el-button>
      </template>
    </el-dialog>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleCancel" :disabled="props.loading">Annuler</el-button>
        <el-button
          type="primary"
          @click="handleSubmit"
          :loading="props.loading"
        >
          <el-icon v-if="!props.loading"><Check /></el-icon>
          {{ isEdit ? 'Mettre à jour' : 'Enregistrer' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { MagicStick, Check, WarningFilled } from '@element-plus/icons-vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  facture: { type: Object, default: () => null },
  clients: { type: Array, default: () => [] },
  prochaineReference: { type: String, default: '' },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue', 'success']);

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const isEdit = computed(() => !!props.facture);
const formRef = ref(null);
const showSautModal = ref(false);
const sautData = reactive({ numeros: 0 });

const getInitialFormData = () => ({
  reference: '',
  date_facture: new Date(),
  client_id: null,
  montant: null,
  ristourne: 0,
});

const form = reactive(getInitialFormData());

watch(() => props.facture, (newFacture) => {
  if (newFacture) {
    form.reference = newFacture.reference || '';
    form.date_facture = newFacture.date_facture ? new Date(newFacture.date_facture) : new Date();
    form.client_id = newFacture.client_id || null;
    form.montant = newFacture.montant || null;
    form.ristourne = newFacture.ristourne || 0;
  }
}, { immediate: true, deep: true });

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    if (props.facture) {
      // Mode édition : recharger les données
      form.reference = props.facture.reference || '';
      form.date_facture = props.facture.date_facture ? new Date(props.facture.date_facture) : new Date();
      form.client_id = props.facture.client_id || null;
      form.montant = props.facture.montant || null;
      form.ristourne = props.facture.ristourne || 0;
    } else {
      // Mode création : auto-remplir la référence
      form.reference = props.prochaineReference;
      form.date_facture = new Date();
      form.client_id = null;
      form.montant = null;
      form.ristourne = 0;
    }
  }
});

const rules = {
  reference: [
    { required: true, message: 'La référence est obligatoire', trigger: 'blur' },
    { pattern: /^\d{4}\/\d{2}\/\d{2}$/, message: 'Format attendu : xxxx/mm/yy', trigger: 'blur' }
  ],
  date_facture: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  client_id: [
    { required: true, message: 'Le client est obligatoire', trigger: 'change' }
  ],
  montant: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== null && value <= 0) {
          callback(new Error('Le montant doit être supérieur à 0'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ]
};

const netAPayer = computed(() => {
  return (form.montant || 0) - (form.ristourne || 0);
});

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const autoReference = () => {
  form.reference = props.prochaineReference;
};

const handleCancel = () => { dialogVisible.value = false; };

const handleClosed = () => {
  if (formRef.value) formRef.value.resetFields();
  const initialData = getInitialFormData();
  Object.keys(initialData).forEach(key => { form[key] = initialData[key]; });
  showSautModal.value = false;
};

const buildPayload = (forceSaut = false) => {
  const dateFacture = form.date_facture instanceof Date
    ? form.date_facture.toISOString().split('T')[0]
    : form.date_facture;

  return {
    reference: form.reference,
    date_facture: dateFacture,
    client_id: form.client_id,
    montant: form.montant,
    ristourne: form.ristourne || 0,
    force_saut: forceSaut,
  };
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    emit('success', buildPayload(false));
  } catch (error) {
    ElMessage.error('Veuillez corriger les erreurs');
  }
};

const forceSubmit = () => {
  showSautModal.value = false;
  emit('success', buildPayload(true));
};

// Exposer pour que le parent puisse afficher le modal de saut
const showSautWarning = (numeros) => {
  sautData.numeros = numeros;
  showSautModal.value = true;
};

defineExpose({ showSautWarning });
</script>

<style scoped>
.facture-client-modal :deep(.el-dialog) { border-radius: 12px; }
.required-star { color: #f56c6c; margin-left: 2px; }
.form-hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 12px; }
.net-a-payer { font-size: 20px; font-weight: 700; color: #059669; padding: 8px 0; font-family: 'Courier New', monospace; }

:deep(.el-form-item__label) { font-weight: 600; color: #374151; font-size: 14px; }
</style>
