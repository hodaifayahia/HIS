// services/specializationService.js
import axios from 'axios'

export const specializationService = {
  /**
   * Fetches all specializations from the API.
   * @param {object} params - Optional query parameters for filtering/pagination.
   * @returns {Promise<object>} An object indicating success, data, or error details.
   */
  async getAll(params = {}) {
    try {
      const response = await axios.get('/api/specializations', { params })
      return {
        success: true,
        data: response.data.data || response.data // Handle both direct data and nested 'data' property
      }
    } catch (error) {
      console.error('Error fetching specializations:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load specializations. Please try again.',
        error
      }
    }
  },

  /**
   * Fetches a single specialization by its ID from the API.
   * @param {string|number} id - The ID of the specialization to fetch.
   * @returns {Promise<object>} An object indicating success, data, or error details.
   */
  async getById(id) {
    try {
      const response = await axios.get(`/api/specializations/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error fetching specialization ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load specialization. Please try again.',
        error
      }
    }
  },

  /**
   * Creates a new specialization via the API.
   * @param {object} data - The data for the new specialization.
   * @returns {Promise<object>} An object indicating success, created data, or error details.
   */
  async create(data) {
    try {
      const response = await axios.post('/api/specializations', data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error creating specialization:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create specialization. Please try again.',
        errors: error.response?.data?.errors || {}, // Include validation errors if available
        error
      }
    }
  },

  /**
   * Updates an existing specialization by its ID via the API.
   * @param {string|number} id - The ID of the specialization to update.
   * @param {object} data - The updated data for the specialization.
   * @returns {Promise<object>} An object indicating success, updated data, or error details.
   */
  async update(id, data) {
    try {
      const response = await axios.put(`/api/specializations/${id}`, data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error updating specialization ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update specialization. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  /**
   * Deletes a specialization by its ID via the API.
   * @param {string|number} id - The ID of the specialization to delete.
   * @returns {Promise<object>} An object indicating success, response data, or error details.
   */
  async delete(id) {
    try {
      const response = await axios.delete(`/api/specializations/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error deleting specialization ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete specialization. Please try again.',
        error
      }
    }
  }
}
export default specializationService
