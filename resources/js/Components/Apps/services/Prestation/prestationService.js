// Components/Apps/services/Remise/prestationService.js
import axios from 'axios'

export const prestationService = {
  async getAll() {
    try {
      const response = await axios.get('/api/prestation')
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching prestations:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestations. Please try again.',
        error
      }
    }
  },

  async getById(id) {
    try {
      const response = await axios.get(`/api/prestation/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error fetching prestation ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestation. Please try again.',
        error
      }
    }
  }
}
export default prestationService;
