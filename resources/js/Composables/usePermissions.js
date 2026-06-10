import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
  const page = usePage();
  const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
  const isAdmin = computed(() => page.props.auth?.user?.is_admin === true);

  const can = (permission) => {
    // L'administrateur a un accès total.
    if (isAdmin.value) return true;
    return userPermissions.value.includes(permission);
  };

  return { can, isAdmin };
}
