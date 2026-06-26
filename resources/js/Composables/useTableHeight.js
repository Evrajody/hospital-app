import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';

/**
 * Calcule une hauteur de tableau (el-table) qui remplit l'espace restant jusqu'au bas
 * de la fenêtre, de sorte que SEUL le corps du tableau défile (en-tête + ligne de total
 * restent figés) et que les filtres/au-dessus restent visibles sans défilement de page.
 *
 * @param {import('vue').Ref<any>} elRef  ref de l'el-table (ou d'un wrapper) servant de repère haut.
 * @param {number} bottomGap  espace réservé sous le tableau (pagination, marges) en px.
 */
export function useTableHeight(elRef, bottomGap = 80) {
  const tableHeight = ref(400);

  const recompute = () => {
    const node = elRef.value?.$el || elRef.value;
    if (!node) return;
    const top = node.getBoundingClientRect().top;
    tableHeight.value = Math.max(220, Math.floor(window.innerHeight - top - bottomGap));
  };

  onMounted(() => {
    nextTick(recompute);
    window.addEventListener('resize', recompute);
  });
  onBeforeUnmount(() => window.removeEventListener('resize', recompute));

  return { tableHeight, recomputeTableHeight: recompute };
}
