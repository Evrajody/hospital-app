<template>
  <div class="paginated-table">
    <el-table
      :data="pagedData"
      v-bind="$attrs"
      :show-summary="showSummary"
      :summary-method="showSummary && summaryMethod ? wrappedSummary : undefined"
    >
      <slot />
    </el-table>

    <div v-if="data.length > pageSizes[0]" class="paginated-table__bar">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="pageSizes"
        :total="data.length"
        layout="total, sizes, prev, pager, next"
        background
        small
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  // Jeu de données COMPLET (la pagination est appliquée en interne).
  data: { type: Array, default: () => [] },
  defaultPageSize: { type: Number, default: 50 },
  pageSizes: { type: Array, default: () => [25, 50, 100] },
  // Repris tels quels d'el-table mais traités ici pour que les totaux
  // portent sur le dataset COMPLET, pas seulement la page affichée.
  showSummary: { type: Boolean, default: false },
  summaryMethod: { type: Function, default: null },
});

const currentPage = ref(1);
const pageSize = ref(props.defaultPageSize);

const pagedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return props.data.slice(start, start + pageSize.value);
});

// Calcule la ligne de totaux sur l'ensemble des données, pas la page courante.
const wrappedSummary = (param) => props.summaryMethod({ columns: param.columns, data: props.data });

watch(() => props.data, () => { currentPage.value = 1; });
watch(pageSize, () => { currentPage.value = 1; });
</script>

<style scoped>
.paginated-table__bar {
  display: flex;
  justify-content: flex-end;
  margin-top: 12px;
}
</style>
