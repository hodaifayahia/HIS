<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Create Consignment Reception"
        :style="{ width: '50rem' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <form @submit.prevent="handleSubmit" class="tw-space-y-4">
            <!-- Supplier Selection -->
            <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Supplier <span class="tw-text-red-500">*</span></label>
                <Dropdown
                    v-model="formData.fournisseur_id"
                    :options="suppliers"
                    optionLabel="company_name"
                    optionValue="id"
                    placeholder="Select Supplier"
                    class="tw-w-full"
                    :class="{ 'p-invalid': errors.fournisseur_id }"
                    filter
                />
                <small v-if="errors.fournisseur_id" class="tw-text-red-500">{{ errors.fournisseur_id }}</small>
            </div>

            <!-- Reception Date -->
            <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Reception Date <span class="tw-text-red-500">*</span></label>
                <Calendar
                    v-model="formData.reception_date"
                    dateFormat="yy-mm-dd"
                    placeholder="Select Date"
                    class="tw-w-full"
                    :class="{ 'p-invalid': errors.reception_date }"
                />
                <small v-if="errors.reception_date" class="tw-text-red-500">{{ errors.reception_date }}</small>
            </div>

            <!-- Origin Note -->
            <div>
                <label class="tw-block tw-text-sm tw-font-medium tw-mb-2">Origin Note</label>
                <Textarea
                    v-model="formData.origin_note"
                    rows="3"
                    placeholder="Optional note about the consignment origin"
                    class="tw-w-full"
                />
            </div>

            <!-- Items -->
            <div>
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-2">
                    <label class="tw-block tw-text-sm tw-font-medium">Items <span class="tw-text-red-500">*</span></label>
                    <Button
                        type="button"
                        label="Add Item"
                        icon="pi pi-plus"
                        size="small"
                        @click="addItem"
                    />
                </div>

                <div v-if="formData.items.length === 0" class="tw-text-center tw-py-8 tw-text-gray-500 tw-border tw-border-dashed tw-rounded">
                    <i class="pi pi-inbox tw-text-4xl tw-mb-2"></i>
                    <p>No items added. Click "Add Item" to start.</p>
                </div>

                <div v-for="(item, index) in formData.items" :key="index" class="tw-border tw-p-3 tw-rounded tw-mb-2 tw-bg-gray-50">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-flex-1">
                            <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2">
                                <Badge 
                                    :value="item.product_type === 'pharmacy' ? 'PHARMACY' : 'STOCK'" 
                                    :severity="item.product_type === 'pharmacy' ? 'danger' : 'info'"
                                    class="tw-text-xs"
                                />
                                <span class="tw-font-semibold tw-text-gray-800">{{ item.product_name }}</span>
                                <Tag :value="item.product_code" severity="secondary" class="tw-text-xs" />
                            </div>
                            <div class="tw-grid tw-grid-cols-3 tw-gap-2">
                                <div>
                                    <label class="tw-block tw-text-xs tw-text-gray-600 tw-mb-1">Quantity</label>
                                    <InputNumber
                                        v-model="item.quantity_received"
                                        :min="1"
                                        class="tw-w-full"
                                        placeholder="Qty"
                                    />
                                </div>
                                <div>
                                    <label class="tw-block tw-text-xs tw-text-gray-600 tw-mb-1">Unit</label>
                                    <InputText
                                        :value="item.unit"
                                        readonly
                                        class="tw-w-full tw-bg-gray-100"
                                    />
                                </div>
                                <div>
                                    <label class="tw-block tw-text-xs tw-text-gray-600 tw-mb-1">Unit Price</label>
                                    <InputNumber
                                        v-model="item.unit_price"
                                        :min="0"
                                        :minFractionDigits="2"
                                        :maxFractionDigits="2"
                                        class="tw-w-full"
                                        placeholder="Price"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="tw-flex tw-flex-col tw-gap-1">
                            <Button
                                type="button"
                                icon="pi pi-pencil"
                                severity="info"
                                size="small"
                                outlined
                                @click="editItem(index)"
                            />
                            <Button
                                type="button"
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                outlined
                                @click="removeItem(index)"
                            />
                        </div>
                    </div>
                </div>

                <small v-if="errors.items" class="tw-text-red-500">{{ errors.items }}</small>
            </div>
        </form>

        <!-- Product Selection Dialog -->
        <ProductSelectionDialog
            v-model:visible="showProductDialog"
            :show-both-types="true"
            default-tab="all"
            @product-selected="handleProductSelected"
        />

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="closeDialog" />
            <Button label="Create Consignment" :loading="submitting" @click="handleSubmit" />
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import ProductSelectionDialog from '@/Components/Purchasing/ProductSelectionDialog.vue';
import consignmentService from '@/services/Purchasing/consignmentService';
import supplierService from '@/services/Purchasing/supplierService';
import productService from '@/services/Product/productService';

const props = defineProps({
    visible: Boolean
});

const emit = defineEmits(['update:visible', 'created']);

const visible = ref(props.visible);
const suppliers = ref([]);
const products = ref([]);
const submitting = ref(false);
const errors = ref({});
const showProductDialog = ref(false);
const currentItemIndex = ref(null);

const formData = ref({
    fournisseur_id: null,
    reception_date: new Date(),
    origin_note: '',
    items: []
});

watch(() => props.visible, (newVal) => {
    visible.value = newVal;
    if (newVal) {
        resetForm();
        loadSuppliers();
        loadProducts();
    }
});

watch(visible, (newVal) => {
    emit('update:visible', newVal);
});

const loadSuppliers = async () => {
    try {
        const response = await supplierService.getAll();
        suppliers.value = response.data;
    } catch (error) {
        console.error('Failed to load suppliers:', error);
    }
};

const loadProducts = async () => {
    try {
        const response = await productService.getAll();
        products.value = response.data;
    } catch (error) {
        console.error('Failed to load products:', error);
    }
};

const addItem = () => {
    currentItemIndex.value = null;
    showProductDialog.value = true;
};

const editItem = (index) => {
    currentItemIndex.value = index;
    showProductDialog.value = true;
};

const removeItem = (index) => {
    formData.value.items.splice(index, 1);
};

const handleProductSelected = (data) => {
    const isPharmacy = data.product.type === 'pharmacy';
    
    const newItem = {
        product_id: !isPharmacy ? data.product.id : null,
        pharmacy_product_id: isPharmacy ? data.product.id : null,
        product_name: isPharmacy 
            ? (data.product.name || data.product.generic_name || data.product.brand_name)
            : (data.product.name || data.product.designation),
        product_code: isPharmacy 
            ? data.product.sku 
            : (data.product.code_interne || data.product.code),
        product_type: data.product.type,
        quantity_received: data.quantity,
        unit: data.unit,
        unit_price: 0
    };
    
    if (currentItemIndex.value !== null) {
        // Edit existing item
        formData.value.items[currentItemIndex.value] = newItem;
    } else {
        // Add new item
        formData.value.items.push(newItem);
    }
    
    showProductDialog.value = false;
    currentItemIndex.value = null;
};

const validate = () => {
    errors.value = {};
    
    if (!formData.value.fournisseur_id) {
        errors.value.fournisseur_id = 'Supplier is required';
    }
    
    if (!formData.value.reception_date) {
        errors.value.reception_date = 'Reception date is required';
    }
    
    if (formData.value.items.length === 0) {
        errors.value.items = 'At least one item is required';
    } else {
        const invalidItems = formData.value.items.some(
            item => !item.product_id || !item.quantity_received || item.unit_price < 0
        );
        if (invalidItems) {
            errors.value.items = 'All items must have product, quantity, and valid price';
        }
    }
    
    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validate()) return;
    
    submitting.value = true;
    try {
        const response = await consignmentService.create(formData.value);
        emit('created', response.data);
        closeDialog();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
    } finally {
        submitting.value = false;
    }
};

const resetForm = () => {
    formData.value = {
        fournisseur_id: null,
        reception_date: new Date(),
        origin_note: '',
        items: []
    };
    errors.value = {};
};

const closeDialog = () => {
    visible.value = false;
};
</script>
