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
    appointmentsFetched: {},   // Track which doctors we've already fetched appointments for
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
      if (this.isLoadingDoctors) return; // Prevent concurrent fetches
      
      this.isLoadingDoctors = true;
      try {
        let response;
        if (specializationId) {
          // Use the dedicated endpoint for specialization filtering
          response = await axios.get(`/api/doctors/specializations/${specializationId}`);
        } else {
          response = await axios.get('/api/doctors');
        }

        // The API returns data in response.data.data in other places; handle both shapes defensively
        this.doctors = response.data.data || response.data || [];

        // Only fetch initial appointments for visible doctors (e.g., first page)
        const visibleDoctors = this.doctors.slice(0, 10); // Adjust number based on your UI
        await Promise.all(visibleDoctors.map(doctor => this.fetchAvailableAppointments(doctor.id)));

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
      // Skip if already fetched or currently loading
      if (this.appointmentsFetched?.[doctorId] || this.loadingAppointments?.[doctorId]) {
        return;
      }

      this.loadingAppointments[doctorId] = true;
      try {
        const response = await axios.get('/api/appointments/available', {
          params: { doctor_id: doctorId }
        });
        this.availableAppointments[doctorId] = {
          canceled_appointments: response.data.canceled_appointments,
          normal_appointments: response.data.normal_appointments
        };
        this.appointmentsFetched[doctorId] = true; // Mark as fetched
      } catch (error) {
        console.error(`Error fetching available appointments for doctor ${doctorId}:`, error);
      } finally {
        this.loadingAppointments[doctorId] = false;
      }
    },

    // Keep track of pending requests
    _pendingRequests: {},

    async getAppointments(doctorId, page = 1, status = null, filter = null, date = null) {
      // Create a unique key for this request
      const requestKey = `${doctorId}-${page}-${status}-${filter}-${date}`;
      
      // If there's already a pending request with the same parameters, return it
      if (this._pendingRequests[requestKey]) {
        return this._pendingRequests[requestKey];
      }

      // If we're already loading with the same parameters, skip
      if (this.loading) {
        console.log('Already loading, skipping duplicate call');
        return;
      }

      this.loading = true;
      this.error = null;

      // Create the promise for this request
      this._pendingRequests[requestKey] = (async () => {
        try {
          const params = { page, status, filter, date };
          const { data } = await axios.get(`/api/appointments/${doctorId}`, { params });

          if (data.success) {
            this.appointments = data.data;
            this.pagination = data.meta;
          }
          return data;
        } catch (err) {
          console.error('Error fetching appointments:', err);
          this.error = 'Failed to load appointments. Please try again.';
          throw err;
        } finally {
          this.loading = false;
          // Clean up the pending request
          delete this._pendingRequests[requestKey];
        }
      })();

      return this._pendingRequests[requestKey];
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