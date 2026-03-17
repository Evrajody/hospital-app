<template>
  <div class="document-container">
    <!-- Modal Critères -->
    <el-dialog
      v-model="showCriteres"
      title="BROUILLARD DE CHÈQUES & IMPUTATIONS COMPTABLES"
      min-width="550px"
      :close-on-click-modal="false"
      :show-close="true"
      @close="handleBack"
      class="criteres-dialog"
    >
      <div class="criteres-subtitle">Indiquez la période</div>
      <div class="criteres-body">
        <div class="critere-date-row">
          <span class="critere-label">Date début :</span>
          <el-date-picker
            v-model="dateDebut"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date début"
          />
        </div>
        <div class="critere-date-row">
          <span class="critere-label">Date fin :</span>
          <el-date-picker
            v-model="dateFin"
            type="date"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            placeholder="Date fin"
          />
        </div>
      </div>

      <template #footer>
        <el-button size="large" @click="handleBack">Retour</el-button>
        <el-button type="primary" size="large" @click="afficher" :loading="loading">
          Afficher
        </el-button>
      </template>
    </el-dialog>

    <!-- Rapport -->
    <div v-if="showReport" class="document-page">
      <div class="header">
        <div class="logo-section">
          <div class="hospital-name">HÔPITAL DE MÉNONTIN</div>
          <div class="hospital-info">
            <div>République du Bénin - Service Comptabilité</div>
            <div>BP 123 - Cotonou - Tél: +229 21 XX XX XX</div>
          </div>
        </div>
      </div>

      <div class="document-title">
        <h1>BROUILLARD DE CHÈQUES</h1>
        <div v-if="periode.debut && periode.fin" class="periode">
          Du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}
        </div>
      </div>

      <div v-if="data.length === 0" class="section">
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée pour cette période.</p>
      </div>

      <div v-else class="section">
        <table class="data-table brouillard-table">
          <thead>
            <tr>
              <th style="width: 100px">Date</th>
              <th>Libellés</th>
              <th class="montant-col" style="width: 130px">Débit</th>
              <th class="montant-col" style="width: 130px">Crédit</th>
              <th class="montant-col" style="width: 130px">Solde</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(group, index) in groupedData" :key="index">
              <!-- Date header row -->
              <tr class="date-header-row">
                <td><strong>{{ group.date }}</strong></td>
                <td colspan="4"></td>
              </tr>
              <!-- Entry rows -->
              <tr v-for="(entry, idx) in group.entries" :key="idx">
                <td></td>
                <td>{{ entry.libelle }}</td>
                <td class="montant-col">{{ entry.debit ? formatMontant(entry.debit) : '' }}</td>
                <td class="montant-col">{{ entry.credit ? formatMontant(entry.credit) : '' }}</td>
                <td class="montant-col solde-col">{{ formatMontant(entry.solde) }}</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="2" class="total-label">TOTAUX</td>
              <td class="montant-col total-cell">{{ formatMontant(totalDebit) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totalCredit) }}</td>
              <td class="montant-col total-cell solde-col">{{ formatMontant(soldeGeneral) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Imputations Comptables -->
      <div v-if="data.length > 0" class="section" style="margin-top: 40px">
        <div class="section-title">IMPUTATIONS COMPTABLES</div>
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 90px">Date</th>
              <th style="width: 130px">N° Pièce</th>
              <th>Libellé</th>
              <th style="width: 100px">Cpte Débit</th>
              <th style="width: 100px">Cpte Crédit</th>
              <th class="montant-col" style="width: 130px">Montant</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(entry, idx) in data" :key="'imp-' + idx">
              <td>{{ entry.date }}</td>
              <td><strong>{{ entry.reference }}</strong></td>
              <td>{{ entry.client_nom }} ({{ entry.facture_ref }})</td>
              <td class="compte-cell">{{ entry.compte_debit }}</td>
              <td class="compte-cell">{{ entry.compte_credit }}</td>
              <td class="montant-col">{{ formatMontant(entry.montant) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="5" class="total-label">TOTAL</td>
              <td class="montant-col total-cell">{{ formatMontant(totalMontant) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="footer">
        <div class="footer-line"></div>
        <div class="footer-content">
          <div>Document généré le {{ formatDateLong(new Date()) }}</div>
          <div class="page-number">Page 1/1</div>
        </div>
        <div class="print-button-container no-print">
          <el-button type="primary" :icon="Printer" @click="handlePrint">Imprimer</el-button>
          <el-button @click="openCriteres">Modifier les critères</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import { Printer } from '@element-plus/icons-vue';

const props = defineProps({
  data: { type: Array, default: () => [] },
  periode: { type: Object, default: () => ({ debut: '', fin: '' }) },
});

const dateDebut = ref(props.periode.debut);
const dateFin = ref(props.periode.fin);
const loading = ref(false);
const showReport = ref(props.data.length > 0);
const showCriteres = ref(!showReport.value);

// Group entries by date
const groupedData = computed(() => {
  const groups = [];
  let currentDate = null;
  let currentGroup = null;

  props.data.forEach(entry => {
    if (entry.date !== currentDate) {
      currentDate = entry.date;
      currentGroup = { date: entry.date, entries: [] };
      groups.push(currentGroup);
    }
    currentGroup.entries.push(entry);
  });

  return groups;
});

const totalDebit = computed(() => {
  return props.data.reduce((sum, e) => sum + (e.debit || 0), 0);
});

const totalCredit = computed(() => {
  return props.data.reduce((sum, e) => sum + (e.credit || 0), 0);
});

const soldeGeneral = computed(() => {
  if (props.data.length === 0) return 0;
  return props.data[props.data.length - 1].solde;
});

const totalMontant = computed(() => {
  return props.data.reduce((sum, e) => sum + (e.montant || 0), 0);
});

const openCriteres = () => {
  showCriteres.value = true;
};

const afficher = () => {
  if (!dateDebut.value || !dateFin.value) {
    ElMessage.warning('Veuillez indiquer la période (date début et date fin)');
    return;
  }

  loading.value = true;
  router.get('/rapports/clients/brouillard-cheques', {
    date_debut: dateDebut.value,
    date_fin: dateFin.value,
  }, {
    preserveState: false,
    onFinish: () => {
      loading.value = false;
      showReport.value = true;
      showCriteres.value = false;
    },
  });
};

const handleBack = () => {
  router.visit('/rapports/clients');
};

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(montant || 0);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateLong = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'long', year: 'numeric'
  });
};

const handlePrint = () => window.print();
</script>

<style scoped>
@import url('../Fournisseurs/rapports-styles.css');

/* Modal critères */
:deep(.criteres-dialog .el-dialog__header) {
  border-bottom: 2px solid #000000;
  padding: 16px 20px;
}

:deep(.criteres-dialog .el-dialog__title) {
  font-size: 18px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}

:deep(.criteres-dialog .el-dialog__body) {
  padding: 0;
}

:deep(.criteres-dialog .el-dialog__footer) {
  border-top: 1px solid #eeeeee;
  padding: 16px 20px;
}

.criteres-subtitle {
  font-size: 14px;
  font-weight: bold;
  color: #cc0000;
  padding: 16px 24px 0;
  text-decoration: underline;
}

.criteres-body {
  padding: 20px 24px 24px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.critere-date-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.critere-label {
  font-weight: 600;
  font-size: 13px;
  min-width: 90px;
}

/* Brouillard table */
.brouillard-table {
  border-collapse: collapse;
}

.date-header-row {
  background: #f0f0f0 !important;
  border-top: 2px solid #000000;
}

.date-header-row td {
  padding: 8px 10px;
  font-size: 13px;
}

.solde-col {
  font-weight: bold;
}

/* Section title */
.section-title {
  font-size: 15px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 10px 0;
  margin-bottom: 12px;
  border-bottom: 2px solid #000000;
}

.compte-cell {
  font-family: monospace;
  font-size: 12px;
  text-align: center;
}

.document-title .periode {
  font-size: 14px;
  margin-top: 8px;
  font-style: italic;
}

@media print {
  .date-header-row {
    background: #f0f0f0 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
