<template>
  <el-dialog
    v-model="dialogVisible"
    title="Nouveau Compte Comptable"
    width="550px"
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
      <el-row :gutter="16">
        <el-col :span="12">
          <el-form-item label="Compte parent" prop="parent_id">
            <el-select
              v-model="form.parent_id"
              placeholder="Sélectionner un compte parent"
              filterable
              clearable
              style="width: 100%"
              @change="handleParentChange"
            >
              <el-option
                v-for="compte in comptesParents"
                :key="compte.id"
                :label="`${compte.numero} - ${compte.libelle}`"
                :value="compte.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Numéro de compte" prop="numero_compte">
            <el-input
              v-model="form.numero_compte"
              placeholder="Ex: 401001"
              maxlength="10"
            >
              <template #prepend v-if="parentPrefix">{{ parentPrefix }}</template>
            </el-input>
          </el-form-item>
        </el-col>
      </el-row>

      <el-form-item label="Libellé du compte" prop="libelle">
        <el-input
          v-model="form.libelle"
          placeholder="Ex: Fournisseur XYZ"
          maxlength="500"
        />
      </el-form-item>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form-item label="Type de compte" prop="type_compte">
            <el-select
              v-model="form.type_compte"
              placeholder="Sélectionner"
              style="width: 100%"
            >
              <el-option label="Actif" value="ACTIF" />
              <el-option label="Passif" value="PASSIF" />
              <el-option label="Charge" value="CHARGE" />
              <el-option label="Produit" value="PRODUIT" />
              <el-option label="Spécial" value="SPECIAL" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Utilisable">
            <el-switch
              v-model="form.utilisable"
              active-text="Oui"
              inactive-text="Non"
            />
            <div class="form-tip">
              Un compte utilisable peut être utilisé dans les écritures comptables
            </div>
          </el-form-item>
        </el-col>
      </el-row>

      <el-alert
        type="info"
        :closable="false"
        show-icon
      >
        <template #title>
          Ce compte sera marqué comme <strong>personnalisé</strong> et pourra être supprimé ultérieurement.
        </template>
      </el-alert>
    </el-form>

    <template #footer>
      <el-button @click="dialogVisible = false">Annuler</el-button>
      <el-button type="primary" @click="handleSubmit" :loading="loading">
        Créer le compte
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  comptesParents: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'success']);

const formRef = ref(null);
const loading = ref(false);
const parentPrefix = ref('');

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const form = reactive({
  parent_id: null,
  numero_compte: '',
  libelle: '',
  type_compte: '',
  utilisable: true
});

const rules = {
  numero_compte: [
    { required: true, message: 'Le numéro est obligatoire', trigger: 'blur' },
    { pattern: /^[0-9]+$/, message: 'Le numéro doit contenir uniquement des chiffres', trigger: 'blur' },
    { min: 2, max: 10, message: 'Le numéro doit contenir entre 2 et 10 chiffres', trigger: 'blur' }
  ],
  libelle: [
    { required: true, message: 'Le libellé est obligatoire', trigger: 'blur' },
    { min: 2, message: 'Le libellé doit contenir au moins 2 caractères', trigger: 'blur' }
  ],
  type_compte: [
    { required: true, message: 'Le type est obligatoire', trigger: 'change' }
  ]
};

const handleParentChange = (parentId) => {
  if (parentId) {
    const parent = props.comptesParents.find(c => c.id === parentId);
    if (parent) {
      parentPrefix.value = parent.numero;
      // Auto-suggest type based on parent's class
      const classe = parent.classe;
      if ([2, 3, 5].includes(classe)) {
        form.type_compte = 'ACTIF';
      } else if (classe === 1) {
        form.type_compte = 'PASSIF';
      } else if (classe === 6) {
        form.type_compte = 'CHARGE';
      } else if (classe === 7) {
        form.type_compte = 'PRODUIT';
      } else if (classe === 4) {
        form.type_compte = 'ACTIF'; // Default for tiers
      }
    }
  } else {
    parentPrefix.value = '';
  }
};

const handleClosed = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  form.parent_id = null;
  form.numero_compte = '';
  form.libelle = '';
  form.type_compte = '';
  form.utilisable = true;
  parentPrefix.value = '';
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    loading.value = true;

    // Build full numero_compte with parent prefix if applicable
    const fullNumero = parentPrefix.value
      ? parentPrefix.value + form.numero_compte
      : form.numero_compte;

    const response = await fetch('/api/plan-comptable', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        parent_id: form.parent_id,
        numero_compte: fullNumero,
        libelle: form.libelle,
        type_compte: form.type_compte,
        utilisable: form.utilisable
      })
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Compte créé avec succès');
      emit('success', result.data);
      dialogVisible.value = false;
    } else {
      ElMessage.error(result.message || 'Une erreur est survenue');
    }
  } catch (error) {
    console.error('Erreur:', error);
    ElMessage.error('Erreur lors de la création du compte');
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}
</style>
