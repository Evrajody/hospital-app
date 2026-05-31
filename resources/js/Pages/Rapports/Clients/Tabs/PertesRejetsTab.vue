<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Type">
          <el-radio-group v-model="selectedType" size="default">
            <el-radio-button label="perte">Pertes</el-radio-button>
            <el-radio-button label="rejet">Rejets</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="Type client">
          <el-select v-model="selectedTypeClient" placeholder="Tous" clearable style="width: 180px">
            <el-option label="Société" value="societe" />
            <el-option label="Divers" value="divers" />
            <el-option label="Personnel" value="personnel" />
            <el-option label="Autre" value="autre" />
          </el-select>
        </el-form-item>

        <el-form-item label="Client">
          <el-select
            v-model="selectedClientId"
            placeholder="Tous les clients"
            filterable
            clearable
            style="width: 280px"
          >
            <el-option
              v-for="c in clients"
              :key="c.id"
              :label="`${c.code} - ${c.nom}`"
              :value="c.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item>
          <el-checkbox v-model="usePeriode">Période</el-checkbox>
        </el-form-item>

        <el-form-item v-if="usePeriode" label="Période">
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

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="data.length === 0" class="empty-state">
        <el-empty description="Aucune donnée trouvée" />
      </div>

      <template v-if="data.length > 0">
        <!-- Tableau -->
        <el-table style="width: 100%" :data="data" border size="small" stripe>
          <el-table-column prop="id" label="N°" min-width="50" align="center">
            <template #default="{ $index }">{{ $index + 1 }}</template>
          </el-table-column>
          <el-table-column prop="date_reglement" label="Date" min-width="100" />
          <el-table-column prop="client_nom" label="Client" min-width="180">
            <template #default="{ row }">
              <strong>{{ row.client_nom }}</strong><br>
              <small style="color: #999">{{ row.client_code }}</small>
            </template>
          </el-table-column>
          <el-table-column prop="facture_reference" label="Réf. Facture" min-width="120" />
          <el-table-column label="Montant" min-width="120" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
          <el-table-column prop="observations" label="Observations" min-width="180">
            <template #default="{ row }">
              <span style="font-size: 12px; color: #666">{{ row.observations || '-' }}</span>
            </template>
          </el-table-column>
        </el-table>
      </template>

      <!-- Actions Export -->
      <div v-if="data.length > 0" class="actions-bar">
        <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
        <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
        <el-button @click="printReport">Imprimer</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePdfViewer } from '@/Composables/usePdfViewer';
const { openPdf } = usePdfViewer();
import { ref, watch } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  clients: { type: Array, default: () => [] },
});

const selectedType = ref('perte');
const selectedTypeClient = ref('');
const selectedClientId = ref(null);
const usePeriode = ref(false);
const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

watch(usePeriode, (v) => {
  if (!v) dateRange.value = [];
});

const buildParams = () => {
  const params = new URLSearchParams();
  params.append('type_reglement', selectedType.value);
  if (selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
  if (selectedClientId.value) params.append('client_id', selectedClientId.value);
  if (usePeriode.value && dateRange.value && dateRange.value[0] && dateRange.value[1]) {
    params.append('date_debut', dateRange.value[0]);
    params.append('date_fin', dateRange.value[1]);
  }
  return params;
};

const fetchData = async () => {
  if (!selectedType.value) {
    ElMessage.warning('Veuillez choisir un type (Pertes ou Rejets)');
    return;
  }
  if (usePeriode.value && (!dateRange.value || !dateRange.value[0] || !dateRange.value[1])) {
    ElMessage.warning('Veuillez sélectionner une période complète');
    return;
  }
  loading.value = true;
  try {
    const params = buildParams();
    const res = await fetch(`/rapports/clients/api/pertes-rejets?${params}`);
    const json = await res.json();
    data.value = json.data || [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const exportPdf = () => {
  openPdf(`/rapports/clients/pdf/pertes-rejets?${buildParams()}`, 'Aperçu du rapport');
};

const exportExcel = () => {
  window.open(`/rapports/clients/excel/pertes-rejets?${buildParams()}`, '_blank');
};

const printReport = () => {
  const params = buildParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/pertes-rejets?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
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
