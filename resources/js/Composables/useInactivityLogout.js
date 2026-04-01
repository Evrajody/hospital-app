import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { ElMessageBox } from 'element-plus';

const INACTIVITY_TIMEOUT = 15 * 60 * 1000; // 15 minutes
const WARNING_BEFORE = 60 * 1000; // 1 minute avant déconnexion

export function useInactivityLogout() {
  let inactivityTimer = null;
  let warningTimer = null;
  let warningShown = false;

  const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];

  const logout = () => {
    router.post('/logout');
  };

  const showWarning = () => {
    if (warningShown) return;
    warningShown = true;

    ElMessageBox.confirm(
      'Vous serez déconnecté dans 1 minute pour cause d\'inactivité. Souhaitez-vous rester connecté ?',
      'Session bientôt expirée',
      {
        confirmButtonText: 'Rester connecté',
        cancelButtonText: 'Se déconnecter',
        type: 'warning',
        closeOnClickModal: false,
        closeOnPressEscape: false,
      }
    ).then(() => {
      warningShown = false;
      resetTimers();
    }).catch(() => {
      warningShown = false;
      logout();
    });

    warningTimer = setTimeout(() => {
      warningShown = false;
      ElMessageBox.close();
      logout();
    }, WARNING_BEFORE);
  };

  const resetTimers = () => {
    if (inactivityTimer) clearTimeout(inactivityTimer);
    if (warningTimer) clearTimeout(warningTimer);
    warningShown = false;

    inactivityTimer = setTimeout(() => {
      showWarning();
    }, INACTIVITY_TIMEOUT - WARNING_BEFORE);
  };

  const onActivity = () => {
    if (!warningShown) {
      resetTimers();
    }
  };

  onMounted(() => {
    events.forEach(event => document.addEventListener(event, onActivity, { passive: true }));
    resetTimers();
  });

  onUnmounted(() => {
    events.forEach(event => document.removeEventListener(event, onActivity));
    if (inactivityTimer) clearTimeout(inactivityTimer);
    if (warningTimer) clearTimeout(warningTimer);
  });
}
