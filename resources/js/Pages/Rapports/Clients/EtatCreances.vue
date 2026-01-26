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
        <h1>ÉTATS PÉRIODIQUES DES CRÉANCES CLIENTS</h1>
        <div class="subtitle">Factures non soldées</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Statistiques globales -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Nombre de factures</div>
            <div class="summary-value">{{ factures.length }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Total Facturé</div>
            <div class="summary-value">{{ formatMontant(totaux.facture) }}</div>
          </div>
          <div class="summary-box warning">
            <div class="summary-label">Total Payé</div>
            <div class="summary-value">{{ formatMontant(totaux.paye) }}</div>
          </div>
          <div class="summary-box danger">
            <div class="summary-label">Total Créances</div>
            <div class="summary-value">{{ formatMontant(totaux.reste) }}</div>
          </div>
        </div>
      </div>

      <!-- Détail par client -->
      <div class="section" v-for="client in clientsAvecCreances" :key="client.id">
        <div class="client-header">
          <h3>
            {{ client.code }} - {{ client.nom }}
          </h3>
          <div class="client-creance">
            <span class="label">Créance totale :</span>
            <span class="value">{{ formatMontant(client.total_creances) }}</span>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>N° Facture</th>
              <th>Date</th>
              <th>Date échéance</th>
              <th class="montant-col">Montant TTC</th>
              <th class="montant-col">Payé</th>
              <th class="montant-col">Reste</th>
              <th>Retard</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="facture in client.factures" :key="facture.id">
              <td><strong>{{ facture.numero }}</strong></td>
              <td>{{ formatDate(facture.date_facture) }}</td>
              <td>{{ formatDate(facture.date_echeance) }}</td>
              <td class="montant-col">{{ formatMontant(facture.montant_ttc) }}</td>
              <td class="montant-col montant-paye">{{ formatMontant(totalPaye(facture)) }}</td>
              <td class="montant-col montant-reste">{{ formatMontant(reste(facture)) }}</td>
              <td>
                <span v-if="joursRetard(facture) > 0" class="retard-badge" :class="getRetardClass(facture)">
                  {{ joursRetard(facture) }} jour(s)
                </span>
                <span v-else>-</span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="3" class="total-label">SOUS-TOTAL CLIENT</td>
              <td class="montant-col total-cell">{{ formatMontant(client.total_facture) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(client.total_paye) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(client.total_creances) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Total général -->
      <div class="section">
        <table class="data-table">
          <tfoot>
            <tr class="total-row grand-total">
              <td colspan="3" class="total-label">TOTAL GÉNÉRAL</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.facture) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.paye) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.reste) }}</td>
              <td></td>
            </tr>
          </tfoot>
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

const totalPaye = (facture) => {
  return facture.reglements?.reduce((sum, r) => sum + r.montant, 0) || 0;
};

const reste = (facture) => {
  return facture.montant_ttc - totalPaye(facture);
};

const joursRetard = (facture) => {
  const echeance = new Date(facture.date_echeance);
  const aujourd_hui = new Date();
  const diff = Math.floor((aujourd_hui - echeance) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
};

const getRetardClass = (facture) => {
  const jours = joursRetard(facture);
  if (jours > 90) return 'retard-grave';
  if (jours > 30) return 'retard-important';
  return 'retard-leger';
};

const clientsAvecCreances = computed(() => {
  const clients = {};
  props.factures.forEach(f => {
    const clientId = f.client.id;
    if (!clients[clientId]) {
      clients[clientId] = {
        id: f.client.id,
        code: f.client.code,
        nom: f.client.nom,
        factures: [],
        total_facture: 0,
        total_paye: 0,
        total_creances: 0
      };
    }
    clients[clientId].factures.push(f);
    clients[clientId].total_facture += f.montant_ttc;
    clients[clientId].total_paye += totalPaye(f);
    clients[clientId].total_creances += reste(f);
  });
  return Object.values(clients);
});

const totaux = computed(() => ({
  facture: props.factures.reduce((sum, f) => sum + f.montant_ttc, 0),
  paye: props.factures.reduce((sum, f) => sum + totalPaye(f), 0),
  reste: props.factures.reduce((sum, f) => sum + reste(f), 0)
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

.summary-box.warning { border-left-color: #f59e0b; }
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

.client-header {
  background: #f9fafb;
  padding: 15px;
  margin-bottom: 10px;
  border-left: 4px solid #dc2626;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.client-header h3 {
  font-size: 14px;
  font-weight: bold;
  color: #1f2937;
  margin: 0;
}

.client-creance {
  display: flex;
  gap: 8px;
  align-items: center;
}

.client-creance .label {
  font-size: 12px;
  color: #6b7280;
}

.client-creance .value {
  font-size: 16px;
  font-weight: bold;
  color: #dc2626;
}

.montant-paye {
  color: #059669;
}

.montant-reste {
  color: #dc2626;
  font-weight: bold;
}

.retard-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.retard-leger {
  background: #fef3c7;
  color: #92400e;
}

.retard-important {
  background: #fed7aa;
  color: #9a3412;
}

.retard-grave {
  background: #fee2e2;
  color: #991b1b;
}

.grand-total {
  background: #1f2937 !important;
  color: white !important;
}

.grand-total .total-label,
.grand-total .total-cell {
  color: white !important;
}
</style>
