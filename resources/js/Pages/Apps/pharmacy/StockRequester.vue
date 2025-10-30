<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Header -->
    <StockHeader 
      title="Stock Requests"
      subtitle="Create and manage your stock transfer requests"
      :show-new-button="true"
      @new-request="showNewRequestDialog = true"
    />

    <!-- Stats Cards -->
    <StockStatsCards :stats="stats" view-type="requester" />

    <!-- Main Content -->
    <Card class="tw-shadow-xl tw-mt-8">
      <template #content>
        <StockRequestsTable 
          :movements="movements"
          :loading="loading"
          :filters="filters"
          @edit-draft="editDraft"
          @send-draft="sendDraft"
          @delete-draft="deleteDraft"
          @view-movement="viewMovement"
        />
      </template>
    </Card>

    <!-- New Request Dialog -->
    <NewRequestDialog 
      v-model:visible="showNewRequestDialog"
      :services="services"
      :user-services="userServices"
      :loading-services="loadingServices"
      :creating-draft="creatingDraft"
      @create-draft="createDraft"
      @close="closeNewRequestDialog"
    />
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Card from 'primevue/card';

// Components
import StockHeader from '../../../Components/Apps/pharmacy/StockHeader.vue';
import StockStatsCards from '../../../Components/Apps/pharmacy/StockStatsCards.vue';
import StockRequestsTable from '../../../Components/Apps/pharmacy/StockRequestsTable.vue';
import NewRequestDialog from '../../../Components/Apps/pharmacy/NewRequestDialog.vue';

export default {
  name: 'StockRequester',
  components: {
    Toast,
    ConfirmDialog,
    Card,
    StockHeader,
    StockStatsCards,
    StockRequestsTable,
    NewRequestDialog
  },
  setup() {
    const router = useRouter();
    const toast = useToast();
    const confirm = useConfirm();

    // Reactive data
    const loading = ref(false);
    const loadingServices = ref(false);
    const creatingDraft = ref(false);
    const showNewRequestDialog = ref(false);
    
    const movements = ref([]);
    const services = ref([]);
    const userServices = ref([]);
    const stats = ref({});
    
    const filters = reactive({
      global: { value: null }
    });

    const newRequest = reactive({
      providing_service_id: null,
      request_reason: '',
      expected_delivery_date: null
    });

    // Computed
    const minDate = computed(() => new Date());

    // Methods
    const loadInitialData = async () => {
      loading.value = true;
      try {
        await Promise.all([
          loadServices(),
          loadStats(),
          loadMovements()
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

    const loadMovements = async () => {
      try {
        // Load movements for all user's services as requester
        const response = await axios.get(`/api/pharmacy/stock-movements?role=requester`);
        movements.value = Array.isArray(response.data) ? response.data : (response.data.data?.data || response.data.data || []);
      } catch (error) {
        console.error('Failed to load movements:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stock movements'
        });
      }
    };

    const loadServices = async () => {
      loadingServices.value = true;
      try {
        const [servicesResponse, userResponse] = await Promise.all([
          axios.get('/api/services?per_page=1000'),
          axios.get('/api/user-info')
        ]);

        const allServices = servicesResponse.data.data || [];
        const userServiceIds = userResponse.data?.service_ids || [];
        
        // User's services - the ones they can request FROM
        userServices.value = allServices.filter(service => userServiceIds.includes(service.id));
        
        // Available services to request from - all services (user can select any)
        services.value = allServices;
        
      } catch (error) {
        console.error('Failed to load services:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load services'
        });
      } finally {
        loadingServices.value = false;
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

    const createDraft = async (formData) => {
      creatingDraft.value = true;
      try {
        const response = await axios.post('/api/pharmacy/stock-movements/create-draft', formData);
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Draft created successfully'
        });

        closeNewRequestDialog();
        router.push({
          name: 'pharmacy.movements.manage',
          params: { id: response.data.data.id }
        });
      } catch (error) {
        const message = error.response?.data?.error || 'Failed to create draft';
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: message
        });
      } finally {
        creatingDraft.value = false;
      }
    };

    const editDraft = (movement) => {
      router.push({
        name: 'pharmacy.movements.manage',
        params: { id: movement.id }
      });
    };

    const sendDraft = (movement) => {
      confirm.require({
        message: 'Are you sure you want to send this request? It will be submitted for approval.',
        header: 'Send Request',
        icon: 'pi pi-send',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-success',
        accept: async () => {
          try {
            await axios.post(`/api/pharmacy/stock-movements/${movement.id}/send`);
            toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Request sent successfully'
            });
            loadMovements();
            loadStats();
          } catch (error) {
            const message = error.response?.data?.error || 'Failed to send request';
            toast.add({
              severity: 'error',
              summary: 'Error',
              detail: message
            });
          }
        }
      });
    };

    const deleteDraft = (movement) => {
      confirm.require({
        message: 'Are you sure you want to delete this draft? This action cannot be undone.',
        header: 'Delete Draft',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-outlined',
        acceptClass: 'p-button-danger',
        accept: async () => {
          try {
            await axios.delete(`/api/pharmacy/stock-movements/${movement.id}`);
            toast.add({
              severity: 'success',
              summary: 'Success',
              detail: 'Draft deleted successfully'
            });
            loadMovements();
            loadStats();
          } catch (error) {
            toast.add({
              severity: 'error',
              summary: 'Error',
              detail: 'Failed to delete draft'
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
          role: 'requester'
        }
      });
    };

    const closeNewRequestDialog = () => {
      showNewRequestDialog.value = false;
      resetNewRequest();
    };

    const resetNewRequest = () => {
      Object.assign(newRequest, {
        providing_service_id: null,
        request_reason: '',
        expected_delivery_date: null
      });
    };

    // Lifecycle
    onMounted(() => {
      loadInitialData();
    });

    return {
      // Data
      loading,
      loadingServices,
      creatingDraft,
      showNewRequestDialog,
      movements,
      services,
      userServices,
      stats,
      filters,
      newRequest,
      minDate,
      
      // Methods
      createDraft,
      editDraft,
      sendDraft,
      deleteDraft,
      viewMovement,
      closeNewRequestDialog
    };
  }
};
</script>

<style scoped>
.tw-shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>