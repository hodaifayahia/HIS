import axios from 'axios'
import { getPaymentMethodsForDropdown } from '@/utils/enums/PaymentMethodEnum'

export const paymentMethodService = {
  /**
   * Get all users with their payment methods
   */
  async getAllUsersWithPaymentMethods(params = {}) {
    try {
      const response = await axios.get('/api/user-payment-methods', { params })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      console.error('Error fetching users with payment methods:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load users with payment methods',
        error
      }
    }
  },

  /**
   * Get user payment methods by user ID
   */
  async getUserPaymentMethods(userId) {
    try {
      const response = await axios.get(`/api/user-payment-methods/${userId}`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      console.error('Error fetching user payment methods:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to load user payment methods',
        error
      }
    }
  },

  /**
   * Bulk assign payment methods to users
   */
  async bulkAssignPaymentMethods(data) {
    try {
      const response = await axios.post('/api/user-payment-methods', data)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error bulk assigning payment methods:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to assign payment methods',
        error
      }
    }
  },

  /**
   * Update user payment methods
   */
  async updateUserPaymentMethods(userId, data) {
    try {
      const response = await axios.put(`/api/user-payment-methods/${userId}`, data)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Error updating user payment methods:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update payment methods',
        error
      }
    }
  },

  /**
   * Delete user payment methods
   */
  async deleteUserPaymentMethods(userId) {
    try {
      await axios.delete(`/api/user-payment-methods/${userId}`)
      return {
        success: true
      }
    } catch (error) {
      console.error('Error deleting user payment methods:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete payment methods',
        error
      }
    }
  },

  /**
   * Get available payment methods (now using local enum)
   */
  getAvailablePaymentMethods() {
    return {
      success: true,
      data: getPaymentMethodsForDropdown()
    }
  }
}

export default paymentMethodService
