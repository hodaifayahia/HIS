// composables/useCaisseApproval.js
import { ref } from 'vue'
import axios from 'axios'

export function useCaisseApproval() {
  const loading = ref(false)
  const users = ref([])
  const approvers = ref([])

  const loadUsers = async (params = {}) => {
    loading.value = true
    try {
      const response = await axios.get('/api/user-caisse-approval', { params })
      users.value = response.data.data || []
      return response.data
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const grantPermission = async (userId) => {
    try {
      const response = await axios.post('/api/user-caisse-approval', {
        user_id: userId
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  const revokePermission = async (userId) => {
    try {
      const response = await axios.delete(`/api/user-caisse-approval/${userId}`)
      return response.data
    } catch (error) {
      throw error
    }
  }

  const loadApprovers = async () => {
    try {
      const response = await axios.get('/api/user-caisse-approval/approvers')
      approvers.value = response.data.data || []
      return response.data
    } catch (error) {
      throw error
    }
  }

  const checkUserAuth = async () => {
    try {
      const response = await axios.get('/api/user-caisse-approval/check')
      return response.data
    } catch (error) {
      throw error
    }
  }

  return {
    loading,
    users,
    approvers,
    loadUsers,
    grantPermission,
    revokePermission,
    loadApprovers,
    checkUserAuth
  }
}
