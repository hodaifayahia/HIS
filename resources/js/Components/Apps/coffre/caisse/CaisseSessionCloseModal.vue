<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { caisseSessionService } from '../../services/Coffre/caisseSessionService';
import { coffreService } from '../../services/Coffre/CoffreService';
// PrimeVue Components
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

// Props
const props = defineProps({
  session: {
    type: Object,
    required: true
  }
});

// Emits
const emit = defineEmits(['close', 'saved']);

// Reactive state
const coffres = ref([]);
const denominations = ref([]);
const saving = ref(false);
const errors = ref({});

const form = reactive({
  closing_amount: null,
  coffre_id: '',
  closing_notes: '',
  denominations: {}
});

// Computed
const notesDenominations = computed(() => {
  return denominations.value.filter(d => d.type === 'note');
});

const coinsDenominations = computed(() => {
  return denominations.value.filter(d => d.type === 'coin');
});

const totalNotes = computed(() => {
  return notesDenominations.value.reduce((total, note) => {
    const quantity = form.denominations[`${note.value}-note`] || 0;
    return total + (note.value * quantity);
  }, 0);
});

const totalCoins = computed(() => {
  return coinsDenominations.value.reduce((total, coin) => {
    const quantity = form.denominations[`${coin.value}-coin`] || 0;
    return total + (coin.value * quantity);
  }, 0);
});

const totalCashCounted = computed(() => {
  return totalNotes.value + totalCoins.value;
});

const cashVariance = computed(() => {
  if (!form.closing_amount) return 0;
  return form.closing_amount - totalCashCounted.value;
});

const isFormValid = computed(() => {
  // valid if explicit closing_amount provided OR denominations/count leads to a total
  const hasExplicit = form.closing_amount !== null && form.closing_amount !== undefined;
  const counted = totalCashCounted.value > 0;
  return (hasExplicit && form.closing_amount >= 0) || counted;
});

// Methods
const loadData = async () => {
  try {
    // Load coffres
    const coffresResult = await coffreService.getAll();
    if (coffresResult.success) {
      coffres.value = coffresResult.data;
    }

    // Load denominations
    const denominationsResult = await caisseSessionService.getDenominations();
    if (denominationsResult.success) {
      denominations.value = denominationsResult.data;
    }
  } catch (error) {
    console.error('Error loading form data:', error);
  }
};

const getDenominationTotal = (value, type) => {
  const quantity = form.denominations[`${value}-${type}`] || 0;
  return value * quantity;
};

const calculateTotal = () => {
  // This method is called on input change to trigger reactivity
  // The actual calculation is done in computed properties
};

const getVarianceClass = (variance) => {
  if (variance > 0) return 'variance-positive';
  if (variance < 0) return 'variance-negative';
  return 'variance-neutral';
};

const closeSession = async () => {
  // allow close if denominations produce a total or explicit amount provided
  if (!isFormValid.value) {
    // give user feedback
    alert('Please enter the counted denominations or a closing amount before closing the session.');
    return;
  }
  
  saving.value = true;
  errors.value = {};

  // Prepare denominations data
  const denominationsData = [];
  Object.keys(form.denominations).forEach(key => {
    const quantity = form.denominations[key];
    if (quantity > 0) {
      const [value, type] = key.split('-');
      denominationsData.push({
        value: parseFloat(value),
        type: type,
        quantity: quantity
      });
    }
  });

  // If user didn't enter an explicit closing_amount, use the counted total (or opening amount fallback)
  if (form.closing_amount === null || form.closing_amount === undefined) {
    form.closing_amount = totalCashCounted.value || props.session.opening_amount || 0;
  }

  const data = {
    closing_amount: form.closing_amount,
    expected_closing_amount: form.closing_amount,
    coffre_id: form.coffre_id || null,
    closing_notes: form.closing_notes || null,
    denominations: denominationsData
  };

  try {
    const result = await caisseSessionService.closeSession(props.session.id, data);

    if (result.success) {
      emit('saved', result.message);
    } else {
      if (result.errors) {
        errors.value = result.errors;
      } else {
        alert(result.message);
      }
    }
  } catch (error) {
    console.error('Error closing session:', error);
    alert('An unexpected error occurred. Please try again.');
  } finally {
    saving.value = false;
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const formatDateTime = (dateString) => {
  if (!dateString) return '';
  return new Intl.DateTimeFormat('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(dateString));
};

// Lifecycle
onMounted(() => {
  loadData();
});
</script>
<template>
  <div class="modal-overlay" >
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-stop"></i>
          Close Cash Register Session
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="closeSession">
          <!-- Session Info -->
          <div class="session-info-card">
            <div class="session-header">
              <div class="session-details">
                <h6>{{ session.caisse?.name }}</h6>
                <p>{{ session.user?.name }} • Opened {{ formatDateTime(session.ouverture_at) }}</p>
              </div>
              <div class="opening-amount">
                <span class="amount-label">Opening Amount</span>
                <span class="amount-value">{{ formatCurrency(session.opening_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Closing Amount -->
          <div class="form-group mb-3">
            <label for="closing_amount" class="form-label">
              <i class="pi pi-dollar"></i>
              Expected Closing Amount *
            </label>
            <div class="p-inputgroup">
              <InputNumber
                v-model="form.closing_amount"
                :min-fraction-digits="2"
                :max-fraction-digits="2"
                :min="0"
                placeholder="0.00"
                class="w-full"
                :class="{ 'p-invalid': errors.closing_amount }"
                :disabled="saving"
              />
              <span class="p-inputgroup-addon">DA</span>
            </div>
            <small v-if="errors.closing_amount" class="p-error">
              {{ errors.closing_amount[0] }}
            </small>
          </div>

          <!-- Safe Selection -->
          <div class="form-group mb-3">
            <label for="coffre_id" class="form-label">
              <i class="pi pi-lock"></i>
              Transfer to Safe
            </label>
            <Dropdown
              v-model="form.coffre_id"
              :options="coffres"
              option-label="name"
              option-value="id"
              placeholder="Select safe (optional)"
              class="w-full"
                 appendTo="self"
              :class="{ 'p-invalid': errors.coffre_id }"
              :disabled="saving"
              showClear
            />
            <small v-if="errors.coffre_id" class="p-error">
              {{ errors.coffre_id[0] }}
            </small>
          </div>

          <!-- Cash Count Section -->
          <div class="cash-count-section">
            <div class="section-header">
              <h6>
                <i class="pi pi-calculator"></i>
                Cash Count (PV)
              </h6>
              <p class="section-subtitle">Count the physical cash in the register</p>
            </div>

            <!-- Notes (Billets) -->
            <div class="denomination-group">
              <h7 class="group-title">
                <i class="pi pi-file"></i>
                Notes (Billets)
              </h7>
              <div class="denomination-grid">
                <div 
                  v-for="note in notesDenominations" 
                  :key="`note-${note.value}`" 
                  class="denomination-item"
                >
                  <div class="denomination-header">
                    <span class="denomination-value">{{ formatCurrency(note.value) }}</span>
                    <span class="denomination-type">Note</span>
                  </div>
                  <div class="denomination-input">
                    <InputNumber
                      v-model="form.denominations[`${note.value}-note`]"
                      :min="0"
                      :max="9999"
                      placeholder="0"
                      @input="calculateTotal"
                      :disabled="saving"
                      class="quantity-input"
                    />
                    <span class="input-label">Qty</span>
                  </div>
                  <div class="denomination-total">
                    {{ formatCurrency(getDenominationTotal(note.value, 'note')) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Coins (Pièces) -->
            <div class="denomination-group">
              <h7 class="group-title">
                <i class="pi pi-circle"></i>
                Coins (Pièces)
              </h7>
              <div class="denomination-grid">
                <div 
                  v-for="coin in coinsDenominations" 
                  :key="`coin-${coin.value}`" 
                  class="denomination-item"
                >
                  <div class="denomination-header">
                    <span class="denomination-value">{{ formatCurrency(coin.value) }}</span>
                    <span class="denomination-type">Coin</span>
                  </div>
                  <div class="denomination-input">
                    <InputNumber
                      v-model="form.denominations[`${coin.value}-coin`]"
                      :min="0"
                      :max="9999"
                      placeholder="0"
                      @input="calculateTotal"
                      :disabled="saving"
                      class="quantity-input"
                    />
                    <span class="input-label">Qty</span>
                  </div>
                  <div class="denomination-total">
                    {{ formatCurrency(getDenominationTotal(coin.value, 'coin')) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Cash Summary -->
            <div class="cash-summary">
              <div class="summary-row">
                <span class="summary-label">Total Notes:</span>
                <span class="summary-value">{{ formatCurrency(totalNotes) }}</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Total Coins:</span>
                <span class="summary-value">{{ formatCurrency(totalCoins) }}</span>
              </div>
              <div class="summary-row total-row">
                <span class="summary-label">Total Cash Counted:</span>
                <span class="summary-value">{{ formatCurrency(totalCashCounted) }}</span>
              </div>
              <div class="summary-row variance-row">
                <span class="summary-label">Variance:</span>
                <span class="summary-value" :class="getVarianceClass(cashVariance)">
                  {{ formatCurrency(cashVariance) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Closing Notes -->
          <div class="form-group mb-4">
            <label for="closing_notes" class="form-label">
              <i class="pi pi-file-edit"></i>
              Closing Notes
            </label>
            <Textarea
              v-model="form.closing_notes"
              rows="3"
              placeholder="Enter closing notes (optional)"
              class="w-full"
              :class="{ 'p-invalid': errors.closing_notes }"
              :disabled="saving"
            />
            <small v-if="errors.closing_notes" class="p-error">
              {{ errors.closing_notes[0] }}
            </small>
          </div>

          <!-- Action Buttons -->
          <div class="button-group">
            <Button
              type="button"
              label="Cancel"
              icon="pi pi-times"
              class="p-button-secondary flex-fill"
              @click="$emit('close')"
              :disabled="saving"
            />
            <Button
              type="submit"
              :label="saving ? 'Closing Session...' : 'Close Session'"
              :icon="saving ? 'pi pi-spin pi-spinner' : 'pi pi-stop'"
              class="p-button-danger flex-fill"
              :disabled="saving"
            />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>



<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 15px;
  width: 95%;
  max-width: 900px;
  max-height: 95vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border-radius: 15px 15px 0 0;
}

.modal-title {
  margin: 0;
  font-weight: 600;
  font-size: 1.2rem;
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
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}

.modal-body {
  padding: 2rem;
}

/* Session Info Card */
.session-info-card {
  background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.session-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.session-details h6 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.session-details p {
  color: #6b7280;
  margin: 0;
  font-size: 0.9rem;
}

.opening-amount {
  text-align: right;
}

.amount-label {
  display: block;
  font-size: 0.8rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.amount-value {
  font-size: 1.2rem;
  font-weight: 700;
  color: #10b981;
}

/* Cash Count Section */
.cash-count-section {
  background: #f9fafb;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.section-header {
  margin-bottom: 1.5rem;
}

.section-header h6 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.section-subtitle {
  color: #6b7280;
  margin: 0;
  font-size: 0.9rem;
}

.denomination-group {
  margin-bottom: 2rem;
}

.group-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 1rem;
}

.denomination-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.denomination-item {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 1rem;
  transition: all 0.2s ease;
}

.denomination-item:hover {
  border-color: #f59e0b;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);
}

.denomination-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.denomination-value {
  font-weight: 600;
  color: #1f2937;
}

.denomination-type {
  font-size: 0.8rem;
  color: #6b7280;
  text-transform: uppercase;
}

.denomination-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.quantity-input {
  flex: 1;
}

.input-label {
  font-size: 0.8rem;
  color: #6b7280;
  white-space: nowrap;
}

.denomination-total {
  text-align: right;
  font-weight: 600;
  color: #f59e0b;
  font-size: 0.9rem;
}

/* Cash Summary */
.cash-summary {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  padding: 1.5rem;
  margin-top: 1.5rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.summary-row:last-child {
  border-bottom: none;
}

.total-row {
  border-top: 2px solid #e5e7eb;
  margin-top: 0.5rem;
  padding-top: 1rem;
  font-weight: 600;
  font-size: 1.1rem;
}

.variance-row {
  font-weight: 600;
}

.summary-label {
  color: #374151;
}

.summary-value {
  font-weight: 600;
}

.variance-positive {
  color: #10b981;
}

.variance-negative {
  color: #ef4444;
}

.variance-neutral {
  color: #6b7280;
}

/* Form Elements */
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

.button-group {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.flex-fill {
  flex: 1;
}

.p-error {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.w-full {
  width: 100%;
}

/* PrimeVue Component Overrides */
:deep(.p-dropdown),
:deep(.p-inputnumber-input),
:deep(.p-inputtextarea) {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.quantity-input .p-inputnumber-input) {
  padding: 0.5rem;
  font-size: 0.9rem;
}

:deep(.p-dropdown:not(.p-disabled):hover),
:deep(.p-inputnumber-input:hover),
:deep(.p-inputtextarea:hover) {
  border-color: #f59e0b;
}

:deep(.p-dropdown:not(.p-disabled).p-focus),
:deep(.p-inputnumber-input:focus),
:deep(.p-inputtextarea:focus) {
  border-color: #f59e0b;
  box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.25);
}

:deep(.p-invalid) {
  border-color: #ef4444;
}

:deep(.p-invalid:focus) {
  box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.25);
}

:deep(.p-button) {
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.2s ease;
}

:deep(.p-button:hover) {
  transform: translateY(-1px);
}

:deep(.p-button:disabled) {
  transform: none;
}

:deep(.p-inputgroup-addon) {
  background: #f3f4f6;
  border-color: #d1d5db;
  color: #f59e0b;
  font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
  .modal-content {
    width: 98%;
    margin: 1rem;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .session-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .denomination-grid {
    grid-template-columns: 1fr;
  }
  
  .button-group {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .denomination-grid {
    gap: 0.5rem;
  }
  
  .denomination-item {
    padding: 0.75rem;
  }
  
  .cash-summary {
    padding: 1rem;
  }
}
</style>
