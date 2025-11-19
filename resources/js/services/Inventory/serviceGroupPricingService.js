import axios from 'axios'

export default {
    /**
     * Get all service groups
     */
    async getServiceGroups() {
        const response = await axios.get('/api/service-groups')
        return response.data.data
    },

    /**
     * Get pricing info for a product across service groups
     */
    async getProductPricingByGroups(productId, isPharmacy = false) {
        const response = await axios.get(`/api/service-group-pricing/product/${productId}/by-groups`, {
            params: { is_pharmacy: isPharmacy ? 1 : 0 }
        })
        return response.data.data
    },

    /**
     * Batch update pricing for multiple products and service groups
     */
    async batchUpdatePricing(pricingData) {
        const response = await axios.post('/api/service-group-pricing/batch-update', pricingData)
        return response.data
    },

    /**
     * Get pricing configuration for a specific service group
     */
    async getServiceGroupPricing(serviceGroupId, productId, isPharmacy = false) {
        const response = await axios.get(`/api/service-group-pricing/${serviceGroupId}/product/${productId}`, {
            params: { is_pharmacy: isPharmacy ? 1 : 0 }
        })
        return response.data.data
    },

    /**
     * Create or update pricing for a service group and product
     */
    async updateServiceGroupPricing(data) {
        const response = await axios.post('/api/service-group-pricing/update', data)
        return response.data
    }
}
