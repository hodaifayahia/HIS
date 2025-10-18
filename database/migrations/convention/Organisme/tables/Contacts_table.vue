<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useRouter } from "vue-router"; // Assuming you still need useRouter for navigation
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const toast = useToast();
const confirm = useConfirm();
const router = useRouter(); // Initialize useRouter

const props = defineProps({
  companyId: {
    type: String,
    required: true,
  },
});

const loading = ref(false);
const addDialog = ref(false);
const editDialog = ref(false);

const searchQuery = ref("");
const searchFilter = ref({ label: "By name", value: "name" }); // Default filter by name

const filterOptions = ref([
  { label: "By name", value: "name" },
  { label: "By ID", value: "id" },
  { label: "By Job Function", value: "role" },
  { label: "By Phone", value: "phone" },
  {label:'start_date', value: 'start_date'},
  { label: "By End Date", value: "end_date" },
  { label: "By Company ID", value: "organisme_id" },
  { label: "By Email", value: "email" }
]);

const items = ref([]);
const newContact = ref({ name: "", role: "", phone: "", email: "", company_id: props.companyId });
const selectedContact = ref({});
const formErrors = ref({
  name: "",
  role: "",
  phone: "",
  email: ""
});

// Fetch contacts for the current company
const fetchContacts = async () => {
  loading.value = true;
  try {
    // Using /api/organisme-contacts with company_id as a query parameter
    // This assumes your backend's OrganismeContactController can filter by company_id
    const response = await axios.get(`/api/organisme-contacts`, {
      params: { organisme_id: props.companyId }
    });

    const contactData = Array.isArray(response.data) ? response.data :
      (response.data.contacts || response.data.data || []);

    items.value = contactData.map(contact => ({
      id: contact.id,
      name: contact.name || contact.name,
      organisme_id: contact.company_id,
      role: contact.role || contact.fonction || "",
      phone: contact.phone || contact.phone || "",
      email: contact.email || ""
    }));

    toast.add({ severity: 'success', summary: 'Success', detail: 'Contacts loaded successfully', life: 3000 });
  } catch (error) {
    console.error("Failed to fetch contacts:", error);
    items.value = [];
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to load contacts', life: 3000 });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchContacts();
  
});

const filteredItems = computed(() => {
  const itemsArray = Array.isArray(items.value) ? items.value : [];

  if (!searchQuery.value && !searchFilter.value) {
    return itemsArray;
  }

  const searchValue = searchQuery.value.toLowerCase();
  const filterKey = searchFilter.value.value; // Get the value from the selected dropdown option

  return itemsArray.filter((item) => {
    const itemValue = String(item[filterKey] || "").toLowerCase();
    return itemValue.includes(searchValue);
  });
});

const resetFormErrors = () => {
  formErrors.value = {
    name: "",
    role: "",
    phone: "",
    email: ""
  };
};

const validateForm = (formData) => {
  resetFormErrors();
  let isValid = true;

  if (!formData.name) {
    formErrors.value.name = "name is required";
    isValid = false;
  }

  if (!formData.phone) {
    formErrors.value.phone = "Phone number is required";
    isValid = false;
  } else if (formData.phone.length !== 10 || !/^\d+$/.test(formData.phone)) {
    formErrors.value.phone = "Phone number must be 10 digits";
    isValid = false;
  }

  if (!formData.email) {
    formErrors.value.email = "Email is required";
    isValid = false;
  } else {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      formErrors.value.email = "Invalid email format";
      isValid = false;
    }
  }
  return isValid;
};

const openAddDialog = () => {
  resetFormErrors();
  newContact.value = { name: "", role: "", phone: "", email: "", company_id: props.companyId };
  addDialog.value = true;
};

const saveContact = async () => {
  if (!validateForm(newContact.value)) {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please correct the form errors.', life: 3000 });
    return;
  }

  try {
    // POST to /api/organisme-contacts
    const response = await axios.post(`/api/organisme-contacts`, {
      name: newContact.value.name,
      role: newContact.value.role,
      phone: newContact.value.phone,
      email: newContact.value.email,
      organisme_id: props.companyId // Ensure company_id is sent with new contact
    });

    // Assuming the API returns the created contact data
    const createdContact = response.data.data || response.data;
    items.value.push({
      id: createdContact.id,
      name: createdContact.name || createdContact.name,
      organisme_id: createdContact.company_id,
      role: createdContact.role || createdContact.fonction || "",
      phone: createdContact.phone || createdContact.phone || "",
      email: createdContact.email || ""
    });

    addDialog.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: 'Contact created successfully', life: 3000 });
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Failed to create contact';
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
    console.error("Error creating contact:", error);
  }
};

const editItem = (item) => {
  resetFormErrors();
  selectedContact.value = { ...item };
  editDialog.value = true;
};

const updateContact = async () => {
  if (!validateForm(selectedContact.value)) {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please correct the form errors.', life: 3000 });
    return;
  }

  try {
    // PUT to /api/organisme-contacts/{id}
    await axios.put(`/api/organisme-contacts/${selectedContact.value.id}`, {
      name: selectedContact.value.name,
      role: selectedContact.value.role,
      phone: selectedContact.value.phone,
      email: selectedContact.value.email,
      organisme_id: selectedContact.value.company_id // Ensure company_id is sent for update
    });

    // Update the item in the local array
    const index = items.value.findIndex(item => item.id === selectedContact.value.id);
    if (index !== -1) {
      items.value[index] = { ...selectedContact.value };
    }

    editDialog.value = false;
    toast.add({ severity: 'success', summary: 'Success', detail: 'Contact updated successfully', life: 3000 });
  } catch (error) {
    const errorMessage = error.response?.data?.message || 'Failed to update contact';
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 3000 });
    console.error("Error updating contact:", error);
  }
};

const confirmDelete = (contact) => {
  confirm.require({
    message: `Are you sure you want to delete contact "${contact.name}"? This action cannot be undone.`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      await deleteContact(contact.id);
    },
    reject: () => {
      toast.add({ severity: 'info', summary: 'Info', detail: 'Contact deletion cancelled.', life: 3000 });
    }
  });
};

const deleteContact = async (contactId) => {
  try {
    // DELETE to /api/organisme-contacts/{id}
    const response = await axios.delete(`/api/organisme-contacts/${contactId}`);
    items.value = items.value.filter((item) => item.id !== contactId);
    toast.add({ severity: 'success', summary: 'Success', detail: response.data.message || "Contact deleted successfully", life: 3000 });
  } catch (error) {
    console.error("Error deleting contact:", error);
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || "Failed to delete contact", life: 3000 });
  }
};

// Placeholder for moreInfo as it navigates to a different route
const moreInfo = (contact) => {
  // This function is kept for consistency with the example,
  // but for contacts, you might not have a separate "details" page
  // or it might be handled by the edit dialog.
  // If you do have a contact details page, adjust the route name.
  router.push({
    name: "contact.details", // Example route name, adjust as per your router setup
    params: { id: contact.id },
  });
};
</script>

<template>
  <div class="container">
    <Toast />
    <ConfirmDialog />

    <div class="header-row">
      <div class="filters">
        <Dropdown
          v-model="searchFilter"
          :options="filterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Filter"
          class="filter-dropdown"
        />
        <InputText
          v-model="searchQuery"
          placeholder="Search contacts..."
          class="filter-input"
        />
      </div>
      <Button
        label="Add Contact"
        icon="pi pi-plus"
        class="add-btn"
        @click="openAddDialog"
      />
    </div>

    <div class="table-card">
      <div v-if="loading" class="centered-col loading">
        <ProgressSpinner style="width:48px; height:48px" strokeWidth="5" />
        <span class="loading-label">Loading contacts...</span>
      </div>

      <div v-else-if="filteredItems.length === 0" class="centered-col empty-state">
        <i class="pi pi-folder-open empty-icon"></i>
        <span>No contacts found</span>
      </div>

      <div v-else>
        <DataTable
          :value="filteredItems"
          stripedRows
          :paginator="true"
          :rows="10"
          :rowsPerPageOptions="[5, 10, 20]"
          responsiveLayout="scroll"
          class="contacts-table"
        >
          <Column field="id" header="ID" />
          <Column field="name" header="name" />
          <Column field="role" header="Function" />
          <Column field="phone" header="Phone" />
          <Column field="email" header="Email" />
          <Column header="Actions" :exportable="false">
            <template #body="slotProps">
              <div class="action-btns">
                <Button
                  icon="pi pi-pencil"
                  class="p-button-sm p-button-text info-btn"
                  @click="editItem(slotProps.data)"
                  v-tooltip.top="'Edit Contact'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-sm p-button-text delete-btn"
                  @click="confirmDelete(slotProps.data)"
                  v-tooltip.top="'Delete Contact'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Add Contact Dialog -->
    <Dialog
      v-model:visible="addDialog"
      modal
      header="Add Contact"
      :style="{ width: '40vw' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
      <template #header>
        <div class="p-d-flex p-ai-center p-gap-2">
          <i class="pi pi-user-plus p-text-xl"></i>
          <span class="p-dialog-title">Add Contact</span>
        </div>
      </template>
      <div class="p-fluid p-formgrid p-grid">
        <div class="p-col-12 p-mb-3">
          <label for="addname" class="p-mb-2">name:</label>
          <InputText
            id="addname"
            v-model="newContact.name"
            :class="{ 'p-invalid': formErrors.name }"
          />
          <small class="p-error" v-if="formErrors.name">{{ formErrors.name }}</small>
        </div>
        <div class="p-col-12 p-mb-3">
          <label for="addFunction" class="p-mb-2">Function:</label>
          <InputText
            id="addFunction"
            v-model="newContact.role"
            :class="{ 'p-invalid': formErrors.role }"
          />
          <small class="p-error" v-if="formErrors.role">{{ formErrors.role }}</small>
        </div>
        <div class="p-col-12 p-mb-3">
          <label for="addPhone" class="p-mb-2">Phone:</label>
          <InputText
            id="addPhone"
            v-model="newContact.phone"
            :class="{ 'p-invalid': formErrors.phone }"
          />
          <small class="p-error" v-if="formErrors.phone">{{ formErrors.phone }}</small>
        </div>
        <div class="p-col-12 p-mb-3">
          <label for="addEmail" class="p-mb-2">Email:</label>
          <InputText
            id="addEmail"
            v-model="newContact.email"
            :class="{ 'p-invalid': formErrors.email }"
          />
          <small class="p-error" v-if="formErrors.email">{{ formErrors.email }}</small>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="addDialog = false" />
        <Button label="Save" icon="pi pi-check" @click="saveContact" />
      </template>
    </Dialog>

    <!-- Edit Contact Dialog -->
    <Dialog
      v-model:visible="editDialog"
      modal
      header="Edit Contact"
      :style="{ width: '40vw' }"
      :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
      <template #header>
        <div class="p-d-flex p-ai-center p-gap-2">
          <i class="pi pi-user-edit p-text-xl"></i>
          <span class="p-dialog-title">Edit Contact</span>
        </div>
      </template>
      <div class="p-fluid p-formgrid p-grid">
        <div class="p-col-12 p-mb-3">
          <label for="editname" class="p-mb-2">name:</label>
          <InputText
            id="editname"
            v-model="selectedContact.name"
            :class="{ 'p-invalid': formErrors.name }"
          />
          <small class="p-error" v-if="formErrors.name">{{ formErrors.name }}</small>
        </div>
        <div class="p-col-12 p-mb-3">
          <label for="editFunction" class="p-mb-2">Function:</label>
          <InputText
            id="editFunction"
            v-model="selectedContact.role"
            :class="{ 'p-invalid': formErrors.role }"
          />
          <small class="p-error" v-if="formErrors.role">{{ formErrors.role }}</small>
        </div>
        <div class="p-col-12 p-mb-3">
          <label for="editPhone" class="p-mb-2">Phone:</label>
          <InputText
            id="editPhone"
            v-model="selectedContact.phone"
            :class="{ 'p-invalid': formErrors.phone }"
          />
          <small class="p-error" v-if="formErrors.phone">{{ formErrors.phone }}</small>
        </div>
        <div class="p-col-12 p-mb-3">
          <label for="editEmail" class="p-mb-2">Email:</label>
          <InputText
            id="editEmail"
            v-model="selectedContact.email"
            :class="{ 'p-invalid': formErrors.email }"
          />
          <small class="p-error" v-if="formErrors.email">{{ formErrors.email }}</small>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="editDialog = false" />
        <Button label="Save" icon="pi pi-check" @click="updateContact" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
/* Main container styling */
.container {
  padding: 2rem 1.5rem;
  min-height: 100vh;
  min-width: 80vw; /* Adjust as needed */
  background: linear-gradient(135deg, #f4f8fa 0%, #e9edf2 100%);
}

/* Header row for filters and add button */
.header-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.filters {
  display: flex;
  flex: 1;
  gap: 1rem;
  min-width: 250px; /* Ensures filters don't collapse too much */
}

.filter-dropdown,
.filter-input {
  width: 220px; /* Fixed width for filters */
  max-width: 100%; /* Ensures responsiveness */
  font-size: 1rem;
  border-radius: 6px;
}

/* Responsive adjustments for header and filters */
@media (max-width: 700px) {
  .header-row {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch; /* Stretch items to full width */
  }
  .filters {
    flex-direction: column;
    gap: 0.75rem;
  }
  .filter-dropdown,
  .filter-input {
    width: 100%; /* Full width on small screens */
  }
}

/* Add button styling */
.add-btn {
  font-weight: bold;
  border-radius: 6px;
  background: linear-gradient(90deg, #007ad9 40%, #094989 100%);
  color: #fff;
  border: none;
  letter-spacing: 0.01em;
  box-shadow: 0 3px 12px -6px rgba(0, 122, 217, 0.33); /* Adjusted shadow for consistency */
}
.add-btn:hover,
.add-btn:focus {
  background: linear-gradient(90deg, #116ab8 0%, #094989 100%);
}

/* Table card styling */
.table-card {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 16px -8px rgba(0, 122, 217, 0.08); /* Adjusted shadow for consistency */
  padding: 1.5rem;
}

/* Centered content for loading and empty states */
.centered-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.loading {
  min-height: 220px;
}
.loading-label {
  margin-top: 1.1rem;
  color: #5580a0;
}
.empty-state {
  min-height: 220px;
  color: #a0a7b3;
}
.empty-icon {
  font-size: 2.7rem;
  margin-bottom: 0.7rem;
  color: #b0bdc9;
}

/* DataTable specific styling */
.contacts-table {
  font-size: 0.95rem;
  border-radius: 6px;
}

/* PrimeVue DataTable header and cell padding/font-size */
.p-datatable .p-datatable-thead > tr > th {
  padding: 0.75rem 1rem;
  font-size: 0.9rem;
  font-weight: 600;
  background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); /* Match original header */
  color: #374151; /* Match original header text color */
}

.p-datatable .p-datatable-tbody > tr > td {
  padding: 0.75rem 1rem;
  font-size: 0.9rem; /* Slightly smaller for table cells */
  color: #4b5563; /* Match original cell text color */
}

/* Striped rows for DataTable */
.p-datatable.p-datatable-stripedRows .p-datatable-tbody > tr:nth-child(even) {
  background-color: #f9fafb; /* Lighter background for even rows */
}
.p-datatable.p-datatable-stripedRows .p-datatable-tbody > tr:nth-child(odd) {
  background-color: #ffffff; /* White background for odd rows */
}

/* Status tags styling (copied from your example) */
.status-tag {
  display: inline-block;
  font-weight: 600;
  font-size: 0.92rem;
  padding: 3px 16px;
  border-radius: 16px;
  letter-spacing: 0.04em;
  text-transform: capitalize;
  margin: 0 2px;
}
.status-tag.active {
  background: #e6fbee;
  color: #2b974c;
  border: 1px solid #56dd8e55;
}
.status-tag.pending {
  background: #fff8e1;
  color: #be8301;
  border: 1px solid #f6bf26aa;
}
.status-tag.expired {
  background: #ffeaea;
  color: #c11c2a;
  border: 1px solid #e2606055;
}

/* Action buttons styling */
.action-btns {
  display: flex;
  gap: 0.5rem;
  justify-content: center; /* Center actions in the column */
}
/* Override PrimeVue button styles for text buttons to match example */
.p-button.p-button-text {
  background-color: transparent !important;
  border-color: transparent !important;
  box-shadow: none !important;
}

.info-btn {
  color: #007ad9 !important; /* Blue from example */
}
.info-btn:hover {
  background: #e8f1fd !important; /* Light blue hover */
}
.delete-btn {
  color: #d94233 !important; /* Red from example */
}
.delete-btn:hover {
  background: #fddede !important; /* Light red hover */
}

/* PrimeVue Dialog header styling */
.p-dialog .p-dialog-header {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); /* Match modal header */
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.p-dialog .p-dialog-header .p-dialog-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  display: flex; /* For icon alignment */
  align-items: center;
  gap: 0.75rem;
}

.p-dialog .p-dialog-header .p-dialog-header-icon {
  width: 2rem; /* Adjust icon button size */
  height: 2rem;
  font-size: 1rem;
  color: #6b7280;
}

/* PrimeVue Form Field Overrides */
.p-fluid .p-formgrid.p-grid {
  display: flex;
  flex-wrap: wrap;
  margin: -0.75rem; /* Adjust negative margin for spacing */
}

.p-fluid .p-formgrid.p-grid > .p-col-12 {
  padding: 0.75rem; /* Padding for each form column */
  width: 100%;
}

.p-inputtext {
  padding: 0.75rem 1rem;
  border: 1px solid var(--surface-border);
  border-radius: 6px;
  font-size: 1rem;
}

.p-inputtext:focus {
  outline: none;
  box-shadow: 0 0 0 0.2rem var(--blue-200); /* PrimeVue focus ring */
  border-color: var(--blue-500);
}

.p-invalid {
  border-color: var(--red-500) !important;
}

.p-error {
  color: var(--red-500);
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

/* Dialog footer buttons */
.p-dialog-footer .p-button {
  font-weight: bold;
}
</style>