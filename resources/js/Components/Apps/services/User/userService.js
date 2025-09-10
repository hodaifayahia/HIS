// Components/Apps/services/Remise/userService.js
import axios from 'axios'

export const userService = {
  async getAll() {
    try {
      const response = await axios.get('/api/users')
      return { success: true, data: response.data.data || response.data }
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Failed to load users.' }
    }
  },

  async getById(id) {
    try {
      const response = await axios.get(`/api/users/${id}`)
      return { success: true, data: response.data.data || response.data }
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Failed to load user.' }
    }
  },

  
}
