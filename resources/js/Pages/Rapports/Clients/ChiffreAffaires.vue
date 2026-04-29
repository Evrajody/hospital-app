<template>
  <div class="document-container">
    <!-- Modal Critères -->
    <el-dialog
      v-model="showCriteres"
      title="CHIFFRE D'AFFAIRE"
      min-width="600px"
      :close-on-click-modal="false"
      :show-close="true"
      @close="handleBack"
      class="criteres-dialog"
    >
      <div class="criteres-subtitle">Critères</div>
      <div class="criteres-body">
        <!-- Option 1 : CA du (date spécifique) -->
        <div class="critere-option">
          <el-radio v-model="selectedMode" label="global_du" size="large">
            <strong>Chiffre d'affaire du :</strong>
          </el-radio>
          <div class="critere-inline-date" v-if="selectedMode === 'global_du'">
            <span class="critere-label">Date :</span>
            <el-date-picker
              v-model="dateRef"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Sélectionner une date"
            />
          </div>
        </div>

        <!-- Option 2 : CA au (cumulé jusqu'à) -->
        <div class="critere-option">
          <el-radio v-model="selectedMode" label="global_au" size="large">
            <strong>Chiffre d'affaire au :</strong>
          </el-radio>
          <div class="critere-inline-date" v-if="selectedMode === 'global_au'">
            <span class="critere-label">Date :</span>
            <el-date-picker
              v-model="dateRef"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              placeholder="Sélectionner une date"
            />
          </div>
        </div>

        <!-- Option 3 : CA période -->
        <div class="critere-option">
          <el-radio v-model="selectedMode" label="global_periode" size="large">
            <strong>Chiffre d'affaire (période) :</strong>
          </el-radio>
          <div class="critere-dates" v-if="selectedMode === 'global_periode'">
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

        <!-- Option 4 : CA d'un client -->
        <div class="critere-option">
          <el-radio v-model="selectedMode" label="par_client" size="large">
            <strong>Chiffre d'affaire d'un client</strong>
          </el-radio>
          <div v-if="selectedMode === 'par_client'" class="critere-sub">
            <div class="critere-field">
              <span class="critere-label">Client :</span>
              <el-select
                v-model="selectedClientId"
                placeholder="Sélectionner un client"
                filterable
                clearable
                style="width: 350px"
              >
                <el-option
                  v-for="c in clients"
                  :key="c.id"
                  :label="`${c.code} - ${c.nom}`"
                  :value="c.id"
                />
              </el-select>
            </div>
            <div class="critere-periode-option">
              <el-checkbox v-model="usePeriode">
                <strong>Période</strong> <span class="option-hint">(optionnelle)</span>
              </el-checkbox>
            </div>
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
      </div>

      <template #footer>
        <el-button size="large" @click="handleBack">Retour</el-button>
        <el-button type="primary" size="large" @click="afficher" :loading="loading">
          Afficher
        </el-button>
      </template>
    </el-dialog>

    <!-- Rapport : Global (du / au / période) -->
    <div v-if="showReport && isGlobalMode" class="document-page">
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
        <h1>
          <template v-if="mode === 'global_du'">
            Chiffre d'affaire réalisé du {{ formatDate(dateRef) }}
          </template>
          <template v-else-if="mode === 'global_au'">
            Chiffre d'affaire réalisé au {{ formatDate(dateRef) }}
          </template>
          <template v-else-if="mode === 'global_periode' && periode.debut && periode.fin">
            Chiffre d'affaire réalisé du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}
          </template>
          <template v-else>
            Chiffre d'affaire réalisé
          </template>
        </h1>
      </div>

      <div class="section ca-global-section">
        <table class="data-table ca-table">
          <thead>
            <tr>
              <th>CHIFFRE D'AFFAIRE</th>
              <th class="montant-col" style="width: 220px">MONTANT</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Théorique (Mt des factures) :</strong></td>
              <td class="montant-col">{{ formatMontant(data.theorique) }}</td>
            </tr>
            <tr>
              <td><strong>Physique (Mt des règlements) :</strong></td>
              <td class="montant-col">{{ formatMontant(data.physique) }}</td>
            </tr>
            <tr class="ecart-row">
              <td class="total-label"><strong>Ecart :</strong></td>
              <td class="montant-col ecart-value">{{ formatMontant(data.ecart) }}</td>
            </tr>
          </tbody>
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

    <!-- Rapport : Par client -->
    <div v-if="showReport && mode === 'par_client'" class="document-page">
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
        <h1>
          ÉTAT CHIFFRE D'AFFAIRE (CA) CLIENT
          <template v-if="periode.debut && periode.fin">
            <br>DU {{ formatDate(periode.debut) }} AU {{ formatDate(periode.fin) }}
          </template>
        </h1>
      </div>

      <div v-if="!data || !data.lignes || data.lignes.length === 0" class="section">
        <p style="text-align: center; padding: 40px; color: #666;">Aucune facture trouvée pour ce client.</p>
      </div>

      <div v-else>
        <div class="client-header-box">
          <span><strong><u>N° Compte :</u></strong> {{ data.numero_compte }}</span>
          <span style="margin-left: 40px"><strong><u>Raison sociale :</u></strong> {{ data.raison_sociale }}</span>
        </div>

        <div class="section">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 50px">N°</th>
                <th>Référence Facture</th>
                <th>Date Facture</th>
                <th class="montant-col">Montant CA Facture</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ligne in data.lignes" :key="ligne.numero">
                <td><strong>{{ ligne.numero }}</strong></td>
                <td>{{ ligne.reference }}</td>
                <td>{{ ligne.date_facture }}</td>
                <td class="montant-col">{{ formatMontant(ligne.montant) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td colspan="3" class="total-label">Total CA Client :</td>
                <td class="montant-col total-cell total-ca">{{ formatMontant(data.total_ca) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
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
  data: { type: [Object, Array], default: () => ({}) },
  clients: { type: Array, default: () => [] },
  mode: { type: String, default: 'global_du' },
  periode: { type: Object, default: () => ({ debut: '', fin: '' }) },
  dateRef: { type: String, default: null },
  selectedClientId: { type: Number, default: null },
});

const selectedMode = ref(props.mode);
const selectedClientId = ref(props.selectedClientId);
const dateDebut = ref(props.periode.debut);
const dateFin = ref(props.periode.fin);
const dateRef = ref(props.dateRef);
const usePeriode = ref(!!(props.periode.debut && props.periode.fin && props.mode === 'par_client'));
const loading = ref(false);

const isGlobalMode = computed(() => {
  return ['global_du', 'global_au', 'global_periode'].includes(props.mode);
});

const hasData = () => {
  if (isGlobalMode.value) return props.data && props.data.theorique !== undefined;
  if (props.mode === 'par_client') return props.data && props.data.lignes;
  return false;
};

const showReport = ref(hasData());
const showCriteres = ref(!showReport.value);

const openCriteres = () => {
  showCriteres.value = true;
};

const afficher = () => {
  if (selectedMode.value === 'par_client' && !selectedClientId.value) {
    ElMessage.warning('Veuillez sélectionner un client');
    return;
  }

  if (['global_du', 'global_au'].includes(selectedMode.value) && !dateRef.value) {
    ElMessage.warning('Veuillez sélectionner une date');
    return;
  }

  loading.value = true;
  const params = { mode: selectedMode.value };

  if (selectedMode.value === 'par_client') {
    params.client_id = selectedClientId.value;
    if (usePeriode.value) {
      params.date_debut = dateDebut.value;
      params.date_fin = dateFin.value;
    }
  } else if (selectedMode.value === 'global_periode') {
    params.date_debut = dateDebut.value;
    params.date_fin = dateFin.value;
  } else {
    params.date_ref = dateRef.value;
  }

  router.get('/rapports/clients/chiffre-affaires', params, {
    preserveState: false,
    onFinish: () => {
      loading.value = false;
      showReport.value = true;
      showCriteres.value = false;
    },
  });
};

const handleBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/rapports/clients');
  }
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
  padding: 12px 24px 20px;
}

.critere-option {
  padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
}

.critere-option:last-of-type {
  border-bottom: none;
}

.critere-sub {
  margin-top: 10px;
}

.critere-inline-date {
  margin-top: 10px;
  margin-left: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.critere-field {
  margin-left: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.critere-label {
  font-weight: 600;
  font-size: 13px;
  min-width: 90px;
}

.critere-periode-option {
  margin: 14px 0 0 28px;
}

.option-hint {
  color: #999999;
  font-size: 12px;
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

/* CA Global */
.ca-global-section {
  max-width: 600px;
  margin: 40px auto;
}

.ca-table {
  border: 2px solid #000000;
}

.ca-table thead tr {
  background: #000000;
}

.ca-table thead th {
  color: #ffffff !important;
  font-size: 14px;
  text-align: center;
  padding: 12px 16px;
}

.ca-table tbody td {
  padding: 14px 16px;
  font-size: 14px;
}

.ecart-row {
  border-top: 2px solid #000000;
}

.ecart-row td {
  font-weight: bold;
}

.ecart-value {
  color: #cc0000 !important;
  font-weight: bold;
  font-size: 15px !important;
}

/* CA Par client */
.client-header-box {
  border: 2px solid #000000;
  padding: 10px 16px;
  margin-bottom: 20px;
  font-size: 13px;
  background: #ffffff;
}

.total-ca {
  font-weight: bold;
  font-size: 14px;
}

.document-title h1 {
  text-align: center;
}

.document-title .periode {
  font-size: 13px;
  margin-top: 8px;
  opacity: 0.9;
}

@media print {
  .ca-table thead tr {
    background: #000000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .ca-table thead th {
    color: #ffffff !important;
  }

  .ecart-value {
    color: #cc0000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
