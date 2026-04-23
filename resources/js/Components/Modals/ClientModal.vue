<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier le Client' : 'Nouveau Client'"
    width="800px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @closed="handleClosed"
    class="client-modal"
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
          <el-icon><WarningFilled /></el-icon>
          Veuillez corriger les erreurs suivantes :
        </div>
      </template>
      <ul class="errors-list">
        <li v-for="(error, index) in validationErrors" :key="index" class="error-item">
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
      <el-tabs v-model="activeTab" type="border-card">
        <!-- Onglet 1: Informations Générales -->
        <el-tab-pane name="general" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><User /></el-icon>
              Informations
              <span class="tab-required-dot" v-if="tabHasRequiredEmpty('general')">*</span>
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <el-col :span="24">
                <el-form-item prop="nom">
                  <template #label>
                    <span>Raison Sociale <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model="form.nom"
                    placeholder="Nom du client"
                    :prefix-icon="User"
                  />
                </el-form-item>
              </el-col>

              <el-col :span="12">
                <el-form-item label="Téléphone" prop="telephone">
                  <el-input
                    v-model="form.telephone"
                    placeholder="+229 XX XX XX XX"
                    :prefix-icon="Phone"
                  />
                </el-form-item>
              </el-col>

              <el-col :span="12">
                <el-form-item label="IFU (optionnel)" prop="ifu">
                  <el-input
                    v-model="form.ifu"
                    placeholder="0000000000000"
                    maxlength="13"
                    show-word-limit
                  />
                  <div class="form-hint">13 chiffres</div>
                </el-form-item>
              </el-col>

              <el-col :span="12">
                <el-form-item prop="type_client">
                  <template #label>
                    <span>Type de client <span class="required-star">*</span></span>
                  </template>
                  <el-select v-model="form.type_client" style="width: 100%">
                    <el-option label="Société" value="societe" />
                    <el-option label="Divers" value="divers" />
                    <el-option label="Personnel" value="personnel" />
                    <el-option label="Autre" value="autre" />
                  </el-select>
                </el-form-item>
              </el-col>

              <el-col :span="12" />

              <el-col :span="24">
                <el-form-item label="Adresse" prop="adresse">
                  <el-input
                    v-model="form.adresse"
                    type="textarea"
                    :rows="2"
                    placeholder="Adresse du client"
                  />
                </el-form-item>
              </el-col>

              <el-col :span="24">
                <el-form-item label="Observation" prop="observation">
                  <el-input
                    v-model="form.observation"
                    type="textarea"
                    :rows="2"
                    placeholder="Notes ou informations complémentaires"
                  />
                </el-form-item>
              </el-col>
            </el-row>
          </div>
        </el-tab-pane>

        <!-- Onglet 2: Compte Comptable -->
        <el-tab-pane name="comptable" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Wallet /></el-icon>
              Compte Comptable
              <span class="tab-required-dot" v-if="tabHasRequiredEmpty('comptable')">*</span>
            </span>
          </template>

          <div class="tab-content">
            <el-alert
              type="info"
              :closable="false"
              class="mb-4"
            >
              <template #title>
                <div class="alert-title">Attribution du compte auxiliaire (Plan Comptable OHADA)</div>
              </template>
              <div class="alert-content">
                Tous les comptes clients (41xxx, 424100xxx) sont disponibles comme compte parent, y compris les comptes créés manuellement.
              </div>
            </el-alert>

            <el-form-item>
              <el-radio-group v-model="compteMode" @change="handleCompteModeChange">
                <el-radio-button value="select">
                  <el-icon><Search /></el-icon>
                  Sélectionner un compte existant
                </el-radio-button>
                <el-radio-button value="create">
                  <el-icon><Plus /></el-icon>
                  Créer un nouveau compte
                </el-radio-button>
              </el-radio-group>
            </el-form-item>

            <!-- Mode: Select existing -->
            <div v-if="compteMode === 'select'" class="compte-section">
              <el-form-item label="Compte Client" prop="compte_comptable_id">
                <el-select
                  v-model="form.compte_comptable_id"
                  filterable
                  placeholder="Rechercher un compte 41xxx ou 424100xxx..."
                  style="width: 100%"
                >
                  <el-option
                    v-for="compte in comptesClients"
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
                <div class="form-hint">
                  {{ comptesClients.length }} compte(s) disponible(s)
                </div>
              </el-form-item>

              <el-card v-if="selectedCompte" class="compte-preview" shadow="never">
                <div class="preview-header">
                  <el-icon><InfoFilled /></el-icon>
                  Compte sélectionné
                </div>
                <el-descriptions :column="2" size="small" border>
                  <el-descriptions-item label="Numéro">
                    <el-tag>{{ selectedCompte.numero }}</el-tag>
                  </el-descriptions-item>
                  <el-descriptions-item label="Libellé">
                    {{ selectedCompte.libelle }}
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>
            </div>

            <!-- Mode: Create new -->
            <div v-else class="compte-section">
              <el-row :gutter="20">
                <el-col :span="24">
                  <el-form-item label="Compte Parent" prop="compte_parent_id">
                    <el-select
                      v-model="form.compte_parent_id"
                      placeholder="Sélectionner un compte parent"
                      style="width: 100%"
                      filterable
                      @change="handleParentCompteChange"
                    >
                      <el-option
                        v-for="compte in comptesParents"
                        :key="compte.id"
                        :label="`${compte.numero} - ${compte.libelle}`"
                        :value="compte.id"
                      >
                        <div class="compte-option">
                          <el-tag size="small" type="warning">{{ compte.numero }}</el-tag>
                          <span class="compte-libelle">{{ compte.libelle }}</span>
                        </div>
                      </el-option>
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="Numéro de Compte" prop="nouveau_compte_numero">
                    <el-input
                      v-model="form.nouveau_compte_numero"
                      placeholder="4111.001"
                    >
                      <template #append>
                        <el-button :icon="MagicStick" @click="suggestAccountNumber">
                          Auto
                        </el-button>
                      </template>
                    </el-input>
                    <div class="form-hint">Format: compte_parent.XXX (auto-incrémenté)</div>
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="Libellé du Compte" prop="nouveau_compte_libelle">
                    <el-input
                      v-model="form.nouveau_compte_libelle"
                      placeholder="Libellé automatique avec le nom"
                    />
                    <div class="form-hint">Par défaut, le nom du client</div>
                  </el-form-item>
                </el-col>
              </el-row>

              <el-alert
                v-if="form.nouveau_compte_numero"
                type="success"
                :closable="false"
                class="mt-4"
              >
                <template #title>
                  <div class="alert-title">
                    <el-icon><SuccessFilled /></el-icon>
                    Compte à créer
                  </div>
                </template>
                <div class="nouveau-compte-preview">
                  <el-tag type="success" size="large">{{ form.nouveau_compte_numero }}</el-tag>
                  <span class="compte-preview-libelle">
                    {{ form.nouveau_compte_libelle || form.nom || 'Nouveau client' }}
                  </span>
                </div>
              </el-alert>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-form>

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
import {
  User,
  Phone,
  Wallet,
  Plus,
  Search,
  MagicStick,
  Check,
  InfoFilled,
  SuccessFilled,
  WarningFilled
} from '@element-plus/icons-vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  client: { type: Object, default: () => null },
  comptesClients: { type: Array, default: () => [] },
  comptesParents: { type: Array, default: () => [] },
  serverErrors: { type: Object, default: () => null },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue', 'success']);

const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const isEdit = computed(() => !!props.client);
const formRef = ref(null);
const activeTab = ref('general');
const compteMode = ref('select');
const validationErrors = ref([]);

const fieldLabels = {
  nom: 'Raison Sociale',
  telephone: 'Téléphone',
  ifu: 'IFU',
  type_client: 'Type de client',
  observation: 'Observation',
  adresse: 'Adresse',
  compte_comptable_id: 'Compte Client',
  nouveau_compte_numero: 'Numéro de Compte',
};

const clearErrors = () => { validationErrors.value = []; };

watch(() => props.serverErrors, (errors) => {
  if (errors && typeof errors === 'object') {
    const errorList = [];
    for (const [fieldName, messages] of Object.entries(errors)) {
      const fieldMessages = Array.isArray(messages) ? messages : [messages];
      for (const message of fieldMessages) {
        errorList.push({ field: fieldLabels[fieldName] || fieldName, message });
      }
    }
    validationErrors.value = errorList;
  }
}, { immediate: true, deep: true });

const getInitialFormData = () => ({
  nom: '',
  telephone: '',
  ifu: '',
  type_client: 'divers',
  observation: '',
  adresse: '',
  compte_comptable_id: null,
  compte_parent_id: null,
  nouveau_compte_numero: '',
  nouveau_compte_libelle: '',
});

const form = reactive(getInitialFormData());

watch(() => props.client, (newClient) => {
  if (newClient) {
    const initialData = getInitialFormData();
    Object.keys(initialData).forEach(key => { form[key] = initialData[key]; });
    Object.keys(form).forEach(key => {
      if (key in newClient && newClient[key] !== null) {
        form[key] = newClient[key];
      }
    });
    if (newClient.compte_comptable_id) {
      compteMode.value = 'select';
      form.compte_comptable_id = newClient.compte_comptable_id;
    }
  }
}, { immediate: true, deep: true });

watch(() => form.nom, (newNom) => {
  if (compteMode.value === 'create' && !form.nouveau_compte_libelle) {
    form.nouveau_compte_libelle = newNom;
  }
});

// Vérifier si un onglet a des champs requis vides
const tabHasRequiredEmpty = (tabName) => {
  if (tabName === 'general') {
    return !form.nom || form.nom.trim() === '';
  }
  if (tabName === 'comptable') {
    if (compteMode.value === 'select') {
      return !form.compte_comptable_id;
    } else {
      return !form.nouveau_compte_numero || form.nouveau_compte_numero.trim() === '';
    }
  }
  return false;
};

const selectedCompte = computed(() => {
  if (compteMode.value === 'select' && form.compte_comptable_id) {
    return props.comptesClients.find(c => c.id === form.compte_comptable_id);
  }
  return null;
});

const rules = computed(() => ({
  nom: [
    { required: true, message: 'La raison sociale est obligatoire', trigger: 'blur' },
    { min: 2, max: 255, message: 'Le nom doit contenir entre 2 et 255 caractères', trigger: 'blur' }
  ],
  ifu: [
    {
      validator: (rule, value, callback) => {
        if (value && !/^\d{13}$/.test(value)) {
          callback(new Error("L'IFU doit contenir exactement 13 chiffres"));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ],
  type_client: [
    { required: true, message: 'Le type de client est obligatoire', trigger: 'change' }
  ],
  nouveau_compte_numero: [
    {
      validator: (rule, value, callback) => {
        if (compteMode.value === 'create' && !value) {
          callback(new Error('Le numéro de compte est obligatoire'));
        } else if (compteMode.value === 'create' && !/^(41|4111|424100)[a-zA-Z0-9.]+$/.test(value)) {
          callback(new Error('Le numéro doit commencer par 41, 4111 ou 424100'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ]
}));

const handleCompteModeChange = () => {
  form.compte_comptable_id = null;
  form.compte_parent_id = null;
  form.nouveau_compte_numero = '';
  form.nouveau_compte_libelle = '';
};

const handleParentCompteChange = () => {
  suggestAccountNumber();
};

const suggestAccountNumber = () => {
  const parentCompte = props.comptesParents.find(c => c.id === form.compte_parent_id);
  if (!parentCompte) {
    ElMessage.warning('Veuillez d\'abord sélectionner un compte parent');
    return;
  }

  const parentNumero = parentCompte.numero;

  const childAccounts = props.comptesClients
    .map(c => c.numero)
    .filter(n => n.startsWith(parentNumero + '.'));

  let maxSuffix = 0;
  for (const numero of childAccounts) {
    const suffix = numero.substring(parentNumero.length + 1);
    const num = parseInt(suffix, 10);
    if (!isNaN(num) && num > maxSuffix) {
      maxSuffix = num;
    }
  }

  const nextSuffix = String(maxSuffix + 1).padStart(3, '0');
  form.nouveau_compte_numero = `${parentNumero}.${nextSuffix}`;
};

const handleCancel = () => { dialogVisible.value = false; };

const handleClosed = () => {
  if (formRef.value) formRef.value.resetFields();
  const initialData = getInitialFormData();
  Object.keys(initialData).forEach(key => { form[key] = initialData[key]; });
  activeTab.value = 'general';
  compteMode.value = 'select';
  validationErrors.value = [];
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  validationErrors.value = [];

  try {
    await formRef.value.validate();

    const data = { ...form };
    if (compteMode.value === 'create') {
      data.create_compte = true;
      data.compte_comptable_id = null;
    } else {
      data.create_compte = false;
      data.compte_parent_id = null;
      data.nouveau_compte_numero = '';
      data.nouveau_compte_libelle = '';
    }

    emit('success', data);
  } catch (error) {
    const errors = [];
    for (const fieldName of Object.keys(error || {})) {
      const fieldErrors = error[fieldName];
      if (Array.isArray(fieldErrors)) {
        for (const fieldError of fieldErrors) {
          errors.push({ field: fieldLabels[fieldName] || fieldName, message: fieldError.message || fieldError });
        }
      }
    }
    validationErrors.value = errors;
    ElMessage.error(`${errors.length} erreur(s) détectée(s)`);
  }
};
</script>

<style scoped>
.global-errors-alert { margin: 16px 20px; border-radius: 8px; }
.errors-title { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; }
.errors-list { margin: 8px 0 0 0; padding-left: 20px; list-style: disc; }
.error-item { margin: 4px 0; font-size: 13px; }
.error-field { font-weight: 600; color: #c45656; }

.client-modal :deep(.el-dialog) { border-radius: 12px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; }
.client-modal :deep(.el-dialog__body) { padding: 0; overflow: hidden; }
.client-modal :deep(.el-tabs__content) { padding: 0; max-height: calc(90vh - 200px); overflow-y: auto; }
.client-modal :deep(.el-tab-pane) { padding: 20px; }

.tab-label { display: flex; align-items: center; gap: 6px; font-size: 14px; }
.tab-required-dot { color: #f56c6c; font-size: 16px; font-weight: bold; margin-left: 4px; vertical-align: middle; }
.tab-content { min-height: 200px; }
.required-star { color: #f56c6c; margin-left: 2px; }
.form-hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }
.alert-title { font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.alert-content { font-size: 13px; line-height: 1.6; margin-top: 4px; }
.compte-section { margin-top: 20px; }
.compte-option { display: flex; align-items: center; gap: 8px; }
.compte-option :deep(.el-tag) { min-width: 85px; text-align: center; font-family: 'Courier New', monospace; }
.compte-libelle { font-size: 13px; color: #6b7280; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.compte-preview { margin-top: 16px; background-color: #f9fafb; }
.preview-header { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #374151; margin-bottom: 12px; }
.nouveau-compte-preview { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
.compte-preview-libelle { font-size: 15px; font-weight: 500; color: #1f2937; }
.dialog-footer { display: flex; justify-content: flex-end; gap: 12px; }
.mb-4 { margin-bottom: 16px; }
.mt-4 { margin-top: 16px; }

:deep(.el-form-item__label) { font-weight: 600; color: #374151; font-size: 14px; }
:deep(.el-tabs--border-card) { border-radius: 8px; border: none; box-shadow: none; }
:deep(.el-tabs__header) { background-color: #f3f4f6; border-radius: 8px 8px 0 0; }
:deep(.el-tabs__item.is-active) { background-color: white; border-radius: 8px 8px 0 0; }
</style>
