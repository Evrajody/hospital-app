import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
  const page = usePage();
  const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
  const isAdmin = computed(() => page.props.auth?.user?.is_admin === true);

  const can = (permission) => {
    // Accès piloté par les permissions réellement accordées (aucun bypass).
    return userPermissions.value.includes(permission);
  };

  return { can, isAdmin };
}
