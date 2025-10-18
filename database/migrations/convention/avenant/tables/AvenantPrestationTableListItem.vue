<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dialog from "primevue/dialog"; // Only for the delete confirmation now
import Dropdown from "primevue/dropdown";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";

// Import the new components and composables
import AddPrestationDialog from "./AddPrestationDialog.vue";
import EditPrestationDialog from "./EditPrestationDialog.vue";
import { useSweetAlert } from "../../../Components/useSweetAlert"; // Adjust path as needed

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

const props = defineProps({
  contractState: String,
  contractdata: Object,
  avenantpage: String,
  avenantState: String,
  avenantid: String,
});

const toast = useToast();
const { showConfirm, showSuccess, showError } = useSweetAlert();

// State variables
const searchQuery = ref("");
const searchFilter = ref("prestation_name");

const prestations = ref([]); // This will hold the fetched PrestationPricing records

const filterOptions = ref([
  { label: "By ID", value: "prestation_id" },
  { label: "By Name", value: "prestation_name" },
  { label: "By Code", value: "formatted_id" },
  { label: "By Global Price", value: "prix" },
]);

// Dialog visibility states for child components
const addDialogVisible = ref(false);
const editDialogVisible = ref(false);
const deleteDialogVisible = ref(false); // For PrimeVue's simple dialog for now
const itemToDelete = ref(null); // Holds the item to be deleted
const selectedPrestationForEdit = ref(null); // Holds the item to be edited

// Fetch data on component mount
onMounted(async () => {
  await fetchPrestations();
});

// Fetch prestations for the avenant
const fetchPrestations = async () => {
  try {
    const response = await axios.get(
      `/api/prestation-pricings/avenant/${props.avenantid}`
    );
    const rawPrestations = response.data.data || response.data;

    prestations.value = rawPrestations.map((item) => {
      // Ensure patientPercentage is calculated for display in the table
      if (item.pricing && item.pricing.prix > 0) {
        item.patientPercentage = (item.pricing.patient_price / item.pricing.prix) * 100;
      } else {
        item.patientPercentage = 0;
      }
      return item;
    });
  } catch (error) {
    console.error("Error fetching prestations:", error);
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Failed to load prestations",
      life: 3000,
    });
  }
};

// Handlers for AddPrestationDialog
const openAddDialog = () => {
  addDialogVisible.value = true;
};

const handlePrestationAdded = async () => {
  addDialogVisible.value = false;
  await fetchPrestations(); // Refresh list after adding
};

// Handlers for EditPrestationDialog
const openEditDialog = (item) => {
  selectedPrestationForEdit.value = item;
  editDialogVisible.value = true;
};

const handlePrestationUpdated = async () => {
  editDialogVisible.value = false;
  await fetchPrestations(); // Refresh list after updating
};

// Delete prestation handlers (using SweetAlert)
const confirmDelete = async (item) => {
  const result = await showConfirm(
    "Confirm Deletion",
    `Are you sure you want to delete prestation: ${item.prestation_name} (${item.formatted_id})?`
  );

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/prestation-pricings/${item.id}`);
      showSuccess("Deleted!", "Prestation has been deleted successfully.");
      await fetchPrestations(); // Refresh the list
    } catch (error) {
      console.error("Error deleting prestation:", error);
      showError(
        "Deletion Failed",
        error.response?.data?.message || "Failed to delete prestation."
      );
    }
  } else if (result.isDenied) {
    toast.add({
      severity: "info",
      summary: "Cancelled",
      detail: "Deletion cancelled.",
      life: 3000,
    });
  }
};

// Filtered items for the DataTable
const filteredItems = computed(() => {
  if (!searchQuery.value) return prestations.value;

  const searchVal = searchQuery.value.toLowerCase();

  return prestations.value.filter((item) => {
    let fieldValue;

    if (searchFilter.value === "prestation_name") {
      fieldValue = item.prestation_name;
    } else if (searchFilter.value === "prestation_id") {
      fieldValue = item.prestation_id;
    } else if (searchFilter.value === "formatted_id") {
      fieldValue = item.formatted_id;
    } else if (searchFilter.value === "prix") {
      fieldValue = item.pricing?.prix;
    } else {
      fieldValue = item[searchFilter.value];
    }

    return String(fieldValue || "").toLowerCase().includes(searchVal);
  });
});
</script>

<template>
  <div class="w-full p-4">
    <div class="flex flex-col lg:flex-row justify-between items-center mb-4 gap-2">
      <div class="relative flex-grow flex items-center gap-2">
        <Dropdown
          v-model="searchFilter"
          :options="filterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Filter By"
          class="border rounded-lg"
        />
        <InputText
          v-model="searchQuery"
          placeholder="Search..."
          class="w-full p-2 border rounded-lg"
        />
      </div>
      <Button
        v-if="
          props.contractState === 'pending' ||
          (props.avenantpage === 'yes' && props.avenantState === 'pending')
        "
        label="Add Prestation"
        icon="pi pi-plus"
        @click="openAddDialog"
      />
    </div>

    <DataTable :value="filteredItems" stripedRows paginator :rows="8" tableStyle="min-width: 50rem">
      <Column field="prestation_id" header="ID"></Column>
      <Column field="prestation_name" header="Name"></Column>
      <Column field="formatted_id" header="Code"></Column>
      <Column field="pricing.prix" header="Global Price"></Column>
      <Column field="pricing.company_price" header="Company Part"></Column>
      <Column field="pricing.patient_price" header="Patient Part"></Column>
      <Column
        v-if="
          props.contractState === 'pending' ||
          (props.avenantpage === 'yes' && props.avenantState === 'pending')
        "
        header="Actions"
      >
        <template #body="slotProps">
          <Button
            icon="pi pi-pencil"
            severity="warn"
            size="small"
            @click="openEditDialog(slotProps.data)"
          />
        </template>
      </Column>
      <Column
        v-if="
          props.contractState === 'pending' ||
          (props.avenantpage === 'yes' && props.avenantState === 'pending')
        "
        header="Delete"
      >
        <template #body="slotProps">
          <Button
            icon="pi pi-trash"
            severity="danger"
            size="small"
            class="ml-2"
            @click="confirmDelete(slotProps.data)"
          />
        </template>
      </Column>
      <template #empty>
        <div class="text-center text-gray-500 py-6 flex flex-col items-center">
          <i class="pi pi-list text-3xl mb-2"></i>
          <span>No prestations found.</span>
        </div>
      </template>
    </DataTable>

    <AddPrestationDialog
      :visible="addDialogVisible"
      @update:visible="addDialogVisible = $event"
      :avenantId="props.avenantid"
      :contractData="props.contractdata"
      @prestationAdded="handlePrestationAdded"
    />

    <EditPrestationDialog
      :visible="editDialogVisible"
      @update:visible="editDialogVisible = $event"
      :selectedPrestation="selectedPrestationForEdit"
      :contractData="props.contractdata"
      @prestationUpdated="handlePrestationUpdated"
    />

    <Toast />
    </div>
</template>

<style scoped>
/* Any global styles that apply to the table or controls */
</style>