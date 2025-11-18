<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-purple-50/30 tw-to-indigo-50/20 tw-p-6">
    <!-- Header -->
    <div class="tw-mb-8">
      <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
        External Prescriptions Dashboard
      </h1>
      <p class="tw-text-slate-600 tw-mt-2">Overview of prescription management activities</p>
    </div>

    <!-- Stats Grid -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <!-- Total Prescriptions -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Total Prescriptions</p>
            <h3 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mt-2">{{ stats.total }}</h3>
            <p class="tw-text-xs tw-text-green-600 tw-mt-2 tw-flex tw-items-center tw-gap-1">
              <i class="pi pi-arrow-up"></i>
              <span>12% from last month</span>
            </p>
          </div>
          <div class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-600 tw-p-4 tw-rounded-xl">
            <i class="pi pi-file-edit tw-text-white tw-text-3xl"></i>
          </div>
        </div>
      </div>

      <!-- Draft Prescriptions -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Draft</p>
            <h3 class="tw-text-3xl tw-font-bold tw-text-amber-600 tw-mt-2">{{ stats.draft }}</h3>
            <p class="tw-text-xs tw-text-slate-500 tw-mt-2">Pending completion</p>
          </div>
          <div class="tw-bg-gradient-to-br tw-from-amber-400 tw-to-orange-500 tw-p-4 tw-rounded-xl">
            <i class="pi pi-clock tw-text-white tw-text-3xl"></i>
          </div>
        </div>
      </div>

      <!-- Confirmed Prescriptions -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Confirmed</p>
            <h3 class="tw-text-3xl tw-font-bold tw-text-green-600 tw-mt-2">{{ stats.confirmed }}</h3>
            <p class="tw-text-xs tw-text-slate-500 tw-mt-2">Ready to dispense</p>
          </div>
          <div class="tw-bg-gradient-to-br tw-from-green-400 tw-to-emerald-500 tw-p-4 tw-rounded-xl">
            <i class="pi pi-check-circle tw-text-white tw-text-3xl"></i>
          </div>
        </div>
      </div>

      <!-- Cancelled Prescriptions -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Cancelled</p>
            <h3 class="tw-text-3xl tw-font-bold tw-text-red-600 tw-mt-2">{{ stats.cancelled }}</h3>
            <p class="tw-text-xs tw-text-slate-500 tw-mt-2">Not dispensed</p>
          </div>
          <div class="tw-bg-gradient-to-br tw-from-red-400 tw-to-rose-500 tw-p-4 tw-rounded-xl">
            <i class="pi pi-times-circle tw-text-white tw-text-3xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity & Charts -->
    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6">
      <!-- Recent Prescriptions -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
          <h2 class="tw-text-xl tw-font-bold tw-text-slate-900">Recent Prescriptions</h2>
          <Button 
            label="View All" 
            class="p-button-text p-button-sm"
            @click="$router.push('/external-prescriptions/list')"
          />
        </div>
        
        <div class="tw-space-y-4">
          <div 
            v-for="prescription in recentPrescriptions" 
            :key="prescription.id"
            class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-bg-slate-50 tw-rounded-xl hover:tw-bg-slate-100 tw-transition-all tw-cursor-pointer"
            @click="viewPrescription(prescription)"
          >
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-purple-100 tw-p-2 tw-rounded-lg">
                <i class="pi pi-file-edit tw-text-purple-600"></i>
              </div>
              <div>
                <p class="tw-font-semibold tw-text-slate-900">{{ prescription.prescription_code }}</p>
                <p class="tw-text-xs tw-text-slate-500">{{ prescription.doctor?.name }}</p>
              </div>
            </div>
            <div class="tw-text-right">
              <Tag :value="prescription.status" :severity="getStatusSeverity(prescription.status)" class="tw-rounded-lg"/>
              <p class="tw-text-xs tw-text-slate-500 tw-mt-1">{{ formatDate(prescription.created_at) }}</p>
            </div>
          </div>
          
          <div v-if="recentPrescriptions.length === 0" class="tw-text-center tw-py-8 tw-text-slate-500">
            <i class="pi pi-inbox tw-text-4xl tw-mb-2"></i>
            <p>No recent prescriptions</p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <h2 class="tw-text-xl tw-font-bold tw-text-slate-900 tw-mb-6">Quick Actions</h2>
        
        <div class="tw-grid tw-grid-cols-1 tw-gap-4">
          <Button 
            label="Create New Prescription"
            icon="pi pi-plus"
            class="p-button-lg tw-justify-start tw-bg-gradient-to-r tw-from-purple-500 tw-to-indigo-600"
            @click="$router.push('/external-prescriptions/create')"
          />
          
          <Button 
            label="View My Prescriptions"
            icon="pi pi-user"
            class="p-button-lg p-button-outlined tw-justify-start"
            @click="$router.push('/external-prescriptions/my-prescriptions')"
          />
          
          <Button 
            label="Draft Prescriptions"
            icon="pi pi-clock"
            class="p-button-lg p-button-outlined tw-justify-start"
            @click="$router.push('/external-prescriptions/drafts')"
          />
          
          <Button 
            label="Confirmed Prescriptions"
            icon="pi pi-check-circle"
            class="p-button-lg p-button-outlined tw-justify-start"
            @click="$router.push('/external-prescriptions/confirmed')"
          />
          
          <Button 
            label="Generate Reports"
            icon="pi pi-chart-bar"
            class="p-button-lg p-button-outlined tw-justify-start"
            @click="$router.push('/external-prescriptions/reports')"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import externalPrescriptionService from '@/services/Pharmacy/externalPrescriptionService';

const router = useRouter();

// State
const stats = ref({
  total: 0,
  draft: 0,
  confirmed: 0,
  cancelled: 0,
});
const recentPrescriptions = ref([]);

// Methods
const loadDashboardData = async () => {
  try {
    const response = await externalPrescriptionService.getExternalPrescriptions({ limit: 5 });
    const prescriptions = response.data;
    
    stats.value = {
      total: prescriptions.length,
      draft: prescriptions.filter(p => p.status === 'draft').length,
      confirmed: prescriptions.filter(p => p.status === 'confirmed').length,
      cancelled: prescriptions.filter(p => p.status === 'cancelled').length,
    };
    
    recentPrescriptions.value = prescriptions.slice(0, 5);
  } catch (error) {
    console.error('Failed to load dashboard data:', error);
  }
};

const viewPrescription = (prescription) => {
  router.push(`/external-prescriptions/${prescription.id}`);
};

const getStatusSeverity = (status) => {
  const severities = {
    draft: 'warning',
    confirmed: 'success',
    cancelled: 'danger',
  };
  return severities[status] || 'info';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Lifecycle
onMounted(() => {
  loadDashboardData();
});
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
