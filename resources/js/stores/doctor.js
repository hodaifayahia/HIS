import { defineStore } from 'pinia';
import axios from 'axios';

export const useDoctorStore = defineStore('doctor', {
  state: () => ({
    currentDoctor: null, // Stores the single doctor's info being viewed
    doctorLoading: false,
    doctorError: null,
    availableAppointments: { // Stores available appointments for the current doctor
      canceled_appointments: null,
      normal_appointments: null
    },
    appointmentsLoading: false,
    appointmentsError: null,
  }),

  getters: {
    // Getter to check if doctor data is loaded
    isDoctorLoaded: (state) => !!state.currentDoctor,
    // Getter for formatted closest canceled appointment
    formatClosestCanceledAppointment: (state) => {
      const appointments = state.availableAppointments.canceled_appointments;
      if (!appointments || appointments.length === 0) return 'No upcoming canceled appointments';

      const sortedAppointments = [...appointments].sort((a, b) => { // Use spread to avoid modifying original array
        const dateA = new Date(a.date + 'T' + a.available_times[0] + ':00');
        const dateB = new Date(b.date + 'T' + b.available_times[0] + ':00');
        return dateA - dateB;
      });

      const closest = sortedAppointments[0];
      return `${closest.date} at ${closest.available_times[0]}`;
    },
  },

  actions: {
    async fetchDoctorInfo(doctorId) {
      if (!doctorId) {
        console.warn('Doctor ID is missing for fetchDoctorInfo.');
        return;
      }

      this.doctorLoading = true;
      this.doctorError = null;
      try {
        const response = await axios.get(`/api/doctors/${doctorId}`);
        this.currentDoctor = response.data.data;
      } catch (error) {
        console.error('Error fetching doctor info:', error);
        this.doctorError = 'Failed to fetch doctor information.';
      } finally {
        this.doctorLoading = false;
      }
    },

    async fetchAvailableAppointments(doctorId) {
      if (!doctorId) {
        console.warn('Doctor ID is missing for fetchAvailableAppointments.');
        return;
      }

      this.appointmentsLoading = true;
      this.appointmentsError = null;
      try {
        const response = await axios.get('/api/appointments/available', {
          params: { doctor_id: doctorId }
        });
        this.availableAppointments = {
          canceled_appointments: response.data.canceled_appointments,
          normal_appointments: response.data.normal_appointments
        };
      } catch (error) {
        console.error('Error fetching available appointments:', error);
        this.appointmentsError = 'Failed to fetch available appointments.';
      } finally {
        this.appointmentsLoading = false;
      }
    },

    // Action to fetch both doctor info and appointments concurrently
    async initializeDoctorData(doctorId) {
      await Promise.all([
        this.fetchDoctorInfo(doctorId),
        this.fetchAvailableAppointments(doctorId)
      ]);
    },

    // Action to clear the current doctor's data when navigating away
    clearDoctorData() {
      this.currentDoctor = null;
      this.availableAppointments = {
        canceled_appointments: null,
        normal_appointments: null
      };
      this.doctorLoading = false;
      this.appointmentsLoading = false;
      this.doctorError = null;
      this.appointmentsError = null;
    }
  }
});