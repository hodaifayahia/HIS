import axios from 'axios';

export default {
    /**
     * Get product pricing information from Bon d'Entrée history.
     */
    async getProductPricingInfo(productId, isPharmacy = false) {
        const response = await axios.get(`/api/purchasing/bon-entrees/product/${productId}/pricing-info`, {
            params: { is_pharmacy: isPharmacy }
        });
        return response.data;
    },

    /**
     * Get pricing for a product in a service group.
     */
    async getServiceGroupPricing(serviceGroupId, productId, isPharmacy = false) {
        const response = await axios.get(
            `/api/purchasing/service-groups/${serviceGroupId}/pricing/product/${productId}`,
            { params: { is_pharmacy: isPharmacy } }
        );
        return response.data;
    },

    /**
     * Update pricing for a product in a service group.
     */
    async updateServiceGroupPricing(serviceGroupId, productId, pricingData) {
        const response = await axios.post(
            `/api/purchasing/service-groups/${serviceGroupId}/pricing/product/${productId}`,
            pricingData
        );
        return response.data;
    },

    /**
     * Get price history for a product in a service group.
     */
    async getPriceHistory(serviceGroupId, productId, isPharmacy = false, limit = 10) {
        const response = await axios.get(
            `/api/purchasing/service-groups/${serviceGroupId}/pricing/product/${productId}/history`,
            { params: { is_pharmacy: isPharmacy, limit } }
        );
        return response.data;
    },

    /**
     * Bulk update prices for multiple products.
     */
    async bulkUpdatePrices(serviceGroupId, isPharmacy, products, percentageIncrease = null) {
        const response = await axios.post(
            `/api/purchasing/service-groups/${serviceGroupId}/pricing/bulk-update`,
            {
                is_pharmacy: isPharmacy,
                products,
                percentage_increase: percentageIncrease
            }
        );
        return response.data;
    },

    /**
     * Get all pricing for a service group.
     */
    async getServiceGroupPricingList(serviceGroupId, isPharmacy = false) {
        const response = await axios.get(
            `/api/purchasing/service-groups/${serviceGroupId}/pricing`,
            { params: { is_pharmacy: isPharmacy } }
        );
        return response.data;
    },

    /**
     * Delete a pricing record.
     */
    async deletePricing(pricingId) {
        const response = await axios.delete(`/api/purchasing/pricing/${pricingId}`);
        return response.data;
    }
};
