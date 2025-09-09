// composables/useTransferApprovals.js
import { ref } from 'vue'
import axios from 'axios'

export function useTransferApprovals() {
  const loading = ref(false)
  const transferApprovals = ref([])
  const summary = ref({})

  const loadTransferApprovals = async (params = {}) => {
    loading.value = true
    try {
      const response = await axios.get('/api/transfer-approvals', { params })
      transferApprovals.value = response.data.data || []
      summary.value = response.data.summary || {}
      return response.data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const createTransferApproval = async (data) => {
    try {
      const response = await axios.post('/api/transfer-approvals', data)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const updateTransferApproval = async (id, data) => {
    try {
      const response = await axios.put(`/api/transfer-approvals/${id}`, data)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const deleteTransferApproval = async (id) => {
    try {
      const response = await axios.delete(`/api/transfer-approvals/${id}`)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const toggleStatus = async (id) => {
    try {
      const response = await axios.post(`/api/transfer-approvals/${id}/toggle-status`)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const checkApprovalLimit = async (userId, amount) => {
    try {
      const response = await axios.post('/api/transfer-approvals/check-limit', {
        user_id: userId,
        amount: amount
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  return {
    loading,
    transferApprovals,
    summary,
    loadTransferApprovals,
    createTransferApproval,
    updateTransferApproval,
    deleteTransferApproval,
    toggleStatus,
    checkApprovalLimit
  }
}
