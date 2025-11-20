<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { useToastr } from '@/Components/toster';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import OverlayPanel from 'primevue/overlaypanel';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    modelValue: String,
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    placeholder: String,
    prestationId: Number,
    onSelectPrestation: Function,
    specializationFilter: Number  // NEW: Filter by specialization
});

const emit = defineEmits(['update:modelValue', 'prestationSelected']);

const toastr = useToastr();
const prestations = ref([]);
const isLoading = ref(false);
const selectedPrestation = ref(null);
const searchQuery = ref('');

const op = ref();
const searchInputRef = ref(null);

// Computed property to safely access prestations
const safePrestations = computed(() => {
    return Array.isArray(prestations.value) ? prestations.value : [];
});

watch(() => props.modelValue, (newValue) => {
    if (newValue && !searchQuery.value) {
        searchQuery.value = newValue;
    }
}, { immediate: true });

const resetSearch = () => {
    searchQuery.value = '';
    selectedPrestation.value = null;
    prestations.value = [];
    if (op.value) {
        try {
            op.value.hide();
        } catch (e) {
            // Silently handle if overlay is already hidden
        }
    }
    emit('update:modelValue', '');
    emit('prestationSelected', null);
};

const handleSearch = debounce(async (event) => {
    const query = event?.target?.value || '';
    searchQuery.value = query;
    emit('update:modelValue', query);

    if (!query || query.length < 2) {
        prestations.value = [];
        if (op.value) {
            try {
                op.value.hide();
            } catch (e) {
                // Silently handle if overlay is already hidden
            }
        }
        return;
    }

    try {
        isLoading.value = true;
        
        // Build params with specialization filter if provided
        const params = { search: query };
        if (props.specializationFilter) {
            params.specialization_id = props.specializationFilter;
        }
        
        const response = await axios.get('/api/prestation', {
            params
        });
        prestations.value = response.data.data || [];

        if (prestations.value.length > 0) {
            if (op.value && searchInputRef.value) {
                try {
                    op.value.show(event, searchInputRef.value.$el || event.target);
                } catch (e) {
                    // Silently handle if overlay cannot be shown
                }
            }
        } else {
            if (op.value && searchInputRef.value) {
                try {
                    op.value.show(event, searchInputRef.value.$el || event.target);
                } catch (e) {
                    // Silently handle if overlay cannot be shown
                }
            }
        }

    } catch (error) {
        console.error('Error searching prestations:', error);
        toastr.error('Failed to search prestations');
        prestations.value = [];
        if (op.value) {
            try {
                op.value.hide();
            } catch (e) {
                // Silently handle if overlay is already hidden
            }
        }
    } finally {
        isLoading.value = false
    }
}, 500);

watch(() => props.prestationId, async (newId) => {
    if (newId) {
        await fetchPrestationById(newId);
    }
}, { immediate: true });

const fetchPrestationById = async (id) => {
    try {
        const response = await axios.get(`/api/prestation/${id}`);
        if (response.data.data) {
            const prestation = response.data.data;
            selectedPrestation.value = prestation;
            searchQuery.value = prestation.name;
            emit('prestationSelected', prestation);
        } else {
            console.error('Prestation not found:', response.data.message);
        }
    } catch (error) {
        console.error('Error fetching prestation by ID:', error);
        toastr.error('Could not find prestation by ID');
    }
};

const selectPrestation = (prestation) => {
    const normalized = getNormalizedPrestation(prestation);
    selectedPrestation.value = normalized;
    emit('prestationSelected', normalized);
    emit('update:modelValue', getNormalizePrestationName(prestation));
    searchQuery.value = getNormalizePrestationName(prestation);
    if (op.value) {
        try {
            op.value.hide();
        } catch (e) {
            // Silently handle if overlay is already hidden
        }
    }
};

const onInputFocus = (event) => {
    if (searchQuery.value && (prestations.value.length > 0 || (searchQuery.value.length >= 2 && prestations.value.length === 0 && !isLoading.value))) {
        if (op.value && searchInputRef.value) {
            try {
                op.value.show(event, searchInputRef.value.$el || event.target);
            } catch (e) {
                // Silently handle if overlay cannot be shown
            }
        }
    }
};

const formatPrice = (price) => {
    if (!price) return 'N/A';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD',
    }).format(price);
};

// Normalize prestation data for display (handles both API response formats)
const getNormalizePrestationName = (prestation) => {
    return prestation.name || prestation.prestation_name || '';
};

const getNormalizedPrestationCode = (prestation) => {
    return prestation.code || prestation.prestation_code || '';
};

const getNormalizedPrestationPrice = (prestation) => {
    // Try to get price from different possible locations
    if (prestation.price_with_vat_and_consumables_variant) {
        if (typeof prestation.price_with_vat_and_consumables_variant === 'object') {
            return prestation.price_with_vat_and_consumables_variant.ttc || prestation.price_with_vat_and_consumables_variant.ttc_with_consumables_vat || 0;
        }
        return prestation.price_with_vat_and_consumables_variant;
    }
    if (prestation.prix) return prestation.prix;
    if (prestation.price) return prestation.price;
    if (prestation.standard_price) return prestation.standard_price;
    return 0;
};

const getNormalizedPrestation = (prestation) => {
    // If it has a nested prestation object (from convention API), merge it
    if (prestation.prestation) {
        return {
            ...prestation.prestation,
            ...prestation,
            id: prestation.prestation_id || prestation.prestation.id || prestation.id
        };
    }
    return prestation;
};

const getNormalizedService = (prestation) => {
    if (prestation.prestation && prestation.prestation.service) {
        return prestation.prestation.service;
    }
    if (prestation.service) {
        return prestation.service;
    }
    return null;
};
</script>

<template>
    <div class="tw-w-full">
        <div class="tw-flex tw-items-center tw-gap-2">
            <!-- Search Input Section -->
            <div class="tw-flex-1 tw-relative">
                <div class="tw-flex tw-items-center tw-gap-2">
                    <InputText
                        ref="searchInputRef"
                        v-model="searchQuery"
                        @input="handleSearch"
                        :placeholder="placeholder || 'Search prestations by name...'"
                        class="tw-w-full"
                        @focus="onInputFocus"
                        :disabled="disabled"
                        :readonly="readonly"
                    />
                    <Button
                        v-if="searchQuery"
                        icon="pi pi-times"
                        class="p-button-secondary p-button-text tw-ml-auto"
                        @click="resetSearch"
                    />
                </div>
            </div>
        </div>

        <!-- Search Results Overlay Panel -->
        <OverlayPanel ref="op" :showCloseIcon="false" class="prestation-overlay">
            <!-- Loading State -->
            <div v-if="isLoading" class="tw-flex tw-items-center tw-justify-center tw-py-6">
                <ProgressSpinner 
                    style="width: 30px; height: 30px" 
                    strokeWidth="6" 
                    animationDuration=".8s" 
                />
                <span class="tw-ml-3 tw-text-gray-600">Searching...</span>
            </div>
            
            <!-- Results Template -->
            <div v-else>
                <!-- Prestation Results Table -->
                <div v-if="safePrestations && safePrestations.length > 0" class="tw-w-full">
                    <div class="tw-font-semibold tw-text-gray-700 tw-mb-3 tw-px-2">
                        Search Results ({{ safePrestations.length }})
                    </div>
                    <DataTable 
                        :key="`prestations-${safePrestations.length}`"
                        :value="safePrestations" 
                        :scrollable="true" 
                        scrollHeight="300px"
                        class="p-datatable-sm tw-border tw-border-gray-200 tw-rounded-lg"
                        :paginator="safePrestations.length > 10"
                        :rows="10"
                        responsiveLayout="scroll"
                    >
                        <Column field="name" header="Name" style="min-width: 150px;">
                            <template #body="slotProps">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-briefcase tw-text-blue-500"></i>
                                    <span class="tw-font-medium tw-text-gray-800">
                                        {{ getNormalizePrestationName(slotProps.data) }}
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column field="code" header="Code" style="min-width: 100px;">
                            <template #body="slotProps">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-tag tw-text-indigo-500"></i>
                                    <span>{{ getNormalizedPrestationCode(slotProps.data) }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="price" header="Price" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-dollar tw-text-green-500"></i>
                                    <span>{{ formatPrice(getNormalizedPrestationPrice(slotProps.data)) }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="service" header="Service" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div v-if="getNormalizedService(slotProps.data)" class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-home tw-text-purple-500"></i>
                                    <span>{{ getNormalizedService(slotProps.data).name }}</span>
                                </div>
                                <span v-else class="tw-text-gray-400">N/A</span>
                            </template>
                        </Column>
                        <Column header="Actions" style="min-width: 100px;">
                            <template #body="slotProps">
                                <Button 
                                    label="Select" 
                                    icon="pi pi-check" 
                                    class="p-button-sm p-button-primary tw-rounded-full" 
                                    @click="selectPrestation(slotProps.data)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
                
                <!-- No Results State -->
                <div v-else class="tw-flex tw-flex-col tw-items-center tw-py-8">
                    <i class="pi pi-search tw-text-5xl tw-text-gray-300 tw-mb-4"></i>
                    <div class="tw-text-gray-500 tw-text-lg">No prestations found</div>
                    <div class="tw-text-gray-400 tw-text-sm tw-mt-1">
                        Try searching with different keywords
                    </div>
                </div>
            </div>
        </OverlayPanel>
    </div>
</template>

<style scoped>
/* Custom overlay panel width and positioning */
:deep(.prestation-overlay) {
    min-width: 700px !important;
    max-width: 900px !important;
    margin-top: 4px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    border-radius: 8px !important;
    border: 1px solid #e5e7eb !important;
}

:deep(.p-overlaypanel-content) {
    padding: 0.75rem !important;
}

/* PrimeVue button customization */
:deep(.p-button-sm) {
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
}

:deep(.p-inputtext) {
    width: 100% !important;
}

/* DataTable customization */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: #f8fafc !important;
    border-bottom: 2px solid #e2e8f0 !important;
    font-weight: 600 !important;
    color: #374151 !important;
    padding: 0.75rem !important;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    border-bottom: 1px solid #e5e7eb !important;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: #eff6ff !important;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem !important;
    vertical-align: middle !important;
}

:deep(.p-paginator) {
    padding: 0.5rem !important;
    border-top: 1px solid #e5e7eb !important;
}
</style>
