<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="taux-container">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1>Taux Fiscaux</h1>
          <p class="subtitle">Configuration des taux TVA et AIB</p>
        </div>
      </div>

      <!-- Stats -->
      <el-row :gutter="20" class="stats-row">
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%)">
                <el-icon :size="24"><Money /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.total_tva }}</div>
                <div class="stat-label">Taux TVA</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                <el-icon :size="24"><Tickets /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.total_aib }}</div>
                <div class="stat-label">Taux AIB</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="8">
          <el-card shadow="hover">
            <div class="stat-card">
              <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%)">
                <el-icon :size="24"><CircleCheck /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ stats.actifs }}</div>
                <div class="stat-label">Taux Actifs</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Section TVA -->
      <el-card class="table-card" shadow="never" style="margin-bottom: 20px">
        <template #header>
          <div class="card-header">
            <span class="card-title">Taux TVA</span>
          </div>
        </template>

        <el-table :data="tauxTva" stripe style="width: 100%">
          <el-table-column label="Actions" width="120" fixed="left">
            <template #default="{ row }">
              <el-button size="small" :icon="Edit" type="warning" @click="handleEdit(row)">Modifier</el-button>
            </template>
          </el-table-column>
          <el-table-column prop="libelle" label="Libellé" sortable>
            <template #default="{ row }">
              <strong>{{ row.libelle }}</strong>
            </template>
          </el-table-column>
          <el-table-column prop="taux" label="Taux" width="120" sortable>
            <template #default="{ row }">
              <el-tag size="large" type="primary">{{ row.taux }}%</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="actif" label="Statut" width="120" sortable>
            <template #default="{ row }">
              <el-switch
                :model-value="row.actif"
                @change="handleToggle(row)"
                active-text="Actif"
                inactive-text="Inactif"
              />
            </template>
          </el-table-column>
          <el-table-column prop="par_defaut" label="Par défaut" width="120" sortable>
            <template #default="{ row }">
              <el-tag v-if="row.par_defaut" type="success" size="small">Par défaut</el-tag>
              <span v-else style="color: #999">-</span>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- Section AIB -->
      <el-card class="table-card" shadow="never">
        <template #header>
          <div class="card-header">
            <span class="card-title">Taux AIB (Acompte sur Impôt assis sur les Bénéfices)</span>
            <el-button type="primary" size="small" :icon="Plus" @click="handleCreate('aib')">
              Ajouter AIB
            </el-button>
          </div>
        </template>

        <el-table :data="tauxAib" stripe style="width: 100%">
          <el-table-column label="Actions" width="200" fixed="left">
            <template #default="{ row }">
              <el-button size="small" :icon="Edit" type="warning" @click="handleEdit(row)">Modifier</el-button>
              <el-popconfirm
                title="Supprimer ce taux ?"
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
          <el-table-column prop="libelle" label="Libellé" sortable>
            <template #default="{ row }">
              <strong>{{ row.libelle }}</strong>
            </template>
          </el-table-column>
          <el-table-column prop="taux" label="Taux" width="120" sortable>
            <template #default="{ row }">
              <el-tag size="large" type="warning">{{ row.taux }}%</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="actif" label="Statut" width="120" sortable>
            <template #default="{ row }">
              <el-switch
                :model-value="row.actif"
                @change="handleToggle(row)"
                active-text="Actif"
                inactive-text="Inactif"
              />
            </template>
          </el-table-column>
          <el-table-column prop="par_defaut" label="Par défaut" width="120" sortable>
            <template #default="{ row }">
              <el-tag v-if="row.par_defaut" type="success" size="small">Par défaut</el-tag>
              <span v-else style="color: #999">-</span>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>

    <!-- Modal -->
    <TauxFiscalModal
      v-model="showModal"
      :taux-fiscal="selectedTaux"
      :type-initial="typeInitial"
      :loading="modalLoading"
      :server-errors="serverErrors"
      @success="handleSuccess"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import AppLayout from '@/Layouts/AppLayout.vue';
import TauxFiscalModal from '@/Components/Modals/TauxFiscalModal.vue';
import { Plus, Edit, Delete, Money, Tickets, CircleCheck } from '@element-plus/icons-vue';
import { fetchApi } from '@/Composables/useFetch';

const props = defineProps({
  user: { type: Object, default: () => ({}) },
  tauxTva: { type: Array, default: () => [] },
  tauxAib: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total_tva: 0, total_aib: 0, actifs: 0 }) },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Paramètres' },
  { title: 'Taux Fiscaux', path: '/taux-fiscaux' },
];

const showModal = ref(false);
const selectedTaux = ref(null);
const typeInitial = ref('tva');
const modalLoading = ref(false);
const serverErrors = ref(null);

const handleCreate = (type) => {
  selectedTaux.value = null;
  typeInitial.value = type;
  serverErrors.value = null;
  showModal.value = true;
};

const handleEdit = (taux) => {
  selectedTaux.value = taux;
  typeInitial.value = taux.type;
  serverErrors.value = null;
  showModal.value = true;
};

const handleDelete = async (taux) => {
  try {
    const response = await fetchApi(`/api/taux-fiscaux/${taux.id}`, {
      method: 'DELETE',
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success('Taux supprimé');
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const handleToggle = async (taux) => {
  try {
    const response = await fetchApi(`/api/taux-fiscaux/${taux.id}/toggle`, {
      method: 'PATCH',
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success(result.message);
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion');
  }
};

const handleSuccess = async (data) => {
  modalLoading.value = true;
  serverErrors.value = null;

  const isEdit = !!selectedTaux.value;
  const url = isEdit ? `/api/taux-fiscaux/${selectedTaux.value.id}` : '/api/taux-fiscaux';

  try {
    const response = await fetchApi(url, {
      method: isEdit ? 'PUT' : 'POST',
      body: data,
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || (isEdit ? 'Taux modifié' : 'Taux créé'));
      showModal.value = false;
      selectedTaux.value = null;
      router.reload();
    } else {
      if (result.errors) {
        serverErrors.value = result.errors;
      }
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (error) {
    ElMessage.error('Erreur de connexion au serveur');
  } finally {
    modalLoading.value = false;
  }
};
</script>

<style scoped>
.taux-container { max-width: 1400px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.page-header h1 { font-size: 28px; font-weight: 600; color: #333; margin: 0 0 8px 0; }
.subtitle { color: #666; font-size: 14px; margin: 0; }
.stats-row { margin-bottom: 20px; }
.stat-card { display: flex; align-items: center; gap: 16px; }
.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; }
.stat-content { flex: 1; }
.stat-value { font-size: 24px; font-weight: bold; color: #333; line-height: 1.2; }
.stat-label { font-size: 13px; color: #666; margin-top: 4px; }
.table-card { border: 1px solid #e8e8e8; }
.table-card :deep(.el-card__body) { padding: 0; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 16px; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
</style>
