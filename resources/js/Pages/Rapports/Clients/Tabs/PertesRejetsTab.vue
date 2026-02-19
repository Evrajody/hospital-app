<template>
  <div class="tab-content">
    <div class="placeholder-section">
      <el-empty description="En cours de développement">
        <template #image>
          <el-icon :size="60" style="color: #ccc;"><Warning /></el-icon>
        </template>
        <p style="color: #999; font-size: 13px; margin-top: 8px;">
          Le module des pertes, rejets et régularisations sera bientôt disponible.
        </p>
      </el-empty>
    </div>
  </div>
</template>

<script setup>
import { Warning } from '@element-plus/icons-vue';
</script>

<style scoped>
.tab-content { padding: 0; }
.placeholder-section { padding: 60px 20px; text-align: center; }
</style>
