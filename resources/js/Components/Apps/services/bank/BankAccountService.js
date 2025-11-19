// services/Bank/BankAccountService.js
import axios from 'axios';

export const bankAccountService = {
    /**
     * Fetches all bank accounts with pagination and filtering
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} - Response with success status, data, and metadata
     */
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/bank-accounts', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
                links: response.data.links || null
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load bank accounts.',
                error
            };
        }
    },

    /**
     * Fetches a single bank account by ID
     * @param {number} id - The bank account ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/bank-accounts/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load bank account.',
                error
            };
        }
    },

    /**
     * Creates a new bank account
     * @param {Object} data - The bank account data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async create(data) {
        try {
            const response = await axios.post('/api/bank-accounts', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Bank account created successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create bank account.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Updates an existing bank account
     * @param {number} id - The bank account ID
     * @param {Object} data - Updated bank account data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async update(id, data) {
        try {
            const response = await axios.put(`/api/bank-accounts/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Bank account updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update bank account.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Deletes a bank account by ID
     * @param {number} id - The bank account ID
     * @returns {Promise<Object>} - Response with success status
     */
    async delete(id) {
        try {
            await axios.delete(`/api/bank-accounts/${id}`);
            return {
                success: true,
                message: 'Bank account deleted successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete bank account.',
                error
            };
        }
    },

    /**
     * Toggles bank account status
     * @param {number} id - The bank account ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async toggleStatus(id) {
        try {
            const response = await axios.patch(`/api/bank-accounts/${id}/toggle-status`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Status updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update status.',
                error
            };
        }
    },

    /**
     * Updates account balance
     * @param {number} id - The bank account ID
     * @param {Object} balanceData - Balance update data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async updateBalance(id, balanceData) {
        try {
            const response = await axios.patch(`/api/bank-accounts/${id}/update-balance`, balanceData);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Balance updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update balance.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Gets active bank accounts
     * @returns {Promise<Object>} - Response with active bank accounts
     */
    async getActive() {
        try {
            const response = await axios.get('/api/bank-accounts-active');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load active accounts.'
            };
        }
    },

    /**
     * Gets bank accounts by currency
     * @param {string} currency - Currency code
     * @returns {Promise<Object>} - Response with bank accounts data
     */
    async getByCurrency(currency) {
        try {
            const response = await axios.get(`/api/bank-accounts-by-currency/${currency}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load accounts by currency.'
            };
        }
    },

    /**
     * Gets bank accounts by bank
     * @param {number} bankId - Bank ID
     * @returns {Promise<Object>} - Response with bank accounts data
     */
    async getByBank(bankId) {
        try {
            const response = await axios.get(`/api/bank-accounts-by-bank/${bankId}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load accounts by bank.'
            };
        }
    },

    /**
     * Gets bank account statistics
     * @returns {Promise<Object>} - Response with stats data
     */
    async getStats() {
        try {
            const response = await axios.get('/api/bank-accounts-stats');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load statistics.'
            };
        }
    },

    /**
     * Gets available currencies
     * @returns {Promise<Object>} - Response with currencies
     */
    async getCurrencies() {
        try {
            const response = await axios.get('/api/bank-accounts-currencies');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load currencies.'
            };
        }
    },

    /**
     * Gets banks for dropdowns
     * @returns {Promise<Object>} - Response with banks data
     */
    async getBanks() {
        try {
            const response = await axios.get('/api/bank-accounts-banks');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load banks.'
            };
        }
    },

    /**
     * Syncs account balances
     * @returns {Promise<Object>} - Response with sync result
     */
    async syncBalances() {
        try {
            const response = await axios.post('/api/bank-accounts-sync-balances');
            return {
                success: true,
                message: response.data.message || 'Balances synchronized successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to sync balances.'
            };
        }
    }
};
