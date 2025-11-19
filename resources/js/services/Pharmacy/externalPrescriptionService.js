import axios from 'axios';

const API_BASE = '/api/external-prescriptions';

export default {
    /**
     * Get all external prescriptions with filters
     */
    async getAll(params = {}) {
        const response = await axios.get(API_BASE, { params });
        return response.data;
    },

    /**
     * Get a single external prescription by ID
     */
    async getById(id) {
        const response = await axios.get(`${API_BASE}/${id}`);
        return response.data;
    },

    /**
     * Get a single external prescription by ID (alias)
     */
    async getExternalPrescription(id) {
        const response = await axios.get(`${API_BASE}/${id}`);
        return response.data;
    },

    /**
     * Get all external prescriptions (alias)
     */
    async getExternalPrescriptions(params = {}) {
        const response = await axios.get(API_BASE, { params });
        return response.data;
    },

    /**
     * Update external prescription
     */
    async updateExternalPrescription(id, data) {
        const response = await axios.patch(`${API_BASE}/${id}`, data);
        return response.data;
    },

    /**
     * Delete external prescription (alias)
     */
    async deleteExternalPrescription(id) {
        const response = await axios.delete(`${API_BASE}/${id}`);
        return response.data;
    },

    /**
     * Generate PDF
     */
    async generatePDF(prescriptionId) {
        const response = await axios.get(`${API_BASE}/${prescriptionId}/pdf`, {
            responseType: 'blob',
        });
        return response.data;
    },

    /**
     * Create a new external prescription
     */
    async create(data) {
        const response = await axios.post(API_BASE, data);
        return response.data;
    },

    /**
     * Delete an external prescription
     */
    async delete(id) {
        const response = await axios.delete(`${API_BASE}/${id}`);
        return response.data;
    },

    /**
     * Add items to prescription
     */
    async addItems(prescriptionId, items) {
        const response = await axios.post(`${API_BASE}/${prescriptionId}/items`, { items });
        return response.data;
    },

    /**
     * Update item quantity
     */
    async updateItem(prescriptionId, itemId, data) {
        const response = await axios.patch(`${API_BASE}/${prescriptionId}/items/${itemId}`, data);
        return response.data;
    },

    /**
     * Dispense item and add to inventory
     */
    async dispenseItem(prescriptionId, itemId, data) {
        const response = await axios.post(`${API_BASE}/${prescriptionId}/items/${itemId}/dispense`, data);
        return response.data;
    },

    /**
     * Cancel an item with reason
     */
    async cancelItem(prescriptionId, itemId, data) {
        const response = await axios.post(`${API_BASE}/${prescriptionId}/items/${itemId}/cancel`, data);
        return response.data;
    },

    /**
     * Delete an item
     */
    async deleteItem(prescriptionId, itemId) {
        const response = await axios.delete(`${API_BASE}/${prescriptionId}/items/${itemId}`);
        return response.data;
    },

    /**
     * Generate and download PDF
     */
    async downloadPDF(prescriptionId) {
        const response = await axios.get(`${API_BASE}/${prescriptionId}/pdf`, {
            responseType: 'blob',
        });
        
        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `external-prescription-${prescriptionId}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        return response.data;
    },

    /**
     * Get statistics for dashboard
     */
    async getStatistics() {
        const response = await axios.get(`${API_BASE}/statistics`);
        return response.data;
    },
};
