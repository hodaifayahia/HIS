// services/Caisse/FinancialTransactionService.js
import axios from 'axios';

export const financialTransactionService = {
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/financial-transactions', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transactions.',
                error
            };
        }
    },

    async getById(id) {
        try {
            const response = await axios.get(`/api/financial-transactions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load transaction.',
                error
            };
        }
    },

    async create(data) {
        try {
            const response = await axios.post('/api/financial-transactions', data);
            return {
                success: true,
                data: response.data.data || response.data,
                updatedItems: response.data.updated_items || null,
                message: response.data.message || 'Transaction created successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create transaction.',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    async update(id, data) {
        try {
            const response = await axios.put(`/api/financial-transactions/${id}`, data);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transaction updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update transaction.',
                error
            };
        }
    },

    async delete(id) {
        try {
            const response = await axios.delete(`/api/financial-transactions/${id}`);
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Transaction deleted successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete transaction.',
                error
            };
        }
    },

    async updatePrestationPrice(prestationId, ficheNavetteItemId, newFinalPrice, paidAmount) {
        try {
            const response = await axios.post(`/api/reception/fiche-navette/${ficheNavetteItemId}/items`, {
                prestation_id: prestationId,
                fiche_navette_item_id: ficheNavetteItemId,
                new_final_price: newFinalPrice,
                paid_amount: paidAmount
            });
            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message || 'Prestation price updated successfully.'
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update prestation price.',
                error
            };
        }
    },

    async getStats(ficheNavetteItemId = null) {
        try {
            const params = ficheNavetteItemId ? { fiche_navette_item_id: ficheNavetteItemId } : {};
            const response = await axios.get('/api/financial-transactions-stats', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load statistics.',
                error
            };
        }
    },

    // New: get prestations with dependencies (accepts patient_id OR fiche_navette_id)
    async getPrestationsWithDependencies({ patientId = null, ficheNavetteId = null } = {}) {
        try {
            const params = {};
            if (patientId) params.patient_id = patientId;
            if (ficheNavetteId) params.fiche_navette_id = ficheNavetteId;

            // Updated route name to avoid conflicts
            const response = await axios.post(`/api/reception/fiche-navette/${ficheNavetteItemId}/items`, {
                patient_id: patientId,
                fiche_navette_id: ficheNavetteId
            });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load prestations.',
                error
            };
        }
    }
};

export default financialTransactionService;