import axios from 'axios'

const API_BASE_URL = '/api/service-demands'

export const ServiceDemandService = {
  /**
   * Get all service demands with pagination and filters
   */
  async getAll(params = {}) {
    try {
      const response = await axios.get(API_BASE_URL, { params })
      return {
        success: true,
        data: response.data.data,
        meta: response.data.meta || null
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Get a specific service demand by ID
   */
  async getById(id) {
    try {
      const response = await axios.get(`${API_BASE_URL}/${id}`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Create a new service demand
   */
  async store(data) {
    try {
      const response = await axios.post(API_BASE_URL, data)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Update a service demand
   */
  async update(id, data) {
    try {
      const response = await axios.put(`${API_BASE_URL}/${id}`, data)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Delete a service demand
   */
  async delete(id) {
    try {
      const response = await axios.delete(`${API_BASE_URL}/${id}`)
      return {
        success: true,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Send a service demand
   */
  async send(id) {
    try {
      const response = await axios.post(`${API_BASE_URL}/${id}/send`)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Add item to service demand
   */
  async addItem(demandId, itemData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/${demandId}/items`, itemData)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Update item in service demand
   */
  async updateItem(demandId, itemId, itemData) {
    try {
      const response = await axios.put(`${API_BASE_URL}/${demandId}/items/${itemId}`, itemData)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Remove item from service demand
   */
  async removeItem(demandId, itemId) {
    try {
      const response = await axios.delete(`${API_BASE_URL}/${demandId}/items/${itemId}`)
      return {
        success: true,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Assign fournisseur to item
   */
  async assignFournisseurToItem(demandId, itemId, assignmentData) {
    try {
      const response = await axios.post(`${API_BASE_URL}/${demandId}/items/${itemId}/assign-fournisseur`, assignmentData)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Create facture proforma from assignments
   */
  async createFactureProforma(demandId, data) {
    try {
      const response = await axios.post(`${API_BASE_URL}/${demandId}/create-facture-proforma`, data)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors || null
      }
    }
  },

  /**
   * Get services for dropdown
   */
  async getServices() {
    try {
      const response = await axios.get(`${API_BASE_URL}/meta/services`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Get products for dropdown
   */
  async getProducts(params = {}) {
    try {
      const response = await axios.get(`${API_BASE_URL}/meta/products`, { params })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Get fournisseurs for dropdown
   */
  async getFournisseurs() {
    try {
      const response = await axios.get(`${API_BASE_URL}/meta/fournisseurs`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Get statistics
   */
  async getStats() {
    try {
      const response = await axios.get(`${API_BASE_URL}/meta/stats`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Get suggestions
   */
  async getSuggestions() {
    try {
      const response = await axios.get(`${API_BASE_URL}/suggestions`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  },

  /**
   * Get assignment summary for a service demand
   */
  async getAssignmentSummary(demandId) {
    try {
      const response = await axios.get(`${API_BASE_URL}/${demandId}/assignment-summary`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || error.message
      }
    }
  }
}

export default ServiceDemandService