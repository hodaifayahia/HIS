<script setup>
import { ref, watch, defineProps, defineEmits  } from "vue";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import axios from "axios";
import { usePrestationPricingLogic } from "../../usePrestationPricingLogic"; // Adjust path as needed

const props = defineProps({
  visible: Boolean,
  avenantId: String,
  contractData: Object,
});

const emit = defineEmits(["update:visible", "prestationAdded"]);

const toast = useToast();

const availableServices = ref([]);
const selectedServiceCategory = ref(null);

const availablePrestations = ref([]);
const selectedPrestationToAdd = ref(null);

// Use the pricing logic composable
const {
  prix,
  company_price,
  patient_price,
  patientPercentage,
  calculatePartsFromGlobal, // Still used for initial calculation on global price change
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
      fetchAvailableServices();
    }
  }
);
watch(dialogVisible, (newVal) => {
  emit("update:visible", newVal);
});

const resetForm = () => {
  selectedServiceCategory.value = null;
  selectedPrestationToAdd.value = null;
  availablePrestations.value = [];
  prix.value = 0;
  company_price.value = 0;
  patient_price.value = 0;
  patientPercentage.value = 0;
};

const fetchAvailableServices = async () => {
  try {
    const response = await axios.get(`/api/services`);
    availableServices.value = response.data.data.map((s) => ({
      label: s.name,
      value: s.id,
    }));
  } catch (error) {
    console.error("Error fetching available services:", error);
    toast.add({
      severity: "error",
      summary: "Error",
      detail: "Failed to load service categories.",
      life: 3000,
    });
  }
};

const fetchPrestationsByServiceAndAvenant = async () => {
  if (!selectedServiceCategory.value) {
    availablePrestations.value = [];
    return;
  }
  try {
    const response = await axios.get(
      `/api/prestations/available-for-service-avenant/${selectedServiceCategory.value}/${props.avenantId}`
    );
    availablePrestations.value = response.data.data.map((p) => ({
      label: `${p.name} (${p.formatted_id || p.internal_code})`,
      value: p.id,
    }));
  } catch (error) {
    console.error("Error fetching prestations for selected service and avenant:", error);
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
  await fetchPrestationsByServiceAndAvenant();
};

const onPrestationSelect = (event) => {
  // When a prestation is selected, associate its ID with the new service
  // No need to set price here, it's user-inputted below
  selectedPrestationToAdd.value = event.value; // Store the ID directly
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
      avenant_id: props.avenantId,
      prestation_id: selectedPrestationToAdd.value,
      prix: parseFloat(prix.value || 0),
      // company_price and patient_price will be calculated by backend
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
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    header="Add New Prestation Pricing"
    modal
    :style="{ width: '45rem',height: 'auto' }"
  >
    <div class="p-fluid flex flex-col gap-3">
      <div>
        <label for="serviceCategory" class="font-bold">Service Category:</label>
        <Dropdown
          id="serviceCategory"
          v-model="selectedServiceCategory"
          :options="availableServices"
          optionLabel="label"
          optionValue="value"
          placeholder="Select Service Category"
          class="w-full"
          @change="onServiceCategorySelect"
        />
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
                No prestations available for this category or already priced for this avenant.
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
            class="p-inputtext-lg" />
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