<script setup>
import { ref, watch, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { useToastr } from '../../../Components/toster';
import { useRouter, useRoute } from 'vue-router';
import { useSweetAlert } from '../../../Components/useSweetAlert';

const router = useRouter();
const route = useRoute();
const templateId = computed(() => route.params.id);
const doctorId = computed(() => route.params.doctor_id);
//Swal
const swal = useSweetAlert();




const dbPlaceholders = ref([]);
const dbAttributes = ref({});
const selectedDbPlaceholder = ref(null);
const selectedDbAttribute = ref(null);
const customPlaceholderName = ref(''); // For custom placeholder input
const newAttributeName = ref(''); // For adding new attribute to an existing placeholder
const textarea = ref(false);

const props = defineProps({
  showModal: Boolean,
  templateData: Object,
  isEdit: {
    type: Boolean,
    default: false
  },
  // NEW: Add doctorId as a prop (camelCase for consistency)
  doctorId: {
    type: [String, Number],
    default: null
  }
});

const folderid = route.params.folderid;
console.log(folderid);


const emit = defineEmits(['close', 'refresh']);

const toaster = useToastr();
const loading = ref(false);
const loadingattrbute = ref(false); // Used for attribute loading state
const error = ref(null);
const doctors = ref([]);
const activeTab = ref('basic');
const documentContent = ref(''); // This will be the source of truth for the form and preview
const wordFile = ref(null);
const uploadProgress = ref(0);
const placeholders = ref([]); // List of placeholders actually inserted into the document
const previewMode = ref(false);
const editorRef = ref(null);
const lastCaretPosition = ref(null); // Store the last caret position (a Range object)

// --- NEW: Static PatientInfo Placeholder Definition ---
const staticPatientInfoPlaceholder = {
  id: 'patient_info_static', // Unique ID for this static entry
  name: 'PatientInfo', // The name displayed and used in the placeholder tag
  isCustom: false, // Treat as a non-custom placeholder so it can have attributes
  isStatic: true, // Custom flag to identify it as static
};

// Computed property to combine dynamic and static placeholders for the dropdown
const allAvailablePlaceholders = computed(() => {
  // Always include the static PatientInfo at the top for easy access
  return [staticPatientInfoPlaceholder, ...dbPlaceholders.value, { id: 'custom', name: 'Custom', isCustom: true }];
});


// Computed property to determine the doctor_id to use
const effectiveDoctorId = computed(() => {
  // Priority: 1. Props doctorId, 2. Route params doctor_id, 3. Form value (last resort, if nothing else)
  return props.doctorId || doctorId.value || form.value.doctor_id;
});

// Computed property to check if doctor selection should be disabled
const isDoctorSelectionDisabled = computed(() => {
  return !!(props.doctorId || doctorId.value);
});

// Form data
const form = ref({
  name: '',
  description: '',
  content: '',
  doctor_id: '',
  mime_type: '',
  folder_id: '',
  placeholders: []
});

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    content: '',
    // Keep doctor_id if it's provided via props or route
    doctor_id: effectiveDoctorId.value || '',
    mime_type: '',
    placeholders: []
  };
  documentContent.value = '';
  placeholders.value = []; // Clear inserted placeholders
  wordFile.value = null;
  uploadProgress.value = 0;
  error.value = null;
  selectedDbPlaceholder.value = null;
  selectedDbAttribute.value = null;
  customPlaceholderName.value = '';
  newAttributeName.value = '';
  textarea.value = false;
  lastCaretPosition.value = null;
};

const fetchPlaceholders = async () => {
  try {
    loadingattrbute.value = true;
    const response = await axios.get('/api/placeholders', {
      params: {
        doctor_id: doctorId.value, // Use props.doctorId if available
      }
    });
    // Dynamically fetched placeholders only
    dbPlaceholders.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load placeholders';
    toaster.error(error.value);
  } finally {
    loadingattrbute.value = false;
  }
};

const fetchAttributes = async (placeholderId) => {
  if (!placeholderId) {
    // Clear attributes if no placeholder is selected (e.g., when resetting selection)
    dbAttributes.value[placeholderId] = [];
    return;
  }

  // --- NEW: Handle static PatientInfo attributes ---
  if (placeholderId === staticPatientInfoPlaceholder.id) {
    dbAttributes.value[placeholderId] = [
      { id: 'pi_id', name: 'id', input_type: 1 },
      { id: 'pi_first_name', name: 'first_name', input_type: 1 },
      { id: 'pi_last_name', name: 'last_name', input_type: 1 },
      { id: 'pi_full_name', name: 'full_name', input_type: 1 },
      { id: 'pi_parent', name: 'Parent', input_type: 1 },
      { id: 'pi_phone', name: 'phone', input_type: 1 },
      { id: 'pi_dateOfBirth', name: 'dateOfBirth', input_type: 1 },
      { id: 'pi_age', name: 'age', input_type: 1 },
      { id: 'pi_idnum', name: 'identification number', input_type: 1 },
      // Add other PatientInfo specific attributes as needed
      // { id: 'pi_notes', name: 'notes', input_type: 0 }, // Example for a textarea attribute
    ];
    loadingattrbute.value = false; // No loading for static data
    return; // Exit function, no API call needed
  }

  // Handle 'custom' placeholder (no attributes)
  if (placeholderId === 'custom') {
    dbAttributes.value[placeholderId] = [];
    return;
  }

  // --- Original logic for fetching dynamic attributes from API ---
  loadingattrbute.value = true;
  try {
    const response = await axios.get(`/api/attributes/${placeholderId}`);
    dbAttributes.value = {
      ...dbAttributes.value,
      [placeholderId]: response.data.data || []
    };
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load attributes';
    toaster.error(error.value);
    dbAttributes.value[placeholderId] = []; // Ensure it's an array on error
  } finally {
    loadingattrbute.value = false;
  }
};

// Function to add a new attribute to the selected placeholder
const addNewAttribute = async () => {
  if (!selectedDbPlaceholder.value || selectedDbPlaceholder.value.isCustom || !newAttributeName.value.trim()) {
    toaster.error("Please select a placeholder and provide a name for the new attribute.");
    return;
  }

  // Prevent adding new attributes to the static PatientInfo placeholder
  if (selectedDbPlaceholder.value.isStatic) {
    toaster.warning('Cannot add new attributes to the static PatientInfo placeholder.');
    newAttributeName.value = ''; // Clear the input
    selectedDbAttribute.value = null; // Reset attribute selection
    return;
  }

  const placeholderId = selectedDbPlaceholder.value.id;
  const attributeName = newAttributeName.value.trim();

  try {
    loading.value = true;
    const response = await axios.post(`/api/attributes`, {
      name: attributeName,
      placeholder_id: placeholderId,
      value: "",
      input_type: textarea.value ? 0 : 1, // 0 for textarea, 1 for text input
    });

    if (!dbAttributes.value[placeholderId]) {
      dbAttributes.value[placeholderId] = [];
    }
    const newAttr = response.data.data || response.data;
    dbAttributes.value[placeholderId].push(newAttr);

    selectedDbAttribute.value = newAttr;
    newAttributeName.value = '';
    toaster.success(`Attribute '${attributeName}' added successfully to '${selectedDbPlaceholder.value.name}'`);

  } catch (err) {
    toaster.error(err.response?.data?.message || 'Failed to add new attribute');
  } finally {
    loading.value = false;
  }
};

// Modified fetchDoctors function - only fetch if doctor_id is not provided
const fetchDoctors = async () => {
  if (isDoctorSelectionDisabled.value) {
    return;
  }

  try {
    loading.value = true;
    const response = await axios.get('/api/doctors');
    doctors.value = response.data.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load doctors';
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

// Watch for changes in effectiveDoctorId and update form
watch(() => effectiveDoctorId.value, (newValue) => {
  if (newValue) {
    form.value.doctor_id = newValue;
  }
}, { immediate: true });

onMounted(async () => {
  // Set initial doctor_id if provided
  if (effectiveDoctorId.value) {
    form.value.doctor_id = effectiveDoctorId.value;
  }

  await Promise.all([
    fetchDoctors(),
    fetchPlaceholders() // Fetches dynamic placeholders
  ]);

  if (templateId.value) {
    await fetchTemplateData(templateId.value);
  }

  nextTick(() => {
    setupEditorEvents();
    if (editorRef.value && documentContent.value) {
      editorRef.value.innerHTML = documentContent.value;
      scanForPlaceholders();
      saveCaretPosition();
    }
  });
});

const templateOptions = ref([
  { value: 'consultation', label: 'Normal A4', template: null },
  { value: 'A5', label: 'Prescription', template: 'prescription' }
]);
// Watch for changes in the selected template typewatch(() => form.value.mime_type, async (newValue, oldValue) => {
  // Skip if no change or initial setup
 watch(() => form.value.mime_type, async (newValue, oldValue) => {
  // Skip if no change or initial setup
  if (newValue === oldValue) return;

  const selectedOption = templateOptions.value.find(opt => opt.value === newValue);
  
  // Only show confirmation if there's existing content
  if (documentContent.value && documentContent.value.trim()) {
   
    
    if (!confirmChange.isConfirmed) {
      form.value.mime_type = oldValue;
      return;
    }
  }
  
  // Load the appropriate template
  if (selectedOption?.template === 'prescription') {
    loadPrescriptionTemplate();
  } else {
    // Clear or load default template for other formats
    documentContent.value = '';
    if (editorRef.value) {
      editorRef.value.innerHTML = '';
    }
  }
});
function mapMimeTypeToSelectValue(mimeType) {
  if (mimeType === 'prescription_type') return 'A5';
  if (mimeType === 'Consultation') return 'consultation';
  return mimeType; // fallback
}
// Function to set up prescription editing behavior
const setupPrescriptionEditing = () => {
  if (!editorRef.value) return;
  
  const prescriptionContent = editorRef.value.querySelector('.prescription-content[data-editable-area="true"]');
  if (!prescriptionContent) return;
  
  // Focus on the prescription content area when editor is clicked
  prescriptionContent.addEventListener('focus', () => {
    if (prescriptionContent.textContent.trim() === 'Click here to add prescription content...') {
      prescriptionContent.textContent = '';
    }
  });
  
  // Handle blur event
  prescriptionContent.addEventListener('blur', () => {
    if (prescriptionContent.textContent.trim() === '') {
      prescriptionContent.textContent = 'Click here to add prescription content...';
    }
    updateDocumentContent();
  });
  
  // Handle input events
  prescriptionContent.addEventListener('input', () => {
    updateDocumentContent();
    scanForPlaceholders();
  });
  
  // Auto-focus the prescription content area
  setTimeout(() => {
    prescriptionContent.focus();
  }, 100);
};

// Function to update document content while preserving template structure
const updateDocumentContent = () => {
  if (editorRef.value) {
    documentContent.value = editorRef.value.innerHTML;
    form.value.content = documentContent.value;
  }
};

// Modified insertPlaceholder function for prescription templates
const insertPlaceholderInPrescription = () => {
  if (!placeholderText.value || !editorRef.value) {
    if (!editorRef.value) {
      toaster.error("Editor is not ready. Please ensure the content tab is active.");
    } else if (!placeholderText.value) {
      toaster.error("Please select a placeholder/attribute or enter a custom name.");
    }
    return;
  }

  // For prescription templates, insert into the prescription-content area
  const prescriptionContent = editorRef.value.querySelector('.prescription-content[data-editable-area="true"]');
  if (prescriptionContent && form.value.mime_type === 'A5') {
    // Clear placeholder text if it exists
    if (prescriptionContent.textContent.trim() === 'Click here to add prescription content...') {
      prescriptionContent.textContent = '';
    }
    
    // Focus the prescription content area
    prescriptionContent.focus();
    
    // Get selection within the prescription content area
    const selection = window.getSelection();
    let range;
    
    if (selection.rangeCount > 0 && prescriptionContent.contains(selection.anchorNode)) {
      range = selection.getRangeAt(0);
    } else {
      // If no selection in prescription area, create one at the end
      range = document.createRange();
      range.selectNodeContents(prescriptionContent);
      range.collapse(false); // Collapse to end
      selection.removeAllRanges();
      selection.addRange(range);
    }
    
    // Create styled span for placeholder
    const spanNode = document.createElement('span');
    spanNode.style.fontSize = '1em';
    spanNode.style.fontWeight = 'normal';
    spanNode.style.backgroundColor = '#f8f9fa';
    spanNode.style.padding = '2px 4px';
    spanNode.style.borderRadius = '3px';
    spanNode.textContent = placeholderText.value;
    
    range.deleteContents();
    range.insertNode(spanNode);
    
    // Position cursor after the inserted placeholder
    const newRange = document.createRange();
    newRange.setStartAfter(spanNode);
    newRange.setEndAfter(spanNode);
    selection.removeAllRanges();
    selection.addRange(newRange);
    
    updateDocumentContent();
    scanForPlaceholders();
    
  } else {
    // Fall back to original insertion method for non-prescription templates
    insertPlaceholder();
  }
  
  // Reset selections
  selectedDbPlaceholder.value = null;
  selectedDbAttribute.value = null;
  customPlaceholderName.value = '';
};

  


const loadPrescriptionTemplate = () => {
  // Set the prescription template HTML content
  documentContent.value = `
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        .container {
            font-family: Arial, sans-serif;
            width: 170mm;
            margin: 0 auto;
            position: relative;
            min-height: 220mm;
            line-height: 1.4;
        }
        .container, .container * {
            font-size: 20px;
        }         
        .prescription-header {
            height: 13mm;
            text-align: center;
        }
        
        .header-clinic-name {
            font-size: 24pt;
            font-weight: bold;
            margin: 0;
        }
        
        .header-clinic-subtitle {
            font-size: 14pt;
            margin-top: 2mm;
            border-bottom: 1px dashed #000;
            padding-bottom: 3mm;
        }
        
        .meta-info {
            display: flex;
            justify-content: space-between;
            width: calc(100% - 25mm);
            margin:12mm 0 -15px 25mm;
        }
        
        .meta-info-doctor {
            flex: 0 0 auto;
        }
        
      .meta-info-date {
    flex: 0 0 auto;
    margin-left: auto;
}
        
        .patient-details {
            display: flex;
            width: calc(100% - 25mm);
            margin: 12mm 0 0 25mm;
        }
        
        .patient-details-item {
            flex: 0 0 auto;
        }
        
        .patient-details-item.patient-details-item--firstname {
            margin-left: 0;
        }
        
        .patient-details-item.patient-details-item--lastname {
            margin-left: 60mm;
        }
        
        .patient-details-item.patient-details-item--age {
            margin-left: 40mm;
        }
        
        .prescription-title {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            text-decoration: underline;
        }
        
        .prescription-content {
            width: calc(100% - 15mm);
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
          
    
        }
        
        .prescription-content:focus {
           
        }
        
        .prescription-content[contenteditable="true"] {
            cursor: text;
        }
        
        .medication-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .medication-list-item {
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
        }
        
        .medication-item-line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 15px;
            flex-wrap: nowrap;
        }
        
        .medication-item-name {
            font-weight: bold;
            flex: 2;
            min-width: 120px;
            margin-left: -10mm;
        }
        
        .medication-item-dosage {
            flex: 1;
            min-width: 80px;
            text-align: left;
            font-size: 0.95em;
        }
        
        .codebash-header {
            position: absolute;
            bottom: 15mm;
            left: 5mm;
            width: 30mm;
            height: 15mm;
        }
        
        .barcode-image {
            margin-top:1mm;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .medication-item-duration {
            flex: 1;
            min-width: 100px;
            font-style: italic;
           
            font-size: 0.7em;
        }
        
        .medication-item-instructions {
            flex: 3;
            min-width: 150px;
            
            font-size: 0.9em;
        }
        
        .medication-list-item-sub-instructions {
            margin-left: 25mm;
            font-size: 0.85em;
            
            margin-top: 2px;
        }
        
        .signature-section {
            margin-top: 15mm;
            text-align: right;
            margin-right: 25mm;
        }
        
        .signature-section-line {
            border-bottom: 1px solid #000;
            width: 80mm;
            margin-left: auto;
            margin-top: 10mm;
        }
        
        .prescription-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            padding-bottom: 5mm;
        }
        
        .additional-notes-section {
            margin: 10mm 25mm 0 25mm;
            padding-top: 5mm;
            border-top: 1px dashed #ccc;
        }

        @media print {
            body {
                width: 100%;
                min-height: 100%;
            }
            .medication-item-line {
                page-break-inside: avoid;
            }
            .prescription-content {
                border: none !important;
                
            }
        }
    </style>
</head>
<body>
    <div class="prescription-header"> 
    </div>
    <div class="container">
        <div class="meta-info">
            <div class="meta-info-doctor">
                <strong></strong> {{doctor_name}}
            </div>
            <div class="meta-info-date">
                <strong></strong> {{current_date}}
            </div>
        </div>
        
        <div class="patient-details">
            <div class="patient-details-item patient-details-item--firstname">
                <strong></strong> {{PatientInfo.first_name}}
            </div>
            <div class="patient-details-item patient-details-item--lastname">
                <strong></strong> {{PatientInfo.last_name}}
            </div>
            <div class="patient-details-item patient-details-item--age">
                <strong></strong> {{PatientInfo.age}}
            </div>
        </div>
        
        <div class="prescription-title">Ordonnance Médicale</div>        
        <div class="prescription-content" contenteditable="true" data-editable-area="true">
           write Here
        </div>
        
        <!-- Barcode in bottom right corner -->
        <div class="codebash-header">
        </div>
    </div>
</body>
</html>
  `;
  
  // Update the form content
  form.value.content = documentContent.value;
  form.value.mime_type = 'A5';
  
  // Set the editor content in the next tick
  nextTick(() => {
    if (editorRef.value) {
      editorRef.value.innerHTML = documentContent.value;
      setupPrescriptionEditing(); // New function to handle prescription-specific editing
      scanForPlaceholders();
      saveCaretPosition();
    }
  });
  
  // Set a default name if empty
  if (!form.value.name) {
    form.value.name = "Prescription Template";
  }
};

watch(() => selectedDbPlaceholder.value, (newValue) => {
  selectedDbAttribute.value = null; // Always reset attribute when placeholder changes
  newAttributeName.value = ''; // Clear new attribute name input
  textarea.value = false; // Reset textarea checkbox

  if (newValue) {
    fetchAttributes(newValue.id); // Fetch attributes for the newly selected placeholder
  } else {
    // If no placeholder selected, ensure attributes are cleared for the dropdown
    dbAttributes.value = {};
  }
});

// WATCHER: When templateData changes, update form and documentContent
watch(() => props.templateData, (newValue) => {
  if (newValue && Object.keys(newValue).length > 0) {
 form.value = {
  name: newValue.name || '',
  description: newValue.description || '',
  content: newValue.content || '',
  doctor_id: effectiveDoctorId.value || newValue.doctor?.id || '',
  mime_type: mapMimeTypeToSelectValue(newValue.mime_type || 'consultation'),
  placeholders: newValue.placeholders || []
};
    documentContent.value = newValue.content || '';
    placeholders.value = newValue.placeholders || [];

    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        scanForPlaceholders();
        saveCaretPosition();
      }
    });
  }
}, { immediate: true, deep: true });

// Function to save the current selection/caret position
const saveCaretPosition = () => {
  if (editorRef.value && window.getSelection) {
    const selection = window.getSelection();
    if (selection.rangeCount > 0 && editorRef.value.contains(selection.anchorNode)) {
      lastCaretPosition.value = selection.getRangeAt(0).cloneRange();
    } else {
      lastCaretPosition.value = null;
    }
  }
};

// Function to restore the saved selection/caret position
const restoreCaretPosition = () => {
  if (lastCaretPosition.value && window.getSelection && editorRef.value) {
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(lastCaretPosition.value);
    editorRef.value.focus();
  }
};

const saveTemplate = async () => {
  if (editorRef.value && !previewMode.value) {
    documentContent.value = editorRef.value.innerHTML;
    form.value.content = documentContent.value;
  }

  form.value.doctor_id = effectiveDoctorId.value;
  form.value.folder_id = folderid;
  

  if (!validateForm()) return;

   // Set mime_type according to selection
 if (form.value.mime_type === 'A5') {
  form.value.mime_type = 'prescription_type';
} else {
  form.value.mime_type = 'Consultation';
}
  
  try {
    loading.value = true;
    form.value.placeholders = placeholders.value;
    const payload = {
       ...form.value ,


    };

    const isEdit = !!templateId.value;
    const url = isEdit ? `/api/templates/${templateId.value}` : '/api/templates';
    const method = isEdit ? 'put' : 'post';

    await axios[method](url, payload);
    toaster.success(`Template ${isEdit ? 'updated' : 'created'} successfully`);

    router.push({
      name: 'admin.consultation.template',
      params: { folderid: folderid }
    });

  } catch (err) {
  } finally {
    loading.value = false;
  }
};

const validateForm = () => {
  if (!form.value.name.trim()) {
    error.value = 'Template name is required';
    toaster.error(error.value);
    return false;
  }
  if (!form.value.content.trim()) {
    error.value = 'Template content is required';
    toaster.error(error.value);
    return false;
  }
  if (!effectiveDoctorId.value) {
    error.value = 'Doctor selection is required';
    toaster.error(error.value);
    return false;
  }
  return true;
};

const handleFileUploadWithDocxPreview = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  try {
    const { renderAsync } = await import('docx-preview');

    wordFile.value = file;
    uploadProgress.value = 30;

    const tempContainer = document.createElement('div');
    tempContainer.style.visibility = 'hidden';
    tempContainer.style.position = 'absolute';
    tempContainer.style.width = '100%';
    tempContainer.style.height = 'auto';
    tempContainer.style.overflow = 'hidden';
    document.body.appendChild(tempContainer);

    uploadProgress.value = 60;

    await renderAsync(file, tempContainer, null, {
      className: 'docx-wrapper',
      inWrapper: true,
      ignoreWidth: false,
      ignoreHeight: false,
      ignoreFonts: false,
      breakPages: false,
      ignoreLastRenderedPageBreak: true,
      experimental: false,
      trimXmlDeclaration: true,
      useBase64URL: true,
      renderHeaders: true,
      renderFooters: true,
      renderFootnotes: true,
      renderEndnotes: true,
    });

    uploadProgress.value = 90;

    let renderedHTML = tempContainer.innerHTML;

    renderedHTML = renderedHTML.replace(/<p>&nbsp;<\/p>/g, '');
    renderedHTML = renderedHTML.replace(/<p><br><\/p>/g, '');
    renderedHTML = renderedHTML.replace(/<div><\/div>/g, '');
    renderedHTML = renderedHTML.replace(/<div>\s*<\/div>/g, '');

    documentContent.value = `
      <div class="document-content-wrapper">
        ${renderedHTML}
      </div>
    `;

    document.body.removeChild(tempContainer);

    if (!form.value.name) {
      form.value.name = file.name.replace(/\.[^/.]+$/, "");
    }

    activeTab.value = 'content';
    uploadProgress.value = 100;

    setTimeout(() => {
      uploadProgress.value = 0;
    }, 1500);

    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        scanForPlaceholders();
        saveCaretPosition();
      }
    });

  } catch (err) {
    console.error("Docx-preview conversion error:", err);
    error.value = 'Failed to convert Word document: ' + (err.message || 'Unknown error');
    toaster.error(error.value);
    uploadProgress.value = 0;
  }
};

const scanForPlaceholders = () => {
  const contentToScan = editorRef.value ? editorRef.value.innerHTML : documentContent.value;
  const placeholderRegex = /\{\{([^}]+)\}\}/g;
  const found = [];
  let match;

  while ((match = placeholderRegex.exec(contentToScan)) !== null) {
    const placeholder = match[0];
    if (!found.includes(placeholder)) {
      found.push(placeholder);
    }
  }
  placeholders.value = found;
};

// Adjusted computed property for placeholderText to handle static PatientInfo and custom
// Adjusted computed property for placeholderText to handle static PatientInfo and custom
const placeholderText = computed(() => {
  if (selectedDbPlaceholder.value) {
    if (selectedDbPlaceholder.value.isCustom) {
      // For custom placeholders, use the custom name directly
      return customPlaceholderName.value.trim() ? `{{custom.${customPlaceholderName.value.trim()}}}` : null;
    } else if (selectedDbAttribute.value && selectedDbAttribute.value !== '__new__') {
      // For existing attributes of a selected non-custom (including static PatientInfo) placeholder
      // ALWAYS use a dot for separation here
      return `{{${selectedDbPlaceholder.value.name}.${selectedDbAttribute.value.name}}}`;
    } else if (selectedDbAttribute.value === '__new__' && newAttributeName.value.trim()) {
      // For a new attribute being added to an existing non-custom placeholder
      // ALWAYS use a dot for separation here
      return `{{${selectedDbPlaceholder.value.name}.${newAttributeName.value.trim()}}}`;
    } else {
      // If only a placeholder is selected but no attribute (e.g., just 'PatientInfo' chosen without an attribute)
      return null; // Don't allow insertion unless a specific attribute is chosen or created
    }
  }
  return null;
});


// ... (previous code)
const insertPlaceholder = () => {
  // Check if this is a prescription template and use specialized insertion
  if (form.value.mime_type === 'A5') {
    insertPlaceholderInPrescription();
    return;
  }
  
  // Original insertion logic for non-prescription templates
  if (!placeholderText.value || !editorRef.value) {
    if (!editorRef.value) {
      toaster.error("Editor is not ready. Please ensure the content tab is active.");
    } else if (!placeholderText.value) {
      toaster.error("Please select a placeholder/attribute or enter a custom name.");
    }
    return;
  }

  const selection = window.getSelection();
  if (selection.rangeCount > 0) {
    const range = selection.getRangeAt(0);

    if (!editorRef.value.contains(range.commonAncestorContainer) && !editorRef.value.isSameNode(range.commonAncestorContainer)) {
      toaster.error("Please click inside the document editor to place the cursor before inserting a placeholder.");
      editorRef.value.focus();
      return;
    }

    saveCaretPosition();

    const spanNode = document.createElement('span');
    spanNode.style.fontSize = '1em';
    spanNode.style.fontWeight = 'normal';
    spanNode.textContent = placeholderText.value;

    range.deleteContents();
    range.insertNode(spanNode);

    const newRange = document.createRange();
    newRange.setStartAfter(spanNode);
    newRange.setEndAfter(spanNode);

    selection.removeAllRanges();
    selection.addRange(newRange);

    documentContent.value = editorRef.value.innerHTML;
    form.value.content = documentContent.value;
    scanForPlaceholders();

    editorRef.value.focus();
    saveCaretPosition();
  }

  // Reset placeholder selections
  selectedDbPlaceholder.value = null;
  selectedDbAttribute.value = null;
  customPlaceholderName.value = '';
};

// ... (rest of the code)

const handleEditorInput = () => {
  saveCaretPosition();
  scanForPlaceholders();
};

const setupEditorEvents = () => {
  if (!editorRef.value) return;

  editorRef.value.addEventListener('mouseup', saveCaretPosition);
  editorRef.value.addEventListener('keyup', saveCaretPosition);
  editorRef.value.addEventListener('blur', () => {
    saveCaretPosition();
    documentContent.value = editorRef.value.innerHTML;
    form.value.content = documentContent.value;
  });
  editorRef.value.addEventListener('focus', () => {
    if (lastCaretPosition.value) {
      setTimeout(restoreCaretPosition, 0);
    }
  });
};

const togglePreview = () => {
  previewMode.value = !previewMode.value;
  if (previewMode.value) {
    if (editorRef.value) {
      documentContent.value = editorRef.value.innerHTML;
      form.value.content = documentContent.value;
    }
  } else {
    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        editorRef.value.focus();
        restoreCaretPosition();
      }
    });
  }
};

const removePlaceholder = (placeholder) => {
  const index = placeholders.value.indexOf(placeholder);
  if (index !== -1) {
    placeholders.value.splice(index, 1);
  }

  if (editorRef.value) {
    const scrollTop = editorRef.value.scrollTop;
    const scrollLeft = editorRef.value.scrollLeft;
    saveCaretPosition();

    let currentEditorContent = editorRef.value.innerHTML;
    const regex = new RegExp(escapeRegExp(placeholder), 'g');
    currentEditorContent = currentEditorContent.replace(regex, '');

    editorRef.value.innerHTML = currentEditorContent;
    documentContent.value = currentEditorContent;
    form.value.content = documentContent.value;

    nextTick(() => {
      editorRef.value.scrollTop = scrollTop;
      editorRef.value.scrollLeft = scrollLeft;
      scanForPlaceholders();
      restoreCaretPosition();
    });
  }
};

function escapeRegExp(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

const switchTab = (tab) => {
  activeTab.value = tab;
  if (editorRef.value && activeTab.value !== 'content') {
    documentContent.value = editorRef.value.innerHTML;
    form.value.content = documentContent.value;
  }
  if (tab === 'content') {
    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        editorRef.value.focus();
        restoreCaretPosition();
      }
    });
  }
};

watch(() => form.mime_type, async (newValue, oldValue) => {
  if (documentContent.value && documentContent.value.trim() && 
      documentContent.value !== form.value.content) {
    const confirmChange = await Swal.fire({
      title: 'Confirm Template Change',
      text: 'Changing the template will replace your current content. Continue?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, change template',
      cancelButtonText: 'Cancel'
    });
    
    if (!confirmChange.isConfirmed) {
      // Revert to the previous value
      form.value.mime_type = oldValue;
      return;
    }
  }
  
  const selectedOption = templateOptions.value.find(opt => opt.value === newValue);
  if (selectedOption?.template === 'prescription') {
    loadPrescriptionTemplate();
  }
});

watch(() => selectedDbAttribute.value, (newValue) => {
  if (newValue === '__new__') {
    newAttributeName.value = '';
  }
});

const fetchTemplateData = async (id) => {
  try {
    loading.value = true;
    error.value = null;
    const response = await axios.get(`/api/templates/${id}`);
    const templateData = response.data.data || response.data;

  form.value = {
  name: templateData.name || '',
  description: templateData.description || '',
  content: templateData.content || '',
  doctor_id: effectiveDoctorId.value || templateData.doctor?.id || '',
  mime_type: mapMimeTypeToSelectValue(templateData.mime_type || 'consultation'),
  placeholders: templateData.placeholders || []
};

    documentContent.value = templateData.content || '';
    placeholders.value = templateData.placeholders || [];

    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        scanForPlaceholders();
        saveCaretPosition();
      }
    });

  } catch (err) {
    error.value = 'Failed to load template data: ' + (err.response?.data?.message || err.message);
    toaster.error(error.value);
  } finally {
    loading.value = false;
  }
};

watch(() => props.showModal, (newValue) => {
  if (newValue && props.isEdit && props.templateData?.id) {
    setTimeout(() => {
      fetchTemplateData(props.templateData.id);
    }, 50);
  } else if (!newValue) {
    resetForm();
  }
});

watch(() => activeTab.value, (newValue) => {
  if (newValue === 'content') {
    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        scanForPlaceholders();
        restoreCaretPosition();
      }
    });
  }
});

</script>
<template>
  <div class="premium-container">
    
    <div v-if="loading" class="loading-overlay">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div class="premium-editor-header">
      <div class="branding">
        <button  class="btn btn-light bg-primary rounded-pill shadow-sm position-absolute"
              style=" " @click="router.go(-1)">
              <i class="fas fa-arrow-left"></i> Back
          </button>
        <h3 class="header-title ">{{ templateId ? 'Edit' : 'Create' }} Template</h3>
      </div>
    </div>

    <div class="premium-tabs">
      <div class="premium-tab" :class="{ active: activeTab === 'basic' }" @click="switchTab('basic')">
        <i class="fas fa-info-circle me-2"></i>Basic Info
      </div>
      <div class="premium-tab" :class="{ active: activeTab === 'content' }" @click="switchTab('content')">
        <i class="fas fa-file-alt me-2"></i>Content & Placeholders
      </div>
      <div class="premium-tab" :class="{ active: activeTab === 'preview' }" @click="switchTab('preview')">
        <i class="fas fa-eye me-2"></i>Preview
      </div>
    </div>

    <div class="premium-body">
      <div v-if="error" class="premium-alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
      </div>

      <div v-if="activeTab === 'basic'" class="premium-tab-content">
        <div class="premium-card">
          <div class="premium-card-header">
            <i class="fas fa-pencil-alt me-2"></i>Template Details
          </div>
          <div class="premium-card-body">
            <div class="premium-form-group">
              <label for="templateName" class="premium-label">Template Name *</label>
              <input type="text" class="premium-input" id="templateName" v-model="form.name"
                placeholder="Enter a descriptive name for your template" required />
            </div>

            <div class="premium-form-group">
              <label for="templateDescription" class="premium-label">Description</label>
              <textarea class="premium-textarea" id="templateDescription" v-model="form.description"
                placeholder="Add a detailed description of this template's purpose" rows="3"></textarea>
            </div>

            <div class="premium-row">

              <div v-if="!doctorId" class="premium-col">
                <label for="doctorSelect" class="premium-label">Associated Doctor</label>
                <div class="premium-select-wrapper">
                  <select class="premium-select" id="doctorSelect" v-model="form.doctor_id">
                    <option value="">No specific doctor</option>
                    <option v-for="doctor in doctors" :key="doctor.id" :value="doctor.id">
                      {{ doctor.name }}
                    </option>
                  </select>
                  <i class="fas fa-chevron-down"></i>
                </div>
                <div v-if="loading && !doctors.length" class="premium-loading">
                  <span class="premium-spinner"></span>
                  <span class="ms-2">Loading doctors...</span>
                </div>
              </div>

              <div class="premium-col">
                <label for="mimeType" class="premium-label">Document Format</label>
                <div class="premium-select-wrapper">
                  <select class="premium-select" id="mimeType" v-model="form.mime_type">
                    <option v-for="option in templateOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                  <i class="fas fa-chevron-down"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="premium-card mt-4" v-if="!templateId  &&  form.mime_type !== 'A5' && form.mime_type !== 'prescription_type'">
          <div class="premium-card-header">
            <i class="fas fa-cloud-upload-alt me-2"></i>Document Upload
          </div>
          <div class="premium-card-body">
            <div class="premium-form-group">
              <label class="premium-label">Upload Word Document</label>
              <div class="premium-upload-container">
                <input type="file" id="wordFileInput" class="premium-file-input" accept=".docx,.doc"
                  @change="handleFileUploadWithDocxPreview" />
                <label for="wordFileInput" class="premium-file-label">
                  <i class="fas fa-cloud-upload-alt me-2"></i>
                  {{ wordFile ? wordFile.name : 'Drop your Word document here or click to browse' }}
                </label>
              </div>

              <div v-if="uploadProgress > 0" class="premium-progress-container mt-3">
                <div class="premium-progress-text">Uploading: {{ uploadProgress }}%</div>
                <div class="premium-progress">
                  <div class="premium-progress-bar" :style="{ width: uploadProgress + '%' }"></div>
                </div>
              </div>

              <div class="premium-help-text">
                <i class="fas fa-info-circle me-1"></i>
                Upload a Word document to create your template. You'll be able to add placeholders in the next step.
              </div>
            </div>
          </div>
        </div>

        <div class="premium-actions-row">
          <div></div>
          <button type="button" class="btn-premium" @click="switchTab('content')" :disabled="!documentContent">
            Continue to Content <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>

      <div v-if="activeTab === 'content'" class="premium-tab-content">
        <div class="premium-editor-container">

          <div class="premium-editor-main">
            <div class="premium-editor-header-controls">
              <div class="premium-editor-title">Document Editor</div>
              <div class="premium-editor-controls">
                <button type="button" class="btn-premium-control" @click="togglePreview"
                  :class="{ active: previewMode }">
                  <i :class="previewMode ? 'fas fa-edit' : 'fas fa-eye'" class="me-1"></i>
                  {{ previewMode ? 'Edit Mode' : 'Preview' }}
                </button>
              </div>
            </div>

            <div v-if="!previewMode" ref="editorRef" class="premium-document-editor" contenteditable="true"
              @input="handleEditorInput" @mouseup="saveCaretPosition" @keyup="saveCaretPosition"
              @blur="saveCaretPosition" spellcheck="false"></div>
            <div v-else class="premium-document-preview" v-html="documentContent"></div>
          </div>

          <div style="width: 300px;" class="">
            <div class="premium-sidebar-panel">
              <div class="premium-sidebar-header">
                <i class="fas fa-puzzle-piece me-2"></i>Placeholders
              </div>

              <div class="premium-sidebar-content">
                <div>
                  <div class="premium-form-group">
                    <label class="premium-label">Placeholder</label>
                    <div class="premium-select-wrapper">
                      <select class="premium-select" v-model="selectedDbPlaceholder">
                        <option :value="null">Select placeholder</option>
                        <!-- Use allAvailablePlaceholders here -->
                        <option v-for="placeholder in allAvailablePlaceholders" :key="placeholder.id"
                          :value="placeholder">
                          {{ placeholder.name }}
                        </option>
                      </select>
                      <i class="fas fa-chevron-down"></i>
                    </div>
                  </div>

                  <div v-if="selectedDbPlaceholder && selectedDbPlaceholder.isCustom" class="premium-form-group">
                    <label class="premium-label">Custom Placeholder Name</label>
                    <input type="text" class="premium-input" v-model="customPlaceholderName"
                      placeholder="e.g., patient.age" />
                  </div>

                  <!-- Show attributes if not a custom placeholder -->
                  <div v-if="selectedDbPlaceholder && !selectedDbPlaceholder.isCustom" class="premium-form-group">
                    <label class="premium-label">Attribute</label>
                    <div class="premium-select-wrapper">
                      <select class="premium-select" v-model="selectedDbAttribute">
                        <option :value="null">Select attribute</option>
                        <option value="__new__">New Attribute ?</option>

                        <!-- Attributes are loaded based on selectedDbPlaceholder.id -->
                        <option v-for="attribute in dbAttributes[selectedDbPlaceholder.id]" :key="attribute.id"
                          :value="attribute">

                          {{ attribute.name }}
                        </option>
                      </select>
                      <i class="fas fa-chevron-down"></i>
                    </div>
                    <div v-if="loadingattrbute" class="premium-loading-state">
                      <span class="premium-spinner"></span>
                      <span class="ms-2">Loading attributes...</span>
                    </div>
                  </div>

                  <div v-if="selectedDbAttribute === '__new__'" class="premium-form-group mt-3">
                    <label class="premium-label mb-2">
                      Add New Attribute to {{ selectedDbPlaceholder.name }}
                    </label>

                    <div class="d-flex align-items-center mb-2">
                      <input type="text" class="premium-input flex-grow-1 me-2" v-model="newAttributeName"
                        placeholder="New attribute name (e.g., diagnosisCode)" />
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <div class="form-check me-3 p-2">
                        <input type="checkbox" id="textareaCheckbox" class="form-check-input" v-model="textarea" />
                        <label for="textareaCheckbox" class="form-check-label">
                          Textarea
                        </label>
                      </div>

                      <button type="button" class="btn-premium btn-small ml-4" @click="addNewAttribute"
                        :disabled="!newAttributeName.trim()">
                        <i class="fas fa-plus me-1"></i> Add
                      </button>
                    </div>
                  </div>


                </div>

                <button class="btn-premium btn-full-width mt-3" @click="insertPlaceholder" :disabled="!placeholderText">
                  <i class="fas fa-plus me-2"></i>Insert Placeholder
                </button>

                <div v-if="placeholders.length > 0" class="premium-placeholders-list">
                  <div class="premium-list-header">Used Placeholders</div>
                  <div class="premium-placeholder-items">
                    <div v-for="(placeholder, index) in placeholders" :key="index" class="premium-placeholder-item">
                      <span class="premium-placeholder-text">{{ placeholder }}</span>
                      <button class="btn-premium-icon" @click="removePlaceholder(placeholder)"
                        title="Remove placeholder">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="premium-actions-row">
          <button type="button" class="btn-premium-light" @click="switchTab('basic')">
            <i class="fas fa-arrow-left me-2"></i>Back
          </button>

          <button type="button" class="btn-premium" @click="switchTab('preview')">
            Preview Template <i class="fas fa-arrow-right ms-2"></i>
          </button>
        </div>
      </div>

      <div v-if="activeTab === 'preview'" class="premium-tab-content">
        <div class="premium-preview-container">
          <div class="premium-preview-sidebar">
            <div class="premium-card">
              <div class="premium-card-header">
                <i class="fas fa-list-alt me-2"></i>Template Details
              </div>
              <div class="premium-card-body">
                <div class="premium-detail-item">
                  <div class="premium-detail-label">Name</div>
                  <div class="premium-detail-value">{{ form.name }}</div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Description</div>
                  <div class="premium-detail-value">{{ form.description || 'No description provided' }}</div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Associated Doctor</div>
                  <div class="premium-detail-value">
                    {{doctors.find(d => d.id === form.doctor_id)?.name || 'No specific doctor'}}
                  </div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Document Format</div>
                  <div class="premium-detail-value">
                    {{templateOptions.find(m => m.value === form.mime_type)?.label || form.mime_type}}
                  </div>
                </div>

                <div class="premium-detail-item">
                  <div class="premium-detail-label">Placeholders</div>
                  <div class="premium-detail-value">
                    <div v-if="placeholders.length" class="premium-tags">
                      <span v-for="(placeholder, index) in placeholders" :key="index" class="premium-tag">
                        {{ placeholder }}
                      </span>
                    </div>
                    <span v-else class="premium-no-data">No placeholders added</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="premium-preview-main">
            <div class="premium-preview-header">
              <i class="fas fa-file-alt me-2"></i>Document Preview
            </div>
            <div class="premium-document-preview-container">
              <div class="premium-document-preview overflow" v-html="documentContent"></div>
            </div>
          </div>
        </div>

        <div class="premium-actions-row">
          <button type="button" class="btn-premium-light" @click="switchTab('content')">
            <i class="fas fa-arrow-left me-2"></i>Back to Editing
          </button>

          <button type="button" class="btn-premium-success" @click="saveTemplate" :disabled="loading">
            <span v-if="loading" class="premium-spinner white me-2"></span>
            <i v-else class="fas fa-save me-2"></i>
            {{ isEdit ? 'Update' : 'Save' }} Template
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Existing styles remain unchanged */

.premium-container {
  background-color: #f8fafc;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  overflow: hidden;
}

.premium-editor-header {
  background: linear-gradient(135deg, #3a5bb1, #4d78ef);
  color: white;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.branding {
  display: flex;
  align-items: center;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin-left: 90px;

}

.premium-tabs {
  display: flex;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  padding: 0 1.5rem;
}

.premium-tab {
  padding: 1.25rem 1.5rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  transition: all 0.3s;
  display: flex;
  align-items: center;
}

.premium-tab:hover {
  color: #3a5bb1;
}

.premium-tab.active {
  color: #3a5bb1;
  border-bottom-color: #3a5bb1;
}

.premium-body {
  padding: 1.5rem;
}

.premium-tab-content {
  min-height: 600px;
  overflow-y: auto;
}

.premium-card {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
  overflow: auto;
}

.premium-card-header {
  padding: 1rem 1.5rem;
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
}

.premium-card-body {
  padding: 1.5rem;
}

.premium-form-group {
  margin-bottom: 1.25rem;
}

.premium-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #334155;
}

.premium-input,
.premium-textarea,
.premium-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #f8fafc;
  font-size: 1rem;
  transition: all 0.3s;
}

.premium-input:focus,
.premium-textarea:focus,
.premium-select:focus {
  outline: none;
  border-color: #4d78ef;
  box-shadow: 0 0 0 3px rgba(77, 120, 239, 0.1);
}

.premium-textarea {
  resize: vertical;
  min-height: 100px;
}

.premium-select-wrapper {
  position: relative;
}

.premium-select-wrapper i {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
}

.premium-select {
  appearance: none;
  padding-right: 2.5rem;
}

.premium-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.25rem;
}

.premium-col {
  flex: 1;
}

.btn-premium,
.btn-premium-light,
.btn-premium-outline,
.btn-premium-success,
.btn-premium-control {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  border: none;
}

.btn-premium {
  background-color: #3a5bb1;
  color: white;
}

.btn-premium:hover {
  background-color: #2c4890;
}

.btn-premium:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.btn-premium-light {
  background-color: #e2e8f0;
  color: #334155;
}

.btn-premium-light:hover {
  background-color: #cbd5e1;
}

.btn-premium-outline {
  background-color: transparent;
  border: 1px solid white;
  color: white;
}

.btn-premium-outline:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.btn-premium-success {
  background-color: #10b981;
  color: white;
}

.btn-premium-success:hover {
  background-color: #059669;
}

.btn-premium-success:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.btn-premium-control {
  background-color: transparent;
  color: #64748b;
  border: 1px solid #e2e8f0;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn-premium-control:hover {
  background-color: #f8fafc;
}

.btn-premium-control.active {
  background-color: #f1f5f9;
  color: #3a5bb1;
  border-color: #cbd5e1;
}

.btn-premium-icon {
  background-color: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  height: 28px;
  width: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-premium-icon:hover {
  background-color: #f1f5f9;
  color: #ef4444;
}

.btn-full-width {
  width: 100%;
}

/* NEW: Small button style for "Add" attribute */
.btn-premium.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  line-height: 1;
}


.premium-upload-container {
  position: relative;
}

.premium-file-input {
  position: absolute;
  width: 0.1px;
  height: 0.1px;
  opacity: 0;
  overflow: hidden;
  z-index: -1;
}

.premium-file-label {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.5rem;
  background-color: #f8fafc;
  border: 2px dashed #cbd5e1;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  transition: all 0.3s;
  color: #64748b;
}

.premium-file-label:hover {
  background-color: #f1f5f9;
  border-color: #94a3b8;
}

.premium-progress-container {
  margin-top: 1rem;
}

.premium-progress-text {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}

.premium-progress {
  height: 8px;
  background-color: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.premium-progress-bar {
  height: 100%;
  background: linear-gradient(to right, #3a5bb1, #4d78ef);
  border-radius: 4px;
}

.premium-help-text {
  margin-top: 1rem;
  color: #64748b;
  font-size: 0.875rem;
}

.premium-editor-container {
  display: flex;
  gap: 1.5rem;
  height: 600px;
}

.premium-editor-main {
  width: 70%;
  display: flex;
  flex-direction: column;
}

.premium-editor-sidebar {
  width: 30%;

  width: 400px;
}

.premium-editor-header-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.premium-editor-title {
  font-weight: 600;
  color: #334155;
}

.premium-editor-controls {
  display: flex;
  gap: 0.5rem;
}

.premium-document-editor,
.premium-document-preview {
  flex: 2;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  overflow-y: auto;
  position: relative;
}

.premium-document-editor:focus {
  outline: none;
}

.premium-document-preview {
  background-color: #f8fafc;
}

.premium-sidebar-panel {
  flex: 2;
  background-color: #fff;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.premium-sidebar-header {
  padding: 1rem;
  background-color: #f1f5f9;
  font-weight: 600;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
}

.premium-sidebar-content {
  padding: 1rem;
  overflow-y: auto;
  flex: 1;
}

.premium-placeholders-list {
  margin-top: 1.5rem;
}

.premium-list-header {
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.premium-placeholder-items {
  max-height: 250px;
  overflow-y: auto;
}

.premium-placeholder-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.5rem;
  background-color: #f1f5f9;
  border-radius: 6px;
}

.premium-placeholder-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 80%;
  font-size: 0.875rem;
}

.premium-preview-container {
  display: flex;
  gap: 1.5rem;
  height: 600px;
}

.premium-preview-sidebar {
  flex: 1;
  max-width: 450px;

}

.premium-preview-main {
  flex: 2;
  display: flex;
  flex-direction: column;
}

.premium-preview-header {
  font-weight: 600;
  color: #334155;
  margin-bottom: 1rem;
}

.premium-document-preview-container {
  flex: 1;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #fff;
  overflow-y:auto;
  position: relative;
}

.premium-detail-item {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.premium-detail-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.premium-detail-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.premium-detail-value {
  color: #334155;
  word-break: break-word;
}

.premium-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.premium-tag {
  background-color: #eef2ff;
  color: #4f46e5;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.premium-no-data {
  color: #94a3b8;
  font-style: italic;
}

.premium-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.premium-loading {
  display: flex;
  align-items: center;
  color: #64748b;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.premium-spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 50%;
  border-top-color: #3a5bb1;
  animation: spin 1s ease-in-out infinite;
}

.premium-spinner.white {
  border-color: rgba(255, 255, 255, 0.3);
  border-top-color: white;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.premium-actions-row {
  display: flex;
  justify-content: space-between;
  margin-top: 2rem;
}

:deep(.template-placeholder) {
  background-color: #eef2ff;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px dashed #4f46e5;
  font-weight: 600;
  color: #4338ca;
  display: inline-block;
  margin: 2px;
  cursor: default;
  user-select: none;
}

:deep(.document-content) {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  line-height: 1.6;
  color: #333;

  p {
    margin: 0 0 1rem 0;
  }

  .text-center {
    text-align: center;
  }

  .text-right {
    text-align: right;
  }

  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    margin: 1.5rem 0 1rem;
    font-weight: 600;
    color: #059669;
  }

  h1 {
    font-size: 2rem;
  }

  h2 {
    font-size: 1.75rem;
  }

  h3 {
    font-size: 1.5rem;
  }

  ul,
  ol {
    margin: 1rem 0;
    padding-left: 2rem;
  }

  li {
    margin-bottom: 0.5rem;
  }

  table {
    border-collapse: collapse;
    width: 100%;
    margin: 1rem 0;
    background-color: #fff;
  }

  th,
  td {
    border: 1px solid #e2e8f0;
    padding: 0.75rem;
    text-align: left;
  }

  th {
    background-color: #f8fafc;
    font-weight: 600;
    color: #334155;
  }

  img.document-image {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: 1rem 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  strong {
    font-weight: 700;
  }

  em {
    font-style: italic;
  }
}

/* Add these styles */
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

/* Utility to ensure flex items distribute space evenly */
.d-flex {
  display: flex;
}

.me-2 {
  margin-right: 0.5rem;
}

/* Add to your existing styles */
:deep(.premium-document-editor),
:deep(.premium-document-preview) {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
  line-height: 1.6;
  color: #333;
}

:deep(.premium-document-editor) {
  white-space: pre-wrap;
}

:deep(.premium-document-editor *),
:deep(.premium-document-preview *) {
  max-width: 100%;
}

:deep(.premium-document-editor table),
:deep(.premium-document-preview table) {
  border-collapse: collapse;
  margin: 1rem 0;
  width: 100%;
}

:deep(.premium-document-editor td),
:deep(.premium-document-editor th),
:deep(.premium-document-preview td),
:deep(.premium-document-preview th) {
  border: 1px solid #e2e8f0;
  padding: 0.75rem;
}

:deep(.premium-document-editor img),
:deep(.premium-document-preview img) {
  max-width: 100%;
  height: auto;
}

:deep(.premium-document-editor p),
:deep(.premium-document-preview p) {
  margin: 1rem 0;
}

:deep(.premium-document-editor ul),
:deep(.premium-document-editor ol),
:deep(.premium-document-preview ul),
:deep(.premium-document-preview ol) {
  margin: 1rem 0;
  padding-left: 2rem;
}

</style>