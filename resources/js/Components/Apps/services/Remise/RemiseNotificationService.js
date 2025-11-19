import axios from 'axios'

export const RemiseNotificationService = {
  // Create comprehensive remise request with prestations and contributions
  async createRequest(payload = {}) {
    try {
      const res = await axios.post('/api/reception/remise-requests', payload)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async getNotifications(params = {}) {
    try {
      const res = await axios.get('/api/reception/remise-requests/notifications', { params })
      return { success: true, data: res.data.data ?? res.data, meta: res.data.meta ?? null, unread_count: res.data.unread_count ?? null }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async getPendingRequests(params = {}) {
    try {
      const res = await axios.get('/api/reception/remise-requests/pending', { params })
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async getRequestHistory(params = {}) {
    try {
      const res = await axios.get('/api/reception/remise-requests/history', { params })
      return { success: true, data: res.data.data ?? res.data, meta: res.data.meta ?? null }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  // Add method to get request details for modal
  async getRequestDetails(requestId) {
    try {
      const res = await axios.get(`/api/reception/remise-requests/${requestId}`)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async approveRequest(requestId, payload = {}) {
    try {
      const res = await axios.patch(`/api/reception/remise-requests/${requestId}/approve`, payload)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async rejectRequest(requestId, payload = {}) {
    try {
      const res = await axios.patch(`/api/reception/remise-requests/${requestId}/reject`, payload)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async applyToSalary(requestId) {
    try {
      const res = await axios.patch(`/api/reception/remise-requests/${requestId}/apply-salary`)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  async markNotificationsRead(notificationIds = []) {
    try {
      const res = await axios.patch('/api/reception/remise-requests/notifications/mark-read', {
        notification_ids: notificationIds
      })
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  // NEW: Get contributions for a specific group
  async getGroupContributions(groupId) {
    try {
      const res = await axios.get(`/api/reception/remise-requests/group/${groupId}/contributions`)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  // NEW: Save contribution drafts
  async saveContributionDrafts(payload = {}) {
    try {
      const res = await axios.post('/api/reception/remise-requests/drafts', payload)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

  // NEW: Update contribution status
  async updateContributionStatus(contributionId, payload = {}) {
    try {
      const res = await axios.patch(`/api/reception/remise-requests/contributions/${contributionId}/status`, payload)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },

// ...existing code...
  // Send fiche_navette id, prestation ids and users to server (use POST)
  async sendFichePrestations(ficheNavetteId, prestationIds = [], users = []) {
    console.log('Sending fiche prestations:', ficheNavetteId, prestationIds, users)
    try {
      const payload = {
        fiche_navette_id: ficheNavetteId,
        prestation_ids: Array.isArray(prestationIds) ? prestationIds : [],
        users: Array.isArray(users) ? users : []
      }
      const res = await axios.post('/api/reception/remise-requests/fiche-prestations', payload)
      return { success: true, data: res.data.data ?? res.data }
    } catch (err) {
      return { success: false, message: err.response?.data?.message ?? err.message }
    }
  },
// ...existing code...
}


export default RemiseNotificationService
