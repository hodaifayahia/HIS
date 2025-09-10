<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AddPrestationModal from '../../Components/prestation/addPrestationModal.vue'; 

// PrimeVue Components
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import Toolbar from 'primevue/toolbar';

const searchQuery = ref('');
const loading = ref(false);
const selectedOption = ref('prestation');
const router = useRouter();
const route = useRoute();

const showAddModal = ref(false);
const isEditMode = ref(false);
const selectedPrestation = ref(null);
const specializationId = parseInt(route.params.id);

const handleOptionChange = (option) => {
  selectedOption.value = option;
  const isPrestation = option === 'prestation' ? 1 : 0;

  if (isPrestation) {
    router.push({
      name: 'admin.Prestation.Single',
      query: { isPrestation, specialization_id: specializationId },
    });
  } else {
    router.push({
      name: 'admin.Prestation.Group',
      query: { isPrestation, specialization_id: specializationId },
    });
  }
};

const openAddModal = () => {
  selectedPrestation.value = specializationId;
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
  isEditMode.value = false;
  selectedPrestation.value = null;
};

const handleSave = (newPrestation) => {
  console.log('New Prestation:', newPrestation);
  closeAddModal();
};

const handleUpdate = (updatedPrestation) => {
  console.log('Updated Prestation:', updatedPrestation);
  closeAddModal();
};

const debouncedSearch = (() => {
  let timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      // Add search logic here if needed
    }, 300);
  };
})();

watch(searchQuery, debouncedSearch);

onMounted(() => {
  // Fetch any initial data if needed
});
</script>

<template>
  <div class="p-4 surface-ground min-h-screen">
    <Toolbar class="mb-4 surface-card shadow-1 border-round">
      <template #start>
        <Button 
          icon="pi pi-arrow-left" 
          label="Back" 
          class="p-button-text p-button-primary p-button-rounded" 
          @click="router.go(-1)" 
        />
      </template>
      <template #center>
        <h1 class="text-2xl font-bold m-0">Prestations</h1>
      </template>
      <template #end>
        <Button 
          label="Add Prestation" 
          icon="pi pi-plus" 
          class="p-button-primary p-button-rounded" 
          @click="openAddModal"
        />
      </template>
    </Toolbar>

    <div class="p-fluid">
      <div class="p-text-center mb-5">
        <h2 class="text-4xl font-semibold text-color">Select a Prestation Type</h2>
        <p class="text-lg text-color-secondary">Choose between a single prestation or a group of prestations.</p>
      </div>

      <div class="grid p-nogutter">
        <div class="col-12 md:col-6 p-4 flex justify-content-center">
          <Card 
            class="surface-card shadow-4 hover:shadow-6 transition-all transition-duration-300 w-full max-w-25rem cursor-pointer text-center"
            @click="handleOptionChange('prestation')"
          >
            <template #content>
              <i class="pi pi-briefcase text-5xl text-yellow-500 mb-3 block"></i>
              <h3 class="text-2xl font-medium text-color">Prestation</h3>
              <p class="text-color-secondary">Add or manage individual services.</p>
            </template>
          </Card>
        </div>

        <div class="col-12 md:col-6 p-4 flex justify-content-center">
          <Card 
            class="surface-card shadow-4 hover:shadow-6 transition-all transition-duration-300 w-full max-w-25rem cursor-pointer text-center"
            @click="handleOptionChange('group')"
          >
            <template #content>
              <i class="pi pi-objects-column text-5xl text-blue-500 mb-3 block"></i>
              <h3 class="text-2xl font-medium text-color">Group of Prestation</h3>
              <p class="text-color-secondary">Create and manage collections of services.</p>
            </template>
          </Card>
        </div>
      </div>
    </div>
    
    
  </div>
</template>

<style scoped>
/*
  The scoped styles can be minimal or removed entirely
  as PrimeVue handles most of the styling.
  Customizations can still be added here if needed.
*/
.p-card-content {
  padding: 1rem;
}
</style>