<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-pencil"></i>
          Edit Price & Payment
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body" v-if="prestation">
        <!-- Current Info -->
        <div class="current-info">
          <h5>{{ prestation.name }}</h5>
          <div class="current-details">
            <div class="detail-item">
              <span class="label">Current Final Price:</span>
              <span class="value">{{ formatCurrency(prestation.final_price) }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Current Paid Amount:</span>
              <span class="value">{{ formatCurrency(prestation.paid_amount) }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Current Remaining:</span>
              <span class="value">{{ formatCurrency(prestation.remaining_amount) }}</span>
            </div>
          </div>
        </div>

        <form @submit.prevent="updatePrice">
          <!-- New Final Price -->
          <div class="form-group mb-3">
            <label for="new_final_price" class="form-label">
              <i class="pi pi-tag"></i>
              New Final Price *
            </label>
            <div class="p-inputgroup">
              <InputNumber
                v-model="form.new_final_price"
                id="new_final_price"
                :min="0"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                placeholder="0.00"
                class="w-full"
                :class="{ 'p-invalid': errors.new_final_price }"
                :disabled="updating"
                @input="calculateAmounts"
              />
              <span class="p-inputgroup-addon">DZD</span>
            </div>
            <small v-if="errors.new_final_price" class="p-error">{{ errors.new_final_price }}</small>
          </div>

          <!-- New Paid Amount -->
          <div class="form-group mb-4">
            <label for="paid_amount" class="form-label">
              <i class="pi pi-check-circle"></i>
              Paid Amount *
            </label>
            <div class="p-inputgroup">
              <InputNumber
                v-model="form.paid_amount"
                id="paid_amount"
                :min="0"
                :max="form.new_final_price"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                placeholder="0.00"
                class="w-full"
                :class="{ 'p-invalid': errors.paid_amount }"
                :disabled="updating"
                @input="calculateAmounts"
              />
              <span class="p-inputgroup-addon">DZD</span>
            </div>
            <small v-if="errors.paid_amount" class="p-error">{{ errors.paid_amount }}</small>
          </div>

          <!-- Calculation Preview -->
          <div class="calculation-preview">
            <h6>Updated Amounts</h6>
            <div class="preview-content">
              <div class="preview-row">
                <span>New Final Price:</span>
                <span class="amount">{{ formatCurrency(form.new_final_price || 0) }}</span>
              </div>
              <div class="preview-row">
                <span>Paid Amount:</span>
                <span class="amount">{{ formatCurrency(form.paid_amount || 0) }}</span>
              </div>
              <div class="preview-row total">
                <span>New Remaining:</span>
                <span class="amount" :class="getRemainingClass()">
                  {{ formatCurrency(calculatedRemaining) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Error Display -->
          <div v-if="errors.general" class="error-message">
            <i class="pi pi-exclamation-triangle"></i>
            <span>{{ errors.general }}</span>
          </div>

          <!-- Action Buttons -->
          <div class="button-group">
            <Button
              type="button"
              label="Cancel"
              icon="pi pi-times"
              class="p-button-secondary flex-fill"
              @click="$emit('close')"
              :disabled="updating"
            />
            <Button
              type="submit"
              :label="updating ? 'Updating...' : 'Update Price'"
              :icon="updating ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
              class="p-button-warning flex-fill"
              :disabled="updating || !isFormValid"
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';

// PrimeVue Components
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';

// Services
import { financialTransactionService } from '../../services/caisse/FinancialTransactionService';

// Props & Emits
const props = defineProps({
  prestation: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'price-updated']);

// Reactive state
const updating = ref(false);
const errors = ref({});

const form = reactive({
  new_final_price: props.prestation.final_price || 0,
  paid_amount: props.prestation.paid_amount || 0
});

// Computed
const calculatedRemaining = computed(() => {
  return (form.new_final_price || 0) - (form.paid_amount || 0);
});

const isFormValid = computed(() => {
  return form.new_final_price >= 0 && 
         form.paid_amount >= 0 && 
         form.paid_amount <= form.new_final_price;
});

// Methods
const calculateAmounts = () => {
  // Clear errors
  errors.value = {};
  
  // Validate amounts
  if (form.paid_amount > form.new_final_price) {
    errors.value.paid_amount = 'Paid amount cannot exceed final price';
  }
};

const updatePrice = async () => {
  if (!isFormValid.value) return;

  updating.value = true;
  errors.value = {};

  try {
    const result = await financialTransactionService.updatePrestationPrice(
      props.prestation.prestation_id,
      props.prestation.fiche_navette_item_id,
      form.new_final_price,
      form.paid_amount
    );

    if (result.success) {
      emit('price-updated', result.message || 'Price updated successfully');
    } else {
      errors.value.general = result.message;
    }
  } catch (error) {
    console.error('Price update error:', error);
    errors.value.general = 'An unexpected error occurred during price update';
  } finally {
    updating.value = false;
  }
};

const getRemainingClass = () => {
  const remaining = calculatedRemaining.value;
  if (remaining < 0) return 'text-red';
  if (remaining === 0) return 'text-green';
  return 'text-orange';
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount || 0);
};

// Watch for changes
watch(() => props.prestation, (newPrestation) => {
  if (newPrestation) {
    form.new_final_price = newPrestation.final_price || 0;
    form.paid_amount = newPrestation.paid_amount || 0;
  }
}, { immediate: true });
</script>

<style scoped>
/* Similar modal styles as PaymentModal but with different color scheme */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 600px;
  max-height: 95vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.modal-header {
  padding: 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border-radius: 20px 20px 0 0;
}

.modal-title {
  margin: 0;
  font-weight: 600;
  font-size: 1.3rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  padding: 0.5rem;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

.modal-body {
  padding: 2rem;
}

.current-info {
  background: #fef3c7;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  border: 1px solid #f59e0b;
}

.current-info h5 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.current-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-item .label {
  font-weight: 500;
  color: #92400e;
}

.detail-item .value {
  font-weight: 700;
  color: #1f2937;
}

.form-group {
  margin-bottom: 1rem;
}

.form-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.calculation-preview {
  background: #fef3c7;
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #f59e0b;
  margin-bottom: 2rem;
}

.calculation-preview h6 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.preview-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
}

.preview-row.total {
  font-weight: 700;
  font-size: 1rem;
  border-top: 1px solid #f59e0b;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
}

.preview-row .amount {
  font-weight: 700;
}

.text-green {
  color: #10b981;
}

.text-orange {
  color: #f59e0b;
}

.text-red {
  color: #ef4444;
}

.error-message {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.button-group {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.flex-fill {
  flex: 1;
}

.w-full {
  width: 100%;
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.p-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .modal-header {
    padding: 1.5rem;
  }
  
  .button-group {
    flex-direction: column;
  }
  
  .detail-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}

/* PrimeVue overrides */
:deep(.p-inputgroup .p-inputtext) {
  border-radius: 10px 0 0 10px;
}

:deep(.p-inputgroup-addon) {
  background: #fef3c7;
  border-color: #f59e0b;
  border-radius: 0 10px 10px 0;
  color: #f59e0b;
  font-weight: 600;
}

:deep(.p-button) {
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
}
</style>
