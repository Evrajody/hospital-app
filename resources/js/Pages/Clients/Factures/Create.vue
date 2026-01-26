<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="facture-create-container">
      <div class="page-header">
        <div>
          <h1>Nouvelle Facture Client</h1>
          <p class="subtitle">Enregistrement d'une nouvelle facture</p>
        </div>
      </div>

      <el-card shadow="never">
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="top"
          size="large"
        >
          <!-- Informations générales -->
          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Client" prop="client_id">
                <el-select
                  v-model="form.client_id"
                  placeholder="Sélectionner un client"
                  filterable
                  style="width: 100%"
                >
                  <el-option
                    v-for="client in clients"
                    :key="client.id"
                    :label="`${client.code} - ${client.nom}`"
                    :value="client.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :xs="24" :sm="12">
              <el-form-item label="Numéro de facture" prop="numero">
                <el-input
                  v-model="form.numero"
                  placeholder="Ex: FC-2024-001"
                  :prefix-icon="DocumentCopy"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Date de facture" prop="date_facture">
                <el-date-picker
                  v-model="form.date_facture"
                  type="date"
                  placeholder="Sélectionner la date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>

            <el-col :xs="24" :sm="12">
              <el-form-item label="Date d'échéance" prop="date_echeance">
                <el-date-picker
                  v-model="form.date_echeance"
                  type="date"
                  placeholder="Sélectionner la date"
                  format="DD/MM/YYYY"
                  value-format="YYYY-MM-DD"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider content-position="left">Montants</el-divider>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="8">
              <el-form-item label="Montant HT" prop="montant_ht">
                <el-input
                  v-model.number="form.montant_ht"
                  type="number"
                  placeholder="0"
                  @input="calculerMontants"
                >
                  <template #append>XOF</template>
                </el-input>
              </el-form-item>
            </el-col>

            <el-col :xs="24" :sm="8">
              <el-form-item label="TVA (18%)">
                <el-input
                  v-model="montant_tva_display"
                  disabled
                  readonly
                >
                  <template #append>XOF</template>
                </el-input>
              </el-form-item>
            </el-col>

            <el-col :xs="24" :sm="8">
              <el-form-item label="Montant TTC">
                <el-input
                  v-model="montant_ttc_display"
                  disabled
                  readonly
                  class="montant-ttc"
                >
                  <template #append>XOF</template>
                </el-input>
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider content-position="left">Détails</el-divider>

          <el-row :gutter="20">
            <el-col :xs="24">
              <el-form-item label="Description" prop="description">
                <el-input
                  v-model="form.description"
                  type="textarea"
                  :rows="3"
                  placeholder="Description de la prestation ou du produit"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24">
              <el-form-item label="Observation" prop="observation">
                <el-input
                  v-model="form.observation"
                  type="textarea"
                  :rows="3"
                  placeholder="Observations ou notes complémentaires"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Banque de dépôt" prop="banque_depot">
                <el-select
                  v-model="form.banque_depot"
                  placeholder="Sélectionner la banque"
                  clearable
                  style="width: 100%"
                >
                  <el-option label="BIBE - Banque Internationale du Bénin" value="BIBE" />
                  <el-option label="BOA - Bank of Africa" value="BOA" />
                  <el-option label="Ecobank Bénin" value="ECOBANK" />
                  <el-option label="SGBBE - Société Générale" value="SGBBE" />
                  <el-option label="CBAO - Compagnie Bancaire de l'Afrique de l'Ouest" value="CBAO" />
                </el-select>
              </el-form-item>
            </el-col>

            <el-col :xs="24" :sm="12">
              <el-form-item label="Mode de paiement prévu" prop="mode_paiement_prevu">
                <el-select
                  v-model="form.mode_paiement_prevu"
                  placeholder="Sélectionner le mode"
                  clearable
                  style="width: 100%"
                >
                  <el-option label="Espèces" value="especes" />
                  <el-option label="Chèque" value="cheque" />
                  <el-option label="Virement bancaire" value="virement" />
                  <el-option label="Carte bancaire" value="carte" />
                  <el-option label="Mobile Money" value="mobile_money" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Actions -->
          <div class="form-actions">
            <el-button @click="handleCancel">Annuler</el-button>
            <el-button type="primary" :loading="loading" @click="handleSubmit">
              Enregistrer la facture
            </el-button>
          </div>
        </el-form>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DocumentCopy } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  clients: {
    type: Array,
    default: () => []
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/clients/factures' },
  { title: 'Nouvelle Facture', path: '/clients/factures/create' }
];

// Form
const formRef = ref(null);
const loading = ref(false);

const form = ref({
  client_id: null,
  numero: '',
  date_facture: '',
  date_echeance: '',
  montant_ht: 0,
  description: '',
  observation: '',
  banque_depot: '',
  mode_paiement_prevu: ''
});

// Computed
const montant_tva_display = computed(() => {
  const tva = form.value.montant_ht * 0.18;
  return formatNumber(tva);
});

const montant_ttc_display = computed(() => {
  const ttc = form.value.montant_ht * 1.18;
  return formatNumber(ttc);
});

// Validation rules
const rules = {
  client_id: [
    { required: true, message: 'Veuillez sélectionner un client', trigger: 'change' }
  ],
  numero: [
    { required: true, message: 'Le numéro de facture est obligatoire', trigger: 'blur' }
  ],
  date_facture: [
    { required: true, message: 'La date de facture est obligatoire', trigger: 'change' }
  ],
  montant_ht: [
    { required: true, message: 'Le montant HT est obligatoire', trigger: 'blur' },
    { type: 'number', min: 0, message: 'Le montant doit être positif', trigger: 'blur' }
  ],
  description: [
    { required: true, message: 'La description est obligatoire', trigger: 'blur' }
  ]
};

// Methods
const calculerMontants = () => {
  // Reactive computation via computed properties
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0);
};

const handleSubmit = () => {
  formRef.value.validate((valid) => {
    if (valid) {
      loading.value = true;

      // TODO: Remplacer par l'envoi réel au backend
      setTimeout(() => {
        loading.value = false;
        ElMessage.success('Facture enregistrée avec succès');
        router.visit('/clients/factures');
      }, 1000);
    } else {
      ElMessage.error('Veuillez corriger les erreurs du formulaire');
    }
  });
};

const handleCancel = () => {
  router.visit('/clients/factures');
};
</script>

<style scoped>
.facture-create-container {
  max-width: 1000px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 28px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
}

.subtitle {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.el-divider {
  margin: 30px 0 20px 0;
}

.montant-ttc :deep(.el-input__inner) {
  font-weight: bold;
  font-size: 16px;
  color: #2563eb;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #e8e8e8;
}
</style>
