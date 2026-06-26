<template>
  <el-drawer v-model="visible" title="État de Règlement Facture" direction="rtl" size="55%" :destroy-on-close="true">
    <div v-if="loading" style="text-align: center; padding: 40px;">
      <el-icon class="is-loading" :size="30"><Refresh /></el-icon>
      <p style="margin-top: 10px; color: #909399;">Chargement...</p>
    </div>
    <div v-else-if="data" class="etat-reglement-content">
      <div class="etat-reglement-header">
        <div class="imputation-hospital-name">{{ data.etablissement.nom }}</div>
        <div class="imputation-hospital-info">
          {{ data.etablissement.adresse }}<br>
          {{ data.etablissement.telephone ? 'Tél.: ' + data.etablissement.telephone : '' }}
          {{ data.etablissement.email ? ' - E-mail: ' + data.etablissement.email : '' }}
        </div>
        <div class="imputation-title-box"><span>ÉTAT DE RÈGLEMENT FACTURE</span></div>
      </div>

      <div class="etat-reglement-fournisseur">
        <strong>Fournisseur :</strong> [{{ data.fournisseur.code }}] {{ data.fournisseur.nom }}
      </div>

      <div class="etat-reglement-info etat-reglement-info-framed">
        <span><strong>N° PC :</strong> {{ data.facture.numero_piece }}</span>
        <span><strong>Date PC :</strong> {{ data.facture.date }}</span>
        <span><strong>Réf. Facture :</strong> {{ data.facture.reference_facture }}</span>
      </div>

      <div class="etat-reglement-objet">
        <strong>Objet :</strong> {{ data.facture.libelle }}
      </div>

      <div class="etat-reglement-montants">
        <span><strong>Montant HT :</strong> {{ data.facture.montant_facture }}</span>
        <span><strong>TVA{{ data.facture.assujetti_tva && data.facture.taux_tva > 0 ? ` (${data.facture.taux_tva}%)` : '' }} :</strong> {{ data.facture.montant_tva || '0' }}</span>
        <span><strong>Montant TTC :</strong> {{ data.facture.montant_ttc }}</span>
      </div>
      <div class="etat-reglement-montants">
        <span><strong>Montant M.O. :</strong> {{ data.facture.montant_mo }}</span>
        <span><strong>AIB{{ data.facture.taux_aib > 0 ? ` (${data.facture.taux_aib}%)` : '' }} :</strong> {{ data.facture.montant_aib || '0' }}</span>
        <span><strong>Avoir :</strong> {{ data.facture.avoir }}</span>
      </div>

      <table class="etat-reglement-table">
        <thead>
          <tr>
            <th>N° Ordre de règlement</th>
            <th>Date règlement</th>
            <th>Mode règlement</th>
            <th>Bénéficiaire</th>
            <th style="text-align: right;">Montant</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(reg, index) in data.reglements" :key="index">
            <td>{{ reg.numero_ordre }}</td>
            <td>{{ reg.date_reglement }}</td>
            <td>{{ reg.mode_paiement }}</td>
            <td>{{ reg.beneficiaire }}</td>
            <td style="text-align: right;">{{ reg.montant }}</td>
          </tr>
          <tr v-if="!data.reglements.length">
            <td colspan="5" style="text-align: center; font-style: italic;">Aucun règlement</td>
          </tr>
        </tbody>
      </table>

      <div class="etat-reglement-totaux">
        <div class="etat-reglement-total-row">
          <span class="etat-reglement-total-label">Total règlement :</span>
          <span class="etat-reglement-total-value">{{ data.total_reglements }}</span>
        </div>
        <div class="etat-reglement-total-row">
          <span class="etat-reglement-total-label">Montant Dû (Net à payer) :</span>
          <span class="etat-reglement-total-value">{{ data.montant_du }}</span>
        </div>
        <div class="etat-reglement-total-row">
          <span class="etat-reglement-total-label">Solde :</span>
          <span class="etat-reglement-total-value">{{ data.solde }}</span>
        </div>
      </div>

      <div style="text-align: right; margin-top: 20px;">
        <el-button type="primary" :icon="Download" @click="downloadPdf">Télécharger PDF</el-button>
      </div>
    </div>
    <div v-else style="text-align: center; padding: 40px; color: #909399;">Aucune donnée trouvée.</div>
  </el-drawer>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { Download, Refresh } from '@element-plus/icons-vue';
import { fetchApi } from '@/Composables/useFetch';

const visible = ref(false);
const loading = ref(false);
const data = ref(null);
const factureId = ref(null);

const open = async (id) => {
  factureId.value = id;
  data.value = null;
  loading.value = true;
  visible.value = true;
  try {
    const response = await fetchApi(`/api/factures-fournisseurs/${id}/etat-reglement-data`);
    const result = await response.json();
    if (result.success) {
      data.value = result;
    } else {
      ElMessage.warning(result.message || 'Données non trouvées');
    }
  } catch (err) {
    ElMessage.error('Erreur lors du chargement de l\'état de règlement');
  } finally {
    loading.value = false;
  }
};

const downloadPdf = () => {
  if (factureId.value) {
    window.open(`/factures-fournisseurs/${factureId.value}/etat-reglement-pdf`, '_blank');
  }
};

defineExpose({ open });
</script>

<style scoped>
.imputation-hospital-name { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
.imputation-hospital-info { font-size: 12px; color: #444; line-height: 1.6; margin-top: 4px; }
.imputation-title-box { margin: 20px auto 15px; display: inline-block; border: 2px solid #000; padding: 8px 30px; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
.etat-reglement-content { padding: 0 15px; font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.6; }
.etat-reglement-header { margin-bottom: 15px; text-align: center; }
.etat-reglement-fournisseur { margin: 10px 0; font-size: 13px; }
.etat-reglement-info { display: flex; gap: 25px; margin: 8px 0; font-size: 13px; }
.etat-reglement-info-framed { border: 1px solid #000; padding: 8px; }
.etat-reglement-objet { margin: 10px 0; font-size: 13px; }
.etat-reglement-montants { display: flex; gap: 40px; margin: 3px 0; font-size: 13px; }
.etat-reglement-table { width: 100%; border-collapse: collapse; font-size: 12px; margin: 15px 0; }
.etat-reglement-table th { border: 1px solid #000; padding: 6px 8px; font-weight: bold; text-align: center; font-size: 11px; background-color: #fff; }
.etat-reglement-table td { border: 1px solid #000; padding: 5px 8px; }
.etat-reglement-totaux { display: flex; flex-direction: column; align-items: flex-end; margin-top: 10px; }
.etat-reglement-total-row { display: flex; gap: 15px; padding: 3px 0; font-size: 13px; }
.etat-reglement-total-label { font-weight: bold; min-width: 140px; text-align: right; }
.etat-reglement-total-value { min-width: 100px; text-align: right; }
</style>
