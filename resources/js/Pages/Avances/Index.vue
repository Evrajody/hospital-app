<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Avances Clients</h1>
          <p class="page-subtitle">Chèques d'avance reçus de sociétés d'assurance ou tierces parties pour le compte des clients</p>
        </div>
        <div style="display: flex; gap: 8px;">
          <el-button size="large" @click="exportEtatPdf">
            <el-icon><Printer /></el-icon>
            État des avances (PDF)
          </el-button>
          <el-button type="primary" size="large" @click="openCreate">
            <el-icon><Plus /></el-icon>
            Nouvelle avance
          </el-button>
        </div>
      </div>

      <!-- Stats Cards -->
      <el-row :gutter="16" class="stats-row">
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-total">
            <div class="stat-content">
              <div class="stat-icon"><el-icon :size="32"><Money /></el-icon></div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.total_avances) }}</div>
                <div class="stat-label">Total des avances reçues</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-today">
            <div class="stat-content">
              <div class="stat-icon"><el-icon :size="32"><CircleCheck /></el-icon></div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.total_utilise) }}</div>
                <div class="stat-label">Total déjà consommé</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-count">
            <div class="stat-content">
              <div class="stat-icon"><el-icon :size="32"><Wallet /></el-icon></div>
              <div class="stat-info">
                <div class="stat-value">{{ formatMontant(stats.total_disponible) }}</div>
                <div class="stat-label">Solde disponible</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card shadow="hover" class="stat-card stat-average">
            <div class="stat-content">
              <div class="stat-icon"><el-icon :size="32"><TrendCharts /></el-icon></div>
              <div class="stat-info">
                <div class="stat-value">{{ stats.nombre_avances }}</div>
                <div class="stat-label">Nombre d'avances</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Filters -->
      <el-card class="filter-card" shadow="never">
        <el-form :inline="true" :model="filters" class="filter-form">
          <el-form-item label="Recherche">
            <el-input v-model="filters.search" placeholder="Société, n° chèque, proforma…" clearable style="width: 280px" :prefix-icon="Search" @input="debouncedSearch" />
          </el-form-item>
          <el-form-item label="Client">
            <el-select v-model="filters.client_id" placeholder="Tous" filterable clearable style="width: 220px" @change="handleSearch">
              <el-option v-for="c in clients" :key="c.id" :label="c.nom" :value="c.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Statut">
            <el-select v-model="filters.statut" placeholder="Tous" clearable style="width: 200px" @change="handleSearch">
              <el-option label="Disponible" value="disponible" />
              <el-option label="Partiellement utilisée" value="partiellement_utilisee" />
              <el-option label="Épuisée" value="epuisee" />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button @click="resetFilters">
              <el-icon><RefreshLeft /></el-icon> Réinitialiser
            </el-button>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- Tableau -->
      <el-card shadow="never" class="table-card">
        <template #header>
          <div class="card-header">
            <span class="card-title">Liste des avances ({{ pagination.total }})</span>
          </div>
        </template>

        <el-table :data="avances" border style="width: 100%" stripe>
          <el-table-column label="Société émettrice" min-width="220">
            <template #default="{ row }">
              <strong>{{ emetteurLabel(row) }}</strong>
            </template>
          </el-table-column>
          <el-table-column label="Bénéficiaire(s)" min-width="200">
            <template #default="{ row }">
              <template v-if="row.beneficiaires?.length">
                <el-tag v-for="b in row.beneficiaires" :key="b.id" size="small" class="benef-tag">{{ b.nom }}</el-tag>
              </template>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="N° Ligne" min-width="90" prop="numero_ligne" />
          <el-table-column label="Date Chèque" min-width="110">
            <template #default="{ row }">{{ formatDate(row.date_cheque) }}</template>
          </el-table-column>
          <el-table-column label="N° Chèque" min-width="120" prop="numero_cheque" />
          <el-table-column label="Proforma" min-width="120">
            <template #default="{ row }">{{ row.numero_proforma || '-' }}</template>
          </el-table-column>
          <el-table-column label="Montant" min-width="120" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
          <el-table-column label="Utilisé" min-width="120" align="right">
            <template #default="{ row }">
              <span style="color: #d97706">{{ formatMontant(row.montant_utilise) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Solde" min-width="120" align="right">
            <template #default="{ row }">
              <strong :style="{ color: row.montant_restant > 0 ? '#059669' : '#9ca3af' }">{{ formatMontant(row.montant_restant) }}</strong>
            </template>
          </el-table-column>
          <el-table-column label="Statut" min-width="130">
            <template #default="{ row }">
              <el-tag :type="statutColor(row.statut)" size="small">{{ row.statut_libelle }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="100" align="center" fixed="right">
            <template #default="{ row }">
              <el-dropdown trigger="click" @command="(cmd) => handleAvanceAction(cmd, row)">
                <el-button size="small" type="primary">
                  Actions <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="view" :icon="View">Voir</el-dropdown-item>
                    <el-dropdown-item command="edit" :icon="Edit">Modifier</el-dropdown-item>
                    <el-dropdown-item divided command="delete" :icon="Delete" :disabled="row.montant_utilise > 0">
                      <span style="color: #f56c6c">Supprimer</span>
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </template>
          </el-table-column>
        </el-table>

        <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
          <el-pagination
            :current-page="pagination.current_page"
            :page-size="pagination.per_page"
            :page-sizes="[10, 20, 50, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next, jumper"
            background
            @size-change="handleSizeChange"
            @current-change="handlePageChange"
          />
        </div>
      </el-card>

      <!-- Modal Création / Édition -->
      <el-dialog
        v-model="formVisible"
        :title="editingId ? `Modifier l'avance` : 'Nouvelle avance'"
        width="720px"
        :close-on-click-modal="false"
      >
        <el-form ref="formRef" :model="form" :rules="rules" label-position="top" size="large">
          <!-- Société émettrice EN PREMIER : client de type Société (porteur du n° de compte) -->
          <el-form-item label="Société émettrice" prop="societe_emettrice_client_id">
            <el-select
              v-model="form.societe_emettrice_client_id"
              filterable
              placeholder="Sélectionner une société émettrice"
              style="width: 100%"
            >
              <el-option
                v-for="s in societes"
                :key="s.id"
                :label="societeOptionLabel(s)"
                :value="s.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Bénéficiaire(s)" prop="beneficiaires">
            <el-select
              v-model="form.beneficiaires"
              multiple
              filterable
              collapse-tags
              collapse-tags-tooltip
              placeholder="Sélectionner un ou plusieurs clients bénéficiaires"
              style="width: 100%"
            >
              <el-option v-for="c in clients" :key="c.id" :label="c.nom" :value="c.id" />
            </el-select>
          </el-form-item>

          <el-row :gutter="20">
            <el-col :span="8">
              <el-form-item label="N° Chèque" prop="numero_cheque">
                <el-input v-model="form.numero_cheque" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Date du chèque" prop="date_cheque">
                <el-date-picker v-model="form.date_cheque" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" style="width: 100%" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Montant (F CFA)" prop="montant">
                <el-input-number
                  v-model="form.montant"
                  :min="1"
                  :precision="0"
                  :formatter="formatInputMontant"
                  :parser="parseInputMontant"
                  controls-position="right"
                  style="width: 100%"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="N° Proforma">
                <el-input v-model="form.numero_proforma" placeholder="(optionnel)" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Institution">
                <el-input v-model="form.institution" placeholder="(optionnel)" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-divider>Dépôt bancaire <span style="font-size: 11px; opacity: 0.6; font-weight: normal;">(optionnel)</span></el-divider>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Banque de dépôt">
                <el-select v-model="form.banque_depot_id" filterable clearable placeholder="Sélectionner" style="width: 100%" @change="form.approvisionnement_id = null">
                  <el-option v-for="b in banques" :key="b.id" :label="b.nom" :value="b.id" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Référence bordereau">
                <el-select
                  v-model="form.approvisionnement_id"
                  filterable
                  clearable
                  placeholder="Sélectionner"
                  style="width: 100%"
                  :disabled="!form.banque_depot_id"
                >
                  <el-option
                    v-for="appro in filteredApprovisionnements"
                    :key="appro.id"
                    :label="appro.reference_bordereau"
                    :value="appro.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Observations">
            <el-input v-model="form.observations" type="textarea" :rows="3" />
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="formVisible = false">Annuler</el-button>
          <el-button type="primary" :loading="submitting" @click="handleSubmit">Enregistrer</el-button>
        </template>
      </el-dialog>

      <!-- Modal Détails -->
      <el-dialog v-model="detailVisible" title="Détails de l'avance" width="600px">
        <div v-if="selected">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="Société émettrice">{{ emetteurLabel(selected) }}</el-descriptions-item>
            <el-descriptions-item label="Bénéficiaire(s)">
              <template v-if="selected.beneficiaires?.length">
                <el-tag v-for="b in selected.beneficiaires" :key="b.id" size="small" class="benef-tag">{{ b.nom }}</el-tag>
              </template>
              <span v-else>-</span>
            </el-descriptions-item>
            <el-descriptions-item label="N° Chèque">{{ selected.numero_cheque }}</el-descriptions-item>
            <el-descriptions-item label="Date Chèque">{{ formatDate(selected.date_cheque) }}</el-descriptions-item>
            <el-descriptions-item label="N° Proforma">{{ selected.numero_proforma || '-' }}</el-descriptions-item>
            <el-descriptions-item label="Montant total">
              <strong>{{ formatMontant(selected.montant) }}</strong>
            </el-descriptions-item>
            <el-descriptions-item label="Montant utilisé">{{ formatMontant(selected.montant_utilise) }}</el-descriptions-item>
            <el-descriptions-item label="Solde restant">
              <strong style="color: #059669">{{ formatMontant(selected.montant_restant) }}</strong>
            </el-descriptions-item>
            <el-descriptions-item label="Statut">
              <el-tag :type="statutColor(selected.statut)" size="small">{{ selected.statut_libelle }}</el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="Institution">{{ selected.institution || '-' }}</el-descriptions-item>
            <el-descriptions-item label="Banque dépôt">{{ selected.banque_depot?.nom || '-' }}</el-descriptions-item>
            <el-descriptions-item label="Réf. bordereau">{{ selected.approvisionnement?.reference_bordereau || '-' }}</el-descriptions-item>
            <el-descriptions-item v-if="selected.bordereau_depot_path" label="Pièce jointe">
              <a :href="`/storage/${selected.bordereau_depot_path}`" target="_blank">Voir</a>
            </el-descriptions-item>
            <el-descriptions-item v-if="selected.observations" label="Observations">{{ selected.observations }}</el-descriptions-item>
          </el-descriptions>
        </div>
        <template #footer>
          <el-button @click="detailVisible = false">Fermer</el-button>
        </template>
      </el-dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search, Delete, Edit, View, RefreshLeft, Money, CircleCheck, Wallet, TrendCharts, ArrowDown, Printer } from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useMontant } from '@/Composables/useMontant';
import { fetchApi } from '@/Composables/useFetch';
import { debounce } from '@/utils/debounce';

const { formatMontant, formatInputMontant, parseInputMontant } = useMontant();

const props = defineProps({
  avances: { type: Array, default: () => [] },
  clients: { type: Array, default: () => [] },
  societes: { type: Array, default: () => [] },
  banques: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total_avances: 0, total_utilise: 0, total_disponible: 0, nombre_avances: 0 }) },
  pagination: { type: Object, default: () => ({ current_page: 1, per_page: 20, total: 0, last_page: 1 }) },
  filters: { type: Object, default: () => ({}) },
  user: { type: Object, default: () => null },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Avances Clients', path: '/avances-clients' },
];

const filters = ref({
  search: props.filters?.search || '',
  client_id: props.filters?.client_id || null,
  statut: props.filters?.statut || '',
});

// --- Filtres & pagination 100 % serveur ---
const handleSearch = () => {
  const params = {};
  if (filters.value.search) params.search = filters.value.search;
  if (filters.value.client_id) params.client_id = filters.value.client_id;
  if (filters.value.statut) params.statut = filters.value.statut;
  router.get('/avances-clients', params, {
    preserveState: true,
    preserveScroll: true,
    only: ['avances', 'stats', 'pagination', 'filters'],
  });
};
const debouncedSearch = debounce(handleSearch, 300);

const handleSizeChange = (size) => {
  const params = new URLSearchParams(window.location.search);
  params.set('per_page', size);
  params.set('page', '1');
  router.visit(`/avances-clients?${params.toString()}`, { preserveState: true, only: ['avances', 'pagination'] });
};

const handlePageChange = (page) => {
  const params = new URLSearchParams(window.location.search);
  params.set('page', page);
  router.visit(`/avances-clients?${params.toString()}`, { preserveState: true, preserveScroll: true, only: ['avances', 'pagination'] });
};

const resetFilters = () => {
  filters.value = { search: '', client_id: null, statut: '' };
  handleSearch();
};

const statutColor = (s) => ({
  disponible: 'success',
  partiellement_utilisee: 'warning',
  epuisee: 'info',
}[s] || 'primary');

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '-';

// --- Form ---
const formVisible = ref(false);
const editingId = ref(null);
const submitting = ref(false);
const formRef = ref(null);
const emptyForm = () => ({
  societe_emettrice_client_id: null,
  beneficiaires: [],
  numero_cheque: '',
  date_cheque: '',
  montant: null,
  numero_proforma: '',
  institution: '',
  banque_depot_id: null,
  approvisionnement_id: null,
  observations: '',
});

const form = ref(emptyForm());

const rules = {
  societe_emettrice_client_id: [{ required: true, message: 'Société émettrice requise', trigger: 'change' }],
  beneficiaires: [{ required: true, type: 'array', min: 1, message: 'Au moins un bénéficiaire', trigger: 'change' }],
  numero_cheque: [{ required: true, message: 'N° chèque requis', trigger: 'blur' }],
  date_cheque: [{ required: true, message: 'Date requise', trigger: 'change' }],
  montant: [{ required: true, message: 'Montant requis', trigger: 'blur' }],
};

const societeOptionLabel = (s) => (s.numero_compte ? `[${s.numero_compte}] ${s.nom}` : s.nom);

const emetteurLabel = (row) => {
  const e = row?.societe_emettrice_client;
  if (e) return e.numero_compte ? `[${e.numero_compte}] ${e.nom}` : e.nom;
  return row?.societe_emettrice || '-';
};

const exportEtatPdf = () => {
  const params = new URLSearchParams();
  if (filters.value.statut) params.set('statut', filters.value.statut);
  params.set('action', 'stream');
  window.open(`/avances-clients/etat/pdf?${params.toString()}`, '_blank');
};

const filteredApprovisionnements = computed(() => {
  if (!form.value.banque_depot_id) return [];
  const b = props.banques.find(x => x.id === form.value.banque_depot_id);
  return b?.approvisionnements || [];
});

const openCreate = () => {
  editingId.value = null;
  form.value = emptyForm();
  formVisible.value = true;
};

const openEdit = (avance) => {
  editingId.value = avance.id;
  form.value = {
    societe_emettrice_client_id: avance.societe_emettrice_client_id || null,
    beneficiaires: (avance.beneficiaires || []).map((b) => b.id),
    numero_cheque: avance.numero_cheque,
    date_cheque: avance.date_cheque,
    montant: avance.montant,
    numero_proforma: avance.numero_proforma || '',
    institution: avance.institution || '',
    banque_depot_id: avance.banque_depot_id || null,
    approvisionnement_id: avance.approvisionnement_id || null,
    observations: avance.observations || '',
  };
  formVisible.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  try {
    await formRef.value.validate();
  } catch {
    ElMessage.error('Corrigez les erreurs');
    return;
  }
  submitting.value = true;

  try {
    let response;

    if (editingId.value) {
      response = await fetchApi(`/api/avances-clients/${editingId.value}`, {
        method: 'PUT',
        body: form.value,
      });
    } else {
      const fd = new FormData();
      Object.entries(form.value).forEach(([k, v]) => {
        if (Array.isArray(v)) {
          v.forEach((item) => fd.append(`${k}[]`, item));
        } else if (v !== null && v !== undefined && v !== '') {
          fd.append(k, v);
        }
      });
      response = await fetchApi('/api/avances-clients', {
        method: 'POST',
        body: fd,
      });
    }

    const result = await response.json();
    if (result.success) {
      ElMessage.success(result.message || 'Succès');
      formVisible.value = false;
      router.reload();
    } else {
      ElMessage.error(result.message || 'Erreur');
    }
  } catch (e) {
    ElMessage.error('Erreur de connexion');
  } finally {
    submitting.value = false;
  }
};

const handleDelete = (avance) => {
  ElMessageBox.confirm(
    `Supprimer l'avance de ${avance.societe_emettrice} (Chq ${avance.numero_cheque}) ?`,
    'Confirmation',
    { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
  ).then(async () => {
    try {
      const res = await fetchApi(`/api/avances-clients/${avance.id}`, {
        method: 'DELETE',
      });
      const result = await res.json();
      if (result.success) {
        ElMessage.success('Avance supprimée');
        router.reload();
      } else {
        ElMessage.error(result.message || 'Erreur');
      }
    } catch {
      ElMessage.error('Erreur de connexion');
    }
  }).catch(() => {});
};

// --- Détails ---
const detailVisible = ref(false);
const selected = ref(null);
const openDetail = (a) => { selected.value = a; detailVisible.value = true; };

const handleAvanceAction = (command, avance) => {
  switch (command) {
    case 'view':
      openDetail(avance);
      break;
    case 'edit':
      openEdit(avance);
      break;
    case 'delete':
      handleDelete(avance);
      break;
  }
};
</script>

<style scoped>
.page-container { display: flex; flex-direction: column; gap: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; }
.page-title { font-size: 24px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0; }
.page-subtitle { font-size: 14px; color: #6b7280; margin: 0; }
.stats-row { margin-bottom: 4px; }
.stat-card { border-radius: 8px; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-content { display: flex; align-items: center; gap: 16px; }
.stat-icon { display: flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 12px; }
.stat-total .stat-icon { background-color: #dcfce7; color: #16a34a; }
.stat-today .stat-icon { background-color: #fef3c7; color: #d97706; }
.stat-count .stat-icon { background-color: #dbeafe; color: #2563eb; }
.stat-average .stat-icon { background-color: #f3e8ff; color: #9333ea; }
.stat-info { flex: 1; }
.stat-value { font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
.stat-label { font-size: 13px; color: #6b7280; }
.filter-card { border-radius: 8px; }
.filter-form { display: flex; flex-wrap: wrap; gap: 8px; }
.table-card { border-radius: 8px; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.card-title { font-size: 16px; font-weight: 600; color: #374151; }
.benef-tag { margin: 2px 4px 2px 0; }
:deep(.el-table th) { background-color: #f9fafb; font-weight: 600; color: #374151; }
:deep(.el-card__header) { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
</style>
