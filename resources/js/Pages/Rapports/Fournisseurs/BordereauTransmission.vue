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
        <h1>BORDEREAU DE TRANSMISSION DES RÈGLEMENTS</h1>
        <div class="subtitle">Transmission pour traitement</div>
        <div class="periode">Date : {{ formatDate(date_bordereau) }}</div>
      </div>

      <!-- Informations bordereau -->
      <div class="section info-section">
        <h2>INFORMATIONS DU BORDEREAU</h2>
        <div class="info-grid">
          <div class="info-item">
            <span class="label">N° Bordereau :</span>
            <span class="value"><strong>{{ numero_bordereau }}</strong></span>
          </div>
          <div class="info-item">
            <span class="label">Date émission :</span>
            <span class="value">{{ formatDate(date_bordereau) }}</span>
          </div>
          <div class="info-item">
            <span class="label">Service émetteur :</span>
            <span class="value">Comptabilité</span>
          </div>
          <div class="info-item">
            <span class="label">Nombre de règlements :</span>
            <span class="value"><strong>{{ reglements.length }}</strong></span>
          </div>
        </div>
      </div>

      <!-- Liste des règlements -->
      <div class="section">
        <h2>DÉTAIL DES RÈGLEMENTS À TRANSMETTRE</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>N°</th>
              <th>Date</th>
              <th>Fournisseur</th>
              <th>N° Facture</th>
              <th>Mode paiement</th>
              <th>Référence</th>
              <th class="montant-col">Montant</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(reglement, index) in reglements" :key="reglement.id">
              <td>{{ index + 1 }}</td>
              <td>{{ formatDate(reglement.date_reglement) }}</td>
              <td>{{ reglement.fournisseur.nom }}</td>
              <td><strong>{{ reglement.facture.numero }}</strong></td>
              <td>{{ getModeLabel(reglement.mode_paiement) }}</td>
              <td>{{ reglement.reference || '-' }}</td>
              <td class="montant-col">{{ formatMontant(reglement.montant) }}</td>
              <td>
                <span class="status-badge">En attente</span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td colspan="6" class="total-label">TOTAL À TRANSMETTRE</td>
              <td class="montant-col total-cell">{{ formatMontant(totalMontant) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Récapitulatif par mode de paiement -->
      <div class="section">
        <h2>RÉCAPITULATIF PAR MODE DE PAIEMENT</h2>
        <table class="data-table">
          <thead>
            <tr>
              <th>Mode de paiement</th>
              <th class="montant-col">Nombre</th>
              <th class="montant-col">Montant total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="mode in modesRecap" :key="mode.mode">
              <td><strong>{{ mode.label }}</strong></td>
              <td class="montant-col">{{ mode.count }}</td>
              <td class="montant-col">{{ formatMontant(mode.montant) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Signatures -->
      <div class="section signature-section">
        <div class="signature-row">
          <div class="signature-box">
            <div class="signature-label">Établi par</div>
            <div class="signature-sub">Le Comptable</div>
            <div class="signature-space"></div>
            <div class="signature-name">Nom et signature</div>
            <div class="signature-date">Date : ___/___/______</div>
          </div>
          <div class="signature-box">
            <div class="signature-label">Vérifié par</div>
            <div class="signature-sub">Le Chef Comptable</div>
            <div class="signature-space"></div>
            <div class="signature-name">Nom et signature</div>
            <div class="signature-date">Date : ___/___/______</div>
          </div>
          <div class="signature-box">
            <div class="signature-label">Reçu par</div>
            <div class="signature-sub">Service destinataire</div>
            <div class="signature-space"></div>
            <div class="signature-name">Nom et signature</div>
            <div class="signature-date">Date : ___/___/______</div>
          </div>
        </div>
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
  numero_bordereau: { type: String, required: true },
  date_bordereau: { type: String, required: true }
});

const totalMontant = computed(() => {
  return props.reglements.reduce((sum, r) => sum + r.montant, 0);
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
    carte: 'Carte bancaire',
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

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  background: #fed7aa;
  color: #92400e;
}

.signature-section {
  margin-top: 40px;
}

.signature-row {
  display: flex;
  justify-content: space-between;
  gap: 15px;
}

.signature-box {
  flex: 1;
  text-align: center;
  padding: 12px;
  border: 1px solid #e5e7eb;
  background: #fafafa;
}

.signature-label {
  font-size: 12px;
  font-weight: bold;
  color: #1f2937;
  margin-bottom: 4px;
}

.signature-sub {
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 25px;
  font-style: italic;
}

.signature-space {
  height: 50px;
  margin: 20px 0 10px 0;
}

.signature-name {
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 4px;
}

.signature-date {
  font-size: 10px;
  color: #9ca3af;
}
</style>
