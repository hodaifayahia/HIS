import axios from 'axios'

/**
 * Service for handling bon commend approval requests
 */
class ApprovalService {
  /**
   * Request approval for a bon commend
   */
  async requestApproval(bonCommendId, thresholdAmount = 10000, notes = '') {
    try {
      const response = await axios.post(`/api/bon-commends/${bonCommendId}/request-approval`, {
        threshold_amount: thresholdAmount,
        notes
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  /**
   * Get pending approvals for the current user
   */
  async getMyPendingApprovals() {
    try {
      const response = await axios.get('/api/bon-commend-approvals/my-pending')
      return response.data
    } catch (error) {
      throw error
    }
  }

  /**
   * Approve a bon commend approval request
   */
  async approve(approvalId, approvalNotes = '') {
    try {
      const response = await axios.post(`/api/bon-commend-approvals/${approvalId}/approve`, {
        approval_notes: approvalNotes
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  /**
   * Reject a bon commend approval request
   */
  async reject(approvalId, approvalNotes = '') {
    try {
      const response = await axios.post(`/api/bon-commend-approvals/${approvalId}/reject`, {
        approval_notes: approvalNotes
      })
      return response.data
    } catch (error) {
      throw error
    }
  }

  /**
   * Cancel an approval request
   */
  async cancel(approvalId) {
    try {
      const response = await axios.post(`/api/bon-commend-approvals/${approvalId}/cancel`)
      return response.data
    } catch (error) {
      throw error
    }
  }

  /**
   * Get approval statistics
   */
  async getStatistics(approvalPersonId = null) {
    try {
      const params = approvalPersonId ? { approval_person_id: approvalPersonId } : {}
      const response = await axios.get('/api/bon-commend-approvals/statistics', { params })
      return response.data
    } catch (error) {
      throw error
    }
  }

  /**
   * Check if bon commend requires approval based on amount
   */
  requiresApproval(totalAmount, threshold = 10000) {
    return totalAmount > threshold
  }
}

export default new ApprovalService()
