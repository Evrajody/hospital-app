<template>
  <el-dialog
    v-model="dialogVisible"
    title="Nouveau Compte Bancaire"
    width="550px"
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
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Banque" prop="banque_id">
            <el-select
              v-model="form.banque_id"
              placeholder="Sélectionner"
              filterable
              allow-create
              style="width: 100%"
              @change="handleBanqueChange"
            >
              <el-option
                v-for="banque in banques"
                :key="banque.id"
                :label="banque.nom"
                :value="banque.id"
              />
            </el-select>
            <div class="form-hint">Sélectionnez ou créez une nouvelle banque</div>
          </el-form-item>
        </el-col>

        <el-col :span="12">
          <el-form-item label="Numéro de compte" prop="numero_compte">
            <el-input
              v-model="form.numero_compte"
              placeholder="Ex: 001234567890"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="Compte OHADA" prop="compte_ohada_id">
        <el-select
          v-model="form.compte_ohada_id"
          placeholder="Sélectionner le compte OHADA"
          filterable
          style="width: 100%"
        >
          <el-option
            v-for="compte in comptesOhada"
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
        <div class="form-hint">Compte de trésorerie (classe 5)</div>
      </el-form-item>

      <el-form-item label="Observations">
        <el-input
          v-model="form.observations"
          type="textarea"
          :rows="2"
          placeholder="Observations..."
        />
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
import { Check } from '@element-plus/icons-vue';

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  banques: {
    type: Array,
    default: () => []
  },
  comptesOhada: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits(['update:modelValue', 'success']);

// State
const formRef = ref(null);
const loading = ref(false);
const newBanqueName = ref(null);

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const form = reactive({
  banque_id: null,
  numero_compte: '',
  compte_ohada_id: null,
  observations: ''
});

// Validation rules
const rules = {
  banque_id: [
    { required: true, message: 'La banque est obligatoire', trigger: 'change' }
  ],
  numero_compte: [
    { required: true, message: 'Le numéro de compte est obligatoire', trigger: 'blur' }
  ],
  compte_ohada_id: [
    { required: true, message: 'Le compte OHADA est obligatoire', trigger: 'change' }
  ]
};

// Methods
const handleBanqueChange = (value) => {
  // Si c'est une nouvelle valeur (string), on la stocke pour créer la banque
  if (typeof value === 'string') {
    newBanqueName.value = value;
  } else {
    newBanqueName.value = null;
  }
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const handleClosed = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  form.banque_id = null;
  form.numero_compte = '';
  form.compte_ohada_id = null;
  form.observations = '';
  newBanqueName.value = null;
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    loading.value = true;

    let banqueId = form.banque_id;

    // Si c'est une nouvelle banque, la créer d'abord
    if (newBanqueName.value) {
      const banqueResponse = await fetch('/api/banques', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ nom: newBanqueName.value })
      });

      const banqueResult = await banqueResponse.json();
      if (!banqueResult.success) {
        ElMessage.error(banqueResult.message || 'Erreur lors de la création de la banque');
        return;
      }
      banqueId = banqueResult.data.id;
    }

    // Créer le compte bancaire
    const response = await fetch('/api/comptes-bancaires', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        banque_id: banqueId,
        numero_compte: form.numero_compte,
        compte_ohada_id: form.compte_ohada_id,
        observations: form.observations || null
      })
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Compte bancaire créé avec succès');
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

<style scoped>
.form-hint {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.compte-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-option :deep(.el-tag) {
  min-width: 70px;
  text-align: center;
  font-family: 'Courier New', monospace;
}

.compte-libelle {
  font-size: 13px;
  color: #6b7280;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
