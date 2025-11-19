import axios from 'axios';

export const requestTransactionApprovalService = {
    /**
     * Fetches pending approval requests for the current user
     * @returns {Promise<Object>} - Response with success status and data
     */
    async getPendingApprovals() {
        try {
            const response = await axios.get('/api/request-transaction-approvals');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching pending approvals:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load pending approvals. Please try again.',
                error
            };
        }
    },

    /**
     * Approves a transaction approval request
     * @param {number} id - The approval request ID
     * @param {Object|FormData} validationData - Optional validation data for bank account transaction
     * @returns {Promise<Object>} - Response with success status and data
     */
    async approve(id, validationData = {}) {
    try {
        let config = {
            headers: {
                'Accept': 'application/json',
            }
        };
        
        // If validationData is FormData, set appropriate headers
        if (validationData instanceof FormData) {
            config.headers['Content-Type'] = 'multipart/form-data';
        } else {
            config.headers['Content-Type'] = 'application/json';
        }
        
        const response = await axios.patch(`/api/request-transaction-approvals/${id}/approve`, validationData, config);
        
        return {
            success: true,
            data: response.data.data || response.data,
            message: response.data.message || 'Transaction approved successfully.'
        };
    } catch (error) {
        console.error(`Error approving request ${id}:`, error);
        
        // Handle validation errors
        if (error.response?.status === 422 && error.response?.data?.errors) {
            return {
                success: false,
                message: error.response.data.message || 'Validation failed',
                errors: error.response.data.errors,
                error
            };
        }
        
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to approve transaction. Please try again.',
            errors: error.response?.data?.errors || {},
            error
        };
    }
},

    /**
     * Rejects a transaction approval request
     * @param {number} id - The approval request ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async reject(id) {
        try {
            const response = await axios.patch(`/api/request-transaction-approvals/${id}/reject`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transaction rejected successfully.'
            };
        } catch (error) {
            console.error(`Error rejecting request ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to reject transaction. Please try again.',
                error
            };
        }
    }
};

export default requestTransactionApprovalService;
