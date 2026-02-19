<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Mode">
          <el-radio-group v-model="selectedMode" size="default">
            <el-radio-button label="global_du">CA du</el-radio-button>
            <el-radio-button label="global_au">CA au</el-radio-button>
            <el-radio-button label="global_periode">CA période</el-radio-button>
            <el-radio-button label="par_client">Par client</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="selectedMode === 'global_du' || selectedMode === 'global_au'" label="Date">
          <el-date-picker v-model="dateRef" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Sélectionner une date" />
        </el-form-item>

        <el-form-item v-if="selectedMode === 'global_periode' || selectedMode === 'par_client'" label="Date début">
          <el-date-picker v-model="dateDebut" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date début" />
        </el-form-item>

        <el-form-item v-if="selectedMode === 'global_periode' || selectedMode === 'par_client'" label="Date fin">
          <el-date-picker v-model="dateFin" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date fin" />
        </el-form-item>

        <el-form-item v-if="selectedMode === 'par_client'" label="Client">
          <el-select
            v-model="selectedClientId"
            placeholder="Sélectionner un client"
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
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <!-- Mode Global -->
      <template v-if="isGlobal && data.theorique !== undefined">
        <div class="global-ca-card">
          <div class="ca-row">
            <div class="ca-item">
              <div class="ca-label">CA Théorique</div>
              <div class="ca-value">{{ formatMontant(data.theorique) }}</div>
            </div>
            <div class="ca-item">
              <div class="ca-label">CA Physique</div>
              <div class="ca-value">{{ formatMontant(data.physique) }}</div>
            </div>
            <div class="ca-item ecart">
              <div class="ca-label">Écart</div>
              <div class="ca-value" :style="{ color: data.ecart > 0 ? '#cc0000' : '#333' }">{{ formatMontant(data.ecart) }}</div>
            </div>
          </div>
        </div>
      </template>

      <!-- Mode Par Client -->
      <template v-if="selectedMode === 'par_client' && data.lignes">
        <div class="client-header-box">
          <strong>{{ data.numero_compte }}</strong> — {{ data.raison_sociale }}
        </div>
        <el-table :data="data.lignes" border size="small" stripe show-summary :summary-method="getClientSummary">
          <el-table-column prop="numero" label="N°" width="50" align="center" />
          <el-table-column prop="reference" label="Réf. Facture" />
          <el-table-column prop="date_facture" label="Date Facture" width="110" />
          <el-table-column label="Montant CA" width="140" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
        </el-table>
      </template>

      <!-- Pas de données -->
      <div v-if="noData" class="empty-state">
        <el-empty description="Aucune donnée trouvée" />
      </div>

      <!-- Actions -->
      <div v-if="!noData" class="actions-bar">
        <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
        <el-button @click="printReport">Imprimer</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  clients: { type: Array, default: () => [] },
});

const selectedMode = ref('global_du');
const selectedClientId = ref(null);
const dateDebut = ref('');
const dateFin = ref('');
const dateRef = ref('');
const loading = ref(false);
const fetched = ref(false);
const data = ref({});

const isGlobal = computed(() => ['global_du', 'global_au', 'global_periode'].includes(selectedMode.value));

const noData = computed(() => {
  if (!fetched.value) return false;
  if (isGlobal.value) return data.value.theorique === undefined;
  if (selectedMode.value === 'par_client') return !data.value.lignes || data.value.lignes.length === 0;
  return true;
});

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (selectedMode.value === 'par_client' && !selectedClientId.value) {
    ElMessage.warning('Veuillez sélectionner un client');
    return;
  }
  if ((selectedMode.value === 'global_du' || selectedMode.value === 'global_au') && !dateRef.value) {
    ElMessage.warning('Veuillez sélectionner une date');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    if (dateRef.value) params.append('date_ref', dateRef.value);
    if (selectedClientId.value) params.append('client_id', selectedClientId.value);
    if (dateDebut.value) params.append('date_debut', dateDebut.value);
    if (dateFin.value) params.append('date_fin', dateFin.value);

    const res = await fetch(`/rapports/clients/api/chiffre-affaires?${params}`);
    const json = await res.json();
    data.value = json.data || {};
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getClientSummary = ({ columns, data: tableData }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    if (i === 3) { sums[i] = formatMontant(tableData.reduce((s, r) => s + (r.montant || 0), 0)); return; }
    sums[i] = '';
  });
  return sums;
};

const buildPdfParams = () => {
  const params = new URLSearchParams({ mode: selectedMode.value });
  if (dateRef.value) params.append('date_ref', dateRef.value);
  if (selectedClientId.value) params.append('client_id', selectedClientId.value);
  if (dateDebut.value) params.append('date_debut', dateDebut.value);
  if (dateFin.value) params.append('date_fin', dateFin.value);
  return params;
};

const exportPdf = () => {
  window.open(`/rapports/clients/pdf/chiffre-affaires?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/chiffre-affaires?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.client-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.global-ca-card { max-width: 700px; margin: 30px auto; }
.ca-row { display: flex; gap: 0; border: 2px solid #333; }
.ca-item { flex: 1; text-align: center; padding: 24px 16px; border-right: 1px solid #ddd; }
.ca-item:last-child { border-right: none; }
.ca-label { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #666; margin-bottom: 10px; }
.ca-value { font-size: 24px; font-weight: bold; font-family: 'Courier New', monospace; }
.ca-item.ecart .ca-value { color: #cc0000; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
