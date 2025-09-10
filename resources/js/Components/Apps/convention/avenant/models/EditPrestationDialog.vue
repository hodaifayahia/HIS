<script setup>
import { ref, watch, defineProps, defineEmits } from "vue";
import Dialog from "primevue/dialog";
import InputNumber from "primevue/inputnumber";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm"; // Still using PrimeVue's confirm for this specific validation flow
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
      currentPrestation.value = { ...newVal.pricing, // Spread pricing directly
        prestation_id: newVal.prestation_id,
        prestation_name: newVal.prestation_name,
        formatted_id: newVal.formatted_id,
        id: newVal.id, // PrestationPricing record ID
      };
    } else {
      currentPrestation.value = {};
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
        severity: "error",
        summary: "CRITICAL ERROR",
        detail: `🚨 Company share (${(company_price.value || 0).toFixed(2)} DZD) CANNOT exceed maximum price of ${
          props.contractData?.max_price?.toFixed(2) || "N/A"
        } DZD`,
        life: 10000,
      });

      confirm.require({
        message: `CRITICAL VALIDATION ERROR\n\nCompany share (${(
          company_price.value || 0
        ).toFixed(2)} DZD) exceeds the maximum allowed price of ${
          props.contractData?.max_price?.toFixed(2) || "N/A"
        } DZD.\n\nThis is not allowed and you cannot save until this is fixed.`,
        header: "🚨 COMPANY SHARE EXCEEDS MAXIMUM PRICE",
        icon: "pi pi-exclamation-triangle",
        rejectLabel: "I Understand",
        acceptLabel: "Auto Fix",
        accept: () => {
          if (props.contractData?.max_price) {
            company_price.value = parseFloat(props.contractData.max_price);
            calculatePatientFromCompany();
          }
        },
        reject: () => {
          toast.add({
            severity: "warn",
            summary: "Action Required",
            detail: "Please reduce the company share or increase the global price",
            life: 5000,
          });
        },
      });
    }
  }
);

const updatePrestationPricing = async () => {
  // STRICT CHECK: Absolutely prevent saving if company exceeds max
  if (priceValidation.value.companyExceedsMax) {
    toast.add({
      severity: "error",
      summary: "SAVE BLOCKED",
      detail:
        "🚨 Cannot save: Company share exceeds maximum price. Please fix this critical error first.",
      life: 8000,
    });

    confirm.require({
      message:
        "SAVE OPERATION BLOCKED\n\nYou cannot save while the company share exceeds the maximum price. Please fix this issue first.",
      header: "🚨 SAVE BLOCKED",
      icon: "pi pi-ban",
      rejectLabel: "OK",
      acceptLabel: "Auto Fix Now",
      accept: () => {
        if (props.contractData?.max_price) {
          company_price.value = parseFloat(props.contractData.max_price);
          calculatePatientFromCompany();
        }
      },
    });
    return; // STOP EXECUTION
  }

  if (priceValidation.value.patientDiffers) {
    confirm.require({
      message: `The patient part differs from the calculated amount (${priceValidation.value.calculatedPatientPart.toFixed(
        2
      )} DZD). Are you sure you want to save with this deviation?`,
      header: "Confirm Patient Part Deviation",
      icon: "pi pi-exclamation-triangle",
      accept: () => {
        performUpdate();
      },
      reject: () => {
        toast.add({
          severity: "info",
          summary: "Cancelled",
          detail: "Update cancelled by user.",
          life: 3000,
        });
      },
    });
  } else {
    performUpdate();
  }
};

const performUpdate = async () => {
  try {
    const updateData = {
      prix: parseFloat(prix.value || 0),
      company_price: parseFloat(company_price.value || 0),
      patient_price: parseFloat(patient_price.value || 0),
      prestationpringid: currentPrestation.value.id,
      prestation_id: currentPrestation.value.prestation_id,
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
  <Dialog
    v-model:visible="dialogVisible"
    header="Edit Prestation Pricing"
    modal
    :style="{ width: '45rem' }" >
    <div class="p-fluid flex flex-col gap-3">
      <div
        v-if="priceValidation.companyExceedsMax"
        class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded"
      >
        <div class="flex items-center">
          <i class="pi pi-exclamation-triangle mr-2"></i>
          <strong>🚨 CRITICAL ERROR</strong>
        </div>
        <p class="mt-1 text-sm">
          Company share cannot exceed maximum price of
          {{ props.contractData?.max_price?.toFixed(2) || "N/A" }} DZD
        </p>
      </div>

      <div>
        <label class="font-bold">Name & Code:</label>
        <div class="p-2 border rounded bg-gray-100">
          {{ currentPrestation.prestation_name }} ({{ currentPrestation.formatted_id }})
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
            class="p-inputtext-lg" />
          <Button
            label="Auto"
            icon="pi pi-calculator"
            @click="calculatePartsFromGlobal"
            class="p-button-secondary"
            v-tooltip.top="'Auto calculate Company & Patient Parts based on Global Price'"
          />
        </div>
      </div>

      <div class=" d-flex gap-2  ">
        <div class="p-col-6 p-pr-2 mr-2">
          <label for="companyPartEdit" class="font-bold">Company Part (DZD):</label>
          <InputNumber
            id="companyPartEdit"
            v-model="company_price"
            @update:modelValue="calculatePatientFromCompany"
            :class="{ 'p-invalid': priceValidation.companyExceedsMax }"
            mode="decimal"
            :min="0"
            :maxFractionDigits="2"
            class="w-full"
          />
          <small v-if="priceValidation.companyExceedsMax" class="p-error mt-1">
            🚨 Max: {{ props.contractData?.max_price?.toFixed(2) || "N/A" }} DZD.
          </small>
        </div>

        <div class="p-col-6 p-pl-2">
          <label for="patientPartEdit" class="font-bold">Patient Part (DZD):</label>
          <InputNumber
            id="patientPartEdit"
            v-model="patient_price"
            @update:modelValue="calculateCompanyFromPatient"
            :class="{ 'p-invalid': priceValidation.patientDiffers }"
            mode="decimal"
            :min="0"
            :maxFractionDigits="2"
            class="w-full"
          />
          <small v-if="priceValidation.patientDiffers" class="p-error mt-1">
            Calculated: {{ priceValidation.calculatedPatientPart.toFixed(2) }} DZD.
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
        :disabled="priceValidation.companyExceedsMax"
        :class="{ 'p-button-danger': priceValidation.companyExceedsMax }"
      />
    </template>
  </Dialog>
</template>
<style scoped>
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