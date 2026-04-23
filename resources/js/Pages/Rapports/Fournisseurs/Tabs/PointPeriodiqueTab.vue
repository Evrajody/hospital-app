<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
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
      <div v-if="groupes.length === 0" class="empty-state">
        <el-empty description="Aucune pièce comptable trouvée pour cette période" />
      </div>
      <template v-else>
        <div class="report-title">{{ titre }}</div>

        <div v-for="(groupe, gi) in groupes" :key="gi" class="date-block">
          <div class="date-header-box">
            <strong>Date d'enregistrement :</strong> <em>{{ groupe.date_longue }}</em>
          </div>
          <el-table style="width: 100%" :data="groupe.lignes" border size="small" stripe>
            <el-table-column prop="numero_piece" label="N° Pièce" min-width="130" />
            <el-table-column prop="libelle" label="Objet PC" min-width="300" />
            <el-table-column label="Montant TTC" min-width="140" align="right">
              <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
            </el-table-column>
          </el-table>
          <div class="date-total">
            TOTAL : <strong>{{ formatMontant(groupe.total) }}</strong>
          </div>
        </div>

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
import { ref } from 'vue';
import { ElMessage } from 'element-plus';

const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const titre = ref('');
const groupes = ref([]);

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const fetchData = async () => {
  if (!dateDebut.value || !dateFin.value) {
    ElMessage.warning('Veuillez indiquer la période complète');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({ date_debut: dateDebut.value, date_fin: dateFin.value });
    const res = await fetch(`/rapports/fournisseurs/api/point-periodique?${params}`);
    const json = await res.json();
    titre.value = json.titre || '';
    groupes.value = json.groupes || [];
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const buildPdfParams = () => {
  return new URLSearchParams({ date_debut: dateDebut.value, date_fin: dateFin.value });
};

const exportPdf = () => {
  window.open(`/rapports/fournisseurs/pdf/point-periodique?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/fournisseurs/pdf/point-periodique?${params}`, '_blank');
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
.date-block { margin-bottom: 24px; }
.date-header-box { background: #f5f5f5; border: 1px solid #ddd; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.date-total { text-align: right; padding: 10px 14px; background: #fafafa; border: 1px solid #eee; font-size: 13px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
