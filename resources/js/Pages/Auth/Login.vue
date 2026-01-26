<template>
  <div class="login-container">
    <!-- Left Side - Form -->
    <div class="login-form-section">
      <div class="form-wrapper">
        <!-- Logo -->
        <div class="login-header">
          <el-icon :size="48" color="#409EFF"><OfficeBuilding /></el-icon>
          <h1>Hôpital de Ménontin</h1>
          <p class="welcome-text">Bienvenue ! Connectez-vous à votre compte</p>
        </div>

        <!-- Form -->
        <el-form
          ref="loginFormRef"
          :model="form"
          :rules="rules"
          label-position="top"
          size="large"
          class="login-form"
        >
          <el-form-item label="Email" prop="email">
            <el-input
              v-model="form.email"
              type="email"
              placeholder="votre.email@example.com"
              :prefix-icon="User"
              clearable
            />
          </el-form-item>

          <el-form-item label="Mot de passe" prop="password">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="Votre mot de passe"
              :prefix-icon="Lock"
              show-password
            />
          </el-form-item>

          <el-form-item>
            <div class="form-options">
              <el-checkbox v-model="form.remember">Se souvenir de moi</el-checkbox>
            </div>
          </el-form-item>

          <el-form-item>
            <el-button
              type="primary"
              style="width: 100%"
              size="large"
              :loading="form.processing"
              @click="handleLogin"
            >
              Se connecter
            </el-button>
          </el-form-item>
        </el-form>

        <!-- Footer -->
        <div class="login-footer">
          <p>&copy; 2025 Hôpital de Ménontin</p>
        </div>
      </div>
    </div>

    <!-- Right Side - Image/Illustration -->
    <div class="login-image-section">
      <div class="image-overlay">
        <div class="overlay-content">
          <h2>Système de Gestion Administrative</h2>
          <p>
            Une plateforme complète pour gérer efficacement les opérations
            financières et administratives de votre établissement
          </p>
          <div class="features-list">
            <div class="feature-item">
              <el-icon :size="20" color="#fff"><Check /></el-icon>
              <span>Gestion des factures fournisseurs et clients</span>
            </div>
            <div class="feature-item">
              <el-icon :size="20" color="#fff"><Check /></el-icon>
              <span>Plan comptable OHADA intégré</span>
            </div>
            <div class="feature-item">
              <el-icon :size="20" color="#fff"><Check /></el-icon>
              <span>Rapports et états automatisés</span>
            </div>
            <div class="feature-item">
              <el-icon :size="20" color="#fff"><Check /></el-icon>
              <span>Gestion bancaire en temps réel</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import { User, Lock, OfficeBuilding, Check } from '@element-plus/icons-vue';

// Form reference
const loginFormRef = ref(null);

// Form data
const form = reactive({
  email: '',
  password: '',
  remember: false,
  processing: false
});

// Validation rules
const rules = {
  email: [
    { required: true, message: 'Veuillez saisir votre email', trigger: 'blur' },
    { type: 'email', message: 'Email invalide', trigger: 'blur' }
  ],
  password: [
    { required: true, message: 'Veuillez saisir votre mot de passe', trigger: 'blur' },
    { min: 6, message: 'Le mot de passe doit contenir au moins 6 caractères', trigger: 'blur' }
  ]
};

// Methods
const handleLogin = async () => {
  if (!loginFormRef.value) return;

  await loginFormRef.value.validate((valid) => {
    if (valid) {
      form.processing = true;

      // TODO: Replace with actual API call when backend is ready
      setTimeout(() => {
        ElMessage.success('Connexion réussie !');
        router.visit('/dashboard');
      }, 1000);
    }
  });
};
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
}

/* Left Side - Form */
.login-form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  background: #ffffff;
}

.form-wrapper {
  width: 100%;
  max-width: 440px;
}

.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.login-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
  margin: 16px 0 8px 0;
}

.welcome-text {
  font-size: 15px;
  color: #6b7280;
  margin: 0;
}

.login-form {
  margin-top: 32px;
}

.form-options {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.login-footer {
  margin-top: 40px;
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
}

/* Right Side - Image */
.login-image-section {
  flex: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
  overflow: hidden;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px;
}

.overlay-content {
  color: white;
  max-width: 500px;
}

.overlay-content h2 {
  font-size: 36px;
  font-weight: 700;
  margin: 0 0 20px 0;
  line-height: 1.2;
}

.overlay-content p {
  font-size: 18px;
  line-height: 1.6;
  margin: 0 0 40px 0;
  opacity: 0.95;
}

.features-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;
}

/* Responsive */
@media (max-width: 968px) {
  .login-container {
    flex-direction: column;
  }

  .login-image-section {
    display: none;
  }

  .login-form-section {
    min-height: 100vh;
  }
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
}

:deep(.el-input__inner) {
  border-radius: 8px;
}

:deep(.el-button--primary) {
  border-radius: 8px;
  font-weight: 600;
}
</style>
