// services/Coffre/CaisseSessionService.js
import axios from 'axios';

export const caisseSessionService = {
    /**
     * Fetches a list of caisse sessions with pagination and filtering
     * @param {Object} params - Query parameters for filtering, searching, and pagination
     * @returns {Promise<Object>} - Response with success status, data, and metadata
     */
    async getAll(params = {}) {
        try {
            if (!params || typeof params !== 'object') {
                throw new Error('Invalid params object. Expected an object but got: ' + typeof params);
            }

            console.log('Fetching caisse sessions with params:', params);

            const response = await axios.get('/api/caisse-sessions', { params });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
                links: response.data.links || null
            };
        } catch (error) {
            console.error('Error fetching caisse sessions:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load cash register sessions. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches a single session by ID
     * @param {number} id - The session ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/caisse-sessions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching session ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load session. Please try again.',
                error
            };
        }
    },

    /**
     * Opens a new cash register session
     * @param {Object} data - The session data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async openSession(data) {
        try {
            const response = await axios.post('/api/caisse-sessions', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Session opened successfully.'
            };
        } catch (error) {
            console.error('Error opening session:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to open session. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Closes a cash register session
     * @param {number} id - The session ID
     * @param {Object} data - Closing data
     * @returns {Promise<Object>} - Response with success status and data
     */
    async closeSession(id, data) {
        try {
            const response = await axios.patch(`/api/caisse-sessions/${id}/close`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Session closed successfully.'
            };
        } catch (error) {
            console.error(`Error closing session ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to close session. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Suspends a cash register session
     * @param {number} id - The session ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async suspendSession(id) {
        try {
            const response = await axios.patch(`/api/caisse-sessions/${id}/suspend`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Session suspended successfully.'
            };
        } catch (error) {
            console.error(`Error suspending session ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to suspend session. Please try again.',
                error
            };
        }
    },

    /**
     * Resumes a suspended cash register session
     * @param {number} id - The session ID
     * @returns {Promise<Object>} - Response with success status and data
     */
    async resumeSession(id) {
        try {
            const response = await axios.patch(`/api/caisse-sessions/${id}/resume`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Session resumed successfully.'
            };
        } catch (error) {
            console.error(`Error resuming session ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to resume session. Please try again.',
                error
            };
        }
    },

    /**
     * Deletes a session by ID
     * @param {number} id - The session ID
     * @returns {Promise<Object>} - Response with success status
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/caisse-sessions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Session deleted successfully.'
            };
        } catch (error) {
            console.error(`Error deleting session ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete session. Please try again.',
                error
            };
        }
    },

    /**
     * Gets active sessions
     * @returns {Promise<Object>} - Response with active sessions
     */
    async getActiveSessions() {
        try {
            const response = await axios.get('/api/caisse-sessions-active');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching active sessions:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load active sessions.'
            };
        }
    },

    /**
     * Fetches caisses for selection
     * @returns {Promise<Object>} - Response with caisses data
     */
    async getCaisses() {
        try {
            const response = await axios.get('/api/users');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching caisses:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load cash registers.'
            };
        }
    },

    /**
     * Fetches users for selection
     * @returns {Promise<Object>} - Response with users data
     */
    async getUsers() {
        try {
            const response = await axios.get('/api/caisse-sessions-users');
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
     * Fetches session statistics
     * @returns {Promise<Object>} - Response with stats data
     */
    async getStats() {
        try {
            const response = await axios.get('/api/caisse-sessions-stats');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching session stats:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load statistics.'
            };
        }
    },
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
                message: error.response?.data?.message || 'Failed to load safes.'
            };
        }
    },

    /**
     * Gets standard denominations
     * @returns {Promise<Object>} - Response with denominations data
     */
    async getDenominations() {
        try {
            const response = await axios.get('/api/caisse-sessions-denominations');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching denominations:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load denominations.'
            };
        }
    }
};


export default caisseSessionService;