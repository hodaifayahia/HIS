<template>
  <div class="tw-space-y-6">
    <!-- Action Buttons -->
    <div class="tw-flex tw-gap-3 tw-mb-6">
      <Button
        label="Import from Excel"
        icon="pi pi-upload"
        class="tw-bg-blue-600 tw-rounded-lg tw-px-6 tw-py-3"
        @click="showImportDialog = true"
      />
      <Button
        label="Download Template"
        icon="pi pi-download"
        severity="secondary"
        class="tw-rounded-lg tw-px-6 tw-py-3"
        @click="downloadTemplate"
      />
      <Button
        label="Export Current"
        icon="pi pi-file-excel"
        severity="success"
        class="tw-rounded-lg tw-px-6 tw-py-3"
        @click="exportToExcel"
      />
      <Button
        v-if="auditItems.length > 0"
        label="Generate PDF Report"
        icon="pi pi-file-pdf"
        severity="danger"
        class="tw-rounded-lg tw-px-6 tw-py-3"
        @click="generatePdfReport"
      />
    </div>

    <!-- Search and Filter -->
    <div class="tw-flex tw-gap-4 tw-items-center">
      <div class="tw-flex-1">
        <InputText
          v-model="searchQuery"
          placeholder="Search by product name..."
          class="tw-w-full tw-rounded-lg tw-border-2 tw-p-3"
          @input="filterProducts"
        />
      </div>
      <Dropdown
        v-model="selectedStockage"
        :options="stockages"
        optionLabel="name"
        optionValue="id"
        placeholder="All Stockages"
        class="tw-w-64 tw-rounded-lg"
        @change="loadProducts"
      />
    </div>

    <!-- Audit Items Table -->
    <DataTable
      :value="filteredAuditItems"
      v-model:selection="selectedItems"
      :loading="loading"
      paginator
      :rows="10"
      class="tw-rounded-xl tw-shadow-lg"
      responsiveLayout="scroll"
      :rowsPerPageOptions="[10, 25, 50]"
    >
      <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
      
      <Column field="product_name" header="Product Name" :sortable="true">
        <template #body="slotProps">
          <div class="tw-font-medium">{{ slotProps.data.product_name }}</div>
        </template>
        <template #editor="{ data }">
          <InputText v-model="data.product_name" class="tw-w-full tw-p-2" />
        </template>
      </Column>

      <Column field="stockage_name" header="Stockage" :sortable="true">
        <template #body="slotProps">
          <Tag :value="slotProps.data.stockage_name" severity="info" />
        </template>
      </Column>

      <Column field="theoretical_quantity" header="Theoretical Qty" :sortable="true">
        <template #body="slotProps">
          <span class="tw-font-semibold tw-text-blue-600">
            {{ formatNumber(slotProps.data.theoretical_quantity) }}
          </span>
        </template>
      </Column>

      <Column field="actual_quantity" header="Actual Qty (Physical Count)" :sortable="true">
        <template #body="slotProps">
          <InputNumber
            v-model="slotProps.data.actual_quantity"
            :min="0"
            :minFractionDigits="0"
            :maxFractionDigits="2"
            class="tw-w-32"
            @update:modelValue="calculateDifference(slotProps.data)"
          />
        </template>
      </Column>

      <Column field="difference" header="Difference" :sortable="true">
        <template #body="slotProps">
          <Tag
            :value="formatDifference(slotProps.data.difference || 0)"
            :severity="getDifferenceSeverity(slotProps.data.difference || 0)"
            class="tw-font-bold"
          />
        </template>
      </Column>

      <Column field="variance_percent" header="Variance %" :sortable="true">
        <template #body="slotProps">
          <span :class="getVarianceClass(slotProps.data.variance_percent || 0)">
            {{ formatPercent(slotProps.data.variance_percent || 0) }}
          </span>
        </template>
      </Column>

      <Column header="Notes">
        <template #body="slotProps">
          <InputText
            v-model="slotProps.data.notes"
            placeholder="Add notes..."
            class="tw-w-full tw-p-2"
          />
        </template>
      </Column>
    </DataTable>

    <!-- Summary Section -->
    <div v-if="auditItems.length > 0" class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6 tw-mt-6">
      <h3 class="tw-text-xl tw-font-bold tw-text-gray-800 tw-mb-4">Audit Summary</h3>
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
        <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow">
          <div class="tw-text-sm tw-text-gray-600">Total Items</div>
          <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ auditItems.length }}</div>
        </div>
        <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow">
          <div class="tw-text-sm tw-text-gray-600">Items with Differences</div>
          <div class="tw-text-2xl tw-font-bold tw-text-orange-600">{{ itemsWithDifferences }}</div>
        </div>
        <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow">
          <div class="tw-text-sm tw-text-gray-600">Total Shortage</div>
          <div class="tw-text-2xl tw-font-bold tw-text-red-600">{{ formatNumber(totalShortage) }}</div>
        </div>
        <div class="tw-bg-white tw-rounded-lg tw-p-4 tw-shadow">
          <div class="tw-text-sm tw-text-gray-600">Total Overage</div>
          <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ formatNumber(totalOverage) }}</div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="tw-flex tw-justify-end tw-gap-4 tw-mt-6 tw-pt-6 tw-border-t">
      <Button
        label="Cancel"
        severity="secondary"
        class="tw-rounded-lg tw-px-6 tw-py-3"
        @click="$emit('close')"
        :disabled="isSubmitting"
      />
      <Button
        label="Load Products for Audit"
        icon="pi pi-download"
        severity="info"
        class="tw-rounded-lg tw-px-6 tw-py-3"
        @click="loadProducts"
        :loading="loading"
        v-if="auditItems.length === 0"
      />
      <Button
        label="Save Audit & Update Inventory"
        icon="pi pi-check"
        class="tw-bg-green-600 tw-rounded-lg tw-px-6 tw-py-3"
        @click="saveAudit"
        :loading="isSubmitting"
        :disabled="isSubmitting || auditItems.length === 0"
      />
    </div>

    <!-- Import Dialog -->
    <Dialog
      v-model:visible="showImportDialog"
      modal
      header="Import Audit Data from Excel"
      :style="{ width: '600px' }"
    >
      <div class="tw-space-y-4">
        <p class="tw-text-gray-600">Upload an Excel file with product audit data. The file should match the template format.</p>
        
        <div class="tw-border-2 tw-border-dashed tw-border-gray-300 tw-rounded-lg tw-p-8 tw-text-center">
          <input
            type="file"
            ref="fileInput"
            accept=".xlsx,.xls"
            @change="handleFileUpload"
            class="tw-hidden"
          />
          <i class="pi pi-cloud-upload tw-text-6xl tw-text-gray-400 tw-mb-4"></i>
          <p class="tw-text-gray-600 tw-mb-4">{{ selectedFile ? selectedFile.name : 'Click to select file or drag and drop' }}</p>
          <Button
            label="Choose File"
            icon="pi pi-folder-open"
            class="tw-rounded-lg"
            @click="$refs.fileInput.click()"
          />
        </div>

        <div v-if="importProgress" class="tw-space-y-2">
          <div class="tw-flex tw-justify-between tw-text-sm">
            <span>Importing...</span>
            <span>{{ importProgress.current }} / {{ importProgress.total }}</span>
          </div>
          <ProgressBar :value="(importProgress.current / importProgress.total) * 100" />
        </div>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          severity="secondary"
          @click="showImportDialog = false"
          :disabled="isImporting"
        />
        <Button
          label="Import"
          icon="pi pi-upload"
          @click="processImport"
          :loading="isImporting"
          :disabled="!selectedFile || isImporting"
        />
      </template>
    </Dialog>
  </div>
</template>

<script>
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import ProgressBar from 'primevue/progressbar';

export default {
  name: 'InventoryAuditProductDialog',
  components: {
    DataTable,
    Column,
    InputText,
    InputNumber,
    Button,
    Dropdown,
    Tag,
    Dialog,
    ProgressBar
  },
  emits: ['close', 'audit-completed'],
  setup() {
    const toast = useToast();
    return { toast };
  },
  data() {
    return {
      auditItems: [],
      filteredAuditItems: [],
      selectedItems: [],
      stockages: [],
      selectedStockage: null,
      searchQuery: '',
      loading: false,
      isSubmitting: false,
      isImporting: false,
      showImportDialog: false,
      selectedFile: null,
      importProgress: null
    };
  },
  computed: {
    itemsWithDifferences() {
      return this.auditItems.filter(item => {
        const diff = parseFloat(item.difference) || 0;
        return Math.abs(diff) > 0.001; // Use small threshold to handle floating point precision
      }).length;
    },
    totalShortage() {
      return this.auditItems
        .filter(item => {
          const diff = parseFloat(item.difference) || 0;
          return diff < 0;
        })
        .reduce((sum, item) => sum + Math.abs(parseFloat(item.difference) || 0), 0);
    },
    totalOverage() {
      return this.auditItems
        .filter(item => {
          const diff = parseFloat(item.difference) || 0;
          return diff > 0;
        })
        .reduce((sum, item) => sum + (parseFloat(item.difference) || 0), 0);
    }
  },
  async mounted() {
    await this.loadStockages();
  },
  methods: {
    async loadStockages() {
      try {
        const response = await axios.get('/api/stockages');
        this.stockages = [
          { id: null, name: 'All Stockages' },
          ...response.data
        ];
      } catch (error) {
        console.error('Error loading stockages:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load stockages',
          life: 3000
        });
      }
    },
    async loadProducts() {
      this.loading = true;
      try {
        const params = {};
        if (this.selectedStockage) {
          params.stockage_id = this.selectedStockage;
        }

        const response = await axios.get('/api/inventory-audit/products', { params });
        
        this.auditItems = response.data.map(item => ({
          product_id: item.product_id,
          product_name: item.product_name,
          stockage_id: item.stockage_id,
          stockage_name: item.stockage_name,
          theoretical_quantity: parseFloat(item.theoretical_quantity) || 0,
          actual_quantity: null,
          difference: 0,
          variance_percent: 0,
          notes: ''
        }));

        this.filteredAuditItems = [...this.auditItems];

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Loaded ${this.auditItems.length} products for audit`,
          life: 3000
        });
      } catch (error) {
        console.error('Error loading products:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to load products for audit',
          life: 3000
        });
      } finally {
        this.loading = false;
      }
    },
    calculateDifference(item) {
      // Ensure actual_quantity is treated as a number
      const actualQty = parseFloat(item.actual_quantity);
      const theoreticalQty = parseFloat(item.theoretical_quantity) || 0;
      
      if (!isNaN(actualQty) && actualQty !== null && actualQty !== undefined) {
        // Calculate difference
        item.difference = actualQty - theoreticalQty;
        
        // Calculate variance percentage
        if (theoreticalQty > 0) {
          item.variance_percent = (item.difference / theoreticalQty) * 100;
        } else {
          item.variance_percent = actualQty > 0 ? 100 : 0;
        }
      } else {
        item.difference = 0;
        item.variance_percent = 0;
      }
      
      // Force reactivity update by updating the filtered list
      this.filterProducts();
    },
    filterProducts() {
      if (!this.searchQuery) {
        this.filteredAuditItems = [...this.auditItems];
        return;
      }

      const query = this.searchQuery.toLowerCase();
      this.filteredAuditItems = this.auditItems.filter(item =>
        item.product_name.toLowerCase().includes(query) ||
        item.stockage_name.toLowerCase().includes(query)
      );
    },
    async saveAudit() {
      // Validate that at least some items have actual quantities
      const itemsWithActualQty = this.auditItems.filter(item => 
        item.actual_quantity !== null && 
        item.actual_quantity !== undefined && 
        item.actual_quantity !== ''
      );

      if (itemsWithActualQty.length === 0) {
        this.toast.add({
          severity: 'warn',
          summary: 'Warning',
          detail: 'Please enter at least one actual quantity',
          life: 3000
        });
        return;
      }

      // Recalculate differences for all items before saving
      itemsWithActualQty.forEach(item => {
        const actualQty = parseFloat(item.actual_quantity);
        const theoreticalQty = parseFloat(item.theoretical_quantity) || 0;
        
        item.difference = actualQty - theoreticalQty;
        
        if (theoreticalQty > 0) {
          item.variance_percent = (item.difference / theoreticalQty) * 100;
        } else {
          item.variance_percent = actualQty > 0 ? 100 : 0;
        }
      });

      this.isSubmitting = true;
      try {
        const response = await axios.post('/api/inventory-audit/save', {
          audit_items: itemsWithActualQty
        });

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Audit saved successfully. ${itemsWithActualQty.length} items updated.`,
          life: 5000
        });

        this.$emit('audit-completed', response.data);
        this.$emit('close');
      } catch (error) {
        console.error('Error saving audit:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to save audit',
          life: 5000
        });
      } finally {
        this.isSubmitting = false;
      }
    },
    async downloadTemplate() {
      try {
        const response = await axios.get('/api/inventory-audit/template', {
          responseType: 'blob'
        });

        const blob = new Blob([response.data], { 
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        });
        
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `inventory_audit_template_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Template downloaded successfully',
          life: 3000
        });
      } catch (error) {
        console.error('Error downloading template:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to download template',
          life: 3000
        });
      }
    },
    async exportToExcel() {
      try {
        const params = {};
        if (this.selectedStockage) {
          params.stockage_id = this.selectedStockage;
        }

        const response = await axios.get('/api/inventory-audit/export', {
          params,
          responseType: 'blob'
        });

        const blob = new Blob([response.data], { 
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        });
        
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `inventory_audit_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'Audit data exported successfully',
          life: 3000
        });
      } catch (error) {
        console.error('Error exporting:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to export audit data',
          life: 3000
        });
      }
    },
    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
    },
    async processImport() {
      if (!this.selectedFile) return;

      this.isImporting = true;
      const formData = new FormData();
      formData.append('file', this.selectedFile);

      try {
        const response = await axios.post('/api/inventory-audit/import', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (progressEvent) => {
            this.importProgress = {
              current: progressEvent.loaded,
              total: progressEvent.total
            };
          }
        });

        // Update audit items with imported data
        response.data.items.forEach(importedItem => {
          const existingItem = this.auditItems.find(
            item => item.product_id === importedItem.product_id && 
                   item.stockage_id === importedItem.stockage_id
          );

          if (existingItem) {
            existingItem.actual_quantity = importedItem.actual_quantity;
            existingItem.notes = importedItem.notes || existingItem.notes;
            this.calculateDifference(existingItem);
          }
        });

        this.filterProducts();
        this.showImportDialog = false;
        this.selectedFile = null;
        this.importProgress = null;

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `Imported ${response.data.items.length} items successfully`,
          life: 3000
        });
      } catch (error) {
        console.error('Error importing:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to import audit data',
          life: 3000
        });
      } finally {
        this.isImporting = false;
      }
    },
    async generatePdfReport() {
      try {
        const itemsWithActualQty = this.auditItems.filter(item => 
          item.actual_quantity !== null && item.actual_quantity !== undefined
        );

        if (itemsWithActualQty.length === 0) {
          this.toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Please enter actual quantities before generating report',
            life: 3000
          });
          return;
        }

        const response = await axios.post('/api/inventory-audit/report', {
          audit_items: itemsWithActualQty
        }, {
          responseType: 'blob'
        });

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `inventory_audit_report_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        this.toast.add({
          severity: 'success',
          summary: 'Success',
          detail: 'PDF report generated successfully',
          life: 3000
        });
      } catch (error) {
        console.error('Error generating PDF:', error);
        this.toast.add({
          severity: 'error',
          summary: 'Error',
          detail: 'Failed to generate PDF report',
          life: 3000
        });
      }
    },
    formatNumber(num) {
      const value = parseFloat(num) || 0;
      return new Intl.NumberFormat().format(value);
    },
    formatDifference(diff) {
      const value = parseFloat(diff) || 0;
      const formatted = this.formatNumber(Math.abs(value));
      if (value > 0.001) return `+${formatted}`;
      if (value < -0.001) return `-${formatted}`;
      return '0';
    },
    formatPercent(percent) {
      const value = parseFloat(percent) || 0;
      return `${value.toFixed(1)}%`;
    },
    getDifferenceSeverity(diff) {
      const value = parseFloat(diff) || 0;
      if (Math.abs(value) < 0.001) return 'success';
      if (value > 0) return 'info';
      return 'danger';
    },
    getVarianceClass(percent) {
      const value = parseFloat(percent) || 0;
      const abs = Math.abs(value);
      if (abs < 0.1) return 'tw-text-green-600 tw-font-semibold';
      if (abs < 5) return 'tw-text-yellow-600 tw-font-semibold';
      if (abs < 10) return 'tw-text-orange-600 tw-font-semibold';
      return 'tw-text-red-600 tw-font-bold';
    }
  }
};
</script>
