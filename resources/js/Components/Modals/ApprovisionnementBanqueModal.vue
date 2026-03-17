<template>
  <el-dialog
    v-model="dialogVisible"
    title="Approvisionnement Banque"
    width="500px"
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
      <el-form-item label="Compte bancaire" prop="compte_bancaire_id">
        <el-select
          v-model="form.compte_bancaire_id"
          placeholder="Sélectionner un compte"
          filterable
          style="width: 100%"
        >
          <el-option
            v-for="compte in comptes"
            :key="compte.id"
            :label="compte.nom"
            :value="compte.id"
          />
        </el-select>
      </el-form-item>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Date Dépôt" prop="date_depot">
            <el-date-picker
              v-model="form.date_depot"
              type="date"
              placeholder="Sélectionner"
              style="width: 100%"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
            />
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="Montant" prop="montant">
            <el-input
              :model-value="formatInputMontant(form.montant)"
              @input="val => form.montant = parseInputMontant(val)"
              placeholder="0"
            >
              <template #append>XOF</template>
            </el-input>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="Observations" prop="observations">
        <el-input
          v-model="form.observations"
          type="textarea"
          :rows="3"
          placeholder="Observations..."
        />
      </el-form-item>

      <el-form-item label="Pièce jointe">
        <el-upload
          ref="uploadRef"
          :auto-upload="false"
          :limit="1"
          accept=".pdf,.jpg,.jpeg,.png"
          :on-change="handleFileChange"
          :on-remove="handleFileRemove"
        >
          <el-button type="primary" plain>
            <el-icon><Upload /></el-icon>
            Sélectionner un fichier
          </el-button>
          <template #tip>
            <div class="el-upload__tip">PDF, JPG ou PNG (max 5 Mo)</div>
          </template>
        </el-upload>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="handleCancel">Annuler</el-button>
      <el-button type="primary" @click="handleSubmit" :loading="loading">
        <el-icon v-if="!loading"><Check /></el-icon>
        Enregistrer
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Check, Upload } from '@element-plus/icons-vue';
import { useMontant } from '@/Composables/useMontant';

const { formatInputMontant, parseInputMontant } = useMontant();

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  comptes: {
    type: Array,
    default: () => []
  },
  compteId: {
    type: [Number, String],
    default: null
  }
});

// Emits
const emit = defineEmits(['update:modelValue', 'success']);

// State
const formRef = ref(null);
const uploadRef = ref(null);
const loading = ref(false);
const selectedFile = ref(null);

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const form = reactive({
  compte_bancaire_id: null,
  date_depot: new Date().toISOString().split('T')[0],
  montant: 0,
  observations: ''
});

// Validation rules
const rules = {
  compte_bancaire_id: [
    { required: true, message: 'Le compte bancaire est obligatoire', trigger: 'change' }
  ],
  date_depot: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  montant: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
    { type: 'number', min: 1, message: 'Le montant doit être supérieur à 0', trigger: 'blur' }
  ]
};

// Methods
const handleFileChange = (file) => {
  selectedFile.value = file.raw;
};

const handleFileRemove = () => {
  selectedFile.value = null;
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const handleClosed = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  form.compte_bancaire_id = null;
  form.date_depot = new Date().toISOString().split('T')[0];
  form.montant = 0;
  form.observations = '';
  selectedFile.value = null;
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    loading.value = true;

    const formData = new FormData();
    formData.append('compte_bancaire_id', form.compte_bancaire_id);
    formData.append('date_depot', form.date_depot);
    formData.append('montant', form.montant);
    formData.append('observations', form.observations || '');
    if (selectedFile.value) {
      formData.append('piece_jointe', selectedFile.value);
    }

    const response = await fetch('/api/banques/approvisionnement', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: formData
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Approvisionnement enregistré avec succès');
      emit('success', result.data);
      dialogVisible.value = false;
    } else {
      ElMessage.error(result.message || 'Une erreur est survenue');
    }
  } catch (error) {
    console.error('Erreur:', error);
    ElMessage.error('Erreur lors de l\'enregistrement');
  } finally {
    loading.value = false;
  }
};

// Watch for compteId prop to pre-select
watch(() => props.modelValue, (val) => {
  if (val && props.compteId) {
    form.compte_bancaire_id = props.compteId;
  }
});
</script>

<style scoped>
.el-upload__tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}
</style>
