<template>
  <div class="document-container">
    <div class="document-page">
      <div class="header">
        <div class="logo-section">
          <div class="republic">RÉPUBLIQUE DU BÉNIN</div>
          <div class="motto">Fraternité - Justice - Travail</div>
          <div class="separator">***</div>
          <div class="hospital-name">HÔPITAL DE MÉNONTIN</div>
          <div class="hospital-info">
            <div>Service Comptabilité</div>
            <div>IFU : 0000000000000</div>
            <div>BP 123 - Cotonou - Tél: +229 21 XX XX XX</div>
          </div>
        </div>
      </div>

      <div class="document-title">
        <h1>DÉCLARATION PÉRIODIQUE DES AIB</h1>
        <div class="subtitle">Acompte sur Impôt sur les Bénéfices</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Résumé AIB -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Total Montant HT</div>
            <div class="summary-value">{{ formatMontant(totaux.ht) }}</div>
          </div>
          <div class="summary-box danger">
            <div class="summary-label">Total AIB</div>
            <div class="summary-value">{{ formatMontant(totaux.aib) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Nombre de factures</div>
            <div class="summary-value">{{ factures.length }}</div>
          </div>
        </div>
      </div>

      <!-- Détail par taux AIB -->
      <div class="section">
        <h2>DÉTAIL PAR TAUX AIB</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Taux AIB</th>
              <th class="montant-col">Nb Factures</th>
              <th class="montant-col">Montant HT</th>
              <th class="montant-col">Montant AIB</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="taux in tauxAIB" :key="taux.taux">
              <td><strong>{{ taux.taux }}%</strong></td>
              <td class="montant-col">{{ taux.nb_factures }}</td>
              <td class="montant-col">{{ formatMontant(taux.montant_ht) }}</td>
              <td class="montant-col aib-cell">{{ formatMontant(taux.montant_aib) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td class="total-label">TOTAL</td>
              <td class="montant-col total-cell">{{ factures.length }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ht) }}</td>
              <td class="montant-col total-cell aib-total">{{ formatMontant(totaux.aib) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Détail des factures -->
      <div class="section">
        <h2>DÉTAIL DES FACTURES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>N° Facture</th>
              <th>Fournisseur</th>
              <th class="montant-col">Montant HT</th>
              <th class="montant-col">Taux AIB</th>
              <th class="montant-col">Montant AIB</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="facture in factures" :key="facture.id">
              <td>{{ formatDate(facture.date_facture) }}</td>
              <td><strong>{{ facture.numero }}</strong></td>
              <td>{{ facture.fournisseur.nom }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ht) }}</td>
              <td class="montant-col">{{ facture.taux_aib }}%</td>
              <td class="montant-col aib-cell">{{ formatMontant(facture.montant_aib) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAL AIB À PAYER</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ht) }}</td>
              <td></td>
              <td class="montant-col total-cell aib-total">{{ formatMontant(totaux.aib) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Note fiscale -->
      <div class="note-fiscale">
        <strong>NOTE FISCALE :</strong> Le montant total d'AIB de <strong>{{ formatMontant(totaux.aib) }}</strong>
        doit être déclaré et versé au Trésor Public avant la date limite de déclaration.
      </div>

      <div class="footer">
        <div class="footer-line"></div>
        <div class="footer-content">
          <div>Document généré le {{ formatDateLong(new Date()) }}</div>
          <div class="page-number">Page 1/1</div>
        </div>
        <div class="print-button-container no-print">
          <el-button type="primary" :icon="Printer" @click="handlePrint">Imprimer</el-button>
          <el-button @click="handleClose">Fermer</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Printer } from '@element-plus/icons-vue';

const props = defineProps({
  factures: { type: Array, default: () => [] },
  periode: { type: Object, required: true }
});

const totaux = computed(() => ({
  ht: props.factures.reduce((sum, f) => sum + f.montant_ht, 0),
  aib: props.factures.reduce((sum, f) => sum + f.montant_aib, 0)
}));

const tauxAIB = computed(() => {
  const taux = {};
  props.factures.forEach(f => {
    if (!taux[f.taux_aib]) {
      taux[f.taux_aib] = { taux: f.taux_aib, nb_factures: 0, montant_ht: 0, montant_aib: 0 };
    }
    taux[f.taux_aib].nb_factures++;
    taux[f.taux_aib].montant_ht += f.montant_ht;
    taux[f.taux_aib].montant_aib += f.montant_aib;
  });
  return Object.values(taux).sort((a, b) => a.taux - b.taux);
});

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');
const formatDateLong = (date) => new Date(date).toLocaleDateString('fr-FR', {
  day: 'numeric', month: 'long', year: 'numeric'
});

const handlePrint = () => window.print();
const handleClose = () => window.close();
</script>

<style scoped>
@import url('./rapports-styles.css');

.republic {
  font-size: 14px;
  font-weight: bold;
  text-align: center;
  color: #1f2937;
  text-transform: uppercase;
}

.motto {
  font-size: 11px;
  text-align: center;
  color: #6b7280;
  font-style: italic;
  margin: 4px 0;
}

.separator {
  text-align: center;
  margin: 6px 0;
  color: #9ca3af;
}

.document-title .periode {
  font-size: 13px;
  margin-top: 8px;
  opacity: 0.9;
}

.summary-section {
  margin-bottom: 30px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.summary-box {
  background: #f9fafb;
  padding: 20px;
  border-radius: 8px;
  border-left: 4px solid #6b7280;
  text-align: center;
}

.summary-box.danger { border-left-color: #dc2626; }

.summary-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 20px;
  font-weight: bold;
  color: #1f2937;
}

.aib-cell {
  color: #dc2626;
  font-weight: bold;
}

.aib-total {
  color: #dc2626 !important;
  font-size: 16px !important;
}

.note-fiscale {
  margin-top: 30px;
  padding: 15px;
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
  font-size: 12px;
  color: #78350f;
  line-height: 1.6;
}
</style>
