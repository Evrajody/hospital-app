<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <div class="page-header">
        <h1 class="page-title">Gestion des Utilisateurs</h1>
        <el-button type="primary" :icon="Plus" @click="openModal()">
          Nouvel Utilisateur
        </el-button>
      </div>

      <el-card shadow="never">
        <el-table :data="pagedUsers" stripe style="width: 100%">
          <el-table-column prop="name" label="Nom" sortable />
          <el-table-column prop="email" label="Email" sortable />
          <el-table-column prop="poste" label="Poste" sortable />
          <el-table-column prop="telephone" label="Téléphone" sortable />
          <el-table-column label="Rôles">
            <template #default="{ row }">
              <el-tag v-for="role in row.roles" :key="role" size="small" style="margin-right: 4px;">
                {{ role }}
              </el-tag>
              <span v-if="!row.roles.length" style="color: #909399; font-size: 12px;">Aucun</span>
            </template>
          </el-table-column>
          <el-table-column label="Statut" width="100" align="center">
            <template #default="{ row }">
              <el-switch
                :model-value="row.is_active"
                @change="toggleActive(row)"
                size="small"
              />
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center">
            <template #default="{ row }">
              <el-button type="primary" :icon="Edit" circle size="small" @click="openModal(row)" />
              <el-button type="danger" :icon="Delete" circle size="small" @click="handleDelete(row)" />
            </template>
          </el-table-column>
        </el-table>

        <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="props.users.length"
            layout="total, sizes, prev, pager, next, jumper"
            background
          />
        </div>
      </el-card>

      <!-- Modal Utilisateur -->
      <el-dialog
        v-model="showModal"
        :title="editingUser ? 'Modifier l\'utilisateur' : 'Nouvel Utilisateur'"
        width="550px"
        :close-on-click-modal="false"
      >
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="top"
          @submit.prevent="handleSubmit"
        >
          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item label="Nom complet" prop="name">
                <el-input v-model="form.name" placeholder="Nom complet" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Email" prop="email">
                <el-input v-model="form.email" type="email" placeholder="email@example.com" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="12">
              <el-form-item :label="editingUser ? 'Nouveau mot de passe' : 'Mot de passe'" prop="password">
                <el-input v-model="form.password" type="password" show-password :placeholder="editingUser ? 'Laisser vide pour ne pas changer' : 'Mot de passe'" />
                <div class="form-hint">8 caractères minimum, minuscule, majuscule, chiffre et caractère spécial.</div>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Téléphone" prop="telephone">
                <el-input v-model="form.telephone" placeholder="Téléphone" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Poste / Fonction" prop="poste">
            <el-input v-model="form.poste" placeholder="Ex: Comptable, Directeur..." />
          </el-form-item>

          <el-form-item label="Rôles" prop="roles">
            <el-select v-model="form.roles" multiple placeholder="Sélectionner les rôles" style="width: 100%;">
              <el-option v-for="role in props.roles" :key="role.id" :label="role.name" :value="role.name" />
            </el-select>
          </el-form-item>

          <el-form-item label="Statut">
            <el-switch v-model="form.is_active" active-text="Actif" inactive-text="Inactif" />
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="showModal = false">Annuler</el-button>
          <el-button type="primary" :loading="submitting" @click="handleSubmit">
            {{ editingUser ? 'Modifier' : 'Créer' }}
          </el-button>
        </template>
      </el-dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Edit, Delete } from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchApi } from '@/Composables/useFetch';

const props = defineProps({
  users: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
});

// Pagination (client-side)
const currentPage = ref(1);
const pageSize = ref(20);
const pagedUsers = computed(() =>
  props.users.slice((currentPage.value - 1) * pageSize.value, currentPage.value * pageSize.value)
);
watch(() => props.users.length, () => {
  const maxPage = Math.max(1, Math.ceil(props.users.length / pageSize.value));
  if (currentPage.value > maxPage) currentPage.value = maxPage;
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Utilisateurs', path: '' },
];

const showModal = ref(false);
const editingUser = ref(null);
const submitting = ref(false);
const formRef = ref(null);

const form = reactive({
  name: '',
  email: '',
  password: '',
  telephone: '',
  poste: '',
  roles: [],
  is_active: true,
});

const rules = {
  name: [{ required: true, message: 'Le nom est requis', trigger: 'blur' }],
  email: [
    { required: true, message: 'L\'email est requis', trigger: 'blur' },
    { type: 'email', message: 'Email invalide', trigger: 'blur' },
  ],
  password: [
    {
      validator: (rule, value, callback) => {
        // En édition, un mot de passe vide est autorisé
        if (!value) {
          if (!editingUser.value) return callback(new Error('Le mot de passe est requis'));
          return callback();
        }
        if (value.length < 8) return callback(new Error('8 caractères minimum'));
        if (!/[a-z]/.test(value)) return callback(new Error('Doit contenir une minuscule'));
        if (!/[A-Z]/.test(value)) return callback(new Error('Doit contenir une majuscule'));
        if (!/[0-9]/.test(value)) return callback(new Error('Doit contenir un chiffre'));
        if (!/[^A-Za-z0-9]/.test(value)) return callback(new Error('Doit contenir un caractère spécial'));
        callback();
      },
      trigger: 'blur',
    },
  ],
};

const openModal = (user = null) => {
  editingUser.value = user;
  if (user) {
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.telephone = user.telephone || '';
    form.poste = user.poste || '';
    form.roles = [...user.roles];
    form.is_active = user.is_active;
  } else {
    form.name = '';
    form.email = '';
    form.password = '';
    form.telephone = '';
    form.poste = '';
    form.roles = [];
    form.is_active = true;
  }
  showModal.value = true;
};

const handleSubmit = async () => {
  if (!formRef.value) return;
  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    // Si création, le password est requis
    if (!editingUser.value && !form.password) {
      ElMessage.warning('Le mot de passe est requis pour un nouvel utilisateur');
      return;
    }

    submitting.value = true;
    const url = editingUser.value
      ? `/api/utilisateurs/${editingUser.value.id}`
      : '/api/utilisateurs';
    const method = editingUser.value ? 'PUT' : 'POST';

    try {
      const payload = { ...form };
      if (editingUser.value && !payload.password) {
        delete payload.password;
      }

      const response = await fetchApi(url, {
        method,
        body: payload,
      });

      const result = await response.json();
      if (result.success) {
        ElMessage.success(result.message);
        showModal.value = false;
        router.reload();
      } else {
        ElMessage.error(result.message || 'Erreur');
      }
    } catch (err) {
      ElMessage.error('Erreur de connexion');
    } finally {
      submitting.value = false;
    }
  });
};

const handleDelete = (user) => {
  ElMessageBox.confirm(
    `Supprimer l'utilisateur "${user.name}" ?`,
    'Confirmation',
    { confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning' }
  ).then(async () => {
    try {
      const response = await fetchApi(`/api/utilisateurs/${user.id}`, {
        method: 'DELETE',
      });
      const result = await response.json();
      if (result.success) {
        ElMessage.success(result.message);
        router.reload();
      } else {
        ElMessage.error(result.message);
      }
    } catch (err) {
      ElMessage.error('Erreur de connexion');
    }
  }).catch(() => {});
};

const toggleActive = async (user) => {
  try {
    const response = await fetchApi(`/api/utilisateurs/${user.id}/toggle-active`, {
      method: 'PATCH',
    });
    const result = await response.json();
    if (result.success) {
      ElMessage.success(result.message);
      router.reload();
    } else {
      ElMessage.error(result.message);
    }
  } catch (err) {
    ElMessage.error('Erreur de connexion');
  }
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
  align-items: center;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}
</style>
