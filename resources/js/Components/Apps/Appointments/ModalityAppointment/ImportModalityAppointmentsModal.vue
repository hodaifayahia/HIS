

<script setup>
import { ref, onMounted, computed } from 'vue';
import { modalityAppointmentServices } from '../../services/modality/ModalityAppointment';
import { useToastr } from '../../../toster';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import ScrollPanel from 'primevue/scrollpanel';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true
    },
    modalityId: {
        type: [String, Number],
        default: null
    }
});

const emit = defineEmits(['close', 'imported']);

const toastr = useToastr();

// State
const selectedFile = ref(null);
const selectedModality = ref(null);
const modalities = ref([]);
const importing = ref(false);
const downloadingTemplate = ref(false);
const loadingModalities = ref(false);
const importResults = ref(null);
const fileUpload = ref(null);

const importOptions = ref({
    skipDuplicates: true,
    validateOnly: false,
    sendNotifications: false
});

// Methods
const loadModalities = async () => {
    if (props.modalityId) return; // Skip if specific modality
    
    loadingModalities.value = true;
    try {
        // You'll need to implement this service method
        const response = await modalityAppointmentServices.getAllModalities();
        if (response.success) {
            modalities.value = response.data;
        }
    } catch (error) {
        console.error('Error loading modalities:', error);
        toastr.error('Failed to load modalities');
    } finally {
        loadingModalities.value = false;
    }
};

const downloadTemplate = async () => {
    downloadingTemplate.value = true;
    try {
        const response = await modalityAppointmentServices.downloadImportTemplate();
        if (response.success) {
            const blob = response.data;
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'modality_appointments_template.xlsx';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
            
            toastr.success('Template downloaded successfully');
        } else {
            toastr.error(response.message);
        }
    } catch (error) {
        console.error('Error downloading template:', error);
        toastr.error('Failed to download template');
    } finally {
        downloadingTemplate.value = false;
    }
};

const onFileSelect = (event) => {
    selectedFile.value = event.files[0];
    importResults.value = null; // Clear previous results
};

const onFileRemove = () => {
    selectedFile.value = null;
    importResults.value = null;
};

const handleImport = async () => {
    if (!selectedFile.value) {
        toastr.error('Please select a file to import');
        return;
    }

    importing.value = true;
    try {
        const modalityIdToUse = props.modalityId || selectedModality.value;
        const response = await modalityAppointmentServices.importAppointments(
            selectedFile.value, 
            modalityIdToUse,
            importOptions.value
        );
        
        importResults.value = response;
        
        if (response.success) {
            toastr.success(response.message);
            if (!importOptions.value.validateOnly) {
                emit('imported', response.data);
            }
        } else {
            toastr.error(response.message);
        }
    } catch (error) {
        console.error('Error importing appointments:', error);
        toastr.error('Failed to import appointments');
        importResults.value = {
            success: false,
            message: 'Import failed due to an unexpected error'
        };
    } finally {
        importing.value = false;
    }
};

const handleClose = () => {
    // Reset state
    selectedFile.value = null;
    selectedModality.value = null;
    importResults.value = null;
    importOptions.value = {
        skipDuplicates: true,
        validateOnly: false,
        sendNotifications: false
    };
    
    // Clear file upload
    if (fileUpload.value) {
        fileUpload.value.clear();
    }
    
    emit('close');
};

// Lifecycle
onMounted(() => {
    loadModalities();
});
</script>
<template>
    <Dialog :visible="visible" modal header="Import Modality Appointments" :style="{ width: '50rem' }" 
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" @update:visible="handleClose">
        
        <div class="p-4">
            <!-- Download Template Section -->
            <div class="mb-4 p-3 border border-dashed border-primary-300 rounded">
                <h4 class="text-primary mb-2">
                    <i class="pi pi-download mr-2"></i>
                    Download Template
                </h4>
                <p class="text-sm text-muted mb-3">
                    Download the Excel template to see the required format for importing appointments.
                </p>
                <Button 
                    @click="downloadTemplate" 
                    icon="pi pi-download" 
                    label="Download Template"
                    class="p-button-outlined p-button-primary"
                    :loading="downloadingTemplate"
                />
            </div>

            <!-- Upload Section -->
            <div class="mb-4">
                <h4 class="mb-2">
                    <i class="pi pi-upload mr-2"></i>
                    Upload Appointments File
                </h4>
                
                <!-- Modality Selection (for specific modality import) -->
                <div class="mb-3" v-if="!modalityId">
                    <label class="block text-sm font-medium mb-2">Import for specific modality (optional):</label>
                    <Dropdown 
                        v-model="selectedModality" 
                        :options="modalities" 
                        optionLabel="name" 
                        optionValue="id"
                        placeholder="Select modality (leave empty for all)" 
                        class="w-full"
                        :loading="loadingModalities"
                    />
                </div>

                <FileUpload
                    ref="fileUpload"
                    :multiple="false"
                    accept=".xlsx,.xls,.csv"
                    :maxFileSize="10000000"
                    @select="onFileSelect"
                    @remove="onFileRemove"
                    :auto="false"
                    chooseLabel="Choose File"
                    uploadLabel="Import"
                    cancelLabel="Clear"
                    :showUploadButton="false"
                    :showCancelButton="false"
                >
                    <template #empty>
                        <div class="text-center p-4">
                            <i class="pi pi-cloud-upload text-4xl text-muted"></i>
                            <p class="mt-2 text-muted">Drag and drop Excel/CSV file here to import</p>
                            <p class="text-sm text-muted">Supported formats: .xlsx, .xls, .csv (Max 10MB)</p>
                        </div>
                    </template>
                </FileUpload>
            </div>

            <!-- Import Options -->
            <div class="mb-4" v-if="selectedFile">
                <h4 class="mb-2">Import Options</h4>
                
                <div class="flex align-items-center mb-2">
                    <Checkbox v-model="importOptions.skipDuplicates" inputId="skipDuplicates" binary />
                    <label for="skipDuplicates" class="ml-2">Skip duplicate appointments</label>
                </div>
                
                <div class="flex align-items-center mb-2">
                    <Checkbox v-model="importOptions.validateOnly" inputId="validateOnly" binary />
                    <label for="validateOnly" class="ml-2">Validate only (don't import)</label>
                </div>
                
                <div class="flex align-items-center">
                    <Checkbox v-model="importOptions.sendNotifications" inputId="sendNotifications" binary />
                    <label for="sendNotifications" class="ml-2">Send notifications to patients</label>
                </div>
            </div>

            <!-- Import Results -->
            <div v-if="importResults" class="mb-4">
                <Message 
                    :severity="importResults.success ? 'success' : 'error'" 
                    :closable="false"
                >
                    {{ importResults.message }}
                </Message>
                
                <div v-if="importResults.data" class="mt-3">
                    <div class="grid">
                        <div class="col-12 md:col-4">
                            <Card>
                                <template #content>
                                    <div class="text-center">
                                        <i class="pi pi-check-circle text-3xl text-green-500 mb-2"></i>
                                        <p class="text-xl font-bold text-green-500">{{ importResults.data.successful || 0 }}</p>
                                        <p class="text-sm text-muted">Successful</p>
                                    </div>
                                </template>
                            </Card>
                        </div>
                        
                        <div class="col-12 md:col-4">
                            <Card>
                                <template #content>
                                    <div class="text-center">
                                        <i class="pi pi-exclamation-triangle text-3xl text-orange-500 mb-2"></i>
                                        <p class="text-xl font-bold text-orange-500">{{ importResults.data.skipped || 0 }}</p>
                                        <p class="text-sm text-muted">Skipped</p>
                                    </div>
                                </template>
                            </Card>
                        </div>
                        
                        <div class="col-12 md:col-4">
                            <Card>
                                <template #content>
                                    <div class="text-center">
                                        <i class="pi pi-times-circle text-3xl text-red-500 mb-2"></i>
                                        <p class="text-xl font-bold text-red-500">{{ importResults.data.failed || 0 }}</p>
                                        <p class="text-sm text-muted">Failed</p>
                                    </div>
                                </template>
                            </Card>
                        </div>
                    </div>
                    
                    <!-- Errors List -->
                    <div v-if="importResults.data.errors && importResults.data.errors.length > 0" class="mt-4">
                        <h5>Import Errors:</h5>
                        <ScrollPanel style="width: 100%; height: 200px">
                            <ul class="list-none p-0">
                                <li v-for="(error, index) in importResults.data.errors" :key="index" 
                                    class="mb-2 p-2 bg-red-50 border-left-3 border-red-500">
                                    <strong>Row {{ error.row }}:</strong> {{ error.message }}
                                </li>
                            </ul>
                        </ScrollPanel>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <Button 
                label="Cancel" 
                @click="handleClose" 
                class="p-button-text"
                :disabled="importing"
            />
            <Button 
                :label="importOptions.validateOnly ? 'Validate' : 'Import'" 
                @click="handleImport" 
                :disabled="!selectedFile || importing"
                :loading="importing"
                class="p-button-primary"
            />
        </template>
    </Dialog>
</template>


<style scoped>
.border-dashed {
    border-style: dashed;
}

.border-primary-300 {
    border-color: var(--primary-300);
}

.bg-red-50 {
    background-color: rgba(239, 68, 68, 0.1);
}

.border-left-3 {
    border-left-width: 3px;
}

.border-red-500 {
    border-left-color: var(--red-500);
}

.text-muted {
    color: var(--text-color-secondary);
}
</style>