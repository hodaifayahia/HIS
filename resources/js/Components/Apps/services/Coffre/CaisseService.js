// services/Coffre/CaisseService.js
import axios from 'axios';

export const caisseService = {
    /**
     * Fetches a list of caisses with pagination and filtering
     * @param {Object} params - Query parameters for filtering, searching, and pagination
     * @returns {Promise<Object>} - Response with success status, data, and metadata
     */
    async getAll(params = {}) {
        try {
            if (!params || typeof params !== 'object') {
                throw new Error('Invalid params object. Expected an object but got: ' + typeof params);
            }

            console.log('Fetching caisses with params:', params);

            const response = await axios.get('/api/caisses', { params });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                stats: response.data.stats || null,
                links: response.data.links || null
            };
        } catch (error) {
            console.error('Error fetching caisses:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load cash registers. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches a single caisse by ID
     * @param {number} id - The caisse ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/caisses/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching caisse ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load cash register. Please try again.',
                error
            };
        }
    },

    /**
     * Creates a new caisse
     * @param {Object} data - The caisse data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async create(data) {
        try {
            const response = await axios.post('/api/caisses', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Cash register created successfully.'
            };
        } catch (error) {
            console.error('Error creating caisse:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create cash register. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Updates an existing caisse
     * @param {number} id - The caisse ID
     * @param {Object} data - Updated caisse data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async update(id, data) {
        try {
            const response = await axios.put(`/api/caisses/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Cash register updated successfully.'
            };
        } catch (error) {
            console.error(`Error updating caisse ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update cash register. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Deletes a caisse by ID
     * @param {number} id - The caisse ID
     * @returns {Promise<Object>} - Response with success status
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/caisses/${id}`);
            // Backend returns { success: true, message: "..." } with status 200
            const responseData = response.data || {};
            
            console.log(`Delete caisse ${id} response:`, responseData);
            
            return {
                success: responseData.success !== false, // Treat as success if success is true or not explicitly false
                data: responseData.data || {},
                message: responseData.message || 'Cash register deleted successfully.'
            };
        } catch (error) {
            console.error(`Error deleting caisse ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || error.message || 'Failed to delete cash register. Please try again.',
                error
            };
        }
    },

    /**
     * Toggles caisse status (active/inactive)
     * @param {number} id - The caisse ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async toggleStatus(id) {
        try {
            const response = await axios.patch(`/api/caisses/${id}/toggle-status`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Status updated successfully.'
            };
        } catch (error) {
            console.error(`Error toggling caisse status ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update status. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches all services for selection
     * @returns {Promise<Object>} - Response with services data
     */
    async getServices() {
        try {
            const response = await axios.get('/api/caisses-services');
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
     * Fetches caisse statistics
     * @returns {Promise<Object>} - Response with stats data
     */
    async getStats() {
        try {
            const response = await axios.get('/api/caisses-stats');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching caisse stats:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load statistics.'
            };
        }
    },

    /**
     * Get services for form dropdown (alias for getServices)
     */
    async getServicesForSelect() {
        return this.getServices();
    },

    /**
     * Get active caisses only
     */
    async getActiveCaisses() {
        return this.getAll({ is_active: true });
    },

    /**
     * Get caisses by service
     */
    async getCaissesByService(serviceId) {
        return this.getAll({ service_id: serviceId });
    }
    
};

export default caisseService;
