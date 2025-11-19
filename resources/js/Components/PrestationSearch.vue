<script setup>
import { ref, onMounted, watch } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import { useToastr } from '@/Components/toster';

// PrimeVue Components
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import OverlayPanel from 'primevue/overlaypanel';
import Tag from 'primevue/tag';

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
        op.value.hide();
    }
    emit('update:modelValue', '');
    emit('prestationSelected', null);
};

const handleSearch = debounce(async (event) => {
    const query = event.target.value;
    searchQuery.value = query;
    emit('update:modelValue', query);

    if (!query || query.length < 1) {
        prestations.value = [];
        if (op.value) {
            op.value.hide();
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
                op.value.show(event, searchInputRef.value.$el || event.target);
            }
        } else {
            if (op.value && searchInputRef.value) {
                op.value.show(event, searchInputRef.value.$el || event.target);
            }
        }

    } catch (error) {
        console.error('Error searching prestations:', error);
        toastr.error('Failed to search prestations');
        prestations.value = [];
        if (op.value) {
            op.value.hide();
        }
    } finally {
        isLoading.value = false;
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
    selectedPrestation.value = prestation;
    emit('prestationSelected', prestation);
    emit('update:modelValue', prestation.name);
    searchQuery.value = prestation.name;
    if (op.value) {
        op.value.hide();
    }
};

const onInputFocus = (event) => {
    if (searchQuery.value && prestations.value.length > 0 || (searchQuery.value && searchQuery.value.length >= 1 && prestations.value.length === 0 && !isLoading.value)) {
        if (op.value && searchInputRef.value) {
            op.value.show(event, searchInputRef.value.$el || event.target);
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
</script>

<template>
    <div class="tw-w-full">
        <!-- Search Input Section -->
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
                class="p-button-secondary p-button-text"
                @click="resetSearch"
            />
        </div>

        <!-- Search Results Overlay Panel -->
        <OverlayPanel ref="op" :showCloseIcon="false" class="prestation-overlay">
            <!-- Loading State -->
            <div v-if="isLoading" class="tw-flex tw-items-center tw-justify-center py-6">
                <ProgressSpinner 
                    style="width: 30px; height: 30px" 
                    strokeWidth="6" 
                    animationDuration=".8s" 
                />
                <span class="tw-ml-3 tw-text-gray-600">Searching...</span>
            </div>
            
            <!-- Results Template -->
            <template v-else>
                <!-- Prestation Results List -->
                <div v-if="prestations.length > 0" class="tw-w-full">
                    <div class="tw-font-semibold tw-text-gray-700 tw-mb-3 tw-px-2">
                        Search Results ({{ prestations.length }})
                    </div>
                    <div class="tw-max-h-96 tw-overflow-y-auto">
                        <div 
                            v-for="prestation in prestations" 
                            :key="prestation.id" 
                            class="tw-prestation-item tw-flex tw-items-center tw-justify-between tw-py-3 tw-px-4 tw-mb-2 tw-rounded-lg tw-bg-white hover:tw-bg-blue-50 tw-cursor-pointer tw-transition-colors tw-duration-200 tw-border tw-border-gray-200 hover:tw-border-blue-300 tw-shadow-sm hover:tw-shadow-md"
                            @click="selectPrestation(prestation)"
                        >
                                <div class="tw-flex tw-flex-col tw-flex-1">
                                <span class="tw-text-base tw-font-semibold tw-text-gray-800">
                                    {{ prestation.name }}
                                </span>
                                <div class="tw-flex tw-flex-wrap tw-gap-2 tw-mt-1">
                                    <Tag 
                                        icon="pi pi-tag" 
                                        :value="prestation.code" 
                                        severity="info" 
                                        class="tw-text-xs"
                                    />
                                    <Tag 
                                        icon="pi pi-dollar" 
                                        :value="formatPrice(prestation.price)" 
                                        severity="success" 
                                        class="tw-text-xs"
                                    />
                                    <Tag 
                                        v-if="prestation.service"
                                        icon="pi pi-home" 
                                        :value="prestation.service.name" 
                                        severity="secondary" 
                                        class="tw-text-xs"
                                    />
                                </div>
                            </div>
                            <i class="pi pi-chevron-right tw-text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- No Results State -->
                <div v-else class="tw-flex tw-flex-col tw-items-center tw-py-8">
                    <i class="pi pi-search tw-text-5xl tw-text-gray-300 tw-mb-4"></i>
                    <div class="tw-text-gray-500 tw-text-lg">No prestations found</div>
                    <div class="tw-text-gray-400 tw-text-sm tw-mt-1">
                        Try searching with different keywords
                    </div>
                </div>
            </template>
        </OverlayPanel>
    </div>
</template>

<style scoped>
/* Custom overlay panel width and positioning */
:deep(.prestation-overlay) {
    width: 250px !important;
    min-width: 250px !important;
    max-width:250px !important;
    margin-top: 4px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
    border-radius: 8px !important;
    border: 1px solid #e5e7eb !important;
}

:deep(.p-overlaypanel-content) {
    padding: 0.75rem !important;
}

/* PrimeVue button customization */
:deep(.p-inputtext) {
    width: 100% !important;
}

/* Prestation item hover effect */
.tw-prestation-item:hover {
    transform: translateX(2px);
}

/* Scrollbar styling for results list */
.tw-max-h-96::-webkit-scrollbar {
    width: 6px;
}

.tw-max-h-96::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.tw-max-h-96::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.tw-max-h-96::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
