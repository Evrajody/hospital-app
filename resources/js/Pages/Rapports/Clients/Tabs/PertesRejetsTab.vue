<template>
  <div class="tab-content">
    <!-- Filtres -->
    <div class="filters-section">
      <el-form :inline="true" class="filters-form">
        <el-form-item label="Type">
          <el-select v-model="selectedType" placeholder="Tous les types" clearable style="width: 200px">
            <el-option label="Pertes" value="perte" />
            <el-option label="Rejets" value="rejet" />
            <el-option label="Régularisations" value="regularisation" />
          </el-select>
        </el-form-item>

        <el-form-item label="Type client">
          <el-select v-model="selectedTypeClient" placeholder="Tous" clearable style="width: 180px">
            <el-option label="Société" value="societe" />
            <el-option label="Divers" value="divers" />
            <el-option label="Personnel" value="personnel" />
            <el-option label="Autre" value="autre" />
          </el-select>
        </el-form-item>

        <el-form-item label="Client">
          <el-select
            v-model="selectedClientId"
            placeholder="Tous les clients"
            filterable
            clearable
            style="width: 280px"
          >
            <el-option
              v-for="c in clients"
              :key="c.id"
              :label="`${c.code} - ${c.nom}`"
              :value="c.id"
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
      <div v-if="data.length === 0" class="empty-state">
        <el-empty description="Aucune donnée trouvée" />
      </div>

      <template v-if="data.length > 0">
        <!-- Synthèse -->
        <div class="synthese-cards">
          <div class="synthese-card danger">
            <div class="synthese-label">Pertes</div>
            <div class="synthese-value">{{ formatMontant(totaux.pertes) }}</div>
          </div>
          <div class="synthese-card warning">
            <div class="synthese-label">Rejets</div>
            <div class="synthese-value">{{ formatMontant(totaux.rejets) }}</div>
          </div>
          <div class="synthese-card success">
            <div class="synthese-label">Régularisations</div>
            <div class="synthese-value">{{ formatMontant(totaux.regularisations) }}</div>
          </div>
          <div class="synthese-card">
            <div class="synthese-label">Solde Net</div>
            <div class="synthese-value" :style="{ color: totaux.solde > 0 ? '#cc0000' : '#008000' }">
              {{ formatMontant(totaux.solde) }}
            </div>
          </div>
        </div>

        <!-- Tableau -->
        <el-table style="width: 100%" :data="data" border size="small" stripe>
          <el-table-column prop="id" label="N°" min-width="50" align="center">
            <template #default="{ $index }">{{ $index + 1 }}</template>
          </el-table-column>
          <el-table-column prop="date_reglement" label="Date" min-width="100" />
          <el-table-column label="Type" min-width="120">
            <template #default="{ row }">
              <el-tag
                :type="row.type_reglement_couleur === 'danger' ? 'danger' : row.type_reglement_couleur === 'warning' ? 'warning' : row.type_reglement_couleur === 'success' ? 'success' : 'primary'"
                size="small"
              >
                {{ row.type_reglement_libelle }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="client_nom" label="Client" min-width="180">
            <template #default="{ row }">
              <strong>{{ row.client_nom }}</strong><br>
              <small style="color: #999">{{ row.client_code }}</small>
            </template>
          </el-table-column>
          <el-table-column label="Type" min-width="100">
            <template #default="{ row }">
              <el-tag size="small" effect="plain">{{ row.type_client_label || '-' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="facture_reference" label="Réf. Facture" min-width="120" />
          <el-table-column label="Montant" min-width="120" align="right">
            <template #default="{ row }">{{ formatMontant(row.montant) }}</template>
          </el-table-column>
          <el-table-column prop="observations" label="Observations" min-width="180">
            <template #default="{ row }">
              <span style="font-size: 12px; color: #666">{{ row.observations || '-' }}</span>
            </template>
          </el-table-column>
        </el-table>
      </template>

      <!-- Actions Export -->
      <div v-if="data.length > 0" class="actions-bar">
        <el-button type="primary" @click="exportPdf">Exporter PDF</el-button>
        <el-button @click="printReport">Imprimer</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';

const props = defineProps({
  clients: { type: Array, default: () => [] },
});

const selectedType = ref('');
const selectedTypeClient = ref('');
const selectedClientId = ref(null);
const dateDebut = ref('');
const dateFin = ref('');
const loading = ref(false);
const fetched = ref(false);
const data = ref([]);
const totaux = ref({ pertes: 0, rejets: 0, regularisations: 0, solde: 0 });

const formatMontant = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v || 0);

const buildParams = () => {
  const params = new URLSearchParams();
  if (selectedType.value) params.append('type_reglement', selectedType.value);
  if (selectedTypeClient.value) params.append('type_client', selectedTypeClient.value);
  if (selectedClientId.value) params.append('client_id', selectedClientId.value);
  if (dateDebut.value) params.append('date_debut', dateDebut.value);
  if (dateFin.value) params.append('date_fin', dateFin.value);
  return params;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = buildParams();
    const res = await fetch(`/rapports/clients/api/pertes-rejets?${params}`);
    const json = await res.json();
    data.value = json.data || [];
    totaux.value = json.totaux || { pertes: 0, rejets: 0, regularisations: 0, solde: 0 };
    fetched.value = true;
  } catch (e) {
    ElMessage.error('Erreur lors du chargement des données');
  } finally {
    loading.value = false;
  }
};

const exportPdf = () => {
  window.open(`/rapports/clients/pdf/pertes-rejets?${buildParams()}`, '_blank');
};

const printReport = () => {
  const params = buildParams();
  params.append('action', 'stream');
  const w = window.open(`/rapports/clients/pdf/pertes-rejets?${params}`, '_blank');
  w.onload = () => setTimeout(() => w.print(), 500);
};
</script>

<style scoped>
.tab-content { padding: 0; }
.filters-section { background: #fafafa; padding: 16px 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
.filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; }
.results-section { padding: 0 4px; }
.empty-state { padding: 40px 0; }

.synthese-cards {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.synthese-card {
  flex: 1;
  background: #fafafa;
  border: 1px solid #e0e0e0;
  border-left: 4px solid #999;
  padding: 14px 16px;
}

.synthese-card.danger { border-left-color: #f56c6c; }
.synthese-card.warning { border-left-color: #e6a23c; }
.synthese-card.success { border-left-color: #67c23a; }

.synthese-label {
  font-size: 12px;
  color: #999;
  margin-bottom: 4px;
}

.synthese-value {
  font-size: 18px;
  font-weight: bold;
  color: #333;
}

.actions-bar { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; padding-top: 16px; border-top: 1px solid #eee; }
</style>
