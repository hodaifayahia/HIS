<template>
  <Dialog v-model:visible="showDialog" :modal="true" :header="null" :style="{ width: '100%', maxWidth: '750px' }" class="tw-rounded-2xl tw-overflow-hidden">
    <!-- Custom Header with Gradient -->
    <template #header>
      <div class="tw-bg-gradient-to-r tw-from-indigo-600 tw-to-purple-700 tw-p-6 tw--m-6 tw-mb-0">
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-bg-white/20 tw-p-3 tw-rounded-xl tw-backdrop-blur-sm">
            <i :class="isEditMode ? 'pi pi-pencil' : 'pi pi-plus-circle'" class="tw-text-2xl tw-text-white"></i>
          </div>
          <div>
            <h2 class="tw-text-2xl tw-font-bold tw-text-white">{{ isEditMode ? 'Edit Admission' : 'Create New Admission' }}</h2>
            <p class="tw-text-blue-100 tw-text-sm tw-mt-1">{{ isEditMode ? 'Update admission details' : 'Register a new patient admission to the system' }}</p>
          </div>
        </div>
      </div>
    </template>

    <form @submit.prevent="submitForm" class="tw-space-y-6 tw-p-6">
      <!-- Patient Selection using PatientSearch Component -->
      <div class="tw-space-y-2">
        <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
          <i class="pi pi-user tw-text-blue-600"></i>Patient
          <span class="tw-text-red-600">*</span>
        </label>
        <PatientSearch
          :modelValue="patientSearchValue"
          @update:modelValue="patientSearchValue = $event"
          @patientSelected="selectPatient"
          :patientId="isEditMode && editingAdmission?.patient_id ? editingAdmission.patient_id : null"
          placeholder="Search patient by name or phone..."
        />
        <div v-if="form.patient_id && selectedPatient" class="tw-p-3 tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-check-circle tw-text-green-600 tw-text-lg"></i>
          <div>
            <strong class="tw-text-green-700">Selected:</strong>
            <span class="tw-text-green-900 tw-ml-2">{{ selectedPatient.first_name || '' }} {{ selectedPatient.last_name || '' }}</span>
          </div>
        </div>
        <small v-if="errors.patient_id" class="tw-text-red-600 tw-block tw-text-xs">
          <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.patient_id[0] }}
        </small>
      </div>

      <!-- Admission Type (MOVED UP) -->
      <div class="tw-space-y-3">
        <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
          <i class="pi pi-building tw-text-amber-600"></i>Admission Type
          <span class="tw-text-red-600">*</span>
        </label>
        <div class="tw-grid tw-grid-cols-2 tw-gap-3">
          <label class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-border tw-border-amber-200 tw-rounded-lg tw-cursor-pointer hover:tw-bg-amber-50 tw-transition-colors" :class="{ 'tw-bg-amber-50 tw-border-amber-500': form.type === 'surgery' }">
            <input
              type="radio"
              name="type"
              value="surgery"
              v-model="form.type"
              @change="onTypeChange"
              class="tw-w-4 tw-h-4"
            />
            <div class="tw-flex-1">
              <div class="tw-font-medium tw-text-gray-800">
                <i class="pi pi-shield tw-mr-2"></i>Surgery (Upfront)
              </div>
              <small class="tw-text-gray-600">Surgical intervention</small>
            </div>
          </label>
          <label class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-border tw-border-green-200 tw-rounded-lg tw-cursor-pointer hover:tw-bg-green-50 tw-transition-colors" :class="{ 'tw-bg-green-50 tw-border-green-500': form.type === 'nursing' }">
            <input
              type="radio"
              name="type"
              value="nursing"
              v-model="form.type"
              @change="onTypeChange"
              class="tw-w-4 tw-h-4"
            />
            <div class="tw-flex-1">
              <div class="tw-font-medium tw-text-gray-800">
                <i class="pi pi-heart tw-mr-2"></i>Nursing (Pay After)
              </div>
              <small class="tw-text-gray-600">Medical care</small>
            </div>
          </label>
        </div>
        <small v-if="errors.type" class="tw-text-red-600 tw-block tw-text-xs">
          <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.type[0] }}
        </small>
      </div>

      <!-- Doctor Selection (Dropdown) - Required for Both Types -->
      <div class="tw-space-y-2">
        <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
          <i class="pi pi-user-md tw-text-indigo-600"></i>Doctor
          <span class="tw-text-red-600">*</span>
        </label>
        <Dropdown
          v-model="form.doctor_id"
          :options="doctorsWithLabel"
          optionLabel="doctorLabel"
          optionValue="id"
          placeholder="-- Select Doctor --"
          @change="onDoctorChange"
          :loading="loadingDoctors"
          class="tw-w-full"
          :class="{ 'p-invalid': errors.doctor_id }"
        >
          <template #option="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-user-md tw-text-indigo-500"></i>
              <div>
                <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                <small class="tw-text-gray-600">{{ slotProps.option.specialization }}</small>
              </div>
            </div>
          </template>
          <template #value="slotProps">
            <div v-if="slotProps.value && selectedDoctor" class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-user-md tw-text-indigo-500"></i>
              <div>
                <div class="tw-font-medium">{{ selectedDoctor.name }}</div>
                <small class="tw-text-gray-600">{{ selectedDoctor.specialization }}</small>
              </div>
            </div>
            <span v-else class="tw-text-gray-500">-- Select Doctor --</span>
          </template>
        </Dropdown>
        <small v-if="errors.doctor_id" class="tw-text-red-600 tw-block tw-text-xs">
          <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.doctor_id[0] }}
        </small>
      </div>

      <!-- Companion Selection (Optional) -->
      <div class="tw-space-y-2">
        <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
          <i class="pi pi-user-plus tw-text-purple-600"></i>Companion (Optional)
        </label>
        <CompanionSearch
          :modelValue="companionSearchValue"
          @update:modelValue="companionSearchValue = $event"
          @companionSelected="selectCompanion"
          :excludePatientId="form.patient_id"
          placeholder="Search companion by name or phone..."
        />
        <div v-if="form.companion_id && selectedCompanion" class="tw-p-3 tw-bg-purple-50 tw-border tw-border-purple-200 tw-rounded-lg tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-check-circle tw-text-purple-600 tw-text-lg"></i>
          <div>
            <strong class="tw-text-purple-700">Selected Companion:</strong>
            <span class="tw-text-purple-900 tw-ml-2">{{ selectedCompanion.first_name || '' }} {{ selectedCompanion.last_name || '' }}</span>
          </div>
        </div>
        <small v-if="errors.companion_id" class="tw-text-red-600 tw-block tw-text-xs">
          <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.companion_id[0] }}
        </small>
      </div>

      <!-- Initial Prestation (Surgery Only) using PrestationSearch Component -->
      <Transition name="fade">
        <div v-if="form.type === 'surgery'" class="tw-space-y-2">
          <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
            <i class="pi pi-plus-circle tw-text-emerald-600"></i>Initial Prestation
            <span class="tw-text-red-600">*</span>
          </label>
          <div v-if="!selectedDoctor" class="tw-p-3 tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-info-circle tw-text-amber-600"></i>
            <span class="tw-text-sm tw-text-amber-800">Please select a doctor first to view available prestations</span>
          </div>
          <PrestationSearch
            v-else
            :modelValue="prestationSearchValue"
            @update:modelValue="prestationSearchValue = $event"
            @prestationSelected="selectPrestation"
            :placeholder="`Search prestation for ${selectedDoctor.specialization}...`"
            :specializationFilter="selectedDoctor.specialization?.id"
          />
          <div v-if="form.initial_prestation_id && selectedPrestation" class="tw-p-3 tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-check-circle tw-text-green-600 tw-text-lg"></i>
            <div>
              <strong class="tw-text-green-700">Selected:</strong>
              <span class="tw-text-green-900 tw-ml-2">{{ selectedPrestation.name }} ({{ selectedPrestation.price_with_vat_and_consumables_variant }} DA)</span>
            </div>
          </div>
          <small v-if="errors.initial_prestation_id" class="tw-text-red-600 tw-block tw-text-xs">
            <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.initial_prestation_id[0] }}
          </small>
        </div>
      </Transition>

      <!-- Fiche Navette Info (Auto-created) -->
      <Transition name="fade">
        <div v-if="currentFicheNavette" class="tw-p-4 tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-border tw-border-blue-200 tw-rounded-lg tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-file-pdf tw-text-2xl tw-text-blue-600"></i>
          <div>
            <strong class="tw-text-blue-700 tw-block">Consultation Reference:</strong>
            <span class="tw-text-blue-900 tw-text-sm">{{ currentFicheNavette.reference_number || 'Auto-created for today' }}</span>
          </div>
        </div>
      </Transition>
      <Transition name="fade">
        <div v-if="creatingFiche && !currentFicheNavette" class="tw-p-4 tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-spin pi-spinner tw-text-xl tw-text-gray-600"></i>
          <span class="tw-text-gray-700 tw-font-medium">Creating consultation reference...</span>
        </div>
      </Transition>
    </form>

    <template #footer>
      <div class="tw-flex tw-justify-end tw-gap-2 tw-p-6 tw-border-t tw-border-gray-200">
        <Button label="Cancel" severity="secondary" @click="closeModal" class="tw-px-6" />
        <Button
          :label="isEditMode ? 'Update Admission' : 'Create Admission'"
          :icon="isEditMode ? 'pi pi-check' : 'pi pi-plus'"
          severity="success"
          @click="submitForm"
          :loading="loading"
          class="tw-px-6"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import { useToastr } from '@/Components/toster'
import { AdmissionService } from '@/services/admissionService'
import PatientSearch from '@/Pages/Appointments/PatientSearch.vue'
import CompanionSearch from '@/Components/CompanionSearch.vue'
import PrestationSearch from '@/Components/PrestationSearch.vue'
import axios from 'axios'

const props = defineProps({
  editMode: {
    type: Boolean,
    default: false
  },
  admissionData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['saved', 'cancelled'])

const router = useRouter()
const toastr = useToastr()

const showDialog = ref(false)
const isEditMode = ref(false)
const editingAdmission = ref(null)

const form = ref({
  patient_id: '',
  doctor_id: '',
  companion_id: '',
  type: 'nursing',
  initial_prestation_id: '',
  fiche_navette_id: '',
})

const loading = ref(false)
const errors = ref({})
const creatingFiche = ref(false)
const loadingDoctors = ref(false)

const doctors = ref([])

const selectedPatient = ref(null)
const selectedDoctor = ref(null)
const selectedPrestation = ref(null)
const selectedCompanion = ref(null)
const currentFicheNavette = ref(null)

const patientSearchValue = ref('')
const companionSearchValue = ref('')
const prestationSearchValue = ref('')

// Computed property to add labels for doctor dropdown
const doctorsWithLabel = computed(() => {
  return doctors.value.map(doctor => ({
    ...doctor,
    doctorLabel: `${doctor.name || 'Unknown'} (${doctor.specialization || 'General'})`
  }))
})

const loadDoctors = async () => {
  loadingDoctors.value = true
  try {
    const response = await axios.get('/api/doctors')
    doctors.value = response.data.data || response.data || []
    console.log('Doctors loaded:', doctors.value)
  } catch (error) {
    console.error('Failed to load doctors:', error)
    toastr.error('Failed to load doctors')
  } finally {
    loadingDoctors.value = false
  }
}

const getOrCreateTodayFicheNavette = async (patientId) => {
  if (!patientId) return null
  
  try {
    creatingFiche.value = true
    // Check if patient has a fiche navette for today
    const response = await axios.get('/api/reception/fiche-navette', {
      params: {
        patient_id: patientId,
        date: new Date().toISOString().split('T')[0] // Today's date
      }
    })
    
    // If exists, use it
    if (response.data.data && response.data.data.length > 0) {
      currentFicheNavette.value = response.data.data[0]
      form.value.fiche_navette_id = currentFicheNavette.value.id
      return currentFicheNavette.value
    }
    
    // If not, create one
    const createResponse = await axios.post('/api/reception/fiche-navette', {
      patient_id: patientId,
      status: 'pending',
      created_at: new Date().toISOString()
    })
    
    currentFicheNavette.value = createResponse.data.data
    form.value.fiche_navette_id = currentFicheNavette.value.id
    return currentFicheNavette.value
  } catch (error) {
    console.error('Error getting/creating fiche navette:', error)
    // Don't show error to user as fiche navette is optional
    return null
  } finally {
    creatingFiche.value = false
  }
}

const selectPatient = async (patient) => {
  form.value.patient_id = patient.id
  selectedPatient.value = patient
  patientSearchValue.value = `${patient.first_name} ${patient.last_name}`
  
  // Auto-create or get today's fiche navette for this patient
  await getOrCreateTodayFicheNavette(patient.id)
}

const selectCompanion = (companion) => {
  form.value.companion_id = companion.id
  selectedCompanion.value = companion
  companionSearchValue.value = `${companion.first_name} ${companion.last_name}`
}

const selectPrestation = (prestation) => {
  form.value.initial_prestation_id = prestation.id
  selectedPrestation.value = prestation
  prestationSearchValue.value = prestation.name
}

const onDoctorChange = () => {
  selectedDoctor.value = doctors.value.find(d => d.id == form.value.doctor_id) || null
  // Clear prestation selection when doctor changes
  form.value.initial_prestation_id = ''
  selectedPrestation.value = null
  prestationSearchValue.value = ''
}

const onTypeChange = () => {
  if (form.value.type === 'nursing') {
    // Clear prestation for nursing type
    form.value.initial_prestation_id = ''
    selectedPrestation.value = null
    prestationSearchValue.value = ''
    // Doctor is still required for nursing, so don't clear it
  } else if (form.value.type === 'surgery') {
    // Load doctors when switching to surgery
    if (doctors.value.length === 0) {
      loadDoctors()
    }
  }
}

const submitForm = async () => {
  errors.value = {}
  loading.value = true

  try {
    let response
    
    if (isEditMode.value && editingAdmission.value) {
      // Update existing admission
      response = await AdmissionService.updateAdmission(editingAdmission.value.id, form.value)
      toastr.success('Admission updated successfully')
    } else {
      // Create new admission
      response = await AdmissionService.createAdmission(form.value)
      toastr.success('Admission created successfully')
    }
    
    closeModal()
    emit('saved', response.data.data)
    
    // Navigate to admission details if it's a new admission
    if (!isEditMode.value) {
      router.push(`/admissions/${response.data.data.id}`)
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toastr.error(error.response?.data?.message || `Failed to ${isEditMode.value ? 'update' : 'create'} admission`)
    }
  } finally {
    loading.value = false
  }
}

const openModal = async (admission = null) => {
  showDialog.value = true
  
  if (admission) {
    // Edit mode
    isEditMode.value = true
    editingAdmission.value = admission
    
    // Populate form with admission data
    form.value = {
      patient_id: admission.patient_id,
      doctor_id: admission.doctor_id || '',
      companion_id: admission.companion_id || '',
      type: admission.type,
      initial_prestation_id: admission.initial_prestation_id || '',
      fiche_navette_id: admission.fiche_navette_id || '',
    }
    
    // Set selected objects - handle different property name cases
    if (admission.patient) {
      selectedPatient.value = {
        id: admission.patient.id,
        first_name: admission.patient.first_name || admission.patient.Firstname || '',
        last_name: admission.patient.last_name || admission.patient.Lastname || '',
        phone: admission.patient.phone || ''
      }
    } else {
      selectedPatient.value = null
    }
    
    selectedDoctor.value = admission.doctor || null
    selectedPrestation.value = admission.initialPrestation || null
    selectedCompanion.value = admission.companion || null
    
    // Set search values - make sure to use fallback values
    if (selectedPatient.value) {
      const firstName = selectedPatient.value.first_name || ''
      const lastName = selectedPatient.value.last_name || ''
      patientSearchValue.value = `${firstName} ${lastName}`.trim()
    } else {
      patientSearchValue.value = ''
    }
    prestationSearchValue.value = selectedPrestation.value ? 
      selectedPrestation.value.name : ''
    
    companionSearchValue.value = selectedCompanion.value ? 
      `${selectedCompanion.value.first_name} ${selectedCompanion.value.last_name}` : ''
    
    // Set current fiche navette if exists
    currentFicheNavette.value = admission.fiche_navette || null
    
  } else {
    // Create mode
    isEditMode.value = false
    editingAdmission.value = null
    resetForm()
  }
  
  // Load doctors if needed
  if (doctors.value.length === 0) {
    loadDoctors()
  }
}

const closeModal = () => {
  showDialog.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    patient_id: '',
    doctor_id: '',
    companion_id: '',
    type: 'nursing',
    initial_prestation_id: '',
    fiche_navette_id: '',
  }
  selectedPatient.value = null
  selectedDoctor.value = null
  selectedPrestation.value = null
  selectedCompanion.value = null
  currentFicheNavette.value = null
  patientSearchValue.value = ''
  companionSearchValue.value = ''
  prestationSearchValue.value = ''
  errors.value = {}
  creatingFiche.value = false
  isEditMode.value = false
  editingAdmission.value = null
}

defineExpose({
  openModal,
  closeModal,
})
</script>

<style scoped>
.tw-animate-fadeIn {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* PrimeVue Dialog customizations */
:deep(.p-dialog-header) {
  padding: 0;
  border: none;
}

:deep(.p-dialog-content) {
  padding: 0;
}

:deep(.p-dialog-footer) {
  padding: 0;
  border: none;
}

/* Dropdown styling */
:deep(.p-dropdown) {
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

:deep(.p-dropdown:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

:deep(.p-dropdown-trigger) {
  background-color: #f9fafb;
}

:deep(.p-dropdown-panel) {
  border-radius: 0.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-dropdown-item) {
  padding: 0.75rem 0.75rem;
  border-radius: 0.375rem;
}

:deep(.p-dropdown-item:hover) {
  background-color: #f0f9ff;
  color: #0369a1;
}

/* Button styling */
:deep(.p-button) {
  border-radius: 0.5rem;
  font-weight: 500;
}

:deep(.p-button-success) {
  background: linear-gradient(135deg, #10b981, #059669);
}

:deep(.p-button-success:hover) {
  background: linear-gradient(135deg, #059669, #047857);
}

:deep(.p-button-secondary) {
  background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
  color: #374151;
}

:deep(.p-button-secondary:hover) {
  background: linear-gradient(135deg, #e5e7eb, #d1d5db);
}

/* Input styling */
:deep(.p-inputtext) {
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

:deep(.p-inputtext:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
