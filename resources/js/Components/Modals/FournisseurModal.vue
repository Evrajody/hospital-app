<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier le Fournisseur' : 'Nouveau Fournisseur'"
    width="85%"
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
      <el-row :gutter="24">
        <!-- Left Column -->
        <el-col :span="12">
          <h3 class="section-title">Informations Générales</h3>

          <el-form-item label="Nom du Fournisseur" prop="nom">
            <el-input
              v-model="form.nom"
              placeholder="Raison sociale complète"
              :prefix-icon="OfficeBuilding"
            />
          </el-form-item>

          <el-form-item label="Personne de Contact" prop="contact">
            <el-input
              v-model="form.contact"
              placeholder="Nom de la personne à contacter"
              :prefix-icon="User"
            />
          </el-form-item>

          <el-row :gutter="16">
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
              <el-form-item label="Email" prop="email">
                <el-input
                  v-model="form.email"
                  type="email"
                  placeholder="email@example.com"
                  :prefix-icon="Message"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Adresse" prop="adresse">
            <el-input
              v-model="form.adresse"
              type="textarea"
              :rows="3"
              placeholder="Adresse complète du fournisseur"
            />
          </el-form-item>

          <el-form-item label="Statut" prop="status">
            <el-radio-group v-model="form.status">
              <el-radio value="actif">Actif</el-radio>
              <el-radio value="inactif">Inactif</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>

        <!-- Right Column -->
        <el-col :span="12">
          <h3 class="section-title">Compte Comptable</h3>

          <el-alert
            type="info"
            :closable="false"
            style="margin-bottom: 20px"
          >
            <template #title>
              <div style="font-size: 14px; font-weight: 500;">
                Attribution du compte auxiliaire
              </div>
            </template>
            <div style="font-size: 13px; line-height: 1.6;">
              Chaque fournisseur doit avoir son propre compte auxiliaire dans la classe 401 (Fournisseurs).
              Vous pouvez soit sélectionner un compte existant, soit créer un nouveau compte.
            </div>
          </el-alert>

          <el-form-item>
            <el-radio-group v-model="compteMode" @change="handleCompteModeChange">
              <el-radio value="select">Sélectionner un compte existant</el-radio>
              <el-radio value="create">Créer un nouveau compte</el-radio>
            </el-radio-group>
          </el-form-item>

          <!-- Mode: Select existing account -->
          <div v-if="compteMode === 'select'">
            <el-form-item label="Compte Fournisseur" prop="compte_comptable_id">
              <el-select
                v-model="form.compte_comptable_id"
                filterable
                placeholder="Rechercher un compte..."
                style="width: 100%"
                @change="handleCompteChange"
              >
                <el-option
                  v-for="compte in comptesFournisseurs"
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
              <div class="form-hint">Sélectionnez le compte comptable associé à ce fournisseur</div>
            </el-form-item>
          </div>

          <!-- Mode: Create new account -->
          <div v-else>
            <el-form-item label="Compte Parent" prop="compte_parent_id">
              <el-select
                v-model="form.compte_parent_id"
                placeholder="401000 - Fournisseurs"
                style="width: 100%"
                @change="handleParentCompteChange"
              >
                <el-option
                  v-for="compte in comptesParents"
                  :key="compte.id"
                  :label="`${compte.numero} - ${compte.libelle}`"
                  :value="compte.id"
                />
              </el-select>
              <div class="form-hint">Compte général sous lequel créer le compte auxiliaire</div>
            </el-form-item>

            <el-form-item label="Numéro de Compte" prop="nouveau_compte_numero">
              <el-input
                v-model="form.nouveau_compte_numero"
                placeholder="401001"
                :prefix-icon="Document"
              >
                <template #append>
                  <el-button :icon="MagicStick" @click="suggestAccountNumber">
                    Auto
                  </el-button>
                </template>
              </el-input>
              <div class="form-hint">Numéro unique du compte auxiliaire (ex: 401001, 401002...)</div>
            </el-form-item>

            <el-form-item label="Libellé du Compte" prop="nouveau_compte_libelle">
              <el-input
                v-model="form.nouveau_compte_libelle"
                placeholder="Le libellé sera rempli automatiquement avec le nom du fournisseur"
                :prefix-icon="Edit"
              />
              <div class="form-hint">Par défaut, le nom du fournisseur sera utilisé</div>
            </el-form-item>

            <el-alert
              v-if="form.nouveau_compte_numero"
              type="success"
              :closable="false"
              style="margin-top: 16px"
            >
              <template #title>
                <div style="font-size: 13px;">
                  <strong>Compte à créer :</strong>
                  {{ form.nouveau_compte_numero }} - {{ form.nouveau_compte_libelle || form.nom || 'Nouveau fournisseur' }}
                </div>
              </template>
            </el-alert>
          </div>

          <el-divider />

          <h3 class="section-title">Informations Fiscales</h3>

          <el-form-item label="IFU (Identifiant Fiscal Unique)" prop="ifu">
            <el-input
              v-model="form.ifu"
              placeholder="0000000000000"
              maxlength="13"
              show-word-limit
              :prefix-icon="CreditCard"
            />
            <div class="form-hint">13 chiffres - Obligatoire pour les fournisseurs au Bénin</div>
          </el-form-item>

          <el-form-item label="Autres Informations" prop="other_infos">
            <el-input
              v-model="form.other_infos"
              type="textarea"
              :rows="3"
              placeholder="Notes ou informations complémentaires..."
            />
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleCancel">Annuler</el-button>
        <el-button
          type="primary"
          :loading="submitting"
          @click="handleSubmit"
        >
          <el-icon v-if="!submitting"><Check /></el-icon>
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
  OfficeBuilding,
  User,
  Phone,
  Message,
  Document,
  MagicStick,
  Edit,
  CreditCard,
  Check
} from '@element-plus/icons-vue';

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  fournisseur: {
    type: Object,
    default: () => null
  },
  comptesFournisseurs: {
    type: Array,
    default: () => []
  },
  comptesParents: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits(['update:modelValue', 'success']);

// State
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const isEdit = computed(() => !!props.fournisseur);
const formRef = ref(null);
const submitting = ref(false);
const compteMode = ref('select'); // 'select' or 'create'

// Form data
const form = reactive({
  nom: props.fournisseur?.nom || '',
  contact: props.fournisseur?.contact || '',
  telephone: props.fournisseur?.telephone || '',
  email: props.fournisseur?.email || '',
  adresse: props.fournisseur?.adresse || '',
  status: props.fournisseur?.status || 'actif',
  compte_comptable_id: props.fournisseur?.compte_comptable_id || null,
  compte_parent_id: null,
  nouveau_compte_numero: '',
  nouveau_compte_libelle: '',
  ifu: props.fournisseur?.ifu || '',
  rccm: props.fournisseur?.rccm || '',
  other_infos: props.fournisseur?.other_infos || ''
});

// Watch nom to auto-fill compte libelle
watch(() => form.nom, (newNom) => {
  if (compteMode.value === 'create' && !form.nouveau_compte_libelle) {
    form.nouveau_compte_libelle = newNom;
  }
});

// Validation rules
const rules = {
  nom: [
    { required: true, message: 'Le nom est obligatoire', trigger: 'blur' },
    { min: 2, max: 255, message: 'Le nom doit contenir entre 2 et 255 caractères', trigger: 'blur' }
  ],
  email: [
    { type: 'email', message: 'Email invalide', trigger: 'blur' }
  ],
  telephone: [
    { pattern: /^[+]?[\d\s-()]+$/, message: 'Numéro de téléphone invalide', trigger: 'blur' }
  ],
  compte_comptable_id: [
    {
      validator: (rule, value, callback) => {
        if (compteMode.value === 'select' && !value) {
          callback(new Error('Veuillez sélectionner un compte comptable'));
        } else {
          callback();
        }
      },
      trigger: 'change'
    }
  ],
  nouveau_compte_numero: [
    {
      validator: (rule, value, callback) => {
        if (compteMode.value === 'create' && !value) {
          callback(new Error('Le numéro de compte est obligatoire'));
        } else if (compteMode.value === 'create' && !/^401\d{3,}$/.test(value)) {
          callback(new Error('Le numéro doit commencer par 401 (ex: 401001)'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ],
  ifu: [
    {
      pattern: /^\d{13}$/,
      message: 'L\'IFU doit contenir exactement 13 chiffres',
      trigger: 'blur'
    }
  ]
};

// Methods
const handleCompteModeChange = () => {
  // Reset compte fields when switching modes
  form.compte_comptable_id = null;
  form.compte_parent_id = null;
  form.nouveau_compte_numero = '';
  form.nouveau_compte_libelle = '';
};

const handleCompteChange = (compteId) => {
  const compte = props.comptesFournisseurs.find(c => c.id === compteId);
  if (compte) {
    console.log('Compte sélectionné:', compte);
  }
};

const handleParentCompteChange = () => {
  // Auto-suggest account number when parent changes
  suggestAccountNumber();
};

const suggestAccountNumber = () => {
  // TODO: Call backend to get next available account number
  // For now, generate a simple suggestion
  const baseNumber = 401000;
  const random = Math.floor(Math.random() * 999) + 1;
  form.nouveau_compte_numero = `${baseNumber + random}`;
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const handleClosed = () => {
  // Réinitialiser le formulaire après fermeture
  if (formRef.value) {
    formRef.value.resetFields();
  }
  compteMode.value = 'select';
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate((valid) => {
    if (valid) {
      submitting.value = true;

      // Prepare data based on compte mode
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

      // TODO: Replace with actual API call when backend is ready
      setTimeout(() => {
        ElMessage.success(
          isEdit.value
            ? 'Fournisseur modifié avec succès'
            : 'Fournisseur créé avec succès'
        );
        emit('success', data);
        dialogVisible.value = false;
        submitting.value = false;
      }, 1000);
    } else {
      ElMessage.error('Veuillez corriger les erreurs du formulaire');
    }
  });
};
</script>

<style scoped>
.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 20px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid #e5e7eb;
}

.form-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}

.compte-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-libelle {
  font-size: 13px;
  color: #6b7280;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

:deep(.el-dialog) {
  border-radius: 8px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

:deep(.el-dialog__body) {
  overflow-y: auto;
  max-height: calc(90vh - 150px);
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

:deep(.el-input__inner),
:deep(.el-textarea__inner) {
  border-radius: 6px;
}

:deep(.el-alert) {
  border-radius: 6px;
}

:deep(.el-divider) {
  margin: 24px 0;
}
</style>