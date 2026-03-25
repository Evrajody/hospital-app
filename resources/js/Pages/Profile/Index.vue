<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="profile-page">
      <div class="page-header">
        <h1>Mon Profil</h1>
        <p class="subtitle">Gérer vos informations personnelles</p>
      </div>

      <el-row :gutter="24">
        <!-- Left: Profile Info Card -->
        <el-col :xs="24" :lg="8">
          <el-card shadow="hover" class="profile-card">
            <div class="profile-avatar-section">
              <el-avatar :size="80" :icon="UserFilled" />
              <h2 class="profile-name">{{ profile.name }}</h2>
              <el-tag type="primary" size="small">{{ profile.role_libelle }}</el-tag>
              <p class="profile-since">Membre depuis le {{ profile.created_at }}</p>
            </div>

            <el-divider />

            <div class="profile-details">
              <div class="detail-item">
                <el-icon><Message /></el-icon>
                <span>{{ profile.email }}</span>
              </div>
              <div v-if="profile.telephone" class="detail-item">
                <el-icon><Phone /></el-icon>
                <span>{{ profile.telephone }}</span>
              </div>
              <div v-if="profile.poste" class="detail-item">
                <el-icon><Briefcase /></el-icon>
                <span>{{ profile.poste }}</span>
              </div>
            </div>
          </el-card>

          <!-- Signature Card -->
          <el-card shadow="hover" class="signature-card">
            <template #header>
              <h3>Ma Signature</h3>
            </template>

            <div class="signature-section">
              <div v-if="signatureUrl" class="signature-preview">
                <img :src="signatureUrl" alt="Signature" class="signature-img" />
                <el-button type="danger" size="small" plain @click="removeSignature" :loading="signatureLoading">
                  Supprimer
                </el-button>
              </div>
              <div v-else class="signature-empty">
                <el-icon :size="40" color="#ccc"><Edit /></el-icon>
                <p>Aucune signature enregistrée</p>
              </div>

              <el-upload
                ref="uploadRef"
                :auto-upload="false"
                :show-file-list="false"
                accept="image/png,image/jpeg,image/jpg"
                :on-change="handleSignatureSelect"
              >
                <template #trigger>
                  <el-button type="primary" size="small">
                    {{ signatureUrl ? 'Changer la signature' : 'Ajouter une signature' }}
                  </el-button>
                </template>
              </el-upload>
              <p class="signature-hint">PNG ou JPG, max 2 Mo</p>
            </div>
          </el-card>
        </el-col>

        <!-- Right: Edit Forms -->
        <el-col :xs="24" :lg="16">
          <!-- Personal Info Form -->
          <el-card shadow="hover" class="form-card">
            <template #header>
              <h3>Informations personnelles</h3>
            </template>

            <el-form
              ref="profileFormRef"
              :model="profileForm"
              :rules="profileRules"
              label-position="top"
            >
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Nom complet" prop="name">
                    <el-input v-model="profileForm.name" placeholder="Votre nom" />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Adresse email" prop="email">
                    <el-input v-model="profileForm.email" type="email" placeholder="votre@email.com" />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Téléphone" prop="telephone">
                    <el-input v-model="profileForm.telephone" placeholder="+229 XX XX XX XX" />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Poste" prop="poste">
                    <el-input v-model="profileForm.poste" placeholder="Votre poste" />
                  </el-form-item>
                </el-col>
              </el-row>

              <div class="form-actions">
                <el-button type="primary" @click="submitProfile" :loading="profileLoading">
                  Enregistrer les modifications
                </el-button>
              </div>
            </el-form>
          </el-card>

          <!-- Password Form -->
          <el-card shadow="hover" class="form-card">
            <template #header>
              <h3>Changer le mot de passe</h3>
            </template>

            <el-form
              ref="passwordFormRef"
              :model="passwordForm"
              :rules="passwordRules"
              label-position="top"
            >
              <el-row :gutter="20">
                <el-col :xs="24" :sm="8">
                  <el-form-item label="Mot de passe actuel" prop="current_password">
                    <el-input
                      v-model="passwordForm.current_password"
                      type="password"
                      show-password
                      placeholder="Mot de passe actuel"
                    />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="8">
                  <el-form-item label="Nouveau mot de passe" prop="password">
                    <el-input
                      v-model="passwordForm.password"
                      type="password"
                      show-password
                      placeholder="Nouveau mot de passe"
                    />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="8">
                  <el-form-item label="Confirmer le mot de passe" prop="password_confirmation">
                    <el-input
                      v-model="passwordForm.password_confirmation"
                      type="password"
                      show-password
                      placeholder="Confirmer"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <div class="form-actions">
                <el-button type="warning" @click="submitPassword" :loading="passwordLoading">
                  Changer le mot de passe
                </el-button>
              </div>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import {
  UserFilled,
  Message,
  Phone,
  Briefcase,
  Edit,
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchApi } from '@/Composables/useFetch';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  profile: { type: Object, default: () => ({}) },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Mon Profil', path: '/profile' },
];

// --- Profile Form ---
const profileFormRef = ref(null);
const profileLoading = ref(false);
const profileForm = reactive({
  name: props.profile.name || '',
  email: props.profile.email || '',
  telephone: props.profile.telephone || '',
  poste: props.profile.poste || '',
});

const profileRules = {
  name: [{ required: true, message: 'Le nom est requis', trigger: 'blur' }],
  email: [
    { required: true, message: "L'email est requis", trigger: 'blur' },
    { type: 'email', message: 'Email invalide', trigger: 'blur' },
  ],
};

const submitProfile = async () => {
  const valid = await profileFormRef.value?.validate().catch(() => false);
  if (!valid) return;

  profileLoading.value = true;
  try {
    const response = await fetchApi('/profile', {
      method: 'PUT',
      body: profileForm,
    });
    const data = await response.json();
    if (response.ok) {
      ElMessage.success(data.message);
      router.reload({ only: ['profile', 'user'] });
    } else {
      ElMessage.error(data.message || 'Erreur lors de la mise à jour');
    }
  } catch (err) {
    ElMessage.error('Erreur lors de la mise à jour');
  } finally {
    profileLoading.value = false;
  }
};

// --- Password Form ---
const passwordFormRef = ref(null);
const passwordLoading = ref(false);
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const passwordRules = {
  current_password: [{ required: true, message: 'Requis', trigger: 'blur' }],
  password: [
    { required: true, message: 'Requis', trigger: 'blur' },
    { min: 8, message: '8 caractères minimum', trigger: 'blur' },
  ],
  password_confirmation: [
    { required: true, message: 'Requis', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== passwordForm.password) {
          callback(new Error('Les mots de passe ne correspondent pas'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
};

const submitPassword = async () => {
  const valid = await passwordFormRef.value?.validate().catch(() => false);
  if (!valid) return;

  passwordLoading.value = true;
  try {
    const response = await fetchApi('/profile/password', {
      method: 'PUT',
      body: passwordForm,
    });
    const data = await response.json();
    if (response.ok) {
      ElMessage.success(data.message);
      passwordForm.current_password = '';
      passwordForm.password = '';
      passwordForm.password_confirmation = '';
    } else {
      ElMessage.error(data.message || 'Erreur');
    }
  } catch (err) {
    ElMessage.error('Erreur');
  } finally {
    passwordLoading.value = false;
  }
};

// --- Signature ---
const signatureUrl = ref(props.profile.signature_url || null);
const signatureLoading = ref(false);
const uploadRef = ref(null);

const handleSignatureSelect = async (file) => {
  if (!file?.raw) return;

  const maxSize = 2 * 1024 * 1024;
  if (file.raw.size > maxSize) {
    ElMessage.error('La taille maximale est de 2 Mo');
    return;
  }

  signatureLoading.value = true;
  const formData = new FormData();
  formData.append('signature', file.raw);

  try {
    const response = await fetchApi('/profile/signature', {
      method: 'POST',
      body: formData,
    });
    const data = await response.json();
    if (response.ok) {
      ElMessage.success(data.message);
      signatureUrl.value = data.signature_url;
    } else {
      ElMessage.error(data.message || "Erreur lors de l'upload");
    }
  } catch (err) {
    ElMessage.error("Erreur lors de l'upload");
  } finally {
    signatureLoading.value = false;
  }
};

const removeSignature = async () => {
  signatureLoading.value = true;
  try {
    const response = await fetchApi('/profile/signature', {
      method: 'DELETE',
    });
    const data = await response.json();
    if (response.ok) {
      ElMessage.success(data.message);
      signatureUrl.value = null;
    } else {
      ElMessage.error(data.message || 'Erreur lors de la suppression');
    }
  } catch (err) {
    ElMessage.error('Erreur lors de la suppression');
  } finally {
    signatureLoading.value = false;
  }
};

</script>

<style scoped>
.profile-page {
  max-width: 1200px;
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

.profile-card {
  margin-bottom: 20px;
}

.profile-avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 10px 0;
}

.profile-name {
  font-size: 20px;
  font-weight: 600;
  margin: 4px 0 0 0;
  color: #333;
}

.profile-since {
  font-size: 12px;
  color: #999;
  margin: 4px 0 0 0;
}

.profile-details {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #555;
}

.detail-item .el-icon {
  color: #409EFF;
  font-size: 18px;
}

.signature-card {
  margin-bottom: 20px;
}

.signature-card h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.signature-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.signature-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.signature-img {
  max-width: 100%;
  max-height: 120px;
  border: 1px dashed #dcdfe6;
  border-radius: 6px;
  padding: 8px;
  background: #fafafa;
}

.signature-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px 0;
  color: #999;
}

.signature-empty p {
  margin: 0;
  font-size: 13px;
}

.signature-hint {
  font-size: 12px;
  color: #999;
  margin: 0;
}

.form-card {
  margin-bottom: 20px;
}

.form-card h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 8px;
}

</style>
