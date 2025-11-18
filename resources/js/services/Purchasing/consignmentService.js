import axios from 'axios';

const BASE_URL = '/api/consignments';

export default {
    /**
     * Get all consignment receptions with filters
     */
    async getAll(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.fournisseur_id) params.append('fournisseur_id', filters.fournisseur_id);
        if (filters.date_from) params.append('date_from', filters.date_from);
        if (filters.date_to) params.append('date_to', filters.date_to);
        if (filters.consignment_code) params.append('consignment_code', filters.consignment_code);
        if (filters.has_uninvoiced !== null && filters.has_uninvoiced !== undefined) {
            params.append('has_uninvoiced', filters.has_uninvoiced);
        }
        
        const response = await axios.get(`${BASE_URL}?${params.toString()}`);
        return response.data;
    },

    /**
     * Get single consignment by ID
     */
    async getById(id) {
        const response = await axios.get(`${BASE_URL}/${id}`);
        return response.data;
    },

    /**
     * Create new consignment reception
     */
    async create(data) {
        const response = await axios.post(BASE_URL, data);
        return response.data;
    },

    /**
     * Get uninvoiced items for a consignment
     */
    async getUninvoicedItems(consignmentId) {
        const response = await axios.get(`${BASE_URL}/${consignmentId}/uninvoiced-items`);
        return response.data;
    },

    /**
     * Create invoice from consumed consignment items
     */
    async createInvoice(consignmentId, data = {}) {
        const response = await axios.post(`${BASE_URL}/${consignmentId}/invoice`, data);
        return response.data;
    },

    /**
     * Get consumption history for a consignment
     */
    async getConsumptionHistory(consignmentId) {
        const response = await axios.get(`${BASE_URL}/${consignmentId}/consumption-history`);
        return response.data;
    },

    /**
     * Confirm consignment reception
     */
    async confirm(consignmentId) {
        const response = await axios.post(`${BASE_URL}/${consignmentId}/confirm`);
        return response.data;
    },

    /**
     * Get supplier statistics
     */
    async getSupplierStats(supplierId) {
        const response = await axios.get(`${BASE_URL}/supplier/${supplierId}/stats`);
        return response.data;
    }
};
