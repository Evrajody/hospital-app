<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="client-edit-container">
      <div class="page-header">
        <div>
          <h1>Modifier le Client</h1>
          <p class="subtitle">{{ client.code }} - {{ client.nom }}</p>
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
          <el-row :gutter="20">
            <!-- Code client -->
            <el-col :xs="24" :sm="12">
              <el-form-item label="Code Client" prop="code">
                <el-input
                  v-model="form.code"
                  placeholder="Ex: CLI-001"
                  :prefix-icon="DocumentCopy"
                />
              </el-form-item>
            </el-col>

            <!-- Type de client -->
            <el-col :xs="24" :sm="12">
              <el-form-item label="Type de Client" prop="type">
                <el-select v-model="form.type" placeholder="Sélectionner le type" style="width: 100%">
                  <el-option label="Particulier" value="particulier" />
                  <el-option label="Entreprise" value="entreprise" />
                  <el-option label="Assurance" value="assurance" />
                  <el-option label="Mutuelle" value="mutuelle" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <!-- Nom du client -->
            <el-col :xs="24">
              <el-form-item label="Nom du Client" prop="nom">
                <el-input
                  v-model="form.nom"
                  placeholder="Nom complet ou raison sociale"
                  :prefix-icon="User"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider content-position="left">Informations de Contact</el-divider>

          <el-row :gutter="20">
            <!-- Téléphone -->
            <el-col :xs="24" :sm="12">
              <el-form-item label="Téléphone" prop="telephone">
                <el-input
                  v-model="form.telephone"
                  placeholder="+229 XX XX XX XX"
                  :prefix-icon="Phone"
                />
              </el-form-item>
            </el-col>

            <!-- Email -->
            <el-col :xs="24" :sm="12">
              <el-form-item label="Email" prop="email">
                <el-input
                  v-model="form.email"
                  type="email"
                  placeholder="email@exemple.com"
                  :prefix-icon="Message"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <!-- Adresse -->
            <el-col :xs="24">
              <el-form-item label="Adresse" prop="adresse">
                <el-input
                  v-model="form.adresse"
                  type="textarea"
                  :rows="3"
                  placeholder="Adresse complète du client"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider content-position="left">Informations Complémentaires</el-divider>

          <el-row :gutter="20">
            <!-- IFU (pour entreprises) -->
            <el-col :xs="24" :sm="12">
              <el-form-item label="IFU (Identifiant Fiscal Unique)" prop="ifu">
                <el-input
                  v-model="form.ifu"
                  placeholder="Numéro IFU"
                  :prefix-icon="Postcard"
                />
              </el-form-item>
            </el-col>

            <!-- Numéro assurance -->
            <el-col :xs="24" :sm="12">
              <el-form-item label="Numéro d'assurance" prop="numero_assurance">
                <el-input
                  v-model="form.numero_assurance"
                  placeholder="Numéro de police d'assurance"
                  :prefix-icon="Tickets"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <!-- Notes -->
            <el-col :xs="24">
              <el-form-item label="Notes / Observations" prop="notes">
                <el-input
                  v-model="form.notes"
                  type="textarea"
                  :rows="3"
                  placeholder="Informations complémentaires"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Actions -->
          <div class="form-actions">
            <el-button @click="handleCancel">Annuler</el-button>
            <el-button type="primary" :loading="loading" @click="handleSubmit">
              Enregistrer les modifications
            </el-button>
          </div>
        </el-form>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DocumentCopy, User, Phone, Message, Postcard, Tickets } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  client: {
    type: Object,
    required: true
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Clients', path: '/clients' },
  { title: 'Modifier', path: `/clients/${props.client.id}/edit` }
];

// Form
const formRef = ref(null);
const loading = ref(false);

const form = ref({
  code: '',
  type: 'particulier',
  nom: '',
  telephone: '',
  email: '',
  adresse: '',
  ifu: '',
  numero_assurance: '',
  notes: ''
});

// Validation rules
const rules = {
  code: [
    { required: true, message: 'Le code client est obligatoire', trigger: 'blur' }
  ],
  type: [
    { required: true, message: 'Le type de client est obligatoire', trigger: 'change' }
  ],
  nom: [
    { required: true, message: 'Le nom du client est obligatoire', trigger: 'blur' }
  ],
  telephone: [
    { required: true, message: 'Le téléphone est obligatoire', trigger: 'blur' }
  ],
  email: [
    { type: 'email', message: 'Email invalide', trigger: 'blur' }
  ]
};

// Lifecycle
onMounted(() => {
  // Populate form with client data
  form.value = {
    code: props.client.code || '',
    type: props.client.type || 'particulier',
    nom: props.client.nom || '',
    telephone: props.client.telephone || '',
    email: props.client.email || '',
    adresse: props.client.adresse || '',
    ifu: props.client.ifu || '',
    numero_assurance: props.client.numero_assurance || '',
    notes: props.client.notes || ''
  };
});

// Methods
const handleSubmit = () => {
  formRef.value.validate((valid) => {
    if (valid) {
      loading.value = true;

      // TODO: Remplacer par l'envoi réel au backend
      setTimeout(() => {
        loading.value = false;
        ElMessage.success('Client modifié avec succès');
        router.visit('/clients');
      }, 1000);
    } else {
      ElMessage.error('Veuillez corriger les erreurs du formulaire');
    }
  });
};

const handleCancel = () => {
  router.visit('/clients');
};
</script>

<style scoped>
.client-edit-container {
  max-width: 900px;
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #e8e8e8;
}
</style>
