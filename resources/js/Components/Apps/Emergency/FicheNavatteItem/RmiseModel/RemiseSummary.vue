<script setup>
defineProps({
  totalOriginal: Number,
  totalSavings: Number,
  savingsPercentage: Number,
  discountImpact: String,
  loading: Boolean
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
  }).format(amount || 0)
}
</script>

<template>
  <div class="summary-header">
    <div class="summary-item">
      <div class="summary-label">Total Services</div>
      <div class="summary-value">{{ $props.totalOriginal }}</div>
    </div>
    <div class="summary-item">
      <div class="summary-label">Original Total</div>
      <div class="summary-value">{{ formatCurrency($props.totalOriginal) }}</div>
    </div>
    <div class="summary-item savings">
      <div class="summary-label">You Save</div>
      <div class="summary-value">{{ formatCurrency($props.totalSavings) }}</div>
      <div class="savings-percentage" :class="$props.discountImpact">
        {{ $props.savingsPercentage }}% OFF
      </div>
    </div>
  </div>

  <ProgressBar 
    v-if="$props.loading" 
    mode="indeterminate" 
    class="progress-bar"
  />
</template>

<style scoped>
.summary-header {
  display: flex;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
  padding: var(--spacing-md);
  background: linear-gradient(135deg, var(--primary-50), var(--success-50));
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-md);
}

.summary-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.summary-item.savings {
  background: rgba(255, 255, 255, 0.7);
  padding: var(--spacing-sm);
  border-radius: var(--border-radius-md);
  border: 1px solid var(--success-200);
}

.summary-label {
  font-size: 0.875rem;
  color: var(--gray-600);
  font-weight: 500;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gray-800);
}

.savings-percentage {
  font-size: 0.875rem;
  font-weight: 600;
  padding: var(--spacing-xxs) var(--spacing-xs);
  border-radius: var(--border-radius-sm);
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xxs);
}

.savings-percentage.high {
  background: var(--success-100);
  color: var(--success-700);
}

.savings-percentage.medium {
  background: var(--warning-100);
  color: var(--warning-700);
}

.savings-percentage.low {
  background: var(--primary-100);
  color: var(--primary-700);
}

.progress-bar {
  margin-bottom: var(--spacing-md);
  height: 3px;
}
</style>