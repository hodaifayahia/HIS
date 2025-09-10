<script setup>
import { ref } from 'vue'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'

const props = defineProps({
  availableRemises: Array,
  selectedRemise: Object,
  isCustomDiscount: Boolean,
  loading: Boolean
})

const emit = defineEmits(['update:selected-remise', 'update:is-custom-discount'])

const isCustomDiscount = ref(props.isCustomDiscount)

const handleCustomDiscountChange = (value) => {
  isCustomDiscount.value = value
  emit('update:is-custom-discount', value)
}

const handleRemiseChange = (value) => {
  emit('update:selected-remise', value)
}
</script>

<template>
  <Card class="section-card">
    <template #title>
      <div class="section-header">
        <div class="header-icon bg-primary">
          <i class="pi pi-percentage"></i>
        </div>
        <div class="header-content ">
          <h3>Discount Selection</h3>
          <p>Select a discount to apply to eligible services</p>
        </div>
      </div>
    </template>
    <template #content>
      <div class="remise-selection">
        <div class="field">
          <label for="remise-dropdown" class="field-label">
            Available Discounts
          </label>
          <Dropdown
            id="remise-dropdown"
            :model-value="selectedRemise"
            :options="availableRemises"
            option-label="name"
            placeholder="Select a discount"
            :loading="loading"
            class="w-full"
            :disabled="isCustomDiscount"
            filter
            @update:model-value="handleRemiseChange"
          >
            <template #option="{ option }">
              <div class="remise-option">
                <div class="remise-info">
                  <div class="remise-name">{{ option.name }}</div>
                  <div class="remise-description">{{ option.description }}</div>
                </div>
                <div class="remise-badge" :class="option.type">
                  <span v-if="option.type === 'percentage'">
                    {{ option.value }}%
                  </span>
                  <span v-else>
                    {{ formatCurrency(option.value) }}
                  </span>
                </div>
              </div>
            </template>
          </Dropdown>
        </div>
        
        <div class="divider">
          <span>OR</span>
        </div>
        
        <div class="field-checkbox">
          <Checkbox
            id="custom-discount"
            v-model="isCustomDiscount"
            binary
            :disabled="!!selectedRemise"
            @change="handleCustomDiscountChange"
          />
          <label for="custom-discount" class="checkbox-label">
            Enable Custom Discount
          </label>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
/* Same styles as before */
.remise-selection {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.field-label {
  display: block;
  font-weight: 500;
  color: var(--gray-700);
  margin-bottom: var(--spacing-sm);
  font-size: 0.95rem;
}

.divider {
  display: flex;
  align-items: center;
  text-align: center;
  color: var(--gray-400);
  font-size: 0.875rem;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid var(--gray-200);
}

.divider::before {
  margin-right: var(--spacing-sm);
}

.divider::after {
  margin-left: var(--spacing-sm);
}

.checkbox-label {
  font-weight: 500;
  color: var(--gray-700);
  margin-left: var(--spacing-sm);
  cursor: pointer;
}

.remise-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-sm) 0;
}

.remise-info {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xxs);
}

.remise-name {
  font-weight: 600;
  color: var(--gray-800);
}

.remise-description {
  font-size: 0.8rem;
  color: var(--gray-500);
}

.remise-badge {
  font-weight: 600;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--border-radius-sm);
  font-size: 0.875rem;
}

.remise-badge.percentage {
  background: var(--success-100);
  color: var(--success-700);
}

.remise-badge.amount {
  background: var(--primary-100);
  color: var(--primary-700);
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

.header-icon.bg-primary {
  background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
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
</style>