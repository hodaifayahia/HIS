import axios from 'axios'

export const prestationPackageService = {
  /**
   * Get all prestation packages
   */
  async getAll(params = {}) {
    try {
      const response = await axios.get('/api/prestation-packages', { params })
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error fetching prestation packages:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestation packages',
        error
      }
    }
  },

  /**
   * Get prestation package by ID
   */
  async getById(id) {
    try {
      const response = await axios.get(`/api/prestation-packages/${id}`)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error fetching prestation package:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load prestation package',
        error
      }
    }
  },

  /**
   * Create a new prestation package
   */
  async create(data) {
    try {
      const response = await axios.post('/api/prestation-packages', data)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error creating prestation package:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create prestation package',
        error
      }
    }
  },

  /**
   * Update an existing prestation package
   */
  async update(id, data) {
    try {
      const response = await axios.put(`/api/prestation-packages/${id}`, data)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error updating prestation package:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update prestation package',
        error
      }
    }
  },

  /**
   * Delete a prestation package
   */
  async delete(id) {
    try {
      await axios.delete(`/api/prestation-packages/${id}`)
      return {
        success: true
      }
    } catch (error) {
      console.error('Error deleting prestation package:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete prestation package',
        error
      }
    }
  },

  /**
   * Clone a prestation package
   */
  async clone(packageId, data) {
    try {
      const response = await axios.post(`/api/prestation-packages/${packageId}/clone`, data)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to clone package'
      }
    }
  }
}

export default prestationPackageService