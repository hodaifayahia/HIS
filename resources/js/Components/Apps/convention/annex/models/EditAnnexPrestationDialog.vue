<script setup>
import { ref, watch, defineProps, defineEmits } from "vue";
import Dialog from "primevue/dialog";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Checkbox from "primevue/checkbox";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import axios from "axios";
import { usePrestationPricingLogic } from "../../usePrestationPricingLogic"; // Adjust path as needed

const props = defineProps({
  visible: Boolean,
  selectedPrestation: Object, // The prestation data to edit
  contractData: Object, // Contract data for pricing rules
});

const emit = defineEmits(["update:visible", "prestationUpdated"]);

const toast = useToast();
const confirm = useConfirm();

// Internal state for the dialog visibility
const dialogVisible = ref(props.visible);
const hasSubname = ref(false);
const subname = ref("");

watch(
  () => props.visible,
  (newVal) => {
    dialogVisible.value = newVal;
  }
);
watch(dialogVisible, (newVal) => {
  emit("update:visible", newVal);
});

// Create a ref for the current selected prestation, which will be passed to the composable
const currentPrestation = ref({});

// Watch selectedPrestation prop and update local ref when it changes
watch(
  () => props.selectedPrestation,
  (newVal) => {
    if (newVal) {
      currentPrestation.value = {
        ...newVal.pricing, // Spread pricing directly
        prestation_id: newVal.prestation_id,
        prestation_name: newVal.prestation_name,
        formatted_id: newVal.formatted_id,
        id: newVal.id, // PrestationPricing record ID
      };
      // Initialize subname state
      hasSubname.value = !!newVal.pricing?.subname;
      subname.value = newVal.pricing?.subname || "";
    } else {
      currentPrestation.value = {};
      hasSubname.value = false;
      subname.value = "";
    }
  },
  { immediate: true, deep: true }
);

// Use the pricing logic composable, passing currentPrestation values to initialize
const {
  prix,
  company_price,
  patient_price,
  patientPercentage,
  priceValidation,
  validatePricing,
  calculatePartsFromGlobal,
  calculatePatientFromCompany,
  calculateCompanyFromPatient,
} = usePrestationPricingLogic(
  ref(props.contractData), // Pass contractData as a ref
  currentPrestation // Pass currentPrestation ref directly
);

// Watch for changes in currentPrestation to update the composable's internal refs
watch(
  currentPrestation,
  (newVal) => {
    if (newVal) {
      prix.value = parseFloat(newVal.prix || 0);
      company_price.value = parseFloat(newVal.company_price || 0);
      patient_price.value = parseFloat(newVal.patient_price || 0);
      if (prix.value > 0) {
        patientPercentage.value = parseFloat(((patient_price.value / prix.value) * 100).toFixed(2));
      } else {
        patientPercentage.value = 0;
      }
      validatePricing(); // Initial validation check when data loads
    }
  },
  { immediate: true, deep: true }
);

// Watchers from the original component, now using the composable's validation state
watch(
  () => priceValidation.value.companyExceedsMax,
  (newValue) => {
    if (newValue === true) {
      toast.add({
        severity: "warning", // Changed from error to warning
        summary: "Warning",
        detail: `⚠️ Company share (${(company_price.value || 0).toFixed(2)} DZD) exceeds recommended maximum of ${
          props.contractData?.max_price?.toFixed(2) || "N/A"
        } DZD`,
        life: 5000,
      });
    }
  }
);

const updatePrestationPricing = async () => {
  if (priceValidation.value.companyExceedsMax) {
    confirm.require({
      message: `Warning: The company share (${(company_price.value || 0).toFixed(2)} DZD) 
                exceeds the recommended maximum of ${props.contractData?.max_price?.toFixed(2) || "N/A"} DZD. 
                Do you want to proceed?`,
      header: "⚠️ Confirm High Company Share",
      icon: "pi pi-exclamation-triangle",
      acceptLabel: "Yes, Save Anyway",
      rejectLabel: "Cancel",
      accept: () => {
        performUpdate();
      },
      reject: () => {
        toast.add({
          severity: "info",
          summary: "Cancelled",
          detail: "Update cancelled",
          life: 3000,
        });
      }
    });
    return;
  }

  // Remove the blocking validation for patient part and directly proceed
  performUpdate();
};

const performUpdate = async () => {
  try {
    const updateData = {
      prix: parseFloat(prix.value || 0),
      company_price: parseFloat(company_price.value || 0),
      patient_price: parseFloat(patient_price.value || 0),
      prestationpringid: currentPrestation.value.id, // Ensure this sends the PrestationPricing ID
      prestation_id: currentPrestation.value.prestation_id, // Ensure this sends the related prestation ID
      subname: hasSubname.value ? subname.value : null, // Only send subname if checkbox is checked
      // annex_id: props.annexId, // Not strictly necessary for PUT if ID is in URL, but harmless.
    };

    await axios.put(`/api/prestation-pricings/${currentPrestation.value.id}`, updateData);

    toast.add({
      severity: "success",
      summary: "Success",
      detail: "Prestation updated successfully",
      life: 3000,
    });
    emit("prestationUpdated");
    dialogVisible.value = false;
  } catch (error) {
    console.error("Error updating prestation:", error);
    const errorMessage = error.response?.data?.message || "Failed to update prestation";
    toast.add({ severity: "error", summary: "Error", detail: errorMessage, life: 5000 });
  }
};
</script>

<template>
  <Dialog v-model:visible="dialogVisible" header="Edit Prestation Pricing" modal :style="{ width: '35rem' }">
    <div class="p-fluid flex flex-col gap-3">
      <!-- Changed error message to warning -->
      <div
        v-if="priceValidation.companyExceedsMax"
        class="mb-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded"
      >
        <div class="flex items-center">
          <i class="pi pi-exclamation-triangle mr-2"></i>
          <strong>⚠️ Warning</strong>
        </div>
        <p class="mt-1 text-sm">
          Company share exceeds recommended maximum of
          {{ props.contractData?.max_price?.toFixed(2) || "N/A" }} DZD
        </p>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="font-bold">Name & Code:</label>
          <div class="d-flex items-center">
            <Checkbox v-model="hasSubname" binary class="mr-2" />
            <label>Add Subname</label>
          </div>
        </div>
        <div class="p-2 border rounded bg-gray-100">
          {{ currentPrestation.prestation_name }} ({{ currentPrestation.formatted_id }})
        </div>
        <div v-if="hasSubname" class="mt-2">
          <label class="font-bold">Subname:</label>
          <InputText v-model="subname" class="w-full" placeholder="Enter subname" />
        </div>
      </div>

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
      </div>

      <div class="d-flex gap-2">
        <div class="p-col-6 p-pr-2 mr-2">
          <label for="companyPartEdit" class="font-bold">Company Part (DZD):</label>
          <InputNumber
            id="companyPartEdit"
            v-model="company_price"
            @update:modelValue="calculatePatientFromCompany"
            :class="{ 'p-warning': priceValidation.companyExceedsMax }"
            mode="decimal"
            :min="0"
            :maxFractionDigits="2"
            class="w-full"
          />
          <small v-if="priceValidation.companyExceedsMax" class="p-warning-text mt-1">
            ⚠️ Recommended max: {{ props.contractData?.max_price?.toFixed(2) || "N/A" }} DZD
          </small>
        </div>

        <div class="p-col-6 p-pl-2">
          <label for="patientPartEdit" class="font-bold">Patient Part (DZD):</label>
          <InputNumber
            id="patientPartEdit"
            v-model="patient_price"
            @update:modelValue="calculateCompanyFromPatient"
            mode="decimal"
            :min="0"
            :maxFractionDigits="2"
            class="w-full"
          />
          <small v-if="priceValidation.patientDiffers" class="p-info-text mt-1">
            💡 Calculated amount: {{ priceValidation.calculatedPatientPart.toFixed(2) }} DZD
          </small>
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
      <Button
        label="Save"
        icon="pi pi-check"
        @click="updatePrestationPricing"
        :class="{ 'p-button-warning': priceValidation.companyExceedsMax }"
        class="ml-2"
      />
    </template>
  </Dialog>
</template>

<style scoped>
/* Add these new styles */
.p-warning {
  border-color: #f59e0b !important;
  background-color: #fef3c7 !important;
}

.p-warning-text {
  color: #b45309;
  font-size: 0.875rem;
}
.p-info-text {
  color: #3b82f6;
  font-size: 0.875rem;
}

/* Keep your existing styles */
.p-warning {
  border-color: #f59e0b !important;
  background-color: #fef3c7 !important;
}

.bg-yellow-100 {
  background-color: #fef3c7;
}

.border-yellow-400 {
  border-color: #fbbf24;
}

.text-yellow-700 {
  color: #b45309;
}

.p-button-warning {
  background-color: #f59e0b !important;
  border-color: #f59e0b !important;
  color: white !important;
}

.p-button-warning:hover {
  background-color: #d97706 !important;
  border-color: #d97706 !important;
}

/* Keep your existing styles */
.p-invalid {
  border-color: #f56565 !important;
}

.p-error {
  color: #f56565;
  font-size: 0.875rem;
}

.bg-red-100 {
  background-color: #fed7d7;
}

.border-red-400 {
  border-color: #fc8181;
}

.text-red-700 {
  color: #c53030;
}

.p-button-danger {
  background-color: #f56565 !important;
  border-color: #f56565 !important;
}

.p-button-danger:disabled {
  background-color: #fed7d7 !important;
  border-color: #fed7d7 !important;
  cursor: not-allowed !important;
}
</style>