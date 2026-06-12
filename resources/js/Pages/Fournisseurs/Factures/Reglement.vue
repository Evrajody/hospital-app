<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">{{ isEdit ? 'Modifier le Règlement' : 'Règlement de Facture' }}</h1>
          <p class="page-subtitle">{{ isEdit ? 'Mise à jour des informations de règlement' : 'Enregistrer un nouveau paiement' }}</p>
        </div>
        <el-button @click="handleCancel">
          <el-icon><ArrowLeft /></el-icon>
          Retour
        </el-button>
      </div>

      <el-row :gutter="20">
        <!-- Left Column: Invoice Summary & Payment History -->
        <el-col :span="10">
          <!-- Invoice Summary Card -->
          <el-card shadow="never" class="info-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Document /></el-icon>
                <span>Informations Facture</span>
              </div>
            </template>

            <div class="info-grid">
              <div class="info-row">
                <span class="info-label">N° Pièce :</span>
                <el-tag type="primary" size="large">{{ facture.numero }}</el-tag>
              </div>

              <div class="info-row">
                <span class="info-label">Fournisseur :</span>
                <div class="fournisseur-info">
                  <strong>{{ facture.fournisseur.nom }}</strong>
                </div>
              </div>

              <div class="info-row">
                <span class="info-label">Date Facture :</span>
                <span>{{ formatDate(facture.date_facture) }}</span>
              </div>

              <div class="info-row">
                <span class="info-label">Référence :</span>
                <span>{{ facture.reference || '-' }}</span>
              </div>

              <el-divider />

              <div class="montants-grid">
                <div class="montant-row">
                  <span class="montant-label">Montant HT :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_ht) }}</span>
                </div>
                <div class="montant-row">
                  <span class="montant-label">TVA (18%) :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_tva) }}</span>
                </div>
                <div class="montant-row" v-if="facture.montant_aib > 0">
                  <span class="montant-label">Mt AIB :</span>
                  <span class="montant-value">{{ formatMontant(facture.montant_aib) }}</span>
                </div>
                <div class="montant-row total-ttc">
                  <span class="montant-label"><strong>Total TTC :</strong></span>
                  <span class="montant-value"><strong>{{ formatMontant(facture.montant_ttc) }}</strong></span>
                </div>
                <div class="montant-row" v-if="facture.avoir > 0">
                  <span class="montant-label">Avoir :</span>
                  <span class="montant-value" style="color: #f56c6c;">- {{ formatMontant(facture.avoir) }}</span>
                </div>
                <div class="montant-row" v-if="facture.montant_reduction > 0">
                  <span class="montant-label">{{ facture.type_reduction_libelle || 'AIB' }} ({{ facture.taux }}%) :</span>
                  <span class="montant-value" style="color: #f56c6c;">- {{ formatMontant(facture.montant_reduction) }}</span>
                </div>
                <div class="montant-row" v-if="facture.montant_net && facture.montant_net !== facture.montant_ttc">
                  <span class="montant-label"><strong>Net à payer :</strong></span>
                  <span class="montant-value"><strong>{{ formatMontant(facture.montant_net) }}</strong></span>
                </div>

                <el-divider style="margin: 12px 0" />

                <div class="montant-row montant-paye">
                  <span class="montant-label">Déjà payé :</span>
                  <span class="montant-value paye">{{ formatMontant(facture.montant_paye) }}</span>
                </div>

                <div class="montant-row reste-a-payer">
                  <span class="montant-label"><strong>Reste à payer :</strong></span>
                  <span class="montant-value reste">
                    <strong>{{ formatMontant(resteAPayer) }}</strong>
                  </span>
                </div>
              </div>
            </div>
          </el-card>

          <!-- Payment History Card -->
          <el-card shadow="never" class="history-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Clock /></el-icon>
                <span>Historique des Règlements</span>
              </div>
            </template>

            <el-timeline v-if="reglements.length > 0">
              <el-timeline-item
                v-for="reglement in reglements"
                :key="reglement.id"
                :timestamp="formatDate(reglement.date_reglement)"
                placement="top"
                :type="reglement.id === editingReglementId ? 'warning' : 'primary'"
              >
                <el-card
                  class="reglement-item"
                  :class="{ 'reglement-item-editing': reglement.id === editingReglementId }"
                >
                  <div class="reglement-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <el-tag :type="getModeTagType(reglement.mode_paiement)" size="small">
                        {{ getModeLabel(reglement.mode_paiement) }}
                      </el-tag>
                      <el-tag v-if="reglement.id === editingReglementId" type="warning" size="small" effect="dark">
                        En cours de modification
                      </el-tag>
                    </div>
                    <strong class="reglement-montant">{{ formatMontant(reglement.montant) }}</strong>
                  </div>
                  <div class="reglement-details">
                    <div v-if="reglement.numero_complet" style="font-weight: 600; color: #374151;">
                      N° {{ reglement.numero_complet }}
                    </div>
                    <div v-if="reglement.beneficiaire">
                      <el-icon><User /></el-icon>
                      {{ reglement.beneficiaire }}
                    </div>
                    <div v-if="reglement.reference">
                      <el-icon><DocumentCopy /></el-icon>
                      Réf: {{ reglement.reference }}
                    </div>
                    <div v-if="reglement.compte_bancaire">
                      <el-icon><CreditCard /></el-icon>
                      {{ reglement.compte_bancaire.libelle }}
                    </div>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>

            <el-empty v-else description="Aucun règlement enregistré" :image-size="80" />
          </el-card>
        </el-col>

        <!-- Right Column: New Payment Form -->
        <el-col :span="14">
          <el-card shadow="never" class="form-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Money /></el-icon>
                <span>Nouveau Règlement</span>
              </div>
            </template>

            <el-form
              ref="formRef"
              :model="form"
              :rules="rules"
              label-position="top"
              size="large"
              @submit.prevent="handleSubmit"
            >
              <el-row :gutter="20">
                <el-col :span="6">
                  <el-form-item label="Année d'exercice" prop="annee_exercice">
                    <el-date-picker
                      v-model="form.annee_exercice"
                      type="year"
                      placeholder="Exercice"
                      format="YYYY"
                      value-format="YYYY"
                      :disabled-date="disableFutureYears"
                      style="width: 100%"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6">
                  <el-form-item label="N° de ligne" prop="numero_ligne">
                    <el-input
                      v-model="form.numero_ligne"
                      placeholder="001"
                      readonly
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6">
                  <el-form-item label="Date Règlement" prop="date_reglement">
                    <el-date-picker
                      v-model="form.date_reglement"
                      type="date"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      format="DD/MM/YYYY"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6">
                  <el-form-item label="Montant payé" prop="montant">
                    <el-input
                      :model-value="formatInputMontant(form.montant)"
                      @input="val => hasCompteCredits ? null : (form.montant = parseInputMontant(val))"
                      placeholder="0"
                      :prefix-icon="Money"
                      :readonly="hasCompteCredits"
                    >
                      <template #append>XOF</template>
                    </el-input>
                    <div class="form-hint">
                      <template v-if="hasCompteCredits">Calculé : Σ lignes − AIB</template>
                      <!-- <template v-else>Montant à régler (compte fournisseur par défaut)</template> -->
                    </div>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- Info quand pas d'imputations crédit sur la facture -->
              <el-alert
                v-if="!hasCompteCredits"
                type="info"
                :closable="false"
                show-icon
                style="margin-bottom: 16px"
              >
                <template #title>
                  <span style="font-size: 13px;">
                    La facture n'a pas d'imputation comptable détaillée. Le règlement utilisera le compte fournisseur par défaut.
                  </span>
                </template>
              </el-alert>

              <!-- Multi-fournisseur : tableau des comptes à régler -->
              <el-row :gutter="20" v-if="hasCompteCredits">
                <el-col :span="24">
                  <el-form-item>
                    <template #label>
                      <span>Comptes fournisseurs à régler <span class="required-star">*</span></span>
                    </template>
                    <el-table :data="form.lignes" border size="small" style="width: 100%" class="lignes-reglement-table">
                      <el-table-column label="Compte fournisseur" min-width="320">
                        <template #default="{ row }">
                          <el-select
                            v-model="row.compte_id"
                            placeholder="Sélectionner"
                            filterable
                            style="width: 100%"
                          >
                            <el-option
                              v-for="ic in facture.imputations_credits"
                              :key="ic.compte_id"
                              :value="ic.compte_id"
                              :label="`${ic.numero_compte} - ${ic.libelle_compte}`"
                              :disabled="isCompteAlreadySelected(ic.compte_id, row)"
                            >
                              <span>
                                <el-tag size="small" type="success">{{ ic.numero_compte }}</el-tag>
                                <span style="margin-left: 8px;">{{ ic.libelle_compte }}</span>
                              </span>
                            </el-option>
                          </el-select>
                        </template>
                      </el-table-column>
                      <el-table-column label="Montant à solder (HT)" width="240">
                        <template #default="{ row }">
                          <div class="montant-cell">
                            <el-input
                              :model-value="formatInputMontant(row.montant)"
                              @input="val => onLigneMontantInput(row, val)"
                              placeholder="0"
                              style="width: 100%"
                              :class="{ 'montant-depasse': ligneDepasse(row) }"
                            >
                              <template #append>XOF</template>
                            </el-input>
                            <div v-if="ligneDepasse(row)" class="montant-warning">
                              <el-icon><WarningFilled /></el-icon>
                              Dépasse le reste HT ({{ formatMontant(getCompteInfo(row.compte_id).reste_a_payer) }})
                            </div>
                          </div>
                        </template>
                      </el-table-column>
                      <el-table-column label="" width="60" align="center">
                        <template #default="{ $index }">
                          <el-button
                            type="danger"
                            link
                            :icon="Delete"
                            :disabled="form.lignes.length <= 1"
                            @click="removeLigne($index)"
                          />
                        </template>
                      </el-table-column>
                    </el-table>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                      <el-button
                        v-if="canAddLigne"
                        :icon="Plus"
                        size="small"
                        @click="addLigne"
                        type="primary"
                        plain
                      >
                        Ajouter un compte
                      </el-button>
                      <span v-else style="font-size: 12px; color: #9ca3af; font-style: italic;">
                        Tous les comptes fournisseurs de la facture sont déjà ajoutés.
                      </span>
                      <div style="font-size: 13px;">
                        <span style="color: #6b7280;">Total à solder : </span>
                        <strong style="font-family: monospace;">{{ formatMontant(totalLignes) }}</strong>
                      </div>
                    </div>
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :span="6">
                  <el-form-item label="Mode de Paiement" prop="mode_paiement">
                    <el-select
                      v-model="form.mode_paiement"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      @change="handleModeChange"
                    >
                      <el-option label="Espèces" value="especes" />
                      <el-option label="Chèque" value="cheque" />
                      <el-option label="Virement bancaire" value="virement" />
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField">
                  <el-form-item prop="banque_id">
                    <template #label>
                      <span>Banque <span class="required-star">*</span></span>
                    </template>
                    <el-select
                      v-model="form.banque_id"
                      filterable
                      placeholder="Sélectionner une banque"
                      style="width: 100%"
                      @change="handleBanqueChange"
                    >
                      <el-option
                        v-for="banque in banques"
                        :key="banque.id"
                        :label="banque.nom"
                        :value="banque.id"
                      />
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField && form.banque_id">
                  <el-form-item prop="compte_bancaire_id">
                    <template #label>
                      <span>Compte bancaire <span class="required-star">*</span></span>
                    </template>
                    <el-select
                      v-model="form.compte_bancaire_id"
                      filterable
                      placeholder="Sélectionner un compte"
                      style="width: 100%"
                    >
                      <el-option
                        v-for="compte in filteredComptes"
                        :key="compte.id"
                        :label="compte.numero_compte"
                        :value="compte.id"
                      >
                        <div class="compte-option">
                          <span>{{ compte.numero_compte }}</span>
                          <span class="compte-solde">Solde: {{ formatMontant(compte.solde) }}</span>
                        </div>
                      </el-option>
                    </el-select>
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField">
                  <el-form-item prop="reference">
                    <template #label>
                      <span>Référence <span class="required-star">*</span></span>
                    </template>
                    <el-input
                      v-model="form.reference"
                      :placeholder="form.mode_paiement === 'cheque' ? 'N° du chèque' : 'Réf. virement'"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="6" v-if="showBankField">
                  <el-form-item prop="date_reference">
                    <template #label>
                      <span>Date Référence <span class="required-star">*</span></span>
                    </template>
                    <el-date-picker
                      v-model="form.date_reference"
                      type="date"
                      placeholder="Sélectionner"
                      style="width: 100%"
                      format="DD/MM/YYYY"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-row :gutter="20">
                <el-col :span="12">
                  <el-form-item label="B&eacute;n&eacute;ficiaire">
                    <el-autocomplete
                      v-model="form.beneficiaire"
                      :fetch-suggestions="fetchBeneficiaireSuggestions"
                      placeholder="Nom du b&eacute;n&eacute;ficiaire du ch&egrave;que"
                      clearable
                      style="width: 100%"
                      :trigger-on-focus="true"
                    />
                  </el-form-item>
                </el-col>

                <el-col :span="12" v-if="hasAib && !aibDejaDeclaree">
                  <el-form-item label=" ">
                    <el-checkbox
                      v-model="form.deduire_aib"
                      :label="`Déclarer l'AIB (${facture.taux || 0}%)`"
                      size="large"
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="12" v-else-if="hasAib && aibDejaDeclaree">
                  <el-form-item label=" ">
                    <el-tag type="success" size="large">AIB déjà déclaré</el-tag>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- Solde du compte sélectionné -->
              <el-alert
                v-if="selectedCompte"
                :type="selectedCompte.solde >= (form.montant || 0) ? 'success' : 'warning'"
                :closable="false"
                style="margin-bottom: 20px"
              >
                <template #title>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Solde du compte {{ selectedCompte.numero_compte }} :</span>
                    <strong>{{ formatMontant(selectedCompte.solde) }}</strong>
                  </div>
                </template>
              </el-alert>

              <el-form-item label="Notes / Remarques">
                <el-input
                  v-model="form.remarques"
                  type="textarea"
                  :rows="3"
                  placeholder="Informations complémentaires..."
                />
              </el-form-item>

              <!-- Payment Summary -->
              <el-alert
                type="success"
                :closable="false"
                style="margin-bottom: 20px"
              >
                <template #title>
                  <div class="payment-summary">
                    <div class="summary-row">
                      <span><strong>Total HT à solder sur fournisseurs :</strong></span>
                      <strong>{{ formatMontant(totalLignes) }}</strong>
                    </div>
                    <template v-if="form.deduire_aib && hasAib">
                      <div class="summary-row" style="color: #e6a23c;">
                        <span>− AIB retenu ({{ facture.taux }}% sur M.O. {{ formatMontant(facture.montant_mo) }}) :</span>
                        <strong>{{ formatMontant(montantAibAttendu) }}</strong>
                      </div>
                    </template>
                    <div class="summary-row aib-total-row">
                      <span><strong>Cash payé en banque/caisse :</strong></span>
                      <strong style="color: #2563eb;">{{ formatMontant(form.montant) }}</strong>
                    </div>
                    <div class="summary-row" style="font-size: 12px; color: #6b7280; font-style: italic; margin-top: 4px;">
                      <span>(La TVA n'est pas remise au fournisseur — modèle "TVA pour compte de l'État")</span>
                    </div>
                  </div>
                </template>
              </el-alert>

              <!-- Action Buttons -->
              <div class="form-actions">
                <el-button size="large" @click="handleCancel">
                  Annuler
                </el-button>
                <el-button
                  type="primary"
                  size="large"
                  :loading="submitting"
                  native-type="submit"
                >
                  <el-icon v-if="!submitting"><Check /></el-icon>
                  {{ isEdit ? 'Mettre à jour le Règlement' : 'Enregistrer le Règlement' }}
                </el-button>
              </div>
            </el-form>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Modal Solde Insuffisant -->
    <el-dialog
      v-model="showInsufficientModal"
      title="Solde insuffisant"
      width="450px"
      :close-on-click-modal="false"
    >
      <div style="text-align: center; padding: 10px 0;">
        <el-icon :size="48" color="#E6A23C" style="margin-bottom: 16px;"><WarningFilled /></el-icon>
        <p style="font-size: 15px; margin-bottom: 12px;">
          Le solde du compte est insuffisant pour ce règlement.
        </p>
        <div style="background: #f5f7fa; padding: 16px; border-radius: 8px; margin-bottom: 12px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span>Solde actuel :</span>
            <strong>{{ formatMontant(insufficientData.solde) }}</strong>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span>Montant demandé :</span>
            <strong style="color: #F56C6C;">{{ formatMontant(insufficientData.montant) }}</strong>
          </div>
          <el-divider style="margin: 8px 0" />
          <div style="display: flex; justify-content: space-between;">
            <span>Nouveau solde :</span>
            <strong style="color: #F56C6C;">{{ formatMontant(insufficientData.solde - insufficientData.montant) }}</strong>
          </div>
        </div>
        <p style="font-size: 13px; color: #909399;">
          Voulez-vous quand même valider ce règlement ?
        </p>
      </div>
      <template #footer>
        <el-button @click="showInsufficientModal = false">Annuler</el-button>
        <el-button type="warning" :loading="submitting" @click="forceSubmit">
          Confirmer le règlement
        </el-button>
      </template>
    </el-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  ArrowLeft,
  Document,
  Clock,
  Money,
  CreditCard,
  DocumentCopy,
  User,
  Check,
  SuccessFilled,
  WarningFilled
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useMontant } from '@/Composables/useMontant';
import { fetchApi } from '@/Composables/useFetch';

// Props
const props = defineProps({
  facture: {
    type: Object,
    required: true
  },
  reglements: {
    type: Array,
    default: () => []
  },
  banques: {
    type: Array,
    default: () => []
  },
  beneficiaires: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: () => null
  }
});

const fetchBeneficiaireSuggestions = (query, cb) => {
  const source = (props.beneficiaires || []).map(name => ({ value: name }));
  if (!query) return cb(source);
  const q = query.toLowerCase();
  cb(source.filter(s => s.value.toLowerCase().includes(q)));
};

// Computed
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Fournisseurs', path: '/factures-fournisseurs' },
  { title: props.facture.numero, path: `/factures-fournisseurs/${props.facture.id}` },
  { title: 'Règlement', path: '' }
];

const resteAPayer = computed(() => {
  if (props.facture.reste_a_payer !== undefined && props.facture.reste_a_payer !== null) {
    return parseFloat(props.facture.reste_a_payer) || 0;
  }
  const montantNet = parseFloat(props.facture.montant_net) || parseFloat(props.facture.montant_ttc) || 0;
  const montantPaye = parseFloat(props.facture.montant_paye) || 0;
  return montantNet - montantPaye;
});

const aibDejaDeclaree = computed(() => {
  return props.reglements.some(r => r.deduire_aib);
});

// La facture a un AIB si elle a un compte AIB OU un taux > 0 OU un montant AIB calculé
const hasAib = computed(() => {
  const taux = parseFloat(props.facture.taux) || 0;
  const mtAib = parseFloat(props.facture.montant_aib || props.facture.montant_reduction) || 0;
  return !!(props.facture.type_reduction || taux > 0 || mtAib > 0);
});

// Comptes crédit (fournisseurs 401/481) saisis dans l'imputation comptable de la facture
const hasCompteCredits = computed(() =>
  Array.isArray(props.facture.imputations_credits) && props.facture.imputations_credits.length > 0
);

// Helpers multi-lignes
const getCompteInfo = (compteId) => {
  if (!compteId) return null;
  return (props.facture.imputations_credits || []).find(ic => ic.compte_id === compteId) || null;
};

const isCompteAlreadySelected = (compteId, currentRow) => {
  if (!compteId) return false;
  return form.lignes.some(l => l !== currentRow && l.compte_id === compteId);
};

// Une ligne dépasse-t-elle le reste HT du compte choisi ?
const ligneDepasse = (row) => {
  const info = getCompteInfo(row.compte_id);
  if (!info) return false;
  return parseFloat(row.montant) > parseFloat(info.reste_a_payer) + 0.01;
};

const totalLignes = computed(() =>
  (form.lignes || []).reduce((sum, l) => sum + (parseFloat(l.montant) || 0), 0)
);

// Peut-on ajouter une ligne ? (Reste-t-il un compte non sélectionné parmi les imputations crédit ?)
const canAddLigne = computed(() => {
  const totalComptes = (props.facture.imputations_credits || []).length;
  const comptesSelectionnes = new Set(
    (form.lignes || []).filter(l => l.compte_id).map(l => l.compte_id)
  );
  return comptesSelectionnes.size < totalComptes;
});

// AIB amount (calculé sur facture si déclaré)
const montantAibAttendu = computed(() => {
  if (!form.deduire_aib || !hasAib.value) return 0;
  return parseFloat(props.facture.montant_aib) || 0;
});

// Bank cash effectivement payé = total des lignes - AIB
const montantBankCash = computed(() => {
  return Math.max(0, totalLignes.value - montantAibAttendu.value);
});

const newReste = computed(() => {
  return Math.max(0, resteAPayer.value - (form.montant || 0));
});

const showBankField = computed(() => {
  return ['cheque', 'virement'].includes(form.mode_paiement);
});

const filteredComptes = computed(() => {
  if (!form.banque_id) return [];
  const banque = props.banques.find(b => b.id === form.banque_id);
  return banque ? banque.comptes : [];
});

const selectedCompte = computed(() => {
  if (!form.compte_bancaire_id) return null;
  for (const banque of props.banques) {
    const compte = banque.comptes.find(c => c.id === form.compte_bancaire_id);
    if (compte) return compte;
  }
  return null;
});

// State
const formRef = ref(null);
const submitting = ref(false);
const showInsufficientModal = ref(false);

// Mode édition : détecte ?edit=ID dans l'URL
const editingReglementId = ref(null);
const ancienMontantReglement = ref(0); // montant du règlement avant modification
const isEdit = computed(() => !!editingReglementId.value);

if (typeof window !== 'undefined') {
  const params = new URLSearchParams(window.location.search);
  const editId = params.get('edit');
  if (editId && /^\d+$/.test(editId)) {
    editingReglementId.value = parseInt(editId, 10);
  }
}

// Plafond autorisé pour le montant : reste_a_payer + ancien montant (si édition)
const montantMaxAutorise = computed(() => {
  return resteAPayer.value + (isEdit.value ? ancienMontantReglement.value : 0);
});

// Empêche la sélection d'une année future
const disableFutureYears = (date) => {
  return date.getFullYear() > new Date().getFullYear();
};
const insufficientData = reactive({ solde: 0, montant: 0 });

const form = reactive({
  annee_exercice: new Date().getFullYear().toString(),
  numero_ligne: String(props.reglements.length + 1),
  date_reglement: new Date(),
  montant: 0, // calculé : totalLignes - AIB
  mode_paiement: '',
  banque_id: null,
  compte_bancaire_id: null,
  reference: '',
  date_reference: null,
  beneficiaire: '',
  deduire_aib: false,
  remarques: '',
  lignes: [], // Multi-fournisseur
});

// Synchroniser form.montant = bank cash (calculé) uniquement si la facture a des imputations crédit
// (sinon l'utilisateur saisit le montant directement)
watch(montantBankCash, (val) => {
  if (hasCompteCredits.value) {
    form.montant = val;
  }
});

// Initialiser au moins une ligne vide
watch(() => props.facture.imputations_credits, (val) => {
  if (Array.isArray(val) && val.length > 0 && form.lignes.length === 0) {
    form.lignes.push({
      compte_id: null,
      montant: 0,
      libelle: '',
    });
  }
}, { immediate: true });

const addLigne = () => {
  form.lignes.push({
    compte_id: null,
    montant: 0,
    libelle: '',
  });
};

const removeLigne = (index) => {
  form.lignes.splice(index, 1);
};

// Quand l'utilisateur saisit un montant manuellement → avertir s'il dépasse le reste HT
const onLigneMontantInput = (row, val) => {
  row.montant = parseInputMontant(val);
  const info = getCompteInfo(row.compte_id);
  if (info && parseFloat(row.montant) > parseFloat(info.reste_a_payer) + 0.01) {
    ElMessage.warning({
      message: `Montant supérieur au reste HT (${formatMontant(info.reste_a_payer)}) pour le compte ${info.numero_compte}.`,
      duration: 2500,
      grouping: true,
    });
  }
};

// L'AIB est déjà déduit du montant net de la facture, pas de calcul ici

// Validation rules
const rules = {
  date_reglement: [
    { required: true, message: 'La date est obligatoire', trigger: 'change' }
  ],
  mode_paiement: [
    { required: true, message: 'Le mode de paiement est obligatoire', trigger: 'change' }
  ],
  montant: [
    { required: true, message: 'Le montant est obligatoire', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value <= 0) {
          callback(new Error('Le montant doit être supérieur à 0'));
        } else if (value > montantMaxAutorise.value + 0.01) {
          callback(new Error(`Le montant ne peut pas dépasser ${formatMontant(montantMaxAutorise.value)}`));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ],
  banque_id: [
    {
      validator: (rule, value, callback) => {
        if (showBankField.value && !value) {
          callback(new Error('La banque est obligatoire'));
        } else {
          callback();
        }
      },
      trigger: 'change'
    }
  ],
  compte_bancaire_id: [
    {
      validator: (rule, value, callback) => {
        if (showBankField.value && form.banque_id && !value) {
          callback(new Error('Le compte bancaire est obligatoire'));
        } else {
          callback();
        }
      },
      trigger: 'change'
    }
  ],
};

// Methods
const { formatMontant, formatInputMontant, parseInputMontant } = useMontant();

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getModeTagType = (mode) => {
  const types = {
    especes: 'success',
    cheque: 'primary',
    virement: 'info',
    carte: 'warning',
    mobile_money: 'success'
  };
  return types[mode] || 'info';
};

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

const handleModeChange = () => {
  form.banque_id = null;
  form.compte_bancaire_id = null;
  form.reference = '';
  form.date_reference = null;
};

const handleBanqueChange = () => {
  form.compte_bancaire_id = null;
};

const handleCancel = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/factures-fournisseurs');
  }
};

const buildPayload = (forceInsufficient = false) => {
  const dateReglement = form.date_reglement instanceof Date
    ? form.date_reglement.toISOString().split('T')[0]
    : form.date_reglement;

  const selectedBanque = form.banque_id
    ? props.banques.find(b => b.id === form.banque_id)
    : null;

  return {
    facture_id: props.facture.id,
    annee_exercice: form.annee_exercice,
    numero_ligne: form.numero_ligne || null,
    date_reglement: dateReglement,
    montant: form.montant, // bank cash = totalLignes - AIB
    mode_paiement: form.mode_paiement,
    reference: form.reference || null,
    date_reference: form.date_reference instanceof Date
      ? form.date_reference.toISOString().split('T')[0]
      : form.date_reference,
    beneficiaire: form.beneficiaire || null,
    banque: selectedBanque ? selectedBanque.nom : null,
    compte_bancaire_id: form.compte_bancaire_id || null,
    numero_compte_bancaire: selectedCompte.value ? selectedCompte.value.numero_compte : null,
    observations: form.remarques || null,
    deduire_aib: form.deduire_aib || false,
    date_aib: form.deduire_aib ? new Date().toISOString().split('T')[0] : null,
    force_insufficient_balance: forceInsufficient,
    lignes: form.lignes
      .filter(l => l.compte_id && parseFloat(l.montant) > 0)
      .map(l => ({
        compte_id: l.compte_id,
        montant: parseFloat(l.montant) || 0,
        libelle: l.libelle || null,
      })),
  };
};

const submitPayment = async (forceInsufficient = false) => {
  submitting.value = true;

  const url = isEdit.value
    ? `/api/reglements-fournisseurs/${editingReglementId.value}`
    : '/api/reglements-fournisseurs';
  const method = isEdit.value ? 'PUT' : 'POST';

  try {
    const response = await fetchApi(url, {
      method,
      body: buildPayload(forceInsufficient)
    });

    const data = await response.json();

    if (data.success) {
      ElMessage.success(data.message || (isEdit.value ? 'Règlement modifié avec succès' : 'Règlement enregistré avec succès'));

      const isEspeces = form.mode_paiement === 'especes';

      if (isEspeces) {
        ElMessage.info('Règlement en espèces : pas d\'écritures comptables à générer.');
        router.visit(`/factures-fournisseurs/${props.facture.id}`);
        return;
      }

      const confirmed = await ElMessageBox.confirm(
        'Autorisez-vous une imputation comptable à l\'enregistrement de cette pièce comptable ?',
        'Imputation comptable',
        {
          confirmButtonText: 'Oui',
          cancelButtonText: 'Non',
          type: 'info',
        }
      ).catch(() => false);

      let ecrituresGenerees = false;
      if (confirmed) {
        // Génération globale : facture + tous ses règlements
        try {
          const r = await fetchApi(`/api/factures-fournisseurs/${props.facture.id}/imputation`, { method: 'POST' });
          const j = await r.json();
          if (j.success) {
            ElMessage.success(j.message || 'Écritures générées');
            ecrituresGenerees = true;
          } else if (j.reason === 'missing') {
            await ElMessageBox.alert(j.message, 'Imputations manquantes', { type: 'warning', confirmButtonText: 'OK' });
          } else if (j.reason === 'incomplete') {
            await ElMessageBox.alert(j.message, 'Imputations incomplètes', { type: 'warning', confirmButtonText: 'OK' });
          } else {
            ElMessage.error(j.message || 'Erreur lors de la génération');
          }
        } catch {
          ElMessage.error('Erreur de connexion');
        }
      }

      // Si écritures générées avec succès, on ouvre directement l'offcanvas sur la fiche facture
      router.visit(`/factures-fournisseurs/${props.facture.id}${ecrituresGenerees ? '?show_imputation=1' : ''}`);
    } else if (data.insufficient_balance) {
      insufficientData.solde = data.solde_actuel;
      insufficientData.montant = data.montant_demande;
      showInsufficientModal.value = true;
    } else {
      ElMessage.error(data.message || 'Erreur lors de l\'enregistrement');
      if (data.errors) {
        Object.values(data.errors).flat().forEach(error => {
          ElMessage.warning(error);
        });
      }
    }
  } catch (error) {
    console.error('Error:', error);
    ElMessage.error('Erreur lors de l\'enregistrement du règlement');
  } finally {
    submitting.value = false;
  }
};

// Pré-remplir le formulaire en mode édition
const loadReglementForEdit = async () => {
  if (!editingReglementId.value) return;
  try {
    const response = await fetchApi(`/api/reglements-fournisseurs/${editingReglementId.value}`);
    const data = await response.json();
    if (!data.success || !data.data) return;
    const r = data.data;

    // Mémoriser l'ancien montant pour calculer le plafond autorisé
    ancienMontantReglement.value = parseFloat(r.montant) || 0;

    if (r.numero_ligne) form.numero_ligne = r.numero_ligne;
    form.date_reglement = r.date_reglement || form.date_reglement;
    form.montant = parseFloat(r.montant) || 0;
    form.mode_paiement = r.mode_paiement || '';
    form.reference = r.reference || '';
    form.date_reference = r.date_reference || null;
    form.beneficiaire = r.beneficiaire || '';
    form.remarques = r.observations || '';
    form.deduire_aib = !!r.deduire_aib;

    // Charger les lignes (multi-fournisseur)
    if (Array.isArray(r.lignes) && r.lignes.length > 0) {
      form.lignes = r.lignes.map(l => ({
        compte_id: l.compte_id,
        montant: parseFloat(l.montant) || 0,
        libelle: l.libelle || '',
      }));
    } else if (r.compte_credit_id) {
      // Legacy : 1 seule ligne basée sur compte_credit_id
      form.lignes = [{
        compte_id: r.compte_credit_id,
        montant: (parseFloat(r.montant) || 0) + (parseFloat(r.montant_aib_deduit) || 0),
        libelle: '',
      }];
    }

    // Restaurer la banque + le compte bancaire à partir du nom de banque et du
    // numéro de compte stockés (l'id du compte bancaire n'est pas persisté tel quel).
    if (r.banque) {
      const banque = props.banques.find(b => b.nom === r.banque);
      if (banque) {
        form.banque_id = banque.id;
        const compte = (banque.comptes || []).find(c => c.numero_compte === r.numero_compte_bancaire);
        if (compte) form.compte_bancaire_id = compte.id;
      }
    }
  } catch (e) {
    console.error('Erreur chargement règlement à éditer', e);
    ElMessage.error('Impossible de charger le règlement à modifier');
  }
};

// Charger les données au montage si mode édition
if (editingReglementId.value) {
  loadReglementForEdit();
}

const handleSubmit = async () => {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    // Validation lignes (multi-fournisseur)
    if (hasCompteCredits.value) {
      const lignesValides = form.lignes.filter(l => l.compte_id && parseFloat(l.montant) > 0);
      if (lignesValides.length === 0) {
        ElMessage.error('Au moins une ligne avec compte et montant est obligatoire.');
        return;
      }
      // Si AIB déclaré : AIB ≤ total lignes
      if (form.deduire_aib && hasAib.value && montantAibAttendu.value > totalLignes.value + 0.01) {
        ElMessage.error('L\'AIB ne peut pas dépasser le total à solder.');
        return;
      }
    }

    // Vérifier le solde du compte bancaire si applicable
    if (selectedCompte.value && form.montant > 0) {
      const solde = parseFloat(selectedCompte.value.solde) || 0;
      if (solde < form.montant) {
        insufficientData.solde = solde;
        insufficientData.montant = form.montant;
        showInsufficientModal.value = true;
        return;
      }
    }

    await submitPayment(false);
  });
};

const forceSubmit = async () => {
  showInsufficientModal.value = false;
  await submitPayment(true);
};
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.page-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.info-card,
.history-card,
.form-card {
  border-radius: 8px;
  margin-bottom: 20px;
}

.card-header-custom {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.info-grid,
.montants-grid {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-row,
.montant-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-label,
.montant-label {
  font-size: 14px;
  color: #6b7280;
}

.fournisseur-info {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.montant-value {
  font-family: 'Courier New', monospace;
  font-size: 15px;
  color: #1f2937;
}

.total-ttc .montant-value,
.reste-a-payer .montant-value {
  font-size: 18px;
  font-weight: 700;
}

.montant-paye .montant-value.paye {
  color: #059669;
}

.reste-a-payer .montant-value.reste {
  color: #dc2626;
}

.reglement-item {
  box-shadow: none;
  border: 1px solid #e5e7eb;
  transition: all 0.2s ease;
}

.reglement-item-editing {
  border: 2px solid #e6a23c;
  background-color: #fdf6ec;
  box-shadow: 0 2px 8px rgba(230, 162, 60, 0.15);
}

.reglement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.reglement-montant {
  font-size: 16px;
  color: #059669;
}

.reglement-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
}

.reglement-details > div {
  display: flex;
  align-items: center;
  gap: 6px;
}

.compte-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.compte-solde {
  font-size: 12px;
  color: #909399;
}

.payment-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 14px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

.aib-total-row {
  border-top: 1px dashed #d1d5db;
  margin-top: 4px;
  padding-top: 4px;
  font-size: 14px;
}

/* Lignes du règlement multi-fournisseur — alignement vertical propre */
.lignes-reglement-table :deep(.el-table__cell) {
  vertical-align: middle;
}

.lignes-reglement-table :deep(.cell) {
  display: flex;
  align-items: center;
}

.montant-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
  width: 100%;
}

.montant-cell :deep(.montant-depasse .el-input__wrapper) {
  box-shadow: 0 0 0 1px #f56c6c inset !important;
  background-color: #fef2f2;
}

.montant-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #f56c6c;
  font-weight: 500;
  line-height: 1.3;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

:deep(.el-timeline-item__timestamp) {
  font-weight: 600;
  color: #6b7280;
}

.aib-info {
  margin-top: 4px;
  font-size: 13px;
  color: #e6a23c;
}

.required-star {
  color: #f56c6c;
  margin-left: 2px;
}
</style>
