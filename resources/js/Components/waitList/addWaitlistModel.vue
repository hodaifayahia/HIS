<script setup>
import { reactive, ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { Form } from 'vee-validate';
import PatientSearch from '../../Pages/Appointments/PatientSearch.vue';
import { useToastr } from '../../Components/toster';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Card from 'primevue/card';

const toastr = useToastr();

const props = defineProps({
  show: Boolean,
  editMode: { type: Boolean, default: false },
  waitlist: { type: Object, default: null },
  specializationId: { type: Number, default: null },
  isDaily: { type: [Number, String], default: null },
});

// Emits
const emit = defineEmits(['close', 'save', 'update']);

const form = reactive({
  doctor_id: null,
  patient_id: null,
  specialization_id: props.specializationId,
  is_Daily: props.isDaily !== null ? !!parseInt(props.isDaily) : false, // Convert prop to boolean
  created_by: null,
  importance: null,
  notes: '',
});

// Reactive data
const doctors = ref([]);
const specializations = ref([]);
const importanceLevels = ref([
    { label: 'Urgent', value: 0 },
    { label: 'Normal', value: 1 },
]);
const searchQuery = ref('');

// Fetch specializations
const fetchSpecializations = async () => {
  try {
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data;
  } catch (error) {
    console.error('Error fetching specializations:', error);
    toastr.error('Failed to fetch specializations. Please try again later.');
  }
};

// Fetch doctors based on specialization
const fetchDoctors = async (specializationId) => {
  if (!specializationId) {
    doctors.value = [];
    return;
  }
  try {
    // Correctly use the specializationId to filter doctors
    const response = await axios.get(`/api/doctors?specialization_id=${specializationId}`);
    doctors.value = response.data.data;
  } catch (error) {
    console.error('Error fetching doctors:', error);
    toastr.error('Failed to fetch doctors. Please try again later.');
  }
};



// Pre-fill form when in edit mode
const populateForm = () => {
  if (props.editMode && props.waitlist) {
    console.log('Populating form from waitlist prop:', props.waitlist);

    // Populate form fields one by one
    form.doctor_id = props.waitlist.doctor_id;
    form.patient_id = props.waitlist.patient_id;
    form.specialization_id = parseInt(props.waitlist.specialization_id);
    form.is_Daily = props.waitlist.is_Daily === 1 || props.waitlist.is_Daily === true; // Ensure boolean value
    form.created_by = props.waitlist.created_by;
    form.importance = props.waitlist.importance;
    form.notes = props.waitlist.notes;

    // Set the patient search query
    searchQuery.value = `${props.waitlist.patient_first_name} ${props.waitlist.patient_last_name}`;
  }
};

// Handle patient selection
const handlePatientSelect = (patient) => {
  form.patient_id = patient.id;
};

// Handle form submission
const handleSubmit = async () => {
  // Simple validation to prevent form submission without required fields
  // if (!form.patient_id || !form.specialization_id || !form.doctor_id || (form.is_Daily && !form.importance)) {
  //   toastr.error('Please fill in all required fields.');
  //   return;
  // }

  try {
    const method = props.editMode ? 'put' : 'post';
    const url = props.editMode
      ? `/api/waitlists/${props.waitlist.id}`
      : '/api/waitlists';

    const response = await axios[method](url, form);
    toastr.success(`${props.editMode ? 'Waitlist updated' : 'Waitlist created'} successfully`);

    emit('save', response.data);
    closeModal();
  } catch (error) {
    console.error(`${props.editMode ? 'Error updating waitlist:' : 'Error creating waitlist:'}`, error);
    toastr.error('An error occurred while processing your request');
  }
};

// Close modal and reset form
const closeModal = () => {
  emit('close');
  // Reset reactive form data
  Object.assign(form, {
    doctor_id: null,
    patient_id: null,
    specialization_id: props.specializationId,
    is_Daily: props.isDaily !== null ? !!parseInt(props.isDaily) : false,
    created_by: null,
    importance: null,
    notes: '',
  });
  searchQuery.value = '';
};

// Watchers
// Combine the watchers into a single, more efficient watcher
watch(
  () => [props.waitlist, form.specialization_id],
  ([newWaitlist, newSpecializationId]) => {
    // If waitlist prop changes, populate the form
    if (newWaitlist) {
      populateForm();
    }
    // If specialization changes, fetch doctors
    if (newSpecializationId) {
      fetchDoctors(newSpecializationId);
    }
  },
  { immediate: true }
);

// Computed property to check if the checkbox should be disabled
const isCheckboxDisabled = computed(() => {
  return props.isDaily !== null;
});

onMounted(async () => {
  await fetchSpecializations();
  populateForm();
});
</script>

<template>
  <Dialog
    :visible="show"
    modal
    :header="editMode ? 'Edit Waitlist' : 'Create Waitlist'"
    :style="{ width: '50rem' }"
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    @update:visible="closeModal"
    @hide="closeModal"
    class="tw-font-sans"
  >
    <Card class="tw-shadow-none tw-border-0">
      <template #content>
        <Form @submit="handleSubmit" v-slot="{ errors }" class="tw-space-y-6">
          <div class="tw-space-y-2">
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
              Patient Search *
            </label>
            <PatientSearch
              v-model="searchQuery"
              :patientId="form.patient_id"
              @patientSelected="handlePatientSelect"
              class="tw-w-full"
            />
          </div>

          <div class="tw-space-y-2">
            <label for="specialization_id" class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
              Specialization *
            </label>
            <Dropdown
              id="specialization_id"
              v-model="form.specialization_id"
              :options="specializations"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a specialization"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.specialization_id }"
            />
          </div>

          <div class="tw-space-y-2">
            <label for="doctor_id" class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
              Doctor *
            </label>
            <Dropdown
              id="doctor_id"
              v-model="form.doctor_id"
              :options="[{ id: null, name: 'ALL Doctors' }, ...doctors]"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a doctor"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.doctor_id }"
            />
          </div>
          <div class="tw-flex tw-items-center tw-space-x-3 tw-py-2">
            <Checkbox
              id="is_Daily"
              v-model="form.is_Daily"
              :binary="true"
              :disabled="isCheckboxDisabled"
              class="tw-mr-2"
            />
            <label for="is_Daily" class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-cursor-pointer">
              Is Daily?
            </label>
          </div>
          <div class="tw-space-y-2" v-if="form.is_Daily">
            <label for="importance" class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
              Importance *
            </label>
            <Dropdown
              id="importance"
              v-model="form.importance"
              :options="importanceLevels"
              optionLabel="label"
              optionValue="value"
              placeholder="Select importance level"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.importance }"
            />
          </div>

          <div class="tw-space-y-2">
            <label for="notes" class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700">
              Notes
            </label>
            <Textarea
              id="notes"
              v-model="form.notes"
              rows="4"
              class="tw-w-full tw-resize-none"
              placeholder="Add any additional notes..."
            />
          </div>

          <div class="tw-flex tw-justify-end tw-space-x-3 tw-pt-4 tw-border-t tw-border-gray-200">
            <Button
              type="button"
              label="Cancel"
              severity="secondary"
              outlined
              @click="closeModal"
              class="tw-px-6 tw-py-2"
            />
            <Button
              type="submit"
              :label="editMode ? 'Update Waitlist' : 'Create Waitlist'"
              severity="primary"
              class="tw-px-6 tw-py-2"
              :loading="false"
            />
          </div>
        </Form>
      </template>
    </Card>
  </Dialog>
</template>

<style scoped>
/* PrimeVue Dialog Customizations */
:deep(.p-dialog) {
  @apply font-sans;
}

:deep(.p-dialog-header) {
  @apply bg-gradient-to-r tw-from-blue-600 tw-to-blue-700 tw-text-white;
}

:deep(.p-dialog-title) {
  @apply font-bold tw-text-lg;
}

:deep(.p-dialog-header-icon) {
  @apply text-white hover:tw-bg-blue-800;
}

:deep(.p-dialog-content) {
  @apply p-6;
}

/* Form Input Styling */
:deep(.p-dropdown) {
  @apply border-gray-300 focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200;
}

:deep(.p-dropdown:not(.p-disabled):hover) {
  @apply border-blue-400;
}

:deep(.p-textarea) {
  @apply border-gray-300 focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200;
}

:deep(.p-textarea:hover) {
  @apply border-blue-400;
}

:deep(.p-checkbox .p-checkbox-box) {
  @apply border-gray-300 focus:tw-border-blue-500 focus:tw-ring-2 focus:tw-ring-blue-200;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
  @apply bg-blue-600 tw-border-blue-600;
}

/* Button Styling */
:deep(.p-button.p-button-primary) {
  @apply bg-blue-600 hover:tw-bg-blue-700 tw-border-blue-600 hover:tw-border-blue-700;
}

:deep(.p-button.p-button-secondary) {
  @apply text-gray-600 tw-border-gray-300 hover:tw-bg-gray-50;
}

/* Error States */
:deep(.p-invalid) {
  @apply border-red-500 focus:tw-border-red-500 focus:tw-ring-red-200;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  :deep(.p-dialog) {
    @apply m-4;
  }
  
  :deep(.p-dialog-content) {
    @apply p-4;
  }
}
</style>