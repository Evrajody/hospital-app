<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="page-container">
      <!-- Hero Header -->
      <div class="hero-header">
        <div class="hero-icon">
          <el-icon :size="28"><Lock /></el-icon>
        </div>
        <div class="hero-content">
          <h1 class="hero-title">Rôles & Permissions</h1>
          <p class="hero-subtitle">
            Gérez la matrice d'accès. Cliquez sur une cellule pour activer ou désactiver une permission pour le rôle concerné.
          </p>
          <div class="hero-stats">
            <span class="stat-pill"><strong>{{ roles.length }}</strong> rôles</span>
            <span class="stat-pill"><strong>{{ permissions.length }}</strong> permissions</span>
          </div>
        </div>
        <el-button type="primary" :icon="Plus" size="large" @click="openRoleModal()">
          Nouveau Rôle
        </el-button>
      </div>

      <!-- Matrice -->
      <div class="matrix-card">
        <div class="matrix-scroll">
          <table class="matrix-table">
            <thead>
              <tr>
                <th class="col-permission">
                  <div class="th-permission-header">
                    <el-icon><Setting /></el-icon>
                    <span>Permission</span>
                  </div>
                </th>
                <th
                  v-for="role in roles"
                  :key="role.id"
                  class="col-role"
                >
                  <div class="role-header">
                    <div class="role-name">{{ role.name }}</div>
                    <div class="role-slug">{{ role.slug }}</div>
                    <div class="role-stats">
                      <span :class="['stat-count', getStatClass(role)]">
                        {{ role.permissions.length }}/{{ permissions.length }}
                      </span>
                    </div>
                    <div class="role-actions">
                      <el-tooltip content="Tout activer pour ce rôle" placement="top">
                        <el-button
                          :icon="Select"
                          size="small"
                          circle
                          @click="bulkRolePermissions(role, true)"
                        />
                      </el-tooltip>
                      <el-tooltip content="Tout désactiver pour ce rôle" placement="top">
                        <el-button
                          :icon="CloseBold"
                          size="small"
                          circle
                          @click="bulkRolePermissions(role, false)"
                        />
                      </el-tooltip>
                      <el-tooltip content="Modifier le rôle" placement="top">
                        <el-button
                          :icon="Edit"
                          size="small"
                          circle
                          type="warning"
                          plain
                          @click="openRoleModal(role)"
                        />
                      </el-tooltip>
                      <el-tooltip content="Supprimer le rôle" placement="top">
                        <el-button
                          :icon="Delete"
                          size="small"
                          circle
                          type="danger"
                          plain
                          :disabled="role.users_count > 0"
                          @click="handleDeleteRole(role)"
                        />
                      </el-tooltip>
                    </div>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <template v-for="group in permissionGroups" :key="group.key">
                <!-- Header de groupe -->
                <tr class="group-row">
                  <td class="col-permission">
                    <div class="group-label">
                      <el-icon><Folder /></el-icon>
                      <strong>{{ group.label }}</strong>
                      <span class="group-count">{{ group.permissions.length }} permissions</span>
                    </div>
                  </td>
                  <td v-for="role in roles" :key="role.id" class="group-bulk-cell">
                    <el-tooltip
                      :content="groupAllChecked(group, role) ? 'Tout retirer' : 'Tout appliquer'"
                      placement="top"
                    >
                      <button
                        class="bulk-btn"
                        :class="{ 'bulk-btn-active': groupAllChecked(group, role) }"
                        @click="toggleGroupForRole(group, role)"
                      >
                        {{ groupAllChecked(group, role) ? '✓ Tout' : 'Tout appliquer' }}
                      </button>
                    </el-tooltip>
                  </td>
                </tr>

                <!-- Permissions du groupe -->
                <tr
                  v-for="perm in group.permissions"
                  :key="perm.id"
                  class="permission-row"
                >
                  <td class="col-permission">
                    <div class="permission-info">
                      <div class="permission-label">{{ perm.label }}</div>
                      <div class="permission-meta">
                        <code class="permission-name">{{ perm.name }}</code>
                        <span class="permission-roles-count">
                          {{ rolesWithPermission(perm.name) }}/{{ roles.length }} rôles
                        </span>
                      </div>
                    </div>
                  </td>
                  <td
                    v-for="role in roles"
                    :key="role.id"
                    class="cell-toggle"
                    :class="{ 'cell-active': role.permissions.includes(perm.name) }"
                    @click="togglePermissionForRole(role, perm.name)"
                  >
                    <el-icon
                      v-if="role.permissions.includes(perm.name)"
                      :size="20"
                      class="icon-check"
                    >
                      <Check />
                    </el-icon>
                    <span v-else class="icon-dash">—</span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Création/Modification Rôle -->
      <el-dialog
        v-model="showRoleModal"
        :title="editingRole ? `Modifier le rôle « ${editingRole.name} »` : 'Nouveau Rôle'"
        width="500px"
        :close-on-click-modal="false"
      >
        <el-form ref="roleFormRef" :model="roleForm" :rules="roleRules" label-position="top">
          <el-form-item label="Nom du rôle" prop="name">
            <el-input v-model="roleForm.name" placeholder="Ex: Comptable, Caissier..." />
          </el-form-item>
        </el-form>

        <p class="modal-hint">
          Une fois le rôle créé, gérez ses permissions directement dans la matrice en cochant les cellules.
        </p>

        <template #footer>
          <el-button @click="showRoleModal = false">Annuler</el-button>
          <el-button type="primary" :loading="submitting" @click="handleSubmitRole">
            {{ editingRole ? 'Mettre à jour' : 'Créer' }}
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
import {
  Plus, Edit, Delete, Check, Lock, Folder, Setting, Select, CloseBold,
} from '@element-plus/icons-vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchApi } from '@/Composables/useFetch';

const props = defineProps({
  roles: { type: Array, default: () => [] },
  permissions: { type: Array, default: () => [] },
  permissionGroups: { type: Array, default: () => [] },
});

const breadcrumbs = [
  { title: 'Tableau de bord', path: '/dashboard' },
  { title: 'Rôles & Permissions', path: '' },
];

// État local des rôles (mutable pour update optimiste)
const roles = ref(JSON.parse(JSON.stringify(props.roles)));
const permissions = computed(() => props.permissions);
const permissionGroups = computed(() => props.permissionGroups);

// Resync quand Inertia recharge les props
watch(() => props.roles, (val) => {
  roles.value = JSON.parse(JSON.stringify(val));
}, { deep: true });

const showRoleModal = ref(false);
const editingRole = ref(null);
const submitting = ref(false);
const roleFormRef = ref(null);

const roleForm = reactive({ name: '' });

const roleRules = {
  name: [{ required: true, message: 'Le nom est requis', trigger: 'blur' }],
};

// ============== Helpers ==============

const getStatClass = (role) => {
  const ratio = permissions.value.length === 0 ? 0 : role.permissions.length / permissions.value.length;
  if (ratio >= 1) return 'stat-full';
  if (ratio >= 0.5) return 'stat-half';
  if (ratio > 0) return 'stat-low';
  return 'stat-zero';
};

const rolesWithPermission = (permName) => {
  return roles.value.filter(r => r.permissions.includes(permName)).length;
};

const groupAllChecked = (group, role) => {
  return group.permissions.every(p => role.permissions.includes(p.name));
};

// ============== Toggle permission ==============

const togglePermissionForRole = async (role, permName) => {
  const newValue = !role.permissions.includes(permName);

  // Optimistic update
  if (newValue) {
    if (!role.permissions.includes(permName)) role.permissions.push(permName);
  } else {
    role.permissions = role.permissions.filter(p => p !== permName);
  }

  try {
    const response = await fetchApi(`/api/roles/${role.id}/permission`, {
      method: 'PATCH',
      body: { permission: permName, value: newValue },
    });
    const result = await response.json();
    if (result.success) {
      role.permissions = result.permissions;
    } else {
      // Revert
      ElMessage.error(result.message || 'Erreur');
      revertRolePermissions(role);
    }
  } catch (e) {
    ElMessage.error('Erreur de connexion');
    revertRolePermissions(role);
  }
};

const toggleGroupForRole = async (group, role) => {
  const allChecked = groupAllChecked(group, role);
  const newValue = !allChecked;
  const permNames = group.permissions.map(p => p.name);

  // Optimistic update
  if (newValue) {
    permNames.forEach(name => {
      if (!role.permissions.includes(name)) role.permissions.push(name);
    });
  } else {
    role.permissions = role.permissions.filter(p => !permNames.includes(p));
  }

  try {
    const response = await fetchApi(`/api/roles/${role.id}/permissions/bulk`, {
      method: 'PATCH',
      body: { permissions: permNames, value: newValue },
    });
    const result = await response.json();
    if (result.success) {
      role.permissions = result.permissions;
      ElMessage.success(result.message);
    } else {
      ElMessage.error(result.message || 'Erreur');
      revertRolePermissions(role);
    }
  } catch (e) {
    ElMessage.error('Erreur de connexion');
    revertRolePermissions(role);
  }
};

const bulkRolePermissions = async (role, value) => {
  const action = value ? 'activer' : 'désactiver';
  try {
    await ElMessageBox.confirm(
      `Voulez-vous ${action} TOUTES les permissions pour le rôle « ${role.name} » ?`,
      'Confirmation',
      { confirmButtonText: 'Oui', cancelButtonText: 'Annuler', type: 'warning' }
    );
  } catch {
    return;
  }

  const allPermNames = permissions.value.map(p => p.name);
  // Optimistic
  role.permissions = value ? [...allPermNames] : [];

  try {
    const response = await fetchApi(`/api/roles/${role.id}/permissions/bulk`, {
      method: 'PATCH',
      body: { permissions: allPermNames, value },
    });
    const result = await response.json();
    if (result.success) {
      role.permissions = result.permissions;
      ElMessage.success(result.message);
    } else {
      ElMessage.error(result.message);
      revertRolePermissions(role);
    }
  } catch (e) {
    ElMessage.error('Erreur de connexion');
    revertRolePermissions(role);
  }
};

const revertRolePermissions = (role) => {
  const original = props.roles.find(r => r.id === role.id);
  if (original) role.permissions = [...original.permissions];
};

// ============== CRUD Rôle ==============

const openRoleModal = (role = null) => {
  editingRole.value = role;
  roleForm.name = role ? role.name : '';
  showRoleModal.value = true;
};

const handleSubmitRole = async () => {
  if (!roleFormRef.value) return;
  await roleFormRef.value.validate(async (valid) => {
    if (!valid) return;
    submitting.value = true;

    const url = editingRole.value ? `/api/roles/${editingRole.value.id}` : '/api/roles';
    const method = editingRole.value ? 'PUT' : 'POST';
    const body = editingRole.value
      ? { name: roleForm.name, permissions: editingRole.value.permissions }
      : { name: roleForm.name, permissions: [] };

    try {
      const response = await fetchApi(url, { method, body });
      const result = await response.json();
      if (result.success) {
        ElMessage.success(result.message);
        showRoleModal.value = false;
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
  ElMessageBox.confirm(`Supprimer le rôle « ${role.name} » ?`, 'Confirmation', {
    confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', type: 'warning',
  }).then(async () => {
    try {
      const response = await fetchApi(`/api/roles/${role.id}`, { method: 'DELETE' });
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
</script>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ============ Hero Header ============ */
.hero-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px 28px;
  background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%);
  border: 1px solid #e0e7ff;
  border-radius: 12px;
}

.hero-icon {
  width: 60px;
  height: 60px;
  border-radius: 14px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
}

.hero-content {
  flex: 1;
}

.hero-title {
  font-size: 22px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px;
}

.hero-subtitle {
  color: #6b7280;
  font-size: 14px;
  margin: 0 0 10px;
  line-height: 1.5;
}

.hero-stats {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.stat-pill {
  background: #fff;
  border: 1px solid #e5e7eb;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  color: #6b7280;
}

.stat-pill strong {
  color: #4f46e5;
  font-weight: 700;
  margin-right: 4px;
}

/* ============ Matrice ============ */
.matrix-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.matrix-scroll {
  overflow-x: auto;
  max-height: calc(100vh - 280px);
}

.matrix-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  min-width: 800px;
}

.matrix-table thead {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #f9fafb;
}

.matrix-table th,
.matrix-table td {
  border-bottom: 1px solid #e5e7eb;
  border-right: 1px solid #f1f5f9;
  padding: 0;
}

.matrix-table th:last-child,
.matrix-table td:last-child {
  border-right: none;
}

/* Colonne Permission */
.col-permission {
  width: 320px;
  min-width: 320px;
  background: #fff;
  position: sticky;
  left: 0;
  z-index: 5;
  border-right: 2px solid #e5e7eb !important;
}

.matrix-table thead .col-permission {
  z-index: 11;
  background: #f9fafb;
}

.th-permission-header {
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 13px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Colonne Rôle */
.col-role {
  min-width: 160px;
  text-align: center;
  background: #f9fafb;
}

.role-header {
  padding: 14px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.role-name {
  font-size: 12px;
  font-weight: 700;
  color: #1f2937;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.2;
  text-align: center;
}

.role-slug {
  font-size: 10px;
  color: #9ca3af;
  font-family: 'Courier New', monospace;
}

.role-stats {
  margin-top: 2px;
}

.stat-count {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.stat-full {
  background: #dcfce7;
  color: #166534;
}

.stat-half {
  background: #fef3c7;
  color: #92400e;
}

.stat-low {
  background: #fee2e2;
  color: #991b1b;
}

.stat-zero {
  background: #f3f4f6;
  color: #6b7280;
}

.role-actions {
  display: flex;
  gap: 4px;
  margin-top: 4px;
}

.role-actions :deep(.el-button) {
  padding: 5px;
  min-height: auto;
}

/* Group row */
.group-row {
  background: linear-gradient(90deg, #f9fafb 0%, #f3f4f6 100%);
}

.group-row .col-permission {
  background: #f9fafb !important;
}

.group-label {
  padding: 12px 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.group-label .el-icon {
  color: #6366f1;
}

.group-count {
  margin-left: auto;
  font-size: 11px;
  color: #9ca3af;
  text-transform: none;
  letter-spacing: 0;
}

.group-bulk-cell {
  text-align: center;
  padding: 8px;
}

.bulk-btn {
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 11px;
  color: #6b7280;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.15s ease;
}

.bulk-btn:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.bulk-btn-active {
  background: #6366f1;
  border-color: #6366f1;
  color: #fff;
}

.bulk-btn-active:hover {
  background: #4f46e5;
  border-color: #4f46e5;
}

/* Permission row */
.permission-row:hover {
  background: #fafafa;
}

.permission-row:hover .col-permission {
  background: #fafafa !important;
}

.permission-info {
  padding: 12px 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.permission-label {
  font-size: 14px;
  font-weight: 500;
  color: #1f2937;
}

.permission-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.permission-name {
  font-size: 11px;
  color: #6b7280;
  background: #f3f4f6;
  padding: 1px 6px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}

.permission-roles-count {
  font-size: 11px;
  color: #9ca3af;
}

/* Cellules toggle */
.cell-toggle {
  text-align: center;
  cursor: pointer;
  transition: background-color 0.15s ease;
  padding: 8px;
  user-select: none;
}

.cell-toggle:hover {
  background: #f0f9ff;
}

.cell-toggle.cell-active {
  background: #f0fdf4;
}

.cell-toggle.cell-active:hover {
  background: #dcfce7;
}

.icon-check {
  color: #22c55e;
  font-weight: bold;
}

.icon-dash {
  color: #d1d5db;
  font-size: 18px;
  font-weight: 600;
}

/* Modal */
.modal-hint {
  margin: 12px 0 0;
  font-size: 13px;
  color: #6b7280;
  background: #f9fafb;
  padding: 10px 12px;
  border-radius: 6px;
  border-left: 3px solid #6366f1;
}
</style>
