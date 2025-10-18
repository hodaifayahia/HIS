import axios from 'axios'

export const prestationService = {
  /**
   * Get all prestations
   */
  async getAll(params = {}) {
    try {
      const response = await axios.get('/api/prestation', { params })
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching prestations:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestations',
        error
      }
    }
  },

  /**
   * Get prestation by ID
   */
  async getById(id) {
    try {
      const response = await axios.get(`/api/prestation/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching prestation:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestation',
        error
      }
    }
  },

  /**
   * Get prestations by IDs (for dependencies)
   */
  async getByIds(ids) {
    try {
      const response = await axios.post('/api/prestation/by-ids', { ids })
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching prestations by IDs:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestations',
        error
      }
    }
  }
}

export default prestationService