import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
  const page = usePage();
  const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
  const isSuperAdmin = computed(() => page.props.auth?.user?.is_super_admin === true);

  const can = (permission) => {
    // Le super administrateur a un accès total.
    if (isSuperAdmin.value) return true;
    return userPermissions.value.includes(permission);
  };

  return { can, isSuperAdmin };
}
