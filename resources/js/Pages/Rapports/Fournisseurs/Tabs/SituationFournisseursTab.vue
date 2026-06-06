<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Mode">
          <el-radio-group v-model="selectedMode" size="default">
            <el-radio-button label="tous">Tous les fournisseurs</el-radio-button>
            <el-radio-button label="par_compte">Par compte</el-radio-button>
            <el-radio-button label="par_fournisseur">Par fournisseur</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="selectedMode === 'par_compte'" label="Compte">
          <el-select
            v-model="selectedCompteId"
            placeholder="Sélectionner un compte"
            filterable
            clearable
            style="width: 300px"
          >
            <el-option
              v-for="c in comptes"
              :key="c.id"
              :label="`${c.numero_compte} ${c.libelle}`"
              :value="c.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item v-if="selectedMode === 'par_fournisseur'" label="Fournisseur">
          <el-select
            v-model="selectedFournisseurId"
            placeholder="Sélectionner un fournisseur"
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
            <el-radio-button label="point">Point au</el-radio-button>
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

        <el-form-item v-else label="Point au">
          <el-date-picker
            v-model="datePoint"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date du point"
            style="width: 200px"
            clearable
          />
          <el-tooltip
            content="Inclut toutes les factures émises et tous les règlements effectués jusqu'à cette date"
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

      <!-- Mode TOUS -->
      <template v-if="selectedMode === 'tous'">
        <div v-if="data.length === 0" class="empty-state">
          <el-empty description="Aucune dette fournisseur trouvée" />
        </div>
        <template v-else>
          <PaginatedTable style="width: 100%" :data="data" border size="small" stripe show-summary :summary-method="getSummaryTous">
            <el-table-column prop="numero" label="N°" min-width="50" align="center" />
            <el-table-column prop="raison_sociale" label="Raison sociale" />
            <el-table-column label="Montant total dû" min-width="150" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant_du) }}</template>
            </el-table-column>
            <el-table-column label="Montant total règlements" min-width="170" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant_reglements) }}</template>
            </el-table-column>
            <el-table-column label="Restant dû" min-width="140" align="right">
              <template #default="{ row }">
                <span style="color: #cc0000; font-weight: bold;">{{ formatMontant(row.restant_du) }}</span>
              </template>
            </el-table-column>
          </PaginatedTable>
        </template>
      </template>

      <!-- Mode PAR COMPTE -->
      <template v-if="selectedMode === 'par_compte'">
        <div v-if="data.length === 0" class="empty-state">
          <el-empty description="Aucune dette fournisseur trouvée pour ce compte" />
        </div>
        <template v-else>
          <div v-if="compteInfo" class="compte-header-box">
            <strong>Compte :</strong> <em>{{ compteInfo.numero_compte }} {{ compteInfo.libelle }}</em>
          </div>

          <PaginatedTable style="width: 100%" :data="data" border size="small" stripe show-summary :summary-method="getSummaryTous">
            <el-table-column prop="numero" label="N°" min-width="50" align="center" />
            <el-table-column label="Raison sociale">
              <template #default="{ row }">[{{ row.numero_compte }}] {{ row.raison_sociale }}</template>
            </el-table-column>
            <el-table-column label="Montant total dû" min-width="150" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant_du) }}</template>
            </el-table-column>
            <el-table-column label="Montant total règlements" min-width="170" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant_reglements) }}</template>
            </el-table-column>
            <el-table-column label="Restant dû" min-width="140" align="right">
              <template #default="{ row }">
                <span style="color: #cc0000; font-weight: bold;">{{ formatMontant(row.restant_du) }}</span>
              </template>
            </el-table-column>
          </PaginatedTable>
        </template>
      </template>

      <!-- Mode PAR FOURNISSEUR -->
      <template v-if="selectedMode === 'par_fournisseur'">
        <div v-if="data.length === 0" class="empty-state">
          <el-empty description="Aucune facture non réglée trouvée" />
        </div>
        <template v-else>
          <GroupNavigator :groups="data">
            <template #label="{ group }">
              <strong>Fournisseur :</strong> {{ group.fournisseur }}
            </template>
            <template #default="{ group }">
            <PaginatedTable style="width: 100%" :data="group.lignes" border size="small" stripe>
              <el-table-column prop="numero_piece" label="N°Pièce" min-width="90" />
              <el-table-column prop="date" label="Date PC" min-width="85" />
              <el-table-column prop="reference_facture" label="Réf. Fact." min-width="100" />
              <el-table-column label="Mt TTC" min-width="95" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_facture) }}</template>
              </el-table-column>
              <el-table-column label="Avoir" min-width="80" align="right">
                <template #default="{ row }">{{ formatMontant(row.avoir) }}</template>
              </el-table-column>
              <el-table-column label="Mt M.O." min-width="85" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_mo) }}</template>
              </el-table-column>
              <el-table-column label="AIB (%)" min-width="65" align="right">
                <template #default="{ row }">{{ row.taux_aib ? row.taux_aib.toFixed(1) + '%' : '0%' }}</template>
              </el-table-column>
              <el-table-column label="Mt AIB" min-width="85" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_aib) }}</template>
              </el-table-column>
              <el-table-column label="Mt Dû" min-width="95" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_du) }}</template>
              </el-table-column>
              <el-table-column label="Total Règ." min-width="95" align="right">
                <template #default="{ row }">{{ formatMontant(row.total_reglement) }}</template>
              </el-table-column>
              <el-table-column label="Solde" min-width="95" align="right">
                <template #default="{ row }">
                  <span style="color: #cc0000; font-weight: bold;">{{ formatMontant(row.solde) }}</span>
                </template>
              </el-table-column>
            </PaginatedTable>
            <div class="fournisseur-totals">
              Total : Mt TTC <strong>{{ formatMontant(group.totaux.montant_facture) }}</strong>
              &nbsp;|&nbsp; Mt Dû <strong>{{ formatMontant(group.totaux.montant_du) }}</strong>
              &nbsp;|&nbsp; Règ. <strong>{{ formatMontant(group.totaux.total_reglement) }}</strong>
              &nbsp;|&nbsp; <span style="color: #cc0000">Solde <strong>{{ formatMontant(group.totaux.solde) }}</strong></span>
            </div>
            </template>
          </GroupNavigator>

          <div class="grand-total">
            <span>TOTAL GÉNÉRAL — Mt TTC : <strong>{{ formatMontant(grandTotaux.montant_facture) }}</strong></span>
            <span>Mt Dû : <strong>{{ formatMontant(grandTotaux.montant_du) }}</strong></span>
            <span>Règ. : <strong>{{ formatMontant(grandTotaux.total_reglement) }}</strong></span>
            <span style="color: #cc0000">Solde : <strong>{{ formatMontant(grandTotaux.solde) }}</strong></span>
          </div>
        </template>
      </template>

      <!-- Actions Export -->
      <div v-if="hasData" class="actions-bar">
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
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { InfoFilled } from '@element-plus/icons-vue';
import PaginatedTable from '@/Components/PaginatedTable.vue';
import GroupNavigator from '@/Components/GroupNavigator.vue';

const props = defineProps({
  fournisseurs: { type: Array, default: () => [] },
  comptes: { type: Array, default: () => [] },
});

const selectedMode = ref('tous');
const selectedCompteId = ref(null);
const selectedFournisseurId = ref(null);
const filtreDate = ref('periode'); // 'periode' | 'point'
const dateRange = ref([]);
const datePoint = ref('');
const loading = ref(false);

// Quand l'utilisateur bascule entre Période et Point au, on vide les autres champs
const onFiltreDateChange = () => {
  if (filtreDate.value === 'periode') {
    datePoint.value = '';
  } else {
    dateRange.value = [];
  }
};
const fetched = ref(false);
const data = ref([]);
const grandTotal = ref({});
const grandTotaux = ref({});
const compteInfo = ref(null);

const hasData = computed(() => data.value.length > 0);

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (selectedMode.value === 'par_compte' && !selectedCompteId.value) {
    ElMessage.warning('Veuillez sélectionner un compte');
    return;
  }
  if (selectedMode.value === 'par_fournisseur' && !selectedFournisseurId.value) {
    ElMessage.warning('Veuillez sélectionner un fournisseur');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    const [debut, fin] = dateRange.value || [];
    if (filtreDate.value === 'point' && datePoint.value) {
      params.append('date_point', datePoint.value);
    } else if (filtreDate.value === 'periode') {
      if (debut) params.append('date_debut', debut);
      if (fin) params.append('date_fin', fin);
    }
    if (selectedMode.value === 'par_compte' && selectedCompteId.value) {
      params.append('compte_id', selectedCompteId.value);
    }
    if (selectedMode.value === 'par_fournisseur' && selectedFournisseurId.value) {
      params.append('fournisseur_id', selectedFournisseurId.value);
    }

    const res = await fetch(`/rapports/fournisseurs/api/situation-fournisseurs?${params}`);
    const json = await res.json();
    data.value = json.data || [];
    grandTotal.value = json.grandTotal || {};
    grandTotaux.value = json.grandTotaux || {};
    compteInfo.value = json.compte || null;
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummaryTous = ({ columns, data: tableData }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    if (i === 1) { sums[i] = ''; return; }
    const key = { 2: 'montant_du', 3: 'montant_reglements', 4: 'restant_du' }[i];
    sums[i] = key ? formatMontant(tableData.reduce((s, r) => s + (r[key] || 0), 0)) : '';
  });
  return sums;
};

const buildPdfParams = () => {
  const params = new URLSearchParams({ mode: selectedMode.value });
  const [debut, fin] = dateRange.value || [];
  if (filtreDate.value === 'point' && datePoint.value) {
    params.append('date_point', datePoint.value);
  } else if (filtreDate.value === 'periode') {
    if (debut) params.append('date_debut', debut);
    if (fin) params.append('date_fin', fin);
  }
  if (selectedMode.value === 'par_compte' && selectedCompteId.value) {
    params.append('compte_id', selectedCompteId.value);
  }
  if (selectedMode.value === 'par_fournisseur' && selectedFournisseurId.value) {
    params.append('fournisseur_id', selectedFournisseurId.value);
  }
  return params;
};

const exportPdf = () => {
  openPdf(`/rapports/fournisseurs/pdf/situation-fournisseurs?${buildPdfParams()}`, 'Aperçu du rapport');
};

const exportExcel = () => {
  window.open(`/rapports/fournisseurs/excel/situation-fournisseurs?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/fournisseurs/pdf/situation-fournisseurs?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.compte-block { margin-bottom: 24px; }
.compte-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.sous-total { display: flex; gap: 20px; padding: 10px 14px; background: #fafafa; border: 1px solid #eee; font-size: 13px; }
.fournisseur-block { margin-bottom: 24px; }
.fournisseur-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.fournisseur-totals { display: flex; gap: 16px; flex-wrap: wrap; padding: 10px 14px; background: #fafafa; border: 1px solid #eee; font-size: 12px; }
.grand-total { display: flex; gap: 24px; flex-wrap: wrap; padding: 14px; background: #f0f0f0; border: 2px solid #333; font-size: 14px; margin-top: 16px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
