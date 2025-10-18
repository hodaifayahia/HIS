<script setup>
import { ref, watch, defineProps, defineEmits, onMounted } from "vue";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { usePrestationPricingLogic } from "../../usePrestationPricingLogic"; // Adjust path as needed

const props = defineProps({
  visible: Boolean,
  annexId: String,
  contractData: Object,
  defaultServiceId: {
    type: String,
    required: true
  },
  defaultServiceName: {
    type: String,
    required: true
  },
});

const emit = defineEmits(["update:visible", "prestationAdded"]);

const toast = useToast();

const availablePrestations = ref([]);
const selectedPrestationToAdd = ref(null);

// Log props when they change
watch(() => props, (newProps) => {
  console.log('Props updated:', {
    defaultServiceId: newProps.defaultServiceId,
    defaultServiceName: newProps.defaultServiceName,
    annexId: newProps.annexId
  });
}, { deep: true, immediate: true });

// Set the service category to the default value
const selectedServiceCategory = ref(props.defaultServiceId);

// Use the pricing logic composable
const {
  prix,
  company_price,
  patient_price,
  patientPercentage,
  calculatePartsFromGlobal,
} = usePrestationPricingLogic(
  ref(props.contractData), // Pass contractData as a ref
  { prix: 0, company_price: 0, patient_price: 0, patientPercentage: 0 } // Initial values
);

// Internal state for the dialog
const dialogVisible = ref(props.visible);
watch(
  () => props.visible,
  (newVal) => {
    dialogVisible.value = newVal;
    if (newVal) {
      resetForm();
      // Ensure default service category is set before fetching prestations
      // This is important if the dialog is opened multiple times
      selectedServiceCategory.value = props.defaultServiceId;
      fetchPrestationsByServiceAndAnnex();
    }
  }
);
watch(dialogVisible, (newVal) => {
  emit("update:visible", newVal);
});

const resetForm = () => {
  selectedServiceCategory.value = props.defaultServiceId;
  selectedPrestationToAdd.value = null;
  availablePrestations.value = [];
  prix.value = 0;
  company_price.value = 0;
  patient_price.value = 0;
  patientPercentage.value = 0;
};

const fetchPrestationsByServiceAndAnnex = async () => {
  if (!selectedServiceCategory.value) {
    availablePrestations.value = [];
    return;
  }
  try {
    console.log("Fetching available prestations for service:", selectedServiceCategory.value, "and Annex ID:", props.annexId);

    const response = await axios.get(
      `/api/prestations/available-for-service-annex/${selectedServiceCategory.value}/${props.annexId}`
    );

    const prestationsData = response.data.data;
    console.log("Prestations from backend for dropdown:", prestationsData);

    availablePrestations.value = prestationsData.map((p) => ({
      label: `${p.name} (${p.formatted_id || p.internal_code})`,
      value: p.id,
      public_price: p.public_price // Make sure public_price is included in the options
    }));

    if (availablePrestations.value.length === 0) {
      toast.add({
        severity: "info",
        summary: "Info",
        detail: "All prestations for this service have already been added to this annex.",
        life: 3000,
      });
    }
  } catch (error) {
    console.error("Error fetching available prestations:", error);
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Failed to load prestations for the selected service.",
      life: 3000,
    });
  }
};

const onServiceCategorySelect = async () => {
  selectedPrestationToAdd.value = null;
  prix.value = 0; // Reset price when service category changes
  company_price.value = 0; // Also reset calculated parts
  patient_price.value = 0;
  await fetchPrestationsByServiceAndAnnex();
};

const onPrestationSelect = (event) => {
  selectedPrestationToAdd.value = event.value; // Store the ID directly

  // Find the selected prestation in the availablePrestations array
  const selectedPrestation = availablePrestations.value.find(
    (p) => p.value === selectedPrestationToAdd.value
  );

  if (selectedPrestation && selectedPrestation.public_price !== undefined) {
    prix.value = selectedPrestation.public_price; // Set global price
    calculatePartsFromGlobal(); // Trigger calculation for company and patient parts
  } else {
    // If public_price is not found or is undefined, reset prices
    prix.value = 0;
    company_price.value = 0;
    patient_price.value = 0;
    toast.add({
        severity: "warn",
        summary: "Warning",
        detail: "Public price not found for the selected prestation.",
        life: 3000,
    });
  }
};

const savePrestationPricing = async () => {
  if (!selectedServiceCategory.value) {
    toast.add({
      severity: "error",
      summary: "Validation Error",
      detail: "Please select a service category.",
      life: 3000,
    });
    return;
  }
  if (!selectedPrestationToAdd.value) {
    toast.add({
      severity: "error",
      summary: "Validation Error",
      detail: "Please select a prestation.",
      life: 3000,
    });
    return;
  }
  if (prix.value <= 0) {
    toast.add({
      severity: "error",
      summary: "Validation Error",
      detail: "Global Price must be greater than 0.",
      life: 3000,
    });
    return;
  }

  try {
    const payload = {
      annex_id: props.annexId,
      prestation_id: selectedPrestationToAdd.value,
      prix: parseFloat(prix.value || 0),
      company_price: parseFloat(company_price.value || 0),
      patient_price: parseFloat(patient_price.value || 0)
    };

    await axios.post(`/api/prestation-pricings`, payload);
    toast.add({
      severity: "success",
      summary: "Success",
      detail: "Prestation added successfully",
      life: 3000,
    });
    emit("prestationAdded");
    dialogVisible.value = false;
  } catch (error) {
    console.error("Error adding prestation:", error);
    const errorMessage = error.response?.data?.message || "Failed to add prestation";
    toast.add({ severity: "error", summary: "Error", detail: errorMessage, life: 5000 });
  }
};

// onMounted hook to ensure initial fetch if dialog is visible on mount
onMounted(() => {
  if (props.visible && props.defaultServiceId) {
    selectedServiceCategory.value = props.defaultServiceId;
    fetchPrestationsByServiceAndAnnex();
  }
});
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Add New Prestation Pricing"
    modal
    :style="{ width: '45rem' }"
  >
    <div class="p-fluid flex flex-col gap-3">
      <div>
        <label class="font-bold">Service Category:</label>
        <div class="p-2 border rounded bg-gray-100">
          {{ props.defaultServiceName }}
        </div>
      </div>

      <div>
        <label for="prestationSelect" class="font-bold">Prestation Name & Code:</label>
        <Dropdown
          id="prestationSelect"
          v-model="selectedPrestationToAdd"
          :options="availablePrestations"
          optionLabel="label"
          optionValue="value"
          placeholder="Select Prestation"
          class="w-full"
          :disabled="!selectedServiceCategory || availablePrestations.length === 0"
          @change="onPrestationSelect"
        >
          <template #empty>
            <div class="text-gray-500 p-2">
              <i class="pi pi-info-circle mr-2"></i>
              {{ selectedServiceCategory
                ? "All prestations for this service have already been added to this annex."
                : "Please select a service category first." }}
            </div>
          </template>
        </Dropdown>
      </div>
      <div>
        <div>
          <label for="globalPriceEdit" class="font-bold">Global Price (DZD):</label>
          <div class="p-inputgroup">
            <InputNumber
              id="globalPriceEdit"
              v-model="prix"
              @update:modelValue="calculatePartsFromGlobal"
              mode="decimal"
              :min="0"
              :maxFractionDigits="2"
              class="p-inputtext-lg"
            />
            <Button
              label="Auto"
              icon="pi pi-calculator"
              @click="calculatePartsFromGlobal"
              class="p-button-secondary"
              v-tooltip.top="'Auto calculate Company & Patient Parts based on Global Price'"
            />
          </div>
          <div class="d-flex gap-2">
            <div class="mr-2"></div>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2">
        <div class="mr-2">
          <label class="font-bold">Company Part (Calculated, DZD):</label>
          <InputNumber
            v-model="company_price"
            mode="decimal"
            :readonly="true"
            class="w-full bg-gray-100"
            :maxFractionDigits="2"
          />
        </div>

        <div>
          <label class="font-bold">Patient Part (Calculated, DZD):</label>
          <InputNumber
            v-model="patient_price"
            mode="decimal"
            :readonly="true"
            class="w-full bg-gray-100"
            :maxFractionDigits="2"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <Button
        label="Cancel"
        icon="pi pi-times"
        @click="dialogVisible = false"
        class="p-button-text"
      />
      <Button label="Save" icon="pi pi-check" @click="savePrestationPricing" />
    </template>
  </Dialog>
</template>

<style scoped>
/* Add any specific styles for this dialog if needed */
</style>