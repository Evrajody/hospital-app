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

        <el-form-item v-if="selectedMode !== 'un_client'" label="Type client">
          <el-select v-model="selectedTypeClient" placeholder="Tous" clearable style="width: 160px">
            <el-option label="Société" value="societe" />
            <el-option label="Divers" value="divers" />
            <el-option label="Personnel" value="personnel" />
            <el-option label="Autre" value="autre" />
          </el-select>
        </el-form-item>

        <el-form-item label="Filtre">
          <el-radio-group v-model="filtreDate" size="default">
            <el-radio-button v-if="selectedMode !== 'tous_clients'" label="aucun">Aucun</el-radio-button>
            <el-radio-button label="date">Date</el-radio-button>
            <el-radio-button label="periode">Période</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="filtreDate === 'date'" label="Au">
          <el-date-picker
            v-model="dateUnique"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date"
          />
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
        <!-- par_client + pas de type filtré : groupement par type -->
        <template v-if="groupesParType && groupesParType.length > 0">
          <div v-for="groupe in groupesParType" :key="groupe.type" class="type-group">
            <div class="type-header">{{ groupe.label }}</div>
            <div v-for="clientData in groupe.clients" :key="clientData.client_id" class="client-block">
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
                <el-table-column label="Total rejet" min-width="110" align="right">
                  <template #default="{ row }">
                    <span :style="{ color: row.total_rejet > 0 ? '#cc0000' : 'inherit' }">{{ formatMontant(row.total_rejet) }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="Total pertes" min-width="110" align="right">
                  <template #default="{ row }">
                    <span :style="{ color: row.total_perte > 0 ? '#cc0000' : 'inherit' }">{{ formatMontant(row.total_perte) }}</span>
                  </template>
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
                <span style="color: #cc0000">Total rejet: <strong>{{ formatMontant(clientData.total_rejet) }}</strong></span>
                <span style="color: #cc0000">Total pertes: <strong>{{ formatMontant(clientData.total_perte) }}</strong></span>
                <span style="color: #cc0000">Reste: <strong>{{ formatMontant(clientData.total_reste) }}</strong></span>
              </div>
            </div>
          </div>
        </template>

        <!-- par_client + type filtré, OU un_client : pas de groupement -->
        <template v-else>
          <div v-for="clientData in data" :key="clientData.client_id" class="client-block">
            <div class="client-header-box">
              <strong>{{ clientData.numero_compte }}</strong> — {{ clientData.raison_sociale }}
              <span v-if="selectedMode === 'un_client'" class="type-inline">&nbsp;- <em>{{ clientData.type_client_label || 'Non défini' }}</em></span>
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
              <el-table-column label="Total rejet" min-width="110" align="right">
                <template #default="{ row }">
                  <span :style="{ color: row.total_rejet > 0 ? '#cc0000' : 'inherit' }">{{ formatMontant(row.total_rejet) }}</span>
                </template>
              </el-table-column>
              <el-table-column label="Total pertes" min-width="110" align="right">
                <template #default="{ row }">
                  <span :style="{ color: row.total_perte > 0 ? '#cc0000' : 'inherit' }">{{ formatMontant(row.total_perte) }}</span>
                </template>
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
              <span style="color: #cc0000">Total rejet: <strong>{{ formatMontant(clientData.total_rejet) }}</strong></span>
              <span style="color: #cc0000">Total pertes: <strong>{{ formatMontant(clientData.total_perte) }}</strong></span>
              <span style="color: #cc0000">Reste: <strong>{{ formatMontant(clientData.total_reste) }}</strong></span>
            </div>
          </div>
        </template>

        <div v-if="data.length > 1" class="grand-total">
          <span>TOTAL GÉNÉRAL — Factures: <strong>{{ formatMontant(grandTotalFacture) }}</strong></span>
          <span>Payé: <strong>{{ formatMontant(grandTotalPaye) }}</strong></span>
          <span style="color: #cc0000">Rejet: <strong>{{ formatMontant(grandTotalRejet) }}</strong></span>
          <span style="color: #cc0000">Pertes: <strong>{{ formatMontant(grandTotalPerte) }}</strong></span>
          <span style="color: #cc0000">Reste: <strong>{{ formatMontant(grandTotalReste) }}</strong></span>
        </div>
      </template>

      <!-- Mode tous_clients -->
      <template v-if="selectedMode === 'tous_clients' && data.length > 0">
        <el-table
          style="width: 100%"
          :data="tousClientsRows"
          border
          size="small"
          stripe
          show-summary
          :summary-method="getSummary"
          :span-method="tousClientsSpanMethod"
          :row-class-name="tousClientsRowClass"
        >
          <el-table-column label="N°" min-width="50" align="center">
            <template #default="{ row }">
              <template v-if="row.isTypeHeader"><strong>{{ row.type_label }}</strong></template>
              <template v-else>{{ row.numero }}</template>
            </template>
          </el-table-column>
          <el-table-column prop="numero_compte" label="N° Compte" min-width="110" />
          <el-table-column prop="raison_sociale" label="Raison sociale" />
          <el-table-column label="Total Factures" min-width="140" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_facture) }}</template>
          </el-table-column>
          <el-table-column label="Total Règlements" min-width="150" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_paye) }}</template>
          </el-table-column>
          <el-table-column label="Total rejet" min-width="120" align="right">
            <template #default="{ row }">
              <span :style="{ color: row.total_rejet > 0 ? '#cc0000' : 'inherit' }">{{ formatMontant(row.total_rejet) }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Total pertes" min-width="120" align="right">
            <template #default="{ row }">
              <span :style="{ color: row.total_perte > 0 ? '#cc0000' : 'inherit' }">{{ formatMontant(row.total_perte) }}</span>
            </template>
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
import { ref, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  clients: { type: Array, default: () => [] },
});

const selectedMode = ref('par_client');
const selectedClientId = ref(null);
const selectedTypeClient = ref('');
const filtreDate = ref('aucun');
const dateUnique = ref('');
const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);
const groupesParType = ref(null);
const typeClientLabel = ref(null);

const grandTotalFacture = computed(() => data.value.reduce((s, d) => s + (d.total_facture || 0), 0));
const grandTotalPaye = computed(() => data.value.reduce((s, d) => s + (d.total_paye || 0), 0));
const grandTotalReste = computed(() => data.value.reduce((s, d) => s + (d.total_reste || 0), 0));
const grandTotalRejet = computed(() => data.value.reduce((s, d) => s + (d.total_rejet || 0), 0));
const grandTotalPerte = computed(() => data.value.reduce((s, d) => s + (d.total_perte || 0), 0));

const tousClientsRows = computed(() => {
  if (selectedMode.value !== 'tous_clients') return [];
  if (typeClientLabel.value) return data.value;
  const sorted = [...data.value].sort((a, b) => {
    const ta = a.type_client_label || 'zzz';
    const tb = b.type_client_label || 'zzz';
    if (ta === tb) return (a.raison_sociale || '').localeCompare(b.raison_sociale || '');
    return ta.localeCompare(tb);
  });
  const rows = [];
  let lastType = null;
  for (const client of sorted) {
    const label = client.type_client_label || 'Non défini';
    if (label !== lastType) {
      rows.push({ isTypeHeader: true, type_label: label });
      lastType = label;
    }
    rows.push(client);
  }
  return rows;
});

const tousClientsSpanMethod = ({ row, columnIndex }) => {
  if (!row?.isTypeHeader) return { rowspan: 1, colspan: 1 };
  if (columnIndex === 0) return { rowspan: 1, colspan: 8 };
  return { rowspan: 0, colspan: 0 };
};

const tousClientsRowClass = ({ row }) => row?.isTypeHeader ? 'type-header-row' : '';

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

// Reset au changement de mode
watch(selectedMode, () => {
  data.value = [];
  groupesParType.value = null;
  typeClientLabel.value = null;
  fetched.value = false;
  // tous_clients : pas d'"Aucun" → force la sélection
  if (selectedMode.value === 'tous_clients' && filtreDate.value === 'aucun') {
    filtreDate.value = 'periode';
  }
});

// Reset des valeurs quand on change de filtre
watch(filtreDate, () => {
  dateUnique.value = '';
  dateRange.value = [];
});

const validate = () => {
  if (selectedMode.value === 'un_client' && !selectedClientId.value) {
    ElMessage.warning('Veuillez sélectionner un client');
    return false;
  }
  if (selectedMode.value === 'tous_clients' && filtreDate.value === 'aucun') {
    ElMessage.warning('Veuillez choisir Date ou Période pour le mode "Tous les clients"');
    return false;
  }
  if (filtreDate.value === 'date' && !dateUnique.value) {
    ElMessage.warning('Veuillez sélectionner une date');
    return false;
  }
  if (filtreDate.value === 'periode' && (!dateRange.value || !dateRange.value[0] || !dateRange.value[1])) {
    ElMessage.warning('Veuillez sélectionner une période complète');
    return false;
  }
  return true;
};

const buildBaseParams = () => {
  const params = new URLSearchParams({ mode: selectedMode.value });
  if (selectedMode.value === 'un_client') params.append('client_id', selectedClientId.value);
  if (selectedMode.value !== 'un_client' && selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
  if (filtreDate.value === 'date' && dateUnique.value) {
    params.append('date_fin', dateUnique.value);
  } else if (filtreDate.value === 'periode' && dateRange.value && dateRange.value[0] && dateRange.value[1]) {
    params.append('date_debut', dateRange.value[0]);
    params.append('date_fin', dateRange.value[1]);
  }
  return params;
};

const fetchData = async () => {
  if (!validate()) return;
  loading.value = true;
  try {
    const res = await fetch(`/rapports/clients/api/etat-creances?${buildBaseParams()}`);
    const json = await res.json();
    data.value = json.data || [];
    groupesParType.value = json.groupes_par_type || null;
    typeClientLabel.value = json.type_client_label || null;
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummary = ({ columns, data: tableData }) => {
  const keys = { 3: 'total_facture', 4: 'total_paye', 5: 'total_rejet', 6: 'total_perte', 7: 'total_reste' };
  return columns.map((_, i) => {
    if (i === 0) return 'TOTAL';
    if (i === 1 || i === 2) return '';
    const key = keys[i];
    return key ? formatMontant(tableData.reduce((s, r) => s + (r[key] || 0), 0)) : '';
  });
};

const exportPdf = () => {
  window.open(`/rapports/clients/pdf/etat-creances?${buildBaseParams()}`, '_blank');
};

const exportExcel = () => {
  window.open(`/rapports/clients/excel/etat-creances?${buildBaseParams()}`, '_blank');
};

const printReport = () => {
  const params = buildBaseParams();
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
.type-group { margin-bottom: 32px; }
.type-header { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 14px; background: #e5e7eb; border-left: 4px solid #1f2937; margin-bottom: 12px; }
.type-inline { color: #555; }
.type-inline em { font-style: italic; }
:deep(.type-header-row td) { background: #ffffff !important; text-align: center; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; font-size: 13px; }
.client-block { margin-bottom: 24px; }
.client-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.client-totals { display: flex; gap: 24px; padding: 10px 14px; background: #fafafa; border: 1px solid #eee; font-size: 13px; }
.grand-total { display: flex; gap: 24px; padding: 14px; background: #f0f0f0; border: 2px solid #333; font-size: 14px; margin-top: 16px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
