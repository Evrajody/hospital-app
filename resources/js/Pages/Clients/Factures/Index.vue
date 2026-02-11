<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="factures-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Factures Clients</h1>
          <p class="subtitle">Gestion des factures et cr&eacute;ances</p>
        </div>
        <el-button type="primary" :icon="Plus" @click="handleCreate">
          Nouvelle Facture
        </el-button>
      </div>

      <!-- Statistiques -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <el-icon :size="24"><Document /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ filteredFactures.length }}</div>
                <div class="stat-label">Total Factures</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                <el-icon :size="24"><Money /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.total_facture) }}</div>
                <div class="stat-label">Total Factur&eacute;</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card success">
              <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)">
                <el-icon :size="24"><SuccessFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.total_paye) }}</div>
                <div class="stat-label">Total Pay&eacute;</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="6">
          <el-card shadow="hover">
            <div class="stat-card danger">
              <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%)">
                <el-icon :size="24"><Warning /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ formatMontant(stats.total_reste) }}</div>
                <div class="stat-label">Cr&eacute;ances</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Recherche et filtres -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true">
          <el-form-item>
            <el-input
              v-model="searchQuery"
              placeholder="Rechercher (r&eacute;f&eacute;rence, client...)"
              :prefix-icon="Search"
              style="width: 350px"
              clearable
            />
          </el-form-item>
          <el-form-item>
            <el-select
              v-model="filterStatut"
              placeholder="Tous les statuts"
              clearable
              style="width: 200px"
            >
              <el-option label="Non pay&eacute;e" value="non_payee" />
              <el-option label="Partiellement pay&eacute;e" value="partiellement_payee" />
              <el-option label="Pay&eacute;e" value="payee" />
            </el-select>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Table -->
      <el-card class="table-card" shadow="never">
        <el-table
          :data="filteredFactures"
          stripe
          style="width: 100%"
          :default-sort="{ prop: 'date_facture', order: 'descending' }"
          class="factures-table"
        >
          <el-table-column prop="reference" label="R&eacute;f&eacute;rence" width="160" sortable>
            <template #default="{ row }">
              <a class="ref-link" @click="handleView(row)">{{ row.reference }}</a>
            </template>
          </el-table-column>
          <el-table-column prop="date_facture" label="Date" width="120" sortable>
            <template #default="{ row }">
              {{ formatDate(row.date_facture) }}
            </template>
          </el-table-column>
          <el-table-column prop="client.nom" label="Client" sortable>
            <template #default="{ row }">
              <strong>{{ row.client?.nom || '-' }}</strong>
            </template>
          </el-table-column>
          <el-table-column label="Montant" width="160" align="right" sortable sort-by="montant">
            <template #default="{ row }">
              <div>{{ formatMontant(row.montant) }}</div>
              <div v-if="row.ristourne > 0" style="font-size: 11px; color: #e6a23c;">
                Rist. -{{ formatMontant(row.ristourne) }}
              </div>
            </template>
          </el-table-column>
          <el-table-column label="Pay&eacute;" width="160" align="right">
            <template #default="{ row }">
              <span class="montant-paye">{{ formatMontant(row.montant_paye) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Reste" width="160" align="right">
            <template #default="{ row }">
              <span :class="['montant-reste', row.reste_a_payer > 0 ? 'has-reste' : '']">
                {{ formatMontant(row.reste_a_payer) }}
              </span>
            </template>
          </el-table-column>
          <el-table-column label="Statut" width="160" align="center">
            <template #default="{ row }">
              <el-tag :type="getStatutType(row.statut)" size="small">
                {{ getStatutLabel(row.statut) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="280" fixed="right">
            <template #default="{ row }">
              <el-button
                v-if="row.statut !== 'payee'"
                size="small"
                type="success"
                @click="handleRegler(row)"
              >
                R&eacute;gler
              </el-button>
              <el-button size="small" :icon="Edit" @click="handleEdit(row)">Modifier</el-button>
              <el-popconfirm
                title="Supprimer cette facture ?"
                confirm-button-text="Oui"
                cancel-button-text="Non"
                @confirm="handleDelete(row)"
              >
                <template #reference>
                  <el-button size="small" type="danger" :icon="Delete" />
                </template>
              </el-popconfirm>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>

    <!-- Modal Facture -->
    <FactureClientModal
      ref="factureModalRef"
      v-model="showFactureModal"
      :facture="selectedFacture"
      :clients="clients"
      :prochaine-reference="prochaineReference"
      :loading="modalLoading"
      @success="handleFactureSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import FactureClientModal from '@/Components/Modals/FactureClientModal.vue';
import {
  Plus, Search, Edit, Delete,
  Document, Money, SuccessFilled, Warning
} from '@element-plus/icons-vue';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  factures: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  prochaineReference: { type: String, default: '' },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Clients', path: '/factures-clients' }
];

const searchQuery = ref('');
const filterStatut = ref('');
const showFactureModal = ref(false);
const selectedFacture = ref(null);
const modalLoading = ref(false);
const factureModalRef = ref(null);

const filteredFactures = computed(() => {
  let result = props.factures;

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(f =>
      f.reference?.toLowerCase().includes(q) ||
      f.client?.nom?.toLowerCase().includes(q)
    );
  }

  if (filterStatut.value) {
    result = result.filter(f => f.statut === filterStatut.value);
  }

  return result;
});

const stats = computed(() => ({
  total_facture: filteredFactures.value.reduce((sum, f) => sum + (f.montant || 0), 0),
  total_paye: filteredFactures.value.reduce((sum, f) => sum + (f.montant_paye || 0), 0),
  total_reste: filteredFactures.value.reduce((sum, f) => sum + (f.reste_a_payer || 0), 0),
}));

const getStatutLabel = (statut) => {
  const labels = {
    non_payee: 'Non pay\u00e9e',
    partiellement_payee: 'Partielle',
    payee: 'Pay\u00e9e',
  };
  return labels[statut] || statut;
};

const getStatutType = (statut) => {
  const types = {
    non_payee: 'danger',
    partiellement_payee: 'warning',
    payee: 'success',
  };
  return types[statut] || '';
};

const handleView = (facture) => {
  router.visit(`/factures-clients/${facture.id}`);
};

const handleRegler = (facture) => {
  router.visit(`/factures-clients/${facture.id}/regler`);
};

const handleCreate = () => {
  selectedFacture.value = null;
  showFactureModal.value = true;
};

const handleEdit = (facture) => {
  selectedFacture.value = facture;
  showFactureModal.value = true;
};

const handleDelete = async (facture) => {
  try {
    const response = await fetch(`/api/factures-clients/${facture.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      }
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Facture supprim\u00e9e');
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const handleFactureSuccess = async (data) => {
  modalLoading.value = true;

  const isEdit = !!selectedFacture.value;
  const url = isEdit ? `/api/factures-clients/${selectedFacture.value.id}` : '/api/factures-clients';

  try {
    const response = await fetch(url, {
      method: isEdit ? 'PUT' : 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Facture modifi\u00e9e' : 'Facture cr\u00e9\u00e9e'));
      showFactureModal.value = false;
      selectedFacture.value = null;
      router.reload();
    } else if (result.saut_numero) {
      // Saut de numéro détecté - afficher le warning
      factureModalRef.value?.showSautWarning(result.numeros_sautes);
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
  }
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<style scoped>
.factures-container { max-width: 1600px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 28px; font-weight: 600; color: #333; margin: 0 0 8px 0; }
.subtitle { color: #666; font-size: 14px; margin: 0; }
.filter-card { margin-bottom: 20px; border: 1px solid #e8e8e8; }
.stats-row { margin-bottom: 20px; }
.stat-card { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; }
.stat-content { flex: 1; }
.stat-value { font-size: 20px; font-weight: bold; color: #333; line-height: 1.2; }
.stat-label { font-size: 13px; color: #666; margin-top: 4px; }
.table-card { border: 1px solid #e8e8e8; }
.factures-table :deep(.el-table__row) { cursor: default; }
.montant-paye { color: #059669; font-weight: 600; }
.montant-reste { font-weight: 600; color: #666; }
.montant-reste.has-reste { color: #dc2626; }
.ref-link { color: #409eff; cursor: pointer; font-weight: 600; text-decoration: none; }
.ref-link:hover { text-decoration: underline; }
</style>
