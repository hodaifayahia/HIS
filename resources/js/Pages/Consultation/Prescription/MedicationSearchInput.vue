<script setup>
import { ref, watch, defineProps, defineEmits } from 'vue';
import axios from 'axios';
import MedicationModel from './MedicationModel.vue'; // Import the modal

const props = defineProps({
  modelValue: { // The current value of the input field (for v-model)
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Rechercher un médicament...' // French placeholder
  },
  searchType: { // 'active_substance', 'brand_name', or 'full_text' (for unified search)
    type: String,
    default: 'full_text' // Default to a general search
  },
  debounceTime: {
    type: Number,
    default: 300 // ms
  },
  minLength: {
    type: Number,
    default: 0 // Changed to 0 to allow showing all medications on focus
  },
  doctorId: {
    type: [Number, String], // Can be number or string, depending on your ID format
    required: true // Making it required ensures it's always provided by the parent
  },
  id: { // Add an id prop for accessibility/labels
    type: String,
    required: true // Making it required ensures it's always provided by the parent
  }
});

const emit = defineEmits(['update:modelValue', 'select']); // 'select' will emit the full object

const searchTerm = ref(props.modelValue);
const suggestions = ref([]);
const allMedications = ref([]); // Store all medications for filtering
const showSuggestions = ref(false);
const searchTimeout = ref(null);
const isLoading = ref(false); // Add a loading state
const showAllModal = ref(false); // Add this

// Watch for external changes to modelValue (e.g., when editing a medication or clearing externally)
watch(() => props.modelValue, (newVal) => {
  if (newVal !== searchTerm.value) { // Prevent unnecessary updates if already same
    searchTerm.value = newVal;
    // If modelValue becomes empty, show all medications if focused
    if (!newVal) {
      if (showSuggestions.value) {
        suggestions.value = allMedications.value;
      }
    } else {
      // If external update has value, filter the medications
      filterMedications();
    }
  }
});
const onMedicationSelectedFromModal = (medication) => {
  if (medication) {
    // Find the displayText for this medication in allMedications
    const found = allMedications.value.find(m => m.fullObject.id === medication.id);
    if (found) {
      searchTerm.value = found.displayText;
      emit('update:modelValue', found.displayText);
      emit('select', found.fullObject);
    }
  }
};

// Basic debounce function
const debounce = (func, delay) => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  searchTimeout.value = setTimeout(() => {
    func();
    searchTimeout.value = null;
  }, delay);
};

const fetchAllMedications = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/api/favorate', {
      params: {
        search: '', // Empty search to get all medications
        doctor_id: props.doctorId,
        search_type: props.searchType
      }
    });

    const medications = response.data.data || response.data;
    allMedications.value = medications.map(med => ({
      fullObject: med,
      displayText: `${med.designation || med.nom_commercial || 'N/A'} ${med.nom_commercial && med.designation ? `(${med.nom_commercial})` : ''} - ${med.forme || med.type_medicament || 'N/A'}`
    }));

    // Sort medications alphabetically by display text
    allMedications.value.sort((a, b) => a.displayText.localeCompare(b.displayText, 'fr', { sensitivity: 'base' }));
    
    return allMedications.value;
  } catch (error) {
    console.error(`Erreur lors de la récupération des médicaments:`, error);
    allMedications.value = [];
    return [];
  } finally {
    isLoading.value = false;
  }
};

const filterMedications = () => {
  const query = searchTerm.value.trim().toLowerCase();
  
  if (!query) {
    // If no search term, show all medications
    suggestions.value = allMedications.value;
  } else {
    // Filter medications that start with the search term
    suggestions.value = allMedications.value.filter(med => 
      med.displayText.toLowerCase().startsWith(query)
    );
  }
  
  showSuggestions.value = true;
};

const performSearch = () => {
  // If we already have all medications loaded, just filter them
  if (allMedications.value.length > 0) {
    filterMedications();
    return;
  }

  // Otherwise, fetch all medications first
  debounce(async () => {
    await fetchAllMedications();
    filterMedications();
  }, props.debounceTime);
};
const refreshMedications = async () => {
  // Fetch all medications again to refresh the list
  await fetchAllMedications();
  // If the input is focused, show suggestions
  if (showSuggestions.value) {
    filterMedications();
  }
};

const selectSuggestion = (suggestionItem) => {
  searchTerm.value = suggestionItem.displayText;
  emit('update:modelValue', suggestionItem.displayText); // Update v-model with the display text
  emit('select', suggestionItem.fullObject); // Emit the full medication object
  suggestions.value = []; // Clear suggestions
  showSuggestions.value = false; // Hide suggestions
};

const handleInput = () => {
  emit('update:modelValue', searchTerm.value); // Update v-model immediately on input
  
  // Always perform search/filter on input
  performSearch();
};

const handleFocus = async () => {
  // On focus, load all medications if not already loaded
  if (allMedications.value.length === 0) {
    await fetchAllMedications();
  }
  
  // Show appropriate suggestions based on current search term
  filterMedications();
};

const handleBlur = () => {
  // Delay hiding suggestions to allow for a click on a suggestion item.
  setTimeout(() => {
    showSuggestions.value = false;
  }, 200); // Increased delay slightly for better UX
};

const clearInput = () => {
  searchTerm.value = '';
  suggestions.value = [];
  showSuggestions.value = false;
  isLoading.value = false; // Ensure loading state is reset
  if (searchTimeout.value) { // Clear any pending debounced search
    clearTimeout(searchTimeout.value);
    searchTimeout.value = null;
  }
  emit('update:modelValue', ''); // Clear the v-model in the parent
  emit('select', null); // Notify parent that selection is cleared
};
</script>

<template>
  <div class="position-relative">
    <input
      :id="id"
      type="text"
      class="form-control"
      :placeholder="placeholder"
      v-model="searchTerm"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      autocomplete="off"
    />
    
    <button v-if="searchTerm && !isLoading" class="clear-input-btn" @click="clearInput" aria-label="Clear search input">
      <i class="fas fa-times-circle"></i>
    </button>

    <div v-if="isLoading" class="spinner-border spinner-border-sm text-primary search-spinner" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    
    <ul v-if="showSuggestions && suggestions.length > 0" class="suggestions-list list-group">
      <li
        v-for="(suggestion, i) in suggestions"
        :key="i"
        class="list-group-item list-group-item-action"
        @mousedown.prevent="selectSuggestion(suggestion)"
      >
        {{ suggestion.displayText }}
      </li>
    </ul>

    <div v-else-if="showSuggestions && !isLoading && allMedications.length > 0 && suggestions.length === 0" class="list-group-item no-results">
      <button class="btn btn-outline-primary w-100" @mousedown.prevent="showAllModal = true">
        Afficher tous les médicaments
      </button>
    </div>

    <MedicationModel
      v-model="showAllModal"
      @medication="refreshMedications"
      @medication-selected="onMedicationSelectedFromModal"
    />
  </div>
</template>

<style scoped>
/* (Your existing styles remain unchanged) */
.position-relative {
  position: relative;
}

.form-control {
  padding-right: 2.5rem; /* Space for clear button and spinner */
}

.clear-input-btn {
  position: absolute;
  right: 1.8rem; /* Adjust based on spinner or other icons */
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #ccc;
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
  z-index: 2;
  transition: color 0.2s ease; /* Smooth transition for hover */
}

.clear-input-btn:hover {
  color: #888;
}

.search-spinner {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
}

.suggestions-list {
  position: absolute;
  top: 100%; /* Position below the input */
  left: 0;
  right: 0;
  z-index: 1000; /* Ensure it appears above other elements */
  border: 1px solid #ced4da;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 200px;
  overflow-y: auto;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  background-color: white;
  list-style: none; /* Remove bullet points */
  padding: 0; /* Remove default padding */
  margin: 0; /* Remove default margin */
}

.suggestions-list .list-group-item {
  cursor: pointer;
  padding: 10px 15px;
  font-size: 0.9rem;
  transition: background-color 0.2s ease;
}

.suggestions-list .list-group-item:hover {
  background-color: #f8f9fa;
  color: #0056b3;
}

.suggestions-list .list-group-item:not(:last-child) {
  border-bottom: 1px solid #e9ecef;
}

.no-results { /* Applied directly to the div */
  padding: 10px 15px;
  font-size: 0.9rem;
  color: #6c757d;
  font-style: italic;
  cursor: default;
  background-color: #f8f9fa;
  border: 1px solid #ced4da;
  border-top: none;
  border-radius: 0 0 8px 8px;
}

/* Basic form-control styles for consistency, though ideally from a framework */
.form-control {
  height: calc(2.25rem + 2px);
  padding: .375rem .75rem;
  font-size: 1rem;
  line-height: 1.5;
  border: 1px solid #ced4da;
  border-radius: .25rem;
  transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.form-control:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 .25rem rgba(0,123,255,.25);
}
</style>