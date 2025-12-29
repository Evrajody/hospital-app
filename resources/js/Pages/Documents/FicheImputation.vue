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
            <div>Service Comptabilité</div>
            <div>BP 123 - Cotonou</div>
            <div>Tél: +229 21 XX XX XX</div>
          </div>
        </div>
        <div class="document-ref">
          <div class="ref-number">FI-{{ String(reglement.id).padStart(6, '0') }}</div>
          <div class="ref-year">{{ new Date(reglement.date_reglement).getFullYear() }}</div>
        </div>
      </div>

      <div class="document-title-section">
        <h1>FICHE D'IMPUTATION COMPTABLE</h1>
        <div class="document-subtitle">Règlement Fournisseurs</div>
      </div>

      <div class="document-date">
        Cotonou, le {{ formatDateLong(reglement.date_reglement) }}
      </div>

      <!-- Informations du règlement -->
      <div class="document-body">
        <div class="section info-section">
          <h2>INFORMATIONS DU RÈGLEMENT</h2>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Fournisseur :</span>
              <span class="info-value"><strong>{{ reglement.fournisseur.nom }}</strong></span>
            </div>
            <div class="info-item">
              <span class="info-label">Code :</span>
              <span class="info-value">{{ reglement.fournisseur.code }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Facture N° :</span>
              <span class="info-value"><strong>{{ reglement.facture.numero }}</strong></span>
            </div>
            <div class="info-item">
              <span class="info-label">Date facture :</span>
              <span class="info-value">{{ formatDate(reglement.facture.date_facture) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Date règlement :</span>
              <span class="info-value">{{ formatDate(reglement.date_reglement) }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Mode paiement :</span>
              <span class="info-value">{{ getModeLabel(reglement.mode_paiement) }}</span>
            </div>
            <div class="info-item" v-if="reglement.reference">
              <span class="info-label">Référence :</span>
              <span class="info-value">{{ reglement.reference }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Montant :</span>
              <span class="info-value montant-highlight">{{ formatMontant(reglement.montant) }}</span>
            </div>
          </div>
        </div>

        <!-- Table d'imputation -->
        <div class="section">
          <h2>IMPUTATION COMPTABLE</h2>
          <table class="imputation-table">
            <thead>
              <tr>
                <th class="compte-col">N° Compte</th>
                <th class="libelle-col">Libellé du compte</th>
                <th class="montant-col">Débit</th>
                <th class="montant-col">Crédit</th>
              </tr>
            </thead>
            <tbody>
              <!-- Débit fournisseur -->
              <tr>
                <td class="compte-cell">{{ reglement.fournisseur.compte_numero || '401XXX' }}</td>
                <td class="libelle-cell">
                  Fournisseurs - {{ reglement.fournisseur.nom }}
                  <div class="detail-cell">Règlement facture {{ reglement.facture.numero }}</div>
                </td>
                <td class="montant-cell debit-cell">{{ formatMontant(reglement.montant) }}</td>
                <td class="montant-cell"></td>
              </tr>

              <!-- Crédit selon mode de paiement -->
              <tr>
                <td class="compte-cell">{{ getCompteCredit(reglement.mode_paiement) }}</td>
                <td class="libelle-cell">
                  {{ getLibelleCredit(reglement.mode_paiement) }}
                  <div class="detail-cell" v-if="reglement.compte_bancaire">
                    {{ reglement.compte_bancaire.banque }} - {{ reglement.compte_bancaire.numero }}
                  </div>
                  <div class="detail-cell" v-if="reglement.reference">
                    Réf: {{ reglement.reference }}
                  </div>
                </td>
                <td class="montant-cell"></td>
                <td class="montant-cell credit-cell">{{ formatMontant(reglement.montant) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="total-row">
                <td colspan="2" class="total-label">TOTAUX</td>
                <td class="montant-cell total-cell">{{ formatMontant(reglement.montant) }}</td>
                <td class="montant-cell total-cell">{{ formatMontant(reglement.montant) }}</td>
              </tr>
            </tfoot>
          </table>

          <div class="equilibre-note">
            <el-icon class="success-icon"><CircleCheck /></el-icon>
            Équilibre comptable vérifié : Débit = Crédit
          </div>
        </div>

        <!-- Pièces justificatives -->
        <div class="section">
          <h2>PIÈCES JUSTIFICATIVES</h2>
          <div class="pieces-list">
            <div class="piece-item">
              <el-icon class="piece-icon"><Document /></el-icon>
              Facture fournisseur N° {{ reglement.facture.numero }}
            </div>
            <div class="piece-item">
              <el-icon class="piece-icon"><DocumentCopy /></el-icon>
              Mandat de paiement N° {{ String(reglement.id).padStart(6, '0') }}
            </div>
            <div class="piece-item" v-if="reglement.mode_paiement === 'cheque'">
              <el-icon class="piece-icon"><Tickets /></el-icon>
              Chèque N° {{ reglement.reference || '______' }}
            </div>
            <div class="piece-item" v-if="reglement.mode_paiement === 'virement'">
              <el-icon class="piece-icon"><Postcard /></el-icon>
              Ordre de virement N° {{ reglement.reference || '______' }}
            </div>
          </div>
        </div>

        <!-- Visas et signatures -->
        <div class="section signature-section">
          <div class="signature-row">
            <div class="signature-box">
              <div class="signature-label">Préparé par</div>
              <div class="signature-sub">Le Comptable</div>
              <div class="signature-space"></div>
              <div class="signature-name">Nom et signature</div>
              <div class="signature-date">Date : ___/___/______</div>
            </div>
            <div class="signature-box">
              <div class="signature-label">Vérifié par</div>
              <div class="signature-sub">Le Chef Comptable</div>
              <div class="signature-space"></div>
              <div class="signature-name">Nom et signature</div>
              <div class="signature-date">Date : ___/___/______</div>
            </div>
            <div class="signature-box">
              <div class="signature-label">Approuvé par</div>
              <div class="signature-sub">Le DAF</div>
              <div class="signature-space"></div>
              <div class="signature-name">Nom et signature</div>
              <div class="signature-date">Date : ___/___/______</div>
            </div>
          </div>
        </div>

        <!-- Note comptable -->
        <div class="comptable-note">
          <strong>NOTE COMPTABLE :</strong> Cette fiche d'imputation constitue une pièce comptable justificative
          de l'enregistrement du règlement fournisseur. Elle doit être classée avec les pièces justificatives de la période.
        </div>
      </div>

      <!-- Pied de page -->
      <div class="footer">
        <div class="footer-line"></div>
        <div class="footer-content">
          <div>Document généré le {{ formatDateLong(new Date()) }}</div>
          <div class="confidential">Document comptable - À conserver</div>
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
import { Printer, CircleCheck, Document, DocumentCopy, Tickets, Postcard } from '@element-plus/icons-vue';

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

// Obtenir le compte de crédit selon le mode de paiement
const getCompteCredit = (mode) => {
  const comptes = {
    especes: '571000',
    cheque: '521000',
    virement: '521000',
    carte: '521000',
    mobile_money: '521500'
  };
  return comptes[mode] || '521000';
};

// Obtenir le libellé du compte de crédit
const getLibelleCredit = (mode) => {
  const libelles = {
    especes: 'Caisse - Espèces',
    cheque: 'Banque - Compte courant',
    virement: 'Banque - Compte courant',
    carte: 'Banque - Compte courant',
    mobile_money: 'Banque - Mobile Money'
  };
  return libelles[mode] || 'Banque';
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
  font-family: 'Times New Roman', serif;
}

.document-page {
  max-width: 210mm;
  min-height: 297mm;
  margin: 0 auto;
  background: white;
  padding: 20mm;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* En-tête officiel */
.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  padding-bottom: 15px;
  border-bottom: 3px double #1f2937;
}

.republic {
  font-size: 15px;
  font-weight: bold;
  text-align: center;
  color: #1f2937;
  text-transform: uppercase;
  letter-spacing: 1px;
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
  font-size: 12px;
  color: #9ca3af;
  margin: 6px 0;
}

.hospital-name {
  font-size: 17px;
  font-weight: bold;
  text-align: center;
  color: #1f2937;
  margin: 8px 0;
  text-transform: uppercase;
}

.hospital-info {
  font-size: 10px;
  text-align: center;
  color: #6b7280;
  line-height: 1.6;
}

.document-ref {
  text-align: right;
  border: 2px solid #059669;
  padding: 12px;
  min-width: 100px;
  background: #f0fdf4;
}

.ref-number {
  font-size: 15px;
  font-weight: bold;
  color: #059669;
}

.ref-year {
  font-size: 11px;
  color: #6b7280;
  margin-top: 4px;
}

/* Titre du document */
.document-title-section {
  text-align: center;
  margin: 25px 0;
  padding: 18px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border-radius: 4px;
}

.document-title-section h1 {
  font-size: 26px;
  font-weight: bold;
  margin: 0 0 6px 0;
  letter-spacing: 2px;
}

.document-subtitle {
  font-size: 13px;
  opacity: 0.9;
}

.document-date {
  text-align: right;
  font-size: 13px;
  color: #1f2937;
  margin-bottom: 25px;
  font-weight: 500;
}

/* Corps */
.document-body {
  margin-bottom: 40px;
}

.section {
  margin-bottom: 25px;
}

.section h2 {
  font-size: 13px;
  font-weight: bold;
  color: white;
  background: #059669;
  padding: 7px 12px;
  margin-bottom: 15px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Section info */
.info-section {
  background: #f9fafb;
  padding: 20px;
  border-left: 4px solid #059669;
  margin-bottom: 30px;
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

.info-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  min-width: 140px;
}

.info-value {
  font-size: 12px;
  color: #1f2937;
  flex: 1;
}

.montant-highlight {
  color: #059669;
  font-weight: bold;
  font-size: 14px;
}

/* Table d'imputation */
.imputation-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  margin-bottom: 15px;
}

.imputation-table thead {
  background: #1f2937;
  color: white;
}

.imputation-table th {
  padding: 10px;
  text-align: left;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 0.5px;
}

.compte-col {
  width: 100px;
}

.libelle-col {
  width: auto;
}

.montant-col {
  width: 140px;
  text-align: right;
}

.imputation-table tbody tr {
  border-bottom: 1px solid #e5e7eb;
}

.imputation-table tbody tr:hover {
  background: #f9fafb;
}

.compte-cell {
  padding: 12px 10px;
  font-weight: bold;
  color: #374151;
  font-family: 'Courier New', monospace;
}

.libelle-cell {
  padding: 12px 10px;
  color: #1f2937;
}

.detail-cell {
  font-size: 10px;
  color: #6b7280;
  margin-top: 3px;
  font-style: italic;
}

.montant-cell {
  padding: 12px 10px;
  text-align: right;
  font-weight: 600;
}

.debit-cell {
  color: #dc2626;
  background: #fef2f2;
}

.credit-cell {
  color: #059669;
  background: #f0fdf4;
}

.imputation-table tfoot {
  background: #f3f4f6;
  border-top: 2px solid #1f2937;
}

.total-row {
  font-weight: bold;
}

.total-label {
  padding: 12px 10px;
  text-transform: uppercase;
  font-size: 12px;
  color: #1f2937;
}

.total-cell {
  font-size: 13px;
  color: #1f2937;
  background: #e5e7eb;
}

.equilibre-note {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #f0fdf4;
  border: 1px solid #059669;
  border-radius: 4px;
  color: #059669;
  font-size: 12px;
  font-weight: 600;
}

.success-icon {
  font-size: 18px;
}

/* Pièces justificatives */
.pieces-list {
  display: grid;
  gap: 10px;
}

.piece-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: #f9fafb;
  border-left: 3px solid #6b7280;
  font-size: 12px;
  color: #374151;
}

.piece-icon {
  font-size: 16px;
  color: #6b7280;
}

/* Signatures */
.signature-section {
  margin-top: 40px;
}

.signature-row {
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.signature-box {
  flex: 1;
  text-align: center;
  padding: 12px;
  border: 1px solid #e5e7eb;
  background: #fafafa;
}

.signature-label {
  font-size: 12px;
  font-weight: bold;
  color: #1f2937;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.signature-sub {
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 25px;
  font-style: italic;
}

.signature-space {
  height: 50px;
  margin: 20px 0 10px 0;
}

.signature-name {
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 4px;
}

.signature-date {
  font-size: 10px;
  color: #9ca3af;
}

/* Note comptable */
.comptable-note {
  margin-top: 30px;
  padding: 12px;
  background: #dbeafe;
  border-left: 4px solid #2563eb;
  font-size: 11px;
  color: #1e40af;
  line-height: 1.6;
}

/* Pied de page */
.footer {
  margin-top: 50px;
}

.footer-line {
  height: 2px;
  background: linear-gradient(to right, #059669, transparent);
  margin-bottom: 12px;
}

.footer-content {
  text-align: center;
  font-size: 10px;
  color: #9ca3af;
  margin-bottom: 15px;
}

.confidential {
  margin-top: 4px;
  font-style: italic;
  color: #059669;
  font-weight: 600;
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
