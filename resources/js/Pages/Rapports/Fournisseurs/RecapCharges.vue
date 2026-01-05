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
        <h1>ÉTAT RÉCAPITULATIF DES CHARGES</h1>
        <div class="subtitle">Dépenses d'exploitation</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Résumé global -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Total Charges HT</div>
            <div class="summary-value">{{ formatMontant(totaux.ht) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Total TVA</div>
            <div class="summary-value">{{ formatMontant(totaux.tva) }}</div>
          </div>
          <div class="summary-box danger">
            <div class="summary-label">Total TTC</div>
            <div class="summary-value">{{ formatMontant(totaux.ttc) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Nombre de factures</div>
            <div class="summary-value">{{ factures.length }}</div>
          </div>
        </div>
      </div>

      <!-- Par catégorie de charge -->
      <div class="section">
        <h2>RÉPARTITION PAR CATÉGORIE DE CHARGE</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Catégorie</th>
              <th>Compte</th>
              <th class="montant-col">Nb Factures</th>
              <th class="montant-col">Montant HT</th>
              <th class="montant-col">TVA</th>
              <th class="montant-col">TTC</th>
              <th class="montant-col">% Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cat in categories" :key="cat.compte">
              <td><strong>{{ cat.libelle }}</strong></td>
              <td>{{ cat.compte }}</td>
              <td class="montant-col">{{ cat.nb_factures }}</td>
              <td class="montant-col">{{ formatMontant(cat.montant_ht) }}</td>
              <td class="montant-col">{{ formatMontant(cat.montant_tva) }}</td>
              <td class="montant-col">{{ formatMontant(cat.montant_ttc) }}</td>
              <td class="montant-col">{{ ((cat.montant_ttc / totaux.ttc) * 100).toFixed(1) }}%</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAL</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ht) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.tva) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ttc) }}</td>
              <td class="montant-col total-cell">100%</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Détail des factures -->
      <div class="section">
        <h2>DÉTAIL DES FACTURES DE CHARGES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>N° Facture</th>
              <th>Fournisseur</th>
              <th>Catégorie</th>
              <th class="montant-col">Montant HT</th>
              <th class="montant-col">TVA</th>
              <th class="montant-col">TTC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="facture in factures" :key="facture.id">
              <td>{{ formatDate(facture.date_facture) }}</td>
              <td><strong>{{ facture.numero }}</strong></td>
              <td>{{ facture.fournisseur.nom }}</td>
              <td>{{ facture.categorie }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ht) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_tva) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ttc) }}</td>
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

const categories = computed(() => {
  const cats = {};
  props.factures.forEach(f => {
    const key = f.compte_imputation || 'Autres';
    if (!cats[key]) {
      cats[key] = {
        compte: f.compte_imputation,
        libelle: f.categorie,
        nb_factures: 0,
        montant_ht: 0,
        montant_tva: 0,
        montant_ttc: 0
      };
    }
    cats[key].nb_factures++;
    cats[key].montant_ht += f.montant_ht;
    cats[key].montant_tva += f.montant_tva;
    cats[key].montant_ttc += f.montant_ttc;
  });
  return Object.values(cats);
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
  grid-template-columns: repeat(4, 1fr);
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
</style>
