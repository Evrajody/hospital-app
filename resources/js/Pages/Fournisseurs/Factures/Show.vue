<template>
  <AppLayout :user="user" :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-left">
          <el-tag :type="getStatutType(facture.statut_paiement)" size="large">
            {{ getStatutLabel(facture.statut_paiement) }}
          </el-tag>
          <el-tag
            v-if="!facture.has_imputation"
            type="info"
            size="default"
            effect="plain"
            style="margin-left: 8px;"
          >
            Imputation non saisie
          </el-tag>
          <el-tag
            v-else
            :type="facture.has_ecritures_facture ? 'success' : 'warning'"
            size="default"
            effect="plain"
            style="margin-left: 8px;"
          >
            {{ facture.has_ecritures_facture ? 'Écritures générées' : 'Écritures non générées' }}
          </el-tag>
          <div>
            <h1 class="page-title">{{ facture.numero }}</h1>
            <p class="page-subtitle">
              Fournisseur: {{ facture.fournisseur.nom }} •
              Date: {{ formatDate(facture.date_facture) }}
            </p>
          </div>
        </div>

        <div class="header-actions">
          <el-button @click="handleBack">
            <el-icon><ArrowLeft /></el-icon>
            Retour
          </el-button>
          <el-dropdown @command="handleAction" trigger="click">
            <el-button type="primary">
              Actions
              <el-icon class="el-icon--right"><ArrowDown /></el-icon>
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item
                  v-if="!estSoldee"
                  command="pay"
                  :icon="Money"
                >
                  Enregistrer un règlement
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="!estSoldee"
                  command="marquer_soldee"
                  :icon="CircleCheck"
                  divided
                >
                  Marquer comme soldée
                </el-dropdown-item>
                <el-dropdown-item v-if="!estSoldee" command="edit" :icon="Edit">
                  Modifier
                </el-dropdown-item>
                <el-dropdown-item command="etat_reglement" :icon="Document">
                  État de Règlement
                </el-dropdown-item>
                <!-- <el-dropdown-item command="print" :icon="Printer">
                  Imprimer
                </el-dropdown-item> -->
                <el-dropdown-item
                  v-if="facture.has_ecritures_facture"
                  command="imputation"
                  :icon="Document"
                  divided
                >
                  Voir Imputation Comptable
                </el-dropdown-item>
                <el-dropdown-item divided command="delete" :icon="Delete">
                  <span style="color: #f56c6c">Supprimer</span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>

      <!-- FactureFournisseurModal for editing -->
      <FactureFournisseurModal
        v-model="showFactureModal"
        :facture="selectedFacture"
        :fournisseurs="fournisseurs"
        :imputations="imputations"
        :comptes="comptes"
        :comptes-aib="comptesAib"
        @success="handleFactureSuccess"
      />

      <el-row :gutter="20">
        <!-- Left Column: Invoice Details -->
        <el-col :span="16">
          <!-- Invoice Info Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Document /></el-icon>
                <span>Informations Générales</span>
              </div>
            </template>

            <el-descriptions :column="2" border>
              <el-descriptions-item label="N° Pièce">
                <el-tag type="primary">{{ facture.numero_piece || facture.numero }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Date">
                {{ formatDate(facture.date || facture.date_facture) }}
              </el-descriptions-item>
              <el-descriptions-item label="Référence Facture / N° B.C" :span="2">
                {{ facture.reference_facture || facture.reference || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="Imputations comptables" :span="2">
                <div v-if="facture.imputations && facture.imputations.length > 0">
                  <!-- Bloc Débits -->
                  <div v-if="imputationsDebits.length > 0 || tvaAutoLigne" class="imputations-list">
                    <div class="imputations-block-title block-debit-title">Débits</div>
                    <div v-for="imp in imputationsDebits" :key="imp.id" class="imputation-row">
                      <el-tag size="small" type="primary">{{ imp.compte ? imp.compte.numero : '-' }}</el-tag>
                      <span class="imputation-libelle">{{ imp.compte ? imp.compte.libelle : (imp.libelle || '-') }}</span>
                      <span class="imputation-montant">{{ formatMontant(imp.montant) }}</span>
                    </div>
                    <!-- Ligne TVA auto-générée -->
                    <div v-if="tvaAutoLigne" class="imputation-row imputation-row-auto">
                      <el-tag size="small" type="warning">{{ tvaAutoLigne.numero }}</el-tag>
                      <span class="imputation-libelle">
                        {{ tvaAutoLigne.libelle }}
                        <el-tag size="small" effect="plain" style="margin-left: 6px; font-size: 10px;">auto</el-tag>
                      </span>
                      <span class="imputation-montant">{{ formatMontant(tvaAutoLigne.montant) }}</span>
                    </div>
                  </div>
                  <!-- Bloc Crédits -->
                  <div v-if="imputationsCredits.length > 0" class="imputations-list" style="margin-top: 8px;">
                    <div class="imputations-block-title block-credit-title">Crédits (Fournisseurs)</div>
                    <div v-for="imp in imputationsCredits" :key="imp.id" class="imputation-row">
                      <el-tag size="small" type="success">{{ imp.compte ? imp.compte.numero : '-' }}</el-tag>
                      <span class="imputation-libelle">{{ imp.compte ? imp.compte.libelle : (imp.libelle || '-') }}</span>
                      <span class="imputation-montant">{{ formatMontant(imp.montant) }}</span>
                    </div>
                  </div>
                </div>
                <div v-else-if="facture.compte" class="compte-info">
                  <el-tag size="small" type="info">{{ facture.compte.numero }}</el-tag>
                  <span>{{ facture.compte.libelle }}</span>
                </div>
                <span v-else>-</span>
              </el-descriptions-item>
              <el-descriptions-item label="Fournisseur" :span="2">
                <div class="fournisseur-info">
                  <div>
                    <strong>{{ facture.fournisseur.nom }}</strong>
                    <!-- <el-tag size="small" type="info" style="margin-left: 8px;">
                      {{ facture.fournisseur.code }}
                    </el-tag> -->
                  </div>
                  <div class="fournisseur-details">
                    <span v-if="facture.fournisseur.contact">
                      <el-icon><User /></el-icon>
                      {{ facture.fournisseur.contact }}
                    </span>
                    <span v-if="facture.fournisseur.telephone">
                      <el-icon><Phone /></el-icon>
                      {{ facture.fournisseur.telephone }}
                    </span>
                    <span v-if="facture.fournisseur.email">
                      <el-icon><Message /></el-icon>
                      {{ facture.fournisseur.email }}
                    </span>
                  </div>
                </div>
              </el-descriptions-item>
              <el-descriptions-item label="Libellé facture" :span="2">
                {{ facture.libelle || '-' }}
              </el-descriptions-item>
              <el-descriptions-item v-if="facture.observations" label="Observations" :span="2">
                {{ facture.observations }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>

          <!-- Montants Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Money /></el-icon>
                <span>Montants</span>
              </div>
            </template>

            <el-descriptions :column="2" border>
              <el-descriptions-item label="Montant Facture HT">
                <strong>{{ formatMontant(facture.montant_facture) }}</strong>
              </el-descriptions-item>
              <el-descriptions-item label="Montant M.O.">
                {{ formatMontant(facture.montant_mo) }}
              </el-descriptions-item>
              <el-descriptions-item label="Avoir" v-if="facture.avoir && facture.avoir > 0">
                {{ formatMontant(facture.avoir) }}
              </el-descriptions-item>
              <el-descriptions-item label="AIB" v-if="facture.type_reduction">
                <el-tag size="small" type="success">{{ getTypeReductionLabel(facture.type_reduction) }}</el-tag>
                <span v-if="facture.taux && facture.taux > 0" style="margin-left: 8px;">
                  — Taux : <strong>{{ facture.taux }}%</strong>
                </span>
              </el-descriptions-item>
              <el-descriptions-item label="Montant (Taux × M.O.)" v-if="facture.taux && facture.taux > 0">
                {{ formatMontant(montantTaux) }}
              </el-descriptions-item>
            </el-descriptions>
          </el-card>
        </el-col>

        <!-- Right Column: Totals & Payments -->
        <el-col :span="8">
          <!-- Totals Card -->
          <el-card shadow="never" class="section-card totals-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Operation /></el-icon>
                <span>Récapitulatif</span>
              </div>
            </template>

            <div class="totals-grid">
              <div class="total-row">
                <span class="total-label">Total HT :</span>
                <span class="total-value">{{ formatMontant(facture.montant_ht) }}</span>
              </div>
              <div class="total-row">
                <span class="total-label">TVA (18%) :</span>
                <span class="total-value total-tva">{{ formatMontant(facture.montant_tva) }}</span>
              </div>
              <div class="total-row" v-if="facture.montant_aib > 0">
                <span class="total-label">AIB :</span>
                <span class="total-value total-aib">{{ formatMontant(facture.montant_aib) }}</span>
              </div>
              <el-divider style="margin: 8px 0" />
              <div class="total-row total-ttc-row">
                <span class="total-label"><strong>Total TTC :</strong></span>
                <span class="total-value total-ttc"><strong>{{ formatMontant(facture.montant_ttc) }}</strong></span>
              </div>
              <div class="total-row" v-if="facture.avoir > 0">
                <span class="total-label">Avoir :</span>
                <span class="total-value" style="color: #f56c6c;">- {{ formatMontant(facture.avoir) }}</span>
              </div>
              <div class="total-row" v-if="facture.montant_reduction > 0 || facture.montant_aib > 0">
                <span class="total-label">{{ facture.type_reduction_libelle || 'AIB' }} ({{ facture.taux || 0 }}%) :</span>
                <span class="total-value" style="color: #f56c6c;">- {{ formatMontant(facture.montant_reduction || facture.montant_aib) }}</span>
              </div>
              <div class="total-row" v-if="montantNetAPayer !== facture.montant_ttc">
                <span class="total-label"><strong>Net à payer :</strong></span>
                <span class="total-value" style="color: #059669;"><strong>{{ formatMontant(montantNetAPayer) }}</strong></span>
              </div>

              <el-divider style="margin: 12px 0" />

              <div class="total-row">
                <span class="total-label">Montant payé :</span>
                <span class="total-value total-paye">{{ formatMontant(facture.montant_paye) }}</span>
              </div>

              <div class="total-row reste-row">
                <span class="total-label"><strong>Reste à payer :</strong></span>
                <span class="total-value total-reste" :class="{ 'soldee': estSoldee }">
                  <strong>{{ estSoldee ? 'Soldée' : formatMontant(resteAPayer) }}</strong>
                </span>
              </div>

              <el-progress
                :percentage="pourcentagePaye"
                :color="progressColor"
                :stroke-width="12"
                style="margin-top: 16px"
              />

              <div v-if="!estSoldee" style="display: flex; gap: 8px; margin-top: 16px;">
                <el-button
                  type="primary"
                  size="large"
                  style="flex: 1;"
                  @click="handleAction('pay')"
                >
                  <el-icon><Money /></el-icon>
                  Enregistrer un règlement
                </el-button>

                <el-button
                  type="success"
                  size="large"
                  plain
                  style="flex: 1;"
                  @click="handleAction('marquer_soldee')"
                >
                  <el-icon><CircleCheck /></el-icon>
                  Marquer comme soldée
                </el-button>
              </div>

              <el-alert
                v-if="estSoldee"
                type="success"
                :closable="false"
                style="margin-top: 16px;"
              >
                <template #title>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <el-icon><CircleCheck /></el-icon>
                    Facture soldée
                  </div>
                </template>
              </el-alert>
            </div>
          </el-card>

          <!-- Payment History Card -->
          <el-card shadow="never" class="section-card">
            <template #header>
              <div class="card-header-custom">
                <el-icon :size="20"><Clock /></el-icon>
                <span>Historique Règlements ({{ reglements.length }})</span>
              </div>
            </template>

            <el-timeline v-if="reglements.length > 0">
              <el-timeline-item
                v-for="reglement in reglements"
                :key="reglement.id"
                :timestamp="formatDate(reglement.date_reglement)"
                placement="top"
                color="#67c23a"
              >
                <el-card class="reglement-item">
                  <div class="reglement-header">
                    <el-tag :type="getModeTagType(reglement.mode_paiement)" size="small">
                      {{ getModeLabel(reglement.mode_paiement) }}
                    </el-tag>
                    <strong class="reglement-montant">{{ formatMontant(reglement.montant) }}</strong>
                  </div>
                  <div class="reglement-details">
                    <div v-if="reglement.reference">
                      <el-icon><DocumentCopy /></el-icon>
                      {{ reglement.reference }}
                    </div>
                    <div v-if="reglement.compte_bancaire">
                      <el-icon><CreditCard /></el-icon>
                      {{ reglement.compte_bancaire.banque }}
                    </div>
                    <div v-if="reglement.observations" class="reglement-remarques">
                      {{ reglement.observations }}
                    </div>
                  </div>
                  <el-divider style="margin: 12px 0" />
                  <div class="reglement-actions">
                    <el-button size="small" type="primary" :icon="DocumentCopy" @click="openMandatDrawer(reglement.id)">
                      Bordereau
                    </el-button>
                  </div>
                </el-card>
              </el-timeline-item>
            </el-timeline>

            <el-empty v-else description="Aucun règlement" :image-size="60">
              <el-button type="primary" size="small" @click="handleAction('pay')">
                Ajouter un règlement
              </el-button>
            </el-empty>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Drawer Imputation Comptable -->
    <el-drawer v-model="showImputationDrawer" title="Imputation Comptable" direction="rtl" size="55%" :destroy-on-close="true">
      <div v-if="imputationLoading" style="text-align: center; padding: 40px;">
        <el-icon class="is-loading" :size="30"><Clock /></el-icon>
        <p style="margin-top: 10px; color: #909399;">Chargement...</p>
      </div>
      <div v-else-if="imputationData" class="imputation-content">
        <div class="imputation-header">
          <div class="imputation-hospital-name">{{ imputationData.etablissement.nom }}</div>
          <div class="imputation-hospital-info">
            {{ imputationData.etablissement.pays }}<br>
            {{ imputationData.etablissement.adresse }}{{ imputationData.etablissement.telephone ? ' - Tel: ' + imputationData.etablissement.telephone : '' }}
          </div>
          <div class="imputation-title-box"><span>IMPUTATION COMPTABLE</span></div>
          <p class="imputation-numero-piece"><strong><u>Numero piece :</u></strong> {{ imputationData.facture.numero_piece }}</p>
        </div>
        <table class="imputation-table">
          <thead>
            <tr>
              <th style="width: 100px;">Date</th>
              <th style="width: 110px;">Compte</th>
              <th style="width: 140px; text-align: right;">Débit</th>
              <th style="width: 140px; text-align: right;">Crédit</th>
              <th>Libellé</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(group, gIndex) in imputationData.ecritures" :key="gIndex">
              <tr
                v-for="(ligne, lIndex) in group.lignes"
                :key="`${gIndex}-${lIndex}`"
                :class="lIndex === 0 && gIndex > 0 ? 'block-separator' : ''"
              >
                <td style="font-weight: 600;">
                  <template v-if="lIndex === 0">
                    {{ group.label || ligne.date }}
                    <div v-if="group.label" style="font-size: 11px; font-weight: normal;">{{ ligne.date }}</div>
                  </template>
                  <template v-else-if="ligne.date !== group.lignes[lIndex - 1].date">
                    <span style="font-weight: 500;">{{ ligne.date }}</span>
                  </template>
                </td>
                <td class="cell-compte-num">{{ ligne.numero_compte }}</td>
                <td class="cell-montant">{{ ligne.debit > 0 ? formatMontant(ligne.debit) : '' }}</td>
                <td class="cell-montant">{{ ligne.credit > 0 ? formatMontant(ligne.credit) : '' }}</td>
                <td class="cell-libelle">{{ (lIndex === 0 || ligne.libelle !== group.lignes[lIndex - 1].libelle) ? (ligne.libelle || '') : '' }}</td>
              </tr>
            </template>
          </tbody>
        </table>
        <div style="text-align: right; margin-top: 20px;">
          <el-button type="primary" :icon="Printer" @click="downloadImputationPdf">Télécharger PDF</el-button>
        </div>
      </div>
      <div v-else style="text-align: center; padding: 40px; color: #909399;">Aucune écriture comptable trouvée.</div>
    </el-drawer>

    <!-- Drawer État de Règlement Facture -->
    <el-drawer v-model="showEtatReglementDrawer" title="État de Règlement Facture" direction="rtl" size="55%" :destroy-on-close="true">
      <div v-if="etatReglementLoading" style="text-align: center; padding: 40px;">
        <el-icon class="is-loading" :size="30"><Clock /></el-icon>
        <p style="margin-top: 10px; color: #909399;">Chargement...</p>
      </div>
      <div v-else-if="etatReglementData" class="etat-reglement-content">
        <div class="etat-reglement-header">
          <div class="imputation-hospital-name">{{ etatReglementData.etablissement.nom }}</div>
          <div class="imputation-hospital-info">
            {{ etatReglementData.etablissement.adresse }}<br>
            {{ etatReglementData.etablissement.telephone ? 'Tél.: ' + etatReglementData.etablissement.telephone : '' }}
            {{ etatReglementData.etablissement.email ? ' - E-mail: ' + etatReglementData.etablissement.email : '' }}
          </div>
          <div class="imputation-title-box"><span>ÉTAT DE RÈGLEMENT FACTURE</span></div>
        </div>

        <div class="etat-reglement-fournisseur">
          <strong>Fournisseur :</strong> [{{ etatReglementData.fournisseur.code }}] {{ etatReglementData.fournisseur.nom }}
        </div>

        <div class="etat-reglement-info etat-reglement-info-framed">
          <span><strong>N° PC :</strong> {{ etatReglementData.facture.numero_piece }}</span>
          <span><strong>Date PC :</strong> {{ etatReglementData.facture.date }}</span>
          <span><strong>Réf facture :</strong> {{ etatReglementData.facture.reference_facture }}</span>
        </div>

        <div class="etat-reglement-objet">
          <strong>Objet :</strong> {{ etatReglementData.facture.libelle }}
        </div>

        <div class="etat-reglement-montants">
          <span><strong>Mt HT :</strong> {{ etatReglementData.facture.montant_facture }}</span>
          <span><strong>TVA{{ etatReglementData.facture.assujetti_tva && etatReglementData.facture.taux_tva > 0 ? ` (${etatReglementData.facture.taux_tva}%)` : '' }} :</strong> {{ etatReglementData.facture.montant_tva || '0' }}</span>
          <span><strong>TTC :</strong> {{ etatReglementData.facture.montant_ttc }}</span>
        </div>
        <div class="etat-reglement-montants">
          <span><strong>Mt M.O. :</strong> {{ etatReglementData.facture.montant_mo }}</span>
          <span><strong>AIB{{ etatReglementData.facture.taux > 0 ? `(${etatReglementData.facture.taux}%)` : '' }} :</strong> {{ etatReglementData.facture.montant_aib || '0' }}</span>
          <span><strong>Avoir :</strong> {{ etatReglementData.facture.avoir }}</span>
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
            <tr v-for="(reg, index) in etatReglementData.reglements" :key="index">
              <td>{{ reg.numero_ordre }}</td>
              <td>{{ reg.date_reglement }}</td>
              <td>{{ reg.mode_paiement }}</td>
              <td>{{ reg.beneficiaire }}</td>
              <td style="text-align: right;">{{ reg.montant }}</td>
            </tr>
            <tr v-if="!etatReglementData.reglements.length">
              <td colspan="5" style="text-align: center; font-style: italic;">Aucun règlement</td>
            </tr>
          </tbody>
        </table>

        <div class="etat-reglement-totaux">
          <div class="etat-reglement-total-row">
            <span class="etat-reglement-total-label">Total règlement :</span>
            <span class="etat-reglement-total-value">{{ etatReglementData.total_reglements }}</span>
          </div>
          <div class="etat-reglement-total-row">
            <span class="etat-reglement-total-label">Montant Dû (Net à payer) :</span>
            <span class="etat-reglement-total-value">{{ etatReglementData.montant_du }}</span>
          </div>
          <div class="etat-reglement-total-row">
            <span class="etat-reglement-total-label">Solde :</span>
            <span class="etat-reglement-total-value">{{ etatReglementData.solde }}</span>
          </div>
        </div>

        <div style="text-align: right; margin-top: 20px;">
          <el-button type="primary" :icon="Printer" @click="downloadEtatReglementPdf">Télécharger PDF</el-button>
        </div>
      </div>
      <div v-else style="text-align: center; padding: 40px; color: #909399;">Aucune donnée trouvée.</div>
    </el-drawer>

    <!-- Drawer Bordereau de Règlement -->
    <el-drawer v-model="showMandatDrawer" title="Bordereau de Règlement" direction="rtl" size="55%" :destroy-on-close="true">
      <div v-if="mandatLoading" style="text-align: center; padding: 40px;">
        <el-icon class="is-loading" :size="30"><Clock /></el-icon>
        <p style="margin-top: 10px; color: #909399;">Chargement...</p>
      </div>
      <div v-else-if="mandatData" class="mandat-content">
        <!-- En-tête centré comme imputation -->
        <div class="mandat-header">
          <div class="imputation-hospital-name">{{ mandatData.etablissement.nom }}</div>
          <div class="imputation-hospital-info">
            {{ mandatData.etablissement.adresse }}<br>
            {{ mandatData.etablissement.telephone ? 'Tél.: ' + mandatData.etablissement.telephone : '' }}
            {{ mandatData.etablissement.email ? ' - E-mail: ' + mandatData.etablissement.email : '' }}
          </div>
          <div class="imputation-title-box"><span>BORDEREAU DE RÈGLEMENT</span></div>
          <p class="imputation-numero-piece"><em>N° {{ mandatData.facture.numero_piece }}/DAF/H.M.</em></p>
        </div>

        <div class="mandat-exercice"><strong>EXERCICE {{ new Date().getFullYear() }}</strong></div>

        <p class="mandat-intro">
          <em>En vertu des crédits ouverts au titre du compte désigné ci-contre, le Directeur de l'hôpital
          ordonne le règlement par la caisse du Centre, de la créance détaillée ci-après :</em>
        </p>

        <!-- Détails -->
        <table class="mandat-details-table">
          <tr>
            <td class="mandat-label">OBJET :</td>
            <td>{{ mandatData.facture.libelle }}</td>
          </tr>
          <tr><td colspan="2" style="height: 10px;"></td></tr>
          <tr>
            <td class="mandat-label">PRESTATAIRE :</td>
            <td>{{ mandatData.fournisseur.nom }}</td>
          </tr>
          <tr><td colspan="2" style="height: 10px;"></td></tr>
          <tr>
            <td class="mandat-label">MONTANT FACTURE HT :</td>
            <td>{{ mandatData.facture.montant_facture }}</td>
          </tr>
          <tr v-if="mandatData.facture.montant_tva > 0">
            <td class="mandat-label">MONTANT TVA ({{ mandatData.facture.taux_tva }}%) :</td>
            <td>{{ mandatData.facture.montant_tva }}</td>
          </tr>
          <tr>
            <td class="mandat-label">MONTANT TTC :</td>
            <td>{{ mandatData.facture.montant_ttc }}</td>
          </tr>
          <tr>
            <td class="mandat-label">MONTANT AVOIR :</td>
            <td>{{ mandatData.facture.montant_avoir }}</td>
          </tr>
          <tr v-if="mandatData.facture.taux_aib > 0">
            <td class="mandat-label">IMPÔT / AIB {{ mandatData.facture.taux_aib }}% :</td>
            <td>{{ mandatData.facture.montant_aib }}</td>
          </tr>
          <tr><td colspan="2" style="height: 10px;"></td></tr>
          <tr>
            <td class="mandat-label">MONTANT PAYE :</td>
            <td>
              {{ mandatData.facture.montant_paye }}
              <span class="mandat-lettres">{{ mandatData.facture.montant_paye_lettres }}</span>
            </td>
          </tr>
          <tr>
            <td class="mandat-label">RESTE A PAYER :</td>
            <td>
              {{ mandatData.facture.reste_a_payer }}
              <span class="mandat-lettres">{{ mandatData.facture.reste_a_payer_lettres }}</span>
            </td>
          </tr>
          <tr><td colspan="2" style="height: 10px;"></td></tr>
          <tr v-if="mandatData.facture.reference_facture">
            <td class="mandat-label">PIECES JUSTIFICATIVES :</td>
            <td>{{ mandatData.facture.reference_facture }}</td>
          </tr>
          <tr><td colspan="2" style="height: 10px;"></td></tr>
          <tr>
            <td class="mandat-label">MODE PAIEMENT :</td>
            <td>
              {{ mandatData.reglement.mode_paiement }}
              <span v-if="mandatData.reglement.banque" style="margin-left: 30px;">{{ mandatData.reglement.banque }}</span>
              <br v-if="mandatData.reglement.reference">
              <span v-if="mandatData.reglement.reference">N°{{ mandatData.reglement.reference }}</span>
              <span v-if="mandatData.reglement.date_reglement" style="margin-left: 30px;">du {{ mandatData.reglement.date_reglement }}</span>
            </td>
          </tr>
        </table>

        <!-- Signatures -->
        <p class="mandat-fait"><em><strong>Fait à Cotonou, le {{ mandatData.reglement.date_reglement }}</strong></em></p>

        <table class="mandat-signatures">
          <tr>
            <td>
              <strong>Le Bénéficiaire,</strong>
              <div class="mandat-signature-name">{{ (mandatData.reglement.beneficiaire || mandatData.fournisseur.nom || '').toUpperCase() }}</div>
            </td>
            <td style="text-align: right;">
              <strong>Le Directeur,</strong>
              <div class="mandat-signature-name">{{ mandatData.etablissement.directeur || '' }}</div>
            </td>
          </tr>
        </table>

        <!-- Télécharger -->
        <div style="text-align: right; margin-top: 30px;">
          <el-button type="primary" :icon="Printer" @click="downloadMandatPdf">Télécharger PDF</el-button>
        </div>
      </div>
      <div v-else style="text-align: center; padding: 40px; color: #909399;">Aucune donnée trouvée.</div>
    </el-drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  ArrowLeft,
  ArrowDown,
  Document,
  Operation,
  Clock,
  Money,
  Edit,
  Delete,
  Printer,
  User,
  Phone,
  Message,
  Notebook,
  DocumentCopy,
  CreditCard,
  CircleCheck
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FactureFournisseurModal from '@/Components/Modals/FactureFournisseurModal.vue';
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
  fournisseurs: {
    type: Array,
    default: () => []
  },
  imputations: {
    type: Array,
    default: () => []
  },
  comptes: {
    type: Array,
    default: () => []
  },
  comptesAib: {
    type: Array,
    default: () => []
  },
  user: {
    type: Object,
    default: () => null
  }
});

// State for modal
const showFactureModal = ref(false);
const selectedFacture = ref(null);

// Drawer Imputation Comptable
const showImputationDrawer = ref(false);
const imputationLoading = ref(false);
const imputationData = ref(null);

// Drawer Bordereau de Règlement
const showMandatDrawer = ref(false);
const mandatLoading = ref(false);
const mandatData = ref(null);
const mandatReglementId = ref(null);

// Drawer État de Règlement
const showEtatReglementDrawer = ref(false);
const etatReglementLoading = ref(false);
const etatReglementData = ref(null);

// Computed
const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Factures Fournisseurs', path: '/factures-fournisseurs' },
  { title: props.facture.numero, path: '' }
];

// Montant net à payer (TTC - réductions/AIB)
const montantNetAPayer = computed(() => {
  if (props.facture.montant_net !== undefined && props.facture.montant_net !== null) {
    return parseFloat(props.facture.montant_net) || 0;
  }
  return parseFloat(props.facture.montant_ttc) || 0;
});

const resteAPayer = computed(() => {
  // Utiliser reste_a_payer de la facture si disponible
  if (props.facture.reste_a_payer !== undefined && props.facture.reste_a_payer !== null) {
    return parseFloat(props.facture.reste_a_payer) || 0;
  }
  // Sinon calculer depuis montant_net
  const montantPaye = parseFloat(props.facture.montant_paye) || 0;
  return montantNetAPayer.value - montantPaye;
});

const pourcentagePaye = computed(() => {
  if (montantNetAPayer.value === 0) return 0;
  const montantPaye = parseFloat(props.facture.montant_paye) || 0;
  return Math.round((montantPaye / montantNetAPayer.value) * 100);
});

// Vérifier si la facture est complètement payée ou soldée
const estSoldee = computed(() => {
  return props.facture.statut === 'payee' ||
         props.facture.statut === 'soldee' ||
         props.facture.statut_paiement === 'payee' ||
         props.facture.statut_paiement === 'soldee';
});

const progressColor = computed(() => {
  if (pourcentagePaye.value === 100) return '#67c23a';
  if (pourcentagePaye.value >= 50) return '#e6a23c';
  return '#f56c6c';
});

// Methods
const formatMontant = (montant) => {
  return new Intl.NumberFormat('fr-FR', {
    maximumFractionDigits: 0,
    minimumFractionDigits: 0
  }).format(montant || 0);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR');
};

const getStatutType = (statut) => {
  const types = {
    impayee: 'danger',
    partielle: 'warning',
    payee: 'success',
    soldee: 'info'
  };
  return types[statut] || 'info';
};

const getStatutLabel = (statut) => {
  const labels = {
    impayee: 'Impayée',
    partielle: 'Partiellement Payée',
    payee: 'Payée',
    soldee: 'Soldée'
  };
  return labels[statut] || statut;
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
    carte: 'Carte',
    mobile_money: 'Mobile Money'
  };
  return labels[mode] || mode;
};

const getTypeReductionLabel = (type) => {
  const labels = {
    aib: 'AIB',
    '4473': '4473 - Contribution nationale',
    '447310': '447310 - Acompte sur prestations',
  };
  return labels[type] || type || 'AIB';
};

// Montant du taux appliqué
// Imputations séparées par nature
const imputationsDebits = computed(() =>
  (props.facture.imputations || []).filter(i => (i.nature || 'debit') === 'debit')
);
const imputationsCredits = computed(() =>
  (props.facture.imputations || []).filter(i => i.nature === 'credit')
);

// Ligne TVA déductible auto-générée (compte 445) — affichée dans le bloc Débits
const tvaAutoLigne = computed(() => {
  const assujetti = !!props.facture.assujetti_tva;
  const tauxTva = parseFloat(props.facture.taux_tva) || 0;
  const montantTva = parseFloat(props.facture.montant_tva) || 0;
  if (!assujetti || tauxTva <= 0 || montantTva <= 0) return null;
  // Chercher un compte 445 dans les comptes fournis ; sinon valeur par défaut
  const compte445 = (props.comptes || []).find(c => {
    const num = String(c.numero || c.code || '');
    return num.startsWith('445');
  });
  return {
    numero: compte445 ? (compte445.numero || compte445.code) : '445',
    libelle: compte445 ? compte445.libelle : `TVA déductible (${tauxTva}%)`,
    montant: montantTva,
  };
});

const montantTaux = computed(() => {
  const taux = parseFloat(props.facture.taux) || 0;
  const montantMO = parseFloat(props.facture.montant_mo) || 0;
  return (taux / 100) * montantMO;
});

const handleBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/factures-fournisseurs');
  }
};

const handleAction = (command) => {
  switch (command) {
    case 'pay':
      router.visit(`/factures-fournisseurs/${props.facture.id}/regler`);
      break;
    case 'marquer_soldee':
      ElMessageBox.confirm(
        `Êtes-vous sûr de vouloir marquer cette facture comme soldée ?

Cette action clôturera définitivement la facture, même si le montant payé (${formatMontant(props.facture.montant_paye)}) ne correspond pas exactement au montant net (${formatMontant(montantNetAPayer.value)}).`,
        'Marquer comme soldée',
        {
          confirmButtonText: 'Oui, solder la facture',
          cancelButtonText: 'Annuler',
          type: 'warning',
          dangerouslyUseHTMLString: false
        }
      ).then(async () => {
        try {
          const response = await fetchApi(`/api/factures-fournisseurs/${props.facture.id}/solder`, {
            method: 'POST'
          });

          const result = await response.json();

          if (result.success) {
            ElMessage.success(result.message || 'Facture marquée comme soldée avec succès');
            router.reload({ only: ['facture'] });
          } else {
            ElMessage.error(result.message || 'Erreur lors du marquage de la facture');
          }
        } catch (error) {
          console.error('Erreur:', error);
          ElMessage.error('Erreur de connexion au serveur');
        }
      }).catch(() => {
        // User cancelled
      });
      break;
    case 'edit':
      selectedFacture.value = props.facture;
      showFactureModal.value = true;
      break;
    case 'print':
      ElMessage.info('Impression en cours de développement...');
      break;
    case 'imputation':
      openImputationDrawer();
      break;
    case 'etat_reglement':
      openEtatReglementDrawer();
      break;
    case 'delete':
      ElMessageBox.confirm(
        'Êtes-vous sûr de vouloir supprimer cette facture ?',
        'Confirmation',
        {
          confirmButtonText: 'Supprimer',
          cancelButtonText: 'Annuler',
          type: 'warning'
        }
      ).then(() => {
        ElMessage.success('Facture supprimée avec succès');
        router.visit('/factures-fournisseurs');
      });
      break;
  }
};

const handleFactureSuccess = async (factureData) => {
  const url = `/api/factures-fournisseurs/${props.facture.id}`;

  try {
    const response = await fetchApi(url, {
      method: 'PUT',
      body: factureData
    });

    const result = await response.json();

    if (result.success) {
      ElMessage.success(result.message || 'Facture modifiée avec succès');
      showFactureModal.value = false;
      const hasImputation = !!result.has_imputation;
      router.reload({ only: ['facture', 'reglements'] });

      const confirmed = await ElMessageBox.confirm(
        'Autorisez-vous une imputation comptable à l\'enregistrement de cette pièce comptable ?',
        'Imputation comptable',
        {
          confirmButtonText: 'Oui',
          cancelButtonText: 'Non',
          type: 'info',
        }
      ).catch(() => false);

      if (confirmed) {
        try {
          const r = await fetchApi(`/api/factures-fournisseurs/${props.facture.id}/imputation`, { method: 'POST' });
          const j = await r.json();
          if (j.success) {
            ElMessage.success(j.message || 'Écritures générées');
            openImputationDrawer();
          } else if (j.reason === 'missing') {
            ElMessageBox.alert(j.message, 'Imputations manquantes', { type: 'warning', confirmButtonText: 'OK' });
          } else if (j.reason === 'incomplete') {
            ElMessageBox.alert(j.message, 'Imputations incomplètes', { type: 'warning', confirmButtonText: 'OK' });
          } else {
            ElMessage.error(j.message || 'Erreur');
          }
        } catch {
          ElMessage.error('Erreur de connexion');
        }
      }
    } else {
      ElMessage.error(result.message || 'Une erreur est survenue');
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    ElMessage.error('Erreur de connexion au serveur');
  }
};

const openMandatDrawer = async (reglementId) => {
  mandatReglementId.value = reglementId;
  mandatData.value = null;
  mandatLoading.value = true;
  showMandatDrawer.value = true;

  try {
    const response = await fetchApi(`/api/reglements-fournisseurs/${reglementId}/mandat-data`);
    const result = await response.json();
    if (result.success) {
      mandatData.value = result;
    } else {
      ElMessage.warning(result.message || 'Données non trouvées');
    }
  } catch (err) {
    ElMessage.error('Erreur lors du chargement du bordereau');
  } finally {
    mandatLoading.value = false;
  }
};

const downloadMandatPdf = () => {
  if (mandatReglementId.value) {
    window.open(`/reglements-fournisseurs/${mandatReglementId.value}/mandat`, '_blank');
  }
};

const openImputationDrawer = async () => {
  imputationData.value = null;
  imputationLoading.value = true;
  showImputationDrawer.value = true;

  try {
    const response = await fetchApi(`/api/factures-fournisseurs/${props.facture.id}/imputation-data`);
    const result = await response.json();
    if (result.success) {
      imputationData.value = result;
    } else {
      ElMessage.warning(result.message || 'Aucune écriture trouvée');
    }
  } catch (err) {
    ElMessage.error('Erreur lors du chargement des écritures');
  } finally {
    imputationLoading.value = false;
  }
};

const downloadImputationPdf = () => {
  window.open(`/factures-fournisseurs/${props.facture.id}/imputation-pdf`, '_blank');
};

const openEtatReglementDrawer = async () => {
  etatReglementData.value = null;
  etatReglementLoading.value = true;
  showEtatReglementDrawer.value = true;

  try {
    const response = await fetchApi(`/api/factures-fournisseurs/${props.facture.id}/etat-reglement-data`);
    const result = await response.json();
    if (result.success) {
      etatReglementData.value = result;
    } else {
      ElMessage.warning(result.message || 'Données non trouvées');
    }
  } catch (err) {
    ElMessage.error('Erreur lors du chargement de l\'état de règlement');
  } finally {
    etatReglementLoading.value = false;
  }
};

const downloadEtatReglementPdf = () => {
  window.open(`/factures-fournisseurs/${props.facture.id}/etat-reglement-pdf`, '_blank');
};

// Auto-ouverture du drawer Imputation Comptable après un règlement
// (la page Reglement.vue redirige avec ?show_imputation=1)
onMounted(() => {
  if (typeof window === 'undefined') return;
  const params = new URLSearchParams(window.location.search);
  if (params.get('show_imputation') === '1') {
    openImputationDrawer();
    // Nettoyer l'URL pour éviter une réouverture lors d'un reload
    params.delete('show_imputation');
    const newSearch = params.toString();
    const newUrl = window.location.pathname + (newSearch ? `?${newSearch}` : '');
    window.history.replaceState({}, '', newUrl);
  }
});
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
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
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

.header-actions {
  display: flex;
  gap: 12px;
}

.section-card {
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

.fournisseur-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.imputations-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

.imputation-row {
  display: grid;
  grid-template-columns: 110px 1fr auto;
  align-items: center;
  gap: 12px;
  padding: 4px 0;
  border-bottom: 1px dashed #e5e7eb;
}

.imputation-row:last-child {
  border-bottom: none;
}

.imputation-row-auto {
  background: #fef3c7;
  padding: 4px 6px;
  border-radius: 4px;
  border-bottom: 1px dashed #fbbf24 !important;
}

.imputation-row-auto .imputation-libelle {
  color: #92400e;
  font-style: italic;
}

.imputations-block-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 4px;
  padding-bottom: 4px;
  border-bottom: 2px solid;
}

.block-debit-title {
  color: #2563eb;
  border-color: #2563eb;
}

.block-credit-title {
  color: #16a34a;
  border-color: #16a34a;
}

.imputation-libelle {
  font-size: 13px;
  color: #4b5563;
}

.imputation-montant {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #1f2937;
}

.fournisseur-details {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #6b7280;
}

.fournisseur-details span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.ligne-description {
  font-size: 14px;
  color: #1f2937;
  margin-bottom: 4px;
}

.ligne-compte {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}

.text-muted {
  color: #d1d5db;
}

.totals-card {
  background-color: #f9fafb;
}

.totals-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}

.total-label {
  font-size: 14px;
  color: #6b7280;
}

.total-value {
  font-size: 15px;
  color: #1f2937;
  font-family: 'Courier New', monospace;
}

.total-tva {
  color: #2563eb;
}

.total-aib {
  color: #d97706;
}

.total-ttc-row,
.reste-row {
  padding: 8px 0;
}

.total-ttc {
  color: #059669;
  font-size: 20px;
}

.total-paye {
  color: #059669;
}

.total-reste {
  color: #dc2626;
  font-size: 18px;
}

.total-reste.soldee {
  color: #059669;
}

.reglement-item {
  box-shadow: none;
  border: 1px solid #e5e7eb;
}

.reglement-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.reglement-montant {
  font-size: 15px;
  color: #059669;
}

.reglement-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}

.reglement-details > div {
  display: flex;
  align-items: center;
  gap: 6px;
}

.reglement-remarques {
  font-style: italic;
  color: #9ca3af;
}

.reglement-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-start;
}

:deep(.el-card__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}

:deep(.el-card__body) {
  padding: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
}

:deep(.el-table) {
  font-size: 13px;
}

:deep(.el-table th) {
  background-color: #f3f4f6;
  font-weight: 600;
  color: #374151;
}

:deep(.el-timeline-item__timestamp) {
  font-weight: 600;
  color: #6b7280;
}

/* Imputation Comptable Drawer */
.imputation-content { padding: 0 15px; font-family: 'Times New Roman', serif; }
.imputation-header { margin-bottom: 20px; text-align: center; }
.imputation-hospital-name { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
.imputation-hospital-info { font-size: 12px; color: #444; line-height: 1.6; margin-top: 4px; }
.imputation-title-box { margin: 20px auto 15px; display: inline-block; border: 2px solid #000; padding: 8px 30px; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
.imputation-numero-piece { text-align: left; margin: 15px 0 0; font-size: 14px; }
.imputation-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.imputation-table th { border: 1px solid #000; padding: 8px 10px; font-weight: bold; text-align: center; text-transform: uppercase; font-size: 12px; background-color: #fff; }
.imputation-table td { border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #ccc; padding: 6px 10px; vertical-align: top; }
.imputation-table tbody tr:last-child td { border-bottom: 1px solid #000; }
.imputation-table tfoot td { border: 1px solid #000; background-color: #fff; font-weight: bold; }
.imputation-table .cell-compte-num { font-family: 'Courier New', monospace; font-weight: 600; text-align: left; white-space: nowrap; }
.imputation-table .cell-compte-lib { font-size: 12px; color: #333; }
.imputation-table .cell-montant { font-family: 'Courier New', monospace; text-align: right; white-space: nowrap; }
.imputation-table tr.block-separator td { border-top: 2px solid #000; }
.imputation-table tr:not(.block-separator) td { border-top: none; }
.imputation-table tbody td.cell-libelle { border-bottom: none; }
.imputation-table tbody tr:last-child td.cell-libelle { border-bottom: 1px solid #000; }
.imputation-table tbody tr.block-separator td.cell-libelle { border-top: 2px solid #000; }

/* Bordereau de Règlement Drawer */
.mandat-content { padding: 0 15px; font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.6; }
.mandat-header { margin-bottom: 15px; text-align: center; }
.mandat-exercice { margin: 10px 0 15px; font-size: 14px; }
.mandat-intro { margin: 0 0 20px; font-size: 13px; line-height: 1.6; }
.mandat-details-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mandat-details-table td { padding: 3px 0; vertical-align: top; }
.mandat-label { width: 240px; font-weight: bold; white-space: nowrap; }
.mandat-lettres { display: inline-block; margin-left: 10px; font-size: 11px; color: #c00; text-transform: uppercase; }
.mandat-fait { margin: 30px 0 20px; text-align: center; }
.mandat-signatures { width: 100%; margin-top: 10px; }
.mandat-signatures td { width: 50%; vertical-align: top; padding: 0 10px; }
.mandat-signature-name { margin-top: 50px; font-size: 13px; }

/* État de Règlement Drawer */
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
