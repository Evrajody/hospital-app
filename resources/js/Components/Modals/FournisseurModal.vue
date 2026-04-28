<template>
  <el-dialog
    v-model="dialogVisible"
    :title="isEdit ? 'Modifier le Fournisseur' : 'Nouveau Fournisseur'"
    width="900px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @closed="handleClosed"
    class="fournisseur-modal"
  >
    <!-- Affichage global des erreurs -->
    <el-alert
      v-if="validationErrors.length > 0"
      type="error"
      :closable="true"
      class="global-errors-alert"
      @close="clearErrors"
    >
      <template #title>
        <div class="errors-title">
          <el-icon><WarningFilled /></el-icon>
          Veuillez corriger les erreurs suivantes :
        </div>
      </template>
      <ul class="errors-list">
        <li v-for="(error, index) in validationErrors" :key="index" class="error-item">
          <span class="error-field">{{ error.field }}</span>: {{ error.message }}
        </li>
      </ul>
    </el-alert>

    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      size="large"
      @submit.prevent="handleSubmit"
    >
      <el-tabs v-model="activeTab" type="border-card">
        <!-- Onglet 1: Informations Générales -->
        <el-tab-pane name="general" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><OfficeBuilding /></el-icon>
              Informations Générales
              <span class="tab-required-dot" v-if="tabHasRequiredEmpty('general')">*</span>
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- Raison Sociale -->
              <el-col :span="24">
                <el-form-item label="Raison Sociale" prop="nom" required>
                  <template #label>
                    <span>Raison Sociale <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model="form.nom"
                    placeholder="Nom complet du fournisseur"
                    :prefix-icon="OfficeBuilding"
                  />
                </el-form-item>
              </el-col>

              <!-- Type de Fournisseur -->
              <el-col :span="12">
                <el-form-item label="Type de Fournisseur" prop="type_fournisseur">
                  <el-select
                    v-model="form.type_fournisseur"
                    placeholder="Sélectionner le type"
                    filterable
                    clearable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="option in typesFournisseurOptions"
                      :key="option.value"
                      :label="option.label"
                      :value="option.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>

            </el-row>
          </div>
        </el-tab-pane>

        <!-- Onglet 2: Coordonnées -->
        <el-tab-pane name="contact" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Phone /></el-icon>
              Coordonnées
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- Personne de Contact -->
              <el-col :span="12">
                <el-form-item label="Personne de Contact" prop="contact">
                  <el-input
                    v-model="form.contact"
                    placeholder="Nom de la personne à contacter"
                    :prefix-icon="User"
                  />
                </el-form-item>
              </el-col>

              <!-- Fonction du Contact -->
              <el-col :span="12">
                <el-form-item label="Fonction du Contact" prop="fonction_contact">
                  <el-input
                    v-model="form.fonction_contact"
                    placeholder="Ex: Directeur Commercial"
                  />
                </el-form-item>
              </el-col>

              <!-- Téléphone Principal -->
              <el-col :span="12">
                <el-form-item label="Téléphone Principal" prop="telephone">
                  <el-input
                    v-model="form.telephone"
                    placeholder="+229 XX XX XX XX"
                    :prefix-icon="Phone"
                  />
                </el-form-item>
              </el-col>

              <!-- Téléphone Secondaire -->
              <el-col :span="12">
                <el-form-item label="Téléphone Secondaire" prop="telephone_secondaire">
                  <el-input
                    v-model="form.telephone_secondaire"
                    placeholder="+229 XX XX XX XX"
                    :prefix-icon="Phone"
                  />
                </el-form-item>
              </el-col>

              <!-- Email -->
              <el-col :span="12">
                <el-form-item label="Email" prop="email">
                  <el-input
                    v-model="form.email"
                    type="email"
                    placeholder="email@example.com"
                    :prefix-icon="Message"
                  />
                </el-form-item>
              </el-col>

              <!-- Site Web -->
              <el-col :span="12">
                <el-form-item label="Site Web" prop="site_web">
                  <el-input
                    v-model="form.site_web"
                    placeholder="https://www.example.com"
                  />
                </el-form-item>
              </el-col>

              <!-- Adresse -->
              <el-col :span="24">
                <el-form-item label="Adresse" prop="adresse">
                  <el-input
                    v-model="form.adresse"
                    type="textarea"
                    :rows="2"
                    placeholder="Adresse complète du fournisseur"
                  />
                </el-form-item>
              </el-col>

              <!-- Ville -->
              <el-col :span="12">
                <el-form-item label="Ville" prop="ville">
                  <el-input
                    v-model="form.ville"
                    placeholder="Ex: Cotonou"
                    :prefix-icon="Location"
                  />
                </el-form-item>
              </el-col>

              <!-- Pays -->
              <el-col :span="12">
                <el-form-item label="Pays" prop="pays">
                  <el-select
                    v-model="form.pays"
                    placeholder="Sélectionner le pays"
                    filterable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="option in paysOptions"
                      :key="option.value"
                      :label="option.label"
                      :value="option.value"
                    >
                      <span>{{ option.flag }} {{ option.label }}</span>
                    </el-option>
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
          </div>
        </el-tab-pane>

        <!-- Onglet 3: Compte Comptable -->
        <el-tab-pane name="comptable" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Wallet /></el-icon>
              Compte Comptable
              <span class="tab-required-dot" v-if="tabHasRequiredEmpty('comptable')">*</span>
            </span>
          </template>

          <div class="tab-content">
            <el-alert
              type="info"
              :closable="false"
              class="mb-4"
            >
              <template #title>
                <div class="alert-title">Attribution du compte auxiliaire (Plan Comptable OHADA)</div>
              </template>
              <div class="alert-content">
                Chaque fournisseur doit avoir son propre compte auxiliaire dans les classes 401 (Fournisseurs) ou 4812 (Fournisseurs d'investissements).
                Les comptes disponibles sont chargés depuis le Plan Comptable OHADA.
                Vous pouvez soit sélectionner un compte existant, soit créer un nouveau compte.
              </div>
            </el-alert>

            <el-form-item>
              <el-radio-group v-model="compteMode" @change="handleCompteModeChange">
                <el-radio-button value="select">
                  <el-icon><Search /></el-icon>
                  Sélectionner un compte existant
                </el-radio-button>
                <el-radio-button value="create">
                  <el-icon><Plus /></el-icon>
                  Créer un nouveau compte
                </el-radio-button>
              </el-radio-group>
            </el-form-item>

            <!-- Mode: Select existing account -->
            <div v-if="compteMode === 'select'" class="compte-section">
              <el-form-item label="Compte Fournisseur (Plan Comptable OHADA)" prop="compte_comptable_id">
                <template #label>
                  <span>Compte Fournisseur <span class="required-star">*</span></span>
                </template>
                <el-select
                  v-model="form.compte_comptable_id"
                  filterable
                  placeholder="Rechercher un compte 401xxx ou 4812xxx..."
                  style="width: 100%"
                  @change="handleCompteChange"
                  :no-data-text="comptesFournisseurs.length === 0 ? 'Aucun compte fournisseur trouvé dans le plan comptable' : 'Aucun résultat'"
                >
                  <el-option
                    v-for="compte in comptesFournisseurs"
                    :key="compte.id"
                    :label="`${compte.numero} - ${compte.libelle}`"
                    :value="compte.id"
                  >
                    <div class="compte-option">
                      <el-tag size="small" type="info">{{ compte.numero }}</el-tag>
                      <span class="compte-libelle">{{ compte.libelle }}</span>
                    </div>
                  </el-option>
                </el-select>
                <div class="form-hint">
                  {{ comptesFournisseurs.length }} compte(s) disponible(s) dans les classes 401 et 4812
                </div>
              </el-form-item>

              <!-- Aperçu du compte sélectionné -->
              <el-card v-if="selectedCompte" class="compte-preview" shadow="never">
                <div class="preview-header">
                  <el-icon><InfoFilled /></el-icon>
                  Compte principal (depuis le Plan Comptable OHADA)
                </div>
                <el-descriptions :column="2" size="small" border>
                  <el-descriptions-item label="Numéro">
                    <el-tag>{{ selectedCompte.numero }}</el-tag>
                  </el-descriptions-item>
                  <el-descriptions-item label="Libellé">
                    {{ selectedCompte.libelle }}
                  </el-descriptions-item>
                </el-descriptions>
              </el-card>

            </div>

            <!-- Mode: Create new account -->
            <div v-else class="compte-section">
              <el-row :gutter="20">
                <el-col :span="24">
                  <el-form-item label="Compte Parent (Plan Comptable OHADA)" prop="compte_parent_id">
                    <el-select
                      v-model="form.compte_parent_id"
                      placeholder="Sélectionner un compte parent (401 ou 4812)"
                      style="width: 100%"
                      filterable
                      @change="handleParentCompteChange"
                      :no-data-text="comptesParents.length === 0 ? 'Aucun compte parent trouvé' : 'Aucun résultat'"
                    >
                      <el-option
                        v-for="compte in comptesParents"
                        :key="compte.id"
                        :label="`${compte.numero} - ${compte.libelle}`"
                        :value="compte.id"
                      >
                        <div class="compte-option">
                          <el-tag size="small" type="warning">{{ compte.numero }}</el-tag>
                          <span class="compte-libelle">{{ compte.libelle }}</span>
                        </div>
                      </el-option>
                    </el-select>
                    <div class="form-hint">Compte général sous lequel créer le compte auxiliaire</div>
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="Numéro de Compte" prop="nouveau_compte_numero">
                    <el-input
                      v-model="form.nouveau_compte_numero"
                      placeholder="401.001"
                      :prefix-icon="Document"
                    >
                      <template #append>
                        <el-button :icon="MagicStick" @click="suggestAccountNumber">
                          Auto
                        </el-button>
                      </template>
                    </el-input>
                    <div class="form-hint">Format: compte_parent.XXX (auto-incrémenté)</div>
                  </el-form-item>
                </el-col>

                <el-col :span="12">
                  <el-form-item label="Libellé du Compte" prop="nouveau_compte_libelle">
                    <el-input
                      v-model="form.nouveau_compte_libelle"
                      placeholder="Libellé automatique avec le nom"
                      :prefix-icon="Edit"
                    />
                    <div class="form-hint">Par défaut, le nom du fournisseur</div>
                  </el-form-item>
                </el-col>
              </el-row>

              <!-- Aperçu du compte à créer -->
              <el-alert
                v-if="form.nouveau_compte_numero"
                type="success"
                :closable="false"
                class="mt-4"
              >
                <template #title>
                  <div class="alert-title">
                    <el-icon><SuccessFilled /></el-icon>
                    Compte à créer
                  </div>
                </template>
                <div class="nouveau-compte-preview">
                  <el-tag type="success" size="large">{{ form.nouveau_compte_numero }}</el-tag>
                  <span class="compte-preview-libelle">
                    {{ form.nouveau_compte_libelle || form.nom || 'Nouveau fournisseur' }}
                  </span>
                </div>
              </el-alert>
            </div>
          </div>
        </el-tab-pane>

        <!-- Onglet 4: Informations Fiscales -->
        <el-tab-pane name="fiscal" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><Document /></el-icon>
              Informations Fiscales
              <span class="tab-required-dot" v-if="tabHasRequiredEmpty('fiscal')">*</span>
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- IFU -->
              <el-col :span="12">
                <el-form-item prop="ifu">
                  <template #label>
                    <span>IFU (Identifiant Fiscal Unique) <span class="required-star">*</span></span>
                  </template>
                  <el-input
                    v-model="form.ifu"
                    placeholder="0000000000000"
                    :prefix-icon="CreditCard"
                    maxlength="13"
                    show-word-limit
                  />
                  <div class="form-hint">13 chiffres - Obligatoire</div>
                </el-form-item>
              </el-col>

              <!-- RCCM -->
              <el-col :span="12">
                <el-form-item label="RCCM" prop="rccm">
                  <el-input
                    v-model="form.rccm"
                    placeholder="RB/COT/XX-X-XXXXX"
                  />
                  <div class="form-hint">Registre du Commerce et du Crédit Mobilier</div>
                </el-form-item>
              </el-col>
            </el-row>
          </div>
        </el-tab-pane>

        <!-- Onglet 5: Informations Complémentaires -->
        <el-tab-pane name="extra" lazy>
          <template #label>
            <span class="tab-label">
              <el-icon><More /></el-icon>
              Complémentaires
            </span>
          </template>

          <div class="tab-content">
            <el-row :gutter="20">
              <!-- Observations -->
              <el-col :span="24">
                <el-form-item label="Observations / Notes" prop="observations">
                  <el-input
                    v-model="form.observations"
                    type="textarea"
                    :rows="4"
                    placeholder="Notes ou informations complémentaires..."
                  />
                </el-form-item>
              </el-col>
            </el-row>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-form>

    <!-- Navigation entre onglets -->
    <div class="tab-navigation">
      <el-button
        v-if="activeTab !== 'general'"
        @click="previousTab"
      >
        <el-icon><ArrowLeft /></el-icon>
        Précédent
      </el-button>
      <el-button
        v-if="activeTab !== 'extra'"
        type="primary"
        @click="nextTab"
      >
        Suivant
        <el-icon><ArrowRight /></el-icon>
      </el-button>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleCancel" :disabled="props.loading">Annuler</el-button>
        <el-button
          type="primary"
          @click="handleSubmit"
          :loading="props.loading"
        >
          <el-icon v-if="!props.loading"><Check /></el-icon>
          {{ isEdit ? 'Mettre à jour' : 'Enregistrer' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import {
  OfficeBuilding,
  User,
  Phone,
  Message,
  Document,
  MagicStick,
  Edit,
  CreditCard,
  Check,
  Wallet,
  Plus,
  Search,
  InfoFilled,
  SuccessFilled,
  WarningFilled,
  More,
  ArrowLeft,
  ArrowRight,
  Location
} from '@element-plus/icons-vue';

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  fournisseur: {
    type: Object,
    default: () => null
  },
  comptesFournisseurs: {
    type: Array,
    default: () => []
  },
  comptesParents: {
    type: Array,
    default: () => []
  },
  additionalFields: {
    type: Array,
    default: () => []
  },
  serverErrors: {
    type: Object,
    default: () => null
  },
  loading: {
    type: Boolean,
    default: false
  }
});

// Emits
const emit = defineEmits(['update:modelValue', 'success', 'delete']);

// State
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const isEdit = computed(() => !!props.fournisseur);
const formRef = ref(null);
const activeTab = ref('general');
const compteMode = ref('select');
const validationErrors = ref([]);

// Map des noms de champs vers des libellés lisibles
const fieldLabels = {
  nom: 'Raison Sociale',
  type_fournisseur: 'Type de Fournisseur',
  contact: 'Personne de Contact',
  fonction_contact: 'Fonction du Contact',
  telephone: 'Téléphone Principal',
  telephone_secondaire: 'Téléphone Secondaire',
  email: 'Email',
  site_web: 'Site Web',
  adresse: 'Adresse',
  ville: 'Ville',
  pays: 'Pays',
  compte_comptable_id: 'Compte Fournisseur',
  compte_parent_id: 'Compte Parent',
  nouveau_compte_numero: 'Numéro de Compte',
  nouveau_compte_libelle: 'Libellé du Compte',
  ifu: 'IFU',
  rccm: 'RCCM',
  observations: 'Observations'
};

const clearErrors = () => {
  validationErrors.value = [];
};

// Watch pour les erreurs du serveur
watch(() => props.serverErrors, (errors) => {
  if (errors && typeof errors === 'object') {
    const errorList = [];
    for (const [fieldName, messages] of Object.entries(errors)) {
      const fieldMessages = Array.isArray(messages) ? messages : [messages];
      for (const message of fieldMessages) {
        errorList.push({
          field: fieldLabels[fieldName] || fieldName,
          message: message
        });
      }
    }
    validationErrors.value = errorList;

    // Scroller vers le haut pour voir les erreurs
    setTimeout(() => {
      const dialogBody = document.querySelector('.fournisseur-modal .el-dialog__body');
      if (dialogBody) {
        dialogBody.scrollTop = 0;
      }
    }, 100);
  }
}, { immediate: true, deep: true });

// Configuration des onglets
const tabOrder = ['general', 'contact', 'comptable', 'fiscal', 'extra'];

// ==========================================
// OPTIONS POUR LES SELECTS
// ==========================================

const typesFournisseurOptions = [
  { value: 'medicaments', label: 'Médicaments & Produits pharmaceutiques' },
  { value: 'equipements', label: 'Équipements médicaux' },
  { value: 'consommables', label: 'Consommables & Fournitures' },
  { value: 'services', label: 'Services (Eau, Électricité, etc.)' },
  { value: 'maintenance', label: 'Maintenance & Réparations' },
  { value: 'autres', label: 'Autres' }
];

const paysOptions = [
  { value: 'BJ', label: 'Bénin', flag: '🇧🇯' },
  { value: 'TG', label: 'Togo', flag: '🇹🇬' },
  { value: 'NE', label: 'Niger', flag: '🇳🇪' },
  { value: 'BF', label: 'Burkina Faso', flag: '🇧🇫' },
  { value: 'CI', label: 'Côte d\'Ivoire', flag: '🇨🇮' },
  { value: 'GH', label: 'Ghana', flag: '🇬🇭' },
  { value: 'NG', label: 'Nigeria', flag: '🇳🇬' },
  { value: 'SN', label: 'Sénégal', flag: '🇸🇳' },
  { value: 'ML', label: 'Mali', flag: '🇲🇱' },
  { value: 'CM', label: 'Cameroun', flag: '🇨🇲' },
  { value: 'GA', label: 'Gabon', flag: '🇬🇦' },
  { value: 'FR', label: 'France', flag: '🇫🇷' },
  { value: 'CN', label: 'Chine', flag: '🇨🇳' },
  { value: 'IN', label: 'Inde', flag: '🇮🇳' },
  { value: 'OTHER', label: 'Autre', flag: '🌍' }
];

// ==========================================
// FORM DATA
// ==========================================

const getInitialFormData = () => ({
  // Informations générales
  nom: '',
  type_fournisseur: '',
  // Coordonnées
  contact: '',
  fonction_contact: '',
  telephone: '',
  telephone_secondaire: '',
  email: '',
  site_web: '',
  adresse: '',
  ville: '',
  pays: 'BJ',
  // Compte comptable
  compte_comptable_id: null,
  compte_parent_id: null,
  nouveau_compte_numero: '',
  nouveau_compte_libelle: '',
  // Fiscal
  ifu: '',
  rccm: '',
  // Extra
  observations: ''
});

const form = reactive(getInitialFormData());

// Watch pour pré-remplir en mode édition
watch(() => props.fournisseur, (newFournisseur) => {
  if (newFournisseur) {
    // Réinitialiser d'abord avec les valeurs par défaut
    const initialData = getInitialFormData();
    Object.keys(initialData).forEach(key => {
      form[key] = initialData[key];
    });

    // Puis charger les données du fournisseur
    Object.keys(form).forEach(key => {
      if (key in newFournisseur && newFournisseur[key] !== null) {
        form[key] = newFournisseur[key];
      }
    });

    // Si le fournisseur a un compte comptable, pré-sélectionner le mode et le compte
    if (newFournisseur.compte_comptable_id) {
      compteMode.value = 'select';
      form.compte_comptable_id = newFournisseur.compte_comptable_id;
    } else if (newFournisseur.compte_comptable?.id) {
      // Si compte_comptable_id n'est pas directement disponible mais l'objet compte_comptable l'est
      compteMode.value = 'select';
      form.compte_comptable_id = newFournisseur.compte_comptable.id;
    }
  }
}, { immediate: true, deep: true });

// Watch pour le dialog visible - recharger les données quand le modal s'ouvre
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.fournisseur) {
    // Recharger les données du fournisseur
    Object.keys(form).forEach(key => {
      if (key in props.fournisseur && props.fournisseur[key] !== null) {
        form[key] = props.fournisseur[key];
      }
    });

    // Gérer le compte comptable
    if (props.fournisseur.compte_comptable_id) {
      compteMode.value = 'select';
      form.compte_comptable_id = props.fournisseur.compte_comptable_id;
    } else if (props.fournisseur.compte_comptable?.id) {
      compteMode.value = 'select';
      form.compte_comptable_id = props.fournisseur.compte_comptable.id;
    }

    // Réinitialiser les erreurs
    validationErrors.value = [];
    activeTab.value = 'general';
  }
});

// Watch nom to auto-fill compte libelle
watch(() => form.nom, (newNom) => {
  if (compteMode.value === 'create' && !form.nouveau_compte_libelle) {
    form.nouveau_compte_libelle = newNom;
  }
});

// Computed pour vérifier si un onglet a des champs requis vides
const tabHasRequiredEmpty = (tabName) => {
  if (tabName === 'general') {
    return !form.nom || form.nom.trim() === '';
  }
  if (tabName === 'comptable') {
    if (compteMode.value === 'select') {
      return !form.compte_comptable_id;
    } else {
      return !form.nouveau_compte_numero || form.nouveau_compte_numero.trim() === '';
    }
  }
  if (tabName === 'fiscal') {
    return !form.ifu || form.ifu.trim() === '';
  }
  return false;
};

// Computed pour le compte sélectionné
const selectedCompte = computed(() => {
  if (compteMode.value === 'select' && form.compte_comptable_id) {
    return props.comptesFournisseurs.find(c => c.id === form.compte_comptable_id);
  }
  return null;
});

// ==========================================
// VALIDATION RULES
// ==========================================

const rules = computed(() => ({
  nom: [
    { required: true, message: 'La raison sociale est obligatoire', trigger: 'blur' },
    { min: 2, max: 255, message: 'Le nom doit contenir entre 2 et 255 caractères', trigger: 'blur' }
  ],
  email: [
    { type: 'email', message: 'Format d\'email invalide', trigger: 'blur' }
  ],
  telephone: [
    { pattern: /^[+]?[\d\s\-()]+$/, message: 'Format de téléphone invalide', trigger: 'blur' }
  ],
  compte_comptable_id: [
    {
      validator: (rule, value, callback) => {
        if (compteMode.value === 'select' && !value) {
          callback(new Error('Veuillez sélectionner un compte comptable'));
        } else {
          callback();
        }
      },
      trigger: 'change'
    }
  ],
  nouveau_compte_numero: [
    {
      validator: (rule, value, callback) => {
        if (compteMode.value === 'create' && !value) {
          callback(new Error('Le numéro de compte est obligatoire'));
        } else if (compteMode.value === 'create' && !/^(401|4812)[a-zA-Z0-9.]+$/.test(value)) {
          callback(new Error('Le numéro doit commencer par 401 ou 4812 (ex: 401.001, 4812.001)'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ],
  ifu: [
    { required: true, message: "L'IFU est obligatoire", trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value && !/^\d{13}$/.test(value)) {
          callback(new Error('L\'IFU doit contenir exactement 13 chiffres'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ]
}));

// ==========================================
// METHODS
// ==========================================

const handleCompteModeChange = () => {
  form.compte_comptable_id = null;
  form.compte_parent_id = null;
  form.nouveau_compte_numero = '';
  form.nouveau_compte_libelle = '';
};

const handleCompteChange = (compteId) => {
  const compte = props.comptesFournisseurs.find(c => c.id === compteId);
  if (compte) {
    console.log('Compte sélectionné:', compte);
  }
};

const handleParentCompteChange = () => {
  suggestAccountNumber();
};

const suggestAccountNumber = () => {
  const parentCompte = props.comptesParents.find(c => c.id === form.compte_parent_id);
  if (!parentCompte) {
    ElMessage.warning('Veuillez d\'abord sélectionner un compte parent');
    return;
  }

  const parentNumero = parentCompte.numero;

  // Chercher les comptes enfants existants au format parent.XXX
  const childAccounts = props.comptesFournisseurs
    .map(c => c.numero)
    .filter(n => n.startsWith(parentNumero + '.'));

  // Trouver le plus grand suffixe
  let maxSuffix = 0;
  for (const numero of childAccounts) {
    const suffix = numero.substring(parentNumero.length + 1); // partie apres le point
    const num = parseInt(suffix, 10);
    if (!isNaN(num) && num > maxSuffix) {
      maxSuffix = num;
    }
  }

  // Incrementer et formater avec 3 chiffres
  const nextSuffix = String(maxSuffix + 1).padStart(3, '0');
  form.nouveau_compte_numero = `${parentNumero}.${nextSuffix}`;
};

const previousTab = () => {
  const currentIndex = tabOrder.indexOf(activeTab.value);
  if (currentIndex > 0) {
    activeTab.value = tabOrder[currentIndex - 1];
  }
};

const nextTab = () => {
  const currentIndex = tabOrder.indexOf(activeTab.value);
  if (currentIndex < tabOrder.length - 1) {
    activeTab.value = tabOrder[currentIndex + 1];
  }
};

const handleCancel = () => {
  dialogVisible.value = false;
};

const handleClosed = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  // Reset form data
  const initialData = getInitialFormData();
  Object.keys(initialData).forEach(key => {
    form[key] = initialData[key];
  });
  activeTab.value = 'general';
  compteMode.value = 'select';
  validationErrors.value = [];
};

const handleSubmit = async () => {
  if (!formRef.value) return;

  // Réinitialiser les erreurs
  validationErrors.value = [];

  try {
    await formRef.value.validate();

    // Prepare data based on compte mode
    const data = { ...form };
    if (compteMode.value === 'create') {
      data.create_compte = true;
      data.compte_comptable_id = null;
    } else {
      data.create_compte = false;
      data.compte_parent_id = null;
      data.nouveau_compte_numero = '';
      data.nouveau_compte_libelle = '';
    }

    // Emit to parent - parent will handle API call
    emit('success', data);

  } catch (error) {
    console.error('Erreurs de validation:', error);

    // Collecter TOUTES les erreurs de validation
    const errors = [];
    const fieldsWithErrors = Object.keys(error || {});

    for (const fieldName of fieldsWithErrors) {
      const fieldErrors = error[fieldName];
      if (Array.isArray(fieldErrors)) {
        for (const fieldError of fieldErrors) {
          errors.push({
            field: fieldLabels[fieldName] || fieldName,
            message: fieldError.message || fieldError
          });
        }
      }
    }

    // Afficher toutes les erreurs globalement
    validationErrors.value = errors;

    // Scroller vers le haut pour voir les erreurs
    const dialogBody = document.querySelector('.fournisseur-modal .el-dialog__body');
    if (dialogBody) {
      dialogBody.scrollTop = 0;
    }

    ElMessage.error({
      message: `${errors.length} erreur(s) détectée(s). Veuillez corriger les champs signalés.`,
      duration: 4000
    });
  }
};
</script>

<style scoped>
/* Styles pour les erreurs globales */
.global-errors-alert {
  margin: 16px 20px;
  border-radius: 8px;
}

.errors-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 14px;
}

.errors-list {
  margin: 8px 0 0 0;
  padding-left: 20px;
  list-style: disc;
}

.error-item {
  margin: 4px 0;
  font-size: 13px;
  line-height: 1.5;
}

.error-field {
  font-weight: 600;
  color: #c45656;
}

.fournisseur-modal :deep(.el-dialog) {
  border-radius: 12px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.fournisseur-modal :deep(.el-dialog__body) {
  padding: 0;
  overflow: hidden;
}

.fournisseur-modal :deep(.el-tabs) {
  height: 100%;
}

.fournisseur-modal :deep(.el-tabs__content) {
  padding: 0;
  max-height: calc(90vh - 250px);
  overflow-y: auto;
}

.fournisseur-modal :deep(.el-tab-pane) {
  padding: 20px;
}

.tab-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.tab-required-dot {
  color: #f56c6c;
  font-size: 16px;
  font-weight: bold;
  margin-left: 4px;
  vertical-align: middle;
}

.tab-content {
  min-height: 300px;
}

.required-star {
  color: #f56c6c;
  margin-left: 2px;
}

.form-hint {
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}

.switch-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ml-2 {
  margin-left: 8px;
}

.input-suffix {
  color: #909399;
  font-size: 13px;
}

.alert-title {
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
}

.alert-content {
  font-size: 13px;
  line-height: 1.6;
  margin-top: 4px;
}

.compte-section {
  margin-top: 20px;
}

.compte-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.compte-option :deep(.el-tag) {
  min-width: 85px;
  text-align: center;
  font-family: 'Courier New', monospace;
}

.compte-libelle {
  font-size: 13px;
  color: #6b7280;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.compte-preview {
  margin-top: 16px;
  background-color: #f9fafb;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 12px;
}

.nouveau-compte-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 8px;
}

.compte-preview-libelle {
  font-size: 15px;
  font-weight: 500;
  color: #1f2937;
}

.tab-navigation {
  display: flex;
  justify-content: space-between;
  padding: 16px 20px;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.mb-4 {
  margin-bottom: 16px;
}

.mt-4 {
  margin-top: 16px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

:deep(.el-form-item.is-error .el-input__wrapper) {
  box-shadow: 0 0 0 1px #f56c6c inset;
}

:deep(.el-form-item__error) {
  font-size: 12px;
  padding-top: 4px;
}

:deep(.el-input__inner),
:deep(.el-textarea__inner) {
  border-radius: 6px;
}

:deep(.el-alert) {
  border-radius: 8px;
}

:deep(.el-radio-button__inner) {
  display: flex;
  align-items: center;
  gap: 6px;
}

:deep(.el-tabs--border-card) {
  border-radius: 8px;
  border: none;
  box-shadow: none;
}

:deep(.el-tabs__header) {
  background-color: #f3f4f6;
  border-radius: 8px 8px 0 0;
}

:deep(.el-tabs__item) {
  padding: 12px 16px;
  height: auto;
}

:deep(.el-tabs__item.is-active) {
  background-color: white;
  border-radius: 8px 8px 0 0;
}

:deep(.el-switch) {
  height: 24px;
}

:deep(.el-switch__core) {
  min-width: 50px;
  height: 24px;
  border-radius: 12px;
}

:deep(.el-switch__core .el-switch__action) {
  width: 18px;
  height: 18px;
}
</style>
