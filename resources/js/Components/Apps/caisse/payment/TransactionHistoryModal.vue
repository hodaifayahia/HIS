<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="pi pi-list"></i>
          Transaction History
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="modal-body" v-if="prestation">
        <!-- Prestation Info -->
        <div class="prestation-info">
          <h5>{{ prestation.name }}</h5>
          <div class="financial-summary">
            <div class="summary-item">
              <span class="label">Final Price:</span>
              <span class="value">{{ formatCurrency(prestation.final_price) }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Total Paid:</span>
              <span class="value paid">{{ formatCurrency(prestation.paid_amount) }}</span>
            </div>
            <div class="summary-item">
              <span class="label">Remaining:</span>
              <span class="value" :class="getRemainingClass(prestation.remaining_amount)">
                {{ formatCurrency(prestation.remaining_amount) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-state">
          <ProgressSpinner size="small" />
          <span>Loading transactions...</span>
        </div>

        <!-- Transactions List -->
        <div v-else-if="transactions.length > 0" class="transactions-list">
          <h6>Transaction History</h6>
          <div class="transaction-items">
            <div 
              v-for="transaction in transactions" 
              :key="transaction.id"
              class="transaction-item"
            >
              <div class="transaction-header">
                <div class="transaction-info">
                  <span class="transaction-ref">{{ transaction.reference }}</span>
                  <Tag 
                    :value="transaction.transaction_type_text"
                    :severity="transaction.transaction_type === 'payment' ? 'success' : 'warning'"
                    size="small"
                  />
                </div>
                <div class="transaction-amount" :class="getAmountClass(transaction.transaction_type)">
                  {{ transaction.transaction_type === 'payment' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                </div>
              </div>
              
              <div class="transaction-details">
                <div class="detail-row">
                  <i class="pi pi-calendar detail-icon"></i>
                  <span>{{ formatDate(transaction.created_at) }}</span>
                </div>
                <div class="detail-row">
                  <i class="pi pi-credit-card detail-icon"></i>
                  <span>{{ transaction.payment_method_text }}</span>
                </div>
                <div class="detail-row" v-if="transaction.cashier">
                  <i class="pi pi-user detail-icon"></i>
                  <span>{{ transaction.cashier.name }}</span>
                </div>
                <div class="detail-row" v-if="transaction.notes">
                  <i class="pi pi-file-edit detail-icon"></i>
                  <span>{{ transaction.notes }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-transactions">
          <i class="pi pi-history empty-icon"></i>
          <h6>No Transactions</h6>
          <p>No payment transactions found for this prestation.</p>
        </div>
      </div>

      <div class="modal-footer">
        <Button
          label="Close"
          icon="pi pi-times"
          class="p-button-secondary"
          @click="$emit('close')"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

// PrimeVue Components
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

// Services
import { financialTransactionService } from '../../services/caisse/FinancialTransactionService';

// Props
const props = defineProps({
  prestation: {
    type: Object,
    required: true
  }
});

// Emits
const emit = defineEmits(['close']);

// Reactive state
const transactions = ref([]);
const loading = ref(true);

// Methods
const loadTransactions = async () => {
  loading.value = true;
  
  try {
    const result = await financialTransactionService.getAll({
      fiche_navette_item_id: props.prestation.fiche_navette_item_id,
      prestation_id: props.prestation.prestation_id
    });

    if (result.success) {
      transactions.value = result.data || [];
    }
  } catch (error) {
    console.error('Error loading transactions:', error);
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD',
    minimumFractionDigits: 2
  }).format(amount || 0);
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(dateString));
};

const getRemainingClass = (amount) => {
  if (amount <= 0) return 'text-green';
  return 'text-red';
};

const getAmountClass = (type) => {
  return type === 'payment' ? 'amount-positive' : 'amount-negative';
};

// Lifecycle
onMounted(() => {
  loadTransactions();
});
</script>

<style scoped>
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
  max-width: 800px;
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
  background: linear-gradient(135deg, #3b82f6, #2563eb);
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

.financial-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.summary-item .label {
  font-size: 0.8rem;
  color: #6b7280;
  font-weight: 500;
}

.summary-item .value {
  font-weight: 700;
  color: #1f2937;
}

.summary-item .value.paid {
  color: #10b981;
}

.text-green {
  color: #10b981;
}

.text-red {
  color: #ef4444;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 3rem 0;
  color: #6b7280;
}

.transactions-list h6 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-weight: 600;
}

.transaction-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.transaction-item {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s ease;
}

.transaction-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.transaction-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.transaction-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.transaction-ref {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #3b82f6;
}

.transaction-amount {
  font-size: 1.1rem;
  font-weight: 700;
}

.amount-positive {
  color: #10b981;
}

.amount-negative {
  color: #ef4444;
}

.transaction-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #6b7280;
}

.detail-icon {
  width: 16px;
  color: #3b82f6;
}

.empty-transactions {
  text-align: center;
  padding: 3rem 0;
}

.empty-icon {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-transactions h6 {
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-transactions p {
  color: #6b7280;
  margin: 0;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  background: #f9fafb;
  border-radius: 0 0 20px 20px;
}

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
  
  .financial-summary {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  
  .transaction-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .transaction-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
