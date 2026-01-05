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
        <h1>ÉTATS PÉRIODIQUES DES RÈGLEMENTS CLIENTS</h1>
        <div class="subtitle">Encaissements reçus</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Statistiques globales -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Nombre de règlements</div>
            <div class="summary-value">{{ reglements.length }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Nombre de clients</div>
            <div class="summary-value">{{ uniqueClients }}</div>
          </div>
          <div class="summary-box success">
            <div class="summary-label">Total Encaissé</div>
            <div class="summary-value">{{ formatMontant(totalEncaisse) }}</div>
          </div>
        </div>
      </div>

      <!-- Tableau des règlements -->
      <div class="section">
        <h2>DÉTAIL DES RÈGLEMENTS</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>N° Facture</th>
              <th>Mode paiement</th>
              <th>Référence</th>
              <th>Banque</th>
              <th class="montant-col">Montant</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reglement in reglements" :key="reglement.id">
              <td>{{ formatDate(reglement.date_reglement) }}</td>
              <td><strong>{{ reglement.client.nom }}</strong><br><span class="client-code">{{ reglement.client.code }}</span></td>
              <td>{{ reglement.facture.numero }}</td>
              <td>{{ getModeLabel(reglement.mode_paiement) }}</td>
              <td>{{ reglement.reference || '-' }}</td>
              <td>{{ reglement.banque || '-' }}</td>
              <td class="montant-col">{{ formatMontant(reglement.montant) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="6" class="total-label">TOTAL ENCAISSÉ</td>
              <td class="montant-col total-cell">{{ formatMontant(totalEncaisse) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Répartition par mode de paiement -->
      <div class="section">
        <h2>RÉPARTITION PAR MODE DE PAIEMENT</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Mode de paiement</th>
              <th class="montant-col">Nombre</th>
              <th class="montant-col">Montant total</th>
              <th class="montant-col">%</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="mode in modesRecap" :key="mode.mode">
              <td><strong>{{ mode.label }}</strong></td>
              <td class="montant-col">{{ mode.count }}</td>
              <td class="montant-col">{{ formatMontant(mode.montant) }}</td>
              <td class="montant-col">{{ ((mode.montant / totalEncaisse) * 100).toFixed(1) }}%</td>
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
  reglements: { type: Array, default: () => [] },
  periode: { type: Object, required: true }
});

const totalEncaisse = computed(() => {
  return props.reglements.reduce((sum, r) => sum + r.montant, 0);
});

const uniqueClients = computed(() => {
  const clientIds = new Set(props.reglements.map(r => r.client.id));
  return clientIds.size;
});

const modesRecap = computed(() => {
  const modes = {};
  props.reglements.forEach(r => {
    if (!modes[r.mode_paiement]) {
      modes[r.mode_paiement] = {
        mode: r.mode_paiement,
        label: getModeLabel(r.mode_paiement),
        count: 0,
        montant: 0
      };
    }
    modes[r.mode_paiement].count++;
    modes[r.mode_paiement].montant += r.montant;
  });
  return Object.values(modes);
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

const handlePrint = () => window.print();
const handleClose = () => window.close();
</script>

<style scoped>
@import url('../Fournisseurs/rapports-styles.css');

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

.summary-box.success { border-left-color: #059669; }

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

.client-code {
  font-size: 11px;
  color: #6b7280;
}
</style>
