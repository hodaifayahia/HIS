<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible', $event)" modal header="Add Product to Request" :style="{width: '50rem'}">
    <div class="tw-space-y-6">
      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Select Product *</label>
        <Dropdown
          v-model="formData.product_id"
          :options="availableProducts"
          optionLabel="name"
          optionValue="id"
          placeholder="Choose a product"
          class="tw-w-full"
          :loading="loadingProducts"
          filter
          filterPlaceholder="Search products..."
        >
          <template #option="slotProps">
            <div class="tw-flex tw-items-center tw-space-x-2">
              <i :class="getProductIcon(slotProps.option.type)" class="tw-text-blue-600"></i>
              <div>
                <div class="tw-font-medium">{{ slotProps.option.name }}</div>
                <div class="tw-text-sm tw-text-gray-500">{{ slotProps.option.unit }} - {{ slotProps.option.type }}</div>
              </div>
            </div>
          </template>
        </Dropdown>
      </div>

      <div class="tw-grid tw-grid-cols-2 tw-gap-4">
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Quantity *</label>
          <InputNumber
            v-model="formData.quantity"
            :min="1"
            :max="9999"
            class="tw-w-full"
            placeholder="Enter quantity"
          />
        </div>

        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Urgency Level</label>
          <Dropdown
            v-model="formData.urgency_level"
            :options="urgencyOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select urgency"
            class="tw-w-full"
          >
            <template #option="slotProps">
              <div class="tw-flex tw-items-center tw-space-x-2">
                <Tag
                  :value="slotProps.option.label"
                  :severity="getUrgencySeverity(slotProps.option.value)"
                  class="tw-text-xs"
                />
              </div>
            </template>
          </Dropdown>
        </div>
      </div>

      <div>
        <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Notes (Optional)</label>
        <Textarea
          v-model="formData.notes"
          rows="3"
          class="tw-w-full"
          placeholder="Additional notes about this product request..."
        />
      </div>

      <!-- Product Preview -->
      <div v-if="selectedProduct" class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
        <h4 class="tw-text-sm tw-font-medium tw-mb-2">Product Details</h4>
        <div class="tw-grid tw-grid-cols-2 tw-gap-4 tw-text-sm">
          <div>
            <span class="tw-text-gray-600">Type:</span>
            <span class="tw-ml-2 tw-font-medium">{{ selectedProduct.type }}</span>
          </div>
          <div>
            <span class="tw-text-gray-600">Unit:</span>
            <span class="tw-ml-2 tw-font-medium">{{ selectedProduct.unit }}</span>
          </div>
          <div v-if="selectedProduct.description" class="tw-col-span-2">
            <span class="tw-text-gray-600">Description:</span>
            <p class="tw-mt-1 tw-text-gray-800">{{ selectedProduct.description }}</p>
          </div>
        </div>
      </div>

      <div class="tw-flex tw-justify-end tw-space-x-3">
        <Button
          type="button"
          label="Cancel"
          icon="pi pi-times"
          class="p-button-text"
          @click="handleClose"
        />
        <Button
          type="button"
          label="Add Product"
          icon="pi pi-plus"
          class="tw-bg-green-600"
          :loading="addingProduct"
          @click="handleAddProduct"
          :disabled="!isFormValid"
        />
      </div>
    </div>
  </Dialog>
</template>

<script>
import { computed, reactive, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';

export default {
  name: 'AddProductDialog',
  components: {
    Dialog,
    Dropdown,
    Tag,
    Textarea,
    Button
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    availableProducts: {
      type: Array,
      default: () => []
    },
    loadingProducts: {
      type: Boolean,
      default: false
    },
    addingProduct: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:visible', 'add-product', 'close'],
  setup(props, { emit }) {
    const formData = reactive({
      product_id: null,
      quantity: 1,
      urgency_level: 'normal',
      notes: ''
    });

    const urgencyOptions = [
      { label: 'Low', value: 'low' },
      { label: 'Normal', value: 'normal' },
      { label: 'High', value: 'high' },
      { label: 'Urgent', value: 'urgent' }
    ];
    
    const isFormValid = computed(() => {
      return formData.product_id && formData.quantity > 0;
    });

    const selectedProduct = computed(() => {
      return props.availableProducts.find(p => p.id === formData.product_id);
    });

    const handleAddProduct = () => {
      if (isFormValid.value) {
        emit('add-product', { ...formData });
      }
    };

    const handleClose = () => {
      emit('close');
      resetForm();
    };

    const resetForm = () => {
      Object.assign(formData, {
        product_id: null,
        quantity: 1,
        urgency_level: 'normal',
        notes: ''
      });
    };

    const getProductIcon = (type) => {
      const icons = {
        'medical': 'pi pi-heart',
        'equipment': 'pi pi-cog',
        'consumable': 'pi pi-box',
        'pharmaceutical': 'pi pi-plus-circle'
      };
      return icons[type] || 'pi pi-box';
    };

    const getUrgencySeverity = (urgency) => {
      const severities = {
        'low': 'success',
        'normal': 'info',
        'high': 'warning',
        'urgent': 'danger'
      };
      return severities[urgency] || 'info';
    };

    // Watch for dialog close to reset form
    watch(() => props.visible, (newVal) => {
      if (!newVal) {
        resetForm();
      }
    });

    return {
      formData,
      urgencyOptions,
      isFormValid,
      selectedProduct,
      handleAddProduct,
      handleClose,
      getProductIcon,
      getUrgencySeverity
    };
  }
};
</script>

<style scoped>
/* Form input enhancements */
.p-inputtext:focus,
.p-textarea:focus,
.p-dropdown:focus,
.p-inputnumber:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
  border-color: rgb(59, 130, 246);
}

/* Dialog animations */
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Product preview styling */
.tw-bg-gray-50 {
  background-color: #f9fafb;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>