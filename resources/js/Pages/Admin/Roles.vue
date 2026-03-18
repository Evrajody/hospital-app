<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <div class="page-header">
        <h1 class="page-title">Rôles & Permissions</h1>
        <el-button type="primary" :icon="Plus" @click="openRoleModal()">
          Nouveau Rôle
        </el-button>
      </div>

      <div class="roles-layout">
        <!-- Sidebar Rôles -->
        <div class="roles-sidebar">
          <div class="sidebar-title">Rôles</div>
          <div
            v-for="role in props.roles"
            :key="role.id"
            class="role-item"
            :class="{ active: selectedRole?.id === role.id }"
            @click="selectRole(role)"
          >
            <div class="role-info">
              <span class="role-name">{{ role.name }}</span>
              <span class="role-users">{{ role.users_count }} utilisateur{{ role.users_count > 1 ? 's' : '' }}</span>
            </div>
            <div class="role-actions">
              <el-button :icon="Edit" circle size="small" @click.stop="openRoleModal(role)" />
              <el-button type="danger" :icon="Delete" circle size="small" @click.stop="handleDeleteRole(role)" />
            </div>
          </div>
          <div v-if="!props.roles.length" class="no-roles">Aucun rôle</div>
        </div>

        <!-- Permissions -->
        <div class="permissions-panel">
          <div v-if="selectedRole" class="permissions-header">
            <span class="permissions-title">Permissions de <strong>{{ selectedRole.name }}</strong></span>
            <span class="permissions-count">{{ selectedRole.permissions.length }} / {{ props.permissions.length }}</span>
          </div>
          <div v-else class="permissions-header">
            <span class="permissions-title">Sélectionnez un rôle pour voir ses permissions</span>
          </div>

          <div class="permissions-grid">
            <div v-for="(perms, module) in props.permissionGroups" :key="module" class="permission-module">
              <div class="module-header">
                {{ module }}
              </div>
              <div class="module-permissions">
                <div
                  v-for="perm in perms"
                  :key="perm.id"
                  class="permission-item"
                  :class="{
                    active: selectedRole && selectedRole.permissions.includes(perm.name),
                    inactive: selectedRole && !selectedRole.permissions.includes(perm.name),
                    'no-selection': !selectedRole
                  }"
                >
                  <el-icon v-if="selectedRole && selectedRole.permissions.includes(perm.name)" color="#67c23a" :size="14"><Check /></el-icon>
                  <el-icon v-else-if="selectedRole" color="#c0c4cc" :size="14"><Close /></el-icon>
                  <span class="permission-label">{{ perm.name.split('.').pop() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Rôle -->
      <el-dialog
        v-model="showRoleModal"
        :title="editingRole ? 'Modifier le rôle' : 'Nouveau Rôle'"
        width="600px"
        :close-on-click-modal="false"
      >
        <el-form ref="roleFormRef" :model="roleForm" :rules="roleRules" label-position="top">
          <el-form-item label="Nom du rôle" prop="name">
            <el-input v-model="roleForm.name" placeholder="Ex: Comptable, Gestionnaire..." />
          </el-form-item>

          <el-form-item label="Permissions">
            <div style="max-height: 400px; overflow-y: auto; width: 100%;">
              <div v-for="(perms, module) in props.permissionGroups" :key="module" style="margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                  <el-checkbox
                    :model-value="isModuleFullyChecked(perms)"
                    :indeterminate="isModulePartiallyChecked(perms)"
                    @change="toggleModule(perms, $event)"
                  >
                    <span style="font-weight: 600; text-transform: uppercase; font-size: 13px;">{{ module }}</span>
                  </el-checkbox>
                </div>
                <div style="padding-left: 24px; display: flex; flex-wrap: wrap; gap: 6px;">
                  <el-checkbox
                    v-for="perm in perms"
                    :key="perm.id"
                    :model-value="roleForm.permissions.includes(perm.name)"
                    @change="togglePermission(perm.name, $event)"
                  >
                    {{ perm.name.split('.').pop() }}
                  </el-checkbox>
                </div>
              </div>
            </div>
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="showRoleModal = false">Annuler</el-button>
          <el-button type="primary" :loading="submitting" @click="handleSubmitRole">
            {{ editingRole ? 'Modifier' : 'Créer' }}
          </el-button>
        </template>
      </el-dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Edit, Delete, Check, Close } from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  roles: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
  permissionGroups: { type: Object, default: () => ({}) },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rôles & Permissions', path: '' },
];

const selectedRole = ref(props.roles.find(r => r.name === 'admin') || props.roles[0] || null);
const showRoleModal = ref(false);
const editingRole = ref(null);
const submitting = ref(false);
const roleFormRef = ref(null);

const roleForm = reactive({ name: '', permissions: [] });

const roleRules = {
  name: [{ required: true, message: 'Le nom est requis', trigger: 'blur' }],
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const selectRole = (role) => {
  selectedRole.value = selectedRole.value?.id === role.id ? null : role;
};

const openRoleModal = (role = null) => {
  editingRole.value = role;
  if (role) {
    roleForm.name = role.name;
    roleForm.permissions = [...role.permissions];
  } else {
    roleForm.name = '';
    roleForm.permissions = [];
  }
  showRoleModal.value = true;
};

const togglePermission = (permName, checked) => {
  if (checked) {
    if (!roleForm.permissions.includes(permName)) roleForm.permissions.push(permName);
  } else {
    roleForm.permissions = roleForm.permissions.filter(p => p !== permName);
  }
};

const isModuleFullyChecked = (perms) => {
  return perms.every(p => roleForm.permissions.includes(p.name));
};

const isModulePartiallyChecked = (perms) => {
  const checked = perms.filter(p => roleForm.permissions.includes(p.name)).length;
  return checked > 0 && checked < perms.length;
};

const toggleModule = (perms, checked) => {
  if (checked) {
    perms.forEach(p => {
      if (!roleForm.permissions.includes(p.name)) roleForm.permissions.push(p.name);
    });
  } else {
    const permNames = perms.map(p => p.name);
    roleForm.permissions = roleForm.permissions.filter(p => !permNames.includes(p));
  }
};

const handleSubmitRole = async () => {
  if (!roleFormRef.value) return;
  await roleFormRef.value.validate(async (valid) => {
    if (!valid) return;
    submitting.value = true;

    const url = editingRole.value ? `/api/roles/${editingRole.value.id}` : '/api/roles';
    const method = editingRole.value ? 'PUT' : 'POST';

    try {
      const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(roleForm),
      });
      const result = await response.json();
      if (result.success) {
        ElMessage.success(result.message);
        showRoleModal.value = false;
        selectedRole.value = null;
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

const handleDeleteRole = (role) => {
  if (role.users_count > 0) {
    ElMessage.warning('Ce rôle est assigné à des utilisateurs');
    return;
  }
  ElMessageBox.confirm(`Supprimer le rôle "${role.name}" ?`, 'Confirmation', {
    confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning',
  }).then(async () => {
    try {
      const response = await fetch(`/api/roles/${role.id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      });
      const result = await response.json();
      if (result.success) {
        ElMessage.success(result.message);
        if (selectedRole.value?.id === role.id) selectedRole.value = null;
        router.reload();
      } else {
        ElMessage.error(result.message);
      }
    } catch (err) {
      ElMessage.error('Erreur de connexion');
    }
  }).catch(() => {});
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

.roles-layout {
  display: flex;
  gap: 20px;
  min-height: 500px;
}

/* Sidebar Rôles */
.roles-sidebar {
  width: 280px;
  flex-shrink: 0;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.sidebar-title {
  padding: 14px 16px;
  font-weight: 600;
  font-size: 15px;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.role-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.15s;
}

.role-item:hover {
  background: #f3f4f6;
}

.role-item.active {
  background: #ecf5ff;
  border-left: 3px solid #409eff;
}

.role-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.role-name {
  font-weight: 600;
  font-size: 14px;
  color: #1f2937;
}

.role-users {
  font-size: 12px;
  color: #9ca3af;
}

.role-actions {
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.15s;
}

.role-item:hover .role-actions {
  opacity: 1;
}

.no-roles {
  padding: 20px;
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
}

/* Permissions Panel */
.permissions-panel {
  flex: 1;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.permissions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.permissions-title {
  font-size: 14px;
  color: #374151;
}

.permissions-count {
  font-size: 13px;
  color: #6b7280;
  background: #e5e7eb;
  padding: 2px 10px;
  border-radius: 12px;
}

.permissions-grid {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.permission-module {
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
}

.module-header {
  padding: 8px 14px;
  font-weight: 600;
  font-size: 13px;
  color: #374151;
  text-transform: uppercase;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  letter-spacing: 0.5px;
}

.module-permissions {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
}

.permission-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  font-size: 13px;
  border-right: 1px solid #f3f4f6;
  border-bottom: 1px solid #f3f4f6;
  min-width: 140px;
  transition: background 0.15s;
}

.permission-item.active {
  background: #f0f9eb;
  color: #1f2937;
}

.permission-item.inactive {
  background: #fafafa;
  color: #c0c4cc;
}

.permission-item.no-selection {
  color: #6b7280;
}

.permission-label {
  text-transform: capitalize;
}
</style>
