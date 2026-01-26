<template>
  <div class="document-container">
    <div class="document-page">
      <!-- En-tête -->
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
        <h1>MOUVEMENT PÉRIODIQUE DES FACTURES FOURNISSEUR</h1>
        <div class="subtitle">{{ fournisseur.nom }} ({{ fournisseur.code }})</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Résumé fournisseur -->
      <div class="section info-section">
        <h2>INFORMATIONS FOURNISSEUR</h2>
        <div class="info-grid">
          <div class="info-item">
            <span class="label">Raison sociale :</span>
            <span class="value"><strong>{{ fournisseur.nom }}</strong></span>
          </div>
          <div class="info-item">
            <span class="label">Code :</span>
            <span class="value">{{ fournisseur.code }}</span>
          </div>
          <div class="info-item">
            <span class="label">Compte comptable :</span>
            <span class="value">{{ fournisseur.compte_numero }}</span>
          </div>
          <div class="info-item">
            <span class="label">Contact :</span>
            <span class="value">{{ fournisseur.contact || 'N/A' }}</span>
          </div>
        </div>
      </div>

      <!-- Tableau des factures -->
      <div class="section">
        <h2>DÉTAIL DES FACTURES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>N° Facture</th>
              <th>Date</th>
              <th>Référence</th>
              <th class="montant-col">Montant HT</th>
              <th class="montant-col">TVA</th>
              <th class="montant-col">AIB</th>
              <th class="montant-col">TTC</th>
              <th class="montant-col">Payé</th>
              <th class="montant-col">Reste</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="facture in factures" :key="facture.id">
              <td><strong>{{ facture.numero }}</strong></td>
              <td>{{ formatDate(facture.date_facture) }}</td>
              <td>{{ facture.reference || '-' }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ht) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_tva) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_aib) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ttc) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_paye) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ttc - facture.montant_paye) }}</td>
              <td>
                <span class="status-badge" :class="getStatusClass(facture)">
                  {{ getStatusLabel(facture) }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">TOTAUX</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ht) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.tva) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.aib) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ttc) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.paye) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.reste) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Statistiques -->
      <div class="section stats-section">
        <div class="stats-grid">
          <div class="stat-box">
            <div class="stat-label">Nombre de factures</div>
            <div class="stat-value">{{ factures.length }}</div>
          </div>
          <div class="stat-box">
            <div class="stat-label">Montant total facturé</div>
            <div class="stat-value">{{ formatMontant(totaux.ttc) }}</div>
          </div>
          <div class="stat-box success">
            <div class="stat-label">Total payé</div>
            <div class="stat-value">{{ formatMontant(totaux.paye) }}</div>
          </div>
          <div class="stat-box danger">
            <div class="stat-label">Reste à payer</div>
            <div class="stat-value">{{ formatMontant(totaux.reste) }}</div>
          </div>
        </div>
      </div>

      <!-- Footer -->
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
  fournisseur: { type: Object, required: true },
  factures: { type: Array, default: () => [] },
  periode: { type: Object, required: true }
});

const totaux = computed(() => ({
  ht: props.factures.reduce((sum, f) => sum + f.montant_ht, 0),
  tva: props.factures.reduce((sum, f) => sum + f.montant_tva, 0),
  aib: props.factures.reduce((sum, f) => sum + f.montant_aib, 0),
  ttc: props.factures.reduce((sum, f) => sum + f.montant_ttc, 0),
  paye: props.factures.reduce((sum, f) => sum + f.montant_paye, 0),
  reste: props.factures.reduce((sum, f) => sum + (f.montant_ttc - f.montant_paye), 0)
}));

const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');

const formatDateLong = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'long', year: 'numeric'
  });
};

const getStatusLabel = (facture) => {
  const reste = facture.montant_ttc - facture.montant_paye;
  if (reste === 0) return 'Réglée';
  if (facture.montant_paye > 0) return 'Partielle';
  return 'Impayée';
};

const getStatusClass = (facture) => {
  const reste = facture.montant_ttc - facture.montant_paye;
  if (reste === 0) return 'status-success';
  if (facture.montant_paye > 0) return 'status-warning';
  return 'status-danger';
};

const handlePrint = () => window.print();
const handleClose = () => window.close();
</script>

<style scoped>
@import url('./rapports-styles.css');

.document-title .periode {
  font-size: 14px;
  margin-top: 8px;
  opacity: 0.9;
  font-weight: normal;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-success { background: #d1fae5; color: #065f46; }
.status-warning { background: #fed7aa; color: #92400e; }
.status-danger { background: #fee2e2; color: #991b1b; }

.stats-section {
  margin-top: 30px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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
.stat-box.danger { border-left-color: #dc2626; }

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

@media print {
  .document-container { background: white; padding: 0; }
  .document-page { box-shadow: none; padding: 15mm; margin: 0; }
  .no-print { display: none !important; }
  @page { margin: 15mm; }
}
</style>
