<template>
  <div class="document-container">
    <!-- Modal Critères -->
    <el-dialog
      v-model="showCriteres"
      title="ÉTAT DES MOUVEMENTS FACTURES"
      min-width="600px"
      :close-on-click-modal="false"
      :show-close="true"
      @close="handleBack"
      class="criteres-dialog"
    >
      <div class="criteres-subtitle">Critères</div>
      <div class="criteres-body">
        <div class="critere-field">
          <span class="critere-label">Fournisseur :</span>
          <el-select
            v-model="selectedFournisseurId"
            placeholder="Sélectionner un fournisseur"
            filterable
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="f in fournisseurs"
              :key="f.id"
              :label="`[${f.code}] ${f.nom}`"
              :value="f.id"
            />
          </el-select>
        </div>

        <div class="critere-option" style="margin-top: 16px">
          <el-checkbox v-model="usePeriode" size="large">
            <strong>Période</strong>
          </el-checkbox>
          <div class="critere-dates" v-if="usePeriode">
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
    <div v-if="showReport" class="document-page landscape">
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
        <h1>ÉTAT DES MOUVEMENTS FACTURES</h1>
        <div v-if="periode.debut && periode.fin" class="periode">
          Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}
        </div>
      </div>

      <!-- Info fournisseur -->
      <div v-if="fournisseurInfo" class="fournisseur-header-box">
        <span><strong><u>N° Compte :</u></strong> {{ fournisseurInfo.code }}</span>
        <span style="margin-left: 40px"><strong><u>Raison sociale :</u></strong> {{ fournisseurInfo.nom }}</span>
      </div>

      <div v-if="lignes.length === 0" class="section">
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
      </div>

      <div v-else class="section">
        <table class="data-table mouvement-table">
          <thead>
            <tr>
              <th style="width: 80px">N°Pièce</th>
              <th style="width: 75px">Date PC</th>
              <th style="width: 90px">Réf. Fact.</th>
              <th class="montant-col">Mt Fact.</th>
              <th class="montant-col">Avoir</th>
              <th class="montant-col">Mt M.O.</th>
              <th class="montant-col" style="width: 55px">AIB (%)</th>
              <th class="montant-col">Mt AIB</th>
              <th class="montant-col">Mt Dû</th>
              <th class="montant-col">Total Rèq.</th>
              <th class="montant-col">Solde</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(ligne, index) in lignes" :key="index">
              <td><strong>{{ ligne.numero_piece }}</strong></td>
              <td>{{ ligne.date }}</td>
              <td>{{ ligne.reference_facture }}</td>
              <td class="montant-col">{{ formatMontant(ligne.montant_facture) }}</td>
              <td class="montant-col">{{ formatMontant(ligne.avoir) }}</td>
              <td class="montant-col">{{ formatMontant(ligne.montant_mo) }}</td>
              <td class="montant-col">{{ ligne.taux_aib ? ligne.taux_aib.toFixed(1) + '%' : '' }}</td>
              <td class="montant-col">{{ formatMontant(ligne.montant_aib) }}</td>
              <td class="montant-col">{{ formatMontant(ligne.montant_du) }}</td>
              <td class="montant-col">{{ formatMontant(ligne.total_reglement) }}</td>
              <td class="montant-col">{{ formatMontant(ligne.solde) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAUX</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.montant_facture) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.avoir) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.montant_mo) }}</td>
              <td class="montant-col total-cell"></td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.montant_aib) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.montant_du) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.total_reglement) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.solde) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="footer">
        <div class="footer-line"></div>
        <div class="footer-content">
          <div class="edite-par">Édité par {{ userName }} - {{ formatDateLong(new Date()) }}</div>
          <div class="page-number">Page 1/1</div>
        </div>
        <div class="print-button-container no-print">
          <el-button type="primary" :icon="Printer" @click="handlePrint">Imprimer</el-button>
          <el-button @click="handleExportPdf" :loading="exportLoading">Exporter PDF</el-button>
          <el-button @click="openCriteres">Modifier les critères</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { ElMessage } from 'element-plus';
import { Printer } from '@element-plus/icons-vue';

const props = defineProps({
  fournisseurs: { type: Array, default: () => [] },
  fournisseur: { type: Object, default: null },
  lignes: { type: Array, default: () => [] },
  totaux: { type: Object, default: () => ({}) },
  periode: { type: Object, default: () => ({ debut: '', fin: '' }) },
  selectedFournisseurId: { type: Number, default: null },
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? 'Utilisateur');

const selectedFournisseurId = ref(props.selectedFournisseurId);
const usePeriode = ref(!!(props.periode.debut && props.periode.fin));
const dateDebut = ref(props.periode.debut);
const dateFin = ref(props.periode.fin);
const loading = ref(false);
const exportLoading = ref(false);
const showReport = ref(props.lignes.length > 0 || props.fournisseur !== null);
const showCriteres = ref(!showReport.value);

const fournisseurInfo = computed(() => props.fournisseur);

const openCriteres = () => {
  showCriteres.value = true;
};

const afficher = () => {
  if (!selectedFournisseurId.value) {
    ElMessage.warning('Veuillez sélectionner un fournisseur');
    return;
  }

  loading.value = true;
  const params = { fournisseur_id: selectedFournisseurId.value };
  if (usePeriode.value && dateDebut.value && dateFin.value) {
    params.date_debut = dateDebut.value;
    params.date_fin = dateFin.value;
  }

  router.get('/rapports/fournisseurs/mouvement-periodique', params, {
    preserveState: false,
    onFinish: () => {
      loading.value = false;
      showReport.value = true;
      showCriteres.value = false;
    },
  });
};

const handleExportPdf = () => {
  if (!selectedFournisseurId.value) return;
  exportLoading.value = true;

  const params = new URLSearchParams({ fournisseur_id: selectedFournisseurId.value });
  if (usePeriode.value && dateDebut.value && dateFin.value) {
    params.set('date_debut', dateDebut.value);
    params.set('date_fin', dateFin.value);
  }

  window.open(`/rapports/fournisseurs/pdf/mouvement-factures?${params.toString()}`, '_blank');
  exportLoading.value = false;
};

const handleBack = () => {
  router.visit('/rapports/fournisseurs');
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
  const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
  return new Date(date).toLocaleDateString('fr-FR', options);
};

const handlePrint = () => window.print();
</script>

<style scoped>
@import url('./rapports-styles.css');

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
  padding: 12px 24px 20px;
}

.critere-option {
  padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
}

.critere-field {
  display: flex;
  align-items: center;
  gap: 12px;
}

.critere-label {
  font-weight: 600;
  font-size: 13px;
  min-width: 100px;
}

.critere-dates {
  padding: 12px 0 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-left: 28px;
}

.critere-date-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Landscape page */
.document-page.landscape {
  max-width: 297mm;
  min-height: 210mm;
}

/* Fournisseur header */
.fournisseur-header-box {
  border: 2px solid #000000;
  padding: 10px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  background: #ffffff;
}

/* Mouvement table - colonnes serrées pour 11 colonnes */
.mouvement-table th,
.mouvement-table td {
  font-size: 9px;
  padding: 5px 4px;
}

.mouvement-table .montant-col {
  font-size: 9px;
}

.mouvement-table .total-cell {
  font-size: 10px;
}

/* Période & footer */
.document-title .periode {
  font-size: 13px;
  margin-top: 8px;
  opacity: 0.9;
}

.edite-par {
  font-style: italic;
}

@media print {
  .document-page.landscape {
    max-width: none;
  }

  @page {
    margin: 15mm;
    size: A4 landscape;
  }
}
</style>
