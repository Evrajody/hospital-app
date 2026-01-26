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
        <h1>BROUILLARD DE CHÈQUES</h1>
        <div class="subtitle">Registre des chèques reçus et imputations comptables</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Statistiques globales -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Nombre de chèques</div>
            <div class="summary-value">{{ cheques.length }}</div>
          </div>
          <div class="summary-box success">
            <div class="summary-label">Total Chèques</div>
            <div class="summary-value">{{ formatMontant(totalCheques) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Nombre de banques</div>
            <div class="summary-value">{{ uniqueBanques }}</div>
          </div>
        </div>
      </div>

      <!-- Tableau des chèques -->
      <div class="section">
        <h2>REGISTRE DES CHÈQUES REÇUS</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>N° Chèque</th>
              <th>Banque</th>
              <th>N° Facture</th>
              <th class="montant-col">Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cheque in cheques" :key="cheque.id">
              <td>{{ formatDate(cheque.date_reglement) }}</td>
              <td>
                <strong>{{ cheque.client.nom }}</strong><br>
                <span class="client-code">{{ cheque.client.code }}</span>
              </td>
              <td><strong>{{ cheque.reference }}</strong></td>
              <td>{{ cheque.banque }}</td>
              <td>{{ cheque.facture.numero }}</td>
              <td class="montant-col">{{ formatMontant(cheque.montant) }}</td>
              <td>
                <span class="statut-badge" :class="getStatutClass(cheque)">
                  {{ cheque.statut_cheque || 'En attente' }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="5" class="total-label">TOTAL</td>
              <td class="montant-col total-cell">{{ formatMontant(totalCheques) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Répartition par banque -->
      <div class="section">
        <h2>RÉPARTITION PAR BANQUE</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Banque</th>
              <th class="montant-col">Nombre de chèques</th>
              <th class="montant-col">Montant total</th>
              <th class="montant-col">%</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="banque in banquesRecap" :key="banque.nom">
              <td><strong>{{ banque.nom }}</strong></td>
              <td class="montant-col">{{ banque.count }}</td>
              <td class="montant-col">{{ formatMontant(banque.montant) }}</td>
              <td class="montant-col">{{ ((banque.montant / totalCheques) * 100).toFixed(1) }}%</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Imputations comptables -->
      <div class="section">
        <h2>IMPUTATIONS COMPTABLES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>N° Pièce</th>
              <th>Client</th>
              <th>Compte Débit</th>
              <th>Compte Crédit</th>
              <th class="montant-col">Montant</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cheque in cheques" :key="'imp-' + cheque.id">
              <td>{{ formatDate(cheque.date_reglement) }}</td>
              <td><strong>{{ cheque.reference }}</strong></td>
              <td>{{ cheque.client.nom }}</td>
              <td>512 - Banque {{ cheque.banque }}</td>
              <td>411 - Clients</td>
              <td class="montant-col">{{ formatMontant(cheque.montant) }}</td>
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
  cheques: { type: Array, default: () => [] },
  periode: { type: Object, required: true }
});

const totalCheques = computed(() => {
  return props.cheques.reduce((sum, c) => sum + c.montant, 0);
});

const uniqueBanques = computed(() => {
  const banques = new Set(props.cheques.map(c => c.banque));
  return banques.size;
});

const banquesRecap = computed(() => {
  const banques = {};
  props.cheques.forEach(c => {
    if (!banques[c.banque]) {
      banques[c.banque] = {
        nom: c.banque,
        count: 0,
        montant: 0
      };
    }
    banques[c.banque].count++;
    banques[c.banque].montant += c.montant;
  });
  return Object.values(banques);
});

const getStatutClass = (cheque) => {
  const statut = cheque.statut_cheque || 'en_attente';
  const classes = {
    en_attente: 'statut-warning',
    encaisse: 'statut-success',
    rejete: 'statut-danger'
  };
  return classes[statut] || 'statut-warning';
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

.statut-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.statut-success {
  background: #d1fae5;
  color: #065f46;
}

.statut-warning {
  background: #fed7aa;
  color: #92400e;
}

.statut-danger {
  background: #fee2e2;
  color: #991b1b;
}
</style>
