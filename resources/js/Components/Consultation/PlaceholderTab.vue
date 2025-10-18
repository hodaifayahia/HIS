<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'; // Add onBeforeUnmount
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { defineProps, defineEmits, defineExpose } from 'vue';
import { debounce } from 'lodash';

const toaster = useToastr();

// Props
const props = defineProps({
  consultationData: {
    type: Object,
    default: () => ({})
  },
  appointmentId: {
    type: Number,
    required: true
  },
  doctorId: {
    type: Number,
    required: false
  },
  patientId: {
    type: Number,
    required: false
  }
});

// Emits
const emit = defineEmits(['update:consultation-data']);

// State
const placeholders = ref([]);
const attributes = ref({});
const isLoadingList = ref(false);
const error = ref(null);
const selectedPlaceholder = ref(null);
const isSaving = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const lastPage = ref(1);

// New state for patient data
const patientInfo = ref(null);
const isLoadingPatientInfo = ref(false);
const patientInfoError = ref(null);
const attributeSuggestions = ref({}); // { [attributeId]: [suggestions] }
const attributeLoading = ref({});
const allSuggestions = ref({}); // Store all suggestions for frontend filtering
const suggestionSearchQueries = ref({}); // Track search queries for each attribute
const activeSuggestionDropdown = ref(null); // Track which dropdown is active

// Define patientFieldsMapping
const patientFieldsMapping = {
  'FullName': 'full_name',
  'DateOfBirth': 'date_of_birth',
  'Gender': 'gender',
  'PhoneNumber': 'phone_number',
  'Address': 'address',
  'Email': 'email',
  'BloodGroup': 'blood_group',
  'EmergencyContactName': 'emergency_contact_name',
  'EmergencyContactNumber': 'emergency_contact_number',
  'PastMedicalHistory': 'past_medical_history',
  'CurrentMedications': 'current_medications',
  'Allergies': 'allergies',
  'FamilyMedicalHistory': 'family_medical_history'
  // Add other mappings as needed
};


// Enhanced suggestion fetching with frontend filtering
const fetchAttributeSuggestions = debounce(async (attributeId, query) => {
  if (!attributeId) return;

  // If we don't have all suggestions for this attribute yet, fetch them
  if (!allSuggestions.value[attributeId]) {
    attributeLoading.value[attributeId] = true;
    try {
      const response = await axios.get('/api/placeholders/consultation-attributes/search-values', {
        params: {
          attribute_id: attributeId,
          query: '', // Fetch all suggestions
          limit: 1000 // Get a large number of suggestions
        }
      });
      allSuggestions.value[attributeId] = response.data.data || [];
    } catch (e) {
      allSuggestions.value[attributeId] = [];
    } finally {
      attributeLoading.value[attributeId] = false;
    }
  }

  // Filter suggestions based on the query
  filterSuggestions(attributeId, query);
}, 300);

const filterSuggestions = (attributeId, query) => {
  const allSuggestionsList = allSuggestions.value[attributeId] || [];

  if (!query || query.trim() === '') {
    attributeSuggestions.value[attributeId] = allSuggestionsList;
  } else {
    const filtered = allSuggestionsList.filter(suggestion =>
      suggestion.toLowerCase().includes(query.toLowerCase())
    );
    attributeSuggestions.value[attributeId] = filtered;
  }
};

const getSavedConsultationAttributes = async () => {
  if (!props.appointmentId) return;
  
  try {
    console.log('Fetching saved consultation attributes for appointment:', props.appointmentId);
    const response = await axios.get(`/api/placeholders/consultation/${props.appointmentId}/attributes`);
    
    if (response.data.success && response.data.data) {
      const savedAttributes = response.data.data;
      console.log('Fetched saved attributes:', savedAttributes);
      
      // Convert the grouped attributes back to the consultation data format
      const restoredConsultationData = { ...props.consultationData };
      
      Object.keys(savedAttributes).forEach(placeholderName => {
        const attributesList = savedAttributes[placeholderName];
        
        attributesList.forEach(attr => {
          // Always use dot notation format
          const key = `${placeholderName}.${attr.attribute_name}`;
          restoredConsultationData[key] = attr.attribute_value;
          console.log(`Restored: ${key} = ${attr.attribute_value}`);
        });
      });
      
      // Find which placeholder was last selected based on the data
      const placeholderWithData = Object.keys(savedAttributes)[0];
      if (placeholderWithData && placeholders.value.length > 0) {
        const placeholder = placeholders.value.find(p => p.name === placeholderWithData);
        if (placeholder) {
          restoredConsultationData._selectedPlaceholderId = placeholder.id;
          console.log('Auto-selecting placeholder based on saved data:', placeholder.name);
        }
      }
      
      // Emit the restored data
      emit('update:consultation-data', restoredConsultationData);
      
      toaster.success('Previous session data restored successfully');
      return true;
    }
  } catch (error) {
    console.error('Error fetching saved consultation attributes:', error);
    // Only show error if it's not a 404 (no saved data)
    if (error.response?.status !== 404) {
      toaster.error('Failed to restore previous session data');
    }
    return false;
  }
};

// Enhanced function to check if consultation data is empty
const isConsultationDataEmpty = () => {
  const data = props.consultationData;
  const keys = Object.keys(data).filter(key => key !== '_selectedPlaceholderId');
  return keys.length === 0 || keys.every(key => !data[key] || data[key].trim() === '');
};

// Updated functions to only use dot notation
const onTextareaInput = (attribute, value) => {
  updateConsultationData(selectedPlaceholder.value.name + '.' + attribute.name, value);
  suggestionSearchQueries.value[attribute.id] = value;
  filterSuggestions(attribute.id, value);
};

const selectTextareaSuggestion = (attribute, suggestion) => {
  updateConsultationData(selectedPlaceholder.value.name + '.' + attribute.name, suggestion);
  suggestionSearchQueries.value[attribute.id] = suggestion;
  hideSuggestions(attribute.id);
};

const appendTextareaSuggestion = (attribute, suggestion) => {
  const currentValue = props.consultationData[selectedPlaceholder.value.name + '.' + attribute.name] || '';
  const separator = currentValue.trim() ? '\n' : '';
  const newValue = currentValue + separator + suggestion;
  updateConsultationData(selectedPlaceholder.value.name + '.' + attribute.name, newValue);
  suggestionSearchQueries.value[attribute.id] = newValue;
};

const selectSuggestion = (attribute, suggestion) => {
  updateConsultationData(selectedPlaceholder.value.name + '.' + attribute.name, suggestion);
  suggestionSearchQueries.value[attribute.id] = suggestion;
  hideSuggestions(attribute.id);
};

const showSuggestions = async (attribute) => {
  activeSuggestionDropdown.value = attribute.id;
  const currentValue = props.consultationData[selectedPlaceholder.value.name + '.' + attribute.name] || '';
  await fetchAttributeSuggestions(attribute.id, currentValue);
};

const hideSuggestions = (attributeId) => {
  if (activeSuggestionDropdown.value === attributeId) {
    activeSuggestionDropdown.value = null;
  }
};

const clearSuggestionSearch = (attributeId) => {
  suggestionSearchQueries.value[attributeId] = '';
  filterSuggestions(attributeId, '');
};

// Computed property to filter placeholders that have attributes
const filteredPlaceholders = computed(() => {
  return placeholders.value.filter(placeholder => {
    const placeholderAttributes = attributes.value[placeholder.id]?.data;
    return attributes.value[placeholder.id]?.loading ||
           (placeholderAttributes && placeholderAttributes.length > 0);
  });
});

console.log(props.doctorId);

/**
 * Fetches patient details from the API based on patientId.
 */
const getPatientInfo = async (patientId) => {
  if (!patientId) {
    patientInfo.value = null;
    return;
  }

  isLoadingPatientInfo.value = true;
  patientInfoError.value = null;
  try {
    const response = await axios.get(`/api/patients/${patientId}`);
    patientInfo.value = response.data || response.data.data;
    console.log('Fetched Patient Info:', patientInfo.value);
  } catch (err) {
    patientInfoError.value = err.response?.data?.message || 'Failed to load patient information';
    toaster.error(patientInfoError.value);
    patientInfo.value = null;
  } finally {
    isLoadingPatientInfo.value = false;
  }
};

// Enhanced getPlaceholders function with session recovery
const getPlaceholders = async (page = 1) => {
  try {
    isLoadingList.value = true;
    error.value = null;

    const params = {
      page: page,
      doctor_id: props.doctorId
    };

    if (searchQuery.value.trim()) {
      params.search = searchQuery.value.trim();
    }

    const response = await axios.get(`/api/placeholders`, { params });
    console.log('Full response:', response.data);

    if (page === 1) {
      placeholders.value = response.data.data || response.data;
    } else {
      const newData = response.data.data || response.data;
      placeholders.value = [...placeholders.value, ...newData];
    }

    if (response.data.current_page !== undefined) {
      currentPage.value = response.data.current_page;
      lastPage.value = response.data.last_page;
    }

    // After fetching placeholders, fetch attributes for each to determine visibility
    await Promise.all(placeholders.value.map(placeholder => getAttributes(placeholder.id)));

    // Check if consultation data is empty and try to restore from session
    if (isConsultationDataEmpty()) {
      console.log('Consultation data is empty, attempting to restore from session...');
      const restored = await getSavedConsultationAttributes();
      
      // Wait a bit for the data to be updated, then restore placeholder selection
      if (restored) {
        setTimeout(() => {
          restoreSelectedPlaceholder();
        }, 100);
      }
    } else {
      // Restore selected placeholder if it exists in consultation data
      restoreSelectedPlaceholder();
    }
    
  } catch (err) {
    console.error('Error fetching placeholders:', err);
    error.value = err.response?.data?.message || 'Failed to load sections';
    toaster.error(error.value);
  } finally {
    isLoadingList.value = false;
  }
};

// Add a manual restore function that can be called by parent component
const restoreSessionData = async () => {
  console.log('Manually restoring session data...');
  const restored = await getSavedConsultationAttributes();
  if (restored) {
    setTimeout(() => {
      restoreSelectedPlaceholder();
    }, 100);
  }
  return restored;
};

const getAttributes = async (placeholderId) => {
  if (!placeholderId || attributes.value[placeholderId]?.data) return;

  attributes.value[placeholderId] = { loading: true, data: null };
  try {
    const response = await axios.get(`/api/attributes/${placeholderId}`);
    attributes.value[placeholderId].data = response.data.data || response.data || [];
  } catch (err) {
    toaster.error(err.response?.data?.message || `Failed to load attributes for placeholder ${placeholderId}`);
    attributes.value[placeholderId].data = [];
  } finally {
    if (attributes.value[placeholderId]) {
      attributes.value[placeholderId].loading = false;
    }
  }
};

/**
 * Restore the selected placeholder from consultation data
 */
const restoreSelectedPlaceholder = () => {
  // Check if there's a previously selected placeholder stored in consultation data
  const storedPlaceholderId = props.consultationData._selectedPlaceholderId;
  if (storedPlaceholderId && placeholders.value.length > 0) {
    const placeholder = placeholders.value.find(p => p.id === storedPlaceholderId);
    if (placeholder) {
      selectedPlaceholder.value = placeholder;
      console.log('Restored selected placeholder:', placeholder.name);
    }
  }
};

/**
 * Selects a placeholder and stores the selection in consultation data
 */
const selectPlaceholder = async (placeholder) => {
  if (selectedPlaceholder.value?.id !== placeholder.id) {
    selectedPlaceholder.value = placeholder;

    // Store the selected placeholder ID in consultation data for persistence
    const updatedData = { ...props.consultationData };
    updatedData._selectedPlaceholderId = placeholder.id;

    await getAttributes(placeholder.id);

    // Auto-fill patient info logic
    if (placeholder.name === 'PatientInfo' && patientInfo.value) {
      console.log('Selected PatientInfo placeholder. Auto-filling patient data.');

      const currentPlaceholderAttributes = attributes.value[placeholder.id]?.data || [];

      for (const attribute of currentPlaceholderAttributes) {
        const patientDataKey = patientFieldsMapping[attribute.name];

        if (patientDataKey && patientInfo.value[patientDataKey] !== undefined) {
          // Always use dot notation format
          const consultationDataKey = `${placeholder.name}.${attribute.name}`;
          updatedData[consultationDataKey] = patientInfo.value[patientDataKey];
          console.log(`Auto-filled: ${consultationDataKey} = ${patientInfo.value[patientDataKey]}`);
        }
      }
    }

    emit('update:consultation-data', updatedData);
  }
};

// Enhanced saveAttributes function with better error handling
const saveAttributes = async () => {
  try {
    if (isSaving.value) return; // Prevent multiple saves
    isSaving.value = true;

    const attributesData = JSON.parse(JSON.stringify(props.consultationData));

    // Remove internal tracking data before saving
    delete attributesData._selectedPlaceholderId;

    // Check if there's any data to save
    if (Object.keys(attributesData).length === 0) {
      toaster.info('No data to save');
      return true;
    }

    console.log('Sending data:', {
      appointment_id: props.appointmentId,
      attributes: attributesData
    });

    const response = await axios.post('/api/placeholders/consultation-attributes/save', {
      appointment_id: props.appointmentId,
      attributes: attributesData,
      doctor_id: props.doctorId,
    }, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    if (response.data.success) {
      toaster.success('Attributes saved successfully');
    } else {
      throw new Error(response.data.message || 'Failed to save attributes.');
    }
    return true; // Indicate success
  } catch (error) {
    console.error('Save Error:', error);
    if (error.response?.status === 401) {
      toaster.error('Session expired. Please login again.');
    } else {
      toaster.error('Failed to save attributes');
    }
    return false; // Indicate failure
  } finally {
    isSaving.value = false;
  }
};

const startAutoSave = () => {
  // Auto-save every 30 seconds if there's data
  autoSaveInterval.value = setInterval(() => {
    if (!isConsultationDataEmpty() && !isSaving.value) {
      console.log('Auto-saving consultation data...');
      saveAttributes();
    }
  }, 30000); // 30 seconds
};

const stopAutoSave = () => {
  if (autoSaveInterval.value) {
    clearInterval(autoSaveInterval.value);
    autoSaveInterval.value = null;
  }
};

// Add a periodic auto-save function
const autoSaveInterval = ref(null);

defineExpose({
  saveAttributes,
  restoreSessionData,
  getSavedConsultationAttributes
});

const updateConsultationData = (key, value) => {
  const updatedData = { ...props.consultationData };
  updatedData[key] = value;
  console.log('Updating consultation data:', key, value, updatedData);
  emit('update:consultation-data', updatedData);
};

// Watch for changes in consultation data to restore state
watch(() => props.consultationData, (newData) => {
  if (newData && Object.keys(newData).length > 0 && placeholders.value.length > 0) {
    restoreSelectedPlaceholder();
  }
}, { deep: true });

// Hide suggestions when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.suggestion-dropdown')) {
    activeSuggestionDropdown.value = null;
  }
};

const onAttributeInput = (attribute, value) => {
  updateConsultationData(selectedPlaceholder.value.name + '.' + attribute.name, value);
  suggestionSearchQueries.value[attribute.id] = value;
  filterSuggestions(attribute.id, value);
};

/**
 * Handle beforeunload event to save data before the page unloads.
 */
const handleBeforeUnload = async (event) => {
  // Only try to save if there's consultation data that might be unsaved
  if (!isConsultationDataEmpty()) {
    console.log('Attempting to autosave before unload...');
    try {
      await saveAttributes();
    } catch (error) {
      console.error('Failed to save before unload:', error);
    }
  }
};

// Enhanced onMounted
onMounted(() => {
  getPlaceholders();
  getSavedConsultationAttributes();
  if (props.patientId) {
    getPatientInfo(props.patientId);
  }
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('beforeunload', handleBeforeUnload);
  
  // Start auto-save
  startAutoSave();
});

// Enhanced onBeforeUnmount
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
  document.removeEventListener('click', handleClickOutside);
  
  // Stop auto-save
  stopAutoSave();
});

watch(() => props.patientId, (newPatientId) => {
  getPatientInfo(newPatientId);
}, { immediate: true });
</script>

<template>
  <div class="master-detail-layout">
    <aside class="placeholder-list-panel">
      <h3 class="panel-title">Folders</h3>
      <div v-if="isLoadingList" class="loading-state">
        <div class="spinner"></div>
      </div>
      <div v-else-if="error" class="error-state">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
      </div>
      <div v-else-if="!filteredPlaceholders.length" class="empty-state">
        <i class="fas fa-puzzle-piece fa-2x text-muted mb-2"></i>
        <span>No placeholders with attributes found.</span>
      </div>
      <ul v-else class="placeholder-list">
        <li
          v-for="placeholder in filteredPlaceholders"
          :key="placeholder.id"
          class="list-item"
          :class="{ 'is-active': selectedPlaceholder?.id === placeholder.id }"
          @click="selectPlaceholder(placeholder)"
        >
          <i class="fas fa-file-alt list-item-icon"></i>
          <span>{{ placeholder.name || `Placeholder ${placeholder.id}` }}</span>
          <i class="fas fa-chevron-right list-item-arrow"></i>
        </li>
      </ul>
    </aside>

    <main class="attribute-detail-panel">
      <div v-if="!selectedPlaceholder" class="initial-state">
        <i class="fas fa-hand-pointer fa-3x"></i>
        <h3>Select a consultation type</h3>
        <p>Choose an item from the left panel to view and edit its attributes.</p>
      </div>

      <template v-else>
        <div class="detail-content-wrapper">
          <header class="detail-header">
            <h2>{{ selectedPlaceholder.name }}</h2>
            <p>Fill out the attributes below for this consultation.</p>
          </header>

          <div class="detail-body">
            <div v-if="attributes[selectedPlaceholder.id]?.loading" class="loading-state">
              <div class="spinner"></div>
              <span>Loading Attributes...</span>
            </div>

            <div v-else-if="attributes[selectedPlaceholder.id]?.data?.length" class="attributes-grid">
              <!-- Regular input fields (input_type !== 0) -->
              <div v-for="attribute in attributes[selectedPlaceholder.id].data.filter(a => a.input_type !== 0)" :key="attribute.id" class="form-group suggestion-dropdown">
                <label class="form-label">{{ attribute.name }}</label>
                <div class="input-wrapper">
                  <input
                    class="form-input"
                    :value="consultationData[selectedPlaceholder.name + '.' + attribute.name] || ''"
                    @input="onAttributeInput(attribute, $event.target.value)"
                    @focus="showSuggestions(attribute)"
                    :placeholder="`Enter ${attribute.name} value`"
                    autocomplete="off"
                  >
                  <i v-if="attributeLoading[attribute.id]" class="fas fa-spinner fa-spin input-icon"></i>
                  <i v-else-if="attributeSuggestions[attribute.id]?.length > 0" class="fas fa-chevron-down input-icon"></i>
                </div>

                <div v-if="activeSuggestionDropdown === attribute.id && (attributeSuggestions[attribute.id]?.length > 0 || attributeLoading[attribute.id])" class="suggestions-dropdown">
                  <div class="suggestions-header">
                    <div class="suggestions-search">
                      <i class="fas fa-search"></i>
                      <input
                        v-model="suggestionSearchQueries[attribute.id]"
                        @input="filterSuggestions(attribute.id, suggestionSearchQueries[attribute.id])"
                        placeholder="Search suggestions..."
                        class="search-input"
                      >
                      <button v-if="suggestionSearchQueries[attribute.id]" @click="clearSuggestionSearch(attribute.id)" class="clear-search">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                    <div class="suggestions-count">
                      {{ attributeSuggestions[attribute.id]?.length || 0 }} items
                    </div>
                  </div>

                  <div class="suggestions-body">
                    <div v-if="attributeLoading[attribute.id]" class="loading-suggestions">
                      <div class="spinner-small"></div>
                      <span>Loading suggestions...</span>
                    </div>
                    <div v-else-if="attributeSuggestions[attribute.id]?.length === 0" class="no-suggestions">
                      <i class="fas fa-search"></i>
                      <span>No suggestions found</span>
                    </div>
                    <ul v-else class="suggestions-list">
                      <li
                        v-for="(suggestion, index) in attributeSuggestions[attribute.id]"
                        :key="index"
                        @click="selectSuggestion(attribute, suggestion)"
                        class="suggestion-item"
                        :title="suggestion"
                      >
                        <i class="fas fa-plus-circle suggestion-icon"></i>
                        <span class="suggestion-text">{{ suggestion }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Textarea fields (input_type === 0) -->
              <div v-for="attribute in attributes[selectedPlaceholder.id].data.filter(a => a.input_type === 0)" :key="attribute.id" class="form-group is-full-width suggestion-dropdown">
                <label class="form-label">{{ attribute.name }}</label>
                <div class="textarea-wrapper">
                  <textarea
                    class="form-textarea"
                    :value="consultationData[selectedPlaceholder.name + '.' + attribute.name] || ''"
                    @input="onTextareaInput(attribute, $event.target.value)"
                    @focus="showSuggestions(attribute)"
                    :placeholder="`Enter ${attribute.name} value`"
                    rows="5"
                  ></textarea>
                  <div class="textarea-icons">
                    <i v-if="attributeLoading[attribute.id]" class="fas fa-spinner fa-spin textarea-icon"></i>
                    <i v-else-if="attributeSuggestions[attribute.id]?.length > 0" class="fas fa-chevron-down textarea-icon"></i>
                  </div>
                </div>

                <div v-if="activeSuggestionDropdown === attribute.id && (attributeSuggestions[attribute.id]?.length > 0 || attributeLoading[attribute.id])" class="suggestions-dropdown textarea-suggestions">
                  <div class="suggestions-header">
                    <div class="suggestions-search">
                      <i class="fas fa-search"></i>
                      <input
                        v-model="suggestionSearchQueries[attribute.id]"
                        @input="filterSuggestions(attribute.id, suggestionSearchQueries[attribute.id])"
                        placeholder="Search suggestions..."
                        class="search-input"
                      >
                      <button v-if="suggestionSearchQueries[attribute.id]" @click="clearSuggestionSearch(attribute.id)" class="clear-search">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                    <div class="suggestions-count">
                      {{ attributeSuggestions[attribute.id]?.length || 0 }} items
                    </div>
                  </div>

                  <div class="suggestions-body">
                    <div v-if="attributeLoading[attribute.id]" class="loading-suggestions">
                      <div class="spinner-small"></div>
                      <span>Loading suggestions...</span>
                    </div>
                    <div v-else-if="attributeSuggestions[attribute.id]?.length === 0" class="no-suggestions">
                      <i class="fas fa-search"></i>
                      <span>No suggestions found</span>
                    </div>
                    <ul v-else class="suggestions-list">
                      <li
                        v-for="(suggestion, index) in attributeSuggestions[attribute.id]"
                        :key="index"
                        @click="selectTextareaSuggestion(attribute, suggestion)"
                        class="suggestion-item textarea-suggestion-item"
                        :title="suggestion"
                      >
                        <i class="fas fa-plus-circle suggestion-icon"></i>
                        <span class="suggestion-text">{{ suggestion }}</span>
                        <button @click.stop="appendTextareaSuggestion(attribute, suggestion)" class="append-button" title="Append to current text">
                          <i class="fas fa-plus"></i>
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="no-attributes-state">
              <i class="fas fa-info-circle"></i>
              <span>No attributes found for this placeholder.</span>
            </div>
          </div>
        </div>

        <div class="actions-panel">
          <button @click="saveAttributes" :disabled="isSaving" class="save-button">
            <i :class="isSaving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </template>
    </main>
  </div>
</template>

<style scoped>
/* Main Layout */
.master-detail-layout {
  display: flex;
  height: 75vh;
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

.detail-content-wrapper {
  flex-grow: 1;
  overflow-y: auto;
}

.detail-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  background-color: #ffffff;
  flex-shrink: 0;
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

/* Enhanced Form Styles */
.attributes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  position: relative;
}

.form-group.is-full-width {
  grid-column: 1 / -1;
}

.form-label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.input-wrapper {
  position: relative;
}

.form-input, .form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  padding-right: 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background-color: #ffffff;
  font-size: 0.9rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.input-icon {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}

/* Enhanced Textarea Styles */
.textarea-wrapper {
  position: relative;
}

.textarea-icons {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  z-index: 1;
}

.textarea-icon {
  color: #9ca3af;
}

.form-textarea {
  resize: vertical;
  min-height: 120px;
}

/* Textarea-specific suggestions */
.textarea-suggestions {
  max-height: 350px;
}

.textarea-suggestion-item {
  position: relative;
  padding-right: 3rem;
}

.append-button {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  opacity: 0;
}

.textarea-suggestion-item:hover .append-button {
  opacity: 1;
}

.append-button:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

.append-button i {
  font-size: 0.75rem;
  color: #6b7280;
}

/* Enhanced Suggestions Dropdown */
.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  margin-top: 4px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  max-height: 400px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.suggestions-header {
  padding: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.suggestions-search {
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
}

.suggestions-search i {
  color: #9ca3af;
  margin-right: 0.5rem;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.875rem;
}

.clear-search {
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.clear-search:hover {
  color: #6b7280;
}

.suggestions-count {
  font-size: 0.75rem;
  color: #6b7280;
  text-align: center;
}

.suggestions-body {
  flex: 1;
  overflow-y: auto;
  max-height: 300px;
}

.loading-suggestions {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6b7280;
}

.spinner-small {
  border: 2px solid #e5e7eb;
  border-top: 2px solid #4f46e5;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
  margin-right: 0.5rem;
}

.no-suggestions {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #9ca3af;
}

.no-suggestions i {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.suggestions-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.suggestion-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f3f4f6;
}

.suggestion-item:hover {
  background-color: #f8fafc;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.suggestion-icon {
  color: #10b981;
  margin-right: 0.75rem;
  font-size: 0.875rem;
}

.suggestion-text {
  flex: 1;
  font-size: 0.875rem;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Generic States & Spinner */
.loading-state, .error-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #6b7280;
  flex-grow: 1;
  text-align: center;
}

.spinner {
  border: 4px solid #e5e7eb;
  border-top: 4px solid #4f46e5;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Actions Panel */
.actions-panel {
  padding: 1rem 2rem;
  background-color: #ffffff;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  flex-shrink: 0;
}

.save-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.save-button:hover {
  background-color: #4338ca;
}

.save-button:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.save-button i {
  font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 900px) {
  .master-detail-layout {
    flex-direction: column;
    height: auto;
    max-height: 85vh;
  }

  .placeholder-list-panel {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    height: auto;
    max-height: 40vh;
    flex-shrink: 1;
  }

  .attribute-detail-panel {
    flex-grow: 1;
  }

  .suggestions-dropdown {
    max-height: 250px;
  }
}
</style>