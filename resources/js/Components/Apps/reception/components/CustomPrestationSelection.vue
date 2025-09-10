<script setup>
import { ref, computed, watch } from 'vue'
import Card from 'primevue/card'
import Dropdown from 'primevue/dropdown'
import MultiSelect from 'primevue/multiselect'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'

import { ficheNavetteService } from '../../../../Components/Apps/services/Reception/ficheNavetteService.js'

const props = defineProps({
  specializations: Array,
  allPrestations: Array,
  allDoctors: Array,
  loading: Boolean,
  prestationAppointments: Object,
  appointmentLoading: [String, Number]
})

const emit = defineEmits(['update:hasSelectedItems', 'takeAppointment', 'cancelAppointment', 'itemsToCreate', 'appointmentRequired'])

const toast = useToast()

// Reactive data
const searchTerm = ref('')
const selectedSpecializationsFilter = ref([])
const customSelectedPrestations = ref([])
const doctorsBySpecialization = ref([])
const selectedCustomNameOption = ref(null)
const customPrestationName = ref('')

// Custom name options
const customNameOptions = ref([
  { label: 'Laboratory', value: 'Laboratory' },
  { label: 'Consultation', value: 'Consultation' },
  { label: 'Radiology', value: 'Radiology' },
  { label: 'Cardiology', value: 'Cardiology' },
  { label: 'Neurology', value: 'Neurology' },
  { label: 'Dermatology', value: 'Dermatology' },
  { label: 'Pharmacy', value: 'Pharmacy' },
  { label: 'Emergency', value: 'Emergency' },
  { label: 'Surgery', value: 'Surgery' },
  { label: 'Physiotherapy', value: 'Physiotherapy' },
  { label: 'Other', value: 'other' }
])

// Computed properties
const filteredCustomPrestations = computed(() => {
  if (!props.allPrestations || props.allPrestations.length === 0) return []

  let filtered = [...props.allPrestations]

  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(p =>
      (p.name && p.name.toLowerCase().includes(search)) ||
      (p.internal_code && p.internal_code.toLowerCase().includes(search))
    )
  }

  if (selectedSpecializationsFilter.value.length > 0) {
    filtered = filtered.filter(p =>
      p.specialization_id &&
      selectedSpecializationsFilter.value.includes(p.specialization_id)
    )
  }

  return filtered
})

const shouldShowCustomNameInput = computed(() => {
  if (customSelectedPrestations.value.length < 2) {
    return false;
  }
  const specializationsInSelection = new Set(customSelectedPrestations.value.map(p => p.specialization_id));
  return specializationsInSelection.size > 1;
})

const nameToUse = computed(() => {
  if (selectedCustomNameOption.value === 'other') {
    return customPrestationName.value.trim() ? customPrestationName.value.trim() : null
  }
  return selectedCustomNameOption.value
})

const hasSelectedItems = computed(() => customSelectedPrestations.value.length > 0)

const customPrestationsNeedingAppointments = computed(() => {
  return customSelectedPrestations.value.filter(p => p.need_an_appointment === true)
})

const customPrestationsNotNeedingAppointments = computed(() => {
  return customSelectedPrestations.value.filter(p => p.need_an_appointment !== true)
})

const customTotalCost = computed(() => {
  return customSelectedPrestations.value.reduce((total, prestation) => {
    return total + parseFloat(prestation.public_price || prestation.price || 0)
  }, 0)
})

// Methods
const onSpecializationFilterChange = async () => {
  doctorsBySpecialization.value = []
  if (selectedSpecializationsFilter.value.length > 0) {
    try {
      const doctorPromises = selectedSpecializationsFilter.value.map(specId =>
        ficheNavetteService.getDoctorsBySpecialization(specId)
      )
      const results = await Promise.all(doctorPromises)
      const allDoctors = results.flatMap(result => result.success ? result.data : [])
      const uniqueDoctors = allDoctors.filter((doctor, index, self) =>
        index === self.findIndex(d => d.id === doctor.id)
      )
      doctorsBySpecialization.value = uniqueDoctors
    } catch (error) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load doctors', life: 3000 })
    }
  }
}
const formatAppointmentDateTime = (dateTime) => {
  if (!dateTime) {
    return 'No date available'
  }
  
  const date = new Date(dateTime)
  
  if (isNaN(date.getTime())) {
    console.warn('Invalid date value:', dateTime)
    return 'Invalid date'
  }
  
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }).format(date)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'DZD' }).format(amount || 0)
}

const getSpecializationName = (specializationId) => {
  const spec = props.specializations.find(s => s.id === specializationId)
  return spec ? spec.name : 'Unknown'
}

const getDoctorsForPrestation = (prestation) => {
  if (!props.allDoctors || props.allDoctors.length === 0) return []
  return props.allDoctors.filter(d => d.specialization_id === prestation.specialization_id)
}

const getDoctorName = (doctorId) => {
  const doctor = props.allDoctors.find(d => d.id === doctorId)
  return doctor ? doctor.name : 'Not assigned'
}

const takeAppointment = (prestation) => {
  emit('takeAppointment', prestation)
}

const cancelAppointment = (prestation) => {
  emit('cancelAppointment', prestation)
}

const createFicheNavette = () => {
  const data = {
    type: 'custom',
    customPrestations: customSelectedPrestations.value.map(p => ({
      ...p,
      display_name: nameToUse.value || p.name,
      type: nameToUse.value ? 'custom' : 'predefined',
      selected_doctor_id: p.selected_doctor_id
    }))
  }
  
  const appointmentsNeeded = customPrestationsNeedingAppointments.value
  const otherItems = customPrestationsNotNeedingAppointments.value

  if (appointmentsNeeded.length > 0) {
    emit('appointmentRequired', {
      appointmentItems: appointmentsNeeded,
      otherItems: otherItems
    })
  } else {
    emit('itemsToCreate', data)
  }
}

// Watchers
watch(hasSelectedItems, (newVal) => {
  emit('update:hasSelectedItems', newVal)
})

watch(selectedCustomNameOption, (val) => {
  if (val !== 'other') {
    customPrestationName.value = ''
  }
})
</script>

<template>
  <div class="custom-tab p-grid p-dir-col">
    <Card class="p-col-12 filter-card">
      <template #title>
        <div class="p-d-flex p-ai-center">
          <i class="pi pi-filter p-mr-2"></i>
          <h4>Filters and Search</h4>
        </div>
      </template>
      <template #content>
        <div class="p-grid p-fluid">
          <div class="p-col-12 p-md-6 filter-field">
            <label for="search-prestations">Search Prestations</label>
            <InputText
              id="search-prestations"
              v-model="searchTerm"
              placeholder="Search by name or code..."
              class="full-width"
            />
          </div>
          <div class="p-col-12 p-md-6 filter-field">
            <label for="specializations-filter">Filter by Specializations</label>
            <MultiSelect
              id="specializations-filter"
              v-model="selectedSpecializationsFilter"
              :options="specializations"
              optionLabel="name"
              optionValue="id"
              placeholder="Select specializations"
              @change="onSpecializationFilterChange"
              class="full-width"
              :loading="loading"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="shouldShowCustomNameInput" class="custom-name-card p-col-12">
      <template #title>
        <div class="p-d-flex p-ai-center">
          <i class="pi pi-tag p-mr-2"></i>
          <h4>Custom Grouping Name</h4>
        </div>
      </template>
      <template #content>
        <div class="p-grid p-fluid">
          <div class="p-col-12 p-md-6 name-field">
            <label for="name-option">Choose a name for this group</label>
            <Dropdown
              id="name-option"
              v-model="selectedCustomNameOption"
              :options="customNameOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select a name"
              class="full-width"
            />
          </div>
          <div v-if="selectedCustomNameOption === 'other'" class="p-col-12 p-md-6 name-field">
            <label for="custom-name">Custom Name</label>
            <InputText
              id="custom-name"
              v-model="customPrestationName"
              placeholder="Enter custom name..."
              class="full-width"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card class="prestations-table-card p-col-12">
      <template #header>
        <div class="table-header">
          <div class="p-d-flex p-ai-center">
            <i class="pi pi-list p-mr-2"></i>
            <h4>Available Prestations ({{ filteredCustomPrestations.length }})</h4>
          </div>
          <Button
            icon="pi pi-times"
            label="Clear Selection"
            class="p-button-text p-button-secondary"
            @click="customSelectedPrestations = []"
            :disabled="customSelectedPrestations.length === 0"
          />
        </div>
      </template>
      <template #content>
        <DataTable
          :value="filteredCustomPrestations"
          v-model:selection="customSelectedPrestations"
          dataKey="id"
          :loading="loading"
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          responsiveLayout="scroll"
          class="custom-datatable"
        >
          <template #empty>
            <div class="empty-message"><i class="pi pi-search"></i><p>No prestations found with current filters</p></div>
          </template>
          <Column headerStyle="width: 3rem">
            <template #header><span>Select</span></template>
            <template #body="{ data }">
              <Checkbox v-if="!data.need_an_appointment" v-model="customSelectedPrestations" :value="data" :binary="false"/>
              <span v-else class="appointment-required-indicator"><i class="pi pi-calendar-times text-orange-500"></i></span>
            </template>
          </Column>
          <Column field="name" header="Prestation" sortable style="min-width: 200px">
            <template #body="{ data }">
              <div class="prestation-name">
                <strong>{{ data.name }}</strong>
                <div class="prestation-meta">
                  <small class="prestation-code">{{ data.internal_code }}</small>
                  <Tag v-if="data.need_an_appointment" value="Appointment Required" severity="warning" size="small" icon="pi pi-calendar-times" class="ml-1"/>
                  <Tag v-else value="No Appointment" severity="success" size="small" icon="pi pi-check" class="ml-1"/>
                </div>
              </div>
            </template>
          </Column>
          <Column field="specialization_name" header="Specialization" sortable>
            <template #body="{ data }">
              <Tag v-if="data.specialization" :value="data.specialization.name" severity="info"/>
              <span v-else>-</span>
            </template>
          </Column>
          <Column field="public_price" header="Price" sortable>
            <template #body="{ data }"><span class="price-tag">{{ formatCurrency(data.public_price || data.price) }}</span></template>
          </Column>
          <Column header="Action" style="min-width: 250px">
            <template #body="{ data }">
              <div v-if="data.need_an_appointment" class="appointment-action">
                <div v-if="!prestationAppointments[data.id]" class="no-appointment">
                  <Button
                    icon="pi pi-calendar-plus"
                    label="Take Appointment"
                    severity="warning"
                    size="small"
                    @click="takeAppointment(data)"
                    :loading="appointmentLoading === data.id"
                    class="take-appointment-btn"
                  />
                </div>
                <div v-else class="appointment-booked">
                  <div class="appointment-info">
                    <div v-if="prestationAppointments[data.id].type === 'waitlist'" class="waitlist-info">
                      <Tag value="On Waiting List" severity="info" icon="pi pi-clock" size="small"/>
                      <small class="appointment-date">{{ formatAppointmentDateTime(prestationAppointments[data.id].date) }}</small>
                    </div>
                    <div v-else class="confirmed-appointment">
                      <Tag value="Appointment Booked" severity="success" icon="pi pi-calendar-check" size="small"/>
                      <small class="appointment-date">{{ formatAppointmentDateTime(prestationAppointments[data.id].datetime) }}</small>
                    </div>
                    <small v-if="prestationAppointments[data.id].doctor_name" class="doctor-name">Dr. {{ prestationAppointments[data.id].doctor_name }}</small>
                  </div>
                  <Button
                    icon="pi pi-times"
                    severity="danger"
                    size="small"
                    @click="cancelAppointment(data)"
                    class="cancel-appointment-btn"
                    v-tooltip.top="'Cancel appointment'"
                  />
                </div>
              </div>
              <div v-else class="doctor-assignment">
                <Dropdown
                  v-model="data.selected_doctor_id"
                  :options="getDoctorsForPrestation(data)"
                  optionLabel="name"
                  optionValue="id"
                  placeholder="Select doctor"
                  class="doctor-dropdown"
                  :disabled="!customSelectedPrestations.includes(data)"
                  size="small"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card v-if="customSelectedPrestations.length > 0" class="selected-summary-card p-col-12">
      <template #title>
        <div class="p-d-flex p-ai-center">
          <i class="pi pi-check-circle p-mr-2"></i>
          <h4>Selected Prestations ({{ customSelectedPrestations.length }})</h4>
        </div>
      </template>
      <template #content>
        <div class="summary-details">
          <div class="summary-breakdown">
            <div class="breakdown-item">
              <span class="breakdown-label">Total Prestations:</span>
              <span class="breakdown-value">{{ customSelectedPrestations.length }}</span>
            </div>
            <div class="breakdown-item" v-if="customPrestationsNeedingAppointments.length > 0">
              <span class="breakdown-label">Need Appointments:</span>
              <span class="breakdown-value appointment-count">
                {{ customPrestationsNeedingAppointments.length }}
              </span>
            </div>
            <div class="breakdown-item" v-if="customPrestationsNotNeedingAppointments.length > 0">
              <span class="breakdown-label">No Appointments:</span>
              <span class="breakdown-value no-appointment-count">
                {{ customPrestationsNotNeedingAppointments.length }}
              </span>
            </div>
          </div>
          <div class="summary-total">
            <span class="total-label">Total Amount:</span>
            <span class="total-amount">{{ formatCurrency(customTotalCost) }}</span>
          </div>
        </div>
        <div class="summary-list">
          <div
            v-for="prestation in customSelectedPrestations"
            :key="prestation.id"
            class="summary-item"
            :class="{ 'appointment-required-item': prestation.need_an_appointment }"
          >
            <div class="item-info">
              <div class="item-header">
                <span class="item-name">{{ prestation.name }}</span>
                <div class="item-tags">
                  <Tag v-if="prestation.need_an_appointment" value="Appointment" severity="warning" size="small" />
                  <Tag v-if="prestation.specialization" :value="prestation.specialization.name" severity="info" size="small" />
                </div>
              </div>
              <small class="item-code">{{ prestation.internal_code }}</small>
            </div>
            <div class="item-actions">
              <span class="item-price">{{ formatCurrency(prestation.public_price) }}</span>
              <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="removeFromCustomSelected(prestation)" />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="doctorsBySpecialization.length > 0" class="doctors-info-card p-col-12">
      <template #title>
        <div class="p-d-flex p-ai-center">
          <i class="pi pi-users p-mr-2"></i>
          <h4>Available Doctors</h4>
        </div>
      </template>
      <template #content>
        <div class="doctors-grid">
          <div
            v-for="doctor in doctorsBySpecialization"
            :key="doctor.id"
            class="doctor-card"
          >
            <div class="doctor-info">
              <span class="doctor-name">{{ doctor.name }}</span>
              <small class="doctor-specialization">{{ getSpecializationName(doctor.specialization_id) }}</small>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <div class="p-col-12 action-buttons" v-if="hasSelectedItems">
      <Button
        label="Create Custom Fiche Navette"
        icon="pi pi-check"
        @click="createFicheNavette"
        :loading="false"
        class="create-btn"
      />
    </div>
  </div>
</template>

<style scoped>
/* Base Styles for Components */
.custom-tab {
  padding: 1rem;
}

.filter-card, .custom-name-card, .prestations-table-card, .selected-summary-card, .doctors-info-card {
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow-2);
  border-radius: var(--border-radius);
}

.filter-card .p-card-header, .custom-name-card .p-card-header, .prestations-table-card .p-card-header, .selected-summary-card .p-card-header, .doctors-info-card .p-card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--surface-border);
}

.filter-card .p-card-header h4, .custom-name-card .p-card-header h4, .prestations-table-card .p-card-header h4, .selected-summary-card .p-card-header h4, .doctors-info-card .p-card-header h4 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.filter-field label, .name-field label {
  font-weight: 500;
  color: var(--text-color-secondary);
  display: block;
  margin-bottom: 0.5rem;
}

/* Data Table Specific Styles */
.custom-datatable {
  margin-top: 1rem;
}

.prestation-name strong {
  display: block;
  font-weight: 600;
  color: var(--text-color);
}

.prestation-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.prestation-code {
  font-size: 0.8rem;
  color: var(--text-color-secondary);
}

.price-tag {
  background: var(--green-500);
  color: #fff;
  padding: 0.25rem 0.5rem;
  border-radius: var(--border-radius);
  font-size: 0.875rem;
  font-weight: 500;
}

.appointment-required-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem;
}

.text-orange-500 {
  color: var(--orange-500);
}

.appointment-action {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60px;
}

.no-appointment, .appointment-booked {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.appointment-booked {
  padding: 0.5rem;
  background: var(--green-50);
  border-radius: 6px;
  border: 1px solid var(--green-200);
  width: 100%;
}

.appointment-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.waitlist-info, .confirmed-appointment {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.appointment-date {
  color: var(--text-color-secondary);
  font-size: 0.75rem;
  font-weight: 500;
}

.doctor-name {
  color: var(--text-color-secondary);
  font-size: 0.7rem;
  font-style: italic;
}

.cancel-appointment-btn {
  padding: 0.25rem 0.5rem;
  min-width: auto;
}

.take-appointment-btn {
  white-space: nowrap;
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
}

.doctor-assignment {
  display: flex;
  align-items: center;
}

.doctor-dropdown {
  width: 100%;
  min-width: 150px;
}

/* Selected Summary Card */
.selected-summary-card {
  background-color: var(--blue-50);
  border: 1px solid var(--blue-200);
}

.summary-details {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1rem 0;
  border-bottom: 1px solid var(--blue-200);
  margin-bottom: 1rem;
}

.summary-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

.breakdown-label {
  font-weight: 500;
  color: var(--text-color);
}

.breakdown-value {
  font-weight: 600;
  color: var(--text-color);
}

.appointment-count {
  color: var(--orange-500);
}

.no-appointment-count {
  color: var(--green-500);
}

.summary-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  text-align: right;
  gap: 0.25rem;
}

.total-label {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--text-color);
}

.total-amount {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--green-500);
}

.summary-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background-color: var(--surface-card);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-1);
  border: 1px solid var(--surface-border);
}

.summary-item.appointment-required-item {
  border-left: 4px solid var(--orange-500);
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.item-name {
  font-weight: 600;
  color: var(--text-color);
}

.item-tags {
  display: flex;
  gap: 0.25rem;
}

.item-code {
  font-size: 0.8rem;
  color: var(--text-color-secondary);
}

.item-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.item-price {
  font-weight: 700;
  font-size: 1.25rem;
  color: var(--text-color);
}

/* Doctors List */
.doctors-info-card {
  background-color: var(--surface-50);
  border: 1px solid var(--surface-border);
}

.doctors-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.doctor-card {
  padding: 1rem;
  background-color: var(--surface-card);
  border-radius: var(--border-radius);
  border: 1px solid var(--surface-border);
}

.doctor-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.doctor-name {
  font-weight: 600;
}

.doctor-specialization {
  color: var(--text-color-secondary);
}

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-border);
}

.create-btn {
  padding: 0.75rem 2rem;
  font-size: 1rem;
  font-weight: 600;
}
</style>