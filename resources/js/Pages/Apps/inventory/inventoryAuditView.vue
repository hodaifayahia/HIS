<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-teal-50 tw-via-white tw-to-cyan-50 tw-p-6">
    <!-- Success Toast -->
    <Toast position="top-right" />

    <!-- Confirm Dialog -->
    <ConfirmDialog />

    <!-- Header Section -->
    <div class="tw-mb-6">
      <div class="tw-bg-gradient-to-r tw-from-teal-600 tw-to-cyan-700 tw-rounded-xl tw-shadow-lg tw-p-6 tw-text-white">
        <div class="tw-flex tw-justify-between tw-items-start tw-gap-4">
          <div class="tw-flex-1">
            <div class="tw-flex tw-items-center tw-gap-3 tw-mb-2">
              <Button
                icon="pi pi-arrow-left"
                class="tw-bg-white/20 tw-border-0 tw-rounded-lg tw-p-2 hover:tw-bg-white/30 tw-transition-all"
                @click="goBack"
              />
              <div class="tw-flex-1">
                <h1 class="tw-text-2xl tw-font-bold tw-mb-1">
                  <i class="pi pi-check-square tw-mr-2"></i>
                  {{ audit?.name || 'Audit Details' }}
                </h1>
                <p class="tw-text-teal-100 tw-text-sm">
                  {{ audit?.description || 'No description provided' }}
                </p>
              </div>
            </div>
          </div>
          <div class="tw-flex tw-gap-2 tw-flex-wrap tw-justify-end">
            <Tag 
              v-if="audit"
              :value="audit.status" 
              :severity="getStatusSeverity(audit.status)"
              :icon="getStatusIcon(audit.status)"
              class="tw-px-3 tw-py-2"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
      <i class="pi pi-spin pi-spinner tw-text-6xl tw-text-teal-500 tw-mb-4"></i>
      <p class="tw-text-gray-500 tw-text-lg">Loading audit details...</p>
    </div>

    <!-- Content -->
    <div v-else-if="audit" class="tw-space-y-6">
      <!-- Quick Actions -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
        <div class="tw-flex tw-flex-wrap tw-gap-2">
          <Button
            v-if="audit.status === 'in_progress' || audit.status === 'completed'"
            label="View Products"
            icon="pi pi-box"
            class="tw-bg-teal-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="goToProducts"
          />
          <Button
            v-if="audit.status === 'draft'"
            label="Start Audit"
            icon="pi pi-play"
            class="tw-bg-blue-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="startAudit"
          />
          <Button
            v-if="audit.status === 'in_progress'"
            label="Complete Audit"
            icon="pi pi-check"
            class="tw-bg-green-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="completeAudit"
          />
          <Button
            v-if="canShowReconciliation"
            label="Reconcile Discrepancies"
            icon="pi pi-check-circle"
            class="tw-bg-purple-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="showReconciliationDialog"
          />
          <Button
            label="Edit Audit"
            icon="pi pi-pencil"
            class="tw-bg-orange-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="editAudit"
          />
          <Button
            label="Delete Audit"
            icon="pi pi-trash"
            class="tw-bg-red-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2 tw-text-sm tw-font-semibold hover:tw-shadow-lg tw-transition-all"
            @click="confirmDelete"
          />
        </div>
      </div>

      <!-- Audit Details Cards -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-4">
        <!-- Audit Type Card -->
        <div class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-teal-400 tw-to-cyan-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-database tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Audit Type</h3>
          </div>
          <div class="tw-space-y-1">
            <Tag 
              v-if="audit.is_global" 
              value="Global Audit" 
              severity="info" 
              icon="pi pi-globe"
              class="tw-px-2 tw-py-1 tw-text-xs tw-w-full tw-justify-center"
            />
            <Tag 
              v-else-if="audit.is_pharmacy_wide" 
              value="Pharmacy" 
              severity="success" 
              icon="pi pi-heart"
              class="tw-px-2 tw-py-1 tw-text-xs tw-w-full tw-justify-center"
            />
            <Tag 
              v-else 
              value="General Stock" 
              severity="warning" 
              icon="pi pi-box"
              class="tw-px-2 tw-py-1 tw-text-xs tw-w-full tw-justify-center"
            />
            <div v-if="audit.is_global" class="tw-text-xs tw-text-gray-400 tw-text-center tw-mt-1">
              All products & stockages
            </div>
          </div>
        </div>

        <!-- Service Card -->
        <div v-if="audit.service" class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-blue-400 tw-to-indigo-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-building tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Service</h3>
          </div>
          <div class="tw-text-base tw-font-semibold tw-text-gray-700 tw-truncate" :title="audit.service.name">
            {{ audit.service.name }}
          </div>
        </div>

        <!-- Stockage Card -->
        <div v-if="audit.stockage" class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-purple-400 tw-to-pink-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-warehouse tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Stockage</h3>
          </div>
          <div class="tw-text-base tw-font-semibold tw-text-gray-700 tw-truncate" :title="audit.stockage.name">
            {{ audit.stockage.name }}
          </div>
          <div v-if="audit.stockage.location" class="tw-text-xs tw-text-gray-400 tw-mt-1 tw-truncate" :title="audit.stockage.location">
            {{ audit.stockage.location }}
          </div>
        </div>
        <div v-else-if="audit.service && !audit.is_global" class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-purple-400 tw-to-pink-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-warehouse tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Stockage</h3>
          </div>
          <div class="tw-text-sm tw-text-gray-400 tw-italic">
            All stockages in service
          </div>
        </div>

        <!-- Creator Card -->
        <div v-if="audit.creator" class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-green-400 tw-to-emerald-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-user tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Created By</h3>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-teal-100 tw-flex tw-items-center tw-justify-center tw-text-teal-600 tw-font-bold tw-text-xs">
              {{ getInitials(audit.creator.name) }}
            </div>
            <div class="tw-flex-1 tw-min-w-0">
              <div class="tw-font-semibold tw-text-gray-800 tw-text-sm tw-truncate" :title="audit.creator.name">{{ audit.creator.name }}</div>
              <div class="tw-text-xs tw-text-gray-400 tw-truncate" :title="audit.creator.email">{{ audit.creator.email }}</div>
            </div>
          </div>
        </div>

        <!-- Scheduled Date Card -->
        <div class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-orange-400 tw-to-red-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-calendar tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Scheduled</h3>
          </div>
          <div class="tw-text-sm tw-text-gray-700">
            {{ formatDateTime(audit.scheduled_at) }}
          </div>
        </div>

        <!-- Started At Card -->
        <div v-if="audit.started_at" class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-yellow-400 tw-to-orange-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-clock tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Started At</h3>
          </div>
          <div class="tw-text-sm tw-text-gray-700">
            {{ formatDateTime(audit.started_at) }}
          </div>
        </div>

        <!-- Completed At Card -->
        <div v-if="audit.completed_at" class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
            <div class="tw-bg-gradient-to-br tw-from-green-400 tw-to-teal-500 tw-w-8 tw-h-8 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-text-white">
              <i class="pi pi-check-circle tw-text-sm"></i>
            </div>
            <h3 class="tw-text-sm tw-font-semibold tw-text-gray-500">Completed At</h3>
          </div>
          <div class="tw-text-sm tw-text-gray-700">
            {{ formatDateTime(audit.completed_at) }}
          </div>
        </div>
      </div>

      <!-- Timeline Section -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow tw-p-4">
        <h2 class="tw-text-base tw-font-bold tw-text-gray-800 tw-mb-3 tw-flex tw-items-center">
          <i class="pi pi-history tw-mr-2 tw-text-teal-500"></i>
          Timeline
        </h2>
        <div class="tw-space-y-2">
          <div class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-blue-50 tw-rounded-lg">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-blue-100 tw-flex tw-items-center tw-justify-center tw-text-blue-600 tw-flex-shrink-0">
              <i class="pi pi-plus tw-text-sm"></i>
            </div>
            <div class="tw-flex-1">
              <div class="tw-font-semibold tw-text-gray-800 tw-text-sm">Created</div>
              <div class="tw-text-xs tw-text-gray-500">{{ formatDateTime(audit.created_at) }}</div>
            </div>
          </div>
          <div v-if="audit.started_at" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-yellow-50 tw-rounded-lg">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-yellow-100 tw-flex tw-items-center tw-justify-center tw-text-yellow-600 tw-flex-shrink-0">
              <i class="pi pi-play tw-text-sm"></i>
            </div>
            <div class="tw-flex-1">
              <div class="tw-font-semibold tw-text-gray-800 tw-text-sm">Started</div>
              <div class="tw-text-xs tw-text-gray-500">{{ formatDateTime(audit.started_at) }}</div>
            </div>
          </div>
          <div v-if="audit.completed_at" class="tw-flex tw-items-center tw-gap-3 tw-p-3 tw-bg-green-50 tw-rounded-lg">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-green-100 tw-flex tw-items-center tw-justify-center tw-text-green-600 tw-flex-shrink-0">
              <i class="pi pi-check tw-text-sm"></i>
            </div>
            <div class="tw-flex-1">
              <div class="tw-font-semibold tw-text-gray-800 tw-text-sm">Completed</div>
              <div class="tw-text-xs tw-text-gray-500">{{ formatDateTime(audit.completed_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Participants Section -->
      <div class="tw-bg-white tw-rounded-xl tw-shadow tw-overflow-hidden">
        <div class="tw-p-4 tw-border-b tw-border-gray-200">
          <div class="tw-flex tw-items-center tw-justify-between">
            <h2 class="tw-text-base tw-font-bold tw-text-gray-800 tw-flex tw-items-center">
              <i class="pi pi-users tw-mr-2 tw-text-teal-500"></i>
              Participants ({{ audit.participants ? audit.participants.length : 0 }})
            </h2>
          </div>
        </div>

        <!-- Participants DataTable -->
        <DataTable
          :value="audit.participants || []"
          :paginator="audit.participants && audit.participants.length > 10"
          :rows="10"
          responsiveLayout="scroll"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          :rowsPerPageOptions="[10, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} participants"
          class="tw-w-full"
          @row-click="handleParticipantClick"
          :rowClass="() => 'tw-cursor-pointer'"
        >
          <template #empty>
            <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-16">
              <i class="pi pi-users tw-text-8xl tw-text-gray-300 tw-mb-4"></i>
              <p class="tw-text-gray-500 tw-text-xl tw-font-semibold tw-mb-2">No participants assigned</p>
              <p class="tw-text-gray-400">Add participants to this audit</p>
            </div>
          </template>

          <!-- Photo Column -->
          <Column header="Photo" style="width: 80px">
            <template #body="slotProps">
              <div class="tw-flex tw-justify-center">
                <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-gradient-to-br tw-from-teal-400 tw-to-cyan-500 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-xs">
                  {{ getInitials(slotProps.data.user.name) }}
                </div>
              </div>
            </template>
          </Column>

          <!-- Name Column -->
          <Column field="user.name" header="Name" style="min-width: 200px">
            <template #body="slotProps">
              <div class="tw-font-semibold tw-text-gray-800 tw-text-sm">
                {{ slotProps.data.user.name }}
              </div>
            </template>
          </Column>
          <!-- Name Column -->
          <Column field="user.status" header="Status" style="min-width: 200px">
            <template #body="slotProps">
              <div class="tw-font-semibold tw-text-gray-800 tw-text-sm">
                {{ slotProps.data.user.status }}
              </div>
            </template>
          </Column>

          <!-- Email Column -->
          <Column field="user.email" header="Email" style="min-width: 250px">
            <template #body="slotProps">
              <div class="tw-flex tw-items-center tw-gap-2 tw-text-gray-600 tw-text-sm">
                <i class="pi pi-envelope tw-text-teal-500 tw-text-xs"></i>
                <span>{{ slotProps.data.user.email }}</span>
              </div>
            </template>
          </Column>

          <!-- Role Column -->
          <Column header="Role" style="min-width: 180px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-1 tw-flex-wrap">
                <Tag
                  v-if="slotProps.data.is_participant"
                  value="Participant"
                  severity="success"
                  icon="pi pi-check"
                  class="tw-px-2 tw-py-1 tw-text-xs"
                />
                <Tag
                  v-if="slotProps.data.is_able_to_see"
                  value="Viewer"
                  severity="info"
                  icon="pi pi-eye"
                  class="tw-px-2 tw-py-1 tw-text-xs"
                />
                <Tag
                  v-if="slotProps.data.is_in_recount_mode"
                  :value="`Recount (${slotProps.data.recount_products_count})`"
                  severity="warning"
                  icon="pi pi-refresh"
                  class="tw-px-2 tw-py-1 tw-text-xs"
                />
              </div>
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" style="width: 150px">
            <template #body="slotProps">
              <div class="tw-flex tw-gap-2">
                <Button
                  v-if="slotProps.data.is_in_recount_mode"
                  icon="pi pi-times"
                  label="Remove Recount"
                  @click.stop="confirmRemoveRecount(slotProps.data)"
                  class="p-button-sm p-button-danger"
                  severity="danger"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- Edit Dialog -->
    <CreateEditAuditDialog
      v-if="showEditDialog"
      :visible="showEditDialog"
      :audit="editingAudit"
      :users="users"
      @close="closeEditDialog"
      @save="handleSave"
    />
 <!-- Reconciliation Dialog -->
<ReconciliationDialog
  v-if="showReconciliation"
  :visible="showReconciliation"
  :data="reconciliationData"
  :participants="users"
  @close="closeReconciliationDialog"
  @assign-recount="handleAssignRecount"
  @finalize="finalizeReconciliation"
/>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

// Custom Components
import CreateEditAuditDialog from '../../../Components/Apps/inventory/CreateEditAuditDialog.vue';
import ReconciliationDialog from '../../../Components/Apps/inventory/ReconciliationDialog.vue';

// Composables
const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirm = useConfirm();

// Reactive state
const audit = ref(null);
const users = ref([]);
const editingAudit = ref(null);
const showEditDialog = ref(false);
const showReconciliation = ref(false);
const reconciliationData = ref(null);
const loading = ref(false);

// Computed - Can show reconciliation button
const canShowReconciliation = computed(() => {
  if (!audit.value || audit.value.status === 'completed') return false;
  
  const participants = audit.value.participants || [];
  const sentCount = participants.filter(p => p.status === 'sent').length;
  
  return sentCount >= 2; // Need at least 2 participants who have sent their counts
});

// Lifecycle
onMounted(() => {
  fetchAuditDetails();
  fetchUsers();
});

// Methods
async function fetchAuditDetails() {
  loading.value = true;
  try {
    const auditId = route.params.id;
    const response = await axios.get(`/api/inventory-audits/${auditId}`);
    audit.value = response.data.data;
  } catch (error) {
    console.error('Failed to fetch audit details:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load audit details',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
}

async function fetchUsers() {
  try {
    const response = await axios.get('/api/users');
    users.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to fetch users:', error);
  }
}

function goBack() {
  router.push({ name: 'Inventory-audit.list' });
}

function goToProducts() {
  router.push({ name: 'Inventory-audit.products', params: { id: audit.value.id } });
}

function handleParticipantClick(event) {
  // Navigate to products page for this audit
  // Extract user_id from the participant data (could be event.data.user_id or event.data.user.id)
  const userId = event.data.user_id || event.data.user?.id;
  
  console.log('🔗 Navigating to products with participant:', userId, event.data);
  
  router.push({ 
    name: 'Inventory-audit.products', 
    params: { id: audit.value.id },
    query: { participant: userId }
  });
}

function editAudit() {
  editingAudit.value = { ...audit.value };
  showEditDialog.value = true;
}

function closeEditDialog() {
  showEditDialog.value = false;
  editingAudit.value = null;
}

async function handleSave(auditData) {
  try {
    await axios.put(`/api/inventory-audits/${audit.value.id}`, auditData);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Audit updated successfully',
      life: 3000
    });
    closeEditDialog();
    fetchAuditDetails();
  } catch (error) {
    console.error('Failed to save audit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save audit',
      life: 3000
    });
  }
}

function confirmDelete() {
  confirm.require({
    message: `Are you sure you want to delete "${audit.value.name}"?`,
    header: 'Delete Confirmation',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => deleteAudit()
  });
}

async function deleteAudit() {
  try {
    await axios.delete(`/api/inventory-audits/${audit.value.id}`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Audit deleted successfully',
      life: 3000
    });
    setTimeout(() => {
      goBack();
    }, 1000);
  } catch (error) {
    console.error('Failed to delete audit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete audit',
      life: 3000
    });
  }
}

async function startAudit() {
  try {
    await axios.post(`/api/inventory-audits/${audit.value.id}/start`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Audit started successfully',
      life: 3000
    });
    fetchAuditDetails();
  } catch (error) {
    console.error('Failed to start audit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to start audit',
      life: 3000
    });
  }
}

async function completeAudit() {
  try {
    await axios.post(`/api/inventory-audits/${audit.value.id}/complete`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Audit completed successfully',
      life: 3000
    });
    fetchAuditDetails();
  } catch (error) {
    console.error('Failed to complete audit:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to complete audit',
      life: 3000
    });
  }
}

function getStatusSeverity(status) {
  const severities = {
    draft: 'secondary',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'danger'
  };
  return severities[status] || 'secondary';
}

function getStatusIcon(status) {
  const icons = {
    draft: 'pi pi-file',
    in_progress: 'pi pi-spin pi-spinner',
    completed: 'pi pi-check-circle',
    cancelled: 'pi pi-times-circle'
  };
  return icons[status] || 'pi pi-info-circle';
}

function getInitials(name) {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function formatDateTime(dateString) {
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

async function showReconciliationDialog() {
  try {
    loading.value = true;
    const response = await axios.get(`/api/inventory-audits/${audit.value.id}/analyze-discrepancies`);
    reconciliationData.value = response.data;
    
    if (!reconciliationData.value.can_reconcile) {
      toast.add({
        severity: 'warn',
        summary: 'Cannot Reconcile',
        detail: reconciliationData.value.message,
        life: 5000
      });
      return;
    }
    
    showReconciliation.value = true;
  } catch (error) {
    console.error('Failed to analyze discrepancies:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to analyze discrepancies',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
}

function closeReconciliationDialog() {
  showReconciliation.value = false;
  reconciliationData.value = null;
}

async function handleAssignRecount(data) {
  console.log('handleAssignRecount called with:', data);
  
  try {
    // Use the new recount-specific endpoint
    const response = await axios.post(`/api/inventory-audits/${audit.value.id}/recount/assign-products`, data);
    
    console.log('Recount assignment response:', response.data);
    
    // Show appropriate message based on response
    const result = response.data.data;
    if (result.skipped_count > 0) {
      toast.add({
        severity: 'warn',
        summary: 'Partially Assigned',
        detail: result.message || `Assigned ${result.products_count} products. ${result.skipped_count} products skipped.`,
        life: 5000
      });
    } else {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Products assigned for recount successfully (${result.products_count} products)`,
        life: 3000
      });
    }
    
    // Refresh audit details after assignment
    await fetchAuditDetails();
  } catch (error) {
    console.error('Failed to assign recount:', error);
    console.error('Error details:', error.response?.data);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to assign recount',
      life: 3000
    });
  }
}

async function finalizeReconciliation() {
  confirm.require({
    message: 'Are you sure you want to finalize this audit? This will mark it as completed.',
    header: 'Finalize Audit',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-success',
    accept: async () => {
      try {
        await axios.post(`/api/inventory-audits/${audit.value.id}/finalize-reconciliation`);
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Audit finalized successfully',
          life: 3000
        });
        closeReconciliationDialog();
        fetchAuditDetails();
      } catch (error) {
        console.error('Failed to finalize:', error);
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to finalize audit',
          life: 3000
        });
      }
    }
  });
}

function confirmRemoveRecount(participant) {
  confirm.require({
    message: `Remove recount for ${participant.user.name}? This will restore their original counts and remove all recount data.`,
    header: 'Remove Recount',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => removeRecount(participant)
  });
}

async function removeRecount(participant) {
  try {
    const userId = participant.user_id || participant.user?.id;
    await axios.delete(`/api/inventory-audits/${audit.value.id}/recount/participants/${userId}`);
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Recount removed and original quantities restored',
      life: 3000
    });
    fetchAuditDetails();
  } catch (error) {
    console.error('Failed to remove recount:', error);
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to remove recount',
      life: 3000
    });
  }
}
</script>

<style scoped>
/* Custom styles for PrimeVue components */
.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f0fdfa !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.1);
}

/* Enhanced button styles */
.p-button {
  transition: all 0.3s ease !important;
}

.p-button:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 8px 25px rgba(20, 184, 166, 0.15) !important;
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

/* Participants table cursor */
.tw-cursor-pointer {
  cursor: pointer !important;
}

/* Responsive design */
@media (max-width: 768px) {
  .tw-flex {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>
