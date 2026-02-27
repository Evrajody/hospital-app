<template>
  <div class="placeholder-tab">
    <el-empty :description="description">
      <template #image>
        <el-icon :size="60" style="color: #ccc"><Warning /></el-icon>
      </template>
    </el-empty>
  </div>
</template>

<script setup>
import { Warning } from '@element-plus/icons-vue';

defineProps({
  description: { type: String, default: 'En cours de développement' },
});
</script>

<style scoped>
.placeholder-tab { padding: 60px 0; }
</style>
