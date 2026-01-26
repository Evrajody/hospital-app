<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="rapports-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Rapports Fournisseurs</h1>
          <p class="subtitle">États et rapports de gestion des fournisseurs</p>
        </div>
      </div>

      <!-- Filtres de période -->
      <el-card class="filter-card" shadow="never">
        <div class="filter-section">
          <el-form :inline="true" :model="filters">
            <el-form-item label="Période">
              <el-date-picker
                v-model="filters.periode"
                type="daterange"
                range-separator="-"
                start-placeholder="Date début"
                end-placeholder="Date fin"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
            <el-form-item label="Fournisseur">
              <el-select
                v-model="filters.fournisseur_id"
                placeholder="Tous"
                clearable
                filterable
                style="width: 250px"
              >
                <el-option
                  v-for="fournisseur in fournisseurs"
                  :key="fournisseur.id"
                  :label="`${fournisseur.code} - ${fournisseur.nom}`"
                  :value="fournisseur.id"
                />
              </el-select>
            </el-form-item>
          </el-form>
        </div>
      </el-card>

      <!-- Liste des rapports -->
      <el-row :gutter="20" class="rapports-grid">
        <!-- Rapports de règlement -->
        <el-col :xs="24" :sm="12" :lg="8" v-for="rapport in rapports" :key="rapport.id">
          <el-card shadow="hover" class="rapport-card" :body-style="{ padding: '0px' }">
            <div class="rapport-icon" :style="{ background: rapport.color }">
              <el-icon :size="40">
                <component :is="rapport.icon" />
              </el-icon>
            </div>
            <div class="rapport-content">
              <h3>{{ rapport.titre }}</h3>
              <p class="rapport-description">{{ rapport.description }}</p>
              <div class="rapport-actions">
                <el-button type="primary" size="small" @click="genererRapport(rapport)">
                  <el-icon><View /></el-icon>
                  Générer
                </el-button>
                <el-button size="small" @click="exporterRapport(rapport)">
                  <el-icon><Download /></el-icon>
                  Exporter
                </el-button>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Document,
  DocumentCopy,
  List,
  Wallet,
  CreditCard,
  TrendCharts,
  Tickets,
  View,
  Download,
  Printer
} from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';

// Props
const props = defineProps({
  user: {
    type: Object,
    default: () => ({})
  },
  fournisseurs: {
    type: Array,
    default: () => []
  }
});

// Breadcrumbs
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rapports Fournisseurs', path: '/rapports/fournisseurs' }
];

// Filters
const filters = ref({
  periode: null,
  fournisseur_id: null
});

// Liste des rapports disponibles
const rapports = ref([
  {
    id: 'etat-reglement-facture',
    titre: 'État de règlement d\'une facture',
    description: 'Détails des paiements effectués sur une facture',
    icon: 'Document',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    route: '/rapports/fournisseurs/etat-reglement-facture'
  },
  {
    id: 'mouvement-periodique',
    titre: 'Mouvement périodique fournisseur',
    description: 'Historique des factures par fournisseur sur une période',
    icon: 'TrendCharts',
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    route: '/rapports/fournisseurs/mouvement-periodique'
  },
  {
    id: 'situation-fournisseurs',
    titre: 'Situation des fournisseurs',
    description: 'Point des dettes fournisseurs en cours',
    icon: 'Wallet',
    color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    route: '/rapports/fournisseurs/situation-fournisseurs'
  },
  {
    id: 'factures-reglees',
    titre: 'Factures réglées',
    description: 'État périodique des factures fournisseurs réglées',
    icon: 'DocumentCopy',
    color: 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
    route: '/rapports/fournisseurs/factures-reglees'
  },
  {
    id: 'declaration-aib',
    titre: 'Déclaration AIB',
    description: 'Déclaration périodique des AIB',
    icon: 'List',
    color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
    route: '/rapports/fournisseurs/declaration-aib'
  },
  {
    id: 'pieces-comptables',
    titre: 'Point des pièces comptables',
    description: 'Récapitulatif périodique des pièces comptables',
    icon: 'Tickets',
    color: 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
    route: '/rapports/fournisseurs/pieces-comptables'
  },
  {
    id: 'situation-banques',
    titre: 'Situation des banques',
    description: 'État périodique des comptes bancaires',
    icon: 'CreditCard',
    color: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
    route: '/rapports/fournisseurs/situation-banques'
  },
  {
    id: 'recap-charges',
    titre: 'Récapitulatif des charges',
    description: 'État récapitulatif périodique des dépenses',
    icon: 'TrendCharts',
    color: 'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)',
    route: '/rapports/fournisseurs/recap-charges'
  },
  {
    id: 'recap-investissements',
    titre: 'Récapitulatif des investissements',
    description: 'État récapitulatif périodique des immobilisations',
    icon: 'TrendCharts',
    color: 'linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%)',
    route: '/rapports/fournisseurs/recap-investissements'
  },
  {
    id: 'plan-comptable',
    titre: 'État du plan comptable',
    description: 'Liste complète du plan comptable',
    icon: 'List',
    color: 'linear-gradient(135deg, #f77062 0%, #fe5196 100%)',
    route: '/rapports/fournisseurs/plan-comptable'
  },
  {
    id: 'bordereau-transmission',
    titre: 'Bordereau de transmission',
    description: 'Bordereau de transmission des règlements',
    icon: 'DocumentCopy',
    color: 'linear-gradient(135deg, #c471f5 0%, #fa71cd 100%)',
    route: '/rapports/fournisseurs/bordereau-transmission'
  },
  {
    id: 'factures-soldes',
    titre: 'Factures et soldes',
    description: 'Détails des règlements par facture',
    icon: 'Document',
    color: 'linear-gradient(135deg, #fccb90 0%, #d57eeb 100%)',
    route: '/rapports/fournisseurs/factures-soldes'
  },
  {
    id: 'declaration-tva',
    titre: 'Déclaration TVA',
    description: 'État de déclaration de la TVA par période',
    icon: 'List',
    color: 'linear-gradient(135deg, #3eecac 0%, #ee74e1 100%)',
    route: '/rapports/fournisseurs/declaration-tva'
  }
]);

// Methods
const genererRapport = (rapport) => {
  router.visit(rapport.route, {
    data: {
      periode: filters.value.periode,
      fournisseur_id: filters.value.fournisseur_id
    }
  });
};

const exporterRapport = (rapport) => {
  ElMessage.info('Fonction d\'export en développement');
};
</script>

<style scoped>
.rapports-container {
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.filter-card {
  margin-bottom: 24px;
  border: 1px solid #e8e8e8;
}

.filter-section {
  display: flex;
  align-items: center;
  gap: 16px;
}

.rapports-grid {
  margin-top: 24px;
}

.rapport-card {
  margin-bottom: 20px;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s;
  cursor: pointer;
}

.rapport-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.rapport-icon {
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.rapport-content {
  padding: 20px;
}

.rapport-content h3 {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
  min-height: 40px;
}

.rapport-description {
  font-size: 13px;
  color: #666;
  margin: 0 0 16px 0;
  min-height: 40px;
  line-height: 1.5;
}

.rapport-actions {
  display: flex;
  gap: 8px;
}

.rapport-actions .el-button {
  flex: 1;
}
</style>
