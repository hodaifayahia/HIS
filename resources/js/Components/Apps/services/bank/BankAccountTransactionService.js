// services/Bank/BankAccountTransactionService.js
import axios from 'axios';

export const bankAccountTransactionService = {
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/bank-account-transactions', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transactions.',
                error
            };
        }
    },

    async getById(id) {
        try {
            const response = await axios.get(`/api/bank-account-transactions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transaction.',
                error
            };
        }
    },

    async create(data) {
        try {
            const response = await axios.post('/api/bank-account-transactions', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transaction created successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create transaction.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    async update(id, data) {
        try {
            const response = await axios.put(`/api/bank-account-transactions/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transaction updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update transaction.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    async delete(id) {
        try {
            await axios.delete(`/api/bank-account-transactions/${id}`);
            return {
                success: true,
                message: 'Transaction deleted successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete transaction.',
                error
            };
        }
    },

    async complete(id) {
        try {
            const response = await axios.patch(`/api/bank-account-transactions/${id}/complete`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: 'Transaction completed successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to complete transaction.',
                error
            };
        }
    },

    async cancel(id) {
        try {
            const response = await axios.patch(`/api/bank-account-transactions/${id}/cancel`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: 'Transaction cancelled successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to cancel transaction.',
                error
            };
        }
    },

    async reconcile(id, userId) {
        try {
            const response = await axios.patch(`/api/bank-account-transactions/${id}/reconcile`, {
                reconciled_by_user_id: userId
            });
            return {
                success: true,
                data: response.data.data || response.data,
                message: 'Transaction reconciled successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to reconcile transaction.',
                error
            };
        }
    },

    async getStats(bankAccountId = null) {
        try {
            const params = bankAccountId ? { bank_account_id: bankAccountId } : {};
            const response = await axios.get('/api/bank-account-transactions-stats', { params });
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
    }
};
