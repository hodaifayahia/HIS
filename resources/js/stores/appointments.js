import { defineStore } from 'pinia';
import axios from 'axios';

export const useAppointmentStore = defineStore('appointment', {
  state: () => ({
    appointments: [],
    loading: false,
    availableAppointments: {}, // Stores appointment availability data by doctorId
    loadingAppointments: {},   // Stores loading status for each doctor's appointments
    doctors: [],               // You might want to store doctors here too if they are shared
    isLoadingDoctors: false,   // Loading status for the main doctors list
    searchQuery: '',           // If search functionality is global
  }),

  getters: {
    // A getter to easily access a specific doctor's appointments
    getDoctorAppointments: (state) => (doctorId) => {
      return state.availableAppointments[doctorId] || { canceled_appointments: [], normal_appointments: {} };
    },
    // A getter to check if a specific doctor's appointments are loading
    getDoctorAppointmentsLoading: (state) => (doctorId) => {
      return state.loadingAppointments[doctorId] || false;
    },
    // Optional: formatted closest canceled appointment getter
    formatClosestCanceledAppointment: (state) => (appointments) => {
      if (!appointments || appointments.length === 0) return 'No upcoming canceled appointments';

      const sortedAppointments = appointments.sort((a, b) => {
        const dateA = new Date(a.date + 'T' + a.available_times[0] + ':00');
        const dateB = new Date(b.date + 'T' + b.available_times[0] + ':00');
        return dateA - dateB;
      });

      const closest = sortedAppointments[0];
      return `${closest.date} at ${closest.available_times[0]}`;
    },
  },

  actions: {
    // Action to fetch all doctors (if doctor data is also managed by this store)
    async fetchDoctors(specializationId = null) {
      this.isLoadingDoctors = true;
      try {
        const response = await axios.get('/api/doctors', {
          params: { query: specializationId },
        });
        this.doctors = response.data.data;

        // Fetch appointments for all doctors concurrently after doctors are loaded
        await Promise.all(this.doctors.map(doctor => this.fetchAvailableAppointments(doctor.id)));

      } catch (error) {
        console.error('Error fetching doctors:', error);
        // You might want to set an error state here
      } finally {
        this.isLoadingDoctors = false;
      }
    },

    // Action to search doctors
    async searchDoctors(query) {
      this.isLoadingDoctors = true;
      this.searchQuery = query; // Update the search query state
      try {
        const response = await axios.get('/api/doctors/search', {
          params: { query: this.searchQuery },
        });
        this.doctors = response.data.data;
        // Optionally fetch appointments for searched doctors too
        await Promise.all(this.doctors.map(doctor => this.fetchAvailableAppointments(doctor.id)));
      } catch (error) {
        console.error('Error searching doctors:', error);
      } finally {
        this.isLoadingDoctors = false;
      }
    },

    // Action to fetch available appointments for a specific doctor
    async fetchAvailableAppointments(doctorId) {
      this.loadingAppointments[doctorId] = true; // Set loading for specific doctor
      try {
        const response = await axios.get('/api/appointments/available', {
          params: { doctor_id: doctorId }
        });
        this.availableAppointments[doctorId] = {
          canceled_appointments: response.data.canceled_appointments,
          normal_appointments: response.data.normal_appointments
        };
      } catch (error) {
        console.error(`Error fetching available appointments for doctor ${doctorId}:`, error);
        // You might want to clear or set an error state for this doctorId
      } finally {
        this.loadingAppointments[doctorId] = false;
      }
    },

    async getAppointments(doctorId, page = 1, status = null, filter = null, date = null) {
      // Prevent duplicate calls
      if (this.loading) {
        console.log('Already loading, skipping duplicate call');
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const params = { page, status, filter, date };
        const { data } = await axios.get(`/api/appointments/${doctorId}`, { params });

        if (data.success) {
          this.appointments = data.data;
          this.pagination = data.meta;
        }
      } catch (err) {
        console.error('Error fetching appointments:', err);
        this.error = 'Failed to load appointments. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    // Add method to check if we should reload
    shouldReload() {
      return !this.loading && this.appointments.length === 0;
    },

    // Action to clear all stored appointment data (e.g., on logout or leaving a section)
    clearAppointments() {
      this.availableAppointments = {};
      this.loadingAppointments = {};
      this.doctors = [];
      this.isLoadingDoctors = false;
      this.searchQuery = '';
    }
  }
});