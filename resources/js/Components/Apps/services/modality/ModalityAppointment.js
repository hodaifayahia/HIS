import axios from 'axios';

export const modalityAppointmentServices = {
    /**
     * Fetches a list of modality appointments.
     * Supports pagination, searching, and filtering via query parameters.
     * @param {Object} params - Query parameters for filtering, searching, and pagination.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getAll(params = {}) {
        try {
            // Validate that params is an object
            if (!params || typeof params !== 'object') {
                throw new Error('Invalid params object. Expected an object but got: ' + typeof params);
            }

            console.log('Fetching modality appointments with params:', params);

            // Extract modality_id from params
            const modalityId = params.modality_id;
            if (!modalityId) {
                throw new Error('modality_id is required');
            }

            // Remove modality_id from params since it's now in the URL
            const { modality_id, ...queryParams } = params;

            const response = await axios.get(`/api/modality-appointments/${modalityId}`, { 
                params: queryParams 
            });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null, // For pagination metadata
                links: response.data.links || null // For pagination links
            };
        } catch (error) {
            console.error('Error fetching modality appointments:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load modality appointments. Please try again.',
                error
            };
        }
    },
     async importAppointments(file, modalityId = null) {
        const formData = new FormData();
        formData.append('file', file);
        if (modalityId) {
            formData.append('modality_id', modalityId);
        }

        try {
            const response = await axios.post('/api/modality-appointments/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return {
                success: true,
                data: response.data,
                message: response.data.message || 'Appointments imported successfully'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to import appointments',
                errors: error.response?.data?.errors
            };
        }
    },

    async downloadImportTemplate() {
        try {
            const response = await axios.get('/api/modality-appointments/import-template', {
                responseType: 'blob'
            });
            return {
                success: true,
                data: response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to download template'
            };
        }
    },
     async getAllModalities() {
        try {
            const response = await axios.get('/api/modality-appointments/modalities');
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch modalities'
            };
        }
    },

    /**
     * Fetches a single modality appointment by its ID.
     * @param {number} modalityId - The modality ID.
     * @param {number} appointmentId - The appointment ID.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async getById(modalityId, appointmentId) {
        try {
            const response = await axios.get(`/api/modality-appointments/${modalityId}/${appointmentId}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error(`Error fetching modality appointment ${appointmentId}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load modality appointment. Please try again.',
                error
            };
        }
    },

    /**
     * Creates a new modality appointment.
     * @param {Object} data - The data for the new modality appointment.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/errors.
     */
    async create(data) {
        try {
            const response = await axios.post('/api/modality-appointments', data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Modality appointment created successfully.'
            };
        } catch (error) {
            console.error('Error creating modality appointment:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create modality appointment. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Updates an existing modality appointment.
     * @param {number} id - The ID of the modality appointment to update.
     * @param {Object} data - The updated data for the modality appointment.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/errors.
     */
    async update(id, data) {
        try {
            const response = await axios.put(`/api/modality-appointments/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Modality appointment updated successfully.'
            };
        } catch (error) {
            console.error(`Error updating modality appointment ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update modality appointment. Please check your input.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Deletes a modality appointment by its ID.
     * @param {number} id - The ID of the modality appointment to delete.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/modality-appointments/${id}`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Modality appointment deleted successfully.'
            };
        } catch (error) {
            console.error(`Error deleting modality appointment ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete modality appointment. Please try again.',
                error
            };
        }
    },

    /**
     * Prints a ticket for a modality appointment.
     * @param {Object} data - The data for the ticket.
     * @returns {Promise<Blob>} - A Blob object representing the ticket PDF.
     */
    async printTicket(data) {
        console.log('Printing ticket with data:', data);
        try {
            const response = await axios.post('/api/modality-appointments/print-ticket', data, {
                responseType: 'blob'
            });
            return {
                success: true,
                data: response.data
            };
        } catch (error) {
            console.error('Error printing modality appointment ticket:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to print ticket. Please try again.',
                error
            };
        }
    },

    /**
     * Prints a confirmation ticket for a modality appointment.
     * @param {Object} data - The data for the confirmation ticket.
     * @returns {Promise<Blob>} - A Blob object representing the confirmation ticket PDF.
     */
    async printConfirmationTicket(data) {
        try {
            const response = await axios.post('/api/modality-appointments/print-confirmation-ticket', data, {
                responseType: 'blob'
            });
            return {
                success: true,
                data: response.data
            };
        } catch (error) {
            console.error('Error printing modality appointment confirmation ticket:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to print confirmation ticket. Please try again.',
                error
            };
        }
    },

    /**
     * Changes the status of a modality appointment.
     * @param {number} id - The ID of the modality appointment.
     * @param {Object} payload - The payload containing the new status and optional reason.
     * @returns {Promise<Object>} - An object with success status, data, and optional message/error.
     */
    async changeStatus(id, payload) {
        try {
            const response = await axios.patch(`/api/modality-appointments/${id}/status`, payload);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Status updated successfully.'
            };
        } catch (error) {
            console.error(`Error changing status for appointment ${id}:`, error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update status. Please try again.',
                error
            };
        }
    },
};