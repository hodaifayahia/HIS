import axios from 'axios'

export const appointmentService = {
  /**
   * Checks same-day appointment availability for a doctor.
   * @param {object} params - Parameters including doctor_id and prestation_id.
   * @returns {Promise<object>} An object indicating success, data, or error details.
   */
  async checkSameDayAvailability(params) {
    try {
      const response = await axios.post('/api/appointments/check-same-day-availability', params)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error checking same-day availability:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to check availability. Please try again.',
        error
      }
    }
  },

  /**
   * Books a same-day appointment.
   * @param {object} appointmentData - The appointment data including doctor_id, patient_id, appointment_time, etc.
   * @returns {Promise<object>} An object indicating success, created appointment, or error details.
   */
  async bookSameDayAppointment(appointmentData) {
    try {
      const response = await axios.post('/api/appointments/book-same-day', appointmentData)
      return {
        success: true,
        data: response.data.appointment || response.data.data || response.data,
        message: response.data.message || 'Appointment booked successfully'
      }
    } catch (error) {
      console.error('Error booking same-day appointment:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to book appointment. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  // Add this method to your appointmentService
async cancelAppointment(data) {
  try {
    const response = await axios.patch(`/api/appointment/${data.appointment_id}/status`, {
      appointment_id: data.appointment_id,
      reason: data.reason,
      status: 2
    });
    
    return {
      success: true,
      data: response.data.data,
      message: response.data.message || 'Appointment cancelled successfully'
    };
  } catch (error) {
    console.error('Error cancelling appointment:', error);
    return {
      success: false,
      message: error.response?.data?.message || 'Failed to cancel appointment',
      error
    };
  }
},
  /**
   * Checks general appointment availability for a doctor.
   * @param {object} params - Parameters including doctor_id, date, etc.
   * @returns {Promise<object>} An object indicating success, data, or error details.
   */
  async checkAvailability(params) {
    try {
      const response = await axios.post('/api/appointments/availability', params)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error checking availability:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to check availability. Please try again.',
        error
      }
    }
  },

  /**
   * Creates a new appointment.
   * @param {object} appointmentData - The appointment data.
   * @returns {Promise<object>} An object indicating success, created appointment, or error details.
   */
  async create(appointmentData) {
    try {
      const response = await axios.post('/api/appointments', appointmentData)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message || 'Appointment created successfully'
      }
    } catch (error) {
      console.error('Error creating appointment:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create appointment. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  /**
   * Updates an existing appointment.
   * @param {string|number} id - The ID of the appointment to update.
   * @param {object} appointmentData - The updated appointment data.
   * @returns {Promise<object>} An object indicating success, updated appointment, or error details.
   */
  async update(id, appointmentData) {
    try {
      const response = await axios.put(`/api/appointments/${id}`, appointmentData)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message || 'Appointment updated successfully'
      }
    } catch (error) {
      console.error(`Error updating appointment ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update appointment. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  /**
   * Deletes an appointment by its ID.
   * @param {string|number} id - The ID of the appointment to delete.
   * @returns {Promise<object>} An object indicating success, response data, or error details.
   */
  async delete(id) {
    try {
      const response = await axios.delete(`/api/appointments/${id}`)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message || 'Appointment deleted successfully'
      }
    } catch (error) {
      console.error(`Error deleting appointment ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete appointment. Please try again.',
        error
      }
    }
  },

  /**
   * Fetches all appointments with optional filtering.
   * @param {object} params - Optional query parameters for filtering/pagination.
   * @returns {Promise<object>} An object indicating success, data, or error details.
   */
  async getAll(params = {}) {
    try {
      const response = await axios.get('/api/appointments', { params })
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching appointments:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load appointments. Please try again.',
        error
      }
    }
  },

  /**
   * Fetches a single appointment by its ID.
   * @param {string|number} id - The ID of the appointment to fetch.
   * @returns {Promise<object>} An object indicating success, data, or error details.
   */
  async getById(id) {
    try {
      const response = await axios.get(`/api/appointments/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error fetching appointment ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load appointment. Please try again.',
        error
      }
    }
  },

  /**
   * Cancels an appointment.
   * @param {string|number} id - The ID of the appointment to cancel.
   * @param {object} cancelData - Optional cancellation data (reason, etc.).
   * @returns {Promise<object>} An object indicating success, response data, or error details.
   */
  async cancel(id, cancelData = {}) {
    try {
      const response = await axios.post(`/api/appointments/${id}/cancel`, cancelData)
      return {
        success: true,
        data: response.data.data || response.data,
        message: response.data.message || 'Appointment cancelled successfully'
      }
    } catch (error) {
      console.error(`Error cancelling appointment ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to cancel appointment. Please try again.',
        error
      }
    }
  }
}

export default appointmentService