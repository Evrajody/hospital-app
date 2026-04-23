<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Mode">
          <el-radio-group v-model="mode" size="default">
            <el-radio-button value="toutes">Toutes les charges</el-radio-button>
            <el-radio-button value="par_compte">Par charge</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="mode === 'par_compte'" label="Compte">
          <el-select
            v-model="compteId"
            filterable
            clearable
            placeholder="Sélectionner un compte"
            style="width: 320px"
          >
            <el-option
              v-for="c in comptesCharges"
              :key="c.id"
              :label="`${c.numero_compte} - ${c.libelle}`"
              :value="c.id"
            />
          </el-select>
        </el-form-item>
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
        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats -->
    <div v-if="fetched" class="results-section">
      <div v-if="lignes.length === 0" class="empty-state">
        <el-empty description="Aucune dépense trouvée pour cette période" />
      </div>
      <template v-else>
        <div class="report-title">{{ titre }}</div>

        <el-table style="width: 100%" :data="lignes" border size="small" stripe show-summary :summary-method="getSummary">
          <el-table-column prop="numero_compte" label="N° Compte" min-width="120" />
          <el-table-column prop="libelle" label="Intitulé" min-width="350" />
          <el-table-column label="Montant TTC" min-width="150" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
        </el-table>

        <!-- Actions Export -->
        <div class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button @click="printReport">Imprimer</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { useMontant } from '@/Composables/useMontant';

const { formatMontant } = useMontant();

const mode = ref('toutes');
const compteId = ref(null);
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const titre = ref('');
const lignes = ref([]);
const totalDepenses = ref(0);
const comptesCharges = ref([]);

const loadComptes = async () => {
  try {
    const res = await fetch('/rapports/fournisseurs/api/recap-charges?load_comptes=1');
    const json = await res.json();
    if (json.comptesDisponibles) comptesCharges.value = json.comptesDisponibles;
  } catch (e) { /* silencieux */ }
};

onMounted(() => loadComptes());

const fetchData = async () => {
  if (!dateDebut.value || !dateFin.value) {
    ElMessage.warning('Veuillez indiquer la période complète');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({
      date_debut: dateDebut.value,
      date_fin: dateFin.value,
      mode: mode.value,
    });
    if (mode.value === 'par_compte' && compteId.value) {
      params.append('compte_id', compteId.value);
    }
    const res = await fetch(`/rapports/fournisseurs/api/recap-charges?${params}`);
    const json = await res.json();
    titre.value = json.titre || '';
    lignes.value = json.lignes || [];
    totalDepenses.value = json.totalDepenses || 0;
    if (json.comptesDisponibles) comptesCharges.value = json.comptesDisponibles;
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummary = ({ columns }) => {
  return columns.map((col, index) => {
    if (index === 0) return '';
    if (index === 1) return 'TOTAL DEPENSES :';
    if (index === 2) return formatMontant(totalDepenses.value);
    return '';
  });
};

const buildPdfParams = () => {
  const params = new URLSearchParams({
    date_debut: dateDebut.value,
    date_fin: dateFin.value,
    mode: mode.value,
  });
  if (mode.value === 'par_compte' && compteId.value) {
    params.append('compte_id', compteId.value);
  }
  return params;
};

const exportPdf = () => {
  window.open(`/rapports/fournisseurs/pdf/recap-charges?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/fournisseurs/pdf/recap-charges?${params}`, '_blank');
  if (w) w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.report-title { font-size: 16px; font-weight: 600; text-align: center; margin-bottom: 20px; text-decoration: underline; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
