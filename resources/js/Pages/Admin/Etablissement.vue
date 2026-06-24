<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <div class="page-header">
        <h1 class="page-title">Paramètres de l'établissement</h1>
        <p class="page-subtitle">Informations affichées dans les en-têtes des rapports et documents PDF</p>
      </div>

      <el-card shadow="hover" class="form-card">
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="top"
        >
          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Nom de l'établissement" prop="nom">
                <el-input v-model="form.nom" placeholder="Ex: Hôpital de zone de Ménontin" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12">
              <el-form-item label="Pays / Entité" prop="pays">
                <el-input v-model="form.pays" placeholder="Ex: République du Bénin" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Adresse" prop="adresse">
                <el-input v-model="form.adresse" placeholder="Ex: BP 123 - Cotonou" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12">
              <el-form-item label="Téléphone" prop="telephone">
                <el-input v-model="form.telephone" placeholder="+229 21 XX XX XX" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Email" prop="email">
                <el-input v-model="form.email" type="email" placeholder="contact@hopital.bj" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12">
              <el-form-item label="IFU (Identifiant Fiscal Unique)" prop="ifu">
                <el-input v-model="form.ifu" placeholder="Ex: 3201912345678" />
                <div class="form-hint">Apparaît dans les en-têtes des rapports et déclarations fiscales</div>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Nom du Directeur" prop="directeur">
                <el-input v-model="form.directeur" placeholder="Ex: Dr. Jean DUPONT" />
                <div class="form-hint">Apparaît en bas des bordereaux de règlement</div>
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider content-position="left">En-tête officiel (coin haut-droit des PDF)</el-divider>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Boîte postale (BP)" prop="entete_bp">
                <el-input v-model="form.entete_bp" placeholder="Ex: BP 01-882 BENIN" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12">
              <el-form-item label="Téléphone (en-tête)" prop="entete_tel">
                <el-input v-model="form.entete_tel" placeholder="Ex: +229 21 33 21 78 / 21 33 21 63" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :xs="24" :sm="12">
              <el-form-item label="Email (en-tête)" prop="entete_email">
                <el-input v-model="form.entete_email" placeholder="Ex: info@sante.gouv.bj" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12">
              <el-form-item label="Site web (en-tête)" prop="entete_site">
                <el-input v-model="form.entete_site" placeholder="Ex: www.sante.gouv.bj" />
              </el-form-item>
            </el-col>
          </el-row>

          <div class="form-actions">
            <el-button v-if="can('parametres.modifier')" type="primary" @click="submitForm" :loading="loading">
              Enregistrer les paramètres
            </el-button>
          </div>
        </el-form>
      </el-card>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { ElMessage } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
  etablissement: { type: Object, default: () => ({}) },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Paramètres', path: '' },
  { title: 'Établissement', path: '/parametres/etablissement' },
];

const formRef = ref(null);
const loading = ref(false);

const form = reactive({
  nom: props.etablissement?.nom || '',
  pays: props.etablissement?.pays || '',
  adresse: props.etablissement?.adresse || '',
  telephone: props.etablissement?.telephone || '',
  email: props.etablissement?.email || '',
  directeur: props.etablissement?.directeur || '',
  ifu: props.etablissement?.ifu || '',
  entete_bp: props.etablissement?.entete_bp || '',
  entete_tel: props.etablissement?.entete_tel || '',
  entete_email: props.etablissement?.entete_email || '',
  entete_site: props.etablissement?.entete_site || '',
});

const rules = {
  nom: [{ required: true, message: "Le nom de l'établissement est requis", trigger: 'blur' }],
};

const submitForm = async () => {
  const valid = await formRef.value?.validate().catch(() => false);
  if (!valid) return;

  loading.value = true;
  try {
    const { data } = await axios.put('/parametres/etablissement', form);
    ElMessage.success(data.message);
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors de la mise à jour';
    ElMessage.error(msg);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.page-container {
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.page-subtitle {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.form-card {
  margin-bottom: 20px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 8px;
}

.form-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}
</style>
