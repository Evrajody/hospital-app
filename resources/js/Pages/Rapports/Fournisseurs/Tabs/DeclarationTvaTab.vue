<template>
  <div class="tab-content">
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

    <div v-if="fetched" class="results-section">
      <div v-if="lignes.length === 0" class="empty-state">
        <el-empty description="Aucune facture assujettie à la TVA pour la période" />
      </div>
      <template v-else>
        <div class="report-title">{{ titreDeclaration }}</div>

        <div class="summary-cards">
          <div class="summary-card">
            <div class="label">Total TTC</div>
            <div class="value">{{ formatMontant(totaux.ttc) }}</div>
          </div>
          <div class="summary-card">
            <div class="label">Total HT</div>
            <div class="value">{{ formatMontant(totaux.ht) }}</div>
          </div>
          <div class="summary-card danger">
            <div class="label">Total TVA</div>
            <div class="value">{{ formatMontant(totaux.tva) }}</div>
          </div>
          <div class="summary-card">
            <div class="label">Nombre de factures</div>
            <div class="value">{{ lignes.length }}</div>
          </div>
        </div>

        <PaginatedTable :data="lignes" border size="small" stripe show-summary :summary-method="summaryMethod">
          <el-table-column prop="date" label="Date" min-width="100" />
          <el-table-column prop="numero_piece" label="N° PC" min-width="110" />
          <el-table-column prop="fournisseur" label="Fournisseur" min-width="180" show-overflow-tooltip />
          <el-table-column prop="fournisseur_ifu" label="IFU" min-width="130" />
          <el-table-column prop="libelle" label="Libellé facture" min-width="240" show-overflow-tooltip />
          <el-table-column label="Montant TTC" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_ttc) }}</template>
          </el-table-column>
          <el-table-column label="Taux TVA" min-width="90" align="right">
            <template #default="{ row }">{{ row.taux_tva }}%</template>
          </el-table-column>
          <el-table-column label="Montant TVA" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_tva) }}</template>
          </el-table-column>
        </PaginatedTable>

        <div class="actions-bar">
          <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
          <el-button type="success" @click="exportExcel">Exporter Excel</el-button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { useAsyncExport } from '@/Composables/useAsyncExport';
const { startExport } = useAsyncExport();
const REPORT_KEY = 'rapports-fournisseurs.declaration-tva';
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { useMontant } from '@/Composables/useMontant';
import PaginatedTable from '@/Components/PaginatedTable.vue';

const { formatMontant } = useMontant();

const selectedMode = ref('mois_annee');
const selectedMois = ref(new Date().getMonth() + 1);
const selectedAnnee = ref(new Date().getFullYear());
const dateRange = ref([]);
const loading = ref(false);
const fetched = ref(false);
const titreDeclaration = ref('');
const lignes = ref([]);
const totaux = ref({ ttc: 0, tva: 0, ht: 0 });

const moisOptions = [
  { value: 1, label: 'JANVIER' }, { value: 2, label: 'FÉVRIER' }, { value: 3, label: 'MARS' },
  { value: 4, label: 'AVRIL' }, { value: 5, label: 'MAI' }, { value: 6, label: 'JUIN' },
  { value: 7, label: 'JUILLET' }, { value: 8, label: 'AOÛT' }, { value: 9, label: 'SEPTEMBRE' },
  { value: 10, label: 'OCTOBRE' }, { value: 11, label: 'NOVEMBRE' }, { value: 12, label: 'DÉCEMBRE' },
];

const buildParams = () => {
  const params = new URLSearchParams({ mode: selectedMode.value });
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
    const res = await fetch(`/rapports/fournisseurs/api/declaration-tva?${buildParams()}`);
    const json = await res.json();
    titreDeclaration.value = json.titreDeclaration || '';
    lignes.value = json.lignes || [];
    totaux.value = json.totaux || { ttc: 0, tva: 0, ht: 0 };
    fetched.value = true;
  } catch {
    ElMessage.error('Erreur chargement');
  } finally {
    loading.value = false;
  }
};

const summaryMethod = ({ columns }) => {
  return columns.map((col, idx) => {
    if (idx === 0) return 'TOTAL';
    if (col.property === 'libelle') return '';
    return '';
  });
};

const exportPdf = () => {
  startExport(REPORT_KEY, 'pdf', Object.fromEntries(buildParams()));
};

const exportExcel = () => {
  startExport(REPORT_KEY, 'excel', Object.fromEntries(buildParams()));
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
.report-title { text-align: center; font-size: 15px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; }
.summary-cards { display: flex; gap: 16px; margin-bottom: 20px; }
.summary-card { flex: 1; background: #fafafa; border: 1px solid #e0e0e0; border-left: 4px solid var(--el-color-primary); padding: 14px 16px; }
.summary-card.danger { border-left-color: #f56c6c; }
.summary-card .label { font-size: 12px; color: #999; margin-bottom: 4px; }
.summary-card .value { font-size: 18px; font-weight: bold; color: #333; }
.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
