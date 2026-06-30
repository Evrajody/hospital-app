<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Fournisseur">
          <el-select
            v-model="selectedFournisseurId"
            placeholder="Tous les fournisseurs"
            filterable
            clearable
            style="width: 300px"
          >
            <el-option
              v-for="f in fournisseurs"
              :key="f.id"
              :label="`[${f.code}] ${f.nom}`"
              :value="f.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Filtre date">
          <el-radio-group v-model="filtreDate" size="default" @change="onFiltreDateChange">
            <el-radio-button label="periode">Période</el-radio-button>
            <el-radio-button label="point">Date</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="filtreDate === 'periode'" label="Période">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="à"
            start-placeholder="Date début"
            end-placeholder="Date fin"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            unlink-panels
          />
        </el-form-item>

        <el-form-item v-else label="À la date">
          <el-date-picker
            v-model="datePoint"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date d'arrêté"
            style="width: 200px"
            clearable
          />
          <el-tooltip
            content="Solde arrêté à cette date : règlements effectués jusqu'à cette date inclus"
            placement="top"
          >
            <el-icon style="margin-left: 6px; color: #909399; cursor: help;"><InfoFilled /></el-icon>
          </el-tooltip>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="factures.length === 0" class="empty-state">
        <el-empty description="Aucune facture soldée trouvée" />
      </div>
      <template v-else>
        <PaginatedTable
          style="width: 100%"
          :data="factures"
          border
          size="small"
          stripe
          show-summary
          :summary-method="getSummary"
        >
          <el-table-column label="Fournisseur" min-width="280">
            <template #default="{ row }">{{ row.fournisseur_label }}</template>
          </el-table-column>
          <el-table-column prop="numero" label="N° Pièce" min-width="120" />
          <el-table-column label="Date Enregistrement" min-width="130" align="center">
            <template #default="{ row }">{{ formatDate(row.date_facture) }}</template>
          </el-table-column>
          <el-table-column label="Montant Dû" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_ttc) }}</template>
          </el-table-column>
          <el-table-column label="Rég. Période" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.reg_periode) }}</template>
          </el-table-column>
          <el-table-column label="Mt Total Rég." min-width="130" align="right">
            <template #default="{ row }">
              <span style="font-weight: bold;">{{ formatMontant(row.mt_total_reg) }}</span>
            </template>
          </el-table-column>
        </PaginatedTable>

        <!-- Actions Export -->
        <div class="actions-bar">
          <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { InfoFilled } from '@element-plus/icons-vue';
import { useMontant } from '@/Composables/useMontant';
import PaginatedTable from '@/Components/PaginatedTable.vue';

const props = defineProps({
  fournisseurs: { type: Array, default: () => [] },
});

const { formatMontant } = useMontant();

const selectedFournisseurId = ref(null);
const filtreDate = ref('periode'); // 'periode' | 'point'
const dateRange = ref([]);
const datePoint = ref('');
const loading = ref(false);
const fetched = ref(false);
const factures = ref([]);
const totaux = ref({});

// Quand l'utilisateur bascule entre Période et Date, on vide l'autre champ
const onFiltreDateChange = () => {
  if (filtreDate.value === 'periode') {
    datePoint.value = '';
  } else {
    dateRange.value = [];
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};

// Construit les paramètres de requête communs (API + Excel)
const buildParams = () => {
  const params = new URLSearchParams();
  if (selectedFournisseurId.value) params.append('fournisseur_id', selectedFournisseurId.value);
  const [debut, fin] = dateRange.value || [];
  if (filtreDate.value === 'point' && datePoint.value) {
    params.append('date_point', datePoint.value);
  } else if (filtreDate.value === 'periode') {
    if (debut) params.append('date_debut', debut);
    if (fin) params.append('date_fin', fin);
  }
  return params;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = buildParams();

    const res = await fetch(`/rapports/fournisseurs/api/factures-soldes?${params}`);
    const json = await res.json();
    factures.value = json.factures || [];
    totaux.value = json.totaux || {};
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummary = ({ columns }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAUX'; return; }
    if (i === 1 || i === 2) { sums[i] = ''; return; }
    const keys = { 3: 'montant_ttc', 4: 'reg_periode', 5: 'mt_total_reg' };
    if (keys[i]) {
      sums[i] = formatMontant(totaux.value[keys[i]] || 0);
    } else {
      sums[i] = '';
    }
  });
  return sums;
};

import { useAsyncExport } from '@/Composables/useAsyncExport';
const { startExport } = useAsyncExport();
const REPORT_KEY = 'rapports-fournisseurs.factures-soldes';

const exportExcel = () => {
  startExport(REPORT_KEY, 'excel', Object.fromEntries(buildParams()));
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
