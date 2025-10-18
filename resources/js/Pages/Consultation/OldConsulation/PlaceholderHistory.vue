<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';

const toaster = useToastr();

const props = defineProps({
  appointmentId: {
    type: Number,
    required: true
  },
  consultationData: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits([]);

// State
const placeholders = ref([]);
const detailedAttributes = ref({});
const isLoading = ref(false);
const error = ref(null);
const selectedPlaceholder = ref(null);

// API Method to fetch all consultation attributes at once
const fetchAllConsultationAttributes = async () => {
  try {
    isLoading.value = true;
    error.value = null;

    const response = await axios.get(`/api/placeholders/consultation/${props.appointmentId}/attributes`);

    if (response.data.success) {
      const apiData = response.data.data;

      placeholders.value = Object.keys(apiData).map((name, index) => ({
        id: name,
        name: name,
        attributes: apiData[name]
      }));

      detailedAttributes.value = apiData;

      if (placeholders.value.length > 0) {
        selectedPlaceholder.value = placeholders.value[0];
      }

    } else {
      throw new Error(response.data.message || 'Failed to fetch consultation attributes');
    }
  } catch (err) {
    error.value = err.message || 'Failed to fetch consultation attributes';
    toaster.error(error.value);
    console.error("API Error:", err);
  } finally {
    isLoading.value = false;
  }
};

const selectPlaceholder = (placeholder) => {
  selectedPlaceholder.value = placeholder;
};


const getInputTypeIcon = (inputType) => {
  const icons = {
    0: 'fas fa-align-left',
    1: 'fas fa-font',
    2: 'fas fa-hashtag',
    3: 'fas fa-calendar-alt',
    4: 'fas fa-envelope',
    5: 'fas fa-phone'
  };
  return icons[inputType] || 'fas fa-font';
};

onMounted(() => {
  fetchAllConsultationAttributes();
});

watch(() => props.appointmentId, (newId, oldId) => {
  if (newId && newId !== oldId) {
    fetchAllConsultationAttributes();
  }
}, { immediate: true });
</script>

<template>
  <div class="master-detail-layout">
    <aside class="placeholder-list-panel">
      <h3 class="panel-title">Consultation Types</h3>
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
      </div>
      <div v-else-if="error" class="error-state">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
      </div>
      <div v-else-if="!placeholders.length" class="empty-state">
        <span>No consultation types found.</span>
      </div>
      <ul v-else class="placeholder-list">
        <li
          v-for="placeholder in placeholders"
          :key="placeholder.id"
          class="list-item"
          :class="{ 'is-active': selectedPlaceholder?.id === placeholder.id }"
          @click="selectPlaceholder(placeholder)"
        >
          <i class="fas fa-file-alt list-item-icon"></i>
          <span>{{ placeholder.name }}</span>
          <i class="fas fa-chevron-right list-item-arrow"></i>
        </li>
      </ul>
    </aside>

    <main class="attribute-detail-panel">
      <div v-if="!selectedPlaceholder" class="initial-state">
        <i class="fas fa-hand-pointer fa-3x"></i>
        <h3>Select a consultation type</h3>
        <p>Choose an item from the left panel to view its details.</p>
      </div>

      <div v-else>
        <header class="detail-header">
          <h2>{{ selectedPlaceholder.name }}</h2>
          <p>Details for this consultation type.</p>
        </header>
        
        <div class="detail-body">
          <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <span>Loading Details...</span>
          </div>
          <div v-else-if="detailedAttributes[selectedPlaceholder.name]?.length" class="attributes-container">
            <div 
              v-for="attr in detailedAttributes[selectedPlaceholder.name]" 
              :key="attr.id" 
              class="attribute-card"
              :class="{ 'is-long-text': attr.input_type === 0 }"
            >
              <div class="attribute-header">
                <div class="attribute-title">
                  <i :class="getInputTypeIcon(attr.input_type)" class="attribute-icon"></i>
                  <h4>{{ attr.attribute_name }}</h4>
                </div>
              </div>
              
              <div class="attribute-content">
                <div v-if="attr.attribute_value" class="attribute-value">
                  <p v-if="attr.input_type === 0" class="long-text">{{ attr.attribute_value }}</p>
                  <span v-else class="short-text">{{ attr.attribute_value }}</span>
                </div>
                <div v-else class="no-value">
                  <i class="fas fa-minus-circle no-value-icon"></i>
                  <span>No {{ attr.attribute_name.toLowerCase() }} provided</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="no-attributes-state">
            <i class="fas fa-info-circle"></i>
            <span>No attributes found for this consultation type.</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Main Layout */
.master-detail-layout {
  display: flex;
  height: 70vh;
  background-color: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}

/* Left Panel: List */
.placeholder-list-panel {
  width: 320px;
  flex-shrink: 0;
  background-color: #ffffff;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
}

.panel-title {
  padding: 1.25rem 1.5rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.placeholder-list {
  list-style: none;
  padding: 0.5rem;
  margin: 0;
  overflow-y: auto;
  flex-grow: 1;
}

.list-item {
  display: flex;
  align-items: center;
  padding: 0.85rem 1rem;
  margin-bottom: 0.25rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s;
  font-weight: 500;
  color: #374151;
}

.list-item-icon {
  margin-right: 0.75rem;
  color: #9ca3af;
}

.list-item-arrow {
  margin-left: auto;
  color: #d1d5db;
  transition: transform 0.2s;
}

.list-item:hover {
  background-color: #f9fafb;
}

.list-item.is-active {
  background-color: #eef2ff;
  color: #4338ca;
  font-weight: 600;
}

.list-item.is-active .list-item-icon {
  color: #6366f1;
}

.list-item.is-active .list-item-arrow {
  transform: translateX(3px);
  color: #6366f1;
}

/* Right Panel: Details */
.attribute-detail-panel {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  background-color: #f9fafb;
}

.initial-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  height: 100%;
  color: #6b7280;
  padding: 2rem;
}

.initial-state i {
  color: #9ca3af;
  margin-bottom: 1rem;
}

.detail-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  background-color: #ffffff;
}

.detail-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.detail-header p {
  margin: 0;
  color: #6b7280;
}

.detail-body {
  padding: 2rem;
}

/* New Card-based Attribute Styles */
.attributes-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.attribute-card {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: box-shadow 0.2s, border-color 0.2s;
}

.attribute-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border-color: #d1d5db;
}

.attribute-card.is-long-text {
  grid-column: 1 / -1;
}

.attribute-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.attribute-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.attribute-icon {
  color: #6366f1;
  font-size: 1.1rem;
}

.attribute-title h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
}

.attribute-type-badge {
  background-color: #f3f4f6;
  color: #6b7280;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.attribute-content {
  margin-top: 1rem;
}

.attribute-value .long-text {
  color: #374151;
  line-height: 1.6;
  margin: 0;
  white-space: pre-wrap;
  background-color: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
}

.attribute-value .short-text {
  color: #1f2937;
  font-weight: 500;
  font-size: 1rem;
  display: block;
  padding: 0.75rem 1rem;
  background-color: #f8fafc;
  border-radius: 8px;
  /* border-left: 4px solid #10b981; */
}

.no-value {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #9ca3af;
  font-style: italic;
  padding: 0.75rem 1rem;
  background-color: #f9fafb;
  border-radius: 8px;
  border: 1px dashed #d1d5db;
}

.no-value-icon {
  font-size: 0.875rem;
}

.no-attributes-state {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #6b7280;
  background-color: #f3f4f6;
  padding: 1.5rem;
  border-radius: 8px;
  justify-content: center;
  border: 1px dashed #d1d5db;
}

/* Generic States & Spinner */
.loading-state, .error-state, .empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6b7280;
  flex-grow: 1;
}

.spinner {
  border: 4px solid #e5e7eb;
  border-top: 4px solid #4f46e5;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  animation: spin 1s linear infinite;
  margin-right: 0.75rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 900px) {
  .master-detail-layout {
    flex-direction: column;
    height: auto;
  }
  
  .placeholder-list-panel {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    height: 40vh;
  }
  
  .attributes-container {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .detail-body {
    padding: 1rem;
  }
  
  .attribute-card {
    padding: 1rem;
  }
  
  .attribute-header {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }
}
</style>