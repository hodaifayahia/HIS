<template>
  <Dialog
    :visible="visible"
    @update:visible="$emit('update:visible', $event)"
    header="Bulk Assign Payment Methods"
    modal
    :style="{ width: '50rem' }"
    class="p-fluid"
  >
    <form @submit.prevent="handleSubmit">
      <div class="grid">
        <!-- User Selection -->
        <div class="col-12">
          <div class="field">
            <label for="users" class="font-semibold">Select Users</label>
            <MultiSelect
              id="users"
              v-model="form.userIds"
              :options="users"
              option-label="name"
              option-value="id"
              placeholder="Select users to assign payment methods"
              :loading="loadingUsers"
              filter
              :filter-fields="['name', 'email']"
              class="w-full"
            >
              <template #option="{ option }">
                <div class="flex align-items-center">
                  <Avatar :label="option.name.charAt(0)" class="mr-2" size="small" />
                  <div>
                    <div>{{ option.name }}</div>
                    <small class="text-600">{{ option.email }}</small>
                  </div>
                </div>
              </template>
            </MultiSelect>
          </div>
        </div>

        <!-- Payment Methods Selection -->
        <div class="col-12">
          <div class="field">
            <label for="paymentMethods" class="font-semibold">Payment Methods</label>
            <MultiSelect
              id="paymentMethods"
              v-model="form.paymentMethodKeys"
              :options="availablePaymentMethods"
              option-label="label"
              option-value="value"
              placeholder="Select payment methods to assign"
              class="w-full"
            >
              <template #option="{ option }">
                <div class="flex align-items-center">
                  <i :class="option.icon" class="mr-2"></i>
                  {{ option.label }}
                </div>
              </template>
              <template #chip="{ value }">
                <div class="flex align-items-center">
                  <i :class="getPaymentMethodIcon(value)" class="mr-1"></i>
                  {{ getPaymentMethodLabel(value) }}
                </div>
              </template>
            </MultiSelect>
          </div>
        </div>

        <!-- Status -->
        <div class="col-12">
          <div class="field">
            <label for="status" class="font-semibold">Status</label>
            <Dropdown
              id="status"
              v-model="form.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="Select status"
              class="w-full"
            />
          </div>
        </div>

        <!-- Summary -->
        <div class="col-12" v-if="form.userIds.length && form.paymentMethodKeys.length">
          <div class="surface-100 border-round p-3">
            <h4 class="mt-0">Assignment Summary</h4>
            <p class="mb-2">
              <strong>{{ form.userIds.length }}</strong> user(s) will be assigned 
              <strong>{{ form.paymentMethodKeys.length }}</strong> payment method(s)
            </p>
            <div class="flex flex-wrap gap-1 mb-2">
              <Chip
                v-for="methodKey in form.paymentMethodKeys"
                :key="methodKey"
                :label="getPaymentMethodLabel(methodKey)"
                class="p-chip-sm"
              >
                <template #icon>
                  <i :class="getPaymentMethodIcon(methodKey)" class="mr-1"></i>
                </template>
              </Chip>
            </div>
          </div>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="flex justify-content-end gap-2">
        <Button
          type="button"
          label="Cancel"
          icon="pi pi-times"
          @click="$emit('update:visible', false)"
          class="p-button-text"
        />
        <Button
          type="submit"
          label="Assign Methods"
          icon="pi pi-check"
          @click="handleSubmit"
          :loading="loading"
          :disabled="!form.userIds.length || !form.paymentMethodKeys.length"
          class="p-button-success"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { paymentMethodService } from '../../../services/Reception/paymentMethodService'
import { getPaymentMethodLabel, getPaymentMethodIcon } from '../../../enums/PaymentMethodEnum'
import axios from 'axios'

// Props & Emits
const props = defineProps({
  visible: Boolean,
  availablePaymentMethods: Array
})

const emit = defineEmits(['update:visible', 'success'])

// Reactive data
const users = ref([])
const loading = ref(false)
const loadingUsers = ref(false)

const form = ref({
  userIds: [],
  paymentMethodKeys: [],
  status: 'active'
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' }
]

// Methods
const loadUsers = async () => {
  loadingUsers.value = true
  try {
    const response = await axios.get('/api/users')
    users.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    loadingUsers.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    const result = await paymentMethodService.bulkAssignPaymentMethods(form.value)
    if (result.success) {
      emit('success')
      resetForm()
    }
  } catch (error) {
    console.error('Failed to assign payment methods:', error)
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    userIds: [],
    paymentMethodKeys: [],
    status: 'active'
  }
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal) {
    resetForm()
    loadUsers()
  }
})
</script>
