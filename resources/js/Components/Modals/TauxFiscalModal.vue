<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier le Taux Fiscal' : 'Nouveau Taux Fiscal'"
    width="500px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @closed="handleClosed"
  >
    <el-alert
      v-if="validationErrors.length > 0"
      type="error"
      :closable="true"
      class="global-errors-alert"
      @close="clearErrors"
    >
      <template #title>
        <div class="errors-title">
          Veuillez corriger les erreurs suivantes :
        </div>
      </template>
      <ul class="errors-list">
        <li v-for="(error, index) in validationErrors" :key="index">
          <span class="error-field">{{ error.field }}</span>: {{ error.message }}
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
      <el-form-item prop="type">
        <template #label>
          <span>Type <span class="required-star">*</span></span>
        </template>
        <el-select v-model="form.type" placeholder="Type de taxe" style="width: 100%" :disabled="isEdit">
          <el-option label="TVA" value="tva" />
          <el-option label="AIB" value="aib" />
        </el-select>
      </el-form-item>

      <el-form-item prop="libelle">
        <template #label>
          <span>Libellé <span class="required-star">*</span></span>
        </template>
        <el-input v-model="form.libelle" placeholder="Ex: TVA Standard, AIB 1%..." />
      </el-form-item>

      <el-form-item prop="taux">
        <template #label>
          <span>Taux (%) <span class="required-star">*</span></span>
        </template>
        <el-input-number
          v-model="form.taux"
          :min="0"
          :max="100"
          :precision="2"
          :step="0.5"
          style="width: 100%"
          placeholder="Ex: 18"
        />
      </el-form-item>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Actif" prop="actif">
            <el-switch v-model="form.actif" active-text="Oui" inactive-text="Non" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Par défaut" prop="par_defaut">
            <el-switch v-model="form.par_defaut" active-text="Oui" inactive-text="Non" />
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <template #footer>
      <el-button @click="handleCancel">Annuler</el-button>
      <el-button type="primary" :loading="loading" @click="handleSubmit">
        {{ isEdit ? 'Mettre à jour' : 'Enregistrer' }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useMontant } from '@/Composables/useMontant';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  tauxFiscal: { type: Object, default: null },
  typeInitial: { type: String, default: 'tva' },
  loading: { type: Boolean, default: false },
  serverErrors: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'success']);

const { castNumericFields } = useMontant();
const formRef = ref(null);
const validationErrors = ref([]);

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
});

const isEdit = computed(() => !!props.tauxFiscal);

const form = reactive({
  type: 'tva',
  libelle: '',
  taux: 0,
  actif: true,
  par_defaut: false,
});

const rules = computed(() => ({
  type: [{ required: true, message: 'Le type est requis', trigger: 'change' }],
  libelle: [
    { required: true, message: 'Le libellé est requis', trigger: 'blur' },
    { min: 2, message: 'Minimum 2 caractères', trigger: 'blur' },
  ],
  taux: [{ required: true, message: 'Le taux est requis', trigger: 'change' }],
}));

const clearErrors = () => {
  validationErrors.value = [];
};

const resetForm = () => {
  form.type = props.typeInitial || 'tva';
  form.libelle = '';
  form.taux = 0;
  form.actif = true;
  form.par_defaut = false;
  validationErrors.value = [];
};

const handleClosed = () => {
  resetForm();
  formRef.value?.resetFields();
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    emit('success', { ...form });
  } catch (err) {
    // Validation errors handled by Element Plus
  }
};

// Watch pour pré-remplir en mode édition
watch(() => props.tauxFiscal, (val) => {
  if (val) {
    form.type = val.type;
    form.libelle = val.libelle;
    form.taux = val.taux;
    form.actif = val.actif;
    form.par_defaut = val.par_defaut;
    castNumericFields(form, ['taux']);
  } else {
    form.type = props.typeInitial || 'tva';
  }
}, { immediate: true });

// Watch pour le type initial quand on crée
watch(() => props.typeInitial, (val) => {
  if (!props.tauxFiscal) {
    form.type = val;
  }
});

// Watch pour les erreurs serveur
watch(() => props.serverErrors, (errors) => {
  if (errors) {
    validationErrors.value = Object.entries(errors).map(([field, messages]) => ({
      field,
      message: Array.isArray(messages) ? messages[0] : messages,
    }));
  }
});
</script>

<style scoped>
.global-errors-alert { margin-bottom: 16px; }
.errors-title { display: flex; align-items: center; gap: 6px; font-weight: 600; }
.errors-list { margin: 8px 0 0 0; padding-left: 20px; }
.error-field { font-weight: 600; text-transform: capitalize; }
.required-star { color: #f56c6c; }
</style>
