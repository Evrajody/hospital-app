<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ isEdit ? 'Modifier le compte' : 'Nouveau compte' }}</h1>
          <p class="page-subtitle">{{ isEdit ? `Modification du compte ${compte?.numero}` : 'Ajout d\'un nouveau compte comptable' }}</p>
        </div>
        <el-button @click="handleCancel">
          <el-icon><ArrowLeft /></el-icon>
          Retour
        </el-button>
      </div>

      <!-- Form Card -->
      <el-card class="form-card" shadow="never">
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-width="180px"
          label-position="left"
          size="large"
        >
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Numéro de compte" prop="numero" required>
                <el-input
                  v-model="form.numero"
                  placeholder="Ex: 601100"
                  maxlength="10"
                  show-word-limit
                >
                  <template #prepend>
                    <el-icon><Notebook /></el-icon>
                  </template>
                </el-input>
                <div class="form-help">
                  Suivez la nomenclature OHADA (ex: classe 6 pour charges)
                </div>
              </el-form-item>
            </el-col>

            <el-col :span="12">
              <el-form-item label="Type de compte" prop="type" required>
                <el-select
                  v-model="form.type"
                  placeholder="Sélectionnez un type"
                  style="width: 100%"
                >
                  <el-option label="Charge" value="charge">
                    <div class="select-option">
                      <el-icon><Money /></el-icon>
                      <span>Charge</span>
                    </div>
                  </el-option>
                  <el-option label="Immobilisation" value="immobilisation">
                    <div class="select-option">
                      <el-icon><Box /></el-icon>
                      <span>Immobilisation</span>
                    </div>
                  </el-option>
                  <el-option label="Tiers" value="tiers">
                    <div class="select-option">
                      <el-icon><UserFilled /></el-icon>
                      <span>Tiers</span>
                    </div>
                  </el-option>
                  <el-option label="Banque / Trésorerie" value="banque">
                    <div class="select-option">
                      <el-icon><CreditCard /></el-icon>
                      <span>Banque / Trésorerie</span>
                    </div>
                  </el-option>
                  <el-option label="Escompte" value="escompte">
                    <div class="select-option">
                      <el-icon><Discount /></el-icon>
                      <span>Escompte</span>
                    </div>
                  </el-option>
                  <el-option label="AIB" value="aib">
                    <div class="select-option">
                      <el-icon><DocumentChecked /></el-icon>
                      <span>AIB</span>
                    </div>
                  </el-option>
                  <el-option label="TVA" value="tva">
                    <div class="select-option">
                      <el-icon><Document /></el-icon>
                      <span>TVA</span>
                    </div>
                  </el-option>
                  <el-option label="Autre" value="autre">
                    <div class="select-option">
                      <el-icon><Grid /></el-icon>
                      <span>Autre</span>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Libellé" prop="libelle" required>
            <el-input
              v-model="form.libelle"
              placeholder="Ex: Achats de médicaments"
              maxlength="200"
              show-word-limit
            >
              <template #prepend>
                <el-icon><EditPen /></el-icon>
              </template>
            </el-input>
          </el-form-item>

          <el-form-item label="Compte parent" prop="compte_parent_id">
            <el-select
              v-model="form.compte_parent_id"
              placeholder="Sélectionnez un compte parent (optionnel)"
              filterable
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="parent in comptesParents"
                :key="parent.id"
                :label="`${parent.numero} - ${parent.libelle}`"
                :value="parent.id"
              >
                <div class="compte-option">
                  <span class="compte-numero">{{ parent.numero }}</span>
                  <span class="compte-libelle">{{ parent.libelle }}</span>
                </div>
              </el-option>
            </el-select>
            <div class="form-help">
              Utilisé pour créer une hiérarchie de comptes (ex: 601100 → 601000)
            </div>
          </el-form-item>

          <el-form-item label="Description" prop="description">
            <el-input
              v-model="form.description"
              type="textarea"
              :rows="3"
              placeholder="Description ou remarques sur l'utilisation de ce compte (optionnel)"
              maxlength="500"
              show-word-limit
            />
          </el-form-item>

          <!-- Info Box -->
          <el-alert
            type="info"
            :closable="false"
            show-icon
            style="margin-bottom: 20px"
          >
            <template #title>
              <strong>Nomenclature OHADA</strong>
            </template>
            <div class="ohada-info">
              <div><strong>Classe 1 :</strong> Comptes de capitaux</div>
              <div><strong>Classe 2 :</strong> Comptes d'immobilisations</div>
              <div><strong>Classe 3 :</strong> Comptes de stocks</div>
              <div><strong>Classe 4 :</strong> Comptes de tiers</div>
              <div><strong>Classe 5 :</strong> Comptes de trésorerie</div>
              <div><strong>Classe 6 :</strong> Comptes de charges</div>
              <div><strong>Classe 7 :</strong> Comptes de produits</div>
            </div>
          </el-alert>

          <!-- Form Actions -->
          <el-form-item>
            <div class="form-actions">
              <el-button @click="handleCancel" size="large">
                Annuler
              </el-button>
              <el-button
                type="primary"
                @click="handleSubmit"
                size="large"
                :loading="submitting"
              >
                <el-icon><Check /></el-icon>
                {{ isEdit ? 'Enregistrer les modifications' : 'Créer le compte' }}
              </el-button>
            </div>
          </el-form-item>
        </el-form>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  ArrowLeft,
  Check,
  Notebook,
  EditPen,
  Money,
  Box,
  UserFilled,
  CreditCard,
  Discount,
  DocumentChecked,
  Document,
  Grid
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  compte: {
    type: Object,
    default: null
  },
  comptesParents: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State
const formRef = ref(null);
const submitting = ref(false);
const isEdit = computed(() => !!props.compte);

const form = reactive({
  numero: props.compte?.numero || '',
  libelle: props.compte?.libelle || '',
  type: props.compte?.type || '',
  compte_parent_id: props.compte?.compte_parent_id || null,
  description: props.compte?.description || ''
});

const breadcrumbs = computed(() => [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Plan Comptable', path: '/plan-comptable' },
  { title: isEdit.value ? 'Modifier' : 'Nouveau compte', path: '' }
]);

// Validation rules
const rules = {
  numero: [
    { required: true, message: 'Le numéro de compte est requis', trigger: 'blur' },
    { min: 3, max: 10, message: 'Le numéro doit contenir entre 3 et 10 caractères', trigger: 'blur' },
    {
      pattern: /^[0-9]+$/,
      message: 'Le numéro doit contenir uniquement des chiffres',
      trigger: 'blur'
    }
  ],
  libelle: [
    { required: true, message: 'Le libellé est requis', trigger: 'blur' },
    { min: 3, max: 200, message: 'Le libellé doit contenir entre 3 et 200 caractères', trigger: 'blur' }
  ],
  type: [
    { required: true, message: 'Le type de compte est requis', trigger: 'change' }
  ]
};

// Methods
const handleSubmit = async () => {
  if (!formRef.value) return;

  try {
    const valid = await formRef.value.validate();
    if (!valid) return;

    submitting.value = true;

    // TODO: Appel API pour sauvegarder le compte
    setTimeout(() => {
      ElMessage.success(isEdit.value ? 'Compte modifié avec succès' : 'Compte créé avec succès');
      router.visit('/plan-comptable');
    }, 1000);
  } catch (error) {
    console.error('Validation failed:', error);
  } finally {
    submitting.value = false;
  }
};

const handleCancel = () => {
  router.visit('/plan-comptable');
};
</script>

<script>
export default {
  layout: null
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

.form-card {
  border-radius: 8px;
}

.form-help {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
  line-height: 1.4;
}

.select-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-option {
  display: flex;
  align-items: center;
  gap: 12px;
}

.compte-numero {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #2563eb;
  min-width: 80px;
}

.compte-libelle {
  flex: 1;
  color: #1f2937;
}

.ohada-info {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  margin-top: 8px;
  font-size: 13px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 30px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
}

:deep(.el-alert__content) {
  width: 100%;
}
</style>
