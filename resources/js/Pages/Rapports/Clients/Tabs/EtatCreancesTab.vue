<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Mode">
          <el-radio-group v-model="selectedMode" size="default">
            <el-radio-button label="par_client">Par client</el-radio-button>
            <el-radio-button label="un_client">Un client</el-radio-button>
            <el-radio-button label="tous_clients">Tous les clients</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="selectedMode === 'un_client'" label="Client">
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

        <el-form-item label="Type client">
          <el-select v-model="selectedTypeClient" placeholder="Tous" clearable style="width: 160px">
            <el-option label="Société" value="societe" />
            <el-option label="Divers" value="divers" />
            <el-option label="Personnel" value="personnel" />
            <el-option label="Autre" value="autre" />
          </el-select>
        </el-form-item>

        <el-form-item v-if="selectedMode === 'tous_clients'" label="Date début">
          <el-date-picker v-model="dateDebut" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date début" />
        </el-form-item>

        <el-form-item v-if="selectedMode === 'tous_clients'" label="Date fin">
          <el-date-picker v-model="dateFin" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date fin" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="data.length === 0" class="empty-state">
        <el-empty description="Aucune créance trouvée" />
      </div>

      <!-- Mode par_client / un_client -->
      <template v-if="(selectedMode === 'par_client' || selectedMode === 'un_client') && data.length > 0">
        <div v-for="clientData in data" :key="clientData.client_id" class="client-block">
          <div class="client-header-box">
            <strong>{{ clientData.numero_compte }}</strong> — {{ clientData.raison_sociale }}
          </div>
          <el-table style="width: 100%" :data="clientData.lignes" border size="small" stripe>
            <el-table-column prop="numero" label="N°" min-width="50" align="center" />
            <el-table-column prop="reference" label="Réf. Facture" />
            <el-table-column prop="date_facture" label="Date Facture" min-width="110" />
            <el-table-column label="Montant Facture" min-width="130" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant_facture) }}</template>
            </el-table-column>
            <el-table-column label="Montant Payé" min-width="120" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant_paye) }}</template>
            </el-table-column>
            <el-table-column label="Reste à Payer" min-width="120" align="right">
              <template #default="{ row }">
                <span style="color: #cc0000; font-weight: bold;">{{ formatMontant(row.reste_a_payer) }}</span>
              </template>
            </el-table-column>
          </el-table>
          <div class="client-totals">
            <span>Total Factures: <strong>{{ formatMontant(clientData.total_facture) }}</strong></span>
            <span>Total Payé: <strong>{{ formatMontant(clientData.total_paye) }}</strong></span>
            <span style="color: #cc0000">Reste: <strong>{{ formatMontant(clientData.total_reste) }}</strong></span>
          </div>
        </div>

        <div v-if="data.length > 1" class="grand-total">
          <span>TOTAL GÉNÉRAL — Factures: <strong>{{ formatMontant(grandTotalFacture) }}</strong></span>
          <span>Payé: <strong>{{ formatMontant(grandTotalPaye) }}</strong></span>
          <span style="color: #cc0000">Reste: <strong>{{ formatMontant(grandTotalReste) }}</strong></span>
        </div>
      </template>

      <!-- Mode tous_clients -->
      <template v-if="selectedMode === 'tous_clients' && data.length > 0">
        <el-table style="width: 100%" :data="data" border size="small" stripe show-summary :summary-method="getSummary">
          <el-table-column prop="numero" label="N°" min-width="50" align="center" />
          <el-table-column prop="numero_compte" label="N° Compte" min-width="110" />
          <el-table-column prop="raison_sociale" label="Raison sociale" />
          <el-table-column label="Total Factures" min-width="140" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_facture) }}</template>
          </el-table-column>
          <el-table-column label="Total Règlements" min-width="150" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_paye) }}</template>
          </el-table-column>
          <el-table-column label="Reste à Payer" min-width="130" align="right">
            <template #default="{ row }">
              <span style="color: #cc0000; font-weight: bold;">{{ formatMontant(row.total_reste) }}</span>
            </template>
          </el-table-column>
        </el-table>
      </template>

      <!-- Actions -->
      <div v-if="data.length > 0" class="actions-bar">
        <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
        <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
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

const selectedMode = ref('par_client');
const selectedClientId = ref(null);
const selectedTypeClient = ref('');
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);

const grandTotalFacture = computed(() => data.value.reduce((s, d) => s + (d.total_facture || 0), 0));
const grandTotalPaye = computed(() => data.value.reduce((s, d) => s + (d.total_paye || 0), 0));
const grandTotalReste = computed(() => data.value.reduce((s, d) => s + (d.total_reste || 0), 0));

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (selectedMode.value === 'un_client' && !selectedClientId.value) {
    ElMessage.warning('Veuillez sélectionner un client');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    if (selectedMode.value === 'un_client') params.append('client_id', selectedClientId.value);
    if (selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
    if (dateDebut.value) params.append('date_debut', dateDebut.value);
    if (dateFin.value) params.append('date_fin', dateFin.value);

    const res = await fetch(`/rapports/clients/api/etat-creances?${params}`);
    const json = await res.json();
    data.value = json.data || [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummary = ({ columns, data: tableData }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    if (i === 1 || i === 2) { sums[i] = ''; return; }
    const key = i === 3 ? 'total_facture' : i === 4 ? 'total_paye' : 'total_reste';
    sums[i] = formatMontant(tableData.reduce((s, r) => s + (r[key] || 0), 0));
  });
  return sums;
};

const buildPdfParams = () => {
  const params = new URLSearchParams({ mode: selectedMode.value });
  if (selectedMode.value === 'un_client') params.append('client_id', selectedClientId.value);
  if (selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
  if (dateDebut.value) params.append('date_debut', dateDebut.value);
  if (dateFin.value) params.append('date_fin', dateFin.value);
  return params;
};

const exportPdf = () => {
  window.open(`/rapports/clients/pdf/etat-creances?${buildPdfParams()}`, '_blank');
};

const exportExcel = () => {
  window.open(`/rapports/clients/excel/etat-creances?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/etat-creances?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.client-block { margin-bottom: 24px; }
.client-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.client-totals { display: flex; gap: 24px; padding: 10px 14px; background: #fafafa; border: 1px solid #eee; font-size: 13px; }
.grand-total { display: flex; gap: 24px; padding: 14px; background: #f0f0f0; border: 2px solid #333; font-size: 14px; margin-top: 16px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
