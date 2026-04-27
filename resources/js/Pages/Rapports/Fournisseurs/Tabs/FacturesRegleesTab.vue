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

        <el-form-item label="Mode">
          <el-radio-group v-model="selectedMode" size="default">
            <el-radio-button label="date">Date</el-radio-button>
            <el-radio-button label="periode">Période</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="selectedMode === 'date'" label="Date">
          <el-date-picker
            v-model="dateRef"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Sélectionner une date"
          />
        </el-form-item>

        <template v-if="selectedMode === 'periode'">
          <el-form-item label="Date début">
            <el-date-picker
              v-model="dateDebut"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Date début"
            />
          </el-form-item>
          <el-form-item label="Date fin">
            <el-date-picker
              v-model="dateFin"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Date fin"
            />
          </el-form-item>
        </template>

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats avec 2 sous-onglets -->
    <div v-if="fetched" class="results-section">
      <div v-if="resume.length === 0 && detail.length === 0" class="empty-state">
        <el-empty description="Aucune facture réglée trouvée pour cette période" />
      </div>
      <template v-else>
        <el-tabs v-model="activeSubTab" type="card" class="sub-tabs">
          <!-- Sous-onglet RÉSUMÉ -->
          <el-tab-pane name="resume">
            <template #label>
              <span class="sub-tab-label">Résumé par fournisseur</span>
            </template>

            <el-table style="width: 100%" :data="resume" border size="small" stripe show-summary :summary-method="getSummaryResume">
              <el-table-column prop="fournisseur" label="Fournisseur" min-width="200" />
              <el-table-column label="Total Mt Fact." min-width="130" align="right">
                <template #default="{ row }">{{ formatMontant(row.total_montant_facture) }}</template>
              </el-table-column>
              <el-table-column label="Total Avoir" min-width="110" align="right">
                <template #default="{ row }">{{ formatMontant(row.total_avoir) }}</template>
              </el-table-column>
              <el-table-column label="Total Mt M.O." min-width="120" align="right">
                <template #default="{ row }">{{ formatMontant(row.total_montant_mo) }}</template>
              </el-table-column>
              <el-table-column label="Total AIB" min-width="110" align="right">
                <template #default="{ row }">{{ formatMontant(row.total_aib) }}</template>
              </el-table-column>
              <el-table-column label="Total Rég. Période" min-width="140" align="right">
                <template #default="{ row }">{{ formatMontant(row.total_reg_periode) }}</template>
              </el-table-column>
              <el-table-column label="Total Mt Rég." min-width="130" align="right">
                <template #default="{ row }">
                  <span style="font-weight: bold;">{{ formatMontant(row.total_mt_reg) }}</span>
                </template>
              </el-table-column>
            </el-table>

            <div class="actions-bar">
              <el-button type="primary" @click="exportPdf('resume')">Exporter PDF</el-button>
              <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
              <el-button @click="printReport('resume')">Imprimer</el-button>
            </div>
          </el-tab-pane>

          <!-- Sous-onglet DÉTAIL -->
          <el-tab-pane name="detail">
            <template #label>
              <span class="sub-tab-label">Détail par fournisseur</span>
            </template>

            <div v-for="(fData, fi) in detail" :key="fi" class="fournisseur-block">
              <div class="fournisseur-header-box">
                <strong>Fournisseur :</strong> {{ fData.fournisseur }}
              </div>
              <el-table style="width: 100%" :data="fData.lignes" border size="small" stripe>
                <el-table-column prop="numero_piece" label="N°PC" min-width="90" />
                <el-table-column prop="date" label="Date PC" min-width="85" />
                <el-table-column prop="date_reglement" label="Date Règ." min-width="85" />
                <el-table-column label="Mt Fact." min-width="95" align="right">
                  <template #default="{ row }">{{ formatMontant(row.montant_facture) }}</template>
                </el-table-column>
                <el-table-column label="Avoir" min-width="80" align="right">
                  <template #default="{ row }">{{ formatMontant(row.avoir) }}</template>
                </el-table-column>
                <el-table-column label="Mt M.O." min-width="85" align="right">
                  <template #default="{ row }">{{ formatMontant(row.montant_mo) }}</template>
                </el-table-column>
                <el-table-column label="AIB" min-width="65" align="right">
                  <template #default="{ row }">{{ row.taux_aib ? row.taux_aib.toFixed(1) + '%' : '0%' }}</template>
                </el-table-column>
                <el-table-column label="Mt AIB" min-width="85" align="right">
                  <template #default="{ row }">{{ formatMontant(row.montant_aib) }}</template>
                </el-table-column>
                <el-table-column label="Rég. Période" min-width="110" align="right">
                  <template #default="{ row }">{{ formatMontant(row.reg_periode) }}</template>
                </el-table-column>
                <el-table-column label="Mt Total Rég." min-width="110" align="right">
                  <template #default="{ row }">
                    <span style="font-weight: bold;">{{ formatMontant(row.mt_total_reg) }}</span>
                  </template>
                </el-table-column>
              </el-table>
              <div class="fournisseur-totals">
                <span><strong>Total Fournisseur :</strong></span>
                <span>Mt TTC <strong>{{ formatMontant(fData.totaux.montant_facture) }}</strong></span>
                <span>Avoir <strong>{{ formatMontant(fData.totaux.avoir) }}</strong></span>
                <span>Mt M.O. <strong>{{ formatMontant(fData.totaux.montant_mo) }}</strong></span>
                <span>AIB <strong>{{ formatMontant(fData.totaux.montant_aib) }}</strong></span>
                <span>Rég. Période <strong>{{ formatMontant(fData.totaux.reg_periode) }}</strong></span>
                <span>Mt Total Rég. <strong>{{ formatMontant(fData.totaux.mt_total_reg) }}</strong></span>
              </div>
            </div>

            <!-- Grand Total -->
            <div class="grand-total">
              <span>TOTAL GÉNÉRAL</span>
              <span>Mt TTC : <strong>{{ formatMontant(grandTotaux.montant_facture) }}</strong></span>
              <span>Avoir : <strong>{{ formatMontant(grandTotaux.avoir) }}</strong></span>
              <span>Mt M.O. : <strong>{{ formatMontant(grandTotaux.montant_mo) }}</strong></span>
              <span>AIB : <strong>{{ formatMontant(grandTotaux.montant_aib) }}</strong></span>
              <span>Rég. Période : <strong>{{ formatMontant(grandTotaux.reg_periode) }}</strong></span>
              <span style="font-weight: bold;">Mt Total Rég. : {{ formatMontant(grandTotaux.mt_total_reg) }}</span>
            </div>

            <div class="actions-bar">
              <el-button type="primary" @click="exportPdf('detail')">Exporter PDF</el-button>
              <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
              <el-button @click="printReport('detail')">Imprimer</el-button>
            </div>
          </el-tab-pane>
        </el-tabs>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  fournisseurs: { type: Array, default: () => [] },
});

const selectedFournisseurId = ref(null);
const selectedMode = ref('date');
const dateRef = ref('');
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const activeSubTab = ref('resume');
const titre = ref('');
const resume = ref([]);
const detail = ref([]);
const grandTotaux = ref({});

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (selectedMode.value === 'date' && !dateRef.value) {
    ElMessage.warning('Veuillez sélectionner une date');
    return;
  }
  if (selectedMode.value === 'periode' && (!dateDebut.value || !dateFin.value)) {
    ElMessage.warning('Veuillez sélectionner la période complète');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    if (selectedMode.value === 'date') {
      params.append('date', dateRef.value);
    } else {
      params.append('date_debut', dateDebut.value);
      params.append('date_fin', dateFin.value);
    }
    if (selectedFournisseurId.value) {
      params.append('fournisseur_id', selectedFournisseurId.value);
    }

    const res = await fetch(`/rapports/fournisseurs/api/factures-reglees?${params}`);
    const json = await res.json();
    titre.value = json.titre || '';
    resume.value = json.resume || [];
    detail.value = json.detail || [];
    grandTotaux.value = json.grandTotaux || {};
    fetched.value = true;
    activeSubTab.value = 'resume';
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummaryResume = ({ columns, data: tableData }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    const keyMap = {
      1: 'total_montant_facture', 2: 'total_avoir', 3: 'total_montant_mo',
      4: 'total_aib', 5: 'total_reg_periode', 6: 'total_mt_reg'
    };
    const key = keyMap[i];
    sums[i] = key ? formatMontant(tableData.reduce((s, r) => s + (r[key] || 0), 0)) : '';
  });
  return sums;
};

const buildPdfParams = (type) => {
  const params = new URLSearchParams({ mode: selectedMode.value, type });
  if (selectedMode.value === 'date') {
    params.append('date', dateRef.value);
  } else {
    params.append('date_debut', dateDebut.value);
    params.append('date_fin', dateFin.value);
  }
  if (selectedFournisseurId.value) {
    params.append('fournisseur_id', selectedFournisseurId.value);
  }
  return params;
};

const exportPdf = (type) => {
  window.open(`/rapports/fournisseurs/pdf/factures-reglees?${buildPdfParams(type)}`, '_blank');
};

const exportExcel = () => {
  window.open(`/rapports/fournisseurs/excel/factures-reglees?${buildPdfParams('detail')}`, '_blank');
};

const printReport = (type) => {
  const params = buildPdfParams(type);
  params.append('action', 'stream');
  const w = window.open(`/rapports/fournisseurs/pdf/factures-reglees?${params}`, '_blank');
  if (w) w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.sub-tabs { margin-top: 4px; }
.sub-tab-label { font-size: 13px; font-weight: 500; }
.fournisseur-block { margin-bottom: 24px; }
.fournisseur-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.fournisseur-totals { display: flex; gap: 16px; flex-wrap: wrap; padding: 10px 14px; background: #fafafa; border: 1px solid #eee; font-size: 12px; }
.grand-total { display: flex; gap: 24px; flex-wrap: wrap; padding: 14px; background: #f0f0f0; border: 2px solid #333; font-size: 14px; margin-top: 16px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
