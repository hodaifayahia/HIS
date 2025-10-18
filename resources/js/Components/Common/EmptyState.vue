<!-- components/Common/EmptyState.vue -->
<script setup>
import Button from 'primevue/button'

const props = defineProps({
  icon: {
    type: String,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  actions: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <div class="empty-state">
    <div class="empty-content">
      <div class="empty-icon">
        <i :class="icon"></i>
      </div>
      <h4 class="empty-title">{{ title }}</h4>
      <p class="empty-description">{{ description }}</p>
      <div v-if="actions.length > 0" class="empty-actions">
        <Button
          v-for="(action, index) in actions"
          :key="index"
          :icon="action.icon"
          :label="action.label"
          :class="action.class || 'p-button-primary'"
          @click="action.action"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 3rem 1rem;
}

.empty-content {
  text-align: center;
  max-width: 400px;
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-color-secondary);
  margin-bottom: 1.5rem;
  opacity: 0.6;
}

.empty-title {
  margin: 0 0 1rem 0;
  color: var(--text-color);
  font-size: 1.5rem;
  font-weight: 600;
}

.empty-description {
  margin: 0 0 2rem 0;
  color: var(--text-color-secondary);
  font-size: 1rem;
  line-height: 1.5;
}

.empty-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
}
</style>
