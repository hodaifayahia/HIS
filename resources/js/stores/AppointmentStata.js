import { defineStore } from 'pinia';
import axios from 'axios';

export const useAppointmentStore = defineStore('appointmentState', {
  state: () => ({
    appointments: [],
    pagination: {},
    loading: false,
    error: null,
    currentFilter: 0, // 0 for Scheduled, null for ALL (or whatever your default is)
    todaysAppointmentsCount: 0,
    countWithDoctor: 0,       // For waitlist specific to doctor
    countWithoutDoctor: 0,    // For waitlist for specialization
    statuses: [
      { name: 'ALL', value: null, color: 'secondary', icon: 'fas fa-list', count: 0 },
      { name: 'SCHEDULED', value: 0, color: 'primary', icon: 'fa fa-calendar-check', count: 0 },
      { name: 'CONFIRMED', value: 1, color: 'success', icon: 'fa fa-check', count: 0 },
      { name: 'CANCELED', value: 2, color: 'danger', icon: 'fa fa-ban', count: 0 },
      { name: 'PENDING', value: 3, color: 'warning', icon: 'fa fa-hourglass-half', count: 0 },
      { name: 'DONE', value: 4, color: 'info', icon: 'fa fa-check-circle', count: 0 },
    ],
    // State for file upload
    uploadProgress: 0,
    currentFileIndex: 0,
    selectedFiles: [],
    uploadResults: { success: [], errors: [] }, // Renamed from 'results' to avoid conflict
  }),

  getters: {
    // Getter for paginated appointments (already have this from storeToRefs)
    // Getter to get a specific status object by its value
    getStatusByValue: (state) => (value) => {
      return state.statuses.find(s => s.value === value);
    },
    // Getter to check if upload is in progress
    isUploading: (state) => state.uploadProgress > 0 && state.uploadProgress < 100,
  },

  actions: {
    // --- Core Appointment Fetching ---
    async getAppointments(doctorId, page = 1, status = null, filter = null, date = null) {
      this.loading = true;
      this.error = null; // Clear previous errors
      this.currentFilter = status; // Update the filter in store

      try {
        const params = {
          page,
          status: status ?? this.currentFilter, // Use filter from store if not provided
          filter,
          date,
        };

        const { data } = await axios.get(`/api/appointments/${doctorId}`, { params });

        if (data.success) {
          this.appointments = data.data;
          this.pagination = data.meta;
          // You might also want to update the counts in the status objects here
          // if your API returns them with the main appointment fetch.
          // Otherwise, getAppointmentsStatus() handles it.
        } else {
          throw new Error(data.message || 'Failed to fetch appointments.');
        }
      } catch (err) {
        console.error('Error fetching appointments:', err);
        this.error = 'Failed to load appointments. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    async getTodaysAppointments(doctorId) {
      this.loading = true;
      this.error = null;
      this.currentFilter = 'TODAY'; // Set filter to 'TODAY'
      try {
        const response = await axios.get(`/api/appointments/${doctorId}`, {
          params: { filter: 'today' }
        });

        if (response.data.success) {
          this.appointments = response.data.data;
          this.pagination = response.data.meta; // Make sure today's appts also paginate
          this.todaysAppointmentsCount = response.data.data.length;
        } else {
          throw new Error(response.data.message);
        }
      } catch (err) {
        console.error('Error fetching today\'s appointments:', err);
        this.error = 'Failed to load today\'s appointments. Please try again later.';
      } finally {
        this.loading = false;
      }
    },

    async getAppointmentsStatus(doctorId) {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/api/appointmentStatus/${doctorId}`);
        if (response.data) {
          // Update counts for each status in the store's statuses array
          this.statuses = this.statuses.map(status => {
            const apiStatus = response.data.find(s => s.value === status.value);
            return { ...status, count: apiStatus ? apiStatus.count : 0 };
          });
          // Update the "ALL" count specifically if your API returns total or calculate it
          const totalCount = response.data.reduce((sum, s) => sum + s.count, 0);
          const allStatus = this.statuses.find(s => s.name === 'ALL');
          if (allStatus) {
            allStatus.count = totalCount;
          }
        }
      } catch (err) {
        console.error('Error fetching appointment statuses:', err);
        this.error = 'Failed to load status filters. Please try again later.';
      } finally {
        this.loading = false;
      }
    },

    // --- Waitlist Fetching ---
    async fetchWaitlists(doctorId, specializationId, NotForYou = false) {
      // You'll need to pass NotForYou from the component if it controls this behavior
      try {
        const params = { is_Daily: 1 };
        params.doctor_id = NotForYou ? "null" : doctorId;
        params.specialization_id = specializationId;

        const response = await axios.get('/api/waitlists', { params });

        this.countWithDoctor = response.data.count_with_doctor;
        this.countWithoutDoctor = response.data.count_without_doctor;
      } catch (error) {
        console.error('Error fetching waitlists:', error);
        this.error = 'Failed to load waitlist counts.';
      }
    },

    // --- File Upload Actions ---
    handleFileSelection(files) {
      const validTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv'
      ];
      this.selectedFiles = Array.from(files).filter(file =>
        validTypes.includes(file.type) ||
        file.name.endsWith('.csv') ||
        file.name.endsWith('.xlsx') ||
        file.name.endsWith('.xls')
      );
    },

    removeFile(index) {
      this.selectedFiles.splice(index, 1);
    },

    async uploadFiles(doctorId) {
      if (!this.selectedFiles.length) return;

      this.loading = true;
      this.uploadProgress = 0;
      this.currentFileIndex = 0;
      this.uploadResults = { success: [], errors: [] }; // Reset results

      for (let i = 0; i < this.selectedFiles.length; i++) {
        this.currentFileIndex = i;
        const file = this.selectedFiles[i];
        const formData = new FormData();
        formData.append('file', file);

        try {
          const response = await axios.post(
            `/api/import/appointment/${doctorId}`,
            formData,
            {
              headers: { 'Content-Type': 'multipart/form-data' },
              onUploadProgress: (progressEvent) => {
                this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
              },
            }
          );
          this.uploadResults.success.push({
            filename: file.name,
            message: response.data.message
          });
        } catch (error) {
          this.uploadResults.errors.push({
            filename: file.name,
            error: error.response?.data?.message || 'Upload failed'
          });
        }
      }

      // Refresh appointments and statuses after upload
      await Promise.all([
        this.getAppointments(doctorId, 1, this.currentFilter), // Re-fetch current view
        this.getAppointmentsStatus(doctorId)
      ]);

      // showResults will be called in the component based on changes to uploadResults
      this.loading = false;
      this.selectedFiles = []; // Clear selected files after upload
      this.uploadProgress = 0; // Reset progress
      this.currentFileIndex = 0; // Reset index
    },

    async exportAppointments() {
      try {
        const response = await axios.get('/api/export/appointment', { responseType: 'blob' });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'appointments.xlsx');
        document.body.appendChild(link);
        link.click();
      } catch (error) {
        console.error('Error exporting appointments:', error);
        this.error = 'Failed to export appointments.';
      }
    },

    // --- Other Actions ---
    handleSearchResults(searchData) {
      this.appointments = searchData.data;
      this.pagination = searchData.meta;
    },

    // Reset store state (useful when navigating away or for clean slate)
    resetStore() {
      this.appointments = [];
      this.pagination = {};
      this.loading = false;
      this.error = null;
      this.currentFilter = 0;
      this.todaysAppointmentsCount = 0;
      this.countWithDoctor = 0;
      this.countWithoutDoctor = 0;
      this.statuses = this.statuses.map(s => ({ ...s, count: 0 })); // Reset counts
      this.uploadProgress = 0;
      this.currentFileIndex = 0;
      this.selectedFiles = [];
      this.uploadResults = { success: [], errors: [] };
    },
  },
});