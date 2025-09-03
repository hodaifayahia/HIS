<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content bulk-modal">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-credit-card"></i>
          Bulk Payment - Pay All Outstanding
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <!-- Summary Section -->
        <div class="payment-summary-section">
          <h5>Payment Summary</h5>
          <div class="summary-grid">
            <div class="summary-card">
              <div class="summary-label">Total Prestations</div>
              <div class="summary-value">{{ prestations.length }}</div>
            </div>
            <div class="summary-card">
              <div class="summary-label">Outstanding Amount</div>
              <div class="summary-value amount-highlight">{{ formatCurrency(totalAmount) }}</div>
            </div>
          </div>
        </div>

        <!-- Prestations List -->
        <div class="prestations-preview">
          <h6>Prestations to Pay</h6>
          <div class="prestations-list">
            <div v-for="prestation in prestations" :key="prestation.id" class="prestation-preview-item">
              <div class="prestation-info">
                <div class="prestation-name">{{ prestation.name }}</div>
                <div class="prestation-patient">{{ prestation.patient_name }}</div>
              </div>
              <div class="prestation-amount">
                <span class="due-amount">{{ formatCurrency(prestation.remaining_amount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Form -->
        <form @submit.prevent="processBulkPayment">
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
              placeholder="Enter bulk payment notes (optional)"
              class="w-full"
              :disabled="processing"
            />
          </div>

          <!-- Total Payment Confirmation -->
          <div class="total-confirmation">
            <div class="confirmation-text">
              <i class="pi pi-info-circle"></i>
              <span>You are about to pay <strong>{{ formatCurrency(totalAmount) }}</strong> for {{ prestations.length }} prestation(s).</span>
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
              :label="processing ? 'Processing...' : `Pay ${formatCurrency(totalAmount)}`"
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
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

// Services
import { financialTransactionService } from '../../services/caisse/FinancialTransactionService';
import { userService } from '../../services/user/userService';

// Props & Emits
const props = defineProps({
  prestations: {
    type: Array,
    required: true,
    default: () => []
  },
  totalAmount: {
    type: Number,
    required: true,
    default: 0
  }
});

const emit = defineEmits(['close', 'payment-success']);

// Reactive state
const processing = ref(false);
const errors = ref({});
const cashierOptions = ref([]);

const form = reactive({
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
  return form.payment_method !== '' && form.cashier_id !== null;
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

const processBulkPayment = async () => {
  if (!isFormValid.value) return;

  processing.value = true;
  errors.value = {};

  try {
    // Process each prestation payment
    const paymentPromises = props.prestations.map(prestation => {
      const paymentData = {
        fiche_navette_item_id: prestation.fiche_navette_item_id,
        patient_id: prestation.prestation_id,
        cashier_id: form.cashier_id,
        amount: prestation.remaining_amount,
        transaction_type: 'payment',
        payment_method: form.payment_method,
        notes: `Bulk Payment - ${form.notes}`.trim()
      };

      return financialTransactionService.create(paymentData);
    });

    const results = await Promise.all(paymentPromises);
    
    // Check if all payments succeeded
    const allSuccessful = results.every(result => result.success);
    
    if (allSuccessful) {
      emit('payment-success', `Successfully processed bulk payment of ${formatCurrency(props.totalAmount)} for ${props.prestations.length} prestations`);
    } else {
      errors.value.general = 'Some payments failed. Please check individual prestations.';
    }
  } catch (error) {
    console.error('Bulk payment error:', error);
    errors.value.general = 'An unexpected error occurred during bulk payment processing';
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
/* Bulk modal specific styles */
.bulk-modal {
  max-width: 900px;
}

.payment-summary-section {
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.payment-summary-section h5 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.summary-card {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.summary-label {
  font-size: 0.8rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.summary-value.amount-highlight {
  color: #059669;
}

/* Prestations preview */
.prestations-preview {
  margin-bottom: 2rem;
}

.prestations-preview h6 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.prestations-list {
  max-height: 200px;
  overflow-y: auto;
  background: #f9fafb;
  border-radius: 8px;
  padding: 0.5rem;
}

.prestation-preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: white;
  border-radius: 6px;
  margin-bottom: 0.5rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.prestation-preview-item:last-child {
  margin-bottom: 0;
}

.prestation-info {
  flex: 1;
}

.prestation-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9rem;
}

.prestation-patient {
  font-size: 0.8rem;
  color: #6b7280;
}

.prestation-amount {
  text-align: right;
}

.due-amount {
  font-weight: 700;
  color: #ef4444;
  font-size: 0.9rem;
}

/* Total confirmation */
.total-confirmation {
  background: #eff6ff;
  border: 1px solid #3b82f6;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.confirmation-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e40af;
  font-size: 0.9rem;
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

.payment-method-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

/* Modal base styles */
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
  
  .summary-grid {
    grid-template-columns: 1fr;
  }
  
  .button-group {
    flex-direction: column;
  }
  
  .prestation-preview-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
