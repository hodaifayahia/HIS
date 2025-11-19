<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="`Consignment Details - ${consignment?.consignment_code || ''}`"
        :style="{ width: '70rem' }"
        :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
    >
        <div v-if="loading" class="tw-text-center tw-py-8">
            <ProgressSpinner />
        </div>

        <div v-else-if="consignment" class="tw-space-y-4">
            <!-- Header Info -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-p-4 tw-bg-gray-50 tw-rounded">
                <div>
                    <label class="tw-block tw-text-xs tw-text-gray-500">Supplier</label>
                    <div class="tw-font-semibold">{{ consignment.fournisseur?.nom_fournisseur }}</div>
                </div>
                <div>
                    <label class="tw-block tw-text-xs tw-text-gray-500">Reception Date</label>
                    <div class="tw-font-semibold">{{ formatDate(consignment.reception_date) }}</div>
                </div>
                <div>
                    <label class="tw-block tw-text-xs tw-text-gray-500">Status</label>
                    <Tag :value="consignment.confirmed_at ? 'Confirmed' : 'Pending'" :severity="consignment.confirmed_at ? 'success' : 'warning'" />
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-4">
                <div class="tw-border tw-rounded tw-p-4 tw-text-center">
                    <div class="tw-text-2xl tw-font-bold tw-text-blue-600">{{ consignment.total_received }}</div>
                    <div class="tw-text-sm tw-text-gray-500">Total Received</div>
                </div>
                <div class="tw-border tw-rounded tw-p-4 tw-text-center">
                    <div class="tw-text-2xl tw-font-bold tw-text-orange-600">{{ consignment.total_consumed }}</div>
                    <div class="tw-text-sm tw-text-gray-500">Total Consumed</div>
                </div>
                <div class="tw-border tw-rounded tw-p-4 tw-text-center">
                    <div class="tw-text-2xl tw-font-bold tw-text-red-600">{{ consignment.total_uninvoiced }}</div>
                    <div class="tw-text-sm tw-text-gray-500">Uninvoiced</div>
                </div>
                <div class="tw-border tw-rounded tw-p-4 tw-text-center">
                    <div class="tw-text-2xl tw-font-bold tw-text-green-600">{{ invoicedCount }}</div>
                    <div class="tw-text-sm tw-text-gray-500">Invoiced</div>
                </div>
            </div>

            <!-- Tabs -->
            <TabView>
                <TabPanel header="Items">
                    <DataTable :value="consignment.items" stripedRows class="p-datatable-sm">
                        <Column field="product.name" header="Product" />
                        <Column field="quantity_received" header="Received" />
                        <Column field="quantity_consumed" header="Consumed">
                            <template #body="{ data }">
                                <span :class="data.quantity_consumed > 0 ? 'tw-text-orange-600 tw-font-semibold' : ''">
                                    {{ data.quantity_consumed }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Invoiced">
                            <template #body="{ data }">
                                <span :class="data.quantity_invoiced > 0 ? 'tw-text-green-600 tw-font-semibold' : ''">
                                    {{ data.quantity_invoiced }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Uninvoiced">
                            <template #body="{ data }">
                                <span :class="data.quantity_uninvoiced > 0 ? 'tw-text-red-600 tw-font-semibold' : ''">
                                    {{ data.quantity_uninvoiced }}
                                </span>
                            </template>
                        </Column>
                        <Column field="unit_price" header="Unit Price">
                            <template #body="{ data }">
                                {{ formatCurrency(data.unit_price) }}
                            </template>
                        </Column>
                        <Column header="Uninvoiced Value">
                            <template #body="{ data }">
                                <span class="tw-font-semibold tw-text-red-600">
                                    {{ formatCurrency(data.uninvoiced_value) }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Progress">
                            <template #body="{ data }">
                                <ProgressBar 
                                    :value="getPercentage(data.quantity_consumed, data.quantity_received)"
                                    :showValue="false"
                                    style="height: 6px"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="Consumption History">
                    <DataTable :value="consumptionHistory" :loading="loadingHistory" stripedRows class="p-datatable-sm">
                        <Column field="product_name" header="Product" />
                        <Column field="patient_name" header="Patient" />
                        <Column field="fiche_navette_id" header="Fiche ID" />
                        <Column field="quantity" header="Quantity" />
                        <Column field="payment_status" header="Payment Status">
                            <template #body="{ data }">
                                <Tag :value="data.payment_status" :severity="getPaymentSeverity(data.payment_status)" />
                            </template>
                        </Column>
                        <Column field="consumed_at" header="Date">
                            <template #body="{ data }">
                                {{ formatDateTime(data.consumed_at) }}
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>

                <TabPanel header="Invoices">
                    <DataTable :value="consignment.invoices" stripedRows class="p-datatable-sm">
                        <Column field="id" header="Invoice #" />
                        <Column field="total_amount" header="Amount">
                            <template #body="{ data }">
                                {{ formatCurrency(data.total_amount) }}
                            </template>
                        </Column>
                        <Column field="status" header="Status">
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getInvoiceSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column field="created_at" header="Created">
                            <template #body="{ data }">
                                {{ formatDate(data.created_at) }}
                            </template>
                        </Column>
                    </DataTable>
                </TabPanel>
            </TabView>
        </div>

        <template #footer>
            <div class="tw-flex tw-justify-between tw-w-full">
                <div>
                    <Button
                        v-if="consignment?.total_uninvoiced > 0"
                        label="Create Invoice"
                        icon="pi pi-file-invoice"
                        severity="warning"
                        @click="showInvoiceDialog = true"
                    />
                </div>
                <div>
                    <Button label="Close" severity="secondary" @click="closeDialog" />
                </div>
            </div>
        </template>

        <!-- Invoice Creation Dialog -->
        <Dialog v-model:visible="showInvoiceDialog" modal header="Create Invoice" :style="{ width: '30rem' }">
            <div class="tw-space-y-4">
                <p>Create invoice for <strong>{{ consignment?.total_uninvoiced }}</strong> uninvoiced items?</p>
                
                <div>
                    <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Notes</label>
                    <Textarea v-model="invoiceNotes" rows="3" class="tw-w-full" placeholder="Invoice notes (optional)" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showInvoiceDialog = false" />
                <Button label="Create Invoice" :loading="creatingInvoice" @click="createInvoice" />
            </template>
        </Dialog>
    </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import ProgressSpinner from 'primevue/progressspinner';
import Textarea from 'primevue/textarea';
import consignmentService from '@/services/Purchasing/consignmentService';

const props = defineProps({
    visible: Boolean,
    consignmentId: Number
});

const emit = defineEmits(['update:visible', 'invoice-created']);

const visible = ref(props.visible);
const consignment = ref(null);
const consumptionHistory = ref([]);
const loading = ref(false);
const loadingHistory = ref(false);
const showInvoiceDialog = ref(false);
const invoiceNotes = ref('');
const creatingInvoice = ref(false);

const invoicedCount = computed(() => {
    if (!consignment.value) return 0;
    return consignment.value.total_consumed - consignment.value.total_uninvoiced;
});

watch(() => props.visible, (newVal) => {
    visible.value = newVal;
    if (newVal && props.consignmentId) {
        loadConsignment();
        loadConsumptionHistory();
    }
});

watch(visible, (newVal) => {
    emit('update:visible', newVal);
});

const loadConsignment = async () => {
    loading.value = true;
    try {
        const response = await consignmentService.getById(props.consignmentId);
        consignment.value = response.data;
    } catch (error) {
        console.error('Failed to load consignment:', error);
    } finally {
        loading.value = false;
    }
};

const loadConsumptionHistory = async () => {
    loadingHistory.value = true;
    try {
        const response = await consignmentService.getConsumptionHistory(props.consignmentId);
        consumptionHistory.value = response.data;
    } catch (error) {
        console.error('Failed to load consumption history:', error);
    } finally {
        loadingHistory.value = false;
    }
};

const createInvoice = async () => {
    creatingInvoice.value = true;
    try {
        await consignmentService.createInvoice(props.consignmentId, {
            notes: invoiceNotes.value
        });
        showInvoiceDialog.value = false;
        invoiceNotes.value = '';
        emit('invoice-created');
        loadConsignment();
    } catch (error) {
        console.error('Failed to create invoice:', error);
    } finally {
        creatingInvoice.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR');
};

const formatCurrency = (value) => {
    if (!value) return '0.00';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD' }).format(value);
};

const getPercentage = (value, total) => {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
};

const getPaymentSeverity = (status) => {
    const severityMap = {
        'paid': 'success',
        'partial': 'warning',
        'pending': 'danger'
    };
    return severityMap[status] || 'info';
};

const getInvoiceSeverity = (status) => {
    const severityMap = {
        'paid': 'success',
        'approved': 'success',
        'pending': 'warning',
        'cancelled': 'danger'
    };
    return severityMap[status] || 'info';
};

const closeDialog = () => {
    visible.value = false;
};
</script>
