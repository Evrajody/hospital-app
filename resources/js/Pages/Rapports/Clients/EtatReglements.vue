<template>
  <div class="document-container">
    <!-- Modal Critères -->
    <el-dialog
      v-model="showCriteres"
      title="ÉTAT DES RÈGLEMENTS CLIENTS"
      width="600px"
      :close-on-click-modal="false"
      :show-close="true"
      @close="handleBack"
      class="criteres-dialog"
    >
      <div class="criteres-subtitle">Critères</div>
      <div class="criteres-body">
        <div class="critere-option">
          <el-radio v-model="selectedMode" label="par_client" size="large">
            <strong>État des règlements par client</strong>
          </el-radio>
        </div>

        <div class="critere-option">
          <el-radio v-model="selectedMode" label="un_client" size="large">
            <strong>État des règlements d'un Client</strong>
          </el-radio>
          <div class="critere-field" v-if="selectedMode === 'un_client'">
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
        </div>

        <div class="critere-option">
          <el-radio v-model="selectedMode" label="tous_clients" size="large">
            <strong>État des règlements de tous les clients</strong>
          </el-radio>
          <div class="critere-dates" v-if="selectedMode === 'tous_clients'">
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

    <!-- Rapport : Par client / Un client (tableau par client) -->
    <div v-if="showReport && (mode === 'par_client' || mode === 'un_client')" class="document-page">
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
        <h1>ÉTAT DES RÈGLEMENTS FACTURES {{ mode === 'un_client' ? 'DU CLIENT' : 'PAR CLIENT' }}</h1>
        <div v-if="periode.debut && periode.fin" class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <div v-if="data.length === 0" class="section">
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
      </div>

      <div v-for="clientData in data" :key="clientData.client_id" class="client-block">
        <div class="client-header-box">
          <span><strong><u>N° Compte :</u></strong> {{ clientData.numero_compte }}</span>
          <span style="margin-left: 40px"><strong><u>Raison sociale :</u></strong> {{ clientData.raison_sociale }}</span>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 50px">N°</th>
              <th>Référence Facture</th>
              <th>Date Facture</th>
              <th>Date Règ. Facture</th>
              <th class="montant-col">Montant Facture</th>
              <th class="montant-col">Montant Payé</th>
              <th class="montant-col rejet-col">Rejet</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ligne in clientData.lignes" :key="ligne.numero">
              <td><strong>{{ ligne.numero }}</strong></td>
              <td>{{ ligne.reference }}</td>
              <td>{{ ligne.date_facture }}</td>
              <td>{{ ligne.date_reglement }}</td>
              <td class="montant-col">{{ formatMontant(ligne.montant_facture) }}</td>
              <td class="montant-col">{{ formatMontant(ligne.montant_paye) }}</td>
              <td class="montant-col rejet-col">{{ formatMontant(ligne.rejet) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="total-label">Total :</td>
              <td class="montant-col total-cell">{{ formatMontant(clientData.total_facture) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(clientData.total_paye) }}</td>
              <td class="montant-col total-cell rejet-col">{{ formatMontant(clientData.total_rejet) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Grand Total -->
      <div v-if="data.length > 1" class="section" style="margin-top: 30px">
        <table class="data-table">
          <tfoot>
            <tr class="total-row">
              <td class="total-label" style="text-align: right">TOTAL GÉNÉRAL :</td>
              <td class="montant-col total-cell" style="width: 160px">{{ formatMontant(grandTotalFacture) }}</td>
              <td class="montant-col total-cell" style="width: 160px">{{ formatMontant(grandTotalPaye) }}</td>
              <td class="montant-col total-cell rejet-col" style="width: 120px">{{ formatMontant(grandTotalRejet) }}</td>
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

    <!-- Rapport : Tous les clients (tableau résumé) -->
    <div v-if="showReport && mode === 'tous_clients'" class="document-page">
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
        <h1>ÉTAT DES RÈGLEMENTS FACTURES DE TOUS LES CLIENTS</h1>
        <div v-if="periode.debut && periode.fin" class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <div v-if="data.length === 0" class="section">
        <p style="text-align: center; padding: 40px; color: #666;">Aucune donnée trouvée.</p>
      </div>

      <div v-else class="section">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 50px">N°</th>
              <th style="width: 120px">N° de Compte</th>
              <th>Raison sociale</th>
              <th class="montant-col">Montant total des factures</th>
              <th class="montant-col">Montant total des règlements</th>
              <th class="montant-col rejet-col">Rejet</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in data" :key="row.numero">
              <td><strong>{{ row.numero }}</strong></td>
              <td>{{ row.numero_compte }}</td>
              <td><strong>{{ row.raison_sociale }}</strong></td>
              <td class="montant-col">{{ formatMontant(row.total_facture) }}</td>
              <td class="montant-col">{{ formatMontant(row.total_paye) }}</td>
              <td class="montant-col rejet-col">{{ formatMontant(row.total_rejet) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAL GÉNÉRAL</td>
              <td class="montant-col total-cell">{{ formatMontant(grandTotalFacture) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(grandTotalPaye) }}</td>
              <td class="montant-col total-cell rejet-col">{{ formatMontant(grandTotalRejet) }}</td>
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
  clients: { type: Array, default: () => [] },
  mode: { type: String, default: 'par_client' },
  periode: { type: Object, default: () => ({ debut: '', fin: '' }) },
  selectedClientId: { type: Number, default: null },
});

const selectedMode = ref(props.mode);
const selectedClientId = ref(props.selectedClientId);
const dateDebut = ref(props.periode.debut);
const dateFin = ref(props.periode.fin);
const loading = ref(false);
const showReport = ref(props.data.length > 0);
const showCriteres = ref(!showReport.value);

const grandTotalFacture = computed(() => {
  return props.data.reduce((sum, d) => sum + (d.total_facture || 0), 0);
});

const grandTotalPaye = computed(() => {
  return props.data.reduce((sum, d) => sum + (d.total_paye || 0), 0);
});

const grandTotalRejet = computed(() => {
  return props.data.reduce((sum, d) => sum + (d.total_rejet || 0), 0);
});

const openCriteres = () => {
  showCriteres.value = true;
};

const afficher = () => {
  if (selectedMode.value === 'un_client' && !selectedClientId.value) {
    ElMessage.warning('Veuillez sélectionner un client');
    return;
  }

  loading.value = true;
  const params = { mode: selectedMode.value };
  if (selectedMode.value === 'un_client') params.client_id = selectedClientId.value;
  if (selectedMode.value === 'tous_clients') {
    params.date_debut = dateDebut.value;
    params.date_fin = dateFin.value;
  }
  router.get('/rapports/clients/etat-reglements', params, {
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
  padding: 12px 24px 20px;
}

.critere-option {
  padding: 14px 0;
  border-bottom: 1px solid #f0f0f0;
}

.critere-option:last-of-type {
  border-bottom: none;
}

.critere-field {
  margin-top: 10px;
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

.critere-dates {
  padding: 16px 0 0;
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

/* Bloc client */
.client-block {
  margin-bottom: 35px;
  page-break-inside: avoid;
}

.client-header-box {
  border: 2px solid #000000;
  padding: 10px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  background: #ffffff;
}

/* Colonne Rejet en rouge */
.rejet-col {
  color: #cc0000 !important;
}

.document-title .periode {
  font-size: 13px;
  margin-top: 8px;
  opacity: 0.9;
}

@media print {
  .client-block {
    page-break-inside: avoid;
  }

  .rejet-col {
    color: #cc0000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
