<script setup>
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import VTooltip from 'primevue/tooltip'

const props = defineProps({
  prestation: {
    type: Object,
    required: true,
  },
  prestationAppointments: {
    type: Object,
    required: true,
  },
  appointmentLoading: {
    type: Number,
    default: null,
  },
  allDoctors: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['take-appointment', 'cancel-appointment'])

// Local methods
const formatAppointmentDateTime = (datetime) => { /* ... */ }
const getDoctorName = (doctorId) => { /* ... */ }
const cancelAppointment = () => { emit('cancel-appointment', props.prestation) }
const takeAppointment = () => { emit('take-appointment', props.prestation) }
</script>

<template>
  <div v-if="prestation.need_an_appointment" class="appointment-action">
    <div v-if="!prestationAppointments[prestation.id]" class="no-appointment">
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
      </div>
  </div>
  <div v-else class="doctor-assignment">
    </div>
</template>