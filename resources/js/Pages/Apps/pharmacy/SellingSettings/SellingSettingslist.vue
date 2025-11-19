<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-via-blue-50/30 tw-to-indigo-50/20">
    <!-- Enhanced Header with Gradient Background -->
    <div class="tw-bg-gradient-to-r tw-from-white tw-via-blue-50/50 tw-to-indigo-50/30 tw-border-b tw-border-slate-200/60 tw-sticky tw-top-0 tw-z-10 tw-shadow-lg tw-backdrop-blur-sm">
      <div class="tw-px-6 tw-py-6">
        <div class="tw-flex tw-flex-col lg:tw-flex-row tw-justify-between tw-items-start lg:tw-items-center tw-gap-6">
          <div class="tw-flex tw-items-center tw-gap-4">
            <div class="tw-relative">
              <div class="tw-absolute tw-inset-0 tw-bg-gradient-to-br tw-from-blue-400 tw-to-indigo-600 tw-rounded-2xl tw-blur-sm tw-opacity-50"></div>
              <div class="tw-relative tw-bg-gradient-to-br tw-from-blue-500 tw-to-indigo-600 tw-p-4 tw-rounded-2xl tw-shadow-xl">
                <i class="pi pi-percentage tw-text-white tw-text-3xl"></i>
              </div>
            </div>
            <div>
              <h1 class="tw-m-0 tw-text-3xl tw-font-bold tw-bg-gradient-to-r tw-from-slate-800 tw-to-slate-600 tw-bg-clip-text tw-text-transparent">
                Selling Settings
              </h1>
              <p class="tw-text-slate-600 tw-text-sm tw-m-0 tw-mt-1">
                Manage pricing percentages for {{ currentType === 'pharmacy' ? 'Pharmacy' : 'Stock' }} services
              </p>
            </div>
          </div>

          <!-- Enhanced Stats Cards -->
          <div class="tw-flex tw-flex-wrap tw-gap-4">
            <div class="tw-bg-gradient-to-br tw-from-amber-50 tw-to-orange-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-amber-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-amber-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-list tw-text-amber-600"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-amber-600 tw-m-0 tw-font-medium">Total Settings</p>
                  <p class="tw-text-xl tw-font-bold tw-text-amber-700 tw-m-0">{{ settings.length }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-green-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-green-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-check-circle tw-text-green-600"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-green-600 tw-m-0 tw-font-medium">Active</p>
                  <p class="tw-text-xl tw-font-bold tw-text-green-700 tw-m-0">{{ activeCount }}</p>
                </div>
              </div>
            </div>

            <div class="tw-bg-gradient-to-br tw-from-blue-50 tw-to-cyan-50 tw-px-4 tw-py-3 tw-rounded-xl tw-border tw-border-blue-200/60 tw-shadow-sm hover:tw-shadow-md tw-transition-all tw-duration-200">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
                  <i class="pi pi-building tw-text-blue-600"></i>
                </div>
                <div>
                  <p class="tw-text-xs tw-text-blue-600 tw-m-0 tw-font-medium">Services</p>
                  <p class="tw-text-xl tw-font-bold tw-text-blue-700 tw-m-0">{{ uniqueServicesCount }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tw-px-6 tw-py-6">
      <!-- Enhanced Action Toolbar -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-p-6 tw-mb-8 tw-backdrop-blur-sm">
        <div class="tw-flex tw-flex-col xl:tw-flex-row tw-justify-between tw-items-start xl:tw-items-center tw-gap-6">
          <!-- Enhanced Filters Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-4 tw-flex-1">
            <!-- Search with Enhanced Design -->
            <div class="tw-relative tw-flex-1 tw-min-w-[250px] tw-max-w-[400px]">
              <div class="tw-absolute tw-inset-y-0 tw-left-0 tw-flex tw-items-center tw-pl-4 tw-pointer-events-none">
                <i class="pi pi-search tw-text-slate-400"></i>
              </div>
              <InputText
                v-model="filters.search"
                @input="debouncedSearch"
                placeholder="Search services..."
                class="tw-w-full tw-pl-11 tw-pr-4 tw-py-3 tw-border-2 tw-border-slate-200 tw-rounded-xl focus:tw-border-blue-400 tw-transition-all tw-duration-200 tw-shadow-sm hover:tw-shadow-md"
              />
              <button
                v-if="filters.search"
                @click="clearSearch"
                class="tw-absolute tw-inset-y-0 tw-right-0 tw-flex tw-items-center tw-pr-4 tw-text-slate-400 hover:tw-text-slate-600"
              >
                <i class="pi pi-times"></i>
              </button>
            </div>

            <!-- Filter Dropdowns -->
            <div class="tw-flex tw-flex-wrap tw-gap-3">
              <Dropdown
                v-model="filters.service_id"
                :options="services"
                optionLabel="name"
                optionValue="id"
                placeholder="All Services"
                @change="applyFilters"
                class="tw-min-w-[200px] tw-rounded-xl"
                :showClear="true"
              />

              <Dropdown
                v-model="filters.is_active"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="All Status"
                @change="applyFilters"
                class="tw-min-w-[150px] tw-rounded-xl"
                :showClear="true"
              />
            </div>
          </div>

          <!-- Enhanced Action Buttons -->
          <div class="tw-flex tw-flex-wrap tw-gap-3">
            <Button
              label="Refresh"
              icon="pi pi-refresh"
              @click="refreshData"
              :loading="loading"
              class="p-button-outlined p-button-secondary p-button-lg tw-rounded-xl hover:tw-shadow-md tw-transition-all tw-duration-200"
            />
            <Button
              label="New Setting"
              icon="pi pi-plus"
              @click="openCreateDialog"
              class="p-button-primary p-button-lg tw-rounded-xl tw-shadow-lg hover:tw-shadow-xl tw-transition-all tw-duration-200 tw-transform hover:tw-scale-105"
            />
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="tw-mt-4 tw-flex tw-flex-wrap tw-items-center tw-gap-2">
          <span class="tw-text-sm tw-text-slate-600 tw-font-medium">Active filters:</span>
          <Tag
            v-if="filters.search"
            :value="`Search: ${filters.search}`"
            severity="info"
            class="tw-rounded-lg tw-cursor-pointer"
            @click="clearSearch"
          />
          <Tag
            v-if="filters.service_id"
            :value="`Service: ${getServiceName(filters.service_id)}`"
            severity="info"
            class="tw-rounded-lg tw-cursor-pointer"
            @click="filters.service_id = null; applyFilters()"
          />
          <Tag
            v-if="filters.is_active !== null"
            :value="`Status: ${filters.is_active ? 'Active' : 'Inactive'}`"
            severity="info"
            class="tw-rounded-lg tw-cursor-pointer"
            @click="filters.is_active = null; applyFilters()"
          />
          <Button
            @click="clearAllFilters"
            label="Clear all"
            class="p-button-text p-button-sm tw-text-slate-500 hover:tw-text-slate-700"
          />
        </div>
      </div>

      <!-- Enhanced Data Table -->
      <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-slate-200/60 tw-overflow-hidden tw-backdrop-blur-sm">
        <DataTable
          v-model:selection="selectedSettings"
          :value="settings"
          :paginator="true"
          :rows="10"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} settings"
          :rowsPerPageOptions="[10, 25, 50]"
          dataKey="id"
          :loading="loading"
          selectionMode="multiple"
          responsiveLayout="scroll"
          class="p-datatable-sm medical-table"
          :class="{ 'tw-hidden': loading }"
        >
          <!-- Selection Column -->
          <Column selectionMode="multiple" headerStyle="width: 3rem" />

          <!-- Service Name -->
          <Column field="service.name" header="Service" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-3">
                <div class="tw-p-2 tw-bg-blue-100 tw-rounded-lg">
                  <i class="pi pi-building tw-text-blue-600"></i>
                </div>
                <div>
                  <div class="tw-font-semibold tw-text-slate-800">{{ data.service?.name || 'N/A' }}</div>
                  <div class="tw-text-sm tw-text-slate-500">{{ data.service?.service_abv || '' }}</div>
                </div>
              </div>
            </template>
          </Column>

          <!-- Type Badge -->
          <Column field="type" header="Type" :sortable="true">
            <template #body="{ data }">
              <Tag
                :value="data.type.charAt(0).toUpperCase() + data.type.slice(1)"
                :severity="data.type === 'pharmacy' ? 'success' : 'info'"
                class="tw-font-semibold"
              />
            </template>
          </Column>

          <!-- Percentage -->
          <Column field="percentage" header="Percentage" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-bg-purple-50 tw-px-3 tw-py-1 tw-rounded-lg">
                  <span class="tw-text-lg tw-font-bold tw-text-purple-700">{{ data.percentage }}%</span>
                </div>
              </div>
            </template>
          </Column>

          <!-- Active Status -->
          <Column field="is_active" header="Status" :sortable="true">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div
                  class="tw-w-2 tw-h-2 tw-rounded-full"
                  :class="data.is_active ? 'tw-bg-green-500' : 'tw-bg-slate-300'"
                ></div>
                <Tag
                  :value="data.is_active ? 'Active' : 'Inactive'"
                  :severity="data.is_active ? 'success' : 'secondary'"
                  class="tw-font-semibold"
                />
              </div>
            </template>
          </Column>

          <!-- Created By -->
          <Column header="Created By">
            <template #body="{ data }">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-bg-slate-100 tw-w-8 tw-h-8 tw-rounded-full tw-flex tw-items-center tw-justify-center">
                  <span class="tw-text-xs tw-font-semibold tw-text-slate-600">
                    {{ getInitials(data.creator?.name) }}
                  </span>
                </div>
                <span class="tw-text-sm tw-text-slate-700">{{ data.creator?.name || 'N/A' }}</span>
              </div>
            </template>
          </Column>

          <!-- Date -->
          <Column field="created_at" header="Created" :sortable="true">
            <template #body="{ data }">
              <div class="tw-text-sm tw-text-slate-600">
                <div>{{ formatDateOnly(data.created_at) }}</div>
                <div class="tw-text-xs tw-text-slate-400">{{ formatTimeOnly(data.created_at) }}</div>
              </div>
            </template>
          </Column>

          <!-- Actions -->
          <Column header="Actions" :exportable="false">
            <template #body="{ data }">
              <div class="tw-flex tw-gap-2">
                <Button
                  icon="pi pi-pencil"
                  class="p-button-rounded p-button-text p-button-info"
                  @click="editSetting(data)"
                  v-tooltip.top="'Edit'"
                />
                <Button
                  :icon="data.is_active ? 'pi pi-times' : 'pi pi-check'"
                  :class="data.is_active ? 'p-button-rounded p-button-text p-button-warning' : 'p-button-rounded p-button-text p-button-success'"
                  @click="toggleActive(data)"
                  v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
                />
                <Button
                  icon="pi pi-trash"
                  class="p-button-rounded p-button-text p-button-danger"
                  @click="deleteSetting(data)"
                  v-tooltip.top="'Delete'"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <!-- Loading State -->
        <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-py-12">
          <div class="tw-flex tw-flex-col tw-items-center tw-gap-4">
            <ProgressSpinner />
            <p class="tw-text-slate-600 tw-m-0">Loading settings...</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="settingDialog"
      :modal="true"
      class="tw-w-full tw-max-w-2xl"
    >
      <template #header>
        <div class="tw-flex tw-items-center tw-gap-3">
          <div class="tw-bg-blue-100 tw-p-2 tw-rounded-lg">
            <i class="pi pi-percentage tw-text-blue-600"></i>
          </div>
          <div>
            <h3 class="tw-m-0 tw-text-xl tw-font-bold tw-text-slate-800">
              {{ editMode ? 'Edit Setting' : 'New Setting' }}
            </h3>
            <p class="tw-m-0 tw-text-sm tw-text-slate-600 tw-mt-1">
              {{ editMode ? 'Update selling setting details' : 'Add a new selling setting' }}
            </p>
          </div>
        </div>
      </template>

      <div class="tw-space-y-4">
        <!-- Service Selection -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">
            Service <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown
            v-model="formData.service_id"
            :options="services"
            optionLabel="name"
            optionValue="id"
            placeholder="Select a service"
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.service_id }"
          />
          <small v-if="formErrors.service_id" class="tw-text-red-500">{{ formErrors.service_id }}</small>
        </div>

        <!-- Percentage -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">
            Percentage <span class="tw-text-red-500">*</span>
          </label>
          <InputNumber
            v-model="formData.percentage"
            :min="0"
            :max="100"
            :minFractionDigits="2"
            :maxFractionDigits="2"
            suffix="%"
            placeholder="Enter percentage"
            class="tw-w-full"
            :class="{ 'p-invalid': formErrors.percentage }"
          />
          <small v-if="formErrors.percentage" class="tw-text-red-500">{{ formErrors.percentage }}</small>
        </div>

        <!-- Type (Read-only, determined by current context) -->
        <div>
          <label class="tw-block tw-text-sm tw-font-medium tw-text-slate-700 tw-mb-2">
            Type
          </label>
          <div class="tw-bg-slate-50 tw-px-4 tw-py-3 tw-rounded-lg tw-border tw-border-slate-200">
            <Tag
              :value="currentType.charAt(0).toUpperCase() + currentType.slice(1)"
              :severity="currentType === 'pharmacy' ? 'success' : 'info'"
              class="tw-font-semibold"
            />
            <span class="tw-text-sm tw-text-slate-600 tw-ml-2">
              (Automatically set based on current context)
            </span>
          </div>
        </div>

        <!-- Active Status -->
        <div class="tw-flex tw-items-center tw-gap-3 tw-bg-blue-50 tw-p-4 tw-rounded-lg tw-border tw-border-blue-200">
          <Checkbox v-model="formData.is_active" :binary="true" inputId="is_active" />
          <label for="is_active" class="tw-text-sm tw-font-medium tw-text-slate-700 tw-cursor-pointer">
            Set as active
          </label>
          <i
            class="pi pi-info-circle tw-text-blue-600 tw-ml-auto"
            v-tooltip.top="'Only one setting can be active per service and type'"
          ></i>
        </div>

        <!-- Warning for Active Setting -->
        <Message
          v-if="formData.is_active"
          severity="warn"
          :closable="false"
        >
          Setting this as active will automatically deactivate any other active setting for this service and type.
        </Message>
      </div>

      <template #footer>
        <div class="tw-flex tw-gap-2 tw-justify-end">
          <Button
            label="Cancel"
            icon="pi pi-times"
            @click="settingDialog = false"
            class="p-button-text p-button-secondary"
          />
          <Button
            :label="editMode ? 'Update' : 'Create'"
            icon="pi pi-check"
            @click="saveSetting"
            :loading="saving"
            class="p-button-primary"
          />
        </div>
      </template>
    </Dialog>

    <!-- ConfirmDialog component -->
    <ConfirmDialog />

    <!-- Toast component -->
    <Toast />

    <!-- Floating Action Button -->
    <button
      @click="openCreateDialog"
      class="fab tw-shadow-xl"
      v-tooltip.top="'Create New Setting'"
    >
      <i class="pi pi-plus tw-text-xl"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Checkbox from 'primevue/checkbox'
import ConfirmDialog from 'primevue/confirmdialog'
import Toast from 'primevue/toast'
import ProgressSpinner from 'primevue/progressspinner'
import axios from 'axios'

// Props
const props = defineProps({
  type: {
    type: String,
    default: 'pharmacy',
    validator: (value) => ['pharmacy', 'stock'].includes(value)
  }
})

// Composables
const toast = useToast()
const confirm = useConfirm()

// State Management
const loading = ref(false)
const saving = ref(false)
const settings = ref([])
const services = ref([])
const selectedSettings = ref([])
const settingDialog = ref(false)
const editMode = ref(false)
const currentSetting = ref(null)

// Current type based on route/context
const currentType = ref(props.type)

// Form Data
const formData = reactive({
  service_id: null,
  percentage: 0,
  type: currentType.value,
  is_active: false
})

const formErrors = reactive({
  service_id: null,
  percentage: null
})

// Filters
const filters = reactive({
  service_id: null,
  is_active: null,
  search: ''
})

// Search debounce
let searchTimeout = null

// Computed Properties
const hasActiveFilters = computed(() => {
  return filters.service_id || filters.is_active !== null || filters.search
})

const activeCount = computed(() => {
  return settings.value.filter(s => s.is_active).length
})

const uniqueServicesCount = computed(() => {
  const uniqueServices = new Set(settings.value.map(s => s.service_id))
  return uniqueServices.size
})

// Options
const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// API Methods
const fetchSettings = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()

    // Always filter by current type
    params.append('type', currentType.value)

    if (filters.service_id) params.append('service_id', filters.service_id)
    if (filters.is_active !== null) params.append('is_active', filters.is_active)
    if (filters.search) params.append('search', filters.search)

    const response = await axios.get(`/api/selling-settings?${params.toString()}`)

    if (response.data.status === 'success') {
      settings.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching settings:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load selling settings',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchServices = async () => {
  try {
    const response = await axios.get('/api/selling-settings/services')
    if (response.data.status === 'success') {
      services.value = response.data.data
    }
  } catch (err) {
    console.error('Error fetching services:', err)
  }
}

const applyFilters = async () => {
  await fetchSettings()
}

const refreshData = async () => {
  await Promise.all([fetchSettings(), fetchServices()])
  toast.add({
    severity: 'success',
    summary: 'Refreshed',
    detail: 'Data refreshed successfully',
    life: 2000
  })
}

const openCreateDialog = () => {
  editMode.value = false
  currentSetting.value = null
  resetForm()
  settingDialog.value = true
}

const editSetting = (setting) => {
  editMode.value = true
  currentSetting.value = setting
  formData.service_id = setting.service_id
  formData.percentage = parseFloat(setting.percentage)
  formData.type = setting.type
  formData.is_active = setting.is_active
  settingDialog.value = true
}

const resetForm = () => {
  formData.service_id = null
  formData.percentage = 0
  formData.type = currentType.value
  formData.is_active = false
  formErrors.service_id = null
  formErrors.percentage = null
}

const validateForm = () => {
  let isValid = true
  formErrors.service_id = null
  formErrors.percentage = null

  if (!formData.service_id) {
    formErrors.service_id = 'Service is required'
    isValid = false
  }

  if (formData.percentage < 0 || formData.percentage > 100) {
    formErrors.percentage = 'Percentage must be between 0 and 100'
    isValid = false
  }

  return isValid
}

const saveSetting = async () => {
  if (!validateForm()) return

  try {
    saving.value = true

    const payload = {
      ...formData,
      type: currentType.value // Always use current context type
    }

    let response
    if (editMode.value) {
      response = await axios.put(`/api/selling-settings/${currentSetting.value.id}`, payload)
    } else {
      response = await axios.post('/api/selling-settings', payload)
    }

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: editMode.value ? 'Setting updated successfully' : 'Setting created successfully',
        life: 3000
      })
      settingDialog.value = false
      await fetchSettings()
    }
  } catch (err) {
    console.error('Error saving setting:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save setting',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const toggleActive = async (setting) => {
  try {
    const response = await axios.post(`/api/selling-settings/${setting.id}/toggle-active`)

    if (response.data.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Status updated successfully',
        life: 2000
      })
      await fetchSettings()
    }
  } catch (err) {
    console.error('Error toggling status:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to toggle status',
      life: 3000
    })
  }
}

const deleteSetting = (setting) => {
  confirm.require({
    message: `Delete setting for ${setting.service?.name}? This action cannot be undone.`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/selling-settings/${setting.id}`)

        if (response.data.status === 'success') {
          toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Setting deleted successfully',
            life: 3000
          })
          await fetchSettings()
        }
      } catch (err) {
        console.error('Error deleting setting:', err)
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete setting',
          life: 3000
        })
      }
    }
  })
}

// Helper Methods
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const clearSearch = () => {
  filters.search = ''
  applyFilters()
}

const clearAllFilters = () => {
  filters.service_id = null
  filters.is_active = null
  filters.search = ''
  applyFilters()
}

const getServiceName = (serviceId) => {
  const service = services.value.find(s => s.id === serviceId)
  return service ? service.name : 'Unknown'
}

const getInitials = (name) => {
  if (!name) return '?'
  return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase().slice(0, 2)
}

const formatDateOnly = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTimeOnly = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    fetchSettings(),
    fetchServices()
  ])
})
</script>

<style scoped>
/* Enhanced Medical Table Styles */
:deep(.medical-table .p-datatable-header) {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

:deep(.medical-table .p-datatable-thead > tr > th) {
  background-color: #f8fafc;
  color: #374151;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 0.75rem;
}

:deep(.medical-table .p-datatable-tbody > tr) {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s ease;
}

:deep(.medical-table .p-datatable-tbody > tr:hover) {
  background-color: rgba(59, 130, 246, 0.05);
}

:deep(.medical-table .p-datatable-tbody > tr.p-highlight) {
  background-color: #eff6ff;
}

:deep(.p-button-sm) {
  font-size: 0.875rem;
  padding: 0.375rem 0.625rem;
}

:deep(.p-tag) {
  font-weight: 600;
}

/* Enhanced Animations and Effects */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(-10px); }
  to { opacity: 1; transform: translateX(0); }
}

.tw-animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.tw-animate-slide-in {
  animation: slideIn 0.2s ease-out;
}

/* Custom scrollbar */
:deep(.p-datatable-wrapper::-webkit-scrollbar) {
  width: 6px;
  height: 6px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-track) {
  background: #f1f5f9;
  border-radius: 3px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb) {
  background: #cbd5e1;
  border-radius: 3px;
}

:deep(.p-datatable-wrapper::-webkit-scrollbar-thumb:hover) {
  background: #94a3b8;
}

/* Floating Action Button */
.fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.39);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  transition: all 0.3s ease;
  z-index: 1000;
}

.fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.fab:active {
  transform: scale(0.95);
}
</style>
