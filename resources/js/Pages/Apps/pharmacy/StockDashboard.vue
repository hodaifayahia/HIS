<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <StockHeader 
        title="Stock Dashboard"
        subtitle="Comprehensive overview of your pharmacy stock management system"
        :show-new-button="false"
      />
      
      <!-- Quick Stats Summary -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mt-6">
        <Card class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-font-medium">Active Movements</p>
                <p class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ (stats.draft || 0) + (stats.requesting_pending || 0) + (stats.providing_pending || 0) }}</p>
              </div>
              <i class="pi pi-refresh tw-text-2xl tw-text-blue-500"></i>
            </div>
          </template>
        </Card>

        <Card class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-font-medium">Completed Today</p>
                <p class="tw-text-2xl tw-font-bold tw-text-green-600">{{ stats.executed || 0 }}</p>
              </div>
              <i class="pi pi-check-circle tw-text-2xl tw-text-green-500"></i>
            </div>
          </template>
        </Card>

        <Card class="tw-bg-white/80 tw-backdrop-blur-sm tw-border tw-border-white/20">
          <template #content>
            <div class="tw-flex tw-items-center tw-justify-between">
              <div>
                <p class="tw-text-sm tw-text-gray-600 tw-font-medium">Critical Alerts</p>
                <p class="tw-text-2xl tw-font-bold tw-text-red-600">{{ (stats.controlled_substances_pending || 0) + (stats.prescription_required_pending || 0) }}</p>
              </div>
              <i class="pi pi-exclamation-triangle tw-text-2xl tw-text-red-500"></i>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Stats Cards -->
    <StockStatsCards :stats="stats" view-type="dashboard" />

    <!-- Quick Actions -->
    <div class="tw-mt-8">
      <h2 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-mb-6 tw-flex tw-items-center">
        <i class="pi pi-bolt tw-text-yellow-500 tw-mr-3"></i>
        Quick Actions
      </h2>
      
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-6">
        <!-- Requests Card -->
        <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-cursor-pointer tw-border-l-4 tw-border-blue-500 tw-bg-white/90 tw-backdrop-blur-sm hover:tw-scale-105" @click="navigateToRequests">
          <template #content>
            <div class="tw-text-center tw-p-6">
              <div class="tw-bg-blue-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-send tw-text-3xl tw-text-blue-600"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-mb-2 tw-text-gray-800">Mes Demandes</h3>
              <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed">Créer et gérer vos demandes de transfert de stock</p>
              <Badge :value="stats.requesting_pending || 0" severity="info" class="tw-mt-3" />
            </div>
          </template>
        </Card>

        <!-- Approvals Card -->
        <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-cursor-pointer tw-border-l-4 tw-border-green-500 tw-bg-white/90 tw-backdrop-blur-sm hover:tw-scale-105" @click="navigateToApprovals">
          <template #content>
            <div class="tw-text-center tw-p-6">
              <div class="tw-bg-green-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-check-circle tw-text-3xl tw-text-green-600"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-mb-2 tw-text-gray-800">Approbations</h3>
              <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed">Examiner et approuver les demandes de transfert</p>
              <Badge :value="stats.providing_pending || 0" severity="warning" class="tw-mt-3" />
            </div>
          </template>
        </Card>

        <!-- Products Card -->
        <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-cursor-pointer tw-border-l-4 tw-border-purple-500 tw-bg-white/90 tw-backdrop-blur-sm hover:tw-scale-105" @click="navigateToProducts">
          <template #content>
            <div class="tw-text-center tw-p-6">
              <div class="tw-bg-purple-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-box tw-text-3xl tw-text-purple-600"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-mb-2 tw-text-gray-800">Produits</h3>
              <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed">Gérer la liste des produits et inventaire</p>
              <Badge :value="productCategories.length" severity="success" class="tw-mt-3" />
            </div>
          </template>
        </Card>

        <!-- Services Card -->
        <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-cursor-pointer tw-border-l-4 tw-border-orange-500 tw-bg-white/90 tw-backdrop-blur-sm hover:tw-scale-105" @click="navigateToServices">
          <template #content>
            <div class="tw-text-center tw-p-6">
              <div class="tw-bg-orange-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-building tw-text-3xl tw-text-orange-600"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-mb-2 tw-text-gray-800">Services</h3>
              <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed">Gérer les services et leurs stocks</p>
            </div>
          </template>
        </Card>

        <!-- Stockages Card -->
        <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-cursor-pointer tw-border-l-4 tw-border-indigo-500 tw-bg-white/90 tw-backdrop-blur-sm hover:tw-scale-105" @click="navigateToStockages">
          <template #content>
            <div class="tw-text-center tw-p-6">
              <div class="tw-bg-indigo-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-warehouse tw-text-3xl tw-text-indigo-600"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-mb-2 tw-text-gray-800">Stockages</h3>
              <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed">Gérer les lieux de stockage</p>
            </div>
          </template>
        </Card>

        <!-- Movements Card -->
        <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-300 tw-cursor-pointer tw-border-l-4 tw-border-teal-500 tw-bg-white/90 tw-backdrop-blur-sm hover:tw-scale-105" @click="navigateToMovements">
          <template #content>
            <div class="tw-text-center tw-p-6">
              <div class="tw-bg-teal-100 tw-w-16 tw-h-16 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-4">
                <i class="pi pi-history tw-text-3xl tw-text-teal-600"></i>
              </div>
              <h3 class="tw-text-xl tw-font-semibold tw-mb-2 tw-text-gray-800">Historique</h3>
              <p class="tw-text-gray-600 tw-text-sm tw-leading-relaxed">Voir l'historique complet des mouvements</p>
              <Badge :value="stockMovements.length" severity="info" class="tw-mt-3" />
            </div>
          </template>
        </Card>
      </div>
    </div>

 
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Toast from 'primevue/toast';
import Badge from 'primevue/badge';

import axios from 'axios';

// Components
import StockHeader from '../../../Components/Apps/stock/StockHeader.vue';
import StockStatsCards from '../../../Components/Apps/stock/StockStatsCards.vue';
import InventorySummary from './StockDashboard/InventorySummary.vue';
import ProductCategories from './StockDashboard/ProductCategories.vue';
import ExpiringItems from './StockDashboard/ExpiringItems.vue';
import StockMovementHistory from './StockDashboard/StockMovementHistory.vue';

export default {
  name: 'StockDashboard',
  components: {
    StockHeader,
    StockStatsCards,
    InventorySummary,
    ProductCategories,
    ExpiringItems,
    StockMovementHistory,
    Card,
    Toast,
    Badge
  },
  setup() {
    const router = useRouter();
    const toast = useToast();

    // Reactive data
    const stats = ref({});
    const inventorySummary = ref([]);
    const productCategories = ref([]);
    const expiringItems = ref([]);
    const stockMovements = ref([]);

    // Methods
    const loadStats = async () => {
      try {
        const response = await axios.get('/api/pharmacy/stock-movements/stats');
        console.log('Stats API Response:', response.data);
        stats.value = response.data.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load dashboard statistics'
        });
      }
    };

    // const loadInventorySummary = async () => {
    //   try {
    //     const response = await axios.get('/api/pharmacy/inventory/summary');
    //     console.log('Inventory Summary API Response:', response.data);
    //     inventorySummary.value = response.data.data || [];
    //   } catch (error) {
    //     console.error('Failed to load inventory summary:', error);
    //     toast.add({
    //       severity: 'error',
    //       summary: 'Error',
    //       detail: 'Failed to load inventory summary'
    //     });
    //   }
    // };

    const loadProductCategories = async () => {
      try {
        const response = await axios.get('/api/pharmacy/products/categories');
        console.log('Product Categories API Response:', response.data);
        productCategories.value = response.data.data || [];
      } catch (error) {
        console.error('Failed to load product categories:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load product categories'
        });
      }
    };

    const loadExpiringItems = async () => {
      try {
        const response = await axios.get('/api/pharmacy/inventory/expiring-soon');
        console.log('Expiring Items API Response:', response.data);
        expiringItems.value = response.data.data || [];
      } catch (error) {
        console.error('Failed to load expiring items:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load expiring items'
        });
      }
    };

    const loadStockMovements = async () => {
      try {
        const response = await axios.get('/api/pharmacy/stock-movements/history');
        console.log('Stock Movements API Response:', response.data);
        stockMovements.value = response.data.data || [];
      } catch (error) {
        console.error('Failed to load stock movements:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stock movement history'
        });
      }
    };

    const navigateToRequests = () => {
      router.push({ name: 'stock.requests' });
    };

    const navigateToApprovals = () => {
      router.push({ name: 'stock.approvals' });
    };

    const navigateToProducts = () => {
      router.push({ name: 'stock.productList' });
    };

    const navigateToServices = () => {
      router.push({ name: 'stock.services' });
    };

    const navigateToStockages = () => {
      router.push({ name: 'stock.stockages' });
    };

    const navigateToMovements = () => {
      router.push({ name: 'stock.movements' });
    };

    // Lifecycle
    onMounted(() => {
      loadStats();
      // loadInventorySummary();
      loadProductCategories();
      loadExpiringItems();
      loadStockMovements();
    });

    return {
      // Data
      stats,
      inventorySummary,
      productCategories,
      expiringItems,
      stockMovements,
      
      // Methods
      navigateToRequests,
      navigateToApprovals,
      navigateToProducts,
      navigateToServices,
      navigateToStockages,
      navigateToMovements
    };
  }
};
</script>

<style scoped>
.tw-shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.tw-transition-shadow {
  transition-property: box-shadow;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>