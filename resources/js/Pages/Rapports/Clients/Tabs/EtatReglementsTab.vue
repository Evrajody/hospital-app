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

        <el-form-item v-if="selectedMode !== 'tous_clients'">
          <el-checkbox v-model="usePeriode">Période</el-checkbox>
        </el-form-item>

        <el-form-item v-if="periodeVisible" label="Période">
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

      <!-- Mode par_client / un_client -->
      <template v-if="(selectedMode === 'par_client' || selectedMode === 'un_client') && data.length > 0">
        <!-- par_client + pas de type filtré : groupement par type -->
        <template v-if="groupesParType && groupesParType.length > 0">
          <GroupNavigator :groups="groupedClients">
            <template #label="{ group }">
              <span class="type-inline"><em>{{ group._typeLabel }}</em> · </span>
              <strong>{{ group.numero_compte }}</strong> — {{ group.raison_sociale }}
            </template>
            <template #default="{ group }">
              <PaginatedTable style="width: 100%" :data="group.lignes" border size="small" stripe>
                <el-table-column prop="numero" label="N°" min-width="50" align="center" />
                <el-table-column prop="reference" label="Réf. Facture" />
                <el-table-column prop="date_facture" label="Date Facture" min-width="110" />
                <el-table-column prop="date_reglement" label="Date Règlement" min-width="120" />
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
                <el-table-column label="Solde" min-width="110" align="right">
                  <template #default="{ row }">
                    <strong>{{ formatMontant(row.solde) }}</strong>
                  </template>
                </el-table-column>
              </PaginatedTable>
              <div class="client-totals">
                <span>Total Factures: <strong>{{ formatMontant(group.total_facture) }}</strong></span>
                <span>Total Payé: <strong>{{ formatMontant(group.total_paye) }}</strong></span>
                <span style="color: #cc0000">Total rejet: <strong>{{ formatMontant(group.total_rejet) }}</strong></span>
                <span style="color: #cc0000">Total pertes: <strong>{{ formatMontant(group.total_perte) }}</strong></span>
                <span>Solde: <strong>{{ formatMontant(group.total_solde) }}</strong></span>
              </div>
            </template>
          </GroupNavigator>
        </template>

        <!-- par_client + type filtré, OU un_client : pas de groupement -->
        <template v-else>
          <GroupNavigator :groups="data">
            <template #label="{ group }">
              <strong>{{ group.numero_compte }}</strong> — {{ group.raison_sociale }}
              <span v-if="selectedMode === 'un_client'" class="type-inline">&nbsp;- <em>{{ group.type_client_label || 'Non défini' }}</em></span>
            </template>
            <template #default="{ group }">
              <PaginatedTable style="width: 100%" :data="group.lignes" border size="small" stripe>
                <el-table-column prop="numero" label="N°" min-width="50" align="center" />
                <el-table-column prop="reference" label="Réf. Facture" />
                <el-table-column prop="date_facture" label="Date Facture" min-width="110" />
                <el-table-column prop="date_reglement" label="Date Règlement" min-width="120" />
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
                <el-table-column label="Solde" min-width="110" align="right">
                  <template #default="{ row }">
                    <strong>{{ formatMontant(row.solde) }}</strong>
                  </template>
                </el-table-column>
              </PaginatedTable>
              <div class="client-totals">
                <span>Total Factures: <strong>{{ formatMontant(group.total_facture) }}</strong></span>
                <span>Total Payé: <strong>{{ formatMontant(group.total_paye) }}</strong></span>
                <span style="color: #cc0000">Total rejet: <strong>{{ formatMontant(group.total_rejet) }}</strong></span>
                <span style="color: #cc0000">Total pertes: <strong>{{ formatMontant(group.total_perte) }}</strong></span>
                <span>Solde: <strong>{{ formatMontant(group.total_solde) }}</strong></span>
              </div>
            </template>
          </GroupNavigator>
        </template>

        <div v-if="data.length > 1" class="grand-total">
          <span>TOTAL GÉNÉRAL — Factures: <strong>{{ formatMontant(grandTotalFacture) }}</strong></span>
          <span>Payé: <strong>{{ formatMontant(grandTotalPaye) }}</strong></span>
          <span style="color: #cc0000">Rejet: <strong>{{ formatMontant(grandTotalRejet) }}</strong></span>
          <span style="color: #cc0000">Pertes: <strong>{{ formatMontant(grandTotalPerte) }}</strong></span>
          <span>Solde: <strong>{{ formatMontant(grandTotalSolde) }}</strong></span>
        </div>
      </template>

      <!-- Mode tous_clients -->
      <template v-if="selectedMode === 'tous_clients' && data.length > 0">
        <PaginatedTable
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
          <el-table-column label="Montant Factures" min-width="140" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_facture) }}</template>
          </el-table-column>
          <el-table-column label="Montant Règlements" min-width="150" align="right">
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
          <el-table-column label="Solde" min-width="120" align="right">
            <template #default="{ row }">
              <strong>{{ formatMontant(row.total_solde) }}</strong>
            </template>
          </el-table-column>
        </PaginatedTable>
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
import { useAsyncExport } from '@/Composables/useAsyncExport';
const { startExport } = useAsyncExport();
const REPORT_KEY = 'rapports-clients.etat-reglements';
import { ref, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import PaginatedTable from '@/Components/PaginatedTable.vue';
import GroupNavigator from '@/Components/GroupNavigator.vue';

const props = defineProps({
  clients: { type: Array, default: () => [] },
});

// Aplatit le groupement type → clients en une liste de clients (un par « page »
// du GroupNavigator), chacun gardant le libellé de son type pour l'affichage.
const groupedClients = computed(() => {
  if (!groupesParType.value) return [];
  return groupesParType.value.flatMap((g) => g.clients.map((c) => ({ ...c, _typeLabel: g.label })));
});

const selectedMode = ref('par_client');
const selectedClientId = ref(null);
const selectedTypeClient = ref('');
const usePeriode = ref(false);
const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);
const groupesParType = ref(null);
const typeClientLabel = ref(null);

const periodeVisible = computed(() => selectedMode.value === 'tous_clients' || usePeriode.value);

watch(selectedMode, () => {
  // Au changement de mode, on vide le rendu précédent pour éviter d'afficher les
  // résultats du mode précédent à côté du formulaire du nouveau mode.
  data.value = [];
  groupesParType.value = null;
  typeClientLabel.value = null;
  fetched.value = false;

  // Quand on bascule sur "tous_clients", on n'utilise plus la checkbox (période obligatoire).
  // Quand on revient sur les autres modes, on garde la checkbox dans son dernier état.
  if (selectedMode.value === 'tous_clients') return;
  if (!usePeriode.value) dateRange.value = [];
});

watch(usePeriode, (v) => {
  if (!v) dateRange.value = [];
});

const grandTotalFacture = computed(() => data.value.reduce((s, d) => s + (d.total_facture || 0), 0));
const grandTotalPaye = computed(() => data.value.reduce((s, d) => s + (d.total_paye || 0), 0));
const grandTotalRejet = computed(() => data.value.reduce((s, d) => s + (d.total_rejet || 0), 0));
const grandTotalPerte = computed(() => data.value.reduce((s, d) => s + (d.total_perte || 0), 0));
const grandTotalSolde = computed(() => data.value.reduce((s, d) => s + (d.total_solde || 0), 0));

// Mode tous_clients sans filtre type : on injecte des lignes-séparateurs (isTypeHeader)
// avant chaque changement de type. Sinon on retourne data brute.
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

const fetchData = async () => {
  if (selectedMode.value === 'un_client' && !selectedClientId.value) {
    ElMessage.warning('Veuillez sélectionner un client');
    return;
  }
  const [debut, fin] = dateRange.value || [];
  if (selectedMode.value === 'tous_clients' && (!debut || !fin)) {
    ElMessage.warning('La période est obligatoire pour le mode "Tous les clients"');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    if (selectedMode.value === 'un_client') params.append('client_id', selectedClientId.value);
    if (selectedMode.value !== 'un_client' && selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
    if (periodeVisible.value && debut) params.append('date_debut', debut);
    if (periodeVisible.value && fin) params.append('date_fin', fin);

    const res = await fetch(`/rapports/clients/api/etat-reglements?${params}`);
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
  const keys = { 3: 'total_facture', 4: 'total_paye', 5: 'total_rejet', 6: 'total_perte', 7: 'total_solde' };
  return columns.map((_, i) => {
    if (i === 0) return 'TOTAL';
    if (i === 1 || i === 2) return '';
    const key = keys[i];
    return key ? formatMontant(tableData.reduce((s, r) => s + (r[key] || 0), 0)) : '';
  });
};

const buildPdfParams = () => {
  const [debut, fin] = dateRange.value || [];
  const params = new URLSearchParams({ mode: selectedMode.value });
  if (selectedMode.value === 'un_client') params.append('client_id', selectedClientId.value);
  if (selectedMode.value !== 'un_client' && selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
  if (periodeVisible.value && debut) params.append('date_debut', debut);
  if (periodeVisible.value && fin) params.append('date_fin', fin);
  return params;
};

const exportPdf = () => {
  startExport(REPORT_KEY, 'pdf', Object.fromEntries(buildPdfParams()), 'État des règlements clients');
};

const exportExcel = () => {
  startExport(REPORT_KEY, 'excel', Object.fromEntries(buildPdfParams()), 'État des règlements clients');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/etat-reglements?${params}`, '_blank');
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
