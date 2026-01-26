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
        <h1>FACTURES ET SOLDES</h1>
        <div class="subtitle">Détails des règlements par facture</div>
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
          <div class="summary-box success">
            <div class="summary-label">Total Payé</div>
            <div class="summary-value">{{ formatMontant(totaux.paye) }}</div>
          </div>
          <div class="summary-box danger">
            <div class="summary-label">Total Reste</div>
            <div class="summary-value">{{ formatMontant(totaux.reste) }}</div>
          </div>
        </div>
      </div>

      <!-- Détail par facture -->
      <div class="section" v-for="facture in factures" :key="facture.id">
        <div class="facture-header">
          <h3>
            Facture N° {{ facture.numero }} - {{ facture.fournisseur.nom }}
            <span class="facture-date">({{ formatDate(facture.date_facture) }})</span>
          </h3>
          <div class="facture-montant">
            <span class="label">Montant TTC :</span>
            <span class="value">{{ formatMontant(facture.montant_ttc) }}</span>
          </div>
        </div>

        <table class="data-table">
          <thead>
            <tr>
              <th>Date règlement</th>
              <th>Mode paiement</th>
              <th>Référence</th>
              <th>Banque</th>
              <th class="montant-col">Montant</th>
              <th class="montant-col">Solde après</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(reglement, index) in facture.reglements" :key="reglement.id">
              <td>{{ formatDate(reglement.date_reglement) }}</td>
              <td>{{ getModeLabel(reglement.mode_paiement) }}</td>
              <td>{{ reglement.reference || '-' }}</td>
              <td>{{ reglement.banque || '-' }}</td>
              <td class="montant-col">{{ formatMontant(reglement.montant) }}</td>
              <td class="montant-col solde-cell">{{ formatMontant(calculSolde(facture, index)) }}</td>
            </tr>
            <tr v-if="facture.reglements.length === 0">
              <td colspan="6" class="no-data">Aucun règlement enregistré</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="total-label">TOTAL PAYÉ</td>
              <td class="montant-col total-cell">{{ formatMontant(totalPaye(facture)) }}</td>
              <td class="montant-col total-cell" :class="reste(facture) > 0 ? 'reste-cell' : 'solde-cell'">
                {{ formatMontant(reste(facture)) }}
              </td>
            </tr>
          </tfoot>
        </table>

        <div class="facture-statut">
          <span class="statut-label">Statut :</span>
          <span class="statut-badge" :class="getStatutClass(facture)">
            {{ getStatutLabel(facture) }}
          </span>
        </div>
      </div>

      <div class="footer">
        <div class="footer-line"></div>
        <div class="footer-content">
          <div>Document généré le {{ formatDateLong(new Date()) }}</div>
          <div class="page-number">Page 1/{{ Math.ceil(factures.length / 3) }}</div>
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
  facture: props.factures.reduce((sum, f) => sum + f.montant_ttc, 0),
  paye: props.factures.reduce((sum, f) => sum + totalPaye(f), 0),
  reste: props.factures.reduce((sum, f) => sum + reste(f), 0)
}));

const totalPaye = (facture) => {
  return facture.reglements?.reduce((sum, r) => sum + r.montant, 0) || 0;
};

const reste = (facture) => {
  return facture.montant_ttc - totalPaye(facture);
};

const calculSolde = (facture, index) => {
  const payeJusqua = facture.reglements.slice(0, index + 1).reduce((sum, r) => sum + r.montant, 0);
  return facture.montant_ttc - payeJusqua;
};

const getStatutLabel = (facture) => {
  const r = reste(facture);
  if (r === 0) return 'SOLDÉE';
  if (totalPaye(facture) > 0) return 'PARTIELLE';
  return 'IMPAYÉE';
};

const getStatutClass = (facture) => {
  const r = reste(facture);
  if (r === 0) return 'statut-success';
  if (totalPaye(facture) > 0) return 'statut-warning';
  return 'statut-danger';
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

.summary-box.success { border-left-color: #059669; }
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

.facture-header {
  background: #f9fafb;
  padding: 15px;
  margin-bottom: 10px;
  border-left: 4px solid #2563eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.facture-header h3 {
  font-size: 14px;
  font-weight: bold;
  color: #1f2937;
  margin: 0;
}

.facture-date {
  font-size: 12px;
  font-weight: normal;
  color: #6b7280;
  margin-left: 8px;
}

.facture-montant {
  display: flex;
  gap: 8px;
  align-items: center;
}

.facture-montant .label {
  font-size: 12px;
  color: #6b7280;
}

.facture-montant .value {
  font-size: 16px;
  font-weight: bold;
  color: #2563eb;
}

.solde-cell {
  color: #059669;
  font-weight: bold;
}

.reste-cell {
  color: #dc2626 !important;
  font-weight: bold;
}

.no-data {
  text-align: center;
  color: #9ca3af;
  font-style: italic;
  padding: 20px;
}

.facture-statut {
  margin-top: 10px;
  padding: 10px;
  background: #f9fafb;
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: flex-end;
}

.statut-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
}

.statut-badge {
  padding: 6px 16px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.statut-success { background: #d1fae5; color: #065f46; }
.statut-warning { background: #fed7aa; color: #92400e; }
.statut-danger { background: #fee2e2; color: #991b1b; }
</style>
