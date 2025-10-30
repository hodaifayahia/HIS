<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const router = useRouter();
const route = useRoute();

// State for the AddWaitlistModal
const showAddModal = ref(false);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);
const specializationId = parseInt(route.params.id);

// Function to handle option change
const handleOptionChange = (option) => {
  const isDaily = option === 'daily' ? 1 : 0;

  // Navigate to the PrimeVue versions of the waitlist components
  if (isDaily) {
    router.push({
      name: 'admin.Waitlist.DailyPrime',
      query: { isDaily, specialization_id: specializationId },
    });
  } else {
    router.push({
      name: 'admin.Waitlist.GeneralPrime',
      query: { isDaily, specialization_id: specializationId },
    });
  }
};

// Open modal for adding a new waitlist
const openAddModal = () => {
  selectedWaitlist.value = specializationId;
  showAddModal.value = true;
};

// Close modal
const closeAddModal = () => {
  showAddModal.value = false;
  isEditMode.value = false;
  selectedWaitlist.value = null;
};

// Handle save event (for adding)
const handleSave = (newWaitlist) => {
  toast.add({ severity: 'success', summary: 'Success', detail: 'Waitlist entry added successfully', life: 3000 });
  closeAddModal();
};

// Handle update event (for editing)
const handleUpdate = (updatedWaitlist) => {
  toast.add({ severity: 'success', summary: 'Success', detail: 'Waitlist entry updated successfully', life: 3000 });
  closeAddModal();
};

onMounted(() => {
  // Component mounted
});
</script>

<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-100 tw-p-6">
    <Toast />
    
    <!-- Header Section -->
    <div class="tw-mb-8">
      <div class="tw-flex tw-items-center tw-justify-between tw-mb-6">
        <div class="tw-flex tw-items-center tw-gap-4">
          <Button 
            icon="pi pi-arrow-left" 
            severity="secondary" 
            outlined 
            @click="router.go(-1)"
            class="tw-rounded-full tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
            size="large"
          />
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-text-gray-800 tw-mb-2">Waitlist Management</h1>
            <p class="tw-text-gray-600 tw-text-lg">Choose your waitlist type to get started</p>
          </div>
        </div>
        <nav class="tw-text-sm tw-text-gray-600 tw-bg-white tw-px-4 tw-py-2 tw-rounded-full tw-shadow-sm">
          <span>Home</span>
          <i class="pi pi-angle-right tw-mx-2"></i>
          <span class="tw-text-blue-600 tw-font-medium">Waitlist</span>
        </nav>
      </div>

      <!-- Quick Add Button -->
      <div class="tw-flex tw-justify-center tw-mb-8">
        <Button 
          label="Quick Add to Waitlist"
          icon="pi pi-plus"
          severity="success"
          @click="openAddModal"
          class="tw-rounded-full tw-px-8 tw-py-3 tw-text-lg tw-font-semibold tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105"
        />
      </div>
    </div>

    <!-- Main Content Cards -->
    <div class="tw-max-w-6xl tw-mx-auto">
      <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-8">
        <!-- Daily Waitlist Card -->
        <Card 
          class="tw-cursor-pointer tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105 hover:tw-shadow-2xl tw-border-0 tw-bg-gradient-to-br tw-from-orange-50 tw-to-yellow-50"
          @click="handleOptionChange('daily')"
        >
          <template #content>
            <div class="tw-text-center tw-p-8">
              <div class="tw-mb-6">
                <div class="tw-w-24 tw-h-24 tw-mx-auto tw-bg-gradient-to-br tw-from-orange-400 tw-to-yellow-500 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-shadow-lg tw-mb-4">
                  <i class="pi pi-calendar-plus tw-text-4xl tw-text-white"></i>
                </div>
                <h2 class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-mb-3">Daily Waitlist</h2>
                <p class="tw-text-gray-600 tw-text-lg tw-leading-relaxed">
                  Manage today's appointments and urgent cases. Perfect for daily operations and immediate scheduling needs.
                </p>
              </div>
              
              <div class="tw-flex tw-flex-wrap tw-gap-2 tw-justify-center tw-mb-6">
                <span class="tw-bg-orange-100 tw-text-orange-800 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  <i class="pi pi-clock tw-mr-1"></i>
                  Today Only
                </span>
                <span class="tw-bg-yellow-100 tw-text-yellow-800 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  <i class="pi pi-bolt tw-mr-1"></i>
                  Quick Access
                </span>
              </div>
              
              <Button 
                label="Open Daily Waitlist"
                icon="pi pi-arrow-right"
                severity="warning"
                class="tw-w-full tw-rounded-full tw-py-3 tw-text-lg tw-font-semibold tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                @click.stop="handleOptionChange('daily')"
              />
            </div>
          </template>
        </Card>

        <!-- General Waitlist Card -->
        <Card 
          class="tw-cursor-pointer tw-transition-all tw-duration-300 tw-transform hover:tw-scale-105 hover:tw-shadow-2xl tw-border-0 tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-50"
          @click="handleOptionChange('general')"
        >
          <template #content>
            <div class="tw-text-center tw-p-8">
              <div class="tw-mb-6">
                <div class="tw-w-24 tw-h-24 tw-mx-auto tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-shadow-lg tw-mb-4">
                  <i class="pi pi-calendar tw-text-4xl tw-text-white"></i>
                </div>
                <h2 class="tw-text-3xl tw-font-bold tw-text-gray-800 tw-mb-3">General Waitlist</h2>
                <p class="tw-text-gray-600 tw-text-lg tw-leading-relaxed">
                  Comprehensive waitlist management for all appointments. Advanced filtering and long-term scheduling.
                </p>
              </div>
              
              <div class="tw-flex tw-flex-wrap tw-gap-2 tw-justify-center tw-mb-6">
                <span class="tw-bg-blue-100 tw-text-blue-800 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  <i class="pi pi-calendar tw-mr-1"></i>
                  All Dates
                </span>
                <span class="tw-bg-indigo-100 tw-text-indigo-800 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
                  <i class="pi pi-filter tw-mr-1"></i>
                  Advanced Filters
                </span>
              </div>
              
              <Button 
                label="Open General Waitlist"
                icon="pi pi-arrow-right"
                severity="primary"
                class="tw-w-full tw-rounded-full tw-py-3 tw-text-lg tw-font-semibold tw-shadow-md hover:tw-shadow-lg tw-transition-all tw-duration-200"
                @click.stop="handleOptionChange('general')"
              />
            </div>
          </template>
        </Card>
      </div>

      <!-- Feature Comparison Section -->
      <div class="tw-mt-12">
        <Card class="tw-bg-white tw-shadow-xl tw-border-0">
          <template #header>
            <div class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-p-6 tw-border-b">
              <h3 class="tw-text-2xl tw-font-bold tw-text-gray-800 tw-text-center tw-flex tw-items-center tw-justify-center tw-gap-3">
                <i class="pi pi-info-circle tw-text-blue-500"></i>
                Feature Comparison
              </h3>
            </div>
          </template>
          
          <template #content>
            <div class="tw-p-6">
              <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-8">
                <!-- Daily Waitlist Features -->
                <div class="tw-space-y-4">
                  <h4 class="tw-text-xl tw-font-semibold tw-text-orange-600 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-calendar-plus"></i>
                    Daily Waitlist Features
                  </h4>
                  <ul class="tw-space-y-3">
                    <li class="tw-flex tw-items-center tw-gap-3">
                      <i class="pi pi-check tw-text-green-500"></i>
                      <span>Today's appointments only</span>
                    </li>
                   
                  </ul>
                </div>
                
                <!-- General Waitlist Features -->
                <div class="tw-space-y-4">
                  <h4 class="tw-text-xl tw-font-semibold tw-text-blue-600 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-calendar"></i>
                    General Waitlist Features
                  </h4>
                  <ul class="tw-space-y-3">
                    <li class="tw-flex tw-items-center tw-gap-3">
                      <i class="pi pi-check tw-text-green-500"></i>
                      <span>All appointments management</span>
                    </li>
                   
                  </ul>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Add/Edit Waitlist Modal -->
    <AddWaitlistModal
      :show="showAddModal"
      :editMode="false"
      :specializationId="specializationId"
      @close="closeAddModal"
      @save="handleSave"
      @update="handleUpdate"
    />
  </div>
</template>

<style scoped>
/* Custom PrimeVue overrides */
:deep(.p-card) {
  @apply border-0 tw-overflow-hidden;
}

:deep(.p-card-content) {
  @apply p-0;
}

:deep(.p-button) {
  @apply transition-all tw-duration-200;
}

/* Gradient animations */
@keyframes gradient-shift {
  0% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0% 50%;
  }
}

.tw-bg-gradient-to-br {
  background-size: 200% 200%;
  animation: gradient-shift 6s ease infinite;
}

/* Hover effects */
.hover\:tw-scale-105:hover {
  transform: scale(1.05);
}

/* Custom shadows */
.tw-shadow-2xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>