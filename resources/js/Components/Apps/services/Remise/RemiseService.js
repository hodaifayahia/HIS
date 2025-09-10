// services/remiseService.js
import { get } from '@vueuse/core'
import axios from 'axios'

export const remiseService = {
  async getAll(params = {}) {
    try {
      const response = await axios.get('/api/remise', { params })
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching remises:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load remises. Please try again.',
        error
      }
    }
  },
  async getRemiseUser(userId) {
    try {
      console.log('Fetching remises for user:', userId);
      const response = await axios.get(`/api/remise/user`, { params: { user_id: userId } })
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error fetching user remises:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load user remises. Please try again.',
        error
      }
    }
  },

  async getById(id) {
    try {
      const response = await axios.get(`/api/remise/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error fetching remise ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load remise. Please try again.',
        error
      }
    }
  },

  async create(data) {
    try {
      const response = await axios.post('/api/remise', data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error creating remise:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create remise. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  async update(id, data) {
    try {
      const response = await axios.put(`/api/remise/${id}`, data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error updating remise ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update remise. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  async delete(id) {
    try {
      const response = await axios.delete(`/api/remise/${id}`)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error(`Error deleting remise ${id}:`, error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete remise. Please try again.',
        error
      }
    }
  },

  async applyRemise(data) {
    try {
      const response = await axios.post('/api/remise/apply', data)
      return {
        success: true,
        data: response.data.data || response.data
      }
    } catch (error) {
      console.error('Error applying remise:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to apply remise. Please try again.',
        errors: error.response?.data?.errors || {},
        error
      }
    }
  },

  async notify(id, payload = {}) {
    try {
      const response = await axios.post(`/api/remise/${id}/notify`, payload)
      return { success: true, data: response.data.data || response.data }
    } catch (error) {
      return { success: false, message: error.response?.data?.message || 'Failed to notify user.' }
    }
  }
}
