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
    excludePatientId: Number, // Exclude the main patient from companions
    onSelectCompanion: Function
});

const emit = defineEmits(['update:modelValue', 'companionSelected']);

const toastr = useToastr();
const companions = ref([]);
const isLoading = ref(false);
const searchQuery = ref('');
const selectedCompanion = ref(null);

const op = ref();
const searchInputRef = ref(null);

watch(() => props.modelValue, (newValue) => {
    if (newValue && !searchQuery.value) {
        searchQuery.value = newValue;
    }
}, { immediate: true });

const resetSearch = () => {
    searchQuery.value = '';
    selectedCompanion.value = null;
    companions.value = [];
    if (op.value) {
        op.value.hide();
    }
    emit('update:modelValue', '');
    emit('companionSelected', null);
};

const handleSearch = debounce(async (event) => {
    const query = event.target.value;
    searchQuery.value = query;
    emit('update:modelValue', query);

    if (!query || query.length < 2) {
        companions.value = [];
        if (op.value) {
            op.value.hide();
        }
        return;
    }

    try {
        isLoading.value = true;
        const response = await axios.get('/api/patients/search', {
            params: { query }
        });
        
        // Filter out the main patient if excludePatientId is provided
        let results = response.data.data || [];
        if (props.excludePatientId) {
            results = results.filter(p => p.id !== props.excludePatientId);
        }
        
        companions.value = results;

        const exactMatch = companions.value.find(p =>
            `${p.first_name} ${p.last_name} ${formatDateOfBirth(p.dateOfBirth)} ${p.phone}` === query
        );
        if (exactMatch) {
            selectCompanion(exactMatch);
            if (op.value) {
                op.value.hide();
            }
        } else if (companions.value.length > 0) {
            if (op.value && searchInputRef.value) {
                op.value.show(event, searchInputRef.value.$el || event.target);
            }
        } else {
            if (op.value && searchInputRef.value) {
                op.value.show(event, searchInputRef.value.$el || event.target);
            }
        }

    } catch (error) {
        console.error('Error searching companions:', error);
        toastr.error('Failed to search companions');
        companions.value = [];
        if (op.value) {
            op.value.hide();
        }
    } finally {
        isLoading.value = false;
    }
}, 500);

const formatDateOfBirth = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toISOString().split('T')[0].replace(/-/g, '/');
};

const selectCompanion = (companion) => {
    selectedCompanion.value = companion;
    emit('companionSelected', companion);
    const companionString = `${companion.first_name} ${companion.last_name} ${formatDateOfBirth(companion.dateOfBirth)} ${companion.phone}`;
    emit('update:modelValue', companionString);
    searchQuery.value = companionString;
    if (op.value) {
        op.value.hide();
    }
};

const onInputFocus = (event) => {
    if (searchQuery.value && companions.value.length > 0 || (searchQuery.value && searchQuery.value.length >= 2 && companions.value.length === 0 && !isLoading.value)) {
        if (op.value && searchInputRef.value) {
            op.value.show(event, searchInputRef.value.$el || event.target);
        }
    }
};
</script>

<template>
    <div class="tw-w-full">
        <!-- Search Input Section -->
        <div class="tw-flex-1 tw-relative">
            <div class="tw-flex tw-items-center tw-gap-2">
                <InputText
                    ref="searchInputRef"
                    v-model="searchQuery"
                    @input="handleSearch"
                    :placeholder="placeholder || 'Search companion by name or phone...'"
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

        <!-- Search Results Overlay Panel -->
        <OverlayPanel ref="op" :showCloseIcon="false" class="tw-companion-overlay">
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
            <template v-else>
                <!-- Companion Results Table -->
                <div v-if="companions.length > 0" class="tw-w-full">
                    <div class="tw-font-semibold tw-text-gray-700 tw-mb-3 tw-px-2">
                        Available Companions ({{ companions.length }})
                    </div>
                    <DataTable 
                        :value="companions" 
                        :scrollable="true" 
                        scrollHeight="300px"
                        class="p-datatable-sm tw-border tw-border-gray-200 tw-rounded-lg"
                        :paginator="companions.length > 10"
                        :rows="10"
                        responsiveLayout="scroll"
                    >
                        <Column field="first_name" header="Name" style="min-width: 150px;">
                            <template #body="slotProps">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-user tw-text-blue-500"></i>
                                    <span class="tw-font-medium tw-text-gray-800">
                                        {{ slotProps.data.first_name }} {{ slotProps.data.last_name }}
                                    </span>
                                </div>
                            </template>
                        </Column>
                        <Column field="phone" header="Phone" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-phone tw-text-green-500"></i>
                                    <span>{{ slotProps.data.phone }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="Idnum" header="ID Number" style="min-width: 120px;">
                            <template #body="slotProps">
                                <div class="tw-flex tw-items-center tw-gap-2">
                                    <i class="pi pi-id-card tw-text-purple-500"></i>
                                    <span>{{ slotProps.data.Idnum }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Actions" style="min-width: 100px;">
                            <template #body="slotProps">
                                <Button 
                                    label="Select" 
                                    icon="pi pi-check" 
                                    class="p-button-sm p-button-primary tw-rounded-full" 
                                    @click="selectCompanion(slotProps.data)"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
                
                <!-- No Results State -->
                <div v-else class="tw-flex tw-flex-col tw-items-center tw-py-8">
                    <i class="pi pi-search tw-text-5xl tw-text-gray-300 tw-mb-4"></i>
                    <div class="tw-text-gray-500 tw-text-lg">No companions found</div>
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
:deep(.tw-companion-overlay) {
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

