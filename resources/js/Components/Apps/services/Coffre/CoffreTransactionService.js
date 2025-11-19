import axios from 'axios';

export const coffreTransactionService = {
    /**
     * Fetches a list of coffre transactions with pagination and filtering
     * @param {Object} params - Query parameters for filtering, searching, and pagination
     * @returns {Promise<Object>} - Response with success status, data, and metadata
     */
    async getAll(params = {}) {
        try {
            if (!params || typeof params !== 'object') {
                throw new Error('Invalid params object. Expected an object but got: ' + typeof params);
            }

            console.log('Fetching coffre transactions with params:', params);

            const response = await axios.get('/api/coffre-transactions', { params });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
                links: response.data.links || null
            };
        } catch (error) {
            console.error('Error fetching coffre transactions:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transactions. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches a single transaction by ID
     * @param {number} id - The transaction ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/coffre-transactions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching transaction ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transaction. Please try again.',
                error
            };
        }
    },

    /**
     * Creates a new transaction
     * @param {Object} data - The transaction data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async create(data = {}) {
        return axios.post('/api/coffre-transactions', data)
            .then(res => res.data)
            .catch(err => ({ success: false, message: err.message, errors: err.response?.data?.errors || null }));
    },

    /**
     * Creates a new transaction with FormData (for file attachments)
     * @param {FormData} formData - The FormData object containing transaction data and files
     * @returns {Promise<Object>} - Response with success status and data
     */
    async createFormData(formData) {
        return axios.post('/api/coffre-transactions', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        .then(res => res.data)
        .catch(err => ({ success: false, message: err.message, errors: err.response?.data?.errors || null }));
    },

    /**
     * Updates an existing transaction
     * @param {number} id - The transaction ID
     * @param {Object} data - Updated transaction data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async update(id, data = {}) {
        return axios.patch(`/api/coffre-transactions/${id}`, data)
            .then(res => res.data)
            .catch(err => ({ success: false, message: err.message, errors: err.response?.data?.errors || null }));
    },

    /**
     * Updates an existing transaction with FormData (for file attachments)
     * @param {number} id - The transaction ID
     * @param {FormData} formData - The FormData object containing updated transaction data and files
     * @returns {Promise<Object>} - Response with success status and data
     */
    async updateFormData(id, formData) {
        return axios.patch(`/api/coffre-transactions/${id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        .then(res => res.data)
        .catch(err => ({ success: false, message: err.message, errors: err.response?.data?.errors || null }));
    },

    /**
     * Deletes a transaction by ID
     * @param {number} id - The transaction ID
     * @returns {Promise<Object>} - Response with success status
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/coffre-transactions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transaction deleted successfully.'
            };
        } catch (error) {
            console.error(`Error deleting transaction ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete transaction. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches transaction types for dropdown
     * @returns {Promise<Object>} - Response with transaction types
     */
    async getTransactionTypes() {
        try {
            const response = await axios.get('/api/coffre-transactions-types');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching transaction types:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transaction types.'
            };
        }
    },

    /**
     * Fetches all coffres for selection
     * @returns {Promise<Object>} - Response with coffres data
     */
    async getCoffres() {
        try {
            const response = await axios.get('/api/coffres');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching coffres:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load coffres.'
            };
        }
    },

    /**
     * Fetches all users for selection
     * @returns {Promise<Object>} - Response with users data
     */
    async getUsers() {
        try {
            const response = await axios.get('/api/coffre-transactions/users/all');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching users:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load users.'
            };
        }
    },

    /**
     * Fetches all banks for selection
     * @returns {Promise<Object>} - Response with banks data
     */
    async getBanks() {
        try {
            const response = await axios.get('/api/bank-accounts');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching banks:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load banks.'
            };
        }
    },

    /**
     * Get recent transactions for dashboard
     * @param {number} limit - Number of recent transactions to fetch
     * @returns {Promise<Object>} - Response with recent transactions
     */
    async getRecentTransactions(limit = 10) {
        try {
            const response = await axios.get('/api/coffre-transactions/recent', {
                params: { limit }
            });
            return {
                success: true,
                data: response.data.data || []
            };
        } catch (error) {
            console.error('Error fetching recent transactions:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch recent transactions',
                error: error
            };
        }
    }
    // Note: getTransactionTypes and getCoffres are implemented above. Avoid duplicate definitions.
};

export default coffreTransactionService;