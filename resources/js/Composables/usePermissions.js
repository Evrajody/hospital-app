import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
  const page = usePage();
  const userPermissions = computed(() => page.props.auth?.user?.permissions || []);

  const can = (permission) => {
    return userPermissions.value.includes(permission);
  };

  return { can };
}
