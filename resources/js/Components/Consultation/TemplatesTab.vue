<script setup>
import { ref, onMounted, watch, computed, onUnmounted } from 'vue';
import axios from 'axios';
import { useToastr } from './../../Components/toster';
import DocumentPreview from '../../Components/Consultation/DocumentPreview.vue';
import PatientDocumentHistory from '../../Components/Consultation/PatientDocumentHistory.vue';

const toaster = useToastr();
// Props
const props = defineProps({
  consultationData: {
    type: Object,
    default: () => ({})
  },
  selectedTemplates: {
    type: Array,
    default: () => []
  },
  patientId: {
    type: [Number, String],
    required: true
  },
  doctorId: {
    type: [Number, String],
    default: null
  },
  appointmentId: {
    type: [Number, String],
    default: null
  }
});




// Add patient info state
const patientInfo = ref(null);
const isLoadingPatientInfo = ref(false);
const patientInfoError = ref(null);

// Add doctor info state
const doctorInfo = ref(null); // New state for doctor info
const isLoadingDoctorInfo = ref(false);
const doctorInfoError = ref(null);

const MimeType = ref(null);

const codebash = ref(null);
const getbashcode = async ()=>{
  try {
    const response = await axios.get(`/api/consultations/by-appointment/${props.appointmentId}`,{
      params: {
        isdoneconsultation: false // <-- This is the key line
      }
    });
    codebash.value = response.data.data.codebash || null;
  } catch (error) {
    console.error('Error fetching CodeBash:', error);
    toaster.error('Failed to load CodeBash');
  }
}
onMounted(() => {
  
  // Fetch patient info when component is mounted
  getPatientInfo(props.patientId);
  // Fetch doctor info when component is mounted
  if (props.doctorId) {
    getDoctorInfo(props.doctorId);
  }
  // Fetch bash code
  getbashcode();
});

const getPatientInfo = async (patientId) => {
  if (!patientId) {
    patientInfo.value = null;
    return;
  }

  isLoadingPatientInfo.value = true;
  patientInfoError.value = null;
  try {
    const response = await axios.get(`/api/patients/${patientId}`);
    patientInfo.value = response.data.data;
    console.log('Fetched Patient Info:', patientInfo.value);
    
    // Auto-fill patient attributes with PatientInfo. prefix
    if (patientInfo.value) {
      Object.keys(patientInfo.value).forEach(key => {
        const templateKey = `PatientInfo.${key}`;
        // Initialize if it doesn't exist
        if (!missingAttributeValues.value.hasOwnProperty(templateKey)) {
          missingAttributeValues.value[templateKey] = '';
        }
        // Only fill if it's currently empty
        if (!missingAttributeValues.value[templateKey]) {
          missingAttributeValues.value[templateKey] = patientInfo.value[key];
        }
      });
    }
  } catch (err) {
    patientInfoError.value = err.response?.data?.message || 'Failed to load patient information';
    toaster.error(patientInfoError.value);
    patientInfo.value = null;
  } finally {
    isLoadingPatientInfo.value = false;
  }
};

// New function to fetch doctor information
const getDoctorInfo = async (doctorId) => {
  if (!doctorId) {
    doctorInfo.value = null;
    return;
  }

  isLoadingDoctorInfo.value = true;
  doctorInfoError.value = null;
  try {
    const response = await axios.get(`/api/doctors/${doctorId}`);
    doctorInfo.value = response.data.data; // Assuming response.data directly contains the doctor object from your toArray method
    console.log('Fetched Doctor Info:', doctorInfo.value);
  } catch (err) {
    doctorInfoError.value = err.response?.data?.message || 'Failed to load doctor information';
    toaster.error(doctorInfoError.value);
    doctorInfo.value = null;
  } finally {
    isLoadingDoctorInfo.value = false;
  }
};


// Emits
const emit = defineEmits(['update:selected-templates', 'update:consultation-data']);

// State
const folders = ref([]);
const templates = ref([]);
const selectedFolder = ref(null);
const selectedTemplate = ref(null);
const loading = ref(false);
const error = ref(null);
const previewContent = ref('');
const previewMode = ref(true);
const documentContent = ref('');
const folderid = ref(null);

// Missing attributes state
const missingAttributes = ref([]);
const showMissingAttributesSection = ref(false);
const missingAttributeValues = ref({});

// Search states
const folderSearchQuery = ref('');
const templateSearchQuery = ref('');
const showFolderDropdown = ref(false);
const showTemplateDropdown = ref(false);
const documentHistoryRef = ref(null);

const handleDocumentSaved = async (documentData) => {
  if (documentHistoryRef.value) {
    await documentHistoryRef.value.refresh();
  }
};

const filteredFolders = computed(() => {
  // If folders.value is null/undefined, return empty array
  if (!folders.value) return []
  
  // If no search query, return all folders
  if (!folderSearchQuery.value || typeof folderSearchQuery.value !== 'string') {
    return folders.value
  }

  const searchTerm = folderSearchQuery.value.toLowerCase().trim()
  
  return folders.value.filter(folder => {
    // Safely check if folder exists and has name property
    return folder?.name?.toLowerCase().includes(searchTerm)
  })
})

const filteredTemplates = computed(() => {
  if (!templateSearchQuery.value) return templates.value;
  return templates.value.filter(template => 
    template.name.toLowerCase().includes(templateSearchQuery.value.toLowerCase())
  );
});

const canSaveMissingAttributes = computed(() => {
  return missingAttributes.value.some(attr => 
    missingAttributeValues.value[attr] && 
    missingAttributeValues.value[attr].trim() !== ''
  );
});

// --- API methods ---
// API Calls
const getFolders = async () => {
  if (props.doctorId === null) {
    console.warn('Cannot fetch folders: currentDoctorId is null.');
    return;
  }
  
  loading.value = true;
  
  try {
    const response = await axios.get('/folders', {
      params: { doctorid: props.doctorId },
    });

    // Convert the object response to array if needed
    if (response.data && typeof response.data === 'object') {
      if (Array.isArray(response.data.data)) {
        // If it's already an array, use it directly
        folders.value = response.data.data;
      } else if (response.data.data && typeof response.data.data === 'object') {
        // If it's an object, convert to array
        folders.value = Object.values(response.data.data);
      } else {
        // If data doesn't exist or is unexpected format
        folders.value = [];
      }
    } else {
      folders.value = [];
    }

    console.log('Folders loaded:', folders.value);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load folders';
    toaster.error(error.value);
    folders.value = [];
  } finally {
    loading.value = false;
  }
};
const getTemplatesByFolder = async (folderId) => {
  try {
    loading.value = true;
    const response = await axios.get(`/api/templates`, {
      params: { folder_id: folderId }
    });
    templates.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load templates';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

// --- Search and selection methods ---
const onFolderSearch = (event) => {
  folderSearchQuery.value = event.target.value;
  showFolderDropdown.value = true;
};

const onFolderSelect = (folder) => {
  selectedFolder.value = folder;
  folderid.value = folder.id;
  folderSearchQuery.value = folder.name;
  showFolderDropdown.value = false;
  selectedTemplate.value = null;
  templateSearchQuery.value = '';
  templates.value = [];
  getTemplatesByFolder(folder.id);
};

const onTemplateSearch = (event) => {
  templateSearchQuery.value = event.target.value;
  showTemplateDropdown.value = true;
};

const onTemplateSelect = (template) => {
  selectedTemplate.value = template;
  console.log('Saving selected template:', template);
  
  MimeType.value = template.mime_type

  templateSearchQuery.value = template.name;
  showTemplateDropdown.value = false;
  emit('update:selected-templates', [template.id]);
};

// --- Core Logic ---

// Helper function to escape special characters for use in a regular expression.
const escapeRegExp = (string) => {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); 
};

// Function to find missing attributes in template content
const findMissingAttributes = (templateContent, data) => {
  const missing = [];
  const placeholderRegex = /\{\{\s*([^}]+)\s*\}\}/g;
  let match;
  
  while ((match = placeholderRegex.exec(templateContent)) !== null) {
    const attribute = match[1].trim();
    // Only consider an attribute missing if it's not provided in the data or is an empty string
    // This will now correctly identify placeholders not fulfilled by patientInfo, doctorInfo, or consultationData
    if (!data.hasOwnProperty(attribute) || data[attribute] === null || data[attribute] === undefined || String(data[attribute]).trim() === '') {
      if (!missing.includes(attribute)) {
        missing.push(attribute);
        // Initialize missing attribute value if not exists, but only if it's not a special auto-filled one
        if (!missingAttributeValues.value.hasOwnProperty(attribute) && !attribute.startsWith('PatientInfo.') && attribute !== 'doctor_name' && attribute !== 'current_date') {
          missingAttributeValues.value[attribute] = '';
        }
      }
    }
  }
  return missing;
};


const generatePreview = async () => {
  if (props.selectedTemplates.length === 0) {
    previewContent.value = '<div class="premium-empty-state"><i class="fas fa-eye-slash fa-3x text-muted mb-3"></i><p>Please select at least one template to preview</p></div>';
    showMissingAttributesSection.value = false;
    return;
  }
  
  try {
    loading.value = true;
    
    // Fetch patient info if not already loaded
    if (props.patientId && !patientInfo.value) {
      await getPatientInfo(props.patientId);
    }
    
    // Fetch doctor info if not already loaded
    if (props.doctorId && !doctorInfo.value) {
      await getDoctorInfo(props.doctorId);
    }

    const selectedTemplateObjects = templates.value.filter(t => props.selectedTemplates.includes(t.id));
    
    if (selectedTemplateObjects.length === 0) {
      previewContent.value = '<div class="premium-empty-state"><i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i><p>Selected templates not found</p></div>';
      return;
    }

    // Create a flattened data structure for easy replacement
    const combinedData = { 
      ...props.consultationData,
      ...missingAttributeValues.value,
      // Add doctor's name
      'doctor_name': doctorInfo.value?.name || '', // Use the name from the fetched doctorInfo
      // Add current date
      'current_date': new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) // Example format: July 20, 2024
    };

    // Add patient info with proper prefixing
    if (patientInfo.value) {
      Object.keys(patientInfo.value).forEach(key => {
        combinedData[`PatientInfo.${key}`] = patientInfo.value[key];
      });
    }

    let allMissingAttributes = [];
    selectedTemplateObjects.forEach(template => {
      const missing = findMissingAttributes(template.content, combinedData);
      allMissingAttributes.push(...missing);
    });
    
    missingAttributes.value = [...new Set(allMissingAttributes)];
    showMissingAttributesSection.value = missingAttributes.value.length > 0;
    
    // Auto-fill patient attributes in missing values (these are user-fillable, not fixed)
    if (patientInfo.value) {
      missingAttributes.value.forEach(attr => {
        if (attr.startsWith('PatientInfo.') && !missingAttributeValues.value[attr]) {
          const patientKey = attr.replace('PatientInfo.', '');
          if (patientInfo.value && patientInfo.value[patientKey]) {
            missingAttributeValues.value[attr] = patientInfo.value[patientKey];
            combinedData[attr] = patientInfo.value[patientKey];
          }
        }
      });
    }

    let combinedContent = '';
    for (const template of selectedTemplateObjects) {
      let content = template.content;
      
      // Replace all placeholders with actual values
      Object.keys(combinedData).forEach(key => {
        const value = combinedData[key];
        // Ensure value is not null/undefined and is converted to string for replacement
        if (value !== null && value !== undefined) {
          const escapedKey = escapeRegExp(key);
          const regex = new RegExp(`\\{\\{\\s*${escapedKey}\\s*\\}\\}`, 'g');
          content = content.replace(regex, String(value));
        } else {
          // If a placeholder has no value, replace it with an empty string
          const escapedKey = escapeRegExp(key);
          const regex = new RegExp(`\\{\\{\\s*${escapedKey}\\s*\\}\\}`, 'g');
          content = content.replace(regex, '');
        }
      });

      if (selectedTemplateObjects.length === 1) {
        combinedContent = `<div class="premium-template-content">${content}</div>`;
      } else {
        combinedContent += `<div class="premium-template-section">
          <h3 class="premium-template-title">${template.name}</h3>
          <div class="premium-template-content">${content}</div>
        </div>`;
      }
    }
    
    previewContent.value = combinedContent;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to generate preview';
    toaster.error(error.value);
    previewContent.value = '<div class="premium-empty-state"><i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i><p>Error generating preview</p></div>';
  } finally {
    loading.value = false;
  }
};

// Add watchers for patientId and doctorId
watch(() => props.patientId, (newId) => {
  if (newId) {
    getPatientInfo(newId);
  }
}, { immediate: true });

watch(() => props.doctorId, (newId) => {
  if (newId) {
    getDoctorInfo(newId);
  }
}, { immediate: true });


// --- Action Handlers ---
const saveMissingAttributes = async () => {
  try {
    loading.value = true;
    
    // Update the consultation data with missing attribute values
    const updatedData = { ...props.consultationData };
    Object.keys(missingAttributeValues.value).forEach(key => {
      // Only include attributes that were actually filled by the user
      const val = missingAttributeValues.value[key];
      if (val !== undefined && val !== null && String(val).trim() !== '') {
        updatedData[key] = val;
      }
    });

    // Save to database
    // Ensure consultation_id is handled correctly, parseInt(17) seems hardcoded here.
    const response = await axios.post('/api/placeholders/consultation-attributes/save', {
      consultation_id: parseInt(props.consultationData.id), // Use dynamic consultation_id if available, fallback to 17
      attributes: updatedData,
      patient_id: props.patientId,
      doctor_id: props.doctorId,
      appointment_id: props.appointmentId
    });

    if (response.data.success) {
      // Update local data
      emit('update:consultation-data', updatedData);
      generatePreview(); // Regenerate preview to reflect saved changes
      showMissingAttributesSection.value = false;
      toaster.success('Attributes saved successfully');
    } else {
      throw new Error(response.data.message);
    }
  } catch (error) {
    console.error('Save Error:', error);
    toaster.error(error.message || 'Failed to save attributes');
  } finally {
    loading.value = false;
  }
};

const toggleEditMode = () => {
  previewMode.value = !previewMode.value;
  if (!previewMode.value) {
    documentContent.value = previewContent.value;
  }
};

const saveEditedContent = () => {
  previewContent.value = documentContent.value;
  previewMode.value = true;
  toaster.success('Changes saved successfully');
};

const handleClickOutside = (event) => {
  if (!event.target.closest('.premium-dropdown-container')) {
    showFolderDropdown.value = false;
    showTemplateDropdown.value = false;
  }
};

const validateAttribute = (attribute) => {
  // This function currently does nothing, but you could add validation logic here
  if (!missingAttributeValues.value[attribute]) {
    missingAttributeValues.value[attribute] = '';
  }
};

// Watchers
watch([() => props.consultationData, () => props.selectedTemplates, doctorInfo, patientInfo], () => {
  generatePreview();
}, { deep: true, immediate: true });

onMounted(() => {
  getFolders();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="premium-tab-content">
    <div class="premium-section-header">
      <h2 class="premium-section-title">Document Generator</h2>
      <p class="premium-section-description">Select templates to generate customized patient documents</p>
    </div>

    <div class="premium-card">
      <div class="premium-card-header">
        <h3 class="premium-card-title">Template Selection</h3>
      </div>
      
      <div class="premium-card-body">
        <div class="premium-form-grid">
          <div class="premium-form-group">
            <label class="premium-label">Search Folders</label>
            <div class="premium-dropdown-container">
             <div class="premium-input-group">
  <i class="fas fa-folder premium-input-icon"></i>
  <input 
    type="text" 
    class="premium-search" 
    placeholder="Type to search folders..."
    v-model="folderSearchQuery"
    @input="onFolderSearch"
    @focus="showFolderDropdown = true"
  >
  <i 
    v-if="folderSearchQuery" 
    class="fas fa-times premium-clear-icon" 
    @click="folderSearchQuery = ''; showFolderDropdown = true"
    title="Clear"
    style="right: 2.5rem; cursor:pointer; position:absolute;"
  ></i>
  <i class="fas fa-chevron-down premium-dropdown-arrow" 
     @click="showFolderDropdown = !showFolderDropdown"></i>
</div>
              
              <div v-if="showFolderDropdown && filteredFolders.length > 0" 
                   class="premium-dropdown-menu">
                <div v-for="folder in filteredFolders" 
                     :key="folder.id" 
                     class="premium-dropdown-item"
                     @click="onFolderSelect(folder)">
                  <i class="fas fa-folder premium-item-icon"></i>
                  {{ folder.name }} </div>
              </div>

              <div v-if="showFolderDropdown && filteredFolders.length === 0 && folderSearchQuery" 
                   class="premium-dropdown-menu">
                <div class="premium-dropdown-empty">No folders found</div>
              </div>
            </div>
          </div>

          <div v-if="selectedFolder" class="premium-form-group">
            <label class="premium-label">Search Templates</label>
            <div class="premium-dropdown-container">
              <div class="premium-input-group">
                <i class="fas fa-file-alt premium-input-icon"></i>
                <input 
                  type="text" 
                  class="premium-search" 
                  placeholder="Type to search templates..."
                  v-model="templateSearchQuery"
                  @input="onTemplateSearch"
                  @focus="showTemplateDropdown = true"
                >
                <i class="fas fa-chevron-down premium-dropdown-arrow" 
                   @click="showTemplateDropdown = !showTemplateDropdown"></i>
              </div>
              
              <div v-if="showTemplateDropdown && filteredTemplates.length > 0" 
                   class="premium-dropdown-menu">
                <div v-for="template in filteredTemplates" 
                     :key="template.id" 
                     class="premium-dropdown-item"
                     @click="onTemplateSelect(template)">
                  <i class="fas fa-file-alt premium-item-icon"></i>
                  {{ template.name }} {{ template.mime_type === 'Consultation' ? 'A4' : template.mime_type }}
                </div>
              </div>
              
              <div v-if="showTemplateDropdown && filteredTemplates.length === 0 && templateSearchQuery" 
                   class="premium-dropdown-menu">
                <div class="premium-dropdown-empty">No templates found</div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="showMissingAttributesSection" class="premium-missing-attributes-section">
          <div class="premium-card">
            <div class="premium-card-header premium-warning-header">
              <h3 class="premium-card-title">
                <i class="fas fa-exclamation-triangle premium-warning-icon"></i>
                Missing Attributes Found
              </h3>
              <p class="premium-warning-description">
                Please fill in the following required attributes:
              </p>
            </div>
            
            <div class="premium-card-body">
              <div class="premium-missing-attributes-grid">
                <div 
                  v-for="attribute in missingAttributes" 
                  :key="attribute" 
                  class="premium-form-group"
                >
                  <label class="premium-label">{{ attribute }}</label>
                  <div class="premium-input-group">
                    <i class="fas fa-edit premium-input-icon"></i>
                    <input 
                      type="text" 
                      class="premium-input" 
                      :placeholder="`Enter value for ${attribute}`"
                      v-model="missingAttributeValues[attribute]"
                      @input="validateAttribute(attribute)"
                    >
                  </div>
                </div>
              </div>
              
              <div class="premium-missing-attributes-actions">
                <button 
                  class="premium-btn premium-btn-primary" 
                  @click="saveMissingAttributes"
                  :disabled="!canSaveMissingAttributes"
                >
                  <i class="fas fa-save"></i>
                  Save & Update Preview
                </button>
                <button 
                  class="premium-btn premium-btn-secondary" 
                  @click="showMissingAttributesSection = false"
                >
                  <i class="fas fa-times"></i>
                  Skip for Now
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="premium-preview-container">
          <document-preview
            :preview-content="previewContent"
            :loading="loading"
            :MimeType="MimeType"
            :codebash="codebash"
            :selected-templates="props.selectedTemplates"
            :template-data="props.consultationData"
            :patient-id="props.patientId"
            :doctor-id="props.doctorId"
            :folderid="folderid"
            :appointment-id="props.appointmentId"
            @update:preview-content="newContent => previewContent = newContent"
            @refresh="generatePreview"
            @document-saved="handleDocumentSaved"
          />
        </div>
      </div>
    </div>

    <PatientDocumentHistory 
      ref="documentHistoryRef"
      :patient-id="props.patientId"
      :appointment-id="props.appointmentId" 
      class="premium-history-section"
    />
  </div>
</template>

<style scoped>
/* Base Styles */
.premium-tab-content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

/* Header */
.premium-section-header {
  margin-bottom: 2rem;
  text-align: center;
}

.premium-section-title {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.premium-section-description {
  color: #64748b;
  font-size: 1rem;
  max-width: 600px;
  margin: 0 auto;
}

/* Card Layout */
.premium-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  overflow: hidden;
}

.premium-card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.premium-card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.premium-card-body {
  padding: 1.5rem;
}

/* Missing Attributes Section */
.premium-missing-attributes-section {
  margin-bottom: 2rem;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.premium-warning-header {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-bottom: 1px solid #f59e0b;
}

.premium-warning-icon {
  color: #d97706;
  margin-right: 0.5rem;
}

.premium-warning-description {
  margin: 0.5rem 0 0 0;
  color: #92400e;
  font-size: 0.875rem;
}

.premium-missing-attributes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.premium-missing-attributes-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  border-top: 1px solid #f1f5f9;
  padding-top: 1rem;
}

/* Form Grid */
.premium-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.premium-form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.premium-label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

/* Dropdown Container */
.premium-dropdown-container {
  position: relative;
}

/* Input Groups */
.premium-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.premium-input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.premium-dropdown-arrow {
  position: absolute;
  right: 1rem;
  color: #94a3b8;
  font-size: 0.875rem;
  cursor: pointer;
  z-index: 2;
  transition: transform 0.2s;
}

.premium-dropdown-arrow:hover {
  color: #3a5bb1;
}

.premium-search,
.premium-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.premium-search:focus,
.premium-input:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Dropdown Menu */
.premium-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
  margin-top: 4px;
}

.premium-dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f1f5f9;
}

.premium-dropdown-item:last-child {
  border-bottom: none;
}

.premium-dropdown-item:hover {
  background-color: #f8fafc;
}

.premium-dropdown-item.selected {
  background-color: #3a5bb1;
  color: white;
}

.premium-item-icon {
  color: #94a3b8;
  font-size: 0.875rem;
}

.premium-dropdown-item:hover .premium-item-icon,
.premium-dropdown-item.selected .premium-item-icon {
  color: #3a5bb1;
}

.premium-dropdown-item.selected .premium-item-icon {
  color: white;
}

.premium-dropdown-empty {
  padding: 1rem;
  text-align: center;
  color: #94a3b8;
  font-style: italic;
}

/* Preview Section */
.premium-preview-container {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

/* Buttons */
.premium-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.premium-btn-primary {
  background-color: #3a5bb1;
  color: white;
}

.premium-btn-primary:hover {
  background-color: #2d4a9e;
}

.premium-btn-primary:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.premium-btn-secondary {
  background-color: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.premium-btn-secondary:hover {
  background-color: #f8fafc;
  border-color: #cbd5e1;
}

.premium-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* History Section */
.premium-history-section {
  margin-top: 2rem;
}

/* Styles for document action buttons */
.premium-document-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 1.5rem;
  border-top: 1px solid #f1f5f9;
  margin-top: 1.5rem;
}

/* Loading State */
.fa-spin {
  animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .premium-tab-content {
    padding: 1rem;
  }
  
  .premium-form-grid,
  .premium-missing-attributes-grid {
    grid-template-columns: 1fr;
  }
  
  .premium-dropdown-menu {
    max-height: 150px;
  }
  
  .premium-missing-attributes-actions,
  .premium-document-actions {
    flex-direction: column;
  }
}
</style>