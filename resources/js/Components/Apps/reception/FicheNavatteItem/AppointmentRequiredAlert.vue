<!-- filepath: resources/js/Components/Apps/reception/FicheNavatteItem/AppointmentRequiredAlert.vue -->
<template>
  <Dialog
    :visible="visible"
    :header="alertTitle"
    modal
    class="appointment-required-alert"
    :style="{ width: '600px' }"
    @update:visible="$emit('update:visible', $event)"
  >
    <div class="alert-content">
      <!-- Warning Icon and Message -->
      <div class="warning-section">
        <div class="warning-icon">
          <i class="pi pi-exclamation-triangle text-orange-500" style="font-size: 3rem;"></i>
        </div>
        <h4 class="warning-title">Appointment Required</h4>
        <p class="warning-message">
          The following {{ prestationsNeedingAppointments.length > 1 ? 'prestations require' : 'prestation requires' }} an appointment to be scheduled:
        </p>
      </div>

      <!-- List of Prestations Requiring Appointments -->
      <div class="prestations-list">
        <div 
          v-for="prestation in prestationsNeedingAppointments" 
          :key="prestation.id || prestation.prestation_id"
          class="prestation-item"
        >
          <div class="prestation-info">
            <div class="prestation-name">
              <i class="pi pi-calendar-times text-orange-500"></i>
              <strong>{{ prestation.name || prestation.prestation_name }}</strong>
            </div>
            <div class="prestation-details">
              <span class="prestation-code">Code: {{ prestation.internal_code || prestation.prestation_code }}</span>
              <span v-if="prestation.specialization_name" class="prestation-specialization">
                Service: {{ prestation.specialization_name }}
              </span>
            </div>
          </div>
          <div class="prestation-price">
            <span class="price-amount">{{ formatCurrency(prestation.public_price || prestation.convention_price || prestation.price) }}</span>
          </div>
        </div>
      </div>

      <!-- Options Section -->
      <div class="options-section">
        <h5>What would you like to do?</h5>
        
        <div class="option-cards">
          <!-- Option 1: Book Appointments -->
          <div class="option-card recommended">
            <div class="option-header">
              <i class="pi pi-calendar-plus text-green-500"></i>
              <h6>Book Appointments</h6>
              <span class="recommended-badge">Recommended</span>
            </div>
            <p class="option-description">
              Schedule appointments for {{ prestationsNeedingAppointments.length > 1 ? 'these prestations' : 'this prestation' }} 
              and add {{ otherItemsCount > 0 ? 'the remaining items' : 'other items' }} to the fiche navette.
            </p>
            <Button
              label="Book Appointments"
              icon="pi pi-calendar-plus"
              @click="proceedWithAppointments"
              :loading="processing"
              class="p-button-success option-button"
            />
          </div>

          <!-- Option 2: Skip Appointments -->
          <div class="option-card">
            <div class="option-header">
              <i class="pi pi-times-circle text-orange-500"></i>
              <h6>Skip Appointments</h6>
            </div>
            <p class="option-description">
              Continue without booking appointments. Only 
              {{ otherItemsCount > 0 ? `${otherItemsCount} other items` : 'items that don\'t require appointments' }} 
              will be added to the fiche navette.
            </p>
            <Button
              label="Continue Without Appointments"
              icon="pi pi-times"
              @click="proceedWithoutAppointments"
              :loading="processing"
              class="p-button-outlined p-button-warning option-button"
            />
          </div>
        </div>

        <!-- Cancel Option -->
        <div class="cancel-section">
          <Button
            label="Cancel"
            icon="pi pi-times"
            @click="cancelProcess"
            class="p-button-text p-button-secondary"
            :disabled="processing"
          />
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  prestationsNeedingAppointments: {
    type: Array,
    default: () => []
  },
  otherItemsCount: {
    type: Number,
    default: 0
  },
  selectedDoctor: {
    type: Number,
    required: false
  }
})

const emit = defineEmits([
  'update:visible', 
  'proceed-with-appointments', 
  'proceed-without-appointments', 
  'cancel'
])

// Reactive data
const processing = ref(false)

// Computed
const alertTitle = computed(() => {
  const count = props.prestationsNeedingAppointments.length
  return count > 1 ? `${count} Prestations Require Appointments` : 'Prestation Requires Appointment'
})

// Methods
const proceedWithAppointments = () => {
  processing.value = true
  emit('proceed-with-appointments', {
    prestationsNeedingAppointments: props.prestationsNeedingAppointments,
    doctorId: props.selectedDoctor
  })
  // Reset processing state after a delay to allow parent to handle
  setTimeout(() => {
    processing.value = false
  }, 1000)
}

const proceedWithoutAppointments = () => {
  processing.value = true
  emit('proceed-without-appointments')
  // Reset processing state after a delay
  setTimeout(() => {
    processing.value = false
  }, 1000)
}

const cancelProcess = () => {
  emit('cancel')
  emit('update:visible', false)
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return 'N/A'
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}
</script>

<style scoped>
.alert-content {
  padding: 1.5rem;
}

.warning-section {
  text-align: center;
  margin-bottom: 2rem;
}

.warning-icon {
  margin-bottom: 1rem;
}

.warning-title {
  margin: 0 0 1rem 0;
  color: var(--orange-600);
  font-size: 1.5rem;
  font-weight: 600;
}

.warning-message {
  color: var(--text-color-secondary);
  font-size: 1.1rem;
  margin: 0;
}

.prestations-list {
  background: var(--orange-50);
  border: 1px solid var(--orange-200);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 2rem;
}

.prestation-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 6px;
  margin-bottom: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.prestation-item:last-child {
  margin-bottom: 0;
}

.prestation-info {
  flex: 1;
}

.prestation-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.prestation-name strong {
  color: var(--text-color);
  font-size: 1rem;
}

.prestation-details {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: var(--text-color-secondary);
}

.prestation-price {
  text-align: right;
}

.price-amount {
  font-weight: 600;
  color: var(--primary-color);
  font-size: 1rem;
}

.options-section h5 {
  margin: 0 0 1.5rem 0;
  color: var(--text-color);
  font-size: 1.2rem;
  text-align: center;
}

.option-cards {
  display: grid;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.option-card {
  border: 2px solid var(--surface-300);
  border-radius: 12px;
  padding: 1.5rem;
  background: white;
  transition: all 0.3s ease;
}

.option-card:hover {
  border-color: var(--primary-color);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.option-card.recommended {
  border-color: var(--green-400);
  background: var(--green-25);
}

.option-card.recommended:hover {
  border-color: var(--green-500);
}

.option-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  position: relative;
}

.option-header h6 {
  margin: 0;
  color: var(--text-color);
  font-size: 1.1rem;
  font-weight: 600;
}

.recommended-badge {
  background: var(--green-500);
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-weight: 500;
  margin-left: auto;
}

.option-description {
  color: var(--text-color-secondary);
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.option-button {
  width: 100%;
  padding: 0.75rem 1rem;
  font-weight: 500;
}

.cancel-section {
  text-align: center;
  padding-top: 1rem;
  border-top: 1px solid var(--surface-200);
}

.text-orange-500 {
  color: #f59e0b;
}

.text-green-500 {
  color: #10b981;
}

@media (max-width: 768px) {
  .appointment-required-alert {
    width: 95vw !important;
  }
  
  .prestation-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .prestation-details {
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .prestation-price {
    align-self: flex-end;
  }
}
</style>