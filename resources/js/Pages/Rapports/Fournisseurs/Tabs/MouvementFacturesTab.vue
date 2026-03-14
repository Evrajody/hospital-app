<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Fournisseur">
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

        <el-form-item>
          <el-checkbox v-model="usePeriode">Période</el-checkbox>
        </el-form-item>

        <el-form-item v-if="usePeriode" label="Date début">
          <el-date-picker v-model="dateDebut" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date début" />
        </el-form-item>

        <el-form-item v-if="usePeriode" label="Date fin">
          <el-date-picker v-model="dateFin" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" placeholder="Date fin" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="!fournisseurInfo && lignes.length === 0" class="empty-state">
        <el-empty description="Aucune donnée trouvée" />
      </div>

      <template v-if="fournisseurInfo">
        <!-- Info fournisseur -->
        <div class="fournisseur-header-box">
          <strong>{{ fournisseurInfo.code }}</strong> — {{ fournisseurInfo.nom }}
          <span v-if="periode.debut && periode.fin" class="periode-badge">
            Période : {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}
          </span>
        </div>

        <div v-if="lignes.length === 0" class="empty-state">
          <el-empty description="Aucune facture trouvée pour ce fournisseur" />
        </div>

        <el-table v-else :data="lignes" border size="small" stripe show-summary :summary-method="getSummary">
          <el-table-column prop="numero_piece" label="N°Pièce" width="90" />
          <el-table-column prop="date" label="Date PC" width="85" />
          <el-table-column prop="reference_facture" label="Réf. Fact." width="100" />
          <el-table-column label="Mt Fact." width="100" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_facture) }}</template>
          </el-table-column>
          <el-table-column label="Avoir" width="85" align="right">
            <template #default="{ row }">{{ formatMontant(row.avoir) }}</template>
          </el-table-column>
          <el-table-column label="Mt M.O." width="90" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_mo) }}</template>
          </el-table-column>
          <el-table-column label="AIB (%)" width="70" align="right">
            <template #default="{ row }">{{ row.taux_aib ? row.taux_aib.toFixed(1) + '%' : '' }}</template>
          </el-table-column>
          <el-table-column label="Mt AIB" width="85" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_aib) }}</template>
          </el-table-column>
          <el-table-column label="Mt Dû" width="100" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_du) }}</template>
          </el-table-column>
          <el-table-column label="Total Règ." width="100" align="right">
            <template #default="{ row }">{{ formatMontant(row.total_reglement) }}</template>
          </el-table-column>
          <el-table-column label="Solde" width="100" align="right">
            <template #default="{ row }">
              <span :style="{ color: row.solde > 0 ? '#cc0000' : 'inherit', fontWeight: row.solde > 0 ? 'bold' : 'normal' }">
                {{ formatMontant(row.solde) }}
              </span>
            </template>
          </el-table-column>
        </el-table>

        <!-- Actions Export -->
        <div v-if="lignes.length > 0" class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button @click="printReport">Imprimer</el-button>
        </div>
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
const usePeriode = ref(false);
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const lignes = ref([]);
const totaux = ref({});
const fournisseurInfo = ref(null);
const periode = ref({ debut: '', fin: '' });

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};

const fetchData = async () => {
  if (!selectedFournisseurId.value) {
    ElMessage.warning('Veuillez sélectionner un fournisseur');
    return;
  }
  loading.value = true;
  try {
    const params = new URLSearchParams({ fournisseur_id: selectedFournisseurId.value });
    if (usePeriode.value && dateDebut.value) params.append('date_debut', dateDebut.value);
    if (usePeriode.value && dateFin.value) params.append('date_fin', dateFin.value);

    const res = await fetch(`/rapports/fournisseurs/api/mouvement-factures?${params}`);
    const json = await res.json();
    lignes.value = json.lignes || [];
    totaux.value = json.totaux || {};
    fournisseurInfo.value = json.fournisseur || null;
    periode.value = json.periode || { debut: '', fin: '' };
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
    if (i === 1 || i === 2 || i === 6) { sums[i] = ''; return; }
    const keys = { 3: 'montant_facture', 4: 'avoir', 5: 'montant_mo', 7: 'montant_aib', 8: 'montant_du', 9: 'total_reglement', 10: 'solde' };
    if (keys[i]) {
      sums[i] = formatMontant(totaux.value[keys[i]] || 0);
    } else {
      sums[i] = '';
    }
  });
  return sums;
};

const buildPdfParams = () => {
  const params = new URLSearchParams({ fournisseur_id: selectedFournisseurId.value });
  if (usePeriode.value && dateDebut.value) params.append('date_debut', dateDebut.value);
  if (usePeriode.value && dateFin.value) params.append('date_fin', dateFin.value);
  return params;
};

const exportPdf = () => {
  window.open(`/rapports/fournisseurs/pdf/mouvement-factures?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/fournisseurs/pdf/mouvement-factures?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.fournisseur-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 12px; font-size: 13px; display: flex; justify-content: space-between; align-items: center; }
.periode-badge { font-size: 12px; color: #666; font-style: italic; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
