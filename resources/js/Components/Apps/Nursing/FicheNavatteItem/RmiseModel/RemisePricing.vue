<script setup>
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Card from 'primevue/card'
import Tag from 'primevue/tag'

defineProps({
  prestationDisplayData: Array,
  totalOriginal: Number,
  totalDiscounted: Number,
  totalSavings: Number,
  savingsPercentage: Number,
  formatCurrency: Function
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD',
  }).format(amount || 0)
}
</script>

<template>
  <Card class="section-card pricing-card">
    <template #title>
      <div class="section-header">
        <div class="header-icon bg-info">
          <i class="pi pi-list"></i>
        </div>
        <div class="header-content">
          <h3>Services & Pricing</h3>
          <p>Review pricing and applied discounts</p>
        </div>
        <Tag 
          v-if="savingsPercentage > 0" 
          :value="`${savingsPercentage}% Saved`" 
          severity="success"
          class="savings-tag"
        />
      </div>
    </template>
    <template #content>
      <div class="pricing-content">
        <DataTable
          :value="prestationDisplayData"
          class="pricing-table"
          responsiveLayout="scroll"
          
          :paginator="true"
          :rows="5"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} services"
          :scrollable="true"
          scrollHeight="400px"
        >
          <Column field="prestationName" header="Service" :sortable="true">
            <template #body="{ data }">
              <div class="service-cell">
                <div class="service-info">
                  <div class="service-name">{{ data.prestationName }}</div>
                  <small class="service-code">{{ data.prestationCode }}</small>
                </div>
                <div v-if="data.isAffected" class="discount-indicator">
                  <i class="pi pi-tag"></i>
                </div>
              </div>
            </template>
          </Column>

          <Column field="originalPrice" header="Original" :sortable="true">
            <template #body="{ data }">
              <span class="price original">{{ formatCurrency(data.originalPrice) }}</span>
            </template>
          </Column>

          <Column field="discount" header="Discount" :sortable="true">
            <template #body="{ data }">
              <span v-if="data.discount > 0" class="price discount">
                -{{ formatCurrency(data.discount) }}
              </span>
              <span v-else class="price no-discount">-</span>
            </template>
          </Column>

          <Column field="discountedPrice" header="Final Price" :sortable="true">
            <template #body="{ data }">
              <div class="final-price-cell">
                <span 
                  :class="['price', 'final', { 'discounted': data.isAffected }]"
                >
                  {{ formatCurrency(data.discountedPrice) }}
                </span>
                <span 
                  v-if="data.isAffected && data.discount > 0" 
                  class="savings-badge"
                >
                  {{ Math.round((data.discount / data.originalPrice) * 100) }}% OFF
                </span>
              </div>
            </template>
          </Column>
        </DataTable>
        
        <div class="pricing-summary">
          <div class="summary-row">
            <div class="summary-label">Subtotal</div>
            <div class="summary-value">{{ formatCurrency(totalOriginal) }}</div>
          </div>
          <div class="summary-row discount">
            <div class="summary-label">Discount</div>
            <div class="summary-value">-{{ formatCurrency(totalSavings) }}</div>
          </div>
          <div class="summary-row total">
            <div class="summary-label">Total</div>
            <div class="summary-value">{{ formatCurrency(totalDiscounted) }}</div>
          </div>
          
          <div v-if="totalSavings > 0" class="savings-message">
            <i class="pi pi-check-circle"></i>
            <div>
              <strong>You save {{ formatCurrency(totalSavings) }} ({{ savingsPercentage }}%)</strong>
              <p>Great choice! You're getting significant savings.</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.pricing-card {
  height: fit-content;
}

.pricing-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.service-cell {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.service-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xxs);
}

.service-name {
  font-weight: 500;
  color: var(--gray-800);
}

.service-code {
  color: var(--gray-500);
  font-size: 0.75rem;
}

.discount-indicator {
  color: var(--success-500);
  background: var(--success-50);
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.price {
  font-weight: 500;
}

.price.original {
  color: var(--gray-600);
}

.price.discount {
  color: var(--danger-500);
  font-weight: 600;
}

.price.no-discount {
  color: var(--gray-400);
}

.price.final {
  font-weight: 600;
  font-size: 1.05rem;
}

.price.final.discounted {
  color: var(--success-600);
}

.savings-badge {
  font-size: 0.7rem;
  color: var(--success-600);
  font-weight: 600;
  background: var(--success-50);
  padding: var(--spacing-xxs) var(--spacing-xs);
  border-radius: var(--border-radius-sm);
  margin-top: var(--spacing-xxs);
}

.pricing-summary {
  border-top: 2px solid var(--gray-100);
  padding-top: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-xs) 0;
}

.summary-row.discount {
  color: var(--danger-500);
  font-weight: 500;
}

.summary-row.total {
  border-top: 1px solid var(--gray-200);
  padding-top: var(--spacing-sm);
  font-weight: 700;
  font-size: 1.2rem;
  color: var(--gray-800);
}

.summary-row .summary-label {
  font-size: 1rem;
  color: var(--gray-600);
}

.summary-row .summary-value {
  font-size: 1.1rem;
}

.summary-row.total .summary-value {
  font-size: 1.3rem;
}

.savings-message {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  padding: var(--spacing-md);
  background: var(--success-50);
  border-radius: var(--border-radius-md);
  border-left: 4px solid var(--success-500);
  margin-top: var(--spacing-sm);
}

.savings-message i {
  color: var(--success-500);
  font-size: 1.5rem;
  margin-top: var(--spacing-xxs);
}

.savings-message div {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xxs);
}

.savings-message strong {
  color: var(--success-700);
  font-size: 1rem;
}

.savings-message p {
  margin: 0;
  color: var(--success-600);
  font-size: 0.875rem;
}

.section-card {
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-lg);
  border: none;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.section-card :deep(.p-card-body) {
  padding: 0;
}

.section-card :deep(.p-card-title) {
  padding: var(--spacing-md) var(--spacing-md) 0;
  margin-bottom: 0;
}

.section-card :deep(.p-card-content) {
  padding: var(--spacing-md);
}

.section-header {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
}

.header-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--border-radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: white;
  font-size: 1.25rem;
}

.header-icon.bg-info {
  background: linear-gradient(135deg, var(--primary-400), var(--primary-500));
}

.header-content h3 {
  margin: 0 0 var(--spacing-xs) 0;
  color: var(--gray-800);
  font-weight: 600;
  font-size: 1.25rem;
}

.header-content p {
  margin: 0;
  color: var(--gray-500);
  font-size: 0.875rem;
}

.savings-tag {
  margin-left: auto;
  align-self: center;
}
</style>