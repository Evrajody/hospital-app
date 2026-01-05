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

      <!-- Résumé TVA -->
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

.summary-box.warning { border-left-color: #f59e0b; }

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

.tva-cell {
  color: #f59e0b;
  font-weight: bold;
}

.tva-total {
  color: #f59e0b !important;
  font-size: 16px !important;
}

.recapitulatif-tva {
  margin-top: 30px;
}

.tva-calculation {
  background: #f9fafb;
  padding: 20px;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
}

.tva-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e5e7eb;
}

.tva-row.total-tva {
  border-bottom: none;
  margin-top: 15px;
  padding-top: 15px;
  border-top: 2px solid #1f2937;
}

.tva-label {
  font-size: 14px;
  color: #6b7280;
}

.tva-value {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.tva-value.tva-collectee {
  color: #f59e0b;
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
