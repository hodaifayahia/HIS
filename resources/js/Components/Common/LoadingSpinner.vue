<!-- components/Common/LoadingSpinner.vue -->
<script setup>
import { computed } from 'vue'
import ProgressSpinner from 'primevue/progressspinner'

const props = defineProps({
  message: {
    type: String,
    default: 'Loading...'
  },
  size: {
    type: String,
    default: 'normal',
    validator: (value) => ['small', 'normal', 'large'].includes(value)
  }
})

const spinnerSize = computed(() => {
  const sizes = {
    small: '2rem',
    normal: '3rem',
    large: '4rem'
  }
  return sizes[props.size]
})
</script>

<template>
  <div class="loading-container">
    <div class="loading-content">
      <ProgressSpinner 
        :style="{ width: spinnerSize, height: spinnerSize }" 
        strokeWidth="3"
        animationDuration="1s"
      />
      <p class="loading-message">{{ message }}</p>
    </div>
  </div>
</template>

<style scoped>
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--surface-200);
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  padding: 2rem;
}

.loading-message {
  color: var(--text-color-secondary);
  font-size: 1.1rem;
  font-weight: 500;
  margin: 0;
  text-align: center;
}
</style>
