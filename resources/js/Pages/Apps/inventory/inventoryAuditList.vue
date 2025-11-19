<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-teal-50 tw-via-white tw-to-cyan-50 tw-p-6">
    <!-- Success Toast -->
    <Toast position="top-right" />

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-mb-8">
      <div class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700 tw-rounded-2xl tw-shadow-xl tw-p-8 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">
              <i class="pi pi-check-square tw-mr-3"></i>
              Inventory Audits
            </h1>
            <p class="tw-text-teal-100 tw-text-lg">
              Manage and track inventory audit processes
            </p>
          </div>
          <div class="tw-text-right">
            <div class="tw-bg-white/20 tw-rounded-xl tw-px-6 tw-py-3">
              <div class="tw-text-sm tw-text-teal-100">Total Audits</div>
              <div class="tw-text-3xl tw-font-bold">{{ total }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Search Section -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-p-6 tw-mb-6">
      <div class="tw-flex tw-flex-col lg:tw-flex-row tw-gap-6 tw-items-start lg:tw-items-center tw-justify-between">
        <!-- Search -->
        <div class="tw-flex-1 tw-max-w-md">
          <span class="p-input-icon-left tw-w-full">
            <i class="pi pi-search tw-text-gray-400" />
            <InputText 
              v-model="searchQuery"
              @input="onSearchInput"
              placeholder="Search audits..." 
              class="tw-w-full tw-pl-10 tw-pr-4 tw-py-3 tw-border-2 tw-border-gray-200 tw-rounded-xl focus:tw-border-teal-500"
            />
          </span>
        </div>

        <!-- Status Filter -->
        <div class="tw-flex tw-gap-3 tw-flex-wrap">
          <Button
            :label="`All (${statusCounts.all})`"
            :class="activeStatusFilter === 'all' ? 'tw-bg-teal-600 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all tw-border-0"
            @click="setStatusFilter('all')"
          />
          <Button
            :label="`Draft (${statusCounts.draft})`"
            :class="activeStatusFilter === 'draft' ? 'tw-bg-gray-500 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all tw-border-0"
            @click="setStatusFilter('draft')"
          />
          <Button
            :label="`In Progress (${statusCounts.in_progress})`"
            :class="activeStatusFilter === 'in_progress' ? 'tw-bg-blue-500 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all tw-border-0"
            @click="setStatusFilter('in_progress')"
          />
          <Button
            :label="`Completed (${statusCounts.completed})`"
            :class="activeStatusFilter === 'completed' ? 'tw-bg-green-500 tw-text-white' : 'tw-bg-gray-100 tw-text-gray-700'"
            class="tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-lg tw-transition-all tw-border-0"
            @click="setStatusFilter('completed')"
          />
        </div>

        <!-- Actions -->
        <div class="tw-flex tw-gap-3">
          <Button
            icon="pi pi-refresh"
            class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-4 tw-py-3 hover:tw-bg-gray-200 tw-transition-all"
            v-tooltip.top="'Refresh'"
            @click="refreshAudits"
          />
          <Button
            label="Create Audit"
            icon="pi pi-plus"
            class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700 tw-text-white tw-border-0 tw-rounded-xl tw-px-6 tw-py-3 tw-font-semibold hover:tw-shadow-xl tw-transition-all"
            @click="openCreateDialog"
          />
        </div>
      </div>
    </div>

    <!-- Audits Table -->
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-overflow-hidden">
      <DataTable
        :value="audits"
        :loading="loading"
        :paginator="true"
        :rows="perPage"
        :totalRecords="total"
        :lazy="true"
        @page="onPage"
        @row-click="onRowClick"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
        :rowsPerPageOptions="[10, 25, 50, 100]"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} audits"
        dataKey="id"
        responsiveLayout="scroll"
        class="tw-w-full"
      >
        <template #loading>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-12">
            <i class="pi pi-spin pi-spinner tw-text-6xl tw-text-teal-500 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-lg">Loading audits...</p>
          </div>
        </template>

        <template #empty>
          <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
            <i class="pi pi-check-square tw-text-8xl tw-text-gray-300 tw-mb-4"></i>
            <p class="tw-text-gray-500 tw-text-xl tw-font-semibold tw-mb-2">No audits found</p>
            <p class="tw-text-gray-400">Create your first inventory audit</p>
          </div>
        </template>

        <Column field="name" header="Audit Name" style="min-width: 300px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-3">
              <div class="tw-bg-gradient-to-br tw-from-teal-400 tw-to-cyan-500 tw-w-12 tw-h-12 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-lg tw-shadow-md">
                <i class="pi pi-check-square"></i>
              </div>
              <div>
                <div class="tw-font-semibold tw-text-gray-800">{{ slotProps.data.name }}</div>
                <div class="tw-text-sm tw-text-gray-500 tw-line-clamp-1">
                  {{ slotProps.data.description || 'No description' }}
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="status" header="Status" style="width: 150px">
          <template #body="slotProps">
            <Tag 
              :value="slotProps.data.status" 
              :severity="getStatusSeverity(slotProps.data.status)"
              :icon="getStatusIcon(slotProps.data.status)"
              class="tw-px-3 tw-py-1"
            />
          </template>
        </Column>

        <Column header="Scope" style="min-width: 250px">
          <template #body="slotProps">
            <div class="tw-space-y-1">
              <!-- Type Badge -->
              <div>
                <Tag 
                  v-if="slotProps.data.is_global" 
                  value="Global Audit" 
                  severity="info" 
                  icon="pi pi-globe"
                  class="tw-px-2 tw-py-1 tw-text-xs"
                />
                <Tag 
                  v-else-if="slotProps.data.is_pharmacy_wide" 
                  value="Pharmacy" 
                  severity="success" 
                  icon="pi pi-heart"
                  class="tw-px-2 tw-py-1 tw-text-xs"
                />
                <Tag 
                  v-else 
                  value="General Stock" 
                  severity="warning" 
                  icon="pi pi-box"
                  class="tw-px-2 tw-py-1 tw-text-xs"
                />
              </div>
              <!-- Service & Stockage Info -->
              <div v-if="!slotProps.data.is_global" class="tw-text-xs tw-text-gray-600">
                <div v-if="slotProps.data.service" class="tw-flex tw-items-center tw-gap-1">
                  <i class="pi pi-building tw-text-teal-500"></i>
                  <span>{{ slotProps.data.service.name }}</span>
                </div>
                <div v-if="slotProps.data.stockage" class="tw-flex tw-items-center tw-gap-1 tw-mt-1">
                  <i class="pi pi-warehouse tw-text-cyan-500"></i>
                  <span>{{ slotProps.data.stockage.name }}</span>
                </div>
                <div v-else-if="slotProps.data.service" class="tw-text-gray-400 tw-italic tw-mt-1">
                  All stockages in service
                </div>
              </div>
              <div v-else class="tw-text-xs tw-text-gray-400 tw-italic">
                All products & stockages
              </div>
            </div>
          </template>
        </Column>

        <Column field="creator" header="Created By" style="min-width: 200px">
          <template #body="slotProps">
            <div v-if="slotProps.data.creator" class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-teal-100 tw-flex tw-items-center tw-justify-center tw-text-teal-600 tw-font-semibold">
                {{ getInitials(slotProps.data.creator.name) }}
              </div>
              <div>
                <div class="tw-text-sm tw-font-medium">{{ slotProps.data.creator.name }}</div>
                <div class="tw-text-xs tw-text-gray-500">{{ slotProps.data.creator.email }}</div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="participants_count" header="Participants" style="width: 150px">
          <template #body="slotProps">
            <div class="tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-users tw-text-teal-500"></i>
              <span class="tw-font-semibold tw-text-gray-700">
                {{ slotProps.data.participants_count || 0 }}
              </span>
            </div>
          </template>
        </Column>

        <Column field="scheduled_at" header="Scheduled" style="width: 180px">
          <template #body="slotProps">
            <div class="tw-text-sm tw-text-gray-600">
              {{ formatDate(slotProps.data.scheduled_at) }}
            </div>
          </template>
        </Column>

        <Column field="created_at" header="Created" style="width: 180px">
          <template #body="slotProps">
            <div class="tw-text-sm tw-text-gray-600">
              {{ formatDateTime(slotProps.data.created_at) }}
            </div>
          </template>
        </Column>

        <Column header="Actions" style="width: 150px" :frozen="true" alignFrozen="right">
          <template #body="slotProps">
            <div class="tw-flex tw-gap-2">
              <Button
                icon="pi pi-eye"
                class="tw-bg-blue-500 tw-border-blue-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-blue-600 tw-transition-all"
                v-tooltip.top="'View'"
                @click.stop="viewAudit(slotProps.data)"
              />
              <Button
                icon="pi pi-pencil"
                class="tw-bg-orange-500 tw-border-orange-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-orange-600 tw-transition-all"
                v-tooltip.top="'Edit'"
                @click.stop="editAudit(slotProps.data)"
              />
              <Button
                icon="pi pi-trash"
                class="tw-bg-red-500 tw-border-red-500 tw-text-white tw-rounded-lg tw-p-2 hover:tw-bg-red-600 tw-transition-all"
                v-tooltip.top="'Delete'"
                @click.stop="confirmDelete(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Create/Edit Audit Dialog -->
    <CreateEditAuditDialog
      v-if="showCreateDialog || showEditDialog"
      :visible="showCreateDialog || showEditDialog"
      :audit="editingAudit"
      :users="users"
      @close="closeCreateDialog"
      @save="handleSave"
    />
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Custom Components
import CreateEditAuditDialog from '../../../Components/Apps/inventory/CreateEditAuditDialog.vue';

export default {
  name: 'InventoryAuditList',
  components: {
    DataTable,
    Column,
    Button,
    InputText,
    Tag,
    Toast,
    ConfirmDialog,
    CreateEditAuditDialog
  },
  data() {
    return {
      audits: [],
      users: [],
      editingAudit: null,
      showCreateDialog: false,
      showEditDialog: false,
      loading: false,
      searchQuery: '',
      searchTimeout: null,
      activeStatusFilter: 'all',
      currentPage: 1,
      perPage: 10,
      total: 0,
      statusCounts: {
        all: 0,
        draft: 0,
        in_progress: 0,
        completed: 0
      }
    };
  },
  mounted() {
    this.toast = useToast();
    this.confirm = useConfirm();
    this.fetchAudits();
    this.fetchUsers();
  },
  methods: {
    async fetchAudits(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.perPage,
          search: this.searchQuery
        };

        if (this.activeStatusFilter !== 'all') {
          params.status = this.activeStatusFilter;
        }

        const response = await axios.get('/api/inventory-audits', { params });
        
        this.audits = response.data.data;
        this.total = response.data.meta.total;
        this.currentPage = response.data.meta.current_page;
        
        this.calculateStatusCounts();
      } catch (error) {
        console.error('Failed to fetch audits:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load audits',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },

    async fetchUsers() {
      try {
        const response = await axios.get('/api/users');
        this.users = response.data.data || response.data;
      } catch (error) {
        console.error('Failed to fetch users:', error);
      }
    },

    calculateStatusCounts() {
      this.statusCounts = {
        all: this.total,
        draft: 0,
        in_progress: 0,
        completed: 0
      };

      this.audits.forEach(audit => {
        if (this.statusCounts[audit.status] !== undefined) {
          this.statusCounts[audit.status]++;
        }
      });
    },

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.fetchAudits(1);
      }, 300);
    },

    onPage(event) {
      this.currentPage = event.page + 1;
      this.perPage = event.rows;
      this.fetchAudits(this.currentPage);
    },

    onRowClick(event) {
      this.viewAudit(event.data);
    },

    setStatusFilter(status) {
      this.activeStatusFilter = status;
      this.currentPage = 1;
      this.fetchAudits(1);
    },

    async refreshAudits() {
      await this.fetchAudits(this.currentPage);
      this.toast.add({
        severity: 'success',
        summary: 'Refreshed',
        detail: 'Audit list updated',
        life: 2000
      });
    },

    openCreateDialog() {
      this.editingAudit = null;
      this.showCreateDialog = true;
    },

    closeCreateDialog() {
      this.showCreateDialog = false;
      this.showEditDialog = false;
      this.editingAudit = null;
    },

    viewAudit(audit) {
      // Navigate to the view page using Vue Router
      this.$router.push({ name: 'Inventory-audit.view', params: { id: audit.id } });
    },

    editAudit(audit) {
      this.editingAudit = { ...audit };
      this.showEditDialog = true;
    },

    async handleSave(auditData) {
      try {
        if (this.editingAudit) {
          // Update existing audit
          await axios.put(`/api/inventory-audits/${this.editingAudit.id}`, auditData);
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Audit updated successfully',
            life: 3000
          });
        } else {
          // Create new audit
          await axios.post('/api/inventory-audits', auditData);
          this.toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Audit created successfully',
            life: 3000
          });
        }

        this.closeCreateDialog();
        this.fetchAudits(this.currentPage);
      } catch (error) {
        console.error('Failed to save audit:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to save audit',
          life: 3000
        });
      }
    },

    confirmDelete(audit) {
      this.confirm.require({
        message: `Are you sure you want to delete "${audit.name}"?`,
        header: 'Delete Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => this.deleteAudit(audit)
      });
    },

    async deleteAudit(audit) {
      try {
        await axios.delete(`/api/inventory-audits/${audit.id}`);
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Audit deleted successfully',
          life: 3000
        });

        this.fetchAudits(this.currentPage);
      } catch (error) {
        console.error('Failed to delete audit:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to delete audit',
          life: 3000
        });
      }
    },

    async startAudit(audit) {
      try {
        await axios.post(`/api/inventory-audits/${audit.id}/start`);
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Audit started successfully',
          life: 3000
        });

        this.fetchAudits(this.currentPage);
      } catch (error) {
        console.error('Failed to start audit:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to start audit',
          life: 3000
        });
      }
    },

    async completeAudit(audit) {
      try {
        await axios.post(`/api/inventory-audits/${audit.id}/complete`);
        
        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Audit completed successfully',
          life: 3000
        });

        this.fetchAudits(this.currentPage);
      } catch (error) {
        console.error('Failed to complete audit:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to complete audit',
          life: 3000
        });
      }
    },

    getStatusSeverity(status) {
      const severities = {
        draft: 'secondary',
        in_progress: 'info',
        completed: 'success',
        cancelled: 'danger'
      };
      return severities[status] || 'secondary';
    },

    getStatusIcon(status) {
      const icons = {
        draft: 'pi pi-file',
        in_progress: 'pi pi-spin pi-spinner',
        completed: 'pi pi-check-circle',
        cancelled: 'pi pi-times-circle'
      };
      return icons[status] || 'pi pi-info-circle';
    },

    getInitials(name) {
      if (!name) return '??';
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatDateTime(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
/* Custom styles for PrimeVue components */
.p-datatable .p-datatable-tbody > tr {
  cursor: pointer !important;
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f0fdfa !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.1);
  transition: all 0.2s ease;
}

/* Enhanced button styles */
.p-button {
  transition: all 0.3s ease !important;
}

.p-button:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(20, 184, 166, 0.15) !important;
}

/* Dialog animations */
.p-dialog {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Tag enhancements */
.p-tag {
  border-radius: 20px !important;
  font-weight: 600 !important;
  letter-spacing: 0.025em !important;
}

/* DataTable header styling */
.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #14b8a6 0%, #06b6d4 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}

/* Enhanced card shadows */
.tw-shadow-lg {
  box-shadow: 0 20px 25px -5px rgba(20, 184, 166, 0.1), 0 10px 10px -5px rgba(20, 184, 166, 0.04) !important;
}

.tw-shadow-xl {
  box-shadow: 0 25px 50px -12px rgba(20, 184, 166, 0.25) !important;
}

/* Line clamp utility */
.tw-line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-flex {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
