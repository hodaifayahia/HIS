<script setup>
const props = defineProps({
  displayMode: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['update:displayMode']);

const setDisplayMode = (mode) => {
  emit('update:displayMode', mode);
};
</script>

<template>
  <div class="toggle-buttons mb-2 mt-2">
    <button 
      class="toggle-btn" 
      :class="{ active: displayMode === 'working' }" 
      @click="setDisplayMode('working')">
      Show Working Dates
    </button>
    <button 
      class="toggle-btn" 
      :class="{ active: displayMode === 'holidays' }" 
      @click="setDisplayMode('holidays')">
      Days off
    </button>
  </div>
</template>

<style scoped>
.toggle-buttons {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  justify-content: center;
}

.toggle-btn {
  padding: 8px 16px;
  border: 1px solid #dee2e6;
  background-color: #f8f9fa;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  background-color: #e9ecef;
}

.toggle-btn.active {
  background-color: #0d6efd;
  color: white;
  border-color: #0d6efd;
}
</style>