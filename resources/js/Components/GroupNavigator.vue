<template>
  <div class="group-navigator">
    <div v-if="groups.length" class="group-navigator__bar">
      <el-button-group>
        <el-button :disabled="current === 0" @click="prev">
          <el-icon><ArrowLeft /></el-icon>
        </el-button>
        <el-button :disabled="current >= groups.length - 1" @click="next">
          <el-icon><ArrowRight /></el-icon>
        </el-button>
      </el-button-group>

      <span class="group-navigator__label">
        <slot name="label" :group="groups[current]" :index="current" :total="groups.length" />
      </span>

      <span class="group-navigator__counter">{{ current + 1 }} / {{ groups.length }}</span>
    </div>

    <slot v-if="groups.length" :group="groups[current]" :index="current" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ArrowLeft, ArrowRight } from '@element-plus/icons-vue';

const props = defineProps({
  // Liste des groupes (un fournisseur / client / date par entrée).
  groups: { type: Array, default: () => [] },
});

const current = ref(0);

const prev = () => { if (current.value > 0) current.value--; };
const next = () => { if (current.value < props.groups.length - 1) current.value++; };

// Revenir au premier groupe quand les données changent.
watch(() => props.groups, () => { current.value = 0; });
</script>

<style scoped>
.group-navigator__bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  padding: 8px 12px;
  background: #f5f7fa;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.group-navigator__label {
  flex: 1;
  min-width: 0;
  font-size: 13px;
  color: #374151;
}

.group-navigator__counter {
  font-size: 12px;
  color: #6b7280;
  font-weight: 600;
  white-space: nowrap;
}
</style>
