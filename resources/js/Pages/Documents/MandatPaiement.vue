<template>
  <div class="document-container">
    <div class="document-page">
      <!-- En-tête officiel -->
      <div class="header">
        <div class="logo-section">
          <div class="republic">RÉPUBLIQUE DU BÉNIN</div>
          <div class="motto">Fraternité - Justice - Travail</div>
          <div class="separator">***</div>
          <div class="hospital-name">HÔPITAL DE MÉNONTIN</div>
          <div class="hospital-info">
            <div>Service Comptabilité et Finances</div>
            <div>BP 123 - Cotonou</div>
            <div>Tél: +229 21 XX XX XX</div>
          </div>
        </div>
        <div class="document-ref">
          <div class="ref-number">N° {{ String(reglement.id).padStart(6, '0') }}</div>
          <div class="ref-year">Année {{ new Date(reglement.date_reglement).getFullYear() }}</div>
        </div>
      </div>

      <div class="document-title-section">
        <h1 style="color: black;">MANDAT DE PAIEMENT</h1>
        <div style="color: black;" class="document-subtitle">Ordre de paiement aux fournisseurs</div>
      </div>

      <div class="document-date">
        Cotonou, le {{ formatDateLong(reglement.date_reglement) }}
      </div>

      <!-- Corps du mandat -->
      <div class="document-body">
        <!-- Objet -->
        <div class="section objet-section">
          <div class="objet-title">OBJET :</div>
          <div class="objet-content">
            Règlement de la facture N° <strong>{{ reglement.facture.numero }}</strong>
          </div>
        </div>

        <!-- Bénéficiaire -->
        <div class="section">
          <h2>BÉNÉFICIAIRE</h2>
          <table class="info-table">
            <tr>
              <td class="label">Raison sociale :</td>
              <td class="value"><strong>{{ reglement.fournisseur.nom }}</strong></td>
            </tr>
            <tr>
              <td class="label">Code fournisseur :</td>
              <td class="value">{{ reglement.fournisseur.code }}</td>
            </tr>
            <tr v-if="reglement.fournisseur.ifu">
              <td class="label">IFU :</td>
              <td class="value">{{ reglement.fournisseur.ifu }}</td>
            </tr>
            <tr v-if="reglement.fournisseur.rccm">
              <td class="label">RCCM :</td>
              <td class="value">{{ reglement.fournisseur.rccm }}</td>
            </tr>
          </table>
        </div>

        <!-- Détails du paiement -->
        <div class="section">
          <h2>DÉTAILS DU PAIEMENT</h2>
          <table class="info-table">
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
              <td class="value"><strong>{{ getModeLabel(reglement.mode_paiement).toUpperCase() }}</strong></td>
            </tr>
            <tr v-if="reglement.reference">
              <td class="label">Référence :</td>
              <td class="value">{{ reglement.reference }}</td>
            </tr>
            <tr v-if="reglement.compte_bancaire">
              <td class="label">Compte bancaire :</td>
              <td class="value">
                <div><strong>{{ reglement.compte_bancaire.banque }}</strong></div>
                <div style="font-size: 12px; color: #666666;">{{ reglement.compte_bancaire.numero }}</div>
              </td>
            </tr>
          </table>
        </div>

        <!-- Montant -->
        <div class="section montant-section">
          <div class="montant-box">
            <div class="montant-row">
              <span class="montant-label">MONTANT À PAYER :</span>
              <span class="montant-value">{{ formatMontant(reglement.montant) }}</span>
            </div>
            <div class="montant-lettres">
              Arrêté le présent mandat à la somme de : <strong>{{ montantEnLettres }}</strong>
            </div>
          </div>
        </div>

        <!-- Visas et signatures -->
        <div class="section signature-section">
          <div class="signature-row">
            <div class="signature-box">
              <div class="signature-label">Le Comptable</div>
              <div class="signature-space"></div>
              <div class="signature-name">Nom et signature</div>
              <div class="signature-date">Date : ___/___/______</div>
            </div>
            <div class="signature-box">
              <div class="signature-label">Le Directeur des Affaires Financières</div>
              <div class="signature-space"></div>
              <div class="signature-name">Nom et signature</div>
              <div class="signature-date">Date : ___/___/______</div>
            </div>
          </div>

          <div class="signature-row" style="margin-top: 20px;">
            <div class="signature-box signature-box-wide">
              <div class="signature-label">Le Directeur Général</div>
              <div class="signature-label-sub">(Approbation et autorisation de paiement)</div>
              <div class="signature-space"></div>
              <div class="signature-name">Nom et signature</div>
              <div class="signature-date">Date : ___/___/______</div>
            </div>
          </div>
        </div>

        <!-- Note officielle -->
        <div class="official-note">
          <strong>NOTE :</strong> Ce mandat autorise le paiement de la somme mentionnée ci-dessus au bénéficiaire désigné.
          Tout paiement effectué sans ce mandat dûment signé et validé sera considéré comme irrégulier.
        </div>
      </div>

      <!-- Pied de page -->
      <div class="footer">
        <div class="footer-line"></div>
        <div class="footer-content">
          <div>Document généré le {{ formatDateLong(new Date()) }}</div>
          <div class="confidential">Document confidentiel - Usage interne uniquement</div>
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
  return `${formatMontant(props.reglement.montant)} (montant en lettres)`;
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
  font-family: 'Times New Roman', serif;
}

.document-page {
  max-width: 210mm;
  max-height: 297mm;
  margin: 0 auto;
  background: white;
  padding: 15mm;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* En-tête officiel */
.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 3px double #000000;
}

.republic {
  font-size: 16px;
  font-weight: bold;
  text-align: center;
  color: #000000;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.motto {
  font-size: 12px;
  text-align: center;
  color: #666666;
  font-style: italic;
  margin: 4px 0;
}

.separator {
  text-align: center;
  font-size: 14px;
  color: #666666;
  margin: 8px 0;
}

.hospital-name {
  font-size: 18px;
  font-weight: bold;
  text-align: center;
  color: #000000;
  margin: 10px 0 8px 0;
  text-transform: uppercase;
}

.hospital-info {
  font-size: 11px;
  text-align: center;
  color: #666666;
  line-height: 1.6;
}

.document-ref {
  text-align: right;
  border: 2px solid #000000;
  padding: 15px;
  min-width: 120px;
}

.ref-number {
  font-size: 16px;
  font-weight: bold;
  color: #000000;
}

.ref-year {
  font-size: 12px;
  color: #666666;
  margin-top: 5px;
}

/* Titre du document */
.document-title-section {
  text-align: center;
  margin: 15px 0;
  padding: 10px;
  background: none;
  color: white;
  border-radius: 0;
}

.document-title-section h1 {
  font-size: 24px;
  font-weight: bold;
  margin: 0 0 4px 0;
  letter-spacing: 2px;
}

.document-subtitle {
  font-size: 13px;
  opacity: 0.9;
}

.document-date {
  text-align: right;
  font-size: 13px;
  color: #000000;
  margin-bottom: 15px;
  font-weight: 500;
}

/* Corps */
.document-body {
  margin-bottom: 40px;
}

.section {
  margin-bottom: 15px;
}

.objet-section {
  background: #f5f5f5;
  padding: 12px;
  border-left: 4px solid #000000;
  margin-bottom: 15px;
}

.objet-title {
  font-size: 14px;
  font-weight: bold;
  color: #000000;
  margin-bottom: 8px;
}

.objet-content {
  font-size: 14px;
  color: #333333;
  line-height: 1.6;
}

.section h2 {
  font-size: 14px;
  font-weight: bold;
  color: white;
  background: #333333;
  padding: 8px 12px;
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.info-table {
  width: 100%;
  border-collapse: collapse;
}

.info-table tr {
  border-bottom: 1px solid #cccccc;
}

.info-table td {
  padding: 8px 0;
  font-size: 12px;
}

.info-table .label {
  width: 220px;
  color: #666666;
  font-weight: 600;
}

.info-table .value {
  color: #000000;
}

/* Montant */
.montant-section {
  margin: 15px 0;
}

.montant-box {
  border: 3px double #000000;
  padding: 15px;
}

.montant-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 2px solid #cccccc;
}

.montant-label {
  font-size: 14px;
  font-weight: bold;
  color: #000000;
  text-transform: uppercase;
}

.montant-value {
  font-size: 28px;
  font-weight: bold;
  color: #000000;
}

.montant-lettres {
  font-size: 13px;
  color: #333333;
  line-height: 1.6;
  font-style: italic;
}

/* Signatures */
.signature-section {
  margin-top: 25px;
}

.signature-row {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.signature-box {
  flex: 1;
  text-align: center;
  padding: 15px;
  border: 1px solid #cccccc;
  background: #f5f5f5;
}

.signature-box-wide {
  max-width: 60%;
  margin: 0 auto;
}

.signature-label {
  font-size: 13px;
  font-weight: bold;
  color: #000000;
  margin-bottom: 5px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.signature-label-sub {
  font-size: 11px;
  color: #666666;
  font-weight: normal;
  font-style: italic;
  margin-bottom: 30px;
}

.signature-space {
  height: 40px;
  margin: 15px 0 10px 0;
}

.signature-name {
  font-size: 12px;
  color: #666666;
  margin-bottom: 5px;
}

.signature-date {
  font-size: 11px;
  color: #666666;
}

/* Note officielle */
.official-note {
  margin-top: 15px;
  padding: 15px;
  background: #eeeeee;
  border-left: 4px solid #000000;
  font-size: 11px;
  color: #000000;
  line-height: 1.6;
}

/* Pied de page */
.footer {
  margin-top: 60px;
}

.footer-line {
  height: 2px;
  background: none;
  margin-bottom: 15px;
}

.footer-content {
  text-align: center;
  font-size: 11px;
  color: #666666;
  margin-bottom: 20px;
}

.confidential {
  margin-top: 5px;
  font-style: italic;
  color: #000000;
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

  @page {
    margin: 15mm;
  }
}
</style>
