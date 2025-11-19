import axios from 'axios';

export const coffreService = {
    /**
     * Fetches a list of coffres with pagination and search
     * @param {Object} params - Query parameters for filtering, searching, and pagination
     * @returns {Promise<Object>} - Response with success status, data, and metadata
     */
    async getAll(params = {}) {
        try {
            if (!params || typeof params !== 'object') {
                throw new Error('Invalid params object. Expected an object but got: ' + typeof params);
            }

            console.log('Fetching coffres with params:', params);

            const response = await axios.get('/api/coffres', { params });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                links: response.data.links || null
            };
        } catch (error) {
            console.error('Error fetching coffres:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load coffres. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches a single coffre by ID
     * @param {number} id - The coffre ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/coffres/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching coffre ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load coffre. Please try again.',
                error
            };
        }
    },

    /**
     * Creates a new coffre
     * @param {Object} data - The coffre data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async create(data) {
        try {
            const response = await axios.post('/api/coffres', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Coffre created successfully.'
            };
        } catch (error) {
            console.error('Error creating coffre:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create coffre. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Updates an existing coffre
     * @param {number} id - The coffre ID
     * @param {Object} data - Updated coffre data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async update(id, data) {
        try {
            const response = await axios.put(`/api/coffres/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Coffre updated successfully.'
            };
        } catch (error) {
            console.error(`Error updating coffre ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update coffre. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Deletes a coffre by ID
     * @param {number} id - The coffre ID
     * @returns {Promise<Object>} - Response with success status
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/coffres/${id}`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Coffre deleted successfully.'
            };
        } catch (error) {
            console.error(`Error deleting coffre ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete coffre. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches all services for location selection
     * @returns {Promise<Object>} - Response with services data
     */
    async getServices() {
        try {
            const response = await axios.get('/api/services');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching services:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load services.'
            };
        }
    },

    /**
     * Fetches all users for responsible user selection
     * @returns {Promise<Object>} - Response with users data
     */
    async getUsers() {
        try {
            const response = await axios.get('/api/users');
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
    }
};
export default coffreService;