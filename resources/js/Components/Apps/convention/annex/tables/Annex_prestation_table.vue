<script setup>
import { ref, computed, defineProps, onMounted } from "vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import axios from "axios";

// Import the new components and composables
import AddAnnexPrestationDialog from "../models/AddAnnexPrestationDialog.vue";
import EditAnnexPrestationDialog from "../models/EditAnnexPrestationDialog.vue";
import { useSweetAlert } from "../../../../useSweetAlert"; // Adjust path as needed
import { useCurrencyFormatter } from "../../../../../composables/useCurrencyFormatter";

const props = defineProps({
  contractState: String,
  contractdata: Object,
  avenantpage: String,
  avenantState: String,
  serviceId: String,
  serviceName: String,
  annexId: String,
});

const toast = useToast();
const swil = useSweetAlert();
const { formatCurrency } = useCurrencyFormatter();

// State variables
const searchQuery = ref("");
const searchFilter = ref("prestation_name");
const selectedPercentageFilter = ref(null); // For dynamic percentage filtering

const prestations = ref([]); // This will hold the fetched PrestationPricing records
const selectedPrestations = ref([]); // New: Holds selected prestations for bulk actions
const currentService = ref(null); // To store the current service info
const contractPercentages = ref([]); // Dynamic contract percentages

const filterOptions = ref([
  { label: "By ID", value: "prestation_id" },
  { label: "By Name", value: "prestation_name" },
  { label: "By Code", value: "formatted_id" },
  { label: "By Global Price", value: "prix" },
  { label: "By Company Percentage", value: "companyPercentage" },
]);

// Dialog visibility states for child components
const addDialogVisible = ref(false);
const editDialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const itemToDelete = ref(null);
const selectedPrestationForEdit = ref(null);

// Fetch data on component mount
onMounted(async () => {
  // Fetch prestations first so we can derive percentages if API returns none
  await fetchPrestations();
  await fetchContractPercentages();
});

// Fetch contract percentages from the API
const fetchContractPercentages = async () => {
  try {
    const params = {};
    if (props.contractdata?.organisme_id) {
      params.organisme_id = props.contractdata.organisme_id;
    }
    if (props.contractdata?.id) {
      params.convention_id = props.contractdata.id;
    }
    // Ensure we include annex_id to get percentages linked to this annex
    if (props.annexId) {
      params.annex_id = props.annexId;
    }

    const { data } = await axios.get('/api/conventions/contract-percentages', { params });

    // Normalize response shape: it can be an array or an object with data
    let list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);

    // Map to a clean shape and filter out invalid entries
    list = (list || []).filter(Boolean).map(cp => ({
      id: cp.id ?? cp.contract_percentage_id ?? cp.contractPercentageId ?? null,
      percentage: cp.percentage !== undefined ? Number(cp.percentage) : null,
    })).filter(p => p.id !== null && !Number.isNaN(p.percentage));

    // Fallback: derive unique percentages from loaded prestations when API returns empty
    if (list.length === 0 && Array.isArray(prestations.value) && prestations.value.length > 0) {
      const map = new Map();
      for (const item of prestations.value) {
        const id = item.contract_percentage_id ?? item.contract_percentage?.id;
        const pctRaw = item.contract_percentage?.percentage ?? item.companyPercentage;
        const pct = pctRaw !== undefined && pctRaw !== null ? Number(pctRaw) : null;
        if (id && pct !== null && !Number.isNaN(pct) && !map.has(id)) {
          map.set(id, pct);
        }
      }
      list = Array.from(map.entries()).map(([id, percentage]) => ({ id, percentage }));
      list.sort((a, b) => a.percentage - b.percentage);
    }

    contractPercentages.value = list;
  } catch (error) {
    console.error("Error fetching contract percentages:", error);
    // Fallback from prestations even on error
    const map = new Map();
    for (const item of prestations.value || []) {
      const id = item.contract_percentage_id ?? item.contract_percentage?.id;
      const pctRaw = item.contract_percentage?.percentage ?? item.companyPercentage;
      const pct = pctRaw !== undefined && pctRaw !== null ? Number(pctRaw) : null;
      if (id && pct !== null && !Number.isNaN(pct) && !map.has(id)) {
        map.set(id, pct);
      }
    }
    contractPercentages.value = Array.from(map.entries()).map(([id, percentage]) => ({ id, percentage }));
  }
};

// Fetch prestations for the annex
const fetchPrestations = async () => {
  try {
    const response = await axios.get(`/api/prestation-pricings`, {
      params: { annex_id: props.annexId },
    });
    const rawPrestations = response.data.data || response.data;

    if (rawPrestations.length > 0) {
      // Get the first prestation's service info
      const firstPrestation = rawPrestations[0];
      if (firstPrestation.prestation?.service) {
        currentService.value = {
          id: firstPrestation.prestation.service.id,
          name: firstPrestation.prestation.service.name
        };
      }
    }

    prestations.value = rawPrestations.map((item) => {
      if (item.pricing && item.pricing.prix > 0) {
        // Calculate company percentage instead of patient percentage
        item.companyPercentage = (item.pricing.company_price / item.pricing.prix) * 100;
        item.patientPercentage = (item.pricing.patient_price / item.pricing.prix) * 100;
      } else {
        item.companyPercentage = 0;
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

// Handlers for AddAnnexPrestationDialog
const openAddDialog = () => {
  addDialogVisible.value = true;
};

const handlePrestationAdded = async () => {
  addDialogVisible.value = false;
  await fetchPrestations(); // Refresh list after adding
};

// Handlers for EditAnnexPrestationDialog
const openEditDialog = (item) => {
  selectedPrestationForEdit.value = item;
  editDialogVisible.value = true;
};

const handlePrestationUpdated = async () => {
  editDialogVisible.value = false;
  await fetchPrestations(); // Refresh list after updating
};

// Single delete prestation handlers (using SweetAlert)
const confirmDelete = async (item) => {
  const result = await swil.fire(
    "Confirm Deletion",
    `Are you sure you want to delete prestation: ${item.prestation_name} (${item.formatted_id})?`,
    "warning",
    true,
    true
  );

  if (result.isConfirmed) {
    try {
      await axios.delete(`/api/prestation-pricings/${item.id}`);
      prestations.value = prestations.value.filter(
        (prestation) => prestation.id !== item.id
      );
      // Remove from selected if it was selected
      selectedPrestations.value = selectedPrestations.value.filter(
        (prestation) => prestation.id !== item.id
      );
      swil.fire("Deleted!", "Prestation has been deleted successfully.", "success");
    } catch (error) {
      console.error("Error deleting prestation:", error);
      swil.fire(
        "Deletion Failed",
        error.response?.data?.message || "Failed to delete prestation.",
        "error"
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

// New: Bulk delete handler
const confirmBulkDelete = async () => {
  if (selectedPrestations.value.length === 0) {
    toast.add({
      severity: "warn",
      summary: "No Selection",
      detail: "Please select prestations to delete.",
      life: 3000,
    });
    return;
  }

  const prestationNames = selectedPrestations.value
    .map((p) => p.prestation_name)
    .join(", ");

  const result = await swil.fire(
    "Confirm Bulk Deletion",
    `Are you sure you want to delete the following ${selectedPrestations.value.length} prestations: ${prestationNames}?`,
    "warning",
    true,
    true
  );

  if (result.isConfirmed) {
    try {
      const idsToDelete = selectedPrestations.value.map((p) => p.id);
      await axios.post("/api/prestation-pricings/bulk-delete", {
        ids: idsToDelete,
      });

      // Update the local list
      prestations.value = prestations.value.filter(
        (prestation) => !idsToDelete.includes(prestation.id)
      );
      selectedPrestations.value = []; // Clear selection after deletion

      swil.fire(
        "Deleted!",
        "Selected prestations have been deleted successfully.",
        "success"
      );
    } catch (error) {
      console.error("Error performing bulk delete:", error);
      swil.fire(
        "Bulk Deletion Failed",
        error.response?.data?.message || "Failed to delete selected prestations.",
        "error"
      );
    }
  } else if (result.isDenied) {
    toast.add({
      severity: "info",
      summary: "Cancelled",
      detail: "Bulk deletion cancelled.",
      life: 3000,
    });
  }
};

// Filtered items for the DataTable with dynamic percentage filtering
const filteredItems = computed(() => {
  let items = prestations.value;

  // Apply dynamic percentage filtering
  if (selectedPercentageFilter.value) {
    items = items.filter((item) => {
      return item.contract_percentage_id === selectedPercentageFilter.value.id;
    });
  }

  // Apply search filtering
  if (!searchQuery.value) return items;

  const searchVal = searchQuery.value.toLowerCase();

  return items.filter((item) => {
        let fieldValue;

        if (searchFilter.value === "prestation_name") {
            fieldValue = item.prestation_name;
        } else if (searchFilter.value === "prestation_id") {
            fieldValue = item.prestation_id;
        } else if (searchFilter.value === "formatted_id") {
            fieldValue = item.formatted_id;
        } else if (searchFilter.value === "prix") {
            fieldValue = item.pricing?.prix;
        } else if (searchFilter.value === "companyPercentage") {
            fieldValue = item.companyPercentage;
        } else {
            fieldValue = item[searchFilter.value];
        }

        return String(fieldValue || "").toLowerCase().includes(searchVal);
    });
});

// Handle percentage filter button clicks
const selectPercentageFilter = (percentage) => {
  if (selectedPercentageFilter.value?.id === percentage.id) {
    selectedPercentageFilter.value = null; // Deselect if already selected
  } else {
    selectedPercentageFilter.value = percentage;
  }
};

// Clear all filters
const clearAllFilters = () => {
  selectedPercentageFilter.value = null;
  searchQuery.value = "";
};

// Format percentage for display
const formatPercentage = (value) => {
  if (value === null || value === undefined || isNaN(value)) {
    return "0.00%";
  }
  return `${Number(value).toFixed(2)}%`;
};

// Computed property to check if the bulk delete button should be visible
const showBulkDeleteButton = computed(() => {
  return (
    selectedPrestations.value.length > 0 &&
    (props.contractState === "pending" ||
      (props.avenantpage === "yes" && props.avenantState === "pending"))
  );
});
</script>

<template>
  <div class="w-full p-4">
    <div class="d-flex justify-content-between align-items-center items-center mb-4 gap-2">
      <div class="d-flex gap-2 pl-2 mr-2 flex-grow-1">
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
          class=" p-2 border rounded-lg flex-grow-1 w-full p-inputtext-sm"
        />
      </div>
      <div class="d-flex gap-2">
        <Button
          v-if="selectedPercentageFilter || searchQuery"
          label="Clear Filters"
          icon="pi pi-filter-slash"
          severity="secondary"
          @click="clearAllFilters"
        />
        <Button
          v-if="showBulkDeleteButton"
          label="Delete Selected"
          icon="pi pi-trash"
          severity="danger"
          @click="confirmBulkDelete"
        />
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
    </div>

    <!-- Dynamic Percentage Filter Buttons -->
    <div v-if="contractPercentages.length > 0" class="mb-4">
      <h6 class="text-sm font-semibold text-gray-700 mb-2">Filter by Contract Percentage:</h6>
      <div class="d-flex gap-2 flex-wrap">
        <Button
          v-for="percentage in contractPercentages"
          :key="percentage.id"
          :label="`${percentage.percentage}%`"
          :severity="selectedPercentageFilter?.id === percentage.id ? 'primary' : 'secondary'"
          :outlined="selectedPercentageFilter?.id !== percentage.id"
          size="small"
          @click="selectPercentageFilter(percentage)"
          class="budget-filter-btn"
        />
      </div>
    </div>

    <DataTable
      :value="filteredItems"
      v-model:selection="selectedPrestations"
      dataKey="id"
      stripedRows
      paginator
      :rows="8"
      tableStyle="min-width: 50rem"
    >
      <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
      <Column field="prestation_id" header="ID"></Column>
      <Column field="prestation_name" header="Name"></Column>
      <Column field="subname" header="sub_prestation_name"></Column>
      <Column field="formatted_id" header="Code"></Column>
      <Column field="service" header="Service"></Column>
      <Column header="Global Price">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.pricing?.prix || 0) }}
        </template>
      </Column>
      <Column header="Company Part">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.pricing?.company_price || 0) }}
        </template>
      </Column>
      <Column header="Patient Part">
        <template #body="slotProps">
          {{ formatCurrency(slotProps.data.pricing?.patient_price || 0) }}
        </template>
      </Column>
      <Column header="Contract %">
        <template #body="slotProps">
          <span class="font-semibold text-blue-600">
            {{ slotProps.data.contract_percentage?.percentage || 'N/A' }}%
          </span>
        </template>
      </Column>
      <Column header="Company %">
        <template #body="slotProps">
          <span class="font-semibold" :class="{
          
          }">
            {{ formatPercentage(slotProps.data.companyPercentage) }}
          </span>
        </template>
      </Column>
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
  <AddAnnexPrestationDialog
    :visible="addDialogVisible"
    @update:visible="addDialogVisible = $event"
    :annexId="props.annexId"
    :contractData="props.contractdata"
    :defaultServiceId="props.serviceId"
    :defaultServiceName="props.serviceName"
    @prestationAdded="handlePrestationAdded"
  />
    <EditAnnexPrestationDialog
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
.budget-filter-btn {
  min-width: 60px;
  font-weight: 600;
}

.budget-filter-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
</style>