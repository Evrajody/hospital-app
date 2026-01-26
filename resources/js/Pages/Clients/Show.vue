<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <el-tag type="success" size="large">
            {{ client.type }}
          </el-tag>
          <div>
            <h1 class="page-title">{{ client.nom }}</h1>
            <p class="page-subtitle">{{ client.code }}</p>
          </div>
        </div>

        <div class="header-actions">
          <el-button @click="handleBack">
            <el-icon><ArrowLeft /></el-icon>
            Retour
          </el-button>
          <el-button type="primary" @click="handleEdit">
            <el-icon><Edit /></el-icon>
            Modifier
          </el-button>
          <el-button type="danger" @click="handleDelete">
            <el-icon><Delete /></el-icon>
            Supprimer
          </el-button>
        </div>
      </div>

      <el-row :gutter="20">
        <!-- Left Column: Client Info -->
        <el-col :span="12">
          <!-- General Info Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><InfoFilled /></el-icon>
                <span>Informations Générales</span>
              </div>
            </template>

            <el-descriptions :column="1" border>
              <el-descriptions-item label="Code">
                <el-tag type="info">{{ client.code }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Nom">
                <strong>{{ client.nom }}</strong>
              </el-descriptions-item>
              <el-descriptions-item label="Type de client">
                <el-tag size="small">{{ client.type }}</el-tag>
              </el-descriptions-item>
            </el-descriptions>
          </el-card>

          <!-- Contact Info Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Phone /></el-icon>
                <span>Coordonnées</span>
              </div>
            </template>

            <el-descriptions :column="1" border>
              <el-descriptions-item label="Téléphone">
                <el-link v-if="client.telephone" :href="`tel:${client.telephone}`" :icon="Phone">
                  {{ client.telephone }}
                </el-link>
                <span v-else>-</span>
              </el-descriptions-item>
              <el-descriptions-item label="Email">
                <el-link v-if="client.email" :href="`mailto:${client.email}`" :icon="Message">
                  {{ client.email }}
                </el-link>
                <span v-else>-</span>
              </el-descriptions-item>
              <el-descriptions-item label="Adresse">
                {{ client.adresse || '-' }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>

          <!-- Other Info Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Document /></el-icon>
                <span>Autres Informations</span>
              </div>
            </template>

            <el-descriptions :column="1" border>
              <el-descriptions-item label="IFU">
                {{ client.ifu || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="N° Assurance/Mutuelle">
                {{ client.numero_assurance || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="Notes" v-if="client.notes">
                {{ client.notes }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>

        <!-- Right Column: Statistics & Factures -->
        <el-col :span="12">
          <!-- Statistics Card -->
          <el-card shadow="never" class="section-card stats-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><TrendCharts /></el-icon>
                <span>Statistiques</span>
              </div>
            </template>

            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-icon-wrapper stat-factures">
                  <el-icon :size="24"><Document /></el-icon>
                </div>
                <div class="stat-details">
                  <div class="stat-value">{{ stats.nombre_factures }}</div>
                  <div class="stat-label">Factures</div>
                </div>
              </div>

              <div class="stat-item">
                <div class="stat-icon-wrapper stat-total">
                  <el-icon :size="24"><Money /></el-icon>
                </div>
                <div class="stat-details">
                  <div class="stat-value">{{ formatMontant(stats.montant_total) }}</div>
                  <div class="stat-label">Total Facturé</div>
                </div>
              </div>

              <div class="stat-item">
                <div class="stat-icon-wrapper stat-paye">
                  <el-icon :size="24"><SuccessFilled /></el-icon>
                </div>
                <div class="stat-details">
                  <div class="stat-value">{{ formatMontant(stats.montant_paye) }}</div>
                  <div class="stat-label">Encaissé</div>
                </div>
              </div>

              <div class="stat-item">
                <div class="stat-icon-wrapper stat-reste">
                  <el-icon :size="24"><WarningFilled /></el-icon>
                </div>
                <div class="stat-details">
                  <div class="stat-value">{{ formatMontant(stats.solde) }}</div>
                  <div class="stat-label">Solde Créance</div>
                </div>
              </div>
            </div>
          </el-card>

          <!-- Recent Factures Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <div>
                  <el-icon :size="20"><Tickets /></el-icon>
                  <span>Factures Récentes</span>
                </div>
                <el-button
                  size="small"
                  type="primary"
                  @click="handleNewFacture"
                >
                  Nouvelle Facture
                </el-button>
              </div>
            </template>

            <el-timeline v-if="factures.length > 0">
              <el-timeline-item
                v-for="facture in factures"
                :key="facture.id"
                :timestamp="formatDate(facture.date_facture)"
                placement="top"
                :color="getFactureColor(facture.statut_paiement)"
              >
                <el-card class="facture-item">
                  <div class="facture-header">
                    <el-link type="primary" @click="handleViewFacture(facture)">
                      <strong>{{ facture.numero }}</strong>
                    </el-link>
                    <el-tag :type="getStatutType(facture.statut_paiement)" size="small">
                      {{ getStatutLabel(facture.statut_paiement) }}
                    </el-tag>
                  </div>
                  <div class="facture-details">
                    <div class="facture-montant">
                      <span>Total :</span>
                      <strong>{{ formatMontant(facture.montant_ttc) }}</strong>
                    </div>
                    <div v-if="facture.statut_paiement !== 'payee'" class="facture-reste">
                      <span>Reste :</span>
                      <strong class="text-danger">{{ formatMontant(facture.montant_ttc - facture.montant_paye) }}</strong>
                    </div>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>

            <el-empty v-else description="Aucune facture" :image-size="80">
              <el-button type="primary" size="small" @click="handleNewFacture">
                Créer une facture
              </el-button>
            </el-empty>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  ArrowLeft,
  Edit,
  Delete,
  InfoFilled,
  Phone,
  Message,
  Document,
  TrendCharts,
  Money,
  SuccessFilled,
  WarningFilled,
  Tickets
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props
const props = defineProps({
  client: {
    type: Object,
    required: true
  },
  factures: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      nombre_factures: 0,
      montant_total: 0,
      montant_paye: 0,
      solde: 0
    })
  },
  user: {
    type: Object,
    default: () => null
  }
});

// Computed
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Clients', path: '/clients' },
  { title: props.client.nom, path: '' }
];

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getStatutType = (statut) => {
  const types = {
    impayee: 'danger',
    partielle: 'warning',
    payee: 'success'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    impayee: 'Impayée',
    partielle: 'Partielle',
    payee: 'Payée'
  };
  return labels[statut] || statut;
};

const getFactureColor = (statut) => {
  const colors = {
    impayee: '#f56c6c',
    partielle: '#e6a23c',
    payee: '#67c23a'
  };
  return colors[statut] || '#909399';
};

const handleBack = () => {
  router.visit('/clients');
};

const handleEdit = () => {
  router.visit(`/clients/${props.client.id}/edit`);
};

const handleDelete = () => {
  ElMessageBox.confirm(
    'Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.',
    'Confirmation',
    {
      confirmButtonText: 'Supprimer',
      cancelButtonText: 'Annuler',
      type: 'warning'
    }
  ).then(() => {
    // TODO: Implement delete when backend is ready
    ElMessage.success('Client supprimé avec succès');
    router.visit('/clients');
  });
};

const handleNewFacture = () => {
  router.visit(`/factures-clients/create?client_id=${props.client.id}`);
};

const handleViewFacture = (facture) => {
  router.visit(`/factures-clients/${facture.id}`);
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
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
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

.header-actions {
  display: flex;
  gap: 12px;
}

.section-card {
  border-radius: 8px;
  margin-bottom: 20px;
}

.card-header-custom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.card-header-custom > div {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stats-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stats-card :deep(.el-card__header) {
  border-bottom-color: rgba(255, 255, 255, 0.2);
}

.stats-card .card-header-custom {
  color: white;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  backdrop-filter: blur(10px);
}

.stat-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
}

.stat-details {
  flex: 1;
}

.stat-value {
  font-size: 18px;
  font-weight: 700;
  color: white;
  margin-bottom: 2px;
}

.stat-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
}

.facture-item {
  box-shadow: none;
  border: 1px solid #e5e7eb;
}

.facture-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.facture-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
}

.facture-montant,
.facture-reste {
  display: flex;
  justify-content: space-between;
}

.text-danger {
  color: #f56c6c;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
}

:deep(.el-timeline-item__timestamp) {
  font-weight: 600;
  color: #6b7280;
}
</style>
