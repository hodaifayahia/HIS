<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />

    <!-- Header -->
    <StockHeader 
      title="Stock Dashboard"
      subtitle="Overview of your stock management system"
      :show-new-button="false"
    />

    <!-- Stats Cards -->
    <StockStatsCards :stats="stats" view-type="dashboard" />

    <!-- Quick Actions -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-3 tw-gap-6 tw-mt-8">
      <!-- Requests Card -->
      <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-shadow tw-cursor-pointer" @click="navigateToRequests">
        <template #content>
          <div class="tw-text-center tw-p-4">
            <i class="pi pi-send tw-text-4xl tw-text-blue-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-mb-2">Mes Demandes</h3>
            <p class="tw-text-gray-600">Créer et gérer vos demandes de transfert de stock</p>
          </div>
        </template>
      </Card>

      <!-- Approvals Card -->
      <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-shadow tw-cursor-pointer" @click="navigateToApprovals">
        <template #content>
          <div class="tw-text-center tw-p-4">
            <i class="pi pi-check-circle tw-text-4xl tw-text-green-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-mb-2">Approbations</h3>
            <p class="tw-text-gray-600">Examiner et approuver les demandes de transfert</p>
          </div>
        </template>
      </Card>

      <!-- Products Card -->
      <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-shadow tw-cursor-pointer" @click="navigateToProducts">
        <template #content>
          <div class="tw-text-center tw-p-4">
            <i class="pi pi-box tw-text-4xl tw-text-purple-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-mb-2">Produits</h3>
            <p class="tw-text-gray-600">Gérer la liste des produits</p>
          </div>
        </template>
      </Card>

      <!-- Services Card -->
      <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-shadow tw-cursor-pointer" @click="navigateToServices">
        <template #content>
          <div class="tw-text-center tw-p-4">
            <i class="pi pi-building tw-text-4xl tw-text-orange-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-mb-2">Services</h3>
            <p class="tw-text-gray-600">Gérer les services et leurs stocks</p>
          </div>
        </template>
      </Card>

      <!-- Stockages Card -->
      <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-shadow tw-cursor-pointer" @click="navigateToStockages">
        <template #content>
          <div class="tw-text-center tw-p-4">
            <i class="pi pi-warehouse tw-text-4xl tw-text-teal-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-mb-2">Stockages</h3>
            <p class="tw-text-gray-600">Gérer les lieux de stockage</p>
          </div>
        </template>
      </Card>

      <!-- Movements Card -->
      <Card class="tw-shadow-lg hover:tw-shadow-xl tw-transition-shadow tw-cursor-pointer" @click="navigateToMovements">
        <template #content>
          <div class="tw-text-center tw-p-4">
            <i class="pi pi-history tw-text-4xl tw-text-indigo-500 tw-mb-4"></i>
            <h3 class="tw-text-xl tw-font-semibold tw-mb-2">Historique</h3>
            <p class="tw-text-gray-600">Voir l'historique complet des mouvements</p>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

// Components
import StockHeader from '../../../Components/Apps/stock/StockHeader.vue';
import StockStatsCards from '../../../Components/Apps/stock/StockStatsCards.vue';

export default {
  name: 'StockDashboard',
  components: {
    StockHeader,
    StockStatsCards
  },
  setup() {
    const router = useRouter();
    const toast = useToast();

    // Reactive data
    const stats = ref({});

    // Methods
    const loadStats = async () => {
      try {
        const response = await axios.get('/api/stock-movements/stats');
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
    });

    return {
      // Data
      stats,
      
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