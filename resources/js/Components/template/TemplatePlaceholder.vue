<script setup>
import { computed } from 'vue';

const props = defineProps({
  dbPlaceholders: {
    type: Array,
    required: true
  },
  dbAttributes: {
    type: Object,
    required: true
  },
  selectedPlaceholder: {
    type: Object,
    default: null
  },
  selectedAttribute: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:selectedPlaceholder', 'update:selectedAttribute', 'insert-placeholder']);

const placeholderText = computed(() => {
  if (props.selectedPlaceholder && props.selectedAttribute) {
    return `{{${props.selectedPlaceholder.name}.${props.selectedAttribute.name}}}`;
  }
  return null;
});

const handleInsert = () => {
  if (placeholderText.value) {
    emit('insert-placeholder', placeholderText.value);
  }
};

const updatePlaceholder = (event) => {
  emit('update:selectedPlaceholder', event.target.value);
};

const updateAttribute = (event) => {
  emit('update:selectedAttribute', event.target.value);
};
</script>

<template>
  <div class="premium-editor-sidebar">
    <div class="premium-sidebar-panel">
      <div class="premium-sidebar-header">
        <i class="fas fa-puzzle-piece me-2"></i>Placeholders
      </div>

      <div class="premium-sidebar-content">
        <div class="premium-form-group">
          <label class="premium-label">Placeholder</label>
          <div class="premium-select-wrapper">
            <select 
              class="premium-select" 
              :value="selectedPlaceholder"
              @change="updatePlaceholder"
            >
              <option :value="null">Select placeholder</option>
              <option v-for="placeholder in dbPlaceholders" :key="placeholder.id" :value="placeholder">
                {{ placeholder.name }}
              </option>
            </select>
            <i class="fas fa-chevron-down"></i>
          </div>
        </div>

        <div v-if="selectedPlaceholder" class="premium-form-group">
          <label class="premium-label">Attribute</label>
          <div class="premium-select-wrapper">
            <select 
              class="premium-select" 
              :value="selectedAttribute"
              @change="updateAttribute"
            >
              <option :value="null">Select attribute</option>
              <option 
                v-for="attr in dbAttributes[selectedPlaceholder.id]" 
                :key="attr.id" 
                :value="attr"
              >
                {{ attr.name }}
              </option>
            </select>
            <i class="fas fa-chevron-down"></i>
          </div>
        </div>

        <button 
          class="btn-premium mt-3" 
          @click="handleInsert"
          :disabled="!placeholderText"
        >
          Insert Placeholder
        </button>
      </div>
    </div>
  </div>
</template>