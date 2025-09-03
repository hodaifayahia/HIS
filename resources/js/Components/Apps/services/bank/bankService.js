// services/Bank/BankService.js
import axios from 'axios';

export const bankService = {
    /**
     * Fetches all banks with pagination and filtering
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} - Response with success status, data, and metadata
     */
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/banks', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load banks.',
                error
            };
        }
    },

    /**
     * Creates a new bank
     * @param {Object} data - The bank data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async create(data) {
        try {
            const response = await axios.post('/api/banks', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Bank created successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create bank.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Updates an existing bank
     * @param {number} id - The bank ID
     * @param {Object} data - Updated bank data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async update(id, data) {
        try {
            const response = await axios.put(`/api/banks/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Bank updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update bank.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Deletes a bank by ID
     * @param {number} id - The bank ID
     * @returns {Promise<Object>} - Response with success status
     */
    async delete(id) {
        try {
            await axios.delete(`/api/banks/${id}`);
            return {
                success: true,
                message: 'Bank deleted successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete bank.',
                error
            };
        }
    },

    /**
     * Toggles bank status
     * @param {number} id - The bank ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async toggleStatus(id) {
        try {
            const response = await axios.patch(`/api/banks/${id}/toggle-status`);
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
     * Gets bank options for dropdowns
     * @returns {Promise<Object>} - Response with bank options
     */
    async getOptions() {
        try {
            const response = await axios.get('/api/banks-options');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load bank options.'
            };
        }
    },

    /**
     * Seeds default banks
     * @returns {Promise<Object>} - Response with success status
     */
    async seedDefault() {
        try {
            const response = await axios.post('/api/banks-seed');
            return {
                success: true,
                message: response.data.message || 'Default banks seeded successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to seed default banks.',
                error
            };
        }
    },

    /**
     * Reorders banks
     * @param {Array} bankIds - Array of bank IDs in new order
     * @returns {Promise<Object>} - Response with success status
     */
    async reorder(bankIds) {
        try {
            const response = await axios.post('/api/banks-reorder', { bank_ids: bankIds });
            return {
                success: true,
                message: response.data.message || 'Banks reordered successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to reorder banks.',
                error
            };
        }
    }
};
