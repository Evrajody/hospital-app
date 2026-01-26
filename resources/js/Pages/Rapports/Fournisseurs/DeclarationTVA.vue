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
        <h1>DÉCLARATION DE LA TVA</h1>
        <div class="subtitle">Taxe sur la Valeur Ajoutée</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Résumé TVA - Commenté pour l'impression
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Total Base HT</div>
            <div class="summary-value">{{ formatMontant(totaux.ht) }}</div>
          </div>
          <div class="summary-box warning">
            <div class="summary-label">Total TVA Collectée (18%)</div>
            <div class="summary-value">{{ formatMontant(totaux.tva) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Nombre de factures</div>
            <div class="summary-value">{{ factures.length }}</div>
          </div>
        </div>
      </div>
      -->

      <!-- Détail des factures -->
      <div class="section">
        <h2>DÉTAIL DES FACTURES AVEC TVA</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>N° Facture</th>
              <th>Fournisseur</th>
              <th class="montant-col">Base HT</th>
              <th class="montant-col">Taux</th>
              <th class="montant-col">Montant TVA</th>
              <th class="montant-col">TTC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="facture in factures" :key="facture.id">
              <td>{{ formatDate(facture.date_facture) }}</td>
              <td><strong>{{ facture.numero }}</strong></td>
              <td>{{ facture.fournisseur.nom }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ht) }}</td>
              <td class="montant-col">18%</td>
              <td class="montant-col tva-cell">{{ formatMontant(facture.montant_tva) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ttc) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAL</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ht) }}</td>
              <td></td>
              <td class="montant-col total-cell tva-total">{{ formatMontant(totaux.tva) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ttc) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Récapitulatif TVA -->
      <div class="section recapitulatif-tva">
        <h2>RÉCAPITULATIF DE LA DÉCLARATION</h2>
        <div class="tva-calculation">
          <div class="tva-row">
            <div class="tva-label">Total Base HT (achats)</div>
            <div class="tva-value">{{ formatMontant(totaux.ht) }}</div>
          </div>
          <div class="tva-row">
            <div class="tva-label">TVA Collectée (18%)</div>
            <div class="tva-value tva-collectee">{{ formatMontant(totaux.tva) }}</div>
          </div>
          <div class="tva-row">
            <div class="tva-label">TVA Déductible (achats)</div>
            <div class="tva-value">{{ formatMontant(totaux.tva) }}</div>
          </div>
          <div class="tva-row total-tva">
            <div class="tva-label"><strong>TVA à décaisser</strong></div>
            <div class="tva-value"><strong>{{ formatMontant(0) }}</strong></div>
          </div>
        </div>
      </div>

      <!-- Note fiscale -->
      <div class="note-fiscale">
        <strong>NOTE FISCALE :</strong> Cette déclaration doit être soumise à la Direction Générale des Impôts
        avant la date limite de dépôt. Toute déclaration hors délai est passible de pénalités.
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
  tva: props.factures.reduce((sum, f) => sum + f.montant_tva, 0),
  ttc: props.factures.reduce((sum, f) => sum + f.montant_ttc, 0)
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

/* NOIR & BLANC UNIQUEMENT */

.republic {
  font-size: 14px;
  font-weight: bold;
  text-align: center;
  color: #000000;
  text-transform: uppercase;
}

.motto {
  font-size: 11px;
  text-align: center;
  color: #666666;
  font-style: italic;
  margin: 4px 0;
}

.separator {
  text-align: center;
  margin: 6px 0;
  color: #666666;
}

.document-title .periode {
  font-size: 13px;
  margin-top: 8px;
  color: #333333;
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
  background: #f5f5f5;
  padding: 20px;
  border-radius: 0;
  border: 2px solid #000000;
  text-align: center;
}

/* Box warning avec bordure épaisse au lieu de couleur */
.summary-box.warning {
  border: 3px solid #000000;
  border-left: 5px solid #000000;
}

.summary-label {
  font-size: 12px;
  color: #666666;
  margin-bottom: 8px;
  font-weight: 600;
}

.summary-value {
  font-size: 20px;
  font-weight: bold;
  color: #000000;
}

.tva-cell {
  color: #000000;
  font-weight: bold;
  font-family: 'Courier New', monospace;
}

.tva-total {
  color: #000000 !important;
  font-size: 16px !important;
  font-weight: bold !important;
}

.recapitulatif-tva {
  margin-top: 30px;
}

.tva-calculation {
  background: #f5f5f5;
  padding: 20px;
  border-radius: 0;
  border: 2px solid #000000;
}

.tva-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #cccccc;
}

.tva-row.total-tva {
  border-bottom: none;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 3px double #000000;
  background: #eeeeee;
}

.tva-label {
  font-size: 14px;
  color: #333333;
}

.tva-value {
  font-size: 16px;
  font-weight: 600;
  color: #000000;
  font-family: 'Courier New', monospace;
}

.tva-value.tva-collectee {
  color: #000000;
  font-weight: bold;
}

/* Note fiscale en noir et blanc avec bordure épaisse */
.note-fiscale {
  margin-top: 30px;
  padding: 15px;
  background: #eeeeee;
  border: 2px solid #000000;
  border-left: 5px solid #000000;
  font-size: 12px;
  color: #000000;
  line-height: 1.6;
}

@media print {
  .summary-box,
  .tva-calculation,
  .note-fiscale {
    border-color: #000000 !important;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
  }
}
</style>
