<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />

    <!-- Header -->
    <StockHeader 
      title="Stock Approvals"
      subtitle="Review and approve stock transfer requests from other services"
      :show-new-button="false"
    />

    <!-- Stats Cards -->
    <StockStatsCards :stats="stats" view-type="receiver" />

    <!-- Main Content -->
    <Card class="tw-shadow-xl tw-mt-8">
      <template #content>
        <StockApprovalsTable 
          :movements="pendingApprovals"
          :loading="loading"
          :filters="filters"
          @approve-request="approveRequest"
          @reject-request="rejectRequest"
          @init-transfer="initTransfer"
          @view-movement="viewMovement"
        />
      </template>
    </Card>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';
import Toast from 'primevue/toast';
import Card from 'primevue/card';

// Components
import StockHeader from '../../../Components/Apps/stock/StockHeader.vue';
import StockStatsCards from '../../../Components/Apps/stock/StockStatsCards.vue';
import StockApprovalsTable from '../../../Components/Apps/stock/StockApprovalsTable.vue';

export default {
  name: 'StockReceiver',
  components: {
    Toast,
    Card,
    StockHeader,
    StockStatsCards,
    StockApprovalsTable
  },
  setup() {
    const router = useRouter();
    const toast = useToast();
    const confirm = useConfirm();

    // Reactive data
    const loading = ref(false);
    const pendingApprovals = ref([]);
    const stats = ref({});
    
    const filters = reactive({
      global: { value: null }
    });

    // Methods
    const loadInitialData = async () => {
      loading.value = true;
      try {
        await Promise.all([
          loadStats(),
          loadPendingApprovals()
        ]);
      } catch (error) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load data'
        });
      } finally {
        loading.value = false;
      }
    };

    const loadPendingApprovals = async () => {
      try {
        const response = await axios.get(`/api/pharmacy/stock-movements?type=receive&status=pending`);
        pendingApprovals.value = Array.isArray(response.data) ? response.data : (response.data.data?.data || response.data.data || []);
      } catch (error) {
        console.error('Failed to load pending approvals:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load pending approvals'
        });
      }
    };

    const loadStats = async () => {
      try {
        const response = await axios.get('/api/pharmacy/stock-movements/stats');
        stats.value = response.data.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    };

    const approveRequest = (movement) => {
      confirm.require({
        message: 'Are you sure you want to approve this stock request?',
        header: 'Approve Request',
        icon: 'pi pi-check',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-success',
        accept: async () => {
          try {
            await axios.post(`/api/pharmacy/stock-movements/${movement.id}/approve`);
            toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Request approved successfully'
            });
            loadPendingApprovals();
            loadStats();
          } catch (error) {
            const message = error.response?.data?.error || 'Failed to approve request';
            toast.add({
              severity: 'error',
              summary: 'Error',
              detail: message
            });
          }
        }
      });
    };

    const rejectRequest = (movement) => {
      confirm.require({
        message: 'Are you sure you want to reject this stock request?',
        header: 'Reject Request',
        icon: 'pi pi-times',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.post(`/api/pharmacy/stock-movements/${movement.id}/reject`);
            toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Request rejected successfully'
            });
            loadPendingApprovals();
            loadStats();
          } catch (error) {
            const message = error.response?.data?.error || 'Failed to reject request';
            toast.add({
              severity: 'error',
              summary: 'Error',
              detail: message
            });
          }
        }
      });
    };

    const initTransfer = (movement) => {
      confirm.require({
        message: 'Are you sure you want to initialize the transfer? This will deduct the requested quantities from your stock.',
        header: 'Initialize Transfer',
        icon: 'pi pi-arrow-right',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-info',
        accept: async () => {
          try {
            await axios.post(`/api/pharmacy/stock-movements/${movement.id}/init-transfer`);
            toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Transfer initialized successfully'
            });
            loadPendingApprovals();
            loadStats();
          } catch (error) {
            const message = error.response?.data?.error || 'Failed to initialize transfer';
            toast.add({
              severity: 'error',
              summary: 'Error',
              detail: message
            });
          }
        }
      });
    };

    const viewMovement = (movement) => {
      router.push({
        name: 'pharmacy.movements.view',
        params: { id: movement.id },
        query: {
          role: 'sender'
        }
      });
    };

    // Lifecycle
    onMounted(() => {
      loadInitialData();
    });

    return {
      // Data
      loading,
      pendingApprovals,
      stats,
      filters,
      
      // Methods
      approveRequest,
      rejectRequest,
      initTransfer,
      viewMovement
    };
  }
};
</script>

<style scoped>
.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>