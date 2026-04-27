<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Mode">
          <el-radio-group v-model="mode" size="default">
            <el-radio-button value="toutes">Toutes les banques</el-radio-button>
            <el-radio-button value="par_banque">Par banque</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="mode === 'par_banque'" label="Compte">
          <el-select v-model="banqueId" placeholder="(optionnelle)" clearable filterable style="width: 280px;">
            <el-option
              v-for="b in banques"
              :key="b.id"
              :label="b.nom"
              :value="b.id"
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
      <!-- Mode Toutes les banques = résumé -->
      <template v-if="resultMode === 'toutes'">
        <div v-if="lignes.length === 0" class="empty-state">
          <el-empty description="Aucune donnée trouvée pour cette période" />
        </div>
        <template v-else>
          <div class="report-title">{{ titre }}</div>

          <el-table style="width: 100%" :data="lignes" border size="small" stripe show-summary :summary-method="getSummaryResume">
            <el-table-column prop="numero_compte" label="N° Compte" min-width="130" />
            <el-table-column prop="intitule" label="Intitulé" min-width="250" />
            <el-table-column label="Total Débit" min-width="170" align="right">
              <template #default="{ row }">{{ fmt(row.total_debit) }}</template>
            </el-table-column>
            <el-table-column label="Total Crédit" min-width="170" align="right">
              <template #default="{ row }">{{ fmt(row.total_credit) }}</template>
            </el-table-column>
            <el-table-column label="Solde" min-width="170" align="right">
              <template #default="{ row }">
                <span :class="{ 'negatif': row.solde < 0 }">{{ fmt(row.solde) }}</span>
              </template>
            </el-table-column>
          </el-table>
        </template>
      </template>

      <!-- Mode Par banque = détail par banque -->
      <template v-else>
        <div v-if="sections.length === 0" class="empty-state">
          <el-empty description="Aucune donnée trouvée pour cette période" />
        </div>
        <template v-else>
          <div class="report-title">{{ titre }}</div>

          <div v-for="(section, si) in sections" :key="si" class="bank-section">
            <div class="bank-header">
              <strong>Banque :</strong> <em>{{ section.numero_compte }} {{ section.intitule }}</em>
            </div>

            <el-table style="width: 100%" :data="section.displayRows" border size="small" :show-header="true" class="detail-table">
              <el-table-column label="DATE" min-width="110">
                <template #default="{ row }">
                  <span :class="{ 'row-special': row._special }">{{ row.date_fmt }}</span>
                </template>
              </el-table-column>
              <el-table-column label="LIBELLES" min-width="350">
                <template #default="{ row }">
                  <span :class="{ 'row-special': row._special }">{{ row.libelle }}</span>
                </template>
              </el-table-column>
              <el-table-column label="DEBIT" min-width="140" align="right">
                <template #default="{ row }">
                  <span :class="{ 'row-special': row._special }">{{ row.debit !== null ? fmt(row.debit) : '' }}</span>
                </template>
              </el-table-column>
              <el-table-column label="CREDIT" min-width="140" align="right">
                <template #default="{ row }">
                  <span :class="{ 'row-special': row._special }">{{ row.credit !== null ? fmt(row.credit) : '' }}</span>
                </template>
              </el-table-column>
              <el-table-column label="SOLDE" min-width="160" align="right">
                <template #default="{ row }">
                  <span :class="{ 'negatif': row.solde < 0, 'row-special': row._special }">{{ fmt(row.solde) }}</span>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </template>
      </template>

      <!-- Actions Export -->
      <div v-if="(resultMode === 'toutes' && lignes.length > 0) || (resultMode === 'par_banque' && sections.length > 0)" class="actions-bar">
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
  banques: { type: Array, default: () => [] },
});

const mode = ref('toutes');
const banqueId = ref('');
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const titre = ref('');
const resultMode = ref('toutes');
const dateAvant = ref('');

// Résumé (toutes)
const lignes = ref([]);
const totalDebit = ref(0);
const totalCredit = ref(0);
const totalSolde = ref(0);

// Détail (par_banque)
const sectionsRaw = ref([]);

const fmt = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

// Build display rows for each section (initial balance + transactions + total)
const sections = computed(() => {
  return sectionsRaw.value.map(s => {
    const rows = [];

    // First row: SOLDE AU {date}
    rows.push({
      _special: true,
      date_fmt: '',
      libelle: `SOLDE AU  ${dateAvant.value}`,
      debit: null,
      credit: null,
      solde: s.solde_initial,
    });

    // Transaction rows
    for (const tx of s.transactions) {
      rows.push({
        _special: false,
        date_fmt: tx.date_fmt,
        libelle: tx.libelle,
        debit: tx.debit,
        credit: tx.credit,
        solde: tx.solde,
      });
    }

    // Last row: SOLDE DE LA PERIODE
    rows.push({
      _special: true,
      date_fmt: '',
      libelle: 'SOLDE DE LA PERIODE',
      debit: s.total_debit,
      credit: s.total_credit,
      solde: s.solde_initial + s.solde_periode,
    });

    return {
      ...s,
      displayRows: rows,
    };
  });
});

const getSummaryResume = ({ columns }) => {
  return columns.map((col, i) => {
    if (i === 0) return 'TOTAL SOLDE';
    if (i === 1) return '';
    if (i === 2) return fmt(totalDebit.value);
    if (i === 3) return fmt(totalCredit.value);
    if (i === 4) return fmt(totalSolde.value);
    return '';
  });
};

const fetchData = async () => {
  if (!dateDebut.value || !dateFin.value) {
    ElMessage.warning('Veuillez indiquer la période complète');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({
      mode: mode.value,
      date_debut: dateDebut.value,
      date_fin: dateFin.value,
    });
    if (mode.value === 'par_banque' && banqueId.value) {
      params.append('banque_id', banqueId.value);
    }
    const basePath = window.location.pathname.includes('/rapports/banques') ? '/rapports/banques' : '/rapports/fournisseurs';
    const res = await fetch(`${basePath}/api/situation-banques?${params}`);
    const json = await res.json();

    titre.value = json.titre || '';
    resultMode.value = json.mode || 'toutes';
    dateAvant.value = json.dateAvant || '';

    // Résumé
    lignes.value = json.lignes || [];
    totalDebit.value = json.totalDebit || 0;
    totalCredit.value = json.totalCredit || 0;
    totalSolde.value = json.totalSolde || 0;

    // Détail
    sectionsRaw.value = json.sections || [];

    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const buildPdfParams = () => {
  const params = new URLSearchParams({
    mode: mode.value,
    date_debut: dateDebut.value,
    date_fin: dateFin.value,
  });
  if (mode.value === 'par_banque' && banqueId.value) {
    params.append('banque_id', banqueId.value);
  }
  return params;
};

const getBasePath = () => window.location.pathname.includes('/rapports/banques') ? '/rapports/banques' : '/rapports/fournisseurs';

const exportPdf = () => {
  window.open(`${getBasePath()}/pdf/situation-banques?${buildPdfParams()}`, '_blank');
};

const exportExcel = () => {
  // Excel export uniquement disponible sur le contrôleur fournisseurs
  window.open(`/rapports/fournisseurs/excel/situation-banques?${buildPdfParams()}`, '_blank');
};

const printReport = () => {
  const params = buildPdfParams();
  params.append('action', 'stream');
  const w = window.open(`${getBasePath()}/pdf/situation-banques?${params}`, '_blank');
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

/* Résumé */
.negatif { color: #e74c3c; }

/* Détail par banque */
.bank-section { margin-bottom: 28px; }
.bank-header { font-size: 14px; padding: 10px 14px; margin-bottom: 8px; border-bottom: 1px solid #333; }
.bank-header em { font-style: italic; }
.row-special { font-weight: 700; font-style: italic; }

.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
