<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue';
import MedicationSearchInput from './MedicationSearchInput.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      medication_id: '',
      medication: null,
      form: '',
      num_times: '',
      frequency: '',
      period_intakes: '',
      frequency_period: '',
      timing_preference: '',
      description: '',
      // Add new fields for custom pill counts
      pills_matin: '',
      pills_apres_midi: '',
      pills_soir: ''
    }),
  },
  editingIndex: {
    type: [Number, null],
    default: null,
  },
  doctorId: {
    type: [Number],
    default: null,
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  isSaving: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'addMedication', 'clearForm', 'cancelEdit']);

const formData = ref(JSON.parse(JSON.stringify(props.modelValue)));

// Local state for custom timing input
const customTimingInput = ref('');

// Split timing_preference into when and meal for UI
const timingWhen = computed({
  get: () => formData.value.timing_preference.split('_')[0] || '',
  set: (value) => {
    const meal = formData.value.timing_preference.split('_')[1] || '';
    formData.value.timing_preference = value && meal ? `${value}_${meal}` : value || meal;
    emit('update:modelValue', JSON.parse(JSON.stringify(formData.value)));
  }
});

const predefinedTimingMeals = [
  "Matin",
  "Après-midi",
  "Soir",
  "Matin/aprèsmidi",
  "Matin/soir",
  "Après-midi/soir",
  "Matin/après-midi/soir",
  "Custom"
];

const timingMeal = computed({
  get: () => {
    const mealPart = formData.value.timing_preference.split('_')[1] || '';
    if (predefinedTimingMeals.includes(mealPart) || !mealPart) {
      return mealPart;
    } else {
      customTimingInput.value = mealPart;
      return "New";
    }
  },
  set: (value) => {
    const when = formData.value.timing_preference.split('_')[0] || '';
    let newMealValue = value;
    if (value === "Custom") {
      newMealValue = customTimingInput.value;
      // Clear pill counts if switching to Custom without a custom value
      if (!customTimingInput.value) {
        formData.value.pills_matin = '';
        formData.value.pills_apres_midi = '';
         formData.value.pills_midi = '';
        formData.value.pills_soir = '';
      }
    } else {
      // Clear custom input and pill counts if switching to a predefined option
      customTimingInput.value = '';
      formData.value.pills_matin = '';
      formData.value.pills_apres_midi = '';
      formData.value.pills_midi = '';
      formData.value.pills_soir = '';
    }
    formData.value.timing_preference = when && newMealValue ? `${when}_${newMealValue}` : newMealValue || when;
    emit('update:modelValue', JSON.parse(JSON.stringify(formData.value)));
  }
});

watch(
  () => props.modelValue,
  (newVal) => {
    if (JSON.stringify(formData.value) !== JSON.stringify(newVal)) {
      formData.value = JSON.parse(JSON.stringify(newVal));
      // Re-evaluate customTimingInput based on new modelValue
      const mealPart = formData.value.timing_preference.split('_')[1] || '';
      if (!predefinedTimingMeals.includes(mealPart)) {
        customTimingInput.value = mealPart;
      } else {
        customTimingInput.value = '';
      }
    }
  },
  { deep: true, immediate: true }
);

// Watch customTimingInput to update timing_preference when it changes and "Custom" is selected
watch(customTimingInput, (newVal) => {
  // Only update timing_preference if 'Custom' is the currently selected option in the dropdown
  // and the new value is different from what's already stored (to prevent infinite loops if newVal is '' and timing_preference has 'Custom_')
  const currentMealPart = formData.value.timing_preference.split('_')[1] || '';
  if (timingMeal.value === "Custom" && newVal !== currentMealPart) {
    const when = formData.value.timing_preference.split('_')[0] || '';
    formData.value.timing_preference = when && newVal ? `${when}_${newVal}` : newVal || when;
    emit('update:modelValue', JSON.parse(JSON.stringify(formData.value)));
  }
});
const updateFormData = (field, value) => {
  formData.value[field] = value;
  emit('update:modelValue', JSON.parse(JSON.stringify(formData.value)));
};

const handleMedicationSelect = (selectedMedication) => {
  if (selectedMedication) {
    formData.value = {
      ...formData.value,
      medication_id: selectedMedication.id,
      medication: selectedMedication,
      form: selectedMedication.forme || selectedMedication.type_medicament || '',
      num_times: formData.value.num_times,
      frequency: formData.value.frequency,
      period_intakes: formData.value.period_intakes,
      frequency_period: formData.value.frequency_period,
      timing_preference: formData.value.timing_preference,
      description: formData.value.description,
      pills_matin: formData.value.pills_matin, // Keep existing if any
      pills_apres_midi: formData.value.pills_apres_midi, // Keep existing if any
      pills_midi: formData.value.pills_midi, // Keep existing if any
      pills_soir: formData.value.pills_soir // Keep existing if any
    };
    emit('update:modelValue', JSON.parse(JSON.stringify(formData.value)));
  }
};

const validateForm = () => {
  const errors = [];

  if (!formData.value.medication_id) {
    errors.push('Please select a medication');
  }

  if (!formData.value.form || formData.value.form.trim() === '') {
    errors.push('Pharmaceutical form is required');
  }

  if (!formData.value.num_times || formData.value.num_times.trim() === '') {
    errors.push('Number of times is required');
  }

  if (!formData.value.frequency || formData.value.frequency.trim() === '') {
    errors.push('Frequency is required');
  }

  if (!formData.value.period_intakes || formData.value.period_intakes.trim() === '') {
    errors.push('Period intakes is required');
  }

  if (!formData.value.frequency_period || formData.value.frequency_period.trim() === '') {
    errors.push('Frequency period is required');
  }

  // If "Custom" timing is selected, validate pill counts
  if (timingMeal.value === 'Custom') {
    if (!customTimingInput.value.trim()) {
      errors.push('Custom time preference cannot be empty when "Custom" is selected.');
    }
    if (!formData.value.pills_matin && !formData.value.pills_apres_midi && !formData.value.pills_soir) {
      errors.push('At least one pill count (Matin, Après-midi, Soir) is required for custom timing.');
    }
    if (formData.value.pills_matin && (isNaN(formData.value.pills_matin) || formData.value.pills_matin < 0)) {
      errors.push('Pills for Matin must be a non-negative number.');
    }
    if (formData.value.pills_apres_midi && (isNaN(formData.value.pills_apres_midi) || formData.value.pills_apres_midi < 0)) {
      errors.push('Pills for Après-midi must be a non-negative number.');
    }
    if (formData.value.pills_soir && (isNaN(formData.value.pills_soir) || formData.value.pills_soir < 0)) {
      errors.push('Pills for Soir must be a non-negative number.');
    }
  }

  return errors;
};

const handleAddOrUpdate = () => {
  const validationErrors = validateForm();

  if (validationErrors.length > 0) {
    alert('Please fix the following errors:\n' + validationErrors.join('\n'));
    return;
  }

  const medicationData = {
    medication_id: formData.value.medication_id,
    medication: formData.value.medication,
    form: formData.value.form.trim(),
    num_times: formData.value.num_times.trim(),
    frequency: formData.value.frequency,
    period_intakes: formData.value.period_intakes.trim(),
    frequency_period: formData.value.frequency_period,
    timing_preference: formData.value.timing_preference,
    description: formData.value.description ? formData.value.description.trim() : '',
    pills_matin: formData.value.pills_matin ? parseInt(formData.value.pills_matin) : null,
    pills_apres_midi: formData.value.pills_apres_midi ? parseInt(formData.value.pills_apres_midi) : null,
    pills_midi: formData.value.pills_midi ? parseInt(formData.value.pills_midi) : null,
    pills_soir: formData.value.pills_soir ? parseInt(formData.value.pills_soir) : null
  };

  emit('addMedication', medicationData);
};

const handleClearForm = () => {
  emit('clearForm');
};

const handleCancelEdit = () => {
  emit('cancelEdit');
};
</script>

<template>
  <div class="medication-form-container">
    <div class="form-header">
      <h5>
        <i class="fas fa-plus-circle me-2"></i>
        <span v-if="editingIndex !== null">Edit Medication #{{ editingIndex + 1 }}</span>
        <span v-else>Add New Medication</span>
      </h5>
      <div v-if="editingIndex !== null" class="editing-indicator">
        <span class="badge bg-warning">Editing Mode</span>
      </div>
    </div>

    <div class="medication-form">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="medicationSearch" class="form-label required">Search Medication </label>
          <MedicationSearchInput :doctorId="props.doctorId"
            :model-value="formData.medication?.designation || formData.medication?.nom_commercial"
            @select="handleMedicationSelect" placeholder="Search medications..." />
          <small class="text-muted">Select a medication from the search results</small>
        </div>

        <div class="col-md-6 mb-3">
          <label for="pharmaceuticalForm" class="form-label required">Pharmaceutical Form </label>
          <input type="text" class="form-control" id="pharmaceuticalForm" :value="formData.form"
            placeholder="e.g., Tablet, Capsule, Syrup" @input="updateFormData('form', $event.target.value)" required>
          <small class="text-muted">Enter the form of the medication</small>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="num_times" class="form-label required">Number of Times</label>
          <input type="text" class="form-control" id="num_times" :value="formData.num_times"
            placeholder="e.g., 1, 2, 3 " @input="updateFormData('num_times', $event.target.value)" required>
          <small class="text-muted">How many times per frequency period</small>
        </div>

        <div class="col-md-6 mb-3">
          <label for="frequency" class="form-label required">Frequency </label>
          <select class="form-control" id="frequency" :value="formData.frequency"
            @change="updateFormData('frequency', $event.target.value)" required>
            <option value="">Sélectionner la fréquence</option>
            <option value="J">Par jour</option>
            <option value="S">Par semaine</option>
            <option value="M">Par mois</option>
          </select>
          <small class="text-muted">Sélectionner la période de fréquence</small>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="period_intakes" class="form-label required">Period Intakes </label>
          <input type="text" class="form-control" id="period_intakes" :value="formData.period_intakes"
            placeholder="e.g., 10,30" @input="updateFormData('period_intakes', $event.target.value)" required>
          <small class="text-muted">Duration or period for taking the medication</small>
        </div>

        <div class="col-md-3 mb-3">
          <label class="form-label required">period d'administration </label>
          <select class="form-control" :value="formData.frequency_period"
            @change="updateFormData('frequency_period', $event.target.value)" required>
            <option value="">Sélectionner une fréquence</option>
            <option value="Jour(s)">Jour(s)</option>
            <option value="Semaine(s)">Semaine(s)</option>
            <option value="Mois">Mois</option>
            <option value="Boite(s)">Boite(s)</option>
            <option value="Au besoin">Au besoin</option>
          </select>
          <small class="text-muted">Détermine la fréquence à laquelle le médicament doit être pris</small>
        </div>

       <div class="col-md-3 mb-3">
  <div class="row">
    <div class="col-6">
      <label class="form-label">Condition</label>
      <select class="form-control" v-model="timingWhen">
        <option value="">Sélectionner</option>
        <option value="Avant Repas">Avant Repas (Before Meal)</option>
        <option value="Après Repas">Après Repas (After Meal)</option>
        <option value="Pendant Repas">Au milieu de Repas (During Meal)</option>
        <option value="À Jeun">À Jeun (On an Empty Stomach)</option>
      </select>
    </div>
    <div class="col-6">
      <label for="timing_preference_meal" class="form-label">Time</label>
      <select class="form-control" id="timing_preference_meal" v-model="timingMeal">
        <option value="">Sélectionner </option>
        <option value="Matin">Matin</option>
        <option value="Après-midi">Après-midi</option>
        <option value="Soir">Soir</option>
        <option value="Matin/aprèsmidi">Matin/après midi</option>
        <option value="Matin/soir">Matin/soir</option>
        <option value="Après-midi/soir">Après midi/soir</option>
        <option value="Matin/après-midi/soir">Matin/après midi/soir</option>
        <option value="New">custom</option>
      </select>
    </div>
  </div>
  <small class="text-muted">Heure préférée pour la prise du médicament</small>



<div v-if="timingMeal === 'New'" class="mt-3">
  <label class="form-label">Custom Dosage Distribution</label>
  <div class="row">
    <div class="col-4">
      <label for="pillsMatin" class="form-label">Matin</label>
      <input type="number" class="form-control" id="pillsMatin"
             v-model="formData.pills_matin" min="0" step="0.5"
             placeholder="e.g., 1">
    </div>
    <div class="col-4">
      <label for="pillsMidi" class="form-label">midi</label>
      <input type="number" class="form-control" id="pillsMidi"
             v-model="formData.pills_midi" min="0" step="0.5"
             placeholder="e.g., 0.5">
    </div>
    <div class="col-4">
      <label for="pillsApresMidi" class="form-label">Après-midi</label>
      <input type="number" class="form-control" id="pillsApresMidi"
             v-model="formData.pills_apres_midi" min="0" step="0.5"
             placeholder="e.g., 0.5">
    </div>
    <div class="col-4">
      <label for="pillsSoir" class="form-label">Soir</label>
      <input type="number" class="form-control" id="pillsSoir"
             v-model="formData.pills_soir" min="0" step="0.5"
             placeholder="e.g., 2">
    </div>
  </div>
  <small class="text-muted">Specify the number of pills for each time period</small>
</div>
</div>

      </div>

      <div class="row">
        <div class="col-12 mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" id="description" :value="formData.description" rows="3"
            placeholder="Enter additional instructions or notes..."
            @input="updateFormData('description', $event.target.value)"></textarea>
          <small class="text-muted">Additional instructions or notes for the patient</small>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="premium-btn premium-btn-primary" @click="handleAddOrUpdate"
          :disabled="isLoading || isSaving">
          <i class="fas fa-plus"></i>
          <span v-if="editingIndex !== null">Update Medication</span>
          <span v-else>Add to List</span>
        </button>

        <button v-if="editingIndex !== null" type="button" class="premium-btn premium-btn-outline"
          @click="handleCancelEdit" :disabled="isLoading || isSaving">
          <i class="fas fa-times"></i>
          Cancel Edit
        </button>

        <button type="button" class="premium-btn premium-btn-secondary" @click="handleClearForm"
          :disabled="isLoading || isSaving">
          <i class="fas fa-eraser"></i>
          Clear Form
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.medication-form-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 123, 255, 0.1);
  overflow: hidden;
  margin-bottom: 24px;
}

.form-header {
  padding: 20px 24px;
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-header h5 {
  margin: 0;
  font-weight: 600;
  display: flex;
  align-items: center;
}

.editing-indicator .badge {
  font-size: 0.75rem;
  padding: 4px 8px;
}

.medication-form {
  padding: 24px;
}

.form-label.required::after {
  content: " *";
  color: #dc3545;
}

.form-control {
  border-radius: 8px;
  border: 1px solid #ced4da;
  padding: 10px 15px;
  font-size: 0.95rem;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  outline: none;
}

.form-control:invalid {
  border-color: #dc3545;
}

.text-muted {
  font-size: 0.875rem;
  color: #6c757d;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-start;
  flex-wrap: wrap;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

.premium-btn {
  padding: 12px 24px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.premium-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.premium-btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.premium-btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

.premium-btn-outline {
  background: transparent;
  color: #6c757d;
  border: 2px solid #6c757d;
}

.premium-btn-outline:hover:not(:disabled) {
  background: #6c757d;
  color: white;
  transform: translateY(-1px);
}

.premium-btn-secondary {
  background: #6c757d;
  color: white;
}

.premium-btn-secondary:hover:not(:disabled) {
  background: #5a6268;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
  }

  .form-header {
    flex-direction: column;
    gap: 10px;
    text-align: center;
  }

  .medication-form {
    padding: 16px;
  }
}
</style>