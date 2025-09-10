<script setup>
import { computed } from 'vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Tooltip from 'primevue/tooltip'

const props = defineProps({
  prestation: Object,
  prestationAppointments: Object,
  appointmentLoading: [String, Number]
})

const emit = defineEmits(['takeAppointment', 'cancelAppointment'])

// Methods
const formatAppointmentDateTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const takeAppointment = () => {
  emit('takeAppointment', props.prestation)
}

const cancelAppointment = () => {
  emit('cancelAppointment', props.prestation)
}

const appointment = computed(() => props.prestationAppointments[props.prestation.id])

</script>

<template>
  <div class="appointment-action">
    <div v-if="!appointment" class="no-appointment">
      <Button
        icon="pi pi-calendar-plus"
        label="Take Appointment"
        severity="warning"
        size="small"
        @click="takeAppointment"
        :loading="appointmentLoading === prestation.id"
        class="take-appointment-btn"
      />
    </div>
    <div v-else class="appointment-booked">
      <div class="appointment-info">
        <div v-if="appointment.type === 'waitlist'" class="waitlist-info">
          <Tag value="On Waiting List" severity="info" icon="pi pi-clock" size="small" />
          <small class="appointment-date">
            {{ formatAppointmentDateTime(appointment.date) }}
          </small>
        </div>
        <div v-else class="confirmed-appointment">
          <Tag value="Appointment Booked" severity="success" icon="pi pi-calendar-check" size="small" />
          <small class="appointment-date">
            {{ formatAppointmentDateTime(appointment.datetime) }}
          </small>
        </div>
        <small v-if="appointment.doctor_name" class="doctor-name">
          Dr. {{ appointment.doctor_name }}
        </small>
      </div>
      <Button
        icon="pi pi-times"
        severity="danger"
        size="small"
        @click="cancelAppointment"
        class="cancel-appointment-btn"
        v-tooltip.top="'Cancel appointment'"
      />
    </div>
  </div>
</template>

<style scoped>
/* You can extract and move the relevant styles for this component here */
.appointment-action {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60px;
}

.no-appointment {
  display: flex;
  align-items: center;
  justify-content: center;
}

.appointment-booked {
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

.waitlist-info,
.confirmed-appointment {
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

.waitlist-info {
  background: var(--blue-50);
  border: 1px solid var(--blue-200);
}

.appointment-booked:has(.waitlist-info) {
  background: var(--blue-50);
  border-color: var(--blue-200);
}

.appointment-booked:has(.confirmed-appointment) {
  background: var(--green-50);
  border-color: var(--green-200);
}

@media (max-width: 768px) {
  .appointment-booked {
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.4rem;
  }
  
  .cancel-appointment-btn {
    align-self: flex-end;
  }
  
  .take-appointment-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>