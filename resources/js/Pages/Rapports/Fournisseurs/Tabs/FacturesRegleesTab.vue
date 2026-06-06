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

        <el-form-item v-if="selectedMode === 'periode'" label="Période">
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

            <PaginatedTable style="width: 100%" :data="resume" border size="small" stripe>
              <el-table-column prop="fournisseur" label="Fournisseur" min-width="200" />
              <el-table-column label="Total Mt TTC" min-width="130" align="right">
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
            </PaginatedTable>

            <div class="actions-bar">
              <el-button type="primary" @click="exportPdf('resume')">Exporter PDF</el-button>
              <el-dropdown trigger="click" @command="exportExcel">
                <el-button type="success">
                  Exporter Excel<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="1">Excel 1 (groupé)</el-dropdown-item>
                    <el-dropdown-item command="2">Excel 2 (liste à plat)</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              <el-button @click="printReport('resume')">Imprimer</el-button>
            </div>
          </el-tab-pane>

          <!-- Sous-onglet DÉTAIL -->
          <el-tab-pane name="detail">
            <template #label>
              <span class="sub-tab-label">Détail par fournisseur</span>
            </template>

            <div v-if="detailHasSoldee" class="soldee-legend">
              <span class="soldee-swatch"></span>
              Lignes en orange = factures <strong>marquées comme soldées</strong> (clôturées sans règlement) ; le « Mt Total Rég. » en rouge signale le déficit.
            </div>

            <GroupNavigator :groups="detail">
              <template #label="{ group }">
                <strong>Fournisseur :</strong> {{ group.fournisseur }}
              </template>
              <template #default="{ group }">
              <PaginatedTable style="width: 100%" :data="group.lignes" border size="small" stripe :row-class-name="rowClassSoldee">
                <el-table-column label="N°PC" min-width="120">
                  <template #default="{ row }">
                    {{ row.numero_piece }}
                    <el-tag v-if="row.marquee_soldee" type="warning" size="small" effect="plain" style="margin-left: 4px;">Soldée</el-tag>
                  </template>
                </el-table-column>
                <el-table-column prop="libelle" label="Libellé facture" min-width="180" show-overflow-tooltip />
                <el-table-column prop="date" label="Date PC" min-width="85" />
                <el-table-column prop="date_reglement" label="Date Règ." min-width="85" />
                <el-table-column label="Mt TTC" min-width="95" align="right">
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
                    <span style="font-weight: bold;" :style="{ color: row.marquee_soldee ? '#b91c1c' : 'inherit' }">{{ formatMontant(row.mt_total_reg) }}</span>
                  </template>
                </el-table-column>
              </PaginatedTable>
              </template>
            </GroupNavigator>

            <div class="actions-bar">
              <el-button type="primary" @click="exportPdf('detail')">Exporter PDF</el-button>
              <el-dropdown trigger="click" @command="exportExcel">
                <el-button type="success">
                  Exporter Excel<el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="1">Excel 1 (groupé)</el-dropdown-item>
                    <el-dropdown-item command="2">Excel 2 (liste à plat)</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
              <el-button @click="printReport('detail')">Imprimer</el-button>
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
import { ArrowDown } from '@element-plus/icons-vue';
import PaginatedTable from '@/Components/PaginatedTable.vue';
import GroupNavigator from '@/Components/GroupNavigator.vue';

const props = defineProps({
  fournisseurs: { type: Array, default: () => [] },
});

const detailHasSoldee = computed(() => detail.value.some((f) => f.has_soldee));
const rowClassSoldee = ({ row }) => (row.marquee_soldee ? 'row-soldee' : '');

const selectedFournisseurId = ref(null);
const selectedMode = ref('date');
const dateRef = ref('');
const dateRange = ref([]);
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
  const [debut, fin] = dateRange.value || [];
  if (selectedMode.value === 'periode' && (!debut || !fin)) {
    ElMessage.warning('Veuillez sélectionner la période complète');
    return;
  }

  loading.value = true;
  try {
    const params = new URLSearchParams({ mode: selectedMode.value });
    if (selectedMode.value === 'date') {
      params.append('date', dateRef.value);
    } else {
      params.append('date_debut', debut);
      params.append('date_fin', fin);
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

const buildPdfParams = (type) => {
  const params = new URLSearchParams({ mode: selectedMode.value, type });
  if (selectedMode.value === 'date') {
    params.append('date', dateRef.value);
  } else {
    const [debut, fin] = dateRange.value || [];
    if (debut) params.append('date_debut', debut);
    if (fin) params.append('date_fin', fin);
  }
  if (selectedFournisseurId.value) {
    params.append('fournisseur_id', selectedFournisseurId.value);
  }
  return params;
};

const exportPdf = (type) => {
  openPdf(`/rapports/fournisseurs/pdf/factures-reglees?${buildPdfParams(type)}`, 'Aperçu du rapport');
};

const exportExcel = (format = '1') => {
  const params = buildPdfParams('detail');
  if (format === '2') params.append('format', '2');
  window.open(`/rapports/fournisseurs/excel/factures-reglees?${params}`, '_blank');
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
.soldee-legend { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #92400e; background: #fff7ed; border: 1px solid #fed7aa; padding: 8px 12px; border-radius: 4px; margin-bottom: 12px; }
.soldee-swatch { display: inline-block; width: 14px; height: 14px; background: #fff7ed; border: 2px solid #fb923c; border-radius: 2px; flex-shrink: 0; }
.row-soldee { background: #fff7ed; }
:deep(.el-table .row-soldee td.el-table__cell) { background: #fff7ed; }
</style>
