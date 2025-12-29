<template>
  <div class="welcome-page">
    <!-- Navigation Bar -->
    <nav class="navbar">
      <div class="nav-content">
        <div class="logo">
          <el-icon :size="32" color="#409EFF"><OfficeBuilding /></el-icon>
          <span>Hôpital de Ménontin</span>
        </div>
        <el-button type="primary" @click="navigateToLogin">
          <el-icon><User /></el-icon>
          Connexion
        </el-button>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <h1>Système de Gestion Administrative</h1>
        <p class="hero-subtitle">
          Une solution complète et moderne pour la gestion des opérations administratives
          et financières de l'hôpital de Ménontin
        </p>
        <div class="hero-actions">
          <el-button type="primary" size="large" @click="navigateToLogin">
            Accéder à l'application
            <el-icon class="ml-2"><ArrowRight /></el-icon>
          </el-button>
        </div>
      </div>
    </section>

    <!-- Features Grid -->
    <section class="features">
      <div class="features-container">
        <h2 class="section-title">Fonctionnalités principales</h2>

        <div class="features-grid">
          <!-- Feature 1 -->
          <div class="feature-card">
            <div class="feature-icon">
              <el-icon :size="40" color="#409EFF"><Document /></el-icon>
            </div>
            <h3>Factures Fournisseurs</h3>
            <p>Gestion complète des factures, règlements multiples et imputations comptables selon les normes OHADA</p>
          </div>

          <!-- Feature 2 -->
          <div class="feature-card">
            <div class="feature-icon">
              <el-icon :size="40" color="#67C23A"><Money /></el-icon>
            </div>
            <h3>Factures Clients</h3>
            <p>Suivi des factures clients, gestion des créances et des règlements avec rapports détaillés</p>
          </div>

          <!-- Feature 3 -->
          <div class="feature-card">
            <div class="feature-icon">
              <el-icon :size="40" color="#E6A23C"><Notebook /></el-icon>
            </div>
            <h3>Plan Comptable OHADA</h3>
            <p>Plan comptable complet conforme aux standards OHADA avec recherche avancée</p>
          </div>

          <!-- Feature 4 -->
          <div class="feature-card">
            <div class="feature-icon">
              <el-icon :size="40" color="#F56C6C"><CreditCard /></el-icon>
            </div>
            <h3>Gestion Bancaire</h3>
            <p>Suivi en temps réel des comptes bancaires, mouvements et approvisionnements</p>
          </div>

          <!-- Feature 5 -->
          <div class="feature-card">
            <div class="feature-icon">
              <el-icon :size="40" color="#909399"><Printer /></el-icon>
            </div>
            <h3>Rapports & États</h3>
            <p>Génération automatique de tous les rapports comptables, TVA, AIB et déclarations fiscales</p>
          </div>

          <!-- Feature 6 -->
          <div class="feature-card">
            <div class="feature-icon">
              <el-icon :size="40" color="#409EFF"><Lock /></el-icon>
            </div>
            <h3>Sécurité & Permissions</h3>
            <p>Gestion fine des rôles et permissions pour sécuriser l'accès aux données sensibles</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-content">
        <p>&copy; 2025 Hôpital de Ménontin. Tous droits réservés.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import {
  OfficeBuilding,
  Document,
  Money,
  Notebook,
  CreditCard,
  Printer,
  Lock,
  User,
  ArrowRight
} from '@element-plus/icons-vue';

const navigateToLogin = () => {
  router.visit('/login');
};
</script>

<style scoped>
.welcome-page {
  min-height: 100vh;
  background: #ffffff;
}

/* Navigation */
.navbar {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 100;
}

.nav-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

/* Hero Section */
.hero {
  padding: 80px 2rem;
  background: linear-gradient(to bottom, #f9fafb, #ffffff);
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.hero h1 {
  font-size: 48px;
  font-weight: 800;
  color: #111827;
  margin: 0 0 24px 0;
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 20px;
  color: #6b7280;
  margin: 0 0 40px 0;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.ml-2 {
  margin-left: 8px;
}

/* Features Section */
.features {
  padding: 80px 2rem;
  background: white;
}

.features-container {
  max-width: 1280px;
  margin: 0 auto;
}

.section-title {
  font-size: 36px;
  font-weight: 700;
  color: #111827;
  text-align: center;
  margin: 0 0 60px 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 32px;
}

.feature-card {
  padding: 32px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: all 0.3s ease;
  background: white;
}

.feature-card:hover {
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  transform: translateY(-4px);
  border-color: #409EFF;
}

.feature-icon {
  margin-bottom: 20px;
}

.feature-card h3 {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 12px 0;
}

.feature-card p {
  font-size: 15px;
  color: #6b7280;
  line-height: 1.6;
  margin: 0;
}

/* Footer */
.footer {
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
  padding: 32px 2rem;
}

.footer-content {
  max-width: 1280px;
  margin: 0 auto;
  text-align: center;
  color: #6b7280;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
  .hero h1 {
    font-size: 36px;
  }

  .hero-subtitle {
    font-size: 18px;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }

  .section-title {
    font-size: 28px;
  }
}
</style>
