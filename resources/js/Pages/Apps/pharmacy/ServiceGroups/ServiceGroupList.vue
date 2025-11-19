<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-white tw-to-indigo-50 tw-p-6">
    <!-- Toast for notifications -->
    <Toast position="top-right" />
    
    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <div class="tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-rounded-2xl tw-shadow-xl tw-p-8 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">Service Groups</h1>
            <p class="tw-text-blue-100">Manage service groups for {{ type === 'pharmacy' ? 'Pharmacy' : 'Stock' }}</p>
          </div>
          <div class="tw-flex tw-gap-4">
            <Button
              @click="refreshGroups"
              icon="pi pi-refresh"
              label="Refresh"
              severity="secondary"
              outlined
              class="tw-bg-white/10 hover:tw-bg-white/20"
            />
            <Button
              @click="openCreateDialog"
              icon="pi pi-plus"
              label="Create Group"
              class="tw-bg-white tw-text-blue-600 hover:tw-bg-blue-50"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-mb-6">
      <div class="tw-flex tw-flex-wrap tw-gap-4 tw-items-center">
        <div class="tw-flex-1 tw-min-w-64">
          <span class="p-input-icon-left tw-w-full">
            <i class="pi pi-search"></i>
            <InputText
              v-model="searchQuery"
              placeholder="Search groups..."
              class="tw-w-full"
              @input="handleSearch"
            />
          </span>
        </div>
        
        <Dropdown
          v-model="activeFilter"
          :options="activeFilterOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Filter by Status"
          class="tw-w-48"
          showClear
          @change="loadGroups"
        />
      </div>
    </div>

    <!-- Service Groups Table -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-overflow-hidden">
      <DataTable
        :value="groups"
        :loading="loading"
        class="p-datatable-sm"
        stripedRows
        showGridlines
        responsiveLayout="scroll"
      >
        <template #loading>
          <div class="tw-text-center tw-py-16">
            <ProgressSpinner />
            <p class="tw-mt-4 tw-text-gray-600">Loading service groups...</p>
          </div>
        </template>

        <template #empty>
          <div class="tw-text-center tw-py-16">
            <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
            <p class="tw-text-gray-600 tw-text-lg">No service groups found</p>
          </div>
        </template>

        <!-- Name Column -->
        <Column field="name" header="Group Name" style="min-width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div 
                class="tw-w-10 tw-h-10 tw-rounded-lg tw-flex tw-items-center tw-justify-center"
                :style="{ backgroundColor: slotProps.data.color }"
              >
                <i class="pi pi-th-large tw-text-white"></i>
              </div>
              <div>
                <div class="tw-font-semibold tw-text-gray-900">{{ slotProps.data.name }}</div>
                <div v-if="slotProps.data.code" class="tw-text-xs tw-text-gray-500">{{ slotProps.data.code }}</div>
              </div>
            </div>
          </template>
        </Column>

        <!-- Description Column -->
        <Column field="description" header="Description" style="min-width: 250px">
          <template #body="slotProps">
            <div class="tw-text-sm tw-text-gray-600">
              {{ slotProps.data.description || 'No description' }}
            </div>
          </template>
        </Column>

        <!-- Services Column -->
        <Column header="Services" style="min-width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-flex-wrap tw-gap-1">
              <Tag 
                v-for="service in slotProps.data.services.slice(0, 3)" 
                :key="service.id"
                :value="service.name"
                severity="info"
                class="tw-text-xs"
              />
              <Tag 
                v-if="slotProps.data.services.length > 3"
                :value="`+${slotProps.data.services.length - 3} more`"
                severity="secondary"
                class="tw-text-xs"
              />
            </div>
            <div class="tw-text-xs tw-text-gray-500 tw-mt-1">
              {{ slotProps.data.services.length }} service(s)
            </div>
          </template>
        </Column>

        <!-- Status Column -->
        <Column field="is_active" header="Status" style="min-width: 100px">
          <template #body="slotProps">
            <Tag 
              :value="slotProps.data.is_active ? 'Active' : 'Inactive'"
              :severity="slotProps.data.is_active ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <!-- Actions Column -->
        <Column header="Actions" style="min-width: 200px">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button
                @click="viewGroup(slotProps.data)"
                icon="pi pi-eye"
                severity="info"
                outlined
                size="small"
                v-tooltip="'View Details'"
              />
              <Button
                @click="editGroup(slotProps.data)"
                icon="pi pi-pencil"
                severity="warning"
                outlined
                size="small"
                v-tooltip="'Edit'"
              />
              <Button
                @click="toggleActive(slotProps.data)"
                :icon="slotProps.data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'"
                :severity="slotProps.data.is_active ? 'warning' : 'success'"
                outlined
                size="small"
                :v-tooltip="slotProps.data.is_active ? 'Deactivate' : 'Activate'"
              />
              <Button
                @click="confirmDelete(slotProps.data)"
                icon="pi pi-trash"
                severity="danger"
                outlined
                size="small"
                v-tooltip="'Delete'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="dialogMode === 'create' ? 'Create Service Group' : 'Edit Service Group'"
      :style="{ width: '60rem' }"
      modal
      class="tw-rounded-2xl"
    >
      <form @submit.prevent="saveGroup" class="tw-space-y-6">
        <!-- Hidden type field -->
        <input type="hidden" v-model="groupForm.type" />
        
        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6">
          <!-- Name -->
          <div class="tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Group Name *
            </label>
            <InputText
              v-model="groupForm.name"
              placeholder="Enter group name"
              class="tw-w-full"
              required
            />
          </div>

          <!-- Code -->
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Code
            </label>
            <InputText
              v-model="groupForm.code"
              placeholder="e.g., GRP1"
              class="tw-w-full"
            />
          </div>

          <!-- Color -->
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Color
            </label>
            <ColorPicker v-model="groupForm.color" class="tw-w-full" />
          </div>

          <!-- Description -->
          <div class="tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Description
            </label>
            <Textarea
              v-model="groupForm.description"
              rows="3"
              placeholder="Enter description"
              class="tw-w-full"
            />
          </div>

          <!-- Sort Order -->
          <div>
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Sort Order
            </label>
            <InputNumber
              v-model="groupForm.sort_order"
              :min="0"
              class="tw-w-full"
            />
          </div>

          <!-- Is Active -->
          <div class="tw-flex tw-items-center tw-gap-3 tw-mt-8">
            <Checkbox v-model="groupForm.is_active" inputId="is_active" binary />
            <label for="is_active" class="tw-text-sm tw-font-medium tw-text-gray-700">
              Active
            </label>
          </div>

          <!-- Services Selection -->
          <div class="tw-col-span-2">
            <label class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              Services
            </label>
            <MultiSelect
              v-model="groupForm.service_ids"
              :options="availableServices"
              optionLabel="name"
              optionValue="id"
              placeholder="Select services"
              :filter="true"
              class="tw-w-full"
            />
            <small class="tw-text-gray-500">Selected {{ groupForm.service_ids.length }} service(s)</small>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="tw-flex tw-justify-end tw-gap-3 tw-pt-4 tw-border-t tw-border-gray-200">
          <Button
            type="button"
            @click="showDialog = false"
            label="Cancel"
            severity="secondary"
            outlined
          />
          <Button
            type="submit"
            :label="dialogMode === 'create' ? 'Create' : 'Update'"
            :loading="submitting"
          />
        </div>
      </form>
    </Dialog>

    <!-- View Details Dialog -->
    <Dialog
      v-model:visible="showViewDialog"
      header="Service Group Details"
      :style="{ width: '70rem' }"
      modal
      class="tw-rounded-2xl"
    >
      <div v-if="viewingGroup" class="tw-space-y-6">
        <!-- Group Info -->
        <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-p-6 tw-rounded-xl">
          <div class="tw-flex tw-items-start tw-justify-between">
            <div class="tw-flex tw-items-center tw-gap-4">
              <div 
                class="tw-w-16 tw-h-16 tw-rounded-xl tw-flex tw-items-center tw-justify-center"
                :style="{ backgroundColor: viewingGroup.color }"
              >
                <i class="pi pi-th-large tw-text-3xl tw-text-white"></i>
              </div>
              <div>
                <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900">{{ viewingGroup.name }}</h3>
                <p v-if="viewingGroup.code" class="tw-text-sm tw-text-gray-600">Code: {{ viewingGroup.code }}</p>
                <Tag 
                  :value="viewingGroup.is_active ? 'Active' : 'Inactive'"
                  :severity="viewingGroup.is_active ? 'success' : 'danger'"
                  class="tw-mt-2"
                />
              </div>
            </div>
          </div>
          <p v-if="viewingGroup.description" class="tw-text-gray-700 tw-mt-4">
            {{ viewingGroup.description }}
          </p>
        </div>

        <!-- Services List -->
        <div>
          <h4 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-4">
            Services in this Group ({{ viewingGroup.services?.length || 0 }})
          </h4>
          <div v-if="viewingGroup.services && viewingGroup.services.length > 0" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div 
              v-for="service in viewingGroup.services" 
              :key="service.id"
              class="tw-p-4 tw-bg-gray-50 tw-rounded-lg tw-border tw-border-gray-200"
            >
              <div class="tw-font-medium tw-text-gray-900">{{ service.name }}</div>
              <div v-if="service.service_abv" class="tw-text-sm tw-text-gray-500">{{ service.service_abv }}</div>
            </div>
          </div>
          <div v-else class="tw-text-center tw-py-8 tw-text-gray-500">
            No services in this group
          </div>
        </div>
      </div>

      <template #footer>
        <Button @click="showViewDialog = false" label="Close" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import ProgressSpinner from 'primevue/progressspinner'
import MultiSelect from 'primevue/multiselect'
import ColorPicker from 'primevue/colorpicker'

export default {
  name: 'ServiceGroupList',
  components: {
    DataTable,
    Column,
    Dialog,
    Button,
    InputText,
    Textarea,
    Dropdown,
    InputNumber,
    Checkbox,
    Tag,
    Toast,
    ConfirmDialog,
    ProgressSpinner,
    MultiSelect,
    ColorPicker,
  },
  props: {
    type: {
      type: String,
      default: 'pharmacy',
      validator: (value) => ['stock', 'pharmacy'].includes(value)
    }
  },
  data() {
    return {
      groups: [],
      availableServices: [],
      loading: false,
      submitting: false,
      searchQuery: '',
      activeFilter: null,
      showDialog: false,
      showViewDialog: false,
      dialogMode: 'create', // 'create' or 'edit'
      viewingGroup: null,
      groupForm: {
        id: null,
        name: '',
        code: '',
        description: '',
        color: '#3B82F6',
        is_active: true,
        sort_order: 0,
        service_ids: [],
        type: this.type,
      },
      activeFilterOptions: [
        { label: 'All', value: null },
        { label: 'Active Only', value: true },
        { label: 'Inactive Only', value: false },
      ],
    }
  },
  mounted() {
    this.toast = useToast()
    this.confirm = useConfirm()
    this.loadGroups()
    this.loadServices()
  },
  methods: {
    async loadGroups() {
      this.loading = true
      try {
        const params = {
          type: this.type
        }
        if (this.searchQuery) params.search = this.searchQuery
        if (this.activeFilter !== null) params.is_active = this.activeFilter

        const response = await axios.get('/api/service-groups', { params })
        this.groups = response.data.data || []
      } catch (error) {
        console.error('Error loading groups:', error)
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load service groups',
          life: 3000,
        })
      } finally {
        this.loading = false
      }
    },
    
    async loadServices() {
      try {
        const response = await axios.get('/api/service-groups/all-services')
        this.availableServices = response.data.data || []
      } catch (error) {
        console.error('Error loading services:', error)
      }
    },

    handleSearch() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.loadGroups()
      }, 300)
    },

    openCreateDialog() {
      this.dialogMode = 'create'
      this.resetForm()
      this.showDialog = true
    },

    editGroup(group) {
      this.dialogMode = 'edit'
      this.groupForm = {
        id: group.id,
        name: group.name,
        code: group.code || '',
        description: group.description || '',
        color: group.color || '#3B82F6',
        is_active: group.is_active,
        sort_order: group.sort_order || 0,
        service_ids: group.services.map(s => s.id),
        type: this.type,
      }
      this.showDialog = true
    },

    viewGroup(group) {
      this.viewingGroup = group
      this.showViewDialog = true
    },

    async saveGroup() {
      this.submitting = true
      try {
        const url = this.dialogMode === 'create' 
          ? '/api/service-groups'
          : `/api/service-groups/${this.groupForm.id}`
        
        const method = this.dialogMode === 'create' ? 'post' : 'put'
        
        const response = await axios[method](url, this.groupForm)
        
        if (response.data.status === 'success') {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message,
            life: 3000,
          })
          this.showDialog = false
          this.loadGroups()
          this.resetForm()
        }
      } catch (error) {
        console.error('Error saving group:', error)
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to save service group',
          life: 3000,
        })
      } finally {
        this.submitting = false
      }
    },

    async toggleActive(group) {
      try {
        const response = await axios.post(`/api/service-groups/${group.id}/toggle-active`)
        
        if (response.data.status === 'success') {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message,
            life: 3000,
          })
          this.loadGroups()
        }
      } catch (error) {
        console.error('Error toggling status:', error)
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to update status',
          life: 3000,
        })
      }
    },

    confirmDelete(group) {
      this.confirm.require({
        message: `Are you sure you want to delete "${group.name}"? This action cannot be undone.`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
          this.deleteGroup(group.id)
        },
      })
    },

    async deleteGroup(id) {
      try {
        const response = await axios.delete(`/api/service-groups/${id}`)
        
        if (response.data.status === 'success') {
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: response.data.message,
            life: 3000,
          })
          this.loadGroups()
        }
      } catch (error) {
        console.error('Error deleting group:', error)
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete service group',
          life: 3000,
        })
      }
    },

    refreshGroups() {
      this.loadGroups()
      this.toast.add({
        severity: 'info',
        summary: 'Refreshed',
        detail: 'Service groups list refreshed',
        life: 2000,
      })
    },

    resetForm() {
      this.groupForm = {
        id: null,
        name: '',
        code: '',
        description: '',
        color: '#3B82F6',
        is_active: true,
        sort_order: 0,
        service_ids: [],
        type: this.type,
      }
    },
  },
}
</script>

<style scoped>
/* Custom styles */
</style>
