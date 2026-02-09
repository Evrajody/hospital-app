<template>
  <el-dialog
    v-model="dialogVisible"
    title="Nouvelle Banque"
    width="400px"
    :close-on-click-modal="false"
    @closed="handleClosed"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      size="large"
    >
      <el-form-item label="Nom de la banque" prop="nom">
        <el-input
          v-model="form.nom"
          placeholder="Ex: BOA, NSIA, Ecobank..."
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="dialogVisible = false">Annuler</el-button>
      <el-button type="primary" @click="handleSubmit" :loading="loading">
        Enregistrer
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'success']);

const formRef = ref(null);
const loading = ref(false);

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const form = reactive({
  nom: ''
});

const rules = {
  nom: [
    { required: true, message: 'Le nom est obligatoire', trigger: 'blur' },
    { min: 2, message: 'Le nom doit contenir au moins 2 caractères', trigger: 'blur' }
  ]
};

const handleClosed = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  form.nom = '';
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    loading.value = true;

    const response = await fetch('/api/banques', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ nom: form.nom })
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Banque créée avec succès');
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
</script>
