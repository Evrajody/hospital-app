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
        <h1>SITUATION DES FOURNISSEURS</h1>
        <div class="subtitle">Point des dettes fournisseurs au {{ formatDate(date_situation) }}</div>
      </div>

      <!-- Résumé global -->
      <div class="section summary-section">
        <div class="summary-grid">
          <div class="summary-box">
            <div class="summary-label">Nombre de fournisseurs</div>
            <div class="summary-value">{{ fournisseurs.length }}</div>
          </div>
          <div class="summary-box">
            <div class="summary-label">Total facturé</div>
            <div class="summary-value">{{ formatMontant(totaux.facture) }}</div>
          </div>
          <div class="summary-box success">
            <div class="summary-label">Total payé</div>
            <div class="summary-value">{{ formatMontant(totaux.paye) }}</div>
          </div>
          <div class="summary-box danger">
            <div class="summary-label">Total dettes</div>
            <div class="summary-value">{{ formatMontant(totaux.dettes) }}</div>
          </div>
        </div>
      </div>

      <!-- Liste des fournisseurs -->
      <div class="section">
        <h2>DÉTAIL PAR FOURNISSEUR</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Fournisseur</th>
              <th>Compte</th>
              <th class="montant-col">Nb Factures</th>
              <th class="montant-col">Total Facturé</th>
              <th class="montant-col">Total Payé</th>
              <th class="montant-col">Dette</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="fourn in fournisseurs" :key="fourn.id">
              <td><strong>{{ fourn.code }}</strong></td>
              <td>{{ fourn.nom }}</td>
              <td>{{ fourn.compte_numero }}</td>
              <td class="montant-col">{{ fourn.nb_factures }}</td>
              <td class="montant-col">{{ formatMontant(fourn.total_facture) }}</td>
              <td class="montant-col">{{ formatMontant(fourn.total_paye) }}</td>
              <td class="montant-col dette-cell">{{ formatMontant(fourn.dette) }}</td>
              <td>
                <span class="status-badge" :class="fourn.dette > 0 ? 'status-danger' : 'status-success'">
                  {{ fourn.dette > 0 ? 'Dette' : 'Soldé' }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="4" class="total-label">TOTAUX</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.facture) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.paye) }}</td>
              <td class="montant-col total-cell">{{ formatMontant(totaux.dettes) }}</td>
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
  fournisseurs: { type: Array, default: () => [] },
  date_situation: { type: String, required: true }
});

const totaux = computed(() => ({
  facture: props.fournisseurs.reduce((sum, f) => sum + f.total_facture, 0),
  paye: props.fournisseurs.reduce((sum, f) => sum + f.total_paye, 0),
  dettes: props.fournisseurs.reduce((sum, f) => sum + f.dette, 0)
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

.dette-cell {
  color: #dc2626;
  font-weight: bold;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-success { background: #d1fae5; color: #065f46; }
.status-danger { background: #fee2e2; color: #991b1b; }
</style>
