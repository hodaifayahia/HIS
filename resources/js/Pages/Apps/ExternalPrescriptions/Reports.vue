<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-purple-50/30 tw-to-indigo-50/20 tw-p-6">
    <!-- Header -->
    <div class="tw-mb-8">
      <h1 class="tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-900 tw-to-slate-700 tw-bg-clip-text tw-text-transparent">
        Prescription Reports
      </h1>
      <p class="tw-text-slate-600 tw-mt-2">Analytics and insights for external prescriptions</p>
    </div>

    <!-- Report Filters -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60 tw-mb-8">
      <h2 class="tw-text-xl tw-font-bold tw-text-slate-900 tw-mb-4">Report Filters</h2>
      
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Date Range</label>
          <Calendar 
            v-model="filters.dateRange" 
            selectionMode="range" 
            :manualInput="false"
            placeholder="Select date range"
            class="tw-w-full"
            dateFormat="yy-mm-dd"
          />
        </div>
        
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">Status</label>
          <Dropdown
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All Status"
            class="tw-w-full"
          />
        </div>
        
        <div class="tw-flex tw-items-end">
          <Button 
            label="Generate Report"
            icon="pi pi-chart-bar"
            class="p-button-success tw-w-full"
            @click="generateReport"
            :loading="loading"
          />
        </div>
      </div>
    </div>

    <!-- Report Summary Cards -->
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
          <div class="tw-bg-gradient-to-br tw-from-purple-500 tw-to-indigo-600 tw-p-3 tw-rounded-xl">
            <i class="pi pi-file-edit tw-text-white tw-text-2xl"></i>
          </div>
        </div>
        <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Total Prescriptions</p>
        <h3 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mt-1">{{ reportData.totalPrescriptions }}</h3>
      </div>

      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
          <div class="tw-bg-gradient-to-br tw-from-green-400 tw-to-emerald-500 tw-p-3 tw-rounded-xl">
            <i class="pi pi-box tw-text-white tw-text-2xl"></i>
          </div>
        </div>
        <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Total Products</p>
        <h3 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mt-1">{{ reportData.totalProducts }}</h3>
      </div>

      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
          <div class="tw-bg-gradient-to-br tw-from-blue-400 tw-to-cyan-500 tw-p-3 tw-rounded-xl">
            <i class="pi pi-percentage tw-text-white tw-text-2xl"></i>
          </div>
        </div>
        <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Dispensed Rate</p>
        <h3 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mt-1">{{ reportData.dispensedRate }}%</h3>
      </div>

      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
          <div class="tw-bg-gradient-to-br tw-from-amber-400 tw-to-orange-500 tw-p-3 tw-rounded-xl">
            <i class="pi pi-user-md tw-text-white tw-text-2xl"></i>
          </div>
        </div>
        <p class="tw-text-sm tw-text-slate-600 tw-font-medium">Active Doctors</p>
        <h3 class="tw-text-3xl tw-font-bold tw-text-slate-900 tw-mt-1">{{ reportData.activeDoctors }}</h3>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-6 tw-mb-8">
      <!-- Prescriptions by Status Chart -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <h2 class="tw-text-xl tw-font-bold tw-text-slate-900 tw-mb-6">Prescriptions by Status</h2>
        <div class="tw-h-64 tw-flex tw-items-center tw-justify-center tw-bg-slate-50 tw-rounded-xl">
          <p class="tw-text-slate-500">Chart placeholder - integrate PrimeVue Chart component</p>
        </div>
      </div>

      <!-- Top Products Chart -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-border tw-border-slate-200/60">
        <h2 class="tw-text-xl tw-font-bold tw-text-slate-900 tw-mb-6">Top Prescribed Products</h2>
        <div class="tw-h-64 tw-flex tw-items-center tw-justify-center tw-bg-slate-50 tw-rounded-xl">
          <p class="tw-text-slate-500">Chart placeholder - integrate PrimeVue Chart component</p>
        </div>
      </div>
    </div>

    <!-- Detailed Report Table -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden">
      <div class="tw-p-6 tw-border-b tw-border-slate-200">
        <h2 class="tw-text-xl tw-font-bold tw-text-slate-900">Detailed Report</h2>
      </div>
      
      <DataTable 
        :value="reportData.prescriptions" 
        :paginator="true" 
        :rows="10"
        responsiveLayout="scroll"
        stripedRows
        class="p-datatable-sm"
      >
        <template #empty>
          <div class="tw-text-center tw-py-12">
            <i class="pi pi-inbox tw-text-6xl tw-text-slate-300 tw-mb-4"></i>
            <p class="tw-text-slate-500 tw-text-lg">No data available. Please select filters and generate report.</p>
          </div>
        </template>
        
        <Column field="prescription_code" header="Prescription Code" :sortable="true"></Column>
        <Column field="doctor.name" header="Doctor" :sortable="true"></Column>
        <Column field="total_items" header="Total Items" :sortable="true"></Column>
        <Column field="dispensed_items" header="Dispensed" :sortable="true"></Column>
        <Column field="status" header="Status" :sortable="true">
          <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="tw-rounded-lg"/>
          </template>
        </Column>
        <Column field="created_at" header="Date" :sortable="true">
          <template #body="slotProps">
            {{ formatDate(slotProps.data.created_at) }}
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import externalPrescriptionService from '@/services/Pharmacy/externalPrescriptionService';

const toast = useToast();

// State
const loading = ref(false);
const filters = ref({
  dateRange: null,
  status: null,
});

const statusOptions = ref([
  { label: 'All Status', value: null },
  { label: 'Draft', value: 'draft' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' },
]);

const reportData = ref({
  totalPrescriptions: 0,
  totalProducts: 0,
  dispensedRate: 0,
  activeDoctors: 0,
  prescriptions: [],
});

// Methods
const generateReport = async () => {
  loading.value = true;
  try {
    const params = {
      start_date: filters.value.dateRange?.[0]?.toISOString().split('T')[0],
      end_date: filters.value.dateRange?.[1]?.toISOString().split('T')[0],
      status: filters.value.status,
    };
    
    const response = await externalPrescriptionService.getExternalPrescriptions(params);
    const prescriptions = response.data;
    
    // Calculate stats
    const totalProducts = prescriptions.reduce((sum, p) => sum + (p.total_items || 0), 0);
    const totalDispensed = prescriptions.reduce((sum, p) => sum + (p.dispensed_items || 0), 0);
    const uniqueDoctors = new Set(prescriptions.map(p => p.doctor_id)).size;
    
    reportData.value = {
      totalPrescriptions: prescriptions.length,
      totalProducts: totalProducts,
      dispensedRate: totalProducts > 0 ? Math.round((totalDispensed / totalProducts) * 100) : 0,
      activeDoctors: uniqueDoctors,
      prescriptions: prescriptions,
    };
    
    toast.add({ 
      severity: 'success', 
      summary: 'Success', 
      detail: 'Report generated successfully', 
      life: 3000 
    });
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Failed to generate report', 
      life: 3000 
    });
  } finally {
    loading.value = false;
  }
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
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
