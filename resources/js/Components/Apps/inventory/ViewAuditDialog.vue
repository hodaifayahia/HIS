<template>
  <Dialog
    :visible="visible"
    modal
    :header="audit ? audit.name : 'Audit Details'"
    :style="{ width: '70rem' }"
    :closable="true"
    class="tw-rounded-2xl"
    @update:visible="$emit('close')"
  >
    <div v-if="audit" class="tw-p-6">
      <!-- Status and Actions -->
      <div class="tw-flex tw-justify-between tw-items-start tw-mb-6 tw-pb-6 tw-border-b tw-border-gray-200">
        <div>
          <Tag 
            :value="audit.status" 
            :severity="getStatusSeverity(audit.status)"
            :icon="getStatusIcon(audit.status)"
            class="tw-px-4 tw-py-2 tw-text-lg"
          />
        </div>
        <div class="tw-flex tw-gap-3">
          <Button
            v-if="audit.status === 'draft'"
            label="Start Audit"
            icon="pi pi-play"
            class="tw-bg-blue-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2"
            @click="$emit('start', audit)"
          />
          <Button
            v-if="audit.status === 'in_progress'"
            label="Complete"
            icon="pi pi-check"
            class="tw-bg-green-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2"
            @click="$emit('complete', audit)"
          />
          <Button
            label="Edit"
            icon="pi pi-pencil"
            class="tw-bg-orange-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2"
            @click="$emit('edit', audit)"
          />
          <Button
            label="Delete"
            icon="pi pi-trash"
            class="tw-bg-red-500 tw-text-white tw-border-0 tw-rounded-lg tw-px-4 tw-py-2"
            @click="$emit('delete', audit)"
          />
        </div>
      </div>

      <!-- Audit Details Grid -->
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-6 tw-mb-6">
        <!-- Description -->
        <div class="md:tw-col-span-2">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-align-left tw-mr-2"></i>Description
          </label>
          <p class="tw-text-gray-600 tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            {{ audit.description || 'No description provided' }}
          </p>
        </div>

        <!-- Creator -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-user tw-mr-2"></i>Created By
          </label>
          <div v-if="audit.creator" class="tw-flex tw-items-center tw-gap-3 tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            <div class="tw-w-12 tw-h-12 tw-rounded-full tw-bg-teal-100 tw-flex tw-items-center tw-justify-center tw-text-teal-600 tw-font-bold tw-text-lg">
              {{ getInitials(audit.creator.name) }}
            </div>
            <div>
              <div class="tw-font-semibold tw-text-gray-800">{{ audit.creator.name }}</div>
              <div class="tw-text-sm tw-text-gray-500">{{ audit.creator.email }}</div>
            </div>
          </div>
        </div>

        <!-- Audit Type -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-database tw-mr-2"></i>Audit Type
          </label>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            <Tag 
              v-if="audit.is_global" 
              value="Global Audit" 
              severity="info" 
              icon="pi pi-globe"
              class="tw-px-3 tw-py-2"
            />
            <Tag 
              v-else-if="audit.is_pharmacy_wide" 
              value="Pharmacy" 
              severity="success" 
              icon="pi pi-heart"
              class="tw-px-3 tw-py-2"
            />
            <Tag 
              v-else 
              value="General Stock" 
              severity="warning" 
              icon="pi pi-box"
              class="tw-px-3 tw-py-2"
            />
          </div>
        </div>

        <!-- Service -->
        <div v-if="audit.service">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-building tw-mr-2"></i>Service
          </label>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-text-gray-700">
            {{ audit.service.name }}
          </div>
        </div>

        <!-- Stockage -->
        <div v-if="audit.stockage">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-warehouse tw-mr-2"></i>Stockage
          </label>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg">
            <div class="tw-font-semibold tw-text-gray-800">{{ audit.stockage.name }}</div>
            <div class="tw-text-sm tw-text-gray-500">{{ audit.stockage.location }}</div>
          </div>
        </div>

        <!-- Scheduled Date -->
        <div>
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-calendar tw-mr-2"></i>Scheduled Date
          </label>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-text-gray-700">
            {{ formatDateTime(audit.scheduled_at) }}
          </div>
        </div>

        <!-- Started At -->
        <div v-if="audit.started_at">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-clock tw-mr-2"></i>Started At
          </label>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-text-gray-700">
            {{ formatDateTime(audit.started_at) }}
          </div>
        </div>

        <!-- Completed At -->
        <div v-if="audit.completed_at">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
            <i class="pi pi-check-circle tw-mr-2"></i>Completed At
          </label>
          <div class="tw-bg-gray-50 tw-p-4 tw-rounded-lg tw-text-gray-700">
            {{ formatDateTime(audit.completed_at) }}
          </div>
        </div>
      </div>

      <!-- Participants Section -->
      <div class="tw-mt-6">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
          <h3 class="tw-text-xl tw-font-bold tw-text-gray-800">
            <i class="pi pi-users tw-mr-2 tw-text-teal-500"></i>
            Participants ({{ audit.participants ? audit.participants.length : 0 }})
          </h3>
        </div>

        <!-- Participants DataTable -->
        <div class="tw-bg-white tw-rounded-xl tw-shadow-md tw-overflow-hidden">
          <DataTable
            :value="audit.participants || []"
            responsiveLayout="scroll"
            class="tw-w-full"
            :paginator="audit.participants && audit.participants.length > 10"
            :rows="10"
          >
            <template #empty>
              <div class="tw-text-center tw-py-8 tw-text-gray-500">
                <i class="pi pi-users tw-text-6xl tw-mb-3 tw-block tw-text-gray-300"></i>
                <p class="tw-text-lg tw-font-semibold tw-mb-1">No participants assigned</p>
                <p class="tw-text-sm">Add participants to this audit</p>
              </div>
            </template>

            <!-- Photo Column -->
            <Column header="Photo" style="width: 100px">
              <template #body="slotProps">
                <div class="tw-flex tw-justify-center">
                  <div class="tw-w-12 tw-h-12 tw-rounded-full tw-bg-gradient-to-br tw-from-teal-400 tw-to-cyan-500 tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-lg tw-shadow-md">
                    {{ getInitials(slotProps.data.user.name) }}
                  </div>
                </div>
              </template>
            </Column>

            <!-- Name Column -->
            <Column field="user.name" header="Name" style="min-width: 200px">
              <template #body="slotProps">
                <div class="tw-font-semibold tw-text-gray-800">
                  {{ slotProps.data.user.name }}
                </div>
              </template>
            </Column>

            <!-- Email Column -->
            <Column field="user.email" header="Email" style="min-width: 250px">
              <template #body="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2 tw-text-gray-600">
                  <i class="pi pi-envelope tw-text-teal-500"></i>
                  <span>{{ slotProps.data.user.email }}</span>
                </div>
              </template>
            </Column>

            <!-- Role Column -->
            <Column header="Role" style="width: 200px">
              <template #body="slotProps">
                <div class="tw-flex tw-gap-2">
                  <Tag
                    v-if="slotProps.data.is_participant"
                    value="Participant"
                    severity="success"
                    icon="pi pi-check"
                    class="tw-px-3 tw-py-1"
                  />
                  <Tag
                    v-if="slotProps.data.is_able_to_see"
                    value="Viewer"
                    severity="info"
                    icon="pi pi-eye"
                    class="tw-px-3 tw-py-1"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>

      <!-- Timeline -->
      <div class="tw-mt-6 tw-pt-6 tw-border-t tw-border-gray-200">
        <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4">
          <i class="pi pi-history tw-mr-2 tw-text-teal-500"></i>
          Timeline
        </h3>
        <div class="tw-space-y-3">
          <div class="tw-flex tw-items-start tw-gap-3">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-blue-100 tw-flex tw-items-center tw-justify-center tw-text-blue-600 tw-flex-shrink-0">
              <i class="pi pi-plus"></i>
            </div>
            <div>
              <div class="tw-font-semibold tw-text-gray-800">Created</div>
              <div class="tw-text-sm tw-text-gray-500">{{ formatDateTime(audit.created_at) }}</div>
            </div>
          </div>
          <div v-if="audit.started_at" class="tw-flex tw-items-start tw-gap-3">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-yellow-100 tw-flex tw-items-center tw-justify-center tw-text-yellow-600 tw-flex-shrink-0">
              <i class="pi pi-play"></i>
            </div>
            <div>
              <div class="tw-font-semibold tw-text-gray-800">Started</div>
              <div class="tw-text-sm tw-text-gray-500">{{ formatDateTime(audit.started_at) }}</div>
            </div>
          </div>
          <div v-if="audit.completed_at" class="tw-flex tw-items-start tw-gap-3">
            <div class="tw-w-8 tw-h-8 tw-rounded-full tw-bg-green-100 tw-flex tw-items-center tw-justify-center tw-text-green-600 tw-flex-shrink-0">
              <i class="pi pi-check"></i>
            </div>
            <div>
              <div class="tw-font-semibold tw-text-gray-800">Completed</div>
              <div class="tw-text-sm tw-text-gray-500">{{ formatDateTime(audit.completed_at) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <Button
        label="Close"
        icon="pi pi-times"
        class="tw-bg-gray-100 tw-text-gray-700 tw-border-0 tw-rounded-xl tw-px-6 tw-py-3"
        @click="$emit('close')"
      />
    </template>
  </Dialog>
</template>

<script>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

export default {
  name: 'ViewAuditDialog',
  components: {
    Dialog,
    Button,
    Tag,
    DataTable,
    Column
  },
  props: {
    visible: {
      type: Boolean,
      required: true
    },
    audit: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'edit', 'delete', 'start', 'complete'],
  methods: {
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

/* DataTable styling to match the main design */
.p-datatable .p-datatable-thead > tr > th {
  background: linear-gradient(135deg, #14b8a6 0%, #06b6d4 100%) !important;
  color: white !important;
  font-weight: 600 !important;
  border: none !important;
  padding: 1rem !important;
}

.p-datatable .p-datatable-tbody > tr {
  transition: all 0.2s ease;
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: #f0fdfa !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.1);
}

/* Tag styling */
.p-tag {
  border-radius: 20px !important;
  font-weight: 600 !important;
  letter-spacing: 0.025em !important;
}
</style>
