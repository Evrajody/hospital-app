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
        <h1>CHIFFRE D'AFFAIRES</h1>
        <div class="subtitle">Global et par client</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Statistiques globales -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Nombre de clients</div>
            <div class="summary-value">{{ clients.length }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Nombre de factures</div>
            <div class="summary-value">{{ totalFactures }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">CA HT</div>
            <div class="summary-value">{{ formatMontant(totaux.ht) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">TVA</div>
            <div class="summary-value">{{ formatMontant(totaux.tva) }}</div>
          </div>
          <div class="summary-box success">
            <div class="summary-label">CA TTC</div>
            <div class="summary-value">{{ formatMontant(totaux.ttc) }}</div>
          </div>
          <div class="summary-box warning">
            <div class="summary-label">Encaissé</div>
            <div class="summary-value">{{ formatMontant(totaux.encaisse) }}</div>
          </div>
        </div>
      </div>

      <!-- Détail par client -->
      <div class="section">
        <h2>CHIFFRE D'AFFAIRES PAR CLIENT</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Code Client</th>
              <th>Nom du client</th>
              <th>Type</th>
              <th class="montant-col">Nb Factures</th>
              <th class="montant-col">CA HT</th>
              <th class="montant-col">TVA</th>
              <th class="montant-col">CA TTC</th>
              <th class="montant-col">% CA Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="client in clients" :key="client.id">
              <td><strong>{{ client.code }}</strong></td>
              <td>{{ client.nom }}</td>
              <td>{{ getTypeLabel(client.type) }}</td>
              <td class="montant-col">{{ client.nb_factures }}</td>
              <td class="montant-col">{{ formatMontant(client.ca_ht) }}</td>
              <td class="montant-col">{{ formatMontant(client.ca_tva) }}</td>
              <td class="montant-col">{{ formatMontant(client.ca_ttc) }}</td>
              <td class="montant-col">{{ ((client.ca_ttc / totaux.ttc) * 100).toFixed(1) }}%</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="total-label">TOTAL</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ht) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.tva) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.ttc) }}</td>
              <td class="montant-col total-cell">100%</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Répartition par type de client -->
      <div class="section">
        <h2>RÉPARTITION PAR TYPE DE CLIENT</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Type de client</th>
              <th class="montant-col">Nombre</th>
              <th class="montant-col">CA TTC</th>
              <th class="montant-col">% CA Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="type in typesRecap" :key="type.type">
              <td><strong>{{ type.label }}</strong></td>
              <td class="montant-col">{{ type.count }}</td>
              <td class="montant-col">{{ formatMontant(type.ca_ttc) }}</td>
              <td class="montant-col">{{ ((type.ca_ttc / totaux.ttc) * 100).toFixed(1) }}%</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Top 10 clients -->
      <div class="section">
        <h2>TOP 10 CLIENTS</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Rang</th>
              <th>Client</th>
              <th class="montant-col">CA TTC</th>
              <th class="montant-col">% CA Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(client, index) in top10Clients" :key="client.id">
              <td><strong>{{ index + 1 }}</strong></td>
              <td>{{ client.code }} - {{ client.nom }}</td>
              <td class="montant-col">{{ formatMontant(client.ca_ttc) }}</td>
              <td class="montant-col">{{ ((client.ca_ttc / totaux.ttc) * 100).toFixed(1) }}%</td>
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
  clients: { type: Array, default: () => [] },
  periode: { type: Object, required: true }
});

const totalFactures = computed(() => {
  return props.clients.reduce((sum, c) => sum + c.nb_factures, 0);
});

const totaux = computed(() => ({
  ht: props.clients.reduce((sum, c) => sum + c.ca_ht, 0),
  tva: props.clients.reduce((sum, c) => sum + c.ca_tva, 0),
  ttc: props.clients.reduce((sum, c) => sum + c.ca_ttc, 0),
  encaisse: props.clients.reduce((sum, c) => sum + (c.ca_encaisse || 0), 0)
}));

const typesRecap = computed(() => {
  const types = {};
  props.clients.forEach(c => {
    const type = c.type || 'particulier';
    if (!types[type]) {
      types[type] = {
        type: type,
        label: getTypeLabel(type),
        count: 0,
        ca_ttc: 0
      };
    }
    types[type].count++;
    types[type].ca_ttc += c.ca_ttc;
  });
  return Object.values(types);
});

const top10Clients = computed(() => {
  return [...props.clients]
    .sort((a, b) => b.ca_ttc - a.ca_ttc)
    .slice(0, 10);
});

const getTypeLabel = (type) => {
  const labels = {
    particulier: 'Particulier',
    entreprise: 'Entreprise',
    assurance: 'Assurance',
    mutuelle: 'Mutuelle'
  };
  return labels[type] || type;
};

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
  grid-template-columns: repeat(6, 1fr);
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
.summary-box.warning { border-left-color: #f59e0b; }

.summary-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 8px;
}

.summary-value {
  font-size: 18px;
  font-weight: bold;
  color: #1f2937;
}

@media print {
  .summary-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
