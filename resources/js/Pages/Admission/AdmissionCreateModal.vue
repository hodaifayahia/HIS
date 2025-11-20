<template>
  <Dialog v-model:visible="showDialog" :modal="true" :header="null" :style="{ width: '100%', maxWidth: '900px' }" class="tw-rounded-2xl tw-overflow-hidden" @keydown.esc="closeModal">
    <!-- Enhanced Header with Gradient Background - Matching Admission List Page -->
    <template #header>
      <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-shadow-lg tw-backdrop-blur-sm tw--m-6 tw-mb-0 tw-w-screen tw--ml-6">
        <div class="tw-px-6 tw-py-6 tw-flex tw-items-center tw-justify-between">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-w-12 tw-h-12 tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-xl tw-flex tw-items-center tw-justify-center tw-shadow-lg">
              <i :class="isEditMode ? 'bi bi-pencil-square' : 'bi bi-plus-circle-fill'" class="tw-text-white tw-text-xl"></i>
            </div>
            <div>
              <h2 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-1">
                {{ isEditMode ? 'Edit Admission' : 'Create New Admission' }}
              </h2>
              <p class="tw-text-slate-600 tw-text-sm">
                {{ isEditMode ? 'Update admission details and information' : 'Register a new patient admission to the system' }}
              </p>
            </div>
          </div>
          <Button
            icon="pi pi-times"
            @click="closeModal"
            severity="secondary"
            text
            rounded
            class="tw-w-10 tw-h-10 tw-flex tw-items-center tw-justify-center hover:tw-bg-gray-200/40 tw-transition-colors"
            v-tooltip="'Close (ESC)'"
          />
        </div>
      </div>
    </template>

    <form @submit.prevent="submitForm" class="tw-bg-gradient-to-br tw-from-white tw-via-slate-50/30 tw-to-blue-50/20">
      <!-- Tabs for organizing form fields -->
      <TabView class="tw-p-6">
        <!-- Tab 1: Patient & Basic Info -->
        <TabPanel header="Patient & Admission Type">
          <div class="tw-space-y-6">
            <!-- Patient Selection -->
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

            <!-- File Number (Auto-generated) -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-file tw-text-indigo-600"></i>File Number
                <Tag v-if="isEditMode && editingAdmission?.file_number_verified" severity="success" class="tw-ml-2">
                  <i class="pi pi-check-circle tw-mr-1"></i>Verified
                </Tag>
                <Tag v-else-if="isEditMode && editingAdmission" severity="warning" class="tw-ml-2">
                  <i class="pi pi-clock tw-mr-1"></i>Unverified
                </Tag>
              </label>
              <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-rounded-lg tw-p-3 tw-flex tw-items-center tw-gap-3">
                <i class="pi pi-file tw-text-green-600 tw-text-xl"></i>
                <div class="tw-flex-1">
                  <p class="tw-text-sm tw-text-green-700 tw-font-medium tw-m-0">Auto-generated File Number</p>
                  <p v-if="!isEditMode && nextFileNumber" class="tw-text-xs tw-text-green-600 tw-m-0 tw-font-mono tw-text-lg">
                    <i class="pi pi-arrow-right tw-mr-1"></i>{{ nextFileNumber }}
                  </p>
                  <p v-else-if="!isEditMode" class="tw-text-xs tw-text-gray-500 tw-m-0">
                    <i class="pi pi-spin pi-spinner tw-mr-1"></i>Loading next number...
                  </p>
                  <div v-else class="tw-flex tw-items-center tw-gap-2 tw-mt-2">
                    <InputText 
                      v-model="form.file_number" 
                      class="tw-font-mono tw-text-sm"
                      :readonly="editingAdmission?.file_number_verified"
                      placeholder="YYYY/number"
                    />
                    <Button 
                      v-if="editingAdmission && !editingAdmission.file_number_verified && form.file_number"
                      icon="pi pi-check" 
                      label="Verify"
                      severity="success"
                      size="small"
                      @click="verifyFileNumber"
                      :loading="loading"
                    />
                  </div>
                </div>
              </div>
              <small v-if="isEditMode && editingAdmission?.file_number_verified" class="tw-text-green-600 tw-block tw-text-xs">
                <i class="pi pi-lock tw-mr-1"></i>Verified file numbers cannot be edited
              </small>
              <small v-else-if="isEditMode" class="tw-text-amber-600 tw-block tw-text-xs">
                <i class="pi pi-info-circle tw-mr-1"></i>Can be edited until verified
              </small>
            </div>

            <!-- Admission Type -->
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
          </div>
        </TabPanel>

        <!-- Tab 2: Doctor & Medical Info -->
        <TabPanel header="Doctor & Medical Details">
          <div class="tw-space-y-6">
            <!-- Company/Organisme (Optional) - Surgery Only -->
            <Transition name="fade">
              <div v-if="form.type === 'surgery'" class="tw-space-y-2">
                <div class="tw-flex tw-items-center tw-justify-between">
                  <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                    <i class="pi pi-building tw-text-cyan-600"></i>Company/Insurance (Optional)
                  </label>
                  <Button
                    @click="showConventionModal = true"
                    severity="info"
                    size="small"
                    icon="pi pi-link"
                    label="Use Convention"
                    outlined
                    type="button"
                  />
                </div>
                <Dropdown
                  v-model="form.company_id"
                  :options="companies"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select company or insurance"
                  :loading="loadingCompanies"
                  class="tw-w-full"
                  filter
                  showClear
                />
                <small v-if="errors.company_id" class="tw-text-red-600 tw-block tw-text-xs">
                  <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.company_id[0] }}
                </small>
              </div>
            </Transition>

            <!-- Doctor Selection -->
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

            <!-- Initial Prestation (Surgery Only) -->
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

            <!-- Observation/Notes -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-comment tw-text-orange-600"></i>Observation/Notes
              </label>
              <Dropdown
                v-model="form.observation"
                :options="observationTypes"
                optionLabel="label"
                optionValue="value"
                placeholder="Select observation type"
                class="tw-w-full"
                showClear
              />
              <small v-if="errors.observation" class="tw-text-red-600 tw-block tw-text-xs">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.observation[0] }}
              </small>
            </div>

            <!-- Custom Observation (when 'other' is selected) -->
            <Transition name="fade">
              <div v-if="form.observation === 'other'" class="tw-space-y-2">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                  <i class="pi pi-pencil tw-text-orange-600"></i>Custom Observation
                </label>
                <Textarea
                  v-model="customObservation"
                  rows="3"
                  placeholder="Enter custom observation..."
                  class="tw-w-full"
                />
              </div>
            </Transition>
          </div>
        </TabPanel>

        <!-- Tab 3: Companion & Relationships -->
        <TabPanel header="Companion & Relations">
          <div class="tw-space-y-6">
            <!-- Companion Selection -->
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

            <!-- Relation Type (if companion selected) -->
            <Transition name="fade">
              <div v-if="form.companion_id" class="tw-space-y-2">
                <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                  <i class="pi pi-users tw-text-teal-600"></i>Relation Type
                </label>
                <Dropdown
                  v-model="form.relation_type"
                  :options="relationTypes"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select relationship"
                  class="tw-w-full"
                  showClear
                />
                <small v-if="errors.relation_type" class="tw-text-red-600 tw-block tw-text-xs">
                  <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.relation_type[0] }}
                </small>
              </div>
            </Transition>

            <!-- Social Security Number -->
            <div class="tw-space-y-2">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700">
                <i class="pi pi-id-card tw-text-pink-600"></i>Social Security Number
              </label>
              <InputText
                v-model="form.social_security_num"
                placeholder="Enter social security number"
                class="tw-w-full"
              />
              <small v-if="errors.social_security_num" class="tw-text-red-600 tw-block tw-text-xs">
                <i class="pi pi-exclamation-circle tw-mr-1"></i>{{ errors.social_security_num[0] }}
              </small>
            </div>
          </div>
        </TabPanel>
      </TabView>
    </form>

    <template #footer>
      <div class="tw-bg-gradient-to-r tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20 tw-border-t tw-border-slate-200/60 tw-px-6 tw-py-4">
        <div class="tw-flex tw-justify-end tw-gap-3">
          <Button
            label="Cancel"
            severity="secondary"
            @click="closeModal"
            class="tw-px-6 tw-py-2.5 tw-bg-white tw-border tw-border-slate-300 tw-text-slate-700 hover:tw-bg-slate-50 tw-rounded-lg tw-font-medium tw-transition-colors"
            outlined
          />
          <Button
            :label="isEditMode ? 'Update Admission' : 'Create Admission'"
            :icon="isEditMode ? 'bi bi-check-circle-fill' : 'bi bi-plus-circle-fill'"
            severity="success"
            @click="submitForm"
            :loading="loading"
            class="tw-px-6 tw-py-2.5 tw-bg-gradient-to-r tw-from-blue-500 tw-to-indigo-600 hover:tw-from-blue-600 hover:tw-to-indigo-700 tw-text-white tw-rounded-lg tw-font-medium tw-transition-all tw-shadow-lg hover:tw-shadow-xl"
          />
        </div>
      </div>
    </template>
  </Dialog>

  <!-- ConventionManagement Modal -->
  <ConventionManagement
    v-model:visible="showConventionModal"
    :ficheNavetteId="currentFicheNavette?.id || null"
    :patientId="form.patient_id"
    @convention-items-added="onConventionItemsAdded"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import { useToastr } from '@/Components/toster'
import { AdmissionService } from '@/services/admissionService'
import PatientSearch from '@/Pages/Appointments/PatientSearch.vue'
import CompanionSearch from '@/Components/CompanionSearch.vue'
import PrestationSearch from '@/Components/PrestationSearch.vue'
import ConventionManagement from '@/Components/Apps/Emergency/FicheNavatteItem/ConventionManagement.vue'
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
  file_number: '',
  observation: '',
  company_id: '',
  social_security_num: '',
  relation_type: '',
})

const loading = ref(false)
const errors = ref({})
const creatingFiche = ref(false)
const loadingDoctors = ref(false)
const showConventionModal = ref(false)

const doctors = ref([])
const companies = ref([])
const relationTypes = ref([])
const loadingCompanies = ref(false)

const observationTypes = ref([
  { value: 'Hotellerie', label: 'Hotellerie' },
  { value: 'Hotellerie pour MAPA (Holter Tensionnel)', label: 'Hotellerie pour MAPA (Holter Tensionnel)' },
  { value: 'Hotellerie Holter ECG', label: 'Hotellerie Holter ECG' },
  { value: 'Non Hospit. - Admis(e) & Sortie par Pavillon des Urgences', label: 'Non Hospit. - Admis(e) & Sortie par Pavillon des Urgences' },
  { value: 'Non Hospit. - Admis(e), Opéré(e), Réglé(e) & Sortie par Pavillon des Urgences', label: 'Non Hospit. - Admis(e), Opéré(e), Réglé(e) & Sortie par Pavillon des Urgences' },
  { value: 'Non Hospit. - Evacuation - *vers autre(s) structure(s)*', label: 'Non Hospit. - Evacuation - *vers autre(s) structure(s)*' },
  { value: 'Sortie Contre Avis Médical', label: 'Sortie Contre Avis Médical' },
  { value: 'Non Hospit. - Sortie Contre Avis Médical', label: 'Non Hospit. - Sortie Contre Avis Médical' },
  { value: 'P. E. C. (Voir Direction)', label: 'P. E. C. (Voir Direction)' },
  { value: 'other', label: 'Other (Custom)' },
])

const customObservation = ref('')

const selectedPatient = ref(null)
const selectedDoctor = ref(null)
const selectedPrestation = ref(null)
const selectedCompanion = ref(null)
const currentFicheNavette = ref(null)
const nextFileNumber = ref('')

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

const loadCompanies = async () => {
  loadingCompanies.value = true
  try {
    const response = await axios.get('/api/organismes')
    companies.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to load companies:', error)
  } finally {
    loadingCompanies.value = false
  }
}

const loadRelationTypes = async () => {
  try {
    const response = await axios.get('/api/config/relation-types')
    relationTypes.value = response.data || []
  } catch (error) {
    // Fallback to hardcoded list
    relationTypes.value = [
      { value: 'father', label: 'Father' },
      { value: 'mother', label: 'Mother' },
      { value: 'spouse', label: 'Spouse' },
      { value: 'son', label: 'Son' },
      { value: 'daughter', label: 'Daughter' },
      { value: 'brother', label: 'Brother' },
      { value: 'sister', label: 'Sister' },
      { value: 'friend', label: 'Friend' },
      { value: 'nurse', label: 'Nurse' },
      { value: 'escort', label: 'Escort' },
    ]
  }
}

const loadNextFileNumber = async () => {
  try {
    const response = await AdmissionService.getNextFileNumber()
    nextFileNumber.value = response.data.data.next_file_number
  } catch (error) {
    console.error('Failed to load next file number:', error)
    nextFileNumber.value = ''
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

const autoSelectPrestationFromConvention = async () => {
  // For surgery type, try to auto-select first prestation from convention if available
  if (form.value.type !== 'surgery' || !selectedDoctor.value || !form.value.company_id) {
    return
  }

  try {
    // Get doctor's convention prestations
    const response = await axios.get('/api/conventions/doctor-prestations', {
      params: {
        doctor_id: form.value.doctor_id,
        company_id: form.value.company_id
      }
    })
    
    if (response.data.data && response.data.data.length > 0) {
      // Auto-select first prestation from convention
      const firstPrestationData = response.data.data[0]
      
      // Handle both nested and flat prestation structures
      const prestationId = firstPrestationData.id || firstPrestationData.prestation_id
      const prestationName = firstPrestationData.name || firstPrestationData.prestation_name
      const prestationCode = firstPrestationData.code || firstPrestationData.prestation_code
      
      form.value.initial_prestation_id = prestationId
      
      // Create normalized prestation object
      selectedPrestation.value = {
        id: prestationId,
        name: prestationName,
        code: prestationCode,
        ...firstPrestationData,
        // Ensure nested prestation data is also accessible
        prestation: firstPrestationData.prestation || null
      }
      
      prestationSearchValue.value = prestationName
      
      console.log('Auto-selected prestation:', selectedPrestation.value)
    }
  } catch (error) {
    // Silently fail - user can manually select if needed
    console.log('Could not auto-select prestation from convention:', error)
  }
}

const onDoctorChange = async () => {
  selectedDoctor.value = doctors.value.find(d => d.id == form.value.doctor_id) || null
  
  // For surgery type, try to auto-select prestation from convention
  if (form.value.type === 'surgery' && form.value.company_id) {
    await autoSelectPrestationFromConvention()
  } else {
    // For nursing type or when no company, clear prestation selection
    form.value.initial_prestation_id = ''
    selectedPrestation.value = null
    prestationSearchValue.value = ''
  }
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
    // Auto-open convention modal for surgery
    showConventionModal.value = true
  }
}

const onConventionItemsAdded = async (data) => {
  // Auto-populate from convention selection
  console.log('Convention items added data:', data);
  
  if (!data) {
    console.warn('No data received from convention modal')
    showConventionModal.value = false
    return
  }
  
  // Set doctor if available
  if (data.doctor_id && doctors.value.length > 0) {
    form.value.doctor_id = data.doctor_id
    selectedDoctor.value = doctors.value.find(d => d.id === data.doctor_id) || null
  }
  
  // Set company if available
  if (data.company_id) {
    form.value.company_id = data.company_id
  }
  
  // Handle prestations from convention data
  if (data.prestations && data.prestations.length > 0 && form.value.type === 'surgery') {
    // Get the first prestation
    const firstPrestation = data.prestations[0]
    
    // Extract ID with multiple fallback options
    const prestationId = firstPrestation.id || 
                        firstPrestation.prestation_id || 
                        (firstPrestation.prestation?.id)
    
    // Extract name with multiple fallback options
    const prestationName = firstPrestation.name || 
                          firstPrestation.prestation_name || 
                          firstPrestation.prestation?.name
    
    // Extract code with multiple fallback options
    const prestationCode = firstPrestation.code || 
                          firstPrestation.prestation_code || 
                          firstPrestation.prestation?.code
    
    if (prestationId) {
      form.value.initial_prestation_id = prestationId
      
      // Create normalized prestation object merging all properties
      selectedPrestation.value = {
        id: prestationId,
        name: prestationName,
        code: prestationCode,
        ...firstPrestation,
        // Ensure nested prestation data is also accessible
        prestation: firstPrestation.prestation || firstPrestation
      }
      
      prestationSearchValue.value = prestationName || ''
      
      console.log('Selected prestation from convention:', selectedPrestation.value)
    }
  }
  
  showConventionModal.value = false
}

const submitForm = async () => {
  errors.value = {}
  loading.value = true

  try {
    let response
    
    // Use custom observation if 'other' is selected
    const formData = { ...form.value }
    if (formData.observation === 'other') {
      formData.observation = customObservation.value
    }
    
    if (isEditMode.value && editingAdmission.value) {
      // Update existing admission
      response = await AdmissionService.updateAdmission(editingAdmission.value.id, formData)
      toastr.success('Admission updated successfully')
    } else {
      // Create new admission
      response = await AdmissionService.createAdmission(formData)
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
      file_number: admission.file_number || '',
      observation: admission.observation || '',
      company_id: admission.company_id || '',
      social_security_num: admission.social_security_num || '',
      relation_type: admission.relation_type || '',
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
  
  // Check if observation is a custom value (not in predefined list)
  if (admission.observation && !observationTypes.value.find(o => o.value === admission.observation)) {
    customObservation.value = admission.observation
    form.value.observation = 'other'
  }  } else {
    // Create mode
    isEditMode.value = false
    editingAdmission.value = null
    resetForm()
    // Load next file number for create mode
    await loadNextFileNumber()
  }
  
  // Auto-open convention modal if surgery type is selected
  if (form.value.type === 'surgery') {
    showConventionModal.value = true
  }
  
  // Load doctors if needed
  if (doctors.value.length === 0) {
    loadDoctors()
  }
  
  // Load companies and relation types
  if (companies.value.length === 0) {
    loadCompanies()
  }
  if (relationTypes.value.length === 0) {
    loadRelationTypes()
  }
}

const verifyFileNumber = async () => {
  try {
    if (!editingAdmission.value || !form.value.file_number) {
      toastr.warning('No file number to verify')
      return
    }
    
    loading.value = true
    await AdmissionService.verifyFileNumber(editingAdmission.value.id)
    
    toastr.success('File number verified successfully')
    editingAdmission.value.file_number_verified = true
  } catch (error) {
    toastr.error(error.response?.data?.message || 'Failed to verify file number')
  } finally {
    loading.value = false
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
    file_number: '',
    observation: '',
    company_id: '',
    social_security_num: '',
    relation_type: '',
  }
  selectedPatient.value = null
  selectedDoctor.value = null
  selectedPrestation.value = null
  selectedCompanion.value = null
  currentFicheNavette.value = null
  customObservation.value = ''
  nextFileNumber.value = ''
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
