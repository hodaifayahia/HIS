// services/Caisse/CaisseTransferService.js
import axios from 'axios';

export const caisseTransferService = {
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/caisse-transfers', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transfers.',
                error
            };
        }
    },

    async getById(id) {
        try {
            const response = await axios.get(`/api/caisse-transfers/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transfer.',
                error
            };
        }
    },

    async create(data) {
        try {
            const response = await axios.post('/api/caisse-transfers', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transfer created successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create transfer.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    async delete(id) {
        try {
            await axios.delete(`/api/caisse-transfers/${id}`);
            return {
                success: true,
                message: 'Transfer deleted successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete transfer.',
                error
            };
        }
    },
    async caisseTransfersPendingRequest() {
        try {
            const response = await axios.get('/api/caisse-transfers-user');
            return {
                success: true,
                data: response.data.data || response.data,
                has_pending_requests: response.data.has_pending_requests,
                pending_count: response.data.pending_count,
                message: response.data.message
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load pending requests.',
                error
            };
        }
    },

    async accept(id, transferToken, amountReceived = null) {
        try {
            const payload = { transfer_token: transferToken };
            if (amountReceived !== null) payload.amount_received = amountReceived;

            const response = await axios.patch(`/api/caisse-transfers/${id}/accept`, payload);
            return {
                success: true,
                data: response.data.data || response.data,
                message: 'Transfer accepted successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to accept transfer.',
                error
            };
        }
    },

    async reject(id) {
        try {
            const response = await axios.patch(`/api/caisse-transfers/${id}/reject`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: 'Transfer rejected successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to reject transfer.',
                error
            };
        }
    },

    async getUserTransfers(userId, type = 'all') {
        try {
            const response = await axios.get('/api/caisse-transfers-auth-user-session', {
              
            });
            console.log(response.data);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load user transfers.',
                error
            };
        }
    },

    async getByToken(token) {
        try {
            const response = await axios.get(`/api/caisse-transfers-by-token/${token}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Transfer not found.',
                error
            };
        }
    },

    async getStats(caisseId = null, userId = null) {
        try {
            const params = {};
            if (caisseId) params.caisse_id = caisseId;
            if (userId) params.user_id = userId;

            const response = await axios.get('/api/caisse-transfers-stats', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load statistics.',
                error
            };
        }
    },
    async getStats(caisseId = null, userId = null) {
        try {
            const params = {};
            if (caisseId) params.caisse_id = caisseId;
            if (userId) params.user_id = userId;

            const response = await axios.get('/api/caisse-transfers-stats', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load statistics.',
                error
            };
        }
    },


    async expireOldTransfers() {
        try {
            const response = await axios.post('/api/caisse-transfers-expire-old');
            return {
                success: true,
                message: response.data.message || 'Old transfers expired successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to expire transfers.',
                error
            };
        }
    }
};
export default caisseTransferService;