<template>
  <div class="document-container">
    <div class="document-page">
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
        <h1>ÉTAT PÉRIODIQUE DES FACTURES RÉGLÉES</h1>
        <div class="subtitle">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Statistiques -->
      <div class="section stats-section">
        <div class="stats-grid">
          <div class="stat-box">
            <div class="stat-label">Nombre de factures réglées</div>
            <div class="stat-value">{{ factures.length }}</div>
          </div>
          <div class="stat-box">
            <div class="stat-label">Montant total facturé</div>
            <div class="stat-value">{{ formatMontant(totaux.ttc) }}</div>
          </div>
          <div class="stat-box success">
            <div class="stat-label">Montant total payé</div>
            <div class="stat-value">{{ formatMontant(totaux.paye) }}</div>
          </div>
        </div>
      </div>

      <!-- Liste des factures -->
      <div class="section">
        <h2>DÉTAIL DES FACTURES RÉGLÉES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date règlement</th>
              <th>N° Facture</th>
              <th>Fournisseur</th>
              <th class="montant-col">Montant TTC</th>
              <th class="montant-col">Payé</th>
              <th>Mode paiement</th>
              <th>Référence</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="facture in factures" :key="facture.id">
              <td>{{ formatDate(facture.date_reglement_final) }}</td>
              <td><strong>{{ facture.numero }}</strong></td>
              <td>{{ facture.fournisseur.nom }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ttc) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_paye) }}</td>
              <td>{{ facture.mode_paiement_final }}</td>
              <td>{{ facture.reference_final || '-' }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAUX</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ttc) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.paye) }}</td>
              <td colspan="2"></td>
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
  ttc: props.factures.reduce((sum, f) => sum + f.montant_ttc, 0),
  paye: props.factures.reduce((sum, f) => sum + f.montant_paye, 0)
}));

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

.stats-section {
  margin-bottom: 30px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 15px;
}

.stat-box {
  background: #f9fafb;
  padding: 20px;
  border-radius: 8px;
  border-left: 4px solid #6b7280;
  text-align: center;
}

.stat-box.success { border-left-color: #059669; }

.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 20px;
  font-weight: bold;
  color: #1f2937;
}
</style>
