<template>
  <div class="tw-min-h-screen tw-bg-gray-50 tw-px-6 tw-py-6">
    <div class="tw-max-w-7xl tw-mx-auto">
      <form @submit.prevent="handleSubmit" class="tw-space-y-6">
        <!-- Header -->
      <!-- Header -->
<div class="tw-bg-white tw-rounded-2xl tw-border tw-border-gray-200 tw-px-6 tw-py-6 tw-text-center tw-relative">
  <h3 class="tw-text-2xl tw-font-semibold tw-text-blue-700 tw-flex tw-items-center tw-justify-center tw-gap-2">
    <i class="pi pi-tag"></i>
    {{ mode === 'add' ? 'Create New Remise' : 'Edit Remise' }}
  </h3>
  <p class="tw-mt-2 tw-text-gray-600">
    Fill in the details below to {{ mode === 'add' ? 'create' : 'update' }} the remise.
  </p>
  <!-- Remove button X in top-right corner -->
  <button 
    type="button" 
    class="tw-absolute tw-top-4 tw-right-4 tw-text-gray-500 hover:tw-text-gray-700 tw-text-2xl tw-font-bold tw-leading-none focus:tw-outline-none" 
    aria-label="Close"
    @click="$emit('cancel')"
  >
    &times;
  </button>
</div>


        <!-- Basic Information -->
        <Card class="tw-rounded-2xl tw-border tw-border-gray-200 tw-shadow-sm">
          <template #title>
            <div class="tw-text-blue-700 tw-font-semibold tw-text-xl tw-flex tw-items-center tw-gap-2 tw-px-6 tw-pt-6 tw-pb-4 tw-border-b tw-border-gray-100">
              <i class="pi pi-info-circle"></i>
              Basic Information
            </div>
          </template>
          <template #content>
            <div class="tw-px-6 tw-py-6">
              <div class="p-fluid formgrid grid tw-gap-y-5">
                <div class="field col-12 md:col-6">
                  <label for="name" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    Name<span class="tw-text-red-600 tw-ml-1">*</span>
                  </label>
                  <InputText
                    id="name"
                    v-model="form.name"
                    :class="{ 'p-invalid': errors.name }"
                    placeholder="Enter remise name (e.g., Summer Sale)"
                    class="tw-w-full tw-rounded-xl tw-border tw-border-gray-300 tw-bg-white tw-px-4 tw-py-3 tw-text-base tw-outline-none focus:tw-ring-0"
                  />
                  <small v-if="errors.name" class="p-error tw-flex tw-items-center tw-mt-2">
                    <i class="pi pi-exclamation-triangle tw-mr-1"></i>
                    {{ errors.name }}
                  </small>
                </div>

                <div class="field col-12 md:col-6">
                  <label for="code" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    Code<span class="tw-text-red-600 tw-ml-1">*</span>
                  </label>
                  <InputText
                    id="code"
                    v-model="form.code"
                    :class="{ 'p-invalid': errors.code }"
                    placeholder="Enter unique code (e.g., SUMMER2024)"
                    class="tw-w-full tw-rounded-xl tw-border tw-border-gray-300 tw-bg-white tw-px-4 tw-py-3 tw-text-base tw-tracking-wider tw-outline-none focus:tw-ring-0"
                    style="text-transform: uppercase;"
                  />
                  <small v-if="errors.code" class="p-error tw-flex tw-items-center tw-mt-2">
                    <i class="pi pi-exclamation-triangle tw-mr-1"></i>
                    {{ errors.code }}
                  </small>
                </div>

                <div class="field col-12">
                  <label for="description" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    Description
                  </label>
                  <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Enter a detailed description of the remise"
                    class="tw-w-full tw-rounded-xl tw-border tw-border-gray-300 tw-bg-white tw-px-4 tw-py-3 tw-text-base tw-outline-none focus:tw-ring-0"
                    :autoResize="true"
                  />
                  <small class="tw-text-gray-500 tw-block tw-mt-2">Optional: Provide additional details about this remise.</small>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Discount Configuration -->
        <Card class="tw-rounded-2xl tw-border tw-border-gray-200 tw-shadow-sm">
          <template #title>
            <div class="tw-text-blue-700 tw-font-semibold tw-text-xl tw-flex tw-items-center tw-gap-2 tw-px-6 tw-pt-6 tw-pb-4 tw-border-b tw-border-gray-100">
              <i class="pi pi-percentage"></i>
              Discount Configuration
            </div>
          </template>
          <template #content>
            <div class="tw-px-6 tw-py-6">
              <div class="p-fluid formgrid grid tw-gap-y-5">
                <div class="field col-12 md:col-6">
                  <label for="type" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    Discount Type<span class="tw-text-red-600 tw-ml-1">*</span>
                  </label>
                  <Dropdown
                    id="type"
                    v-model="form.type"
                    :options="typeOptions"
                    optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': errors.type }"
                    placeholder="Select discount type"
                    class="tw-w-full"
                    @change="onTypeChange"
                  >
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i :class="slotProps.option.icon"></i>
                        <span>{{ slotProps.option.label }}</span>
                      </div>
                    </template>
                  </Dropdown>
                  <small v-if="errors.type" class="p-error tw-flex tw-items-center tw-mt-2">
                    <i class="pi pi-exclamation-triangle tw-mr-1"></i>
                    {{ errors.type }}
                  </small>
                </div>

                <div class="field col-12 md:col-6">
                  <label class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    Status
                  </label>
                  <div class="tw-flex tw-items-center tw-gap-3 tw-py-2">
                    <InputSwitch v-model="form.is_active" :class="form.is_active ? 'switch-active' : 'switch-inactive'" />
                    <span
                      class="tw-inline-flex tw-items-center tw-gap-2 tw-font-semibold"
                      :class="form.is_active ? 'tw-text-green-700' : 'tw-text-red-700'"
                    >
                      <i :class="form.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                      {{ form.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  <small class="tw-text-gray-500 tw-block">Toggle to activate or deactivate this remise.</small>
                </div>

                <div class="field col-12 md:col-6" v-if="form.type === 'fixed'">
                  <label for="amount" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    <i class="pi pi-money-bill tw-mr-2"></i>
                    Fixed Amount<span class="tw-text-red-600 tw-ml-1">*</span>
                  </label>
                  <InputNumber
                    id="amount"
                    v-model="form.amount"
                    mode="currency"
                    currency="DZD"
                    locale="fr-DZ"
                    :class="{ 'p-invalid': errors.amount }"
                    placeholder="0.00"
                    class="tw-w-full tw-rounded-xl tw-border tw-border-gray-300 tw-bg-white tw-px-4 tw-py-3 tw-text-base tw-outline-none focus:tw-ring-0"
                    :min="0"
                    :step="0.01"
                  />
                  <small v-if="errors.amount" class="p-error tw-flex tw-items-center tw-mt-2">
                    <i class="pi pi-exclamation-triangle tw-mr-1"></i>
                    {{ errors.amount }}
                  </small>
                  <small v-else class="tw-text-gray-500 tw-block tw-mt-2">Enter the fixed discount amount in Algerian Dinars (DZD).</small>
                </div>

                <div class="field col-12 md:col-6" v-if="form.type === 'percentage'">
                  <label for="percentage" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    <i class="pi pi-percentage tw-mr-2"></i>
                    Percentage<span class="tw-text-red-600 tw-ml-1">*</span>
                  </label>
                  <InputNumber
                    id="percentage"
                    v-model="form.percentage"
                    suffix="%"
                    :min="0"
                    :max="100"
                    :class="{ 'p-invalid': errors.percentage }"
                    placeholder="0"
                    class="tw-w-full tw-rounded-xl tw-border tw-border-gray-300 tw-bg-white tw-px-4 tw-py-3 tw-text-base tw-outline-none focus:tw-ring-0"
                    :step="1"
                  />
                  <small v-if="errors.percentage" class="p-error tw-flex tw-items-center tw-mt-2">
                    <i class="pi pi-exclamation-triangle tw-mr-1"></i>
                    {{ errors.percentage }}
                  </small>
                  <small v-else class="tw-text-gray-500 tw-block tw-mt-2">Enter percentage between 0 and 100.</small>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Assignment -->
        <Card class="tw-rounded-2xl tw-border tw-border-gray-200 tw-shadow-sm">
          <template #title>
            <div class="tw-text-blue-700 tw-font-semibold tw-text-xl tw-flex tw-items-center tw-gap-2 tw-px-6 tw-pt-6 tw-pb-4 tw-border-b tw-border-gray-100">
              <i class="pi pi-users"></i>
              Assignment
            </div>
          </template>
          <template #content>
            <div class="tw-px-6 tw-py-6">
              <div class="p-fluid formgrid grid tw-gap-y-5">
                <div class="field col-12 md:col-6">
                  <label for="users" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    <i class="pi pi-user tw-mr-2"></i>
                    Assigned Users
                  </label>
                  <MultiSelect
                    id="users"
                    v-model="form.user_ids"
                    :options="users"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select users to assign this remise"
                    :maxSelectedLabels="2"
                    class="tw-w-full"
                    :filter="true"
                    filterPlaceholder="Search users..."
                    :showToggleAll="true"
                    :loading="!users.length"
                  >
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="pi pi-user tw-text-blue-600"></i>
                        <span>{{ slotProps.option.label }}</span>
                      </div>
                    </template>
                    <template #selectedItems="slotProps">
                      <div
                        v-if="slotProps.value.length"
                        class="tw-inline-flex tw-items-center tw-py-1 tw-px-2 tw-m-1 tw-text-sm tw-bg-blue-600 tw-text-white tw-rounded-md"
                      >
                        <i class="pi pi-user tw-mr-1"></i>
                        <span>{{ slotProps.value.length }} user(s) selected</span>
                      </div>
                      <span v-else>Select users to assign this remise</span>
                    </template>
                  </MultiSelect>
                  <small class="tw-text-gray-500 tw-block tw-mt-2">Choose which users can use this remise.</small>
                </div>

                <div class="field col-12 md:col-6">
                  <label for="prestations" class="tw-font-semibold tw-text-gray-800 tw-mb-2 tw-inline-flex tw-items-center">
                    <i class="pi pi-cog tw-mr-2"></i>
                    Applicable Prestations
                  </label>
                  <MultiSelect
                    id="prestations"
                    v-model="form.prestation_ids"
                    :options="prestations"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select applicable prestations"
                    :maxSelectedLabels="2"
                    class="tw-w-full"
                    :filter="true"
                    filterPlaceholder="Search prestations..."
                    :showToggleAll="true"
                    :loading="!prestations.length"
                  >
                    <template #option="slotProps">
                      <div class="tw-flex tw-items-center tw-gap-2">
                        <i class="pi pi-cog tw-text-blue-600"></i>
                        <span>{{ slotProps.option.label }}</span>
                      </div>
                    </template>
                    <template #selectedItems="slotProps">
                      <div
                        v-if="slotProps.value.length"
                        class="tw-inline-flex tw-items-center tw-py-1 tw-px-2 tw-m-1 tw-text-sm tw-bg-blue-600 tw-text-white tw-rounded-md"
                      >
                        <i class="pi pi-cog tw-mr-1"></i>
                        <span>{{ slotProps.value.length }} prestation(s) selected</span>
                      </div>
                      <span v-else>Select applicable prestations</span>
                    </template>
                  </MultiSelect>
                  <small class="tw-text-gray-500 tw-block tw-mt-2">Select which prestations this remise applies to.</small>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Actions -->
        <div class="tw-flex tw-items-center tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
          <Button
            label="Cancel"
            icon="pi pi-times"
            class="p-button-outlined p-button-secondary"
            @click="$emit('cancel')"
            type="button"
            :disabled="loading"
          />
          <Button
            :label="mode === 'add' ? 'Create Remise' : 'Update Remise'"
            :icon="mode === 'add' ? 'pi pi-plus' : 'pi pi-check'"
            type="submit"
            :loading="loading"
            :disabled="!isFormValid || loading"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, defineProps, defineEmits } from 'vue'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'
import Card from 'primevue/card'

const props = defineProps({
  remise: { type: Object, default: () => ({}) },
  mode: { type: String, default: 'add' },
  users: { type: Array, default: () => [] },
  prestations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['save', 'cancel'])

const form = ref({
  name: '',
  description: '',
  code: '',
  type: 'fixed',
  amount: null,
  percentage: null,
  is_active: true,
  user_ids: [],
  prestation_ids: []
})

const errors = ref({})

const typeOptions = [
  { label: 'Fixed Amount', value: 'fixed', icon: 'pi pi-euro' },
  { label: 'Percentage', value: 'percentage', icon: 'pi pi-percentage' }
]

const isFormValid = computed(() => {
  errors.value = {}
  let valid = true
  if (!form.value.name?.trim()) { errors.value.name = 'Name is required.'; valid = false }
  if (!form.value.code?.trim()) { errors.value.code = 'Code is required.'; valid = false }
  if (!form.value.type) { errors.value.type = 'Discount type is required.'; valid = false }
  if (form.value.type === 'fixed') {
    if (form.value.amount === null || form.value.amount === '' || form.value.amount <= 0) {
      errors.value.amount = 'Fixed amount must be greater than 0.'; valid = false
    }
  } else if (form.value.type === 'percentage') {
    if (form.value.percentage === null || form.value.percentage === '' || form.value.percentage < 0 || form.value.percentage > 100) {
      errors.value.percentage = 'Percentage must be between 0 and 100.'; valid = false
    }
  }
  return valid
})

const onTypeChange = () => {
  if (form.value.type === 'fixed') {
    form.value.percentage = null
    delete errors.value.percentage
  } else {
    form.value.amount = null
    delete errors.value.amount
  }
  delete errors.value.amount
  delete errors.value.percentage
}

const handleSubmit = () => {
  if (isFormValid.value) {
    const formData = {
      ...form.value,
      name: form.value.name?.trim(),
      code: form.value.code?.trim().toUpperCase(),
      description: form.value.description?.trim()
    }
    if (formData.type === 'fixed') delete formData.percentage
    else delete formData.amount
    emit('save', formData)
  }
}

watch(() => props.remise, (newRemise) => {
  if (newRemise && Object.keys(newRemise).length > 0) {
    form.value = {
      ...newRemise,
      user_ids: newRemise.users ? newRemise.users.map((u) => u.id) : [],
      prestation_ids: newRemise.prestations ? newRemise.prestations.map((p) => p.id) : []
    }
    if (newRemise.type === 'percentage' && newRemise.percentage !== undefined) form.value.amount = null
    else if (newRemise.type === 'fixed' && newRemise.amount !== undefined) form.value.percentage = null
  } else {
    form.value = {
      name: '',
      description: '',
      code: '',
      type: 'fixed',
      amount: null,
      percentage: null,
      is_active: true,
      user_ids: [],
      prestation_ids: []
    }
  }
  errors.value = {}
}, { immediate: true })

watch(() => form.value.code, (newCode) => {
  if (newCode) form.value.code = newCode.toUpperCase()
})
</script>

<style scoped>
/* PrimeVue content paddings align with Tailwind blocks */
:deep(.p-card .p-card-body) { padding: 0 !important; }
:deep(.p-card .p-card-content) { padding: 0 !important; }

/* Error states kept minimal and clear */
.p-invalid {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 0.15rem rgba(239, 68, 68, 0.2) !important;
}
.p-error {
  color: #ef4444;
  font-size: 0.8rem;
}

/* InputSwitch color states without hover */
:deep(.p-inputswitch.switch-active .p-inputswitch-slider) { background-color: #16a34a !important; }
:deep(.p-inputswitch.switch-inactive .p-inputswitch-slider) { background-color: #dc2626 !important; }
</style>
