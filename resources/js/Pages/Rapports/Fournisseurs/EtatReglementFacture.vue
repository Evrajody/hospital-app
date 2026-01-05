<template>
  <div class="document-container">
    <div class="document-page">
      <!-- En-tête -->
      <div class="header">
        <div class="logo-section">
          <div class="hospital-name">HÔPITAL DE MÉNONTIN</div>
          <div class="hospital-info">
            <div>République du Bénin</div>
            <div>Service Comptabilité</div>
            <div>BP 123 - Cotonou - Tél: +229 21 XX XX XX</div>
          </div>
        </div>
        <div class="document-ref">
          <div class="ref-date">{{ formatDateLong(new Date()) }}</div>
        </div>
      </div>

      <div class="document-title">
        <h1>ÉTAT DE RÈGLEMENT D'UNE FACTURE</h1>
        <div class="subtitle">Détails des paiements effectués</div>
      </div>

      <!-- Informations facture -->
      <div class="section info-section">
        <h2>INFORMATIONS FACTURE</h2>
        <div class="info-grid">
          <div class="info-item">
            <span class="label">N° Facture :</span>
            <span class="value"><strong>{{ facture.numero }}</strong></span>
          </div>
          <div class="info-item">
            <span class="label">Date facture :</span>
            <span class="value">{{ formatDate(facture.date_facture) }}</span>
          </div>
          <div class="info-item">
            <span class="label">Fournisseur :</span>
            <span class="value"><strong>{{ facture.fournisseur.nom }}</strong></span>
          </div>
          <div class="info-item">
            <span class="label">Code fournisseur :</span>
            <span class="value">{{ facture.fournisseur.code }}</span>
          </div>
          <div class="info-item">
            <span class="label">Référence :</span>
            <span class="value">{{ facture.reference || 'N/A' }}</span>
          </div>
          <div class="info-item">
            <span class="label">Date échéance :</span>
            <span class="value">{{ formatDate(facture.date_echeance) }}</span>
          </div>
        </div>
      </div>

      <!-- Récapitulatif financier -->
      <div class="section">
        <h2>RÉCAPITULATIF FINANCIER</h2>
        <table class="summary-table">
          <tr>
            <td class="label-cell">Montant HT</td>
            <td class="value-cell">{{ formatMontant(facture.montant_ht) }}</td>
          </tr>
          <tr>
            <td class="label-cell">TVA (18%)</td>
            <td class="value-cell">{{ formatMontant(facture.montant_tva) }}</td>
          </tr>
          <tr>
            <td class="label-cell">AIB</td>
            <td class="value-cell">{{ formatMontant(facture.montant_aib) }}</td>
          </tr>
          <tr>
            <td class="label-cell">Escompte</td>
            <td class="value-cell">{{ formatMontant(facture.montant_escompte) }}</td>
          </tr>
          <tr class="total-row">
            <td class="label-cell"><strong>Montant TTC</strong></td>
            <td class="value-cell"><strong>{{ formatMontant(facture.montant_ttc) }}</strong></td>
          </tr>
        </table>
      </div>

      <!-- Historique des règlements -->
      <div class="section">
        <h2>HISTORIQUE DES RÈGLEMENTS</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Mode paiement</th>
              <th>Référence</th>
              <th>Banque</th>
              <th class="montant-col">Montant</th>
              <th>Utilisateur</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reglement in reglements" :key="reglement.id">
              <td>{{ formatDate(reglement.date_reglement) }}</td>
              <td>
                <el-tag :type="getModeType(reglement.mode_paiement)" size="small">
                  {{ getModeLabel(reglement.mode_paiement) }}
                </el-tag>
              </td>
              <td>{{ reglement.reference || '-' }}</td>
              <td>{{ reglement.compte_bancaire?.banque || '-' }}</td>
              <td class="montant-col">{{ formatMontant(reglement.montant) }}</td>
              <td>{{ reglement.user?.name || 'N/A' }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="total-label">TOTAL PAYÉ</td>
              <td class="montant-col total-cell">{{ formatMontant(totalPaye) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Statut de paiement -->
      <div class="section status-section">
        <div class="status-box">
          <div class="status-row">
            <span class="status-label">Montant total facture :</span>
            <span class="status-value">{{ formatMontant(facture.montant_ttc) }}</span>
          </div>
          <div class="status-row">
            <span class="status-label">Total payé :</span>
            <span class="status-value paid">{{ formatMontant(totalPaye) }}</span>
          </div>
          <div class="status-row reste">
            <span class="status-label"><strong>Reste à payer :</strong></span>
            <span class="status-value" :class="reste > 0 ? 'unpaid' : 'paid'">
              <strong>{{ formatMontant(reste) }}</strong>
            </span>
          </div>
        </div>

        <div class="status-badge">
          <el-tag :type="getStatusType()" size="large">
            {{ getStatusLabel() }}
          </el-tag>
        </div>
      </div>

      <!-- Pied de page -->
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

// Props
const props = defineProps({
  facture: {
    type: Object,
    required: true
  },
  reglements: {
    type: Array,
    default: () => []
  }
});

// Computed
const totalPaye = computed(() => {
  return props.reglements.reduce((sum, r) => sum + r.montant, 0);
});

const reste = computed(() => {
  return props.facture.montant_ttc - totalPaye.value;
});

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateLong = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

const getModeLabel = (mode) => {
  const labels = {
    especes: 'Espèces',
    cheque: 'Chèque',
    virement: 'Virement',
    carte: 'Carte',
    mobile_money: 'Mobile Money'
  };
  return labels[mode] || mode;
};

const getModeType = (mode) => {
  const types = {
    especes: 'warning',
    cheque: 'info',
    virement: 'success',
    carte: 'primary',
    mobile_money: 'warning'
  };
  return types[mode] || 'info';
};

const getStatusType = () => {
  if (reste.value === 0) return 'success';
  if (totalPaye.value > 0) return 'warning';
  return 'danger';
};

const getStatusLabel = () => {
  if (reste.value === 0) return 'FACTURE RÉGLÉE';
  if (totalPaye.value > 0) return 'PARTIELLEMENT RÉGLÉE';
  return 'NON RÉGLÉE';
};

const handlePrint = () => {
  window.print();
};

const handleClose = () => {
  window.close();
};
</script>

<style scoped>
.document-container {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 20px;
  font-family: 'Arial', sans-serif;
}

.document-page {
  max-width: 210mm;
  min-height: 297mm;
  margin: 0 auto;
  background: white;
  padding: 20mm;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* En-tête */
.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  padding-bottom: 15px;
  border-bottom: 3px solid #2563eb;
}

.hospital-name {
  font-size: 22px;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 8px;
}

.hospital-info {
  font-size: 11px;
  color: #6b7280;
  line-height: 1.6;
}

.document-ref {
  text-align: right;
}

.ref-date {
  font-size: 12px;
  color: #6b7280;
}

/* Titre */
.document-title {
  text-align: center;
  margin: 25px 0;
  padding: 18px;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  border-radius: 4px;
}

.document-title h1 {
  font-size: 24px;
  font-weight: bold;
  margin: 0 0 6px 0;
  letter-spacing: 1px;
}

.subtitle {
  font-size: 13px;
  opacity: 0.9;
}

/* Sections */
.section {
  margin-bottom: 25px;
}

.section h2 {
  font-size: 13px;
  font-weight: bold;
  color: white;
  background: #1f2937;
  padding: 8px 12px;
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Info section */
.info-section {
  background: #f9fafb;
  padding: 20px;
  border-left: 4px solid #2563eb;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.info-item {
  display: flex;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}

.info-item .label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  min-width: 140px;
}

.info-item .value {
  font-size: 12px;
  color: #1f2937;
}

/* Tables */
.summary-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 10px;
}

.summary-table tr {
  border-bottom: 1px solid #e5e7eb;
}

.summary-table .label-cell {
  padding: 10px;
  color: #6b7280;
  font-size: 13px;
}

.summary-table .value-cell {
  padding: 10px;
  text-align: right;
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
}

.summary-table .total-row {
  background: #f3f4f6;
  border-top: 2px solid #1f2937;
}

.summary-table .total-row .value-cell {
  color: #2563eb;
  font-size: 15px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.data-table thead {
  background: #1f2937;
  color: white;
}

.data-table th {
  padding: 10px;
  text-align: left;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 11px;
}

.data-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.data-table td {
  padding: 10px;
}

.montant-col {
  text-align: right;
  font-weight: 600;
}

.data-table tfoot {
  background: #f3f4f6;
  border-top: 2px solid #1f2937;
  font-weight: bold;
}

.total-label {
  text-align: right;
  padding: 12px;
  text-transform: uppercase;
  font-size: 12px;
}

.total-cell {
  color: #2563eb;
  font-size: 14px;
}

/* Status section */
.status-section {
  margin-top: 30px;
}

.status-box {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 15px;
}

.status-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid #e5e7eb;
}

.status-row.reste {
  border-bottom: none;
  margin-top: 10px;
  padding-top: 15px;
  border-top: 2px solid #1f2937;
}

.status-label {
  font-size: 14px;
  color: #6b7280;
}

.status-value {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.status-value.paid {
  color: #059669;
}

.status-value.unpaid {
  color: #dc2626;
}

.status-badge {
  text-align: center;
  padding: 15px;
}

/* Footer */
.footer {
  margin-top: 50px;
}

.footer-line {
  height: 2px;
  background: linear-gradient(to right, #2563eb, transparent);
  margin-bottom: 12px;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #9ca3af;
  margin-bottom: 15px;
}

.print-button-container {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  justify-content: center;
}

/* Print styles */
@media print {
  .document-container {
    background: white;
    padding: 0;
  }

  .document-page {
    box-shadow: none;
    padding: 15mm;
    margin: 0;
  }

  .no-print {
    display: none !important;
  }

  @page {
    margin: 15mm;
  }
}
</style>
