<script setup>
import { ref, computed } from 'vue'; // Import computed

const props = defineProps({
  showModal: Boolean,
  placeholders: Array
});

const emit = defineEmits(['close', 'save']);

const selectedPlaceholder = ref(null);
const attributeName = ref(''); // Simple ref now
const attributeValue = ref('');

const handleSave = () => {
  if (!selectedPlaceholder.value || !attributeName.value) {
    return;
  }

  let processedAttributeName = attributeName.value;

  // Option 1: Replace spaces with underscores
  processedAttributeName = processedAttributeName.replace(/\s+/g, '_');

  // Option 2: Replace spaces with hyphens (uncomment this and comment out Option 1 to use)
  // processedAttributeName = processedAttributeName.replace(/\s+/g, '-');

  emit('save', {
    placeholder: selectedPlaceholder.value,
    name: processedAttributeName, // Use the processed name
    value: attributeValue.value
  });

  // Reset form
  selectedPlaceholder.value = null;
  attributeName.value = '';
  attributeValue.value = '';
};
</script>

<template>
  <div v-if="showModal" class="premium-modal-overlay">
    <div class="premium-modal">
      <div class="premium-modal-header">
        <h3>Add New Attribute</h3>
        <button class="premium-close-btn" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="premium-modal-body">
        <div class="premium-form-group">
          <label class="premium-label">Select Placeholder</label>
          <div class="premium-select-wrapper">
            <select class="premium-select" v-model="selectedPlaceholder">
              <option value="">Choose a placeholder</option>
              <option v-for="placeholder in placeholders" :key="placeholder.id" :value="placeholder">
                {{ placeholder.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="premium-form-group">
          <label class="premium-label">Attribute Name</label>
          <input type="text" class="premium-input" v-model="attributeName" placeholder="Enter attribute name" />
        </div>

        <div class="premium-form-group">
          <label class="premium-label">Default Value (Optional)</label>
          <input type="text" class="premium-input" v-model="attributeValue" placeholder="Enter default value" />
        </div>
      </div>

      <div class="premium-modal-footer">
        <button class="btn-premium-light" @click="$emit('close')">Cancel</button>
        <button class="btn-premium" @click="handleSave" :disabled="!selectedPlaceholder || !attributeName">
          Save Attribute
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.premium-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.premium-modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.premium-modal-header {
  padding: 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.premium-modal-body {
  padding: 1.5rem;
}

.premium-modal-footer {
  padding: 1.25rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.premium-close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #64748b;
}
</style>