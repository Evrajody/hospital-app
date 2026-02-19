<template>
  <div class="tab-content">
    <!-- Filtres -->
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
      <div v-if="data.length === 0" class="empty-state">
        <el-empty description="Aucune donnée trouvée pour cette période" />
      </div>

      <template v-if="data.length > 0">
        <!-- Brouillard de chèques -->
        <h3 class="section-title">Brouillard de Chèques</h3>
        <el-table :data="data" border size="small" stripe show-summary :summary-method="getBrouillardSummary">
          <el-table-column prop="date" label="Date" width="100" />
          <el-table-column prop="libelle" label="Libellés" />
          <el-table-column label="Débit" width="130" align="right">
            <template #default="{ row }">{{ row.debit ? formatMontant(row.debit) : '' }}</template>
          </el-table-column>
          <el-table-column label="Crédit" width="130" align="right">
            <template #default="{ row }">{{ row.credit ? formatMontant(row.credit) : '' }}</template>
          </el-table-column>
          <el-table-column label="Solde" width="130" align="right">
            <template #default="{ row }">
              <strong>{{ formatMontant(row.solde) }}</strong>
            </template>
          </el-table-column>
        </el-table>

        <!-- Imputations Comptables -->
        <h3 class="section-title" style="margin-top: 30px;">Imputations Comptables</h3>
        <el-table :data="data" border size="small" stripe show-summary :summary-method="getImputationSummary">
          <el-table-column prop="date" label="Date" width="90" />
          <el-table-column prop="reference" label="N° Pièce" width="120">
            <template #default="{ row }"><strong>{{ row.reference }}</strong></template>
          </el-table-column>
          <el-table-column label="Libellé">
            <template #default="{ row }">{{ row.client_nom }} ({{ row.facture_ref }})</template>
          </el-table-column>
          <el-table-column prop="compte_debit" label="Cpte Débit" width="100" align="center">
            <template #default="{ row }"><code>{{ row.compte_debit }}</code></template>
          </el-table-column>
          <el-table-column prop="compte_credit" label="Cpte Crédit" width="100" align="center">
            <template #default="{ row }"><code>{{ row.compte_credit }}</code></template>
          </el-table-column>
          <el-table-column label="Montant" width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
        </el-table>

        <!-- Actions -->
        <div class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button @click="printReport">Imprimer</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';

const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);

const totalDebit = computed(() => data.value.reduce((s, e) => s + (e.debit || 0), 0));
const totalCredit = computed(() => data.value.reduce((s, e) => s + (e.credit || 0), 0));
const soldeGeneral = computed(() => data.value.length > 0 ? data.value[data.value.length - 1].solde : 0);
const totalMontant = computed(() => data.value.reduce((s, e) => s + (e.montant || 0), 0));

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (!dateDebut.value || !dateFin.value) {
    ElMessage.warning('Veuillez indiquer la période (date début et date fin)');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ date_debut: dateDebut.value, date_fin: dateFin.value });
    const res = await fetch(`/rapports/clients/api/brouillard-cheques?${params}`);
    const json = await res.json();
    data.value = json.data || [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getBrouillardSummary = ({ columns }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAUX'; return; }
    if (i === 1) { sums[i] = ''; return; }
    if (i === 2) { sums[i] = formatMontant(totalDebit.value); return; }
    if (i === 3) { sums[i] = formatMontant(totalCredit.value); return; }
    if (i === 4) { sums[i] = formatMontant(soldeGeneral.value); return; }
    sums[i] = '';
  });
  return sums;
};

const getImputationSummary = ({ columns }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    if (i === 5) { sums[i] = formatMontant(totalMontant.value); return; }
    sums[i] = '';
  });
  return sums;
};

const buildPdfParams = () => new URLSearchParams({ date_debut: dateDebut.value, date_fin: dateFin.value });

const exportPdf = () => {
  window.open(`/rapports/clients/pdf/brouillard-cheques?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/brouillard-cheques?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.section-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 8px; margin-bottom: 12px; border-bottom: 2px solid #333; color: #333; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
code { font-family: 'Courier New', monospace; font-size: 12px; background: #f5f5f5; padding: 2px 4px; }
</style>
