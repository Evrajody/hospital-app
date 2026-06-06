<template>
  <div class="tab-content">
    <!-- Filtres partagés -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Mode">
          <el-radio-group v-model="selectedMode" size="default">
            <el-radio-button label="mois_annee">Mois/Année</el-radio-button>
            <el-radio-button label="periode">Période</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <template v-if="selectedMode === 'mois_annee'">
          <el-form-item label="Mois">
            <el-select v-model="selectedMois" placeholder="Mois" style="width: 150px">
              <el-option v-for="m in moisOptions" :key="m.value" :label="m.label" :value="m.value" />
            </el-select>
          </el-form-item>
          <el-form-item label="Année">
            <el-input-number v-model="selectedAnnee" :min="2000" :max="2099" :controls="false" style="width: 90px" />
          </el-form-item>
        </template>

        <template v-if="selectedMode === 'periode'">
          <el-form-item label="Période">
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
        </template>

        <el-form-item>
          <el-button type="primary" @click="fetchData" :loading="loading">Afficher</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Résultats avec 3 sous-onglets -->
    <div v-if="fetched" class="results-section">
      <div v-if="lignes.length === 0" class="empty-state">
        <el-empty description="Aucune facture avec AIB trouvée pour cette période" />
      </div>
      <template v-else>
        <el-tabs v-model="activeSubTab" type="card" class="sub-tabs">

          <!-- Sous-onglet DÉCLARATION AIB -->
          <el-tab-pane name="declaration">
            <template #label><span class="sub-tab-label">Déclaration AIB</span></template>

            <div class="report-title">{{ titreDeclaration }}</div>

            <PaginatedTable style="width: 100%" :data="lignes" border size="small" stripe show-summary :summary-method="getSummaryDeclaration">
              <el-table-column prop="numero_piece" label="N°Pièce" min-width="100" />
              <el-table-column prop="date" label="Date AIB" min-width="90" />
              <el-table-column prop="ifu" label="IFU" min-width="120" />
              <el-table-column prop="fournisseur" label="Fournisseur" min-width="220" />
              <el-table-column prop="libelle" label="Libellé facture" min-width="200" />
              <el-table-column label="Mt TTC" min-width="100" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_facture) }}</template>
              </el-table-column>
              <el-table-column label="Mt M.O." min-width="100" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_mo) }}</template>
              </el-table-column>
              <el-table-column label="Taux AIB" min-width="75" align="right">
                <template #default="{ row }">{{ Math.round(row.taux_aib) }}%</template>
              </el-table-column>
              <el-table-column label="Montant AIB" min-width="100" align="right">
                <template #default="{ row }">{{ formatMontant(row.montant_aib) }}</template>
              </el-table-column>
            </PaginatedTable>

            <div class="actions-bar">
              <el-button type="primary" @click="exportPdf('declaration')">Exporter PDF</el-button>
              <el-button @click="printReport('declaration')">Imprimer</el-button>
            </div>
          </el-tab-pane>

          <!-- Sous-onglet BORDEREAU DE VERSEMENT -->
          <el-tab-pane name="bordereau">
            <template #label><span class="sub-tab-label">Bordereau de versement</span></template>

            <div class="bordereau-preview">
              <div class="bordereau-title">BORDEREAU DE VERSEMENT DES PRÉLÈVEMENTS D'ACOMPTE IMPUTABLE SUR L'IMPÔT ASSIS SUR LES BÉNÉFICES</div>

              <div class="bordereau-section-header">II- LIQUIDATION DES DROITS</div>
              <el-table style="width: 100%" :data="bordereauRows" border size="small" :show-header="true">
                <el-table-column prop="nature" label="NATURE" min-width="300" />
                <el-table-column label="BASE" min-width="130" align="right">
                  <template #default="{ row }">
                    <span v-if="row.isCategory" style="font-weight: bold;"></span>
                    <span v-else>{{ row.base !== null ? formatMontant(row.base) : '-' }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="MONTANT" min-width="130" align="right">
                  <template #default="{ row }">
                    <span v-if="row.isCategory"></span>
                    <span v-else-if="row.isTotal" style="font-weight: bold; font-size: 14px;">{{ formatMontant(row.montant) }}</span>
                    <span v-else>{{ row.montant !== null ? formatMontant(row.montant) : '-' }}</span>
                  </template>
                </el-table-column>
              </el-table>

              <div class="montant-lettres">
                Montant du versement en lettres : <strong>{{ montantEnLettres }}</strong>
              </div>
            </div>

            <div class="actions-bar">
              <el-button type="primary" @click="exportPdf('bordereau')">Exporter PDF</el-button>
              <el-button @click="printReport('bordereau')">Imprimer</el-button>
            </div>
          </el-tab-pane>

        </el-tabs>
      </template>
    </div>
  </div>
</template>

<script setup>
import { usePdfViewer } from '@/Composables/usePdfViewer';
const { openPdf } = usePdfViewer();
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
import PaginatedTable from '@/Components/PaginatedTable.vue';

const selectedMode = ref('mois_annee');
const selectedMois = ref(new Date().getMonth() + 1);
const selectedAnnee = ref(new Date().getFullYear());
const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const activeSubTab = ref('declaration');

const titreDeclaration = ref('');
const lignes = ref([]);
const totaux = ref({});
const parTaux = ref([]);
const montantTotal = ref(0);
const montantEnLettres = ref('');

const moisOptions = [
  { value: 1, label: 'JANVIER' }, { value: 2, label: 'FÉVRIER' }, { value: 3, label: 'MARS' },
  { value: 4, label: 'AVRIL' }, { value: 5, label: 'MAI' }, { value: 6, label: 'JUIN' },
  { value: 7, label: 'JUILLET' }, { value: 8, label: 'AOÛT' }, { value: 9, label: 'SEPTEMBRE' },
  { value: 10, label: 'OCTOBRE' }, { value: 11, label: 'NOVEMBRE' }, { value: 12, label: 'DÉCEMBRE' },
];

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const bordereauRows = computed(() => {
  const rows = [];
  rows.push({ nature: 'Achats de marchandises', isCategory: true, base: null, montant: null });
  rows.push({ nature: 'AIB de %', isCategory: false, base: null, montant: null });
  rows.push({ nature: 'Prestations de services', isCategory: true, base: null, montant: null });
  parTaux.value.forEach(t => {
    rows.push({ nature: `AIB de ${Math.round(t.taux)}%`, isCategory: false, base: t.base, montant: t.montant });
  });
  rows.push({ nature: 'Montant total à reverser', isCategory: false, isTotal: true, base: null, montant: montantTotal.value });
  return rows;
});

const fetchData = async () => {
  if (selectedMode.value === 'mois_annee' && (!selectedMois.value || !selectedAnnee.value)) {
    ElMessage.warning('Veuillez sélectionner le mois et l\'année');
    return;
  }
  const [debut, fin] = dateRange.value || [];
  if (selectedMode.value === 'periode' && (!debut || !fin)) {
    ElMessage.warning('Veuillez sélectionner la période complète');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    if (selectedMode.value === 'mois_annee') {
      params.append('mois', selectedMois.value);
      params.append('annee', selectedAnnee.value);
    } else {
      params.append('date_debut', debut);
      params.append('date_fin', fin);
    }

    const res = await fetch(`/rapports/fournisseurs/api/declaration-aib?${params}`);
    const json = await res.json();
    titreDeclaration.value = json.titreDeclaration || '';
    lignes.value = json.lignes || [];
    totaux.value = json.totaux || {};
    parTaux.value = json.parTaux || [];
    montantTotal.value = json.montantTotal || 0;
    montantEnLettres.value = json.montantEnLettres || '';
    fetched.value = true;
    activeSubTab.value = 'declaration';
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const getSummaryDeclaration = ({ columns, data: tableData }) => {
  const sums = [];
  columns.forEach((col, i) => {
    if (i === 0) { sums[i] = 'TOTAL'; return; }
    if (i === 5) { sums[i] = formatMontant(tableData.reduce((s, r) => s + (r.montant_mo || 0), 0)); return; }
    if (i === 7) { sums[i] = formatMontant(tableData.reduce((s, r) => s + (r.montant_aib || 0), 0)); return; }
    sums[i] = '';
  });
  return sums;
};

const buildPdfParams = (type) => {
  const params = new URLSearchParams({ mode: selectedMode.value, type });
  if (selectedMode.value === 'mois_annee') {
    params.append('mois', selectedMois.value);
    params.append('annee', selectedAnnee.value);
  } else {
    const [debut, fin] = dateRange.value || [];
    if (debut) params.append('date_debut', debut);
    if (fin) params.append('date_fin', fin);
  }
  return params;
};

const exportPdf = (type) => {
  openPdf(`/rapports/fournisseurs/pdf/declaration-aib?${buildPdfParams(type)}`, 'Aperçu du rapport');
};

const printReport = (type) => {
  const params = buildPdfParams(type);
  params.append('action', 'stream');
  const w = window.open(`/rapports/fournisseurs/pdf/declaration-aib?${params}`, '_blank');
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
.report-title { font-size: 16px; font-weight: 600; text-align: center; margin-bottom: 20px; text-decoration: underline; }
.bordereau-preview { max-width: 700px; margin: 0 auto; }
.bordereau-title { text-align: center; font-size: 15px; font-weight: 700; border: 2px solid #333; padding: 12px; margin-bottom: 20px; }
.bordereau-section-header { background: #ddd; border: 1px solid #333; padding: 8px 12px; text-align: center; font-weight: bold; font-size: 13px; margin-bottom: 0; }
.montant-lettres { padding: 12px; border: 1px solid #ddd; background: #fafafa; font-size: 13px; margin-top: 12px; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
