<template>
  <DataTable
    :value="movements"
    :loading="loading"
    paginator
    :rows="10"
    :rowsPerPageOptions="[5, 10, 25, 50]"
    tableStyle="min-width: 50rem"
    filterDisplay="row"
    :globalFilterFields="['movement_number', 'providing_service.name', 'requesting_service.name', 'requesting_user.name', 'status']"
    removableSort
    dataKey="id"
    responsiveLayout="scroll"
    :breakpoint="'960px'"
    showGridlines
    stripedRows
    size="small"
    :scrollable="true"
    scrollHeight="600px"
    :resizableColumns="true"
    columnResizeMode="fit"
    :reorderableColumns="true"
    :exportFilename="'stock-requests'"
    :metaKeySelection="false"
    :rowHover="true"
    :autoLayout="true"
    :lazy="false"
    :sortMode="'multiple'"
    :multiSortMeta="[]"
    :defaultSortOrder="1"
    editMode="row"
    :editingRows="[]"
    :rowClass="() => ''"
    :rowStyle="() => ({})"
    :cellClass="() => ''"
    :cellStyle="() => ({})"
    :first="0"
    :totalRecords="movements.length"
    :alwaysShowPaginator="true"
    paginatorPosition="both"
    :paginatorTemplate="'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown'"
    :currentPageReportTemplate="'Showing {first} to {last} of {totalRecords} entries'"
    :rowsPerPageTemplate="'{value}'"
    :pageLinkSize="5"
    :stateStorage="'local'"
    :stateKey="'dt-state-demo-local'"
    :contextMenu="false"
    :contextMenuSelection="null"
    :expandedRows="[]"
    :expandedRowIcon="'pi pi-chevron-down'"
    :collapsedRowIcon="'pi pi-chevron-right'"
    :rowGroupMode="null"
    :groupRowsBy="null"
    :expandableRowGroups="false"
    :rowGroupHeaderTemplate="null"
    :rowGroupFooterTemplate="null"
    :frozenColumns="[]"
    :frozenValue="[]"
    :csvSeparator="','"
    :exportFunction="null"
    :filterLocale="undefined"
    :selection="null"
    :selectionMode="null"
    :compareSelectionBy="'deepEquals'"
    :selectAll="null"
    :selectOnFocus="true"
    :dataKey="'id'"
    :tableProps="null"
    :filterInputProps="null"
  >
    <template #header>
      <div class="tw-flex tw-justify-between tw-items-center">
        <h3 class="tw-text-xl tw-font-semibold">My Requests</h3>
        <span class="p-input-icon-left">
          <i class="pi pi-search" />
          <InputText v-model="filters['global'].value" placeholder="Search requests..." />
        </span>
      </div>
    </template>

    <Column field="movement_number" header="Request #" sortable style="min-width:12rem">
      <template #body="slotProps">
        <span class="tw-font-mono tw-text-sm">{{ slotProps.data.movement_number }}</span>
      </template>
    </Column>

    <Column header="Products">
      <template #body="slotProps">
        <div class="tw-space-y-1">
          <div v-for="item in slotProps.data.items" :key="item.id" class="tw-flex tw-justify-between tw-text-sm">
            <span>{{ item.product?.name || 'Product not loaded' }}</span>
            <span class="tw-font-medium">{{ item.requested_quantity }}</span>
          </div>
          <div v-if="slotProps.data.items.length === 0" class="tw-text-gray-500 tw-text-sm">
            No products added
          </div>
        </div>
      </template>
    </Column>

    <Column header="Requesting Service" style="min-width:15rem">
      <template #body="slotProps">
        <div class="tw-flex tw-items-center tw-space-x-3">
          <div class="tw-w-8 tw-h-8 tw-bg-green-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
            <i class="pi pi-building tw-text-green-600"></i>
          </div>
          <div>
            <p class="tw-font-medium">{{ slotProps.data.requesting_service?.name }}</p>
          </div>
        </div>
      </template>
    </Column>

    <Column header="Providing Service" style="min-width:15rem">
      <template #body="slotProps">
        <div class="tw-flex tw-items-center tw-space-x-3">
          <div class="tw-w-8 tw-h-8 tw-bg-blue-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
            <i class="pi pi-building tw-text-blue-600"></i>
          </div>
          <div>
            <p class="tw-font-medium">{{ slotProps.data?.providing_service?.name }}</p>
          </div>
        </div>
      </template>
    </Column>

    <Column header="Requested By" style="min-width:10rem">
      <template #body="slotProps">
        <div class="tw-flex tw-items-center tw-space-x-3">
          <div class="tw-w-8 tw-h-8 tw-bg-purple-100 tw-rounded-full tw-flex tw-items-center tw-justify-center">
            <i class="pi pi-user tw-text-purple-600"></i>
          </div>
          <div>
            <p class="tw-font-medium">{{ slotProps.data?.requesting_user?.name }}</p>
            <p class="tw-text-sm tw-text-gray-500">{{ slotProps.data?.requesting_user?.email }}</p>
          </div>
        </div>
      </template>
    </Column>

    <Column field="status" header="Status" sortable>
      <template #body="slotProps">
        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
      </template>
    </Column>

    <Column field="updated_at" header="Last Updated" sortable style="min-width:12rem">
      <template #body="slotProps">
        <div>
          <p class="tw-font-medium">{{ formatDate(slotProps.data.updated_at) }}</p>
          <p class="tw-text-sm tw-text-gray-500">{{ formatTime(slotProps.data.updated_at) }}</p>
        </div>
      </template>
    </Column>

    <Column header="Actions" style="min-width:15rem">
      <template #body="slotProps">
        <div class="tw-flex tw-space-x-2">
          <Button
            v-if="slotProps.data.status === 'draft'"
            icon="pi pi-pencil"
            class="p-button-rounded p-button-warning p-button-text"
            @click="$emit('edit-draft', slotProps.data)"
            v-tooltip="'Edit Draft'"
          />

          <Button
            v-if="slotProps.data.status === 'draft' && slotProps.data.items.length > 0"
            icon="pi pi-send"
            class="p-button-rounded p-button-success p-button-text"
            @click="$emit('send-draft', slotProps.data)"
            v-tooltip="'Send Request'"
          />

          <Button
            v-if="slotProps.data.status === 'draft'"
            icon="pi pi-trash"
            class="p-button-rounded p-button-danger p-button-text"
            @click="$emit('delete-draft', slotProps.data)"
            v-tooltip="'Delete Draft'"
          />

          <Button
            v-if="slotProps.data.status === 'approved'"
            icon="pi pi-check-circle"
            class="p-button-rounded p-button-success p-button-text"
            v-tooltip="'Approved'"
            disabled
          />
          
          <Button
            icon="pi pi-eye"
            class="p-button-rounded p-button-info p-button-text"
            @click="$emit('view-movement', slotProps.data)"
            v-tooltip="'View Details'"
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<script>
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

export default {
  name: 'StockRequestsTable',
  components: {
    InputText,
    DataTable,
    Column,
    Tag,
    Button
  },
  props: {
    movements: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    filters: {
      type: Object,
      required: true
    }
  },
  emits: ['edit-draft', 'send-draft', 'delete-draft', 'view-movement'],
  methods: {
    getStatusSeverity(status) {
      const severities = {
        'draft': 'secondary',
        'pending': 'warning',
        'approved': 'success',
        'partially_approved': 'info',
        'rejected': 'danger',
        'executed': 'success',
        'partially_executed': 'info',
        'cancelled': 'secondary',
        'in_transfer': 'info'
      };
      return severities[status] || 'info';
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    formatTime(date) {
      return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.p-datatable .p-datatable-tbody > tr:hover {
  background-color: rgba(59, 130, 246, 0.05);
}

.p-button {
  transition: all 0.2s ease-in-out;
}

.p-button:hover {
  transform: translateY(-1px);
}

.p-tag {
  font-weight: 500;
  text-transform: capitalize;
}
</style>