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
      <div v-if="factures.length === 0" class="empty-state">
        <el-empty description="Aucune facture trouvée" />
      </div>
      <template v-else>
        <el-table
          style="width: 100%"
          :data="factures"
          border
          size="small"
          stripe
          show-summary
          :summary-method="getSummary"
        >
          <el-table-column label="Fournisseur" min-width="280">
            <template #default="{ row }">{{ row.fournisseur_label }}</template>
          </el-table-column>
          <el-table-column prop="numero" label="N° Pièce" min-width="120" />
          <el-table-column label="Date Enregistrement" min-width="130" align="center">
            <template #default="{ row }">{{ formatDate(row.date_facture) }}</template>
          </el-table-column>
          <el-table-column label="Montant Dû" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_ttc) }}</template>
          </el-table-column>
          <el-table-column label="Total Règlement" min-width="130" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant_paye) }}</template>
          </el-table-column>
          <el-table-column label="Solde" min-width="130" align="right">
            <template #default="{ row }">
              <span :style="{ color: row.reste_a_payer > 0 ? '#cc0000' : 'inherit', fontWeight: row.reste_a_payer > 0 ? 'bold' : 'normal' }">
                {{ formatMontant(row.reste_a_payer) }}
              </span>
            </template>
          </el-table-column>
        </el-table>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { useMontant } from '@/Composables/useMontant';

const props = defineProps({
  fournisseurs: { type: Array, default: () => [] },
});

const { formatMontant } = useMontant();

const selectedFournisseurId = ref(null);
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const factures = ref([]);
const totaux = ref({});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (selectedFournisseurId.value) params.append('fournisseur_id', selectedFournisseurId.value);
    if (dateDebut.value) params.append('date_debut', dateDebut.value);
    if (dateFin.value) params.append('date_fin', dateFin.value);

    const res = await fetch(`/rapports/fournisseurs/api/factures-soldes?${params}`);
    const json = await res.json();
    factures.value = json.factures || [];
    totaux.value = json.totaux || {};
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
    if (i === 1 || i === 2) { sums[i] = ''; return; }
    const keys = { 3: 'montant_ttc', 4: 'montant_paye', 5: 'reste_a_payer' };
    if (keys[i]) {
      sums[i] = formatMontant(totaux.value[keys[i]] || 0);
    } else {
      sums[i] = '';
    }
  });
  return sums;
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }
</style>
