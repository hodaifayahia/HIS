<script setup>
import { ref, defineProps, defineEmits, watch, onMounted, computed } from 'vue';
import PrescriptionHistoryTab from './PrescriptionHistory.vue';
import TemplateModel from './TemplateModel.vue';
import MedicationForm from './MedicationForm.vue';
import { useToastr } from '../../../Components/toster';
import axios from 'axios';
import { useAuthStoreDoctor } from '../../../stores/AuthDoctor';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  patientId: {
    type: [String, Number],
    required: true
  },
  appointmentId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['update:modelValue']);

const toaster = useToastr();
const prescriptionDetails = ref({
  patientAge: null,
  patientWeight: null,
  patientDob: null,
  useDob: false,
  prescriptionDate: new Date().toISOString().split('T')[0], // Default to today
});

// Reactive state for the new checkbox
const includeDate = ref(false); // Default to not including the date
const ageUnit = ref('Années'); // Default to years for age input

const doctors = useAuthStoreDoctor();
const currentDoctorId = ref(null);
const specializationId = ref(null);
const autoTemplateSwitch = ref(false);

onMounted(async () => {
  await doctors.getDoctor();

  if (doctors.doctorData) {
    currentDoctorId.value = doctors.doctorData.id;
    specializationId.value = doctors.doctorData.specialization_id
                               ? Number(doctors.doctorData.specialization_id)
                               : null;
  } else {
    toaster.error('Failed to load doctor profile. Please try again.');
    return;
  }

  await fetchPatientData();

  if (currentDoctorId.value) {
    console.log(currentDoctorId.value);

    getPrescriptionTemplate();
  } else {
    toaster.info('Doctor ID not found. Cannot fetch folders.');
  }
});

// Updated watch function for autoTemplateSwitch
watch(autoTemplateSwitch, async (val) => {
  if (val) {
    // Switch is ON: Save prescription and open template modal
    if (!isPrescriptionSaved.value) {
      try {
        await postPrescription();
        // After successful save, open template modal
        showTemplateModal.value = true;
      } catch (error) {
        // If save fails, reset the switch
        autoTemplateSwitch.value = false;
        toaster.error('Failed to save prescription. Template modal not opened.');
      }
    } else {
      // If already saved, just open template modal
      showTemplateModal.value = true;
    }
  } else {
    // Switch is OFF: Just save prescription without opening template modal
    if (!isPrescriptionSaved.value) {
      try {
        await postPrescription();
        toaster.success('Prescription saved successfully!');
      } catch (error) {
        toaster.error('Failed to save prescription.');
      }
    }
  }
});

const calculatedAge = computed(() => {
  if (!prescriptionDetails.value.patientDob) return null;

  const dob = new Date(prescriptionDetails.value.patientDob);
  const today = new Date();
  let age = today.getFullYear() - dob.getFullYear();
  const monthDiff = today.getMonth() - dob.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
    age--;
  }

  return age;
});

const medications = ref([]);
const templateSearchQuery = ref("");
const showTemplateDropdown = ref(false);
const selectedTemplate = ref(null);

const PrescriptionTemplate = ref({
  templates: []
});
const showTemplateModal = ref(false);

const isLoading = ref(false);
const isSaving = ref(false);
const editingIndex = ref(null);

const currentMedication = ref({
  cdActiveSubstance: '',
  Frequency: '',
  pharmaceuticalForm: '',
  numIntakesPerTimes: '',
  durationOrBoxes: '',
  endDate: '',
  startDate: '',
  period_intakes: '',
  timing_preference: '',
  frequency_period: '',
  description: '',
  brandName: '',
  // Add custom pill count fields
  pills_matin: '',
  pills_apres_midi: '',
  pills_midi: '',
  pills_soir: ''
});

const currentTheme = ref('default');

onMounted(() => {
  const savedTheme = localStorage.getItem('prescriptionTheme');
  if (savedTheme) {
    currentTheme.value = savedTheme;
  }
  getPrescriptionTemplate();
});

watch(currentTheme, (newTheme) => {
  localStorage.setItem('prescriptionTheme', newTheme);
});

const themeClasses = computed(() => `theme-${currentTheme.value}`);
const cardThemeClasses = computed(() => `theme-card-${currentTheme.value}`);
const buttonThemeClasses = computed(() => `theme-button-${currentTheme.value}`);

watch(
  () => props.modelValue,
  (newVal) => {
    medications.value = newVal.length > 0 ? [...newVal] : [];
  },
  { immediate: true, deep: true }
);

const savedPrescriptionId = ref(null);
const prescriptionHistoryRef = ref(null);

const isPrescriptionSaved = computed(() => {
  return savedPrescriptionId.value !== null;
});

const filteredTemplates = computed(() => {
  if (!PrescriptionTemplate.value.templates) {
    return { templates: [] };
  }

  if (!templateSearchQuery.value) {
    return PrescriptionTemplate.value;
  }

  const filtered = PrescriptionTemplate.value.templates.filter(template =>
    template.name.toLowerCase().includes(templateSearchQuery.value.toLowerCase())
  );

  return { templates: filtered };
});

const getPrescriptionTemplate = async () => {
  isLoading.value = true;
  try {
    const response = await axios.get('/api/prescription/prescription-templates', {
      params: { doctor_id: currentDoctorId.value }
    });
    PrescriptionTemplate.value.templates = response.data.templates;
  } catch (error) {
    console.error('Error fetching prescription templates:', error);
  } finally {
    isLoading.value = false;
  }
};

const fetchPatientData = async () => {
  try {
    const response = await axios.get(`/api/patients/${props.patientId}`);
    const patientData = response.data.data;

    if (patientData) {
      if (patientData.dateOfBirth) {
        prescriptionDetails.value.patientDob = patientData.dateOfBirth;
        prescriptionDetails.value.useDob = true;
      }

      if (patientData.weight) {
        prescriptionDetails.value.patientWeight = patientData.weight;
      }
    }
  } catch (error) {
    console.error('Error fetching patient data:', error);
  }
};

const SaveAsTemplate = async () => {
  if (medications.value.length === 0) {
    toaster.error('Please add at least one medication before saving as a template.');
    return;
  }

  if (!isPrescriptionSaved.value) {
    toaster.error('Please save the prescription first before creating a template.');
    return;
  }

  showTemplateModal.value = true;
};

const handleTemplateSaved = async (templateData) => {
  showTemplateModal.value = false;
  toaster.success('Template saved successfully!');
  getPrescriptionTemplate();

  if (templateData && templateData.id) {
    try {
      isLoading.value = true;
      const response = await axios.get(`/api/prescription/prescription-templates/${templateData.id}/medications`, {
        params: {
          patient_id: props.patientId,
          appointment_id: props.appointmentId
        }
      });
      if (response.data.success && response.data.data && response.data.data.medications) {
        const templateMedications = response.data.data.medications.map(med => ({
          medication_id: med.id,
          medication: {
            id: med.id,
            designation: med.designation || '',
            nom_commercial: med.nom_commercial || '',
            forme: med.forme || '',
            type_medicament: med.type_medicament || '',
            boite_de: med.boite_de || '',
            code_pch: med.code_pch || ''
          },
          form: med.forme || '',
          num_times: med.num_intakes_per_time || '1',
          frequency: med.frequency || 'day',
          start_date: med.start_date || '',
          timing_preference: med.timing_preference || '',
          end_date: med.end_date || '',
          period_intakes: med.period_intakes || '',
          frequency_period: med.frequency_period || '',
          description: med.description || '',
          // Include custom pill counts
          pills_matin: med.pills_matin || '',
          pills_apres_midi: med.pills_apres_midi || '',
          pills_midi: med.pills_midi || '',
          pills_soir: med.pills_soir || '',
          isFromTemplate: true
        }));
        medications.value = templateMedications;
        emit('update:modelValue', medications.value);
        toaster.success('Medications replaced with template.');
      }
    } catch (error) {
      toaster.error('Failed to load template medications.');
    } finally {
      isLoading.value = false;
      autoTemplateSwitch.value = false;
    }
  } else {
    autoTemplateSwitch.value = false;
  }
};

const handleTemplateCancel = () => {
  showTemplateModal.value = false;
  autoTemplateSwitch.value = false;
};
const handleAddMedication = (newMedication) => {
  if (!newMedication.medication_id) {
    toaster.error('Please select a medication');
    return;
  }

  if (!newMedication.form || !newMedication.num_times || !newMedication.frequency) {
    toaster.error('Please fill in all required fields');
    return;
  }

  const medicationData = {
    medication_id: parseInt(newMedication.medication_id),
    medication: newMedication.medication,
    form: newMedication.form.trim(),
    num_times: newMedication.num_times.trim(),
    frequency: newMedication.frequency,
    start_date: newMedication.start_date || null,
    end_date: newMedication.end_date || null,
    timing_preference: String(newMedication.timing_preference || ''),
    // Convert to string and provide default empty string
    period_intakes: String(newMedication.period_intakes || ''),
    frequency_period: String(newMedication.frequency_period || ''),
    description: newMedication.description ? String(newMedication.description).trim() : '',
    // Convert pill counts to string or null
    pills_matin: newMedication.pills_matin ? String(newMedication.pills_matin) : null,
    pills_apres_midi: newMedication.pills_apres_midi ? String(newMedication.pills_apres_midi) : null,
    pills_midi: newMedication.pills_midi ? String(newMedication.pills_midi) : null,
    pills_soir: newMedication.pills_soir ? String(newMedication.pills_soir) : null
  };

  if (editingIndex.value !== null) {
    medications.value[editingIndex.value] = medicationData;
    toaster.success('Medication updated successfully');
  } else {
    medications.value.push(medicationData);
    toaster.success('Medication added successfully');
  }

  editingIndex.value = null;
  handleClearForm();
  emitUpdate();

  savedPrescriptionId.value = null;
};
const postPrescription = async () => {
  if (medications.value.length === 0) {
    toaster.error('Please add at least one medication before saving.');
    return;
  }

  const invalidMedications = medications.value.filter(med =>
    !med.medication_id || !med.form || !med.num_times || !med.frequency
  );

  if (invalidMedications.length > 0) {
    toaster.error('Some medications are missing required fields. Please check all medications.');
    return;
  }

  isSaving.value = true;
  try {
    const prescriptionData = {
      patient_id: parseInt(props.patientId),
      patient_age: prescriptionDetails.value.useDob ? calculatedAge.value : prescriptionDetails.value.patientAge,
      patient_weight: prescriptionDetails.value.patientWeight || null,
      patient_dob: prescriptionDetails.value.useDob ? prescriptionDetails.value.patientDob : null,
      prescription_date: includeDate.value ? prescriptionDetails.value.prescriptionDate : null,
      ageUnit: ageUnit.value,
      medications: medications.value.map(med => ({
        medication_id: parseInt(med.medication_id),
        form: med.form.trim(),
        num_times: med.num_times.trim(),
        frequency: med.frequency,
        start_date: med.start_date || null,
        end_date: med.end_date || null,
        // Convert to string and provide default empty string
        period_intakes: String(med.period_intakes || ''),
        timing_preference: String(med.timing_preference || ''),
        frequency_period: String(med.frequency_period || ''),
        description: med.description ? String(med.description).trim() : '',
        // Convert pill counts to string or null
        pills_matin: med.pills_matin ? String(med.pills_matin) : null,
        pills_apres_midi: med.pills_apres_midi ? String(med.pills_apres_midi) : null,
        pills_midi: med.pills_midi ? String(med.pills_midi) : null,
        pills_soir: med.pills_soir ? String(med.pills_soir) : null
      })),
      appointment_id: props.appointmentId ? parseInt(props.appointmentId) : null,
      with_date: includeDate.value
    };

    const response = await axios.post('/api/prescription', prescriptionData);

    if (response.data && response.data.id) {
      savedPrescriptionId.value = response.data.id;
      if (prescriptionHistoryRef.value) {
        prescriptionHistoryRef.value.fetchPrescriptionsHistory();
      }
      toaster.success('Prescription saved successfully!');
      return response.data;
    } else if (response.data && response.data.prescription && response.data.prescription.id) {
      savedPrescriptionId.value = response.data.prescription.id;
      if (prescriptionHistoryRef.value) {
        prescriptionHistoryRef.value.fetchPrescriptionsHistory();
      }
      toaster.success('Prescription saved successfully!');
      return response.data;
    } else {
      throw new Error('Invalid response format from server');
    }
  } catch (error) {
    console.error('Error saving prescription:', error);
    if (error.response?.status === 422) {
      const errors = error.response.data.errors;
      if (errors) {
        Object.keys(errors).forEach(field => {
          errors[field].forEach(message => {
            toaster.error(`${field}: ${message}`);
          });
        });
      } else {
        toaster.error('Validation failed. Please check all required fields.');
      }
    } else if (error.response?.data?.message) {
      toaster.error(error.response.data.message);
    } else {
      toaster.error('Error saving prescription. Please try again.');
    }
    throw error;
  } finally {
    isSaving.value = false;
    medications.value = [];
  }
};

const getDisplayText = (medication, field) => {
  switch (field) {
    case 'name':
      return medication.medication?.designation || medication.medication?.nom_commercial || '-';
    case 'brand':
      return medication.medication?.nom_commercial || medication.medication?.brand_name || '-';
    case 'form':
      return medication.form || '-';
    case 'frequency':
      return medication.num_times && medication.frequency
        ? `${medication.num_times} per ${medication.frequency}`
        : '-';
    case 'start_date':
      return medication.start_date || '-';
    case 'end_date':
      return medication.end_date || '-';
    case 'description':
      return medication.description || '-';
    default:
      return '-';
  }
};

const removeMedication = (index) => {
  medications.value.splice(index, 1);
  emitUpdate();
  savedPrescriptionId.value = null;
};

const editMedication = (index) => {
  editingIndex.value = index;
  currentMedication.value = { ...medications.value[index] };
};

const handleCancelEdit = () => {
  editingIndex.value = null;
  handleClearForm();
};

const emitUpdate = () => {
  emit('update:modelValue', medications.value);
};

const clearTemplateSelection = () => {
  selectedTemplate.value = null;
  templateSearchQuery.value = '';
  medications.value = [];
  emitUpdate();
  savedPrescriptionId.value = null;
};

const handleClearForm = () => {
  currentMedication.value = {
    Frequency: '',
    cdActiveSubstance: '',
    pharmaceuticalForm: '',
    startDate: '',
    endDate: '',
    description: '',
    timing_preference: '',
    numIntakesPerTimes: '',
    period_intakes: '',
    frequency_period: '',
    durationOrBoxes: '',
    brandName: '',
    // Clear custom pill counts
    pills_matin: '',
    pills_apres_midi: '',
    pills_midi: '',
    pills_soir: ''
  };
  editingIndex.value = null;
};

const onTemplateSearch = (event) => {
  templateSearchQuery.value = event.target.value;
  showTemplateDropdown.value = true;
  if (!event.target.value) {
    selectedTemplate.value = null;
  }
};
const onTemplateSelect = async (template) => {
  try {
    if (!template || !template.id) {
      return;
    }

    isLoading.value = true;
    showTemplateDropdown.value = false;
    selectedTemplate.value = template;
    templateSearchQuery.value = template.name;

    const response = await axios.get(`/api/prescription/prescription-templates/${template.id}/medications`, {
      params: {
        patient_id: props.patientId,
        appointment_id: props.appointmentId
      }
    });

    if (response.data.success && response.data.data && response.data.data.template.medications) {
      // Get existing medication IDs to check against
      const existingMedicationIds = new Set(medications.value.map(m => m.medication_id));
      
      // Helper function to get distinct items by medication_id
      const getDistinctMedications = (meds) => {
        const seen = new Set();
        return meds.filter(med => {
          if (seen.has(med.medication_id)) {
            return false;
          }
          seen.add(med.medication_id);
          return true;
        });
      };

      // First, remove duplicates from template medications
      const distinctTemplateMedications = getDistinctMedications(response.data.data.template.medications);
      
      const templateMedications = distinctTemplateMedications
        // Filter to only include medications not already in the list
        .filter(med => !existingMedicationIds.has(med.medication_id))
        .map(med => ({
          medication_id: med.medication_id,
          medication: {
            id: med.medication.id,
            designation: med.medication.designation || '',
            nom_commercial: med.medication.nom_commercial || '',
            forme: med.medication.forme || '',
            type_medicament: med.medication.type_medicament || '',
            boite_de: med.medication.boite_de || '',
            code_pch: med.medication.code_pch || ''
          },
          form: med.form || '',
          num_times: med.num_times || '1',
          frequency: med.frequency || 'day',
          start_date: med.start_date || '',
          timing_preference: String(med.timing_preference || ''),
          end_date: med.end_date || '',
          period_intakes: String(med.period_intakes || ''),
          frequency_period: String(med.frequency_period || ''),
          description: String(med.description || ''),
          pills_matin: med.pills_matin ? String(med.pills_matin) : null,
          pills_apres_midi: med.pills_apres_midi ? String(med.pills_apres_midi) : null,
          pills_midi: med.pills_midi ? String(med.pills_midi) : null,
          pills_soir: med.pills_soir ? String(med.pills_soir) : null,
          isFromTemplate: true
        }));

      if (templateMedications.length > 0) {
        // Ensure the final medications array also has no duplicates
        const updatedMedications = [...templateMedications, ...medications.value];
        medications.value = getDistinctMedications(updatedMedications);
        
        emit('update:modelValue', medications.value);
        toaster.success(`Added ${templateMedications.length} new medications from template`);
      } else {
        toaster.info('All medications in this template are already in your prescription');
      }
      
      savedPrescriptionId.value = null;
    } else {
      throw new Error('No medications found in template');
    }
  } catch (error) {
    console.error('Error fetching template medications:', error);
    toaster.error('Failed to load template medications: ' + (error.message || 'Unknown error'));
  } finally {
    isLoading.value = false;
  }
};



const calculateDoseByWeight = (baseDose, weight) => {
  if (!baseDose || !weight) return baseDose;

  try {
    const match = baseDose.match(/(\d+\.?\d*)\s*(\w+)/);
    if (!match) return baseDose;

    const [_, value, unit] = match;
    const numericValue = parseFloat(value);
    const adjustedValue = (numericValue * weight) / 70;

    return `${adjustedValue.toFixed(2)} ${unit}`;
  } catch (e) {
    console.warn('Error calculating weight-based dose:', e);
    return baseDose;
  }
};
</script>
<template>
  <div class="premium-tab-content" :class="themeClasses">


    <div class="premium-form-group mb-4">
      <label class="premium-label">Search and Select a Template</label>
      <div class="premium-dropdown-container" @focusout="() => showTemplateDropdown = false">
        <div class="premium-input-group">
          <i class="fas fa-search premium-input-icon"></i>
          <input type="text" class="premium-search" placeholder="Type to search for a template..."
            v-model="templateSearchQuery" @input="onTemplateSearch" @focus="showTemplateDropdown = true">
          <button v-if="selectedTemplate || templateSearchQuery" class="clear-btn" @click="clearTemplateSelection"
            title="Clear selection">
            <i class="fas fa-times"></i>
          </button>
          <i class="fas fa-chevron-down premium-dropdown-arrow"
            @click="showTemplateDropdown = !showTemplateDropdown"></i>
        </div>

        <div v-if="showTemplateDropdown" class="premium-dropdown-menu">

          <div v-if="filteredTemplates.templates.length > 0">

            <div v-for="template in filteredTemplates.templates" :key="template.id" class="premium-dropdown-item"
              @click.prevent="onTemplateSelect(template)" @mousedown.prevent>
              <i class="fas fa-file-alt premium-item-icon"></i>
              <span>{{ template.name }}</span>
            </div>
          </div>
          <div v-else class="premium-dropdown-empty">
            No templates found. You can create one after adding medications.
          </div>
        </div>
      </div>
    </div>
  <div class="row m-2">
    <div class="col-md-8">
      <label for="prescriptionDate" class="form-label">Prescription Date</label>
      <input type="date" class="form-control" id="prescriptionDate" v-model="prescriptionDetails.prescriptionDate"
        required>
    </div>

      <div class="col-md-4 mt-4 pt-2">
    <div class="custom-switch-wrapper">
      <label class="custom-switch">
        <input type="checkbox" id="includeDateSwitch" v-model="includeDate" />
        <span class="custom-slider"></span>
      </label>
      <label for="includeDateSwitch" class="form-label mb-0">
        Include Date in Prescription
        <i
          class="bi bi-info-circle ms-2 text-muted"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          title="Checking this will include the prescription date in the final document."
        ></i>
      </label>
    </div>
    </div>

  </div>

    <div class="card premium-card mb-4" :class="cardThemeClasses">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Patient Specific Details</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="useDobSwitch" v-model="prescriptionDetails.useDob">
              <label class="form-check-label" for="useDobSwitch">
                Use Date of Birth instead of Age
              </label>
            </div>

            <div v-if="!prescriptionDetails.useDob">
              <label for="patientAge" class="form-label">Patient's Age (optional)</label>
              <div class="input-group">
                <input type="number" class="form-control" id="patientAge"
                  v-model.number="calculatedAge"  placeholder="e.g., 5" min="0"
                  :max="ageUnit.value === 'Années' ? 120 : 1440">
                <select class="form-select" v-model="ageUnit" style="width: auto;">
                  <option value="Années">Années</option>
                  <option value="Mois">Mois</option>
                </select>

              </div>
            </div>

            <div v-else>
              <label for="patientDob" class="form-label">Patient's Date of Birth</label>
              <input type="date" class="form-control" id="patientDob" v-model="prescriptionDetails.patientDob"
                :max="new Date().toISOString().split('T')[0]" required>
              <small class="text-muted" v-if="prescriptionDetails.patientDob && calculatedAge">
                Age: {{ calculatedAge }} years
              </small>
            </div>
          </div>

          <div class="col-md-6 mb-3 mt-4 pt-3">
            <label for="patientWeight" class="form-label">Patient's Weight (kg)</label>
            <input type="number" class="form-control" id="patientWeight" v-model="prescriptionDetails.patientWeight"
              placeholder="e.g., 70" min="0" step="0.1" required>
            <small class="text-muted">Weight in kilograms</small>


          </div>
        </div>
      </div>
    </div>

    <MedicationForm :doctorId="currentDoctorId" class="medication-form-container" v-model="currentMedication"
      :editing-index="editingIndex" :is-loading="isLoading" :is-saving="isSaving" @addMedication="handleAddMedication"
      @clearForm="handleClearForm" @cancelEdit="handleCancelEdit" />

    <div v-if="medications.length > 0" class="medications-table-container mb-4" :class="cardThemeClasses">
      <div class="table-header">
        <h5><i class="fas fa-list me-2"></i>Added Medications ({{ medications.length }})</h5>
        <div class="prescription-status">
          <span v-if="isPrescriptionSaved" class="status-badge saved">
            <i class="fas fa-check-circle"></i> Saved
          </span>
          <span v-else class="status-badge unsaved">
            <i class="fas fa-exclamation-circle"></i> Unsaved
          </span>
        </div>
      </div>

      <div
        class="action-buttons-container d-flex flex-column flex-md-row justify-content-end align-items-md-center gap-3">
        <button type="button" class="btn btn-primary" @click="postPrescription" :disabled="isLoading || isSaving">
          <span v-if="isSaving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          <i v-else class="fas fa-save me-2"></i>
          <span class="btn-text">{{ isSaving ? 'Saving...' : 'Save Prescription' }}</span>
        </button>

        <div class="custom-switch-wrapper ">
          <label class="custom-switch">
            <input type="checkbox" v-model="autoTemplateSwitch" />
            <span class="custom-slider"></span>
          </label>
          <span class="fw-semibold text-dark">
            {{ autoTemplateSwitch ? 'Add as Template: ON' : 'Add as Template: OFF' }}
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table medications-table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Designation</th>
              <th scope="col">Brand Name</th>
              <th scope="col">Form</th>
              <th scope="col">Daily Frequency</th>
              <th scope="col">frequency period</th>
              <th scope="col">period</th>
              <th scope="col">timing preference</th>
              <th scope="col">description</th>
              <th scope="col">Pills Matin</th>
              <th scope="col">Pills Après-midi</th>
              <th scope="col">Pills Midi</th>
              <th scope="col">Pills Soir</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
          <tr v-for="(medication, index) in medications" :key="index" class="medication-row"
  :class="{ 'editing-row': editingIndex === index, 'template-medication': medication.isFromTemplate }">
  <td class="row-number">{{ index + 1 }}</td>
  <td>{{ medication.medication?.designation || '-' }}</td>
  <td>{{ medication.medication?.nom_commercial || '-' }}</td>
  <td>{{ medication.form || '-' }}</td>
  <td>{{ `${medication.num_times} per ${medication.frequency}` || '-' }}</td>
  <td>{{ medication.frequency_period || '-' }}</td>
  <td>{{ medication.period_intakes || '-' }}</td>
  <td>
    {{ medication.timing_preference ? medication.timing_preference.replace('_New', '').trim() || '-' : '-' }}
  </td> <td>{{ medication.description || '-' }}</td>

  <td>
    <span v-if="medication.timing_preference && medication.timing_preference.includes('New')">{{ medication.pills_matin || '-' }}</span>
    <span v-else>-</span>
  </td>
  <td>
    <span v-if="medication.timing_preference && medication.timing_preference.includes('New')">{{ medication.pills_apres_midi || '-' }}</span>
    <span v-else>-</span>
  </td>
  <td>
    <span v-if="medication.timing_preference && medication.timing_preference.includes('New')">{{ medication.pills_midi || '-' }}</span>
    <span v-else>-</span>
  </td>
  <td>
    <span v-if="medication.timing_preference && medication.timing_preference.includes('New')">{{ medication.pills_soir || '-' }}</span>
    <span v-else>-</span>
  </td>
  <td class="actions-cell">
    <button type="button" class="action-btn edit-btn" @click="editMedication(index)" title="Edit Medication"
      :disabled="isLoading || isSaving">
      <i class="fas fa-edit"></i>
    </button>
    <button type="button" class="action-btn delete-btn" @click="removeMedication(index)"
      title="Remove Medication" :disabled="isLoading || isSaving">
      <i class="fas fa-trash-alt"></i>
    </button>
  </td>
</tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="medications.length === 0" class="empty-state-message" :class="cardThemeClasses">
      <div class="empty-icon">
        <i class="fas fa-pills"></i>
      </div>
      <p>
        <strong>No medications added yet.</strong><br />
        You can start by selecting a template above or by filling out the form to add a new medication.
      </p>
    </div>

    <TemplateModel :showTemplateModal="showTemplateModal" :prescription-id="savedPrescriptionId"
      @saved="handleTemplateSaved" @cancel="handleTemplateCancel" :doctorId="currentDoctorId" />

    <div class="mt-4">
      <PrescriptionHistoryTab ref="prescriptionHistoryRef" :patient-id="patientId" :appointment-id="appointmentId" />
    </div>
  </div>
</template>
<style scoped>
/* General Styles */
.premium-tab-content {
  padding: 24px;
  background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
  min-height: 100vh;
  transition: background-color 0.3s ease, color 0.3s ease;
}
.custom-switch-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
}

.custom-switch {
  position: relative;
  width: 60px;
  height: 30px;
}

.custom-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.custom-slider {
  position: absolute;
  cursor: pointer;
  background: #ccc; /* Light gray for default state */
  border-radius: 34px;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  transition: background-color 0.4s;
}

.custom-slider::before {
  content: "";
  position: absolute;
  height: 22px;
  width: 22px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(47, 59, 237, 0.2);
  transition: transform 0.4s;
}

input:checked + .custom-slider {
  background: #066ec9; /* Bootstrap secondary gray */
}

input:checked + .custom-slider::before {
  transform: translateX(30px);
}

.premium-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 123, 255, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
}

.premium-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.premium-form-group .premium-label {
  display: block;
  font-weight: 600;
  color: #495057;
  margin-bottom: 8px;
}

/* Dropdown and Template Search */
.premium-dropdown-container {
  position: relative;
}

.premium-input-group {
  display: flex;
  align-items: center;
  position: relative;
}

.premium-input-icon {
  position: absolute;
  left: 15px;
  color: #adb5bd;
}

.premium-search {
  width: 100%;
  padding: 12px 40px;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.premium-search:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
}

.clear-btn {
  position: absolute;
  right: 40px;
  border: none;
  background: transparent;
  color: #6c757d;
  cursor: pointer;
  padding: 0 5px;
  font-size: 1rem;
}

.premium-dropdown-arrow {
  position: absolute;
  right: 15px;
  color: #adb5bd;
  cursor: pointer;
}

.premium-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  margin-top: 8px;
  z-index: 1000;
  max-height: 250px;
  overflow-y: auto;
}

.premium-dropdown-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.premium-dropdown-item:hover {
  background-color: #f8f9fa;
}

.premium-item-icon {
  margin-right: 12px;
  color: #007bff;
}

.premium-dropdown-empty {
  padding: 16px;
  text-align: center;
  color: #6c757d;
}

/* Action Buttons */
.action-buttons-container {
  display: flex;
  gap: 8px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  align-items: center;
  justify-content: flex-end;
  margin-bottom: 16px;
  transition: background-color 0.3s ease;
}

.premium-btn {
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  min-width: 90px;
  justify-content: center;
}

.premium-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(110%);
}

.premium-btn:active:not(:disabled) {
  transform: translateY(0);
}

.premium-btn-success {
  background: #28a745;
  color: white;
  box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}

.premium-btn-info {
  background: #17a2b8;
  color: white;
  box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
}

.premium-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-text {
  font-size: 0.875rem;
  font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .action-buttons-container {
    padding: 8px;
  }

  .premium-btn {
    padding: 6px 12px;
    min-width: 80px;
  }

  .btn-text {
    font-size: 0.8125rem;
  }
}

/* Medications Table */
.medications-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  border: 1px solid rgba(0, 123, 255, 0.1);
  transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.table-header {
  padding: 16px 24px;
  background: linear-gradient(135deg, #f8f9ff 0%, #e9ecef 100%);
  border-bottom: 1px solid #dee2e6;
  transition: background 0.3s ease, border-color 0.3s ease;
}

.table-header h5 {
  margin: 0;
  font-weight: 600;
  color: #495057;
  transition: color 0.3s ease;
}

.medications-table thead th {
  background: linear-gradient(135deg, #f1f3f4 0%, #e9ecef 100%);
  border: none;
  padding: 16px 12px;
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
  transition: background 0.3s ease, color 0.3s ease;
}

.medications-table tbody tr.editing-row {
  background-color: #fff3cd;
  border-left: 4px solid #ffc107;
}

.medications-table tbody tr.template-medication {
  background-color: #e6f7ff;
  border-left: 4px solid #007bff;
}

.actions-cell {
  width: 120px;
  text-align: center;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

/* Empty State */
.empty-state-message {
  text-align: center;
  padding: 40px 20px;
  color: #6c757d;
  background: white;
  border-radius: 12px;
  border: 2px dashed #dee2e6;
  margin-bottom: 24px;
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
}

.empty-icon {
  font-size: 2rem;
  color: #007bff;
  margin-bottom: 16px;
  transition: color 0.3s ease;
}

/* Theme Specific Styles */
.theme-dark {
  background: #343a40 !important;
  color: #e0e0e0 !important;
}

.theme-dark .premium-card,
.theme-dark .medications-table-container,
.theme-dark .empty-state-message {
  background: #495057;
  border-color: rgba(255, 255, 255, 0.1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.theme-dark .form-header {
  background: linear-gradient(135deg, #212529 0%, #343a40 100%) !important;
  color: white;
}

.theme-dark .premium-btn-primary {
  background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
  box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3) !important;
}

.theme-dark .premium-btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #495057 0%, #343a40 100%) !important;
  box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4) !important;
}

.theme-dark .premium-btn-success {
  background: #004d40 !important;
  box-shadow: 0 2px 4px rgba(0, 77, 64, 0.2) !important;
}

.theme-dark .premium-btn-info {
  background: #006064 !important;
  box-shadow: 0 2px 4px rgba(0, 96, 100, 0.2) !important;
}

.theme-dark .action-buttons-container {
  background: #5a5f63;
}

.theme-dark .table-header {
  background: linear-gradient(135deg, #5b6268 0%, #495057 100%);
  border-bottom-color: #6c757d;
}

.theme-dark .table-header h5 {
  color: #f8f9fa;
}

.theme-dark .medications-table thead th {
  background: linear-gradient(135deg, #5b6268 0%, #495057 100%);
  color: #f8f9fa;
}

.theme-dark .medications-table tbody tr {
  background-color: #3f4549;
  color: #e0e0e0;
}

.theme-dark .medications-table tbody tr.editing-row {
  background-color: #5a5f63;
  border-left-color: #ffc107;
}

.theme-dark .medications-table tbody tr.template-medication {
  background-color: #3b4d61;
  border-left-color: #007bff;
}

.theme-dark .empty-state-message {
  background: #495057;
  border-color: #6c757d;
  color: #e0e0e0;
}

.theme-dark .empty-icon {
  color: #adb5bd;
}

.theme-dark .premium-input-group .premium-search,
.theme-dark .form-control {
  background-color: #555;
  color: #eee;
  border-color: #777;
}

.theme-dark .premium-search:focus,
.theme-dark .form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
}

.theme-dark .premium-dropdown-menu {
  background-color: #555;
  border-color: #777;
}

.theme-dark .premium-dropdown-item {
  color: #eee;
}

.theme-dark .premium-dropdown-item:hover {
  background-color: #666;
}

.theme-purple {
  background: linear-gradient(135deg, #f0e6fa 0%, #f7ebfd 100%) !important;
  color: #3a005c !important;
}

.theme-purple .premium-card,
.theme-purple .medications-table-container,
.theme-purple .empty-state-message {
  background: #fdf5ff;
  border-color: rgba(123, 0, 255, 0.1);
}

.theme-purple .form-header {
  background: linear-gradient(135deg, #6f42c1 0%, #8e2de2 100%) !important;
  color: white;
}

.theme-purple .premium-btn-primary {
  background: linear-gradient(135deg, #6f42c1 0%, #8e2de2 100%) !important;
  box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3) !important;
}

.theme-purple .premium-btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #8e2de2 0%, #6f42c1 100%) !important;
  box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4) !important;
}

.theme-purple .premium-btn-success {
  background: #502b80 !important;
}

.theme-purple .premium-btn-info {
  background: #9c27b0 !important;
}

.theme-purple .action-buttons-container {
  background: #e0b0ff;
}

.theme-purple .table-header {
  background: linear-gradient(135deg, #e0b0ff 0%, #d8a0ff 100%);
  border-bottom-color: #c080ff;
}

.theme-purple .table-header h5 {
  color: #3a005c;
}

.theme-purple .medications-table thead th {
  background: linear-gradient(135deg, #e0b0ff 0%, #d8a0ff 100%);
  color: #3a005c;
}

.theme-purple .medications-table tbody tr {
  background-color: #f5edfc;
  color: #3a005c;
}

.theme-purple .medications-table tbody tr.editing-row {
  background-color: #f7e0ff;
  border-left-color: #a020f0;
}

.theme-purple .medications-table tbody tr.template-medication {
  background-color: #e6cffc;
  border-left-color: #8e2de2;
}

.theme-purple .empty-state-message {
  background: #fdf5ff;
  border-color: #e0b0ff;
  color: #6a0dad;
}

.theme-purple .empty-icon {
  color: #8e2de2;
}

.theme-purple .premium-input-group .premium-search,
.theme-purple .form-control {
  background-color: #fff;
  color: #3a005c;
  border-color: #d1baff;
}

.theme-purple .premium-search:focus,
.theme-purple .form-control:focus {
  border-color: #8e2de2;
  box-shadow: 0 0 0 3px rgba(142, 45, 226, 0.2);
}

.theme-purple .premium-dropdown-menu {
  background-color: #fff;
  border-color: #d1baff;
}

.theme-purple .premium-dropdown-item {
  color: #3a005c;
}

.theme-purple .premium-dropdown-item:hover {
  background-color: #f0e6fa;
}

.theme-selector {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.theme-selector .form-label {
  margin-bottom: 0;
  font-weight: 600;
  color: inherit;
}

.theme-selector .form-control {
  width: auto;
  min-width: 150px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.9rem;
  background-color: var(--form-control-bg, #ffffff);
  color: var(--form-control-color, #495057);
  border: 1px solid var(--form-control-border, #ced4da);
  transition: all 0.3s ease;
}

.premium-tab-content {
  --form-control-bg: #ffffff;
  --form-control-color: #495057;
  --form-control-border: #ced4da;
}

.theme-dark {
  --form-control-bg: #555;
  --form-control-color: #eee;
  --form-control-border: #777;
}

.theme-purple {
  --form-control-bg: #fff;
  --form-control-color: #3a005c;
  --form-control-border: #d1baff;
}
</style>