// services/Prestation/PrestationPaymentService.js
import axios from 'axios';

export const prestationPaymentService = {
    /**
     * Get prestations with payment information from fiche navette
     */
    async getPrestations(params = {}) {
        try {
            const { ficheNavetteItemId, ...otherParams } = params;
            
            if (!ficheNavetteItemId) {
                throw new Error('ficheNavetteItemId is required');
            }

            const response = await axios.post(`/api/reception/fiche-navette/${ficheNavetteItemId}/items`, {
                patient_id: otherParams.patient_id,
                fiche_navette_id: otherParams.fiche_navette_id,
                ...otherParams
            });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load prestations.',
                error
            };
        }
    },

    /**
     * Get prestations by fiche navette ID (alternative method)
     */
    async getPrestationsByFicheNavette(ficheNavetteId, patientId = null) {
        try {
            const response = await axios.get(`/api/reception/fiche-navette/${ficheNavetteId}/prestations`, {
                params: {
                    patient_id: patientId,
                    include_payments: true
                }
            });
            
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load prestations.',
                error
            };
        }
    },

    /**
     * Get all prestations with payment info (general method)
     */
    async getAllPrestationsWithPayments(params = {}) {
        try {
            const response = await axios.get('/api/reception/prestations-with-payments', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load prestations.',
                error
            };
        }
    },

    /**
     * Get fiche navette options for filtering
     */
    async getFicheNavetteOptions() {
        try {
            const response = await axios.get('/api/reception/fiche-navette');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load fiche navette options.',
                error
            };
        }
    },

    /**
     * Get prestations for a specific patient
     */
    async getPatientPrestations(patientId, params = {}) {
        try {
            const response = await axios.get(`/api/patients/${patientId}/prestations-with-payments`, { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || null,
                summary: response.data.summary || null,
            };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to load patient prestations.',
                error
            };
        }
    }
};
