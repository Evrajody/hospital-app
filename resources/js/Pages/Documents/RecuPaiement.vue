<template>
  <div class="document-container">
    <div class="document-page">
      <!-- En-tête -->
      <div class="header">
        <div class="logo-section">
          <div class="hospital-name">HÔPITAL DE MÉNONTIN</div>
          <div class="hospital-info">
            <div>République du Bénin</div>
            <div>BP 123 - Cotonou</div>
            <div>Tél: +229 21 XX XX XX</div>
          </div>
        </div>
        <div class="document-title">
          <h1>REÇU DE PAIEMENT</h1>
          <div class="document-number">N° {{ reglement.reference || reglement.id }}</div>
        </div>
      </div>

      <div class="document-date">
        Cotonou, le {{ formatDateLong(reglement.date_reglement) }}
      </div>

      <!-- Corps du document -->
      <div class="document-body">
        <div class="section">
          <h2>Informations du règlement</h2>
          <table class="info-table">
            <tr>
              <td class="label">Fournisseur :</td>
              <td class="value"><strong>{{ reglement.fournisseur.nom }}</strong></td>
            </tr>
            <tr>
              <td class="label">Code fournisseur :</td>
              <td class="value">{{ reglement.fournisseur.code }}</td>
            </tr>
            <tr>
              <td class="label">Facture N° :</td>
              <td class="value"><strong>{{ reglement.facture.numero }}</strong></td>
            </tr>
            <tr>
              <td class="label">Date de règlement :</td>
              <td class="value">{{ formatDate(reglement.date_reglement) }}</td>
            </tr>
            <tr>
              <td class="label">Mode de paiement :</td>
              <td class="value">{{ getModeLabel(reglement.mode_paiement) }}</td>
            </tr>
            <tr v-if="reglement.reference">
              <td class="label">Référence :</td>
              <td class="value">{{ reglement.reference }}</td>
            </tr>
            <tr v-if="reglement.compte_bancaire">
              <td class="label">Banque :</td>
              <td class="value">{{ reglement.compte_bancaire.banque }} - {{ reglement.compte_bancaire.numero }}</td>
            </tr>
          </table>
        </div>

        <div class="section montant-section">
          <div class="montant-box">
            <div class="montant-label">Montant réglé</div>
            <div class="montant-value">{{ formatMontant(reglement.montant) }}</div>
            <div class="montant-lettres">{{ montantEnLettres }}</div>
          </div>
        </div>

        <div class="section signature-section">
          <div class="signature-box">
            <div class="signature-label">Le Caissier</div>
            <div class="signature-space"></div>
            <div class="signature-name">{{ reglement.user?.name || 'Administrateur' }}</div>
          </div>
          <div class="signature-box">
            <div class="signature-label">Le Bénéficiaire</div>
            <div class="signature-space"></div>
            <div class="signature-name">Signature et cachet</div>
          </div>
        </div>
      </div>

      <!-- Pied de page -->
      <div class="footer">
        <div>Document généré le {{ formatDateLong(new Date()) }}</div>
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
import { router } from '@inertiajs/vue3';
import { Printer } from '@element-plus/icons-vue';

// Props
const props = defineProps({
  reglement: {
    type: Object,
    required: true
  }
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
    virement: 'Virement bancaire',
    carte: 'Carte bancaire',
    mobile_money: 'Mobile Money'
  };
  return labels[mode] || mode;
};

// Conversion montant en lettres (simplifié)
const montantEnLettres = computed(() => {
  // TODO: Implémenter une vraie conversion nombre -> lettres
  return `(${formatMontant(props.reglement.montant)} en lettres)`;
});

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
  padding-bottom: 20px;
  border-bottom: 3px solid #2563eb;
}

.hospital-name {
  font-size: 24px;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 8px;
}

.hospital-info {
  font-size: 12px;
  color: #6b7280;
  line-height: 1.6;
}

.document-title {
  text-align: right;
}

.document-title h1 {
  font-size: 28px;
  font-weight: bold;
  color: #2563eb;
  margin: 0 0 8px 0;
}

.document-number {
  font-size: 14px;
  color: #6b7280;
}

.document-date {
  text-align: right;
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 30px;
}

/* Corps */
.document-body {
  margin-bottom: 40px;
}

.section {
  margin-bottom: 30px;
}

.section h2 {
  font-size: 16px;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 15px;
  padding-bottom: 5px;
  border-bottom: 2px solid #e5e7eb;
}

.info-table {
  width: 100%;
  border-collapse: collapse;
}

.info-table tr {
  border-bottom: 1px solid #f3f4f6;
}

.info-table td {
  padding: 10px 0;
  font-size: 14px;
}

.info-table .label {
  width: 200px;
  color: #6b7280;
  font-weight: 500;
}

.info-table .value {
  color: #1f2937;
}

/* Montant */
.montant-section {
  margin: 40px 0;
}

.montant-box {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: white;
  padding: 30px;
  border-radius: 8px;
  text-align: center;
}

.montant-label {
  font-size: 16px;
  margin-bottom: 10px;
  opacity: 0.9;
}

.montant-value {
  font-size: 36px;
  font-weight: bold;
  margin-bottom: 10px;
}

.montant-lettres {
  font-size: 14px;
  opacity: 0.8;
  font-style: italic;
}

/* Signatures */
.signature-section {
  display: flex;
  justify-content: space-between;
  margin-top: 60px;
}

.signature-box {
  width: 45%;
  text-align: center;
}

.signature-label {
  font-size: 14px;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 40px;
}

.signature-space {
  height: 60px;
  border-bottom: 1px solid #d1d5db;
  margin-bottom: 10px;
}

.signature-name {
  font-size: 12px;
  color: #6b7280;
}

/* Pied de page */
.footer {
  margin-top: 60px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
  text-align: center;
  font-size: 12px;
  color: #9ca3af;
}

.print-button-container {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  justify-content: center;
}

/* Style impression */
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

  .footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
  }
}
</style>
