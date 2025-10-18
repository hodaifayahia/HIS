import axios from 'axios';

export const modalityService = {
    /**
     * Fetches a list of modalities.
     * Supports pagination, searching, and filtering via query parameters.
     * @param {Object} params - Query parameters for filtering, searching, and pagination (e.g., { search: 'term', modality_type_id: 1, page: 1, per_page: 10 })
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/modalities', { params });
            // API resource collections often return data in a 'data' key,
            // while raw responses might return it directly. Handle both.
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null, // For pagination metadata
                links: response.data.links || null // For pagination links
            };
        } catch (error) {
            console.error('Error fetching modalities:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load modalities. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches a single modality by its ID.
     * @param {number} id - The modality ID.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/modalities/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching modality ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load modality. Please try again.',
                error
            };
        }
    },

    /**
     * Creates a new modality.
     * @param {Object} data - The data for the new modality (e.g., { name: 'New Modality', modality_type_id: 1, ... }).
     * @returns {Promise<Object>} - An object with success status, data, and optional message/errors.
     */
  async create(data) {
    try {
        // Flatten the schedules array if it is nested
        if (data.schedules && data.schedules.schedules) {
            data.schedules = data.schedules.schedules.map(schedule => ({
                day_of_week: schedule.day_of_week,
                shift_period: schedule.shift_period,
                start_time: schedule.start_time,
                end_time: schedule.end_time,
                is_active: schedule.is_active,
                number_of_patients_per_day: schedule.number_of_patients_per_day,
            }));
        }

        // Send the request to the backend
        const response = await axios.post('/api/modalities', data);

        return {
            success: true,
            data: response.data.data || response.data,
            message: response.data.message || 'Modality created successfully.', // Laravel API resources often return a message
        };
    } catch (error) {
        console.error('Error creating modality:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to create modality. Please check your input.',
            errors: error.response?.data?.errors || {}, // Validation errors from Laravel
            error,
        };
    }
},

    /**
     * Updates an existing modality.
     * @param {number} id - The ID of the modality to update.
     * @param {Object} data - The updated data for the modality.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/errors.
     */
   async update(id, data) {
    
        try {
            // Flatten the schedules array if it is nested, similar to create method
            if (data.schedules && data.schedules.schedules) {
                data.schedules = data.schedules.schedules.map(schedule => ({
                    day_of_week: schedule.day_of_week,
                    shift_period: schedule.shift_period,
                    start_time: schedule.start_time,
                    end_time: schedule.end_time,
                    is_active: schedule.is_active,
                    number_of_patients_per_day: schedule.number_of_patients_per_day,
                }));
            }

            const response = await axios.put(`/api/modalities/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Modality updated successfully.'
            };
        } catch (error) {
            console.error(`Error updating modality ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update modality. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },


    /**
     * Deletes a modality by its ID.
     * @param {number} id - The ID of the modality to delete.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/modalities/${id}`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Modality deleted successfully.'
            };
        } catch (error) {
            console.error(`Error deleting modality ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete modality. Please try again.',
                error
            };
        }
    },

    /**
     * Fetches filter options for modalities (e.g., modality types, specializations).
     * This calls the custom route `getFilterOptions` in your controller.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getFilterOptions() {
        try {
            const response = await axios.get('/api/modalities'); // Assuming you add this route
            return {
                success: true,
                data: response.data
            };
        } catch (error) {
            console.error('Error fetching modality filter options:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load filter options. Please try again.',
                error
            };
        }
    },
    async getModalityTypes() {
    try {
        const response = await axios.get('/api/modality-types');
        return {
            success: true,
            data: response.data.data || response.data,
        };
    } catch (error) {
        console.error('Error fetching modality types:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to load modality types. Please try again.',
            error,
        };
    }
},

    /**
     * Exports modalities based on current filters.
     * This calls the custom route `export` in your controller.
     * @param {Object} params - Query parameters for filtering the export.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async export(params = {}) {
        try {
            const response = await axios.get('/api/modalities/export', { params }); // Assuming you add this route
            return {
                success: true,
                data: response.data.data || response.data, // Export might return data directly or wrapped
                total: response.data.total || response.data.length // Include total if available
            };
        } catch (error) {
            console.error('Error exporting modalities:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to export modalities. Please try again.',
                error
            };
        }
    }
};