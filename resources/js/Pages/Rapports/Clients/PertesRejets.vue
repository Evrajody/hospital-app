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
        <h1>PERTES, REJETS ET RÉGULARISATIONS</h1>
        <div class="subtitle">État des impayés et opérations de régularisation</div>
        <div class="periode">Période du {{ formatDate(periode.debut) }} au {{ formatDate(periode.fin) }}</div>
      </div>

      <!-- Statistiques globales -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box danger">
            <div class="summary-label">Total Pertes</div>
            <div class="summary-value">{{ formatMontant(totaux.pertes) }}</div>
          </div>
          <div class="summary-box warning">
            <div class="summary-label">Total Rejets</div>
            <div class="summary-value">{{ formatMontant(totaux.rejets) }}</div>
          </div>
          <div class="summary-box success">
            <div class="summary-label">Total Régularisations</div>
            <div class="summary-value">{{ formatMontant(totaux.regularisations) }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Solde Net</div>
            <div class="summary-value">{{ formatMontant(totaux.solde) }}</div>
          </div>
        </div>
      </div>

      <!-- Pertes (créances irrécouvrables) -->
      <div class="section">
        <h2>PERTES - CRÉANCES IRRÉCOUVRABLES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>N° Facture</th>
              <th>Motif</th>
              <th class="montant-col">Montant</th>
              <th>Décision</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="perte in pertes" :key="perte.id">
              <td>{{ formatDate(perte.date_operation) }}</td>
              <td>
                <strong>{{ perte.client.nom }}</strong><br>
                <span class="client-code">{{ perte.client.code }}</span>
              </td>
              <td>{{ perte.facture.numero }}</td>
              <td>{{ perte.motif }}</td>
              <td class="montant-col montant-perte">{{ formatMontant(perte.montant) }}</td>
              <td><span class="decision-badge">{{ perte.decision }}</span></td>
            </tr>
            <tr v-if="pertes.length === 0">
              <td colspan="6" class="no-data">Aucune perte enregistrée</td>
            </tr>
          </tbody>
          <tfoot v-if="pertes.length > 0">
            <tr class="total-row">
              <td colspan="4" class="total-label">SOUS-TOTAL PERTES</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.pertes) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Rejets de chèques -->
      <div class="section">
        <h2>REJETS DE CHÈQUES</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date Rejet</th>
              <th>Client</th>
              <th>N° Chèque</th>
              <th>Banque</th>
              <th>Motif de rejet</th>
              <th class="montant-col">Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rejet in rejets" :key="rejet.id">
              <td>{{ formatDate(rejet.date_rejet) }}</td>
              <td>
                <strong>{{ rejet.client.nom }}</strong><br>
                <span class="client-code">{{ rejet.client.code }}</span>
              </td>
              <td><strong>{{ rejet.numero_cheque }}</strong></td>
              <td>{{ rejet.banque }}</td>
              <td>{{ rejet.motif_rejet }}</td>
              <td class="montant-col montant-rejet">{{ formatMontant(rejet.montant) }}</td>
              <td>
                <span class="statut-badge" :class="getStatutRejetClass(rejet)">
                  {{ rejet.statut }}
                </span>
              </td>
            </tr>
            <tr v-if="rejets.length === 0">
              <td colspan="7" class="no-data">Aucun rejet enregistré</td>
            </tr>
          </tbody>
          <tfoot v-if="rejets.length > 0">
            <tr class="total-row">
              <td colspan="5" class="total-label">SOUS-TOTAL REJETS</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.rejets) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Régularisations -->
      <div class="section">
        <h2>RÉGULARISATIONS</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Client</th>
              <th>Type</th>
              <th>Description</th>
              <th class="montant-col">Montant</th>
              <th>Référence</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="reg in regularisations" :key="reg.id">
              <td>{{ formatDate(reg.date_operation) }}</td>
              <td>
                <strong>{{ reg.client.nom }}</strong><br>
                <span class="client-code">{{ reg.client.code }}</span>
              </td>
              <td>{{ getTypeRegLabel(reg.type) }}</td>
              <td>{{ reg.description }}</td>
              <td class="montant-col montant-regularisation">{{ formatMontant(reg.montant) }}</td>
              <td>{{ reg.reference || '-' }}</td>
            </tr>
            <tr v-if="regularisations.length === 0">
              <td colspan="6" class="no-data">Aucune régularisation enregistrée</td>
            </tr>
          </tbody>
          <tfoot v-if="regularisations.length > 0">
            <tr class="total-row">
              <td colspan="4" class="total-label">SOUS-TOTAL RÉGULARISATIONS</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.regularisations) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Synthèse -->
      <div class="section">
        <h2>SYNTHÈSE</h2>
        <table class="data-table">
          <tbody>
            <tr>
              <td class="synthese-label">Total des pertes (créances irrécouvrables)</td>
              <td class="montant-col montant-perte">{{ formatMontant(totaux.pertes) }}</td>
            </tr>
            <tr>
              <td class="synthese-label">Total des rejets de chèques</td>
              <td class="montant-col montant-rejet">{{ formatMontant(totaux.rejets) }}</td>
            </tr>
            <tr>
              <td class="synthese-label">Total des régularisations</td>
              <td class="montant-col montant-regularisation">{{ formatMontant(totaux.regularisations) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td class="total-label">SOLDE NET (Pertes + Rejets - Régularisations)</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.solde) }}</td>
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
  pertes: { type: Array, default: () => [] },
  rejets: { type: Array, default: () => [] },
  regularisations: { type: Array, default: () => [] },
  periode: { type: Object, required: true }
});

const totaux = computed(() => {
  const pertes = props.pertes.reduce((sum, p) => sum + p.montant, 0);
  const rejets = props.rejets.reduce((sum, r) => sum + r.montant, 0);
  const regularisations = props.regularisations.reduce((sum, r) => sum + r.montant, 0);
  return {
    pertes,
    rejets,
    regularisations,
    solde: pertes + rejets - regularisations
  };
});

const getStatutRejetClass = (rejet) => {
  const statut = rejet.statut || 'en_attente';
  const classes = {
    en_attente: 'statut-warning',
    regularise: 'statut-success',
    non_regularise: 'statut-danger'
  };
  return classes[statut] || 'statut-warning';
};

const getTypeRegLabel = (type) => {
  const labels = {
    remboursement: 'Remboursement',
    avoir: 'Avoir',
    annulation: 'Annulation',
    rectification: 'Rectification',
    remise: 'Remise gracieuse'
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

.client-code {
  font-size: 11px;
  color: #6b7280;
}

.montant-perte {
  color: #dc2626;
  font-weight: bold;
}

.montant-rejet {
  color: #f59e0b;
  font-weight: bold;
}

.montant-regularisation {
  color: #059669;
  font-weight: bold;
}

.decision-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  background: #f3f4f6;
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

.synthese-label {
  font-weight: 600;
  color: #374151;
  padding: 12px;
}

.no-data {
  text-align: center;
  color: #9ca3af;
  font-style: italic;
  padding: 20px;
}
</style>
