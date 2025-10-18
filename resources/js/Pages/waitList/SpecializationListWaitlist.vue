<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AddWaitlistModal from '../../Components/waitList/addWaitlistModel.vue';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

// Reactive state
const specializations = ref([]);
const searchQuery = ref('');
const loading = ref(false);
const errorMessage = ref('');
const router = useRouter();

// State for the AddWaitlistModal
const showAddModal = ref(false);
const isEditMode = ref(false);
const selectedWaitlist = ref(null);

// Computed property for filtered specializations
const filteredSpecializations = ref([]);

// Fetch specializations
const getSpecializations = async () => {
  try {
    loading.value = true;
    errorMessage.value = '';
    const response = await axios.get('/api/specializations');
    specializations.value = response.data.data || response.data;
    filterSpecializations();
  } catch (error) {
    console.error('Error fetching specializations:', error);
    errorMessage.value = error.response?.data?.message || 'Failed to load specializations. Please try again later.';
  } finally {
    loading.value = false;
  }
};

// Filter specializations based on search query
const filterSpecializations = () => {
  if (!searchQuery.value) {
    filteredSpecializations.value = specializations.value;
  } else {
    filteredSpecializations.value = specializations.value.filter(spec =>
      spec.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }
};

// Navigate to waitlist types page
const goToWaitlistTypes = (specialization) => {
  router.push({
    name: 'admin.Waitlist.types',
    params: { id: specialization.id },
    query: { name: specialization.name },
  });
};

// Modal functions
const openAddModal = () => {
  showAddModal.value = true;
};

const closeAddModal = () => {
  showAddModal.value = false;
};

const handleSave = (newWaitlist) => {
  console.log('New Waitlist:', newWaitlist);
  closeAddModal();
};

const handleUpdate = (updatedWaitlist) => {
  console.log('Updated Waitlist:', updatedWaitlist);
  closeAddModal();
};

// Watch for search query changes with debounce
let _searchTimeout;
watch(searchQuery, () => {
  clearTimeout(_searchTimeout);
  _searchTimeout = setTimeout(() => {
    filterSpecializations();
  }, 300);
});

// Fetch data on mount
onMounted(() => {
  getSpecializations();
});
</script>

<template>
  <div>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Waitlist Specializations</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Waitlist</li>
              <li class="breadcrumb-item active">Specializations</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container">
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <h2 class="text-center mb-0">Select Specialization for Waitlist</h2>
              <Button
                @click="openAddModal"
                class="p-button-success p-button-rounded"
                icon="pi pi-plus"
                label="Add to Waitlist"
              />
            </div>
          </div>
        </div>

        <div class="mb-4 d-flex justify-content-center">
          <span class="p-input-icon-right" style="width: 100%; max-width: 500px;">
            <i class="pi pi-search" />
            <InputText
              v-model="searchQuery"
              placeholder="Search specializations for waitlist"
              class="p-inputtext-lg w-100"
            />
          </span>
        </div>

        <div v-if="loading" class="d-flex justify-content-center mt-5">
          <ProgressSpinner />
        </div>

        <div v-if="errorMessage" class="mt-4">
          <Message severity="error">{{ errorMessage }}</Message>
        </div>

        <div class="row">
          <div
            v-for="specialization in filteredSpecializations"
            :key="specialization.id"
            class="col-md-3 mb-4 d-flex justify-content-center"
          >
            <Card
              class="text-center shadow-lg specialization-card"
              style="width: 100%; max-width: 250px; border-radius: 15px;"
              @click="goToWaitlistTypes(specialization)"
            >
              <template #content>
                <div class="p-3">
                  <div class="mx-auto" style="width: 120px; height: 120px; overflow: hidden; border-radius: 10px;">
                    <img
                      :src="specialization.photo_url"
                      alt="Specialization image"
                      class="w-100 h-100"
                      style="object-fit: cover;"
                    />
                  </div>
                </div>
                <p class="card-text text-dark fw-bold fs-5 mt-3 mb-2">
                  {{ specialization.name }}
                </p>
              </template>
            </Card>
          </div>
        </div>

        <div v-if="!loading && !errorMessage && filteredSpecializations.length === 0" class="text-center mt-4">
          <div class="alert alert-info">
            <i class="pi pi-info-circle me-2"></i>
            <strong>No specializations found</strong> matching your search criteria.
          </div>
        </div>

        <div v-if="!loading && !errorMessage && specializations.length === 0" class="text-center mt-4">
          <div class="alert alert-warning">
            <i class="pi pi-exclamation-triangle me-2"></i>
            <strong>No specializations available</strong> at the moment.
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Waitlist Modal -->
    <AddWaitlistModal
      :show="showAddModal"
      :editMode="false"
      :waitlist="selectedWaitlist"
      @close="closeAddModal"
      @save="handleSave"
      @update="handleUpdate"
    />
  </div>
</template>

<style scoped>
/* Keep your existing card hover effect and other custom styles */
.specialization-card {
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.specialization-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

/* Adjust PrimeVue InputText for better fit with original design */
.p-inputtext-lg {
  padding: 12px 20px;
  font-size: 16px;
  border-radius: 50px;
  border: 2px solid #e9ecef;
  transition: all 0.3s ease;
}

.p-inputtext-lg:focus {
  border-color: var(--primary-color, #007bff);
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Style for the search icon */
.p-input-icon-right .pi {
  right: 1rem;
  color: #6c757d;
  transition: color 0.3s ease;
}

.p-input-icon-right:focus-within .pi {
  color: var(--primary-color, #007bff);
}

/* Custom button styling to match the theme */
.p-button-success {
  background: linear-gradient(45deg, #28a745, #20c997);
  border: none;
  box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  transition: all 0.3s ease;
}

.p-button-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

/* Alert styling improvements */
.alert {
  border-radius: 10px;
  border: none;
  padding: 1rem 1.5rem;
}

.alert-info {
  background: linear-gradient(45deg, #d1ecf1, #bee5eb);
  color: #0c5460;
}

.alert-warning {
  background: linear-gradient(45deg, #fff3cd, #ffeaa7);
  color: #856404;
}

/* Content header improvements */
.content-header {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  border-bottom: 1px solid #dee2e6;
}

.breadcrumb-item a {
  color: #007bff;
  text-decoration: none;
}

.breadcrumb-item a:hover {
  color: #0056b3;
  text-decoration: underline;
}

/* Card content improvements */
.card-text {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  letter-spacing: 0.5px;
}

.text-muted {
  font-size: 0.9rem;
}

/* Loading spinner custom styling */
.p-progress-spinner {
  width: 3rem;
  height: 3rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
  .col-md-3 {
    flex: 0 0 50%;
    max-width: 50%;
  }
  
  .specialization-card {
    max-width: 200px !important;
  }
}

@media (max-width: 576px) {
  .col-md-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  
  .d-flex.justify-content-between {
    flex-direction: column;
    gap: 1rem;
  }
  
  .p-button-success {
    width: 100%;
  }
}
</style>
