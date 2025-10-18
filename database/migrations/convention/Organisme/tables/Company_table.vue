<template>
  <div class="w-full p-4">
    <!-- Search Bar & Add Button -->
    <div class="flex flex-col lg:flex-row justify-between items-center mb-4 gap-2">
      <div class="relative flex-grow flex items-center gap-2">
        <Dropdown v-model="searchFilter" :options="filterOptions" optionLabel="label" optionValue="value"
          placeholder="Filter By" class="border rounded-lg" />
        <InputText v-model="searchQuery" placeholder="Search..." class="w-full p-2 border rounded-lg" />
      </div>
      <Button label="Add Company" icon="pi pi-plus" @click="openAddDialog" />
    </div>

    <!-- PrimeVue DataTable -->
    <DataTable :value="filteredItems" stripedRows paginator :rows="8" tableStyle="min-width: 50rem">
      <Column field="id" header="ID"></Column>
      <Column field="name" header="Name"></Column>
      <Column field="address" header="Address"></Column>
      <Column field="phone" header="Phone"></Column>
      <Column field="email" header="Email"></Column>
      <Column header="Edit">
        <template #body="slotProps">
          <Button icon="pi pi-pencil" severity="warn" size="small"  @click="editItem(slotProps.data)" />
        </template>
      </Column>
      <Column header="Details">
        <template #body="slotProps">
          <Button icon="pi pi-eye" severity="info" label="Details" size="small" @click="viewContract(slotProps.data)" />
        </template>
      </Column>

      <template #empty>
        <div class="text-center text-gray-500 py-6 flex flex-col items-center">
          <i class="pi pi-building text-3xl mb-2"></i>
          <span>No companies found.</span>
        </div>
      </template>
    </DataTable>

    <!-- Add Company Dialog -->
    <Dialog v-model:visible="addDialog" header="Add Company" modal>
      <div class="p-fluid flex flex-col px-5 w-full lg:w-96">
        <label>Name:</label>
        <InputText size="small" v-model="newCompany.name" />
        <small v-if="addFormErrors.name" class="text-red-500">{{ addFormErrors.name }}</small>

        <label class="mt-3">Address:</label>
        <InputText size="small" v-model="newCompany.address" />
        <small v-if="addFormErrors.address" class="text-red-500">{{ addFormErrors.address }}</small>

        <label class="mt-3">Phone:</label>
        <InputText size="small" v-model="newCompany.phone" />
        <small v-if="addFormErrors.phone" class="text-red-500">{{ addFormErrors.phone }}</small>

        <label class="mt-3">Email:</label>
        <InputText size="small" v-model="newCompany.email" />
        <small v-if="addFormErrors.email" class="text-red-500">{{ addFormErrors.email }}</small>

        <!-- First company notification -->
        <div v-if="isFirstCompany" class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg border border-red-300">
          <i class="pi pi-exclamation-triangle mr-2"></i>
          <strong>Important:</strong> This company will be set as the public company (Oisis) and will automatically be
          assigned the general contract for the system.
        </div>

        <div class="mt-4 text-right">
          <Button label="Cancel" icon="pi pi-times" @click="addDialog = false" class="p-button-text" />
          <Button label="Save" icon="pi pi-check" @click="saveCompany" class="p-button-primary ml-2" />
        </div>
      </div>
    </Dialog>

    <!-- Edit Dialog -->
    <Dialog v-model:visible="editDialog" header="Edit Company" modal>
      <div class="p-fluid flex flex-col px-5 w-full lg:w-96">
        <label>Name:</label>
        <InputText size="small" v-model="selectedCompany.name" />
        <small v-if="editFormErrors.name" class="text-red-500">{{ editFormErrors.name }}</small>

        <label class="mt-3">Address:</label>
        <InputText size="small" v-model="selectedCompany.address" />
        <small v-if="editFormErrors.address" class="text-red-500">{{ editFormErrors.address }}</small>

        <label class="mt-3">Phone:</label>
        <InputText size="small" v-model="selectedCompany.phone" />
        <small v-if="editFormErrors.phone" class="text-red-500">{{ editFormErrors.phone }}</small>

        <label class="mt-3">Email:</label>
        <InputText size="small" v-model="selectedCompany.email" />
        <small v-if="editFormErrors.email" class="text-red-500">{{ editFormErrors.email }}</small>

        <div class="mt-4 text-right">
          <Button label="Cancel" icon="pi pi-times" @click="editDialog = false" class="p-button-text" />
          <Button label="Save" icon="pi pi-check" @click="updateCompany" class="p-button-primary ml-2" />
        </div>
      </div>
    </Dialog>

    <!-- Toast for notifications -->
    <Toast />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { useRouter } from 'vue-router';
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;
const router = useRouter();
const toast = useToast();
const searchQuery = ref("");
const searchFilter = ref("name"); // Default filter by name
const isFirstCompany = ref(false);

// Dropdown options for filtering
const filterOptions = ref([
  { label: "By Name", value: "name" },
  { label: "By ID", value: "id" },
  { label: "By Address", value: "address" },
  { label: "By Phone", value: "phone" },
  { label: "By Email", value: "email" }
]);

const items = ref([]);

// Form error refs
const addFormErrors = ref({});
const editFormErrors = ref({});

// Validation function
const validateForm = (company, formErrors) => {
  // Clear previous errors
  formErrors.value = {};
  let isValid = true;

  if (!company.name.trim()) {
    formErrors.value.name = "Name is required";
    isValid = false;
  }

  if (!company.address.trim()) {
    formErrors.value.address = "Address is required";
    isValid = false;
  }

  if (!company.phone.trim() || company.phone.trim().length !== 10) {
    formErrors.value.phone = "Phone number must be exactly 10 digits";
    isValid = false;
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!company.email.trim() || !emailPattern.test(company.email.trim())) {
    formErrors.value.email = "Please enter a valid email address";
    isValid = false;
  }

  return isValid;
};

const fetchCompanies = async () => {
  try {
    const response = await axios.get(`${API_BASE_URL}/api/convention/companies`);
    items.value = response.data.map(company => ({
      id: company.id,
      name: company.company_name,
      address: company.address,
      phone: company.phone_number,
      email: company.email
    }));

    // Check if this is the first company to be added
    isFirstCompany.value = items.value.length === 0;
  } catch (error) {
    console.error("Failed to fetch companies:", error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load companies',
      life: 3000
    });
  }
};

onMounted(() => {
  fetchCompanies();
});

const addDialog = ref(false);
const newCompany = ref({ name: "", address: "", phone: "", email: "" });

const editDialog = ref(false);
const selectedCompany = ref({});

const filteredItems = computed(() => {
  return items.value.filter(item => {
    const searchValue = searchQuery.value.toLowerCase();
    return String(item[searchFilter.value]).toLowerCase().includes(searchValue);
  });
});

const openAddDialog = () => {
  newCompany.value = { name: "", address: "", phone: "", email: "" };
  addFormErrors.value = {}; // Clear form errors

  // Check if this will be the first company
  isFirstCompany.value = items.value.length === 0;

  addDialog.value = true;
};

const saveCompany = async () => {
  // Validate form before proceeding
  if (!validateForm(newCompany.value, addFormErrors)) {
    return;
  }

  try {
    // Map the form fields to match backend expected fields
    const companyData = {
      company_name: newCompany.value.name,
      address: newCompany.value.address,
      phone_number: newCompany.value.phone,
      email: newCompany.value.email
    };

    // Call the POST API endpoint
    const response = await axios.post(`${API_BASE_URL}/api/convention/companies`, companyData);

    // Add the new company to the items array
    const addedCompany = response.data.company;
    items.value.unshift({
      id: addedCompany.id,
      name: addedCompany.company_name,
      address: addedCompany.address,
      phone: addedCompany.phone_number,
      email: addedCompany.email
    });

    // Show success toast with appropriate message
    let successMsg = 'Company created successfully';
    if (response.data.isPublic) {
      successMsg = 'Public company created with general contract successfully';
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: successMsg,
      life: 3000
    });

    addDialog.value = false;

    // After adding, we need to update isFirstCompany flag
    isFirstCompany.value = false;
  } catch (error) {
    // Show error toast with backend error message
    const errorMessage = error.response?.data?.message || 'Failed to create company';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    });
  }
};

const editItem = (item) => {
  selectedCompany.value = { ...item };
  editFormErrors.value = {}; // Clear form errors
  editDialog.value = true;
};

const updateCompany = async () => {
  // Validate form before proceeding
  if (!validateForm(selectedCompany.value, editFormErrors)) {
    return;
  }

  try {
    // Map the form fields to match backend expected fields
    const companyData = {
      company_name: selectedCompany.value.name,
      address: selectedCompany.value.address,
      phone_number: selectedCompany.value.phone,
      email: selectedCompany.value.email
    };

    // Call the PUT API endpoint
    await axios.put(`${API_BASE_URL}/api/convention/companies/${selectedCompany.value.id}`, companyData);

    // Update the company in the items array
    const index = items.value.findIndex(item => item.id === selectedCompany.value.id);
    if (index !== -1) {
      items.value[index] = { ...selectedCompany.value };
    }

    // Show success toast
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Company updated successfully',
      life: 3000
    });

    editDialog.value = false;
  } catch (error) {
    // Show error toast with backend error message
    const errorMessage = error.response?.data?.message || 'Failed to update company';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 3000
    });
  }
};

const viewContract = (company) => {
  router.push({
    path: `/company/${company.id}`,
  });
};
</script>