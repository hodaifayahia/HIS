<template>
  <Dialog
    v-model:visible="internalVisible"
    header="Select Doctor for Appointment"
    modal
    class="doctor-selection-modal"
    :style="{ width: '500px' }"
  >
    <div class="doctor-selection-content">
      <!-- Prestation details -->
      <div class="prestation-info">
        <h5>
          <i class="pi pi-medical"></i>
          {{ prestation?.name }}
        </h5>
        <p>{{ prestation?.internal_code }}</p>
        <Tag
          value="Appointment Required"
          severity="warning"
          icon="pi pi-calendar-times"
        />
      </div>

      <!-- Doctor selection -->
      <div class="doctor-selection">
        <label for="doctor-select">Choose a Doctor:</label>
        <Dropdown
          id="doctor-select"
          v-model="selectedDoctor"
          :options="availableDoctors"
          optionLabel="name"
          optionValue="id"
          placeholder="Select a doctor"
          class="doctor-dropdown"
          :loading="loading"
        >
          <template #option="{ option }">
            <div class="doctor-option">
              <div class="doctor-info">
                <span class="doctor-name">{{ option.name }}</span>
                <small class="doctor-specialization">
                  {{ getSpecializationName(option.specialization_id) }}
                </small>
              </div>
            </div>
          </template>
        </Dropdown>
      </div>

      <!-- Selected doctor summary -->
      <div v-if="selectedDoctor" class="selected-doctor-info">
        <h6>Selected Doctor:</h6>
        <div class="doctor-card">
          <Avatar icon="pi pi-user" class="doctor-avatar" />
          <div class="doctor-details">
            <span class="doctor-name">{{ getSelectedDoctorName() }}</span>
            <small class="doctor-specialization">
              {{ getSelectedDoctorSpecialization() }}
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <Button
        label="Cancel"
        icon="pi pi-times"
        severity="secondary"
        @click="cancel"
      />
      <Button
        label="Book Appointment"
        icon="pi pi-calendar-plus"
        severity="warning"
        @click="proceedToAppointment"
        :disabled="!selectedDoctor"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'

const props = defineProps({
  visible: { type: Boolean, default: false },
  prestation: { type: Object, default: null },
  doctors: { type: Array, default: () => [] },
  specializations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'doctor-selected', 'cancel'])

// Local state synced with prop
const internalVisible = ref(props.visible)
watch(() => props.visible, (val) => (internalVisible.value = val))
watch(internalVisible, (val) => emit('update:visible', val))

const selectedDoctor = ref(null)

const availableDoctors = computed(() => {
  if (!props.prestation?.specialization_id) return props.doctors
  return props.doctors.filter(
    (doctor) => doctor.specialization_id === props.prestation.specialization_id
  )
})

const getSpecializationName = (id) => {
  const spec = props.specializations.find((s) => s.id === id)
  return spec ? spec.name : 'Unknown'
}

const getSelectedDoctorName = () => {
  const doc = availableDoctors.value.find((d) => d.id === selectedDoctor.value)
  return doc ? doc.name : ''
}

const getSelectedDoctorSpecialization = () => {
  const doc = availableDoctors.value.find((d) => d.id === selectedDoctor.value)
  return doc ? getSpecializationName(doc.specialization_id) : ''
}

const proceedToAppointment = () => {
  if (selectedDoctor.value) {
    emit('doctor-selected', {
      doctorId: selectedDoctor.value,
      prestation: props.prestation
    })
  }
}

const cancel = () => {
  selectedDoctor.value = null
  emit('cancel')
  internalVisible.value = false
}

// Reset doctor on modal close
watch(() => props.visible, (newVal) => {
  if (!newVal) selectedDoctor.value = null
})
</script>

<style scoped>
.doctor-selection-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.prestation-info {
  padding: 1rem;
  background: var(--orange-50);
  border-radius: 8px;
  border: 1px solid var(--orange-200);
}

.prestation-info h5 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem 0;
  color: var(--orange-700);
}

.prestation-info p {
  margin: 0 0 0.5rem 0;
  color: var(--text-color-secondary);
}

.doctor-selection label {
  font-weight: 500;
}

.doctor-dropdown {
  width: 100%;
}

.doctor-option {
  display: flex;
  gap: 0.75rem;
}

.doctor-name {
  font-weight: 500;
}

.doctor-specialization {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
}

.selected-doctor-info {
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 8px;
}

.doctor-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.doctor-avatar {
  background: var(--primary-500);
}
</style>
