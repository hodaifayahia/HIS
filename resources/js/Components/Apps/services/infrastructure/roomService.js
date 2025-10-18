import axios from 'axios';

export const roomService = {
    /**
     * Fetches a list of all rooms.
     * Supports pagination, searching, and filtering via query parameters.
     * @param {Object} params - Query parameters for filtering, searching, and pagination
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/rooms', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null, // For pagination metadata
                links: response.data.links || null // For pagination links
            };
        } catch (error) {
            console.error('Error fetching rooms:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load rooms. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches a single room by its ID.
     * @param {number} id - The room ID.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/rooms/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching room ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load room. Please try again.',
                error
            };
        }
    }
};