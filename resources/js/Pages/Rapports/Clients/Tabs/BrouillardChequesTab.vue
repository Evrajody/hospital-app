<template>
  <div class="tab-content">
    <!-- Filtres (partagés entre les 2 sous-onglets) -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Date début">
          <el-date-picker v-model="dateDebut" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date début" />
        </el-form-item>

        <el-form-item label="Date fin">
          <el-date-picker v-model="dateFin" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date fin" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <el-tabs v-model="activeSubTab" class="sub-tabs">
        <!-- Sous-onglet : Brouillard de chèques -->
        <el-tab-pane label="Brouillard de chèques" name="brouillard">
          <div v-if="brouillard.length === 0" class="empty-state">
            <el-empty description="Aucune donnée trouvée pour cette période" />
          </div>

          <template v-if="brouillard.length > 0">
            <el-table style="width: 100%" :data="brouillard" border size="small" :span-method="brouillardSpanMethod">
              <el-table-column label="Date" min-width="100">
                <template #default="{ row }">
                  <strong>{{ row.date }}</strong>
                </template>
              </el-table-column>
              <el-table-column prop="libelle" label="Libellés" />
              <el-table-column label="Débit" min-width="130" align="right">
                <template #default="{ row }">{{ row.debit ? formatMontant(row.debit) : '' }}</template>
              </el-table-column>
              <el-table-column label="Crédit" min-width="130" align="right">
                <template #default="{ row }">{{ row.credit ? formatMontant(row.credit) : '' }}</template>
              </el-table-column>
              <el-table-column label="Solde" min-width="130" align="right">
                <template #default="{ row }">
                  <strong>{{ formatMontant(row.solde) }}</strong>
                </template>
              </el-table-column>
            </el-table>

            <div class="actions-bar">
              <el-button type="primary" @click="exportBrouillardPdf">Exporter PDF</el-button>
              <el-button type="success" @click="exportBrouillardExcel">Exporter Excel</el-button>
              <el-button @click="printBrouillard">Imprimer</el-button>
            </div>
          </template>
        </el-tab-pane>

        <!-- Sous-onglet : Imputations comptables -->
        <el-tab-pane label="Imputations comptables" name="imputations">
          <div v-if="imputations.length === 0" class="empty-state">
            <el-empty description="Aucune imputation trouvée pour cette période" />
          </div>

          <template v-if="imputations.length > 0">
            <div v-for="groupe in imputations" :key="groupe.reference_bordereau + groupe.date_raw" class="imputation-block">
              <div class="imputation-date">Date d'imputation : <strong>{{ groupe.date }}</strong></div>
              <el-table :data="groupe.lignes" border size="small" style="width: 100%">
                <el-table-column label="Compte" prop="compte" width="140" align="center">
                  <template #default="{ row }"><code>{{ row.compte }}</code></template>
                </el-table-column>
                <el-table-column label="Débit" width="160" align="right">
                  <template #default="{ row }">{{ row.debit ? formatMontant(row.debit) : '' }}</template>
                </el-table-column>
                <el-table-column label="Crédit" width="160" align="right">
                  <template #default="{ row }">{{ row.credit ? formatMontant(row.credit) : '' }}</template>
                </el-table-column>
                <el-table-column label="Libellé" prop="libelle" />
              </el-table>
            </div>

            <div class="actions-bar">
              <el-button type="primary" @click="exportImputationsPdf">Exporter PDF</el-button>
              <el-button type="success" @click="exportImputationsExcel">Exporter Excel</el-button>
              <el-button @click="printImputations">Imprimer</el-button>
            </div>
          </template>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';

const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const activeSubTab = ref('brouillard');

const brouillard = ref([]);
const imputations = ref([]);

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const brouillardSpanMethod = ({ rowIndex, columnIndex }) => {
  if (columnIndex !== 0) return { rowspan: 1, colspan: 1 };
  const rows = brouillard.value;
  if (rowIndex > 0 && rows[rowIndex - 1].date === rows[rowIndex].date) {
    return { rowspan: 0, colspan: 0 };
  }
  let count = 1;
  while (rowIndex + count < rows.length && rows[rowIndex + count].date === rows[rowIndex].date) {
    count++;
  }
  return { rowspan: count, colspan: 1 };
};

const fetchData = async () => {
  if (!dateDebut.value || !dateFin.value) {
    ElMessage.warning('Veuillez indiquer la période (date début et date fin)');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ date_debut: dateDebut.value, date_fin: dateFin.value });
    const [resBrouillard, resImputations] = await Promise.all([
      fetch(`/rapports/clients/api/brouillard-cheques?${params}`),
      fetch(`/rapports/clients/api/imputations-comptables?${params}`),
    ]);
    const jsonBrouillard = await resBrouillard.json();
    const jsonImputations = await resImputations.json();
    brouillard.value = jsonBrouillard.data || [];
    imputations.value = jsonImputations.groupes || [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const buildParams = () => new URLSearchParams({ date_debut: dateDebut.value, date_fin: dateFin.value });

const exportBrouillardPdf = () => {
  window.open(`/rapports/clients/pdf/brouillard-cheques?${buildParams()}`, '_blank');
};
const exportBrouillardExcel = () => {
  window.open(`/rapports/clients/excel/brouillard-cheques?${buildParams()}`, '_blank');
};
const printBrouillard = () => {
  const p = buildParams();
  p.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/brouillard-cheques?${p}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};

const exportImputationsPdf = () => {
  window.open(`/rapports/clients/pdf/imputations-comptables?${buildParams()}`, '_blank');
};
const exportImputationsExcel = () => {
  window.open(`/rapports/clients/excel/imputations-comptables?${buildParams()}`, '_blank');
};
const printImputations = () => {
  const p = buildParams();
  p.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/imputations-comptables?${p}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.sub-tabs { padding: 0 4px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }

.imputation-block { margin-bottom: 24px; }
.imputation-date { font-size: 14px; margin-bottom: 8px; padding-bottom: 4px; }
.imputation-date strong { text-decoration: underline; }

code { font-family: 'Courier New', monospace; font-size: 12px; background: #f5f5f5; padding: 2px 4px; }
</style>
