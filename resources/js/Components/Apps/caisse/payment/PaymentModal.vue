<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-credit-card"></i>
          Make Payment
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body" v-if="prestation">
        <!-- Prestation Info -->
        <div class="prestation-info">
          <h5>{{ prestation.name }}</h5>
          <div class="prestation-details">
            <div class="detail-row">
              <span class="detail-label">Final Price:</span>
              <span class="detail-value">{{ formatCurrency(prestation.final_price) }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Already Paid:</span>
              <span class="detail-value paid">{{ formatCurrency(prestation.paid_amount) }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Remaining Amount:</span>
              <span class="detail-value remaining">{{ formatCurrency(prestation.remaining_amount) }}</span>
            </div>
          </div>
        </div>

        <form @submit.prevent="processPayment">
          <!-- Payment Amount -->
          <div class="form-group mb-4">
            <label for="amount" class="form-label">
              <i class="pi pi-dollar"></i>
              Payment Amount *
            </label>
            <div class="p-inputgroup">
              <InputNumber
                v-model="form.amount"
                id="amount"
                :min="0.01"
                :max="prestation.remaining_amount"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                placeholder="0.00"
                class="w-full"
                :class="{ 'p-invalid': errors.amount }"
                :disabled="processing"
                @input="validateAmount"
              />
              <span class="p-inputgroup-addon">DZD</span>
            </div>
            <small v-if="errors.amount" class="p-error">{{ errors.amount }}</small>
            <small class="p-help">Maximum: {{ formatCurrency(prestation.remaining_amount) }}</small>
          </div>

          <!-- Quick Amount Buttons -->
          <div class="quick-amounts mb-4">
            <label class="form-label">Quick Amounts:</label>
            <div class="quick-amount-buttons">
              <Button
                v-for="quickAmount in getQuickAmounts()"
                :key="quickAmount.value"
                :label="quickAmount.label"
                class="p-button-outlined p-button-sm"
                type="button"
                @click="setQuickAmount(quickAmount.value)"
                :disabled="processing"
              />
            </div>
          </div>

          <!-- Payment Method -->
          <div class="form-group mb-4">
            <label for="payment_method" class="form-label">
              <i class="pi pi-credit-card"></i>
              Payment Method *
            </label>
            <Dropdown
              v-model="form.payment_method"
              :options="paymentMethodOptions"
              option-label="label"
              append-to="self"
              option-value="value"
              placeholder="Select payment method"
              class="w-full"
              :class="{ 'p-invalid': errors.payment_method }"
              :disabled="processing"
            >
              <template #value="slotProps">
                <div v-if="slotProps.value" class="payment-method-item">
                  <i :class="getPaymentMethodIcon(slotProps.value)"></i>
                  <span>{{ getPaymentMethodLabel(slotProps.value) }}</span>
                </div>
                <span v-else>{{ slotProps.placeholder }}</span>
              </template>
              <template #option="slotProps">
                <div class="payment-method-item">
                  <i :class="getPaymentMethodIcon(slotProps.option.value)"></i>
                  <span>{{ slotProps.option.label }}</span>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.payment_method" class="p-error">{{ errors.payment_method }}</small>
          </div>

          <!-- Cashier -->
          <div class="form-group mb-4">
            <label for="cashier_id" class="form-label">
              <i class="pi pi-user"></i>
              Cashier *
            </label>
            <Dropdown
              v-model="form.cashier_id"
              :options="cashierOptions"
              option-label="label"
                      append-to="self"
              option-value="value"
              placeholder="Select cashier"
              class="w-full"
              :class="{ 'p-invalid': errors.cashier_id }"
              :disabled="processing"
              filter
            />
            <small v-if="errors.cashier_id" class="p-error">{{ errors.cashier_id }}</small>
          </div>

          <!-- Notes -->
          <div class="form-group mb-4">
            <label for="notes" class="form-label">
              <i class="pi pi-file-edit"></i>
              Payment Notes
            </label>
            <Textarea
              v-model="form.notes"
              id="notes"
              rows="3"
              placeholder="Enter payment notes (optional)"
              class="w-full"
              :disabled="processing"
            />
          </div>

          <!-- Payment Summary -->
          <div class="payment-summary">
            <h6>Payment Summary</h6>
            <div class="summary-content">
              <div class="summary-row">
                <span>Payment Amount:</span>
                <span class="amount">{{ formatCurrency(form.amount || 0) }}</span>
              </div>
              <div class="summary-row">
                <span>New Paid Amount:</span>
                <span class="amount">{{ formatCurrency((prestation.paid_amount || 0) + (form.amount || 0)) }}</span>
              </div>
              <div class="summary-row total">
                <span>New Remaining:</span>
                <span class="amount">{{ formatCurrency((prestation.remaining_amount || 0) - (form.amount || 0)) }}</span>
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
              :disabled="processing"
            />
            <Button
              type="submit"
              :label="processing ? 'Processing...' : 'Process Payment'"
              :icon="processing ? 'pi pi-spin pi-spinner' : 'pi pi-check'"
              class="p-button-success flex-fill"
              :disabled="processing || !isFormValid"
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';

// PrimeVue Components
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

// Services
import { financialTransactionService } from '../../services/caisse/FinancialTransactionService';
import { userService } from '../../services/user/userService';

// Props & Emits
const props = defineProps({
  prestation: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'payment-success']);

// Reactive state
const processing = ref(false);
const errors = ref({});
const cashierOptions = ref([]);

const form = reactive({
  amount: 0,
  payment_method: '',
  cashier_id: null,
  notes: ''
});

const paymentMethodOptions = [
  { label: 'Cash', value: 'cash' },
  { label: 'Credit Card', value: 'card' },
  { label: 'Check', value: 'check' },
  { label: 'Bank Transfer', value: 'transfer' },
  { label: 'Insurance', value: 'insurance' }
];

// Computed
const isFormValid = computed(() => {
  return form.amount > 0 && 
         form.amount <= props.prestation.remaining_amount &&
         form.payment_method !== '' &&
         form.cashier_id !== null;
});

// Methods
const loadCashiers = async () => {
  try {
    const result = await userService.getCashiers();
    if (result.success) {
      cashierOptions.value = result.data.map(user => ({
        label: user.name,
        value: user.id
      }));
    }
  } catch (error) {
    console.error('Error loading cashiers:', error);
  }
};

const getQuickAmounts = () => {
  const remaining = props.prestation.remaining_amount;
  const amounts = [];
  
  if (remaining >= 100) amounts.push({ label: '100 DZD', value: 100 });
  if (remaining >= 500) amounts.push({ label: '500 DZD', value: 500 });
  if (remaining >= 1000) amounts.push({ label: '1,000 DZD', value: 1000 });
  if (remaining > 0) amounts.push({ label: 'Full Amount', value: remaining });
  
  return amounts;
};

const setQuickAmount = (amount) => {
  form.amount = amount;
  validateAmount();
};

const validateAmount = () => {
  errors.value.amount = '';
  
  if (!form.amount || form.amount <= 0) {
    errors.value.amount = 'Amount must be greater than 0';
  } else if (form.amount > props.prestation.remaining_amount) {
    errors.value.amount = 'Amount cannot exceed remaining amount';
  }
};

const processPayment = async () => {
  if (!isFormValid.value) return;

  processing.value = true;
  errors.value = {};

  const paymentData = {
    fiche_navette_item_id: props.prestation.fiche_navette_item_id,
    patient_id: props.prestation.prestation_id, // This is actually prestation_id
    cashier_id: form.cashier_id,
    amount: form.amount,
    transaction_type: 'payment',
    payment_method: form.payment_method,
    notes: form.notes.trim() || null
  };

  try {
    const result = await financialTransactionService.create(paymentData);

    if (result.success) {
      emit('payment-success', result.message || 'Payment processed successfully');
    } else {
      if (result.errors) {
        errors.value = result.errors;
      } else {
        errors.value.general = result.message;
      }
    }
  } catch (error) {
    console.error('Payment error:', error);
    errors.value.general = 'An unexpected error occurred during payment processing';
  } finally {
    processing.value = false;
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const getPaymentMethodIcon = (method) => {
  const icons = {
    'cash': 'pi pi-money-bill',
    'card': 'pi pi-credit-card',
    'check': 'pi pi-file-edit',
    'transfer': 'pi pi-send',
    'insurance': 'pi pi-shield'
  };
  return icons[method] || 'pi pi-credit-card';
};

const getPaymentMethodLabel = (method) => {
  const option = paymentMethodOptions.find(opt => opt.value === method);
  return option ? option.label : method;
};

// Lifecycle
onMounted(() => {
  loadCashiers();
});
</script>

<style scoped>
/* Modal styles */
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
  max-width: 700px;
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
  background: linear-gradient(135deg, #059669, #10b981);
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

/* Prestation info */
.prestation-info {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.prestation-info h5 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.prestation-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-weight: 500;
  color: #6b7280;
}

.detail-value {
  font-weight: 700;
  color: #1f2937;
}

.detail-value.paid {
  color: #10b981;
}

.detail-value.remaining {
  color: #ef4444;
}

/* Form styles */
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

.quick-amounts {
  margin-bottom: 1rem;
}

.quick-amount-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 0.5rem;
}

.payment-method-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Payment summary */
.payment-summary {
  background: #f0f9ff;
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #0ea5e9;
  margin-bottom: 2rem;
}

.payment-summary h6 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
}

.summary-row.total {
  font-weight: 700;
  font-size: 1rem;
  border-top: 1px solid #0ea5e9;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
}

.summary-row .amount {
  font-weight: 700;
  color: #059669;
}

/* Error message */
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

/* Button styles */
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

.mb-4 {
  margin-bottom: 1.5rem;
}

.p-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.p-help {
  color: #6b7280;
  font-size: 0.8rem;
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
  
  .quick-amount-buttons {
    flex-direction: column;
  }
  
  .detail-row {
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
  background: #f3f4f6;
  border-color: #d1d5db;
  border-radius: 0 10px 10px 0;
  color: #059669;
  font-weight: 600;
}

:deep(.p-dropdown) {
  border-radius: 10px;
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
