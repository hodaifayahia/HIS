<template>
  <div class="admission-create">
    <div class="row">
      <div class="col-md-8 offset-md-2">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Create New Admission</h5>
          </div>

          <form @submit.prevent="submitForm" class="card-body">
            <!-- Patient Search -->
            <div class="mb-3">
              <label for="patient_search" class="form-label required">Search Patient</label>
              <input
                v-model="patientSearch"
                @input="searchPatients"
                type="text"
                id="patient_search"
                class="form-control"
                placeholder="Search by name, phone, ID number..."
                autocomplete="off"
              />
              
              <!-- Patient Search Results Dropdown -->
              <div v-if="patientSearch && filteredPatients.length > 0" class="list-group mt-2">
                <button
                  v-for="patient in filteredPatients"
                  :key="patient.id"
                  type="button"
                  @click="selectPatient(patient)"
                  class="list-group-item list-group-item-action"
                >
                  <div class="d-flex justify-content-between">
                    <span><strong>{{ patient.Firstname }} {{ patient.Lastname }}</strong></span>
                    <small class="text-muted">{{ patient.phone }}</small>
                  </div>
                  <small class="text-muted d-block">ID: {{ patient.Idnum }}</small>
                  <!-- Show today's fiche navette status if exists -->
                  <small v-if="patient.today_fiche_navette" class="text-success d-block">
                    <i class="bi bi-check-circle"></i> Today's Fiche: {{ patient.today_fiche_navette.reference }}
                  </small>
                </button>
              </div>

              <small v-if="patientSearch && filteredPatients.length === 0" class="text-danger d-block mt-2">
                No patients found
              </small>
            </div>

            <!-- Selected Patient Display -->
            <div v-if="form.patient_id" class="alert alert-info mb-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong>Selected Patient:</strong><br />
                  {{ selectedPatientData?.Firstname }} {{ selectedPatientData?.Lastname }}
                  <br />
                  <small class="text-muted">Phone: {{ selectedPatientData?.phone }} | ID: {{ selectedPatientData?.Idnum }}</small>
                  
                  <!-- Fiche Navette Info -->
                  <div v-if="selectedPatientData?.today_fiche_navette" class="mt-2 pt-2 border-top">
                    <i class="bi bi-info-circle"></i>
                    <strong class="text-success ms-1">Today's Fiche Navette Already Exists</strong>
                    <br />
                    <small>Reference: {{ selectedPatientData.today_fiche_navette.reference }}</small>
                    <br />
                    <small>Status: {{ selectedPatientData.today_fiche_navette.status }}</small>
                  </div>
                </div>
                <button type="button" @click="clearPatientSelection" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-x"></i> Clear
                </button>
              </div>
            </div>

            <small v-if="errors.patient_id" class="text-danger d-block mb-3">{{ errors.patient_id[0] }}</small>

            <!-- Doctor Selection -->
            <div class="mb-3">
              <label for="doctor_id" class="form-label">Doctor</label>
              <select v-model="form.doctor_id" id="doctor_id" class="form-select">
                <option value="">-- Select Doctor --</option>
                <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                  {{ doctor.user.name }}
                </option>
              </select>
            </div>

            <!-- Admission Type -->
            <div class="mb-3">
              <label class="form-label required">Admission Type</label>
              <div class="btn-group" role="group">
                <input
                  type="radio"
                  class="btn-check"
                  name="type"
                  id="type_surgery"
                  value="surgery"
                  v-model="form.type"
                />
                <label class="btn btn-outline-warning" for="type_surgery">
                  <i class="bi bi-bandaid"></i> Surgery (Upfront)
                </label>

                <input
                  type="radio"
                  class="btn-check"
                  name="type"
                  id="type_nursing"
                  value="nursing"
                  v-model="form.type"
                />
                <label class="btn btn-outline-success" for="type_nursing">
                  <i class="bi bi-heart-pulse"></i> Nursing (Pay After)
                </label>
              </div>
              <small v-if="errors.type" class="text-danger d-block">{{ errors.type[0] }}</small>
              <small class="d-block mt-2 text-muted">
                <i class="bi bi-info-circle"></i>
                <strong>Surgery:</strong> Requires upfront payment with initial prestation<br />
                <strong>Nursing:</strong> Pay-after-services (no default prestation)
              </small>
            </div>

            <!-- Initial Prestation (Surgery Only) -->
            <div v-if="form.type === 'surgery'" class="mb-3">
              <label for="initial_prestation_id" class="form-label required">Initial Prestation</label>
              <select v-model="form.initial_prestation_id" id="initial_prestation_id" class="form-select" required>
                <option value="">-- Select Prestation --</option>
                <option v-for="prestation in prestations" :key="prestation.id" :value="prestation.id">
                  {{ prestation.name }} - {{ prestation.internal_code }}
                </option>
              </select>
              <small v-if="errors.initial_prestation_id" class="text-danger">
                {{ errors.initial_prestation_id[0] }}
              </small>
            </div>

            <!-- Info Messages -->
            <div v-if="form.type === 'surgery'" class="alert alert-warning mb-3">
              <i class="bi bi-exclamation-triangle"></i>
              <strong>Surgery Admission:</strong> The selected initial prestation will be automatically added to the fiche navette.
            </div>

            <div v-if="form.type === 'nursing'" class="alert alert-info mb-3">
              <i class="bi bi-info-circle"></i>
              <strong>Nursing Admission:</strong> No default prestation will be added. Services will be added as needed.
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" :disabled="loading || !form.patient_id">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ loading ? 'Creating...' : 'Create Admission' }}
              </button>
              <router-link to="/admissions" class="btn btn-secondary">Cancel</router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { AdmissionService } from '@/services/admissionService'
import { useNotification } from '@/composables/useNotification'

const router = useRouter()
const { notify } = useNotification()

const form = ref({
  patient_id: '',
  doctor_id: '',
  type: 'nursing',
  initial_prestation_id: '',
})

const loading = ref(false)
const errors = ref({})

const patientSearch = ref('')
const allPatients = ref([])
const doctors = ref([])
const prestations = ref([])
const selectedPatientData = ref(null)

// Filter patients based on search input
const filteredPatients = computed(() => {
  if (!patientSearch.value.trim()) return []
  
  const searchTerm = patientSearch.value.toLowerCase()
  return allPatients.value.filter(patient => {
    const fullName = `${patient.Firstname} ${patient.Lastname}`.toLowerCase()
    return (
      fullName.includes(searchTerm) ||
      (patient.phone && patient.phone.toLowerCase().includes(searchTerm)) ||
      (patient.Idnum && patient.Idnum.toLowerCase().includes(searchTerm))
    )
  })
})

const searchPatients = async () => {
  if (!patientSearch.value.trim()) {
    return
  }

  try {
    const response = await axios.get('/api/patients/search', {
      params: { query: patientSearch.value }
    })
    allPatients.value = response.data
  } catch (error) {
    notify('error', 'Failed to search patients')
  }
}

const selectPatient = (patient) => {
  form.value.patient_id = patient.id
  selectedPatientData.value = patient
  patientSearch.value = ''
}

const clearPatientSelection = () => {
  form.value.patient_id = ''
  form.value.doctor_id = ''
  form.value.type = 'nursing'
  form.value.initial_prestation_id = ''
  selectedPatientData.value = null
  patientSearch.value = ''
}

const loadOptions = async () => {
  try {
    const [doctorsRes, prestationsRes] = await Promise.all([
      axios.get('/api/doctors?per_page=1000'),
      axios.get('/api/prestations?per_page=1000'),
    ])

    doctors.value = doctorsRes.data.data
    prestations.value = prestationsRes.data.data
  } catch (error) {
    notify('error', 'Failed to load options')
  }
}

const submitForm = async () => {
  errors.value = {}
  loading.value = true

  try {
    const response = await AdmissionService.createAdmission(form.value)
    notify('success', 'Admission created successfully. Fiche Navette has been automatically created/linked.')
    router.push(`/admissions/${response.data.data.id}`)
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      notify('error', error.response?.data?.message || 'Failed to create admission')
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadOptions()
})
</script>

<style scoped>
.admission-create {
  padding: 20px;
}

.form-label.required::after {
  content: ' *';
  color: red;
}

.btn-group .btn-check:checked + .btn {
  box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

.list-group {
  max-height: 300px;
  overflow-y: auto;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.list-group-item {
  border: 1px solid #dee2e6;
  transition: all 0.2s ease;
}

.list-group-item:hover {
  background-color: #f8f9fa;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
