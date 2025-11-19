<template>
    <div v-if="pricingInfo" class="pricing-info-panel">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <!-- Average Price -->
            <div class="pricing-card bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-xs text-blue-600 font-semibold mb-1">Average Price</div>
                <div class="text-2xl font-bold text-blue-700">
                    {{ formatPrice(pricingInfo.average_price) }}
                </div>
            </div>

            <!-- Last Price -->
            <div class="pricing-card bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-xs text-green-600 font-semibold mb-1">Last Price</div>
                <div class="text-2xl font-bold text-green-700">
                    {{ formatPrice(pricingInfo.last_price) }}
                </div>
            </div>

            <!-- Current Price -->
            <div class="pricing-card bg-purple-50 border border-purple-200 rounded-lg p-4">
                <div class="text-xs text-purple-600 font-semibold mb-1">Current Price</div>
                <div class="text-2xl font-bold text-purple-700">
                    {{ formatPrice(pricingInfo.current_price) }}
                </div>
            </div>

            <!-- Min Price -->
            <div class="pricing-card bg-orange-50 border border-orange-200 rounded-lg p-4">
                <div class="text-xs text-orange-600 font-semibold mb-1">Min Price</div>
                <div class="text-2xl font-bold text-orange-700">
                    {{ formatPrice(pricingInfo.min_price) }}
                </div>
            </div>

            <!-- Max Price -->
            <div class="pricing-card bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="text-xs text-red-600 font-semibold mb-1">Max Price</div>
                <div class="text-2xl font-bold text-red-700">
                    {{ formatPrice(pricingInfo.max_price) }}
                </div>
            </div>
        </div>

        <!-- Price History Table -->
        <div v-if="pricingInfo.price_history && pricingInfo.price_history.length > 0" class="mt-4">
            <h3 class="text-sm font-semibold mb-2 text-gray-700">Recent Purchase History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bon d'Entrée</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Purchase Price</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sell Price</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">TVA (%)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="(item, index) in pricingInfo.price_history" :key="index" class="hover:bg-gray-50">
                            <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ item.date }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ item.bon_entree_code || 'N/A' }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-right font-medium text-gray-900">
                                {{ formatPrice(item.purchase_price) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-gray-700">
                                {{ formatPrice(item.sell_price) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-gray-700">
                                {{ item.tva }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- No History Message -->
        <div v-else class="mt-4 text-center py-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-500">No purchase history available for this product</p>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProductPricingInfoPanel',
    props: {
        pricingInfo: {
            type: Object,
            default: null
        }
    },
    methods: {
        formatPrice(price) {
            if (price === null || price === undefined) {
                return 'N/A';
            }
            return new Intl.NumberFormat('fr-DZ', {
                style: 'currency',
                currency: 'DZD',
                minimumFractionDigits: 2
            }).format(price);
        }
    }
};
</script>

<style scoped>
.pricing-info-panel {
    background: white;
    border-radius: 8px;
    padding: 16px;
}

.pricing-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.pricing-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
