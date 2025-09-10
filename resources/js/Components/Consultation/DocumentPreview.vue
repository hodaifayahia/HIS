<template>
  <div class="premium-document-editor">
    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="upload-progress">
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
      </div>
      <div class="progress-text">{{ uploadProgress }}% - Processing document...</div>
    </div>

    <div class="premium-preview-main">
      <div class="premium-preview-header">
        <div class="premium-preview-title">
          <h3 class="title-text">
            Document Preview
            <span v-if="isTemplate" class="template-badge">Template</span>
          </h3>
        </div>

        <div class="premium-preview-actions">
          <button @click="refreshPreview" class="premium-btn premium-refresh-btn">
            🔄 Refresh
          </button>
          <button
            @click="toggleEditMode"
            class="premium-btn premium-edit-btn"
            :class="{ active: !previewMode }"
          >
            <span v-if="previewMode">✏️ Edit</span>
            <span v-else>✅ Done</span>
          </button>
          <button
            @click="generatePdf"
            class="premium-btn premium-pdf-btn"
            :disabled="!documentContent || isGeneratingPdf"
          >
            <span v-if="isGeneratingPdf">Saving...</span>
            <span v-else>💾 Save </span>
          </button>
        </div>
      </div>
      
      <div v-if="!previewMode" class="premium-editor-toolbar">
        <div class="toolbar-group">
          <button @click="formatText('bold')" class="toolbar-btn">
            <b>B</b>
          </button>
          <button @click="formatText('italic')" class="toolbar-btn">
            <i>I</i>
          </button>
          <button @click="formatText('underline')" class="toolbar-btn">
            <u>U</u>
          </button>
        </div>
        <div class="toolbar-group">
          <select @change="changeHeading($event.target.value)" class="heading-select">
            <option value="p">Paragraph</option>
            <option value="h1">Heading 1</option>
            <option value="h2">Heading 2</option>
            <option value="h3">Heading 3</option>
            <option value="h4">Heading 4</option>
          </select>
        </div>
        <div class="toolbar-group">
          <button @click="insertList(false)" class="toolbar-btn">
            • List
          </button>
          <button @click="insertList(true)" class="toolbar-btn">
            1. List
          </button>
        </div>
        <div class="toolbar-group">
          <button @click="insertLink" class="toolbar-btn">
            🔗 Link
          </button>
        </div>
      </div>

      <div class="premium-document-preview-container">
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading document preview...</p>
        </div>

        <div v-else-if="!documentContent" class="empty-state">
          <div class="empty-icon">📄</div>
          <h3>No Document Loaded</h3>
          <p>Upload a DOCX file or add content to get started</p>
        </div>

        <div v-else class="document-wrapper">
          <div
            v-if="previewMode"
            class="document-preview"
            v-html="documentContent"
          ></div>

          <div
            v-else
            ref="editorRef"
            class="document-editor"
            contenteditable="true"
            @input="handleEditorInput"
            @keydown="handleKeydown"
            spellcheck="false"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import { useToastr } from '../../Components/toster';
import { renderAsync } from 'docx-preview';
import axios from 'axios';
import { useSweetAlert } from '../../Components/useSweetAlert';
import { saveAs } from 'file-saver';

const toaster = useToastr();
const props = defineProps({
  previewContent: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  },
  selectedTemplates: {
    type: Object,
  },

  templateData: {
    type: Object,
    default: () => ({})
  },
  appointmentId: {
    type: [String, Number],
    required: true
  },
  isTemplate: {
    type: Boolean,
    default: false
  },
  MimeType: {
    type: String,
  },
  codebash: {
    type: String,
    default: ''
  },
  doctorId: {
    type: [String, Number],
    required: true
  },
  patientId: {
    type: [String, Number],
    required: true
  },
  folderid: {
    type: Number,
    required: false
  }
});

const emit = defineEmits(['update:previewContent', 'refresh', 'documentGenerated', 'documentSaved']);

const previewMode = ref(true);
const documentContent = ref(props.previewContent);
const originalContent = ref(props.previewContent); // Store original content
const editorRef = ref(null);
const wordFile = ref(null);
const uploadProgress = ref(0);
const templateFile = ref(null);
const isTemplate = ref(false);
const isGeneratingPdf = ref(false);
const documentHistory = ref(null);

const barcodeBase64 = ref('');
const codebashValue = ref(''); // <-- Add this

const getbashcode = async () => {
  try {
    const response = await axios.get(`/api/consultations/by-appointment/${props.appointmentId}`, {
      params: {
        isdoneconsultation: false
      }
    });
    barcodeBase64.value = response.data.barcode_base64 || null;
    codebashValue.value = response.data.data?.codebash || ''; // <-- Set codebash from backend
  } catch (error) {
    console.error('Error fetching CodeBash:', error);
    toaster.error('Failed to load CodeBash');
  }
}
// Modal state
const showTypeDialog = ref(false);
const pendingPdfAction = ref(null);
const docTypeLabel = ref('');

const getDocTypeLabel = (mimeType) => {
  if (mimeType === 'prescription_type' || mimeType === 'consulation') return 'consulation';
  return 'prescription';
};

const handleSaveClick = () => {
  let mimeType = props.MimeType || props.selectedTemplates?.mime_type || '';
  docTypeLabel.value = mimeType;
  showTypeDialog.value = true;
  pendingPdfAction.value = () => generatePdf();
};

const closeTypeDialog = () => {
  showTypeDialog.value = false;
  pendingPdfAction.value = null;
};

// Watch for changes in the previewContent prop
watch(() => props.previewContent, (newValue) => {
  if (previewMode.value) {
    documentContent.value = newValue;
    originalContent.value = newValue; // Update original content when prop changes
  }
});

// Update the handleDocumentSaved function
const handleDocumentSaved = () => {
  // Access the ref's value property
  if (documentHistory.value) {
    documentHistory.value.refresh();
  }
};

const renderDocxToHtml = async (blob) => {
  try {
    const tempContainer = document.createElement('div');
    document.body.appendChild(tempContainer);

    await renderAsync(blob, tempContainer, null, {
      className: 'docx-wrapper',
      inWrapper: false,
      ignoreWidth: false,
      ignoreHeight: false,
      ignoreFonts: false,
      breakPages: false,
      ignoreLastRenderedPageBreak: false,
      experimental: false,
      trimXmlDeclaration: false,
      useBase64URL: true,
      renderHeaders: true,
      renderFooters: true,
      renderFootnotes: true,
      renderEndnotes: true
    });

    let renderedHTML = tempContainer.innerHTML;
    renderedHTML = cleanDocxPreviewHtml(renderedHTML);
    renderedHTML = preserveWordFormatting(renderedHTML);

    document.body.removeChild(tempContainer);

    const finalContent = `
      <div class="document-content" contenteditable="true">
        ${renderedHTML}
      </div>
    `;

    documentContent.value = finalContent;
    originalContent.value = finalContent; // Store the original rendered content

  } catch (error) {
    console.error('Docx rendering error:', error);
  }
};

const generateDocumentWithData = async (newData) => {
  if (!templateFile.value) return;

  try {
    const PizZip = (await import('pizzip')).default;
    const Docxtemplater = (await import('docxtemplater')).default;

    const content = await readFileAsArrayBuffer(templateFile.value);
    const zip = new PizZip(content);
    const doc = new Docxtemplater(zip, {
      paragraphLoop: true,
      linebreaks: true,
    });

    doc.setData(newData);
    doc.render();

    const buffer = doc.getZip().generate({ type: 'arraybuffer' });
    const blob = new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    });

    await renderDocxToHtml(blob);

    return blob;

  } catch (error) {
    console.error('Document generation error:', error);
    throw error;
  }
};

const downloadGeneratedDocument = async (data = documentContent.value) => {
  try {
    const blob = await generateDocumentWithData(data);
    saveAs(blob, `generated-document-${Date.now()}.docx`);
  } catch (error) {
    console.error('Download failed:', error);
    toaster.error('Failed to generate document. Please check your template data.');
  }
};
const confirmTypeDialog = () => {
  showTypeDialog.value = false;
  if (pendingPdfAction.value) pendingPdfAction.value();
  pendingPdfAction.value = null;
};
/**
 * Generates a PDF from the preview content using a backend service.
 */
const generatePdf = async () => {
  if (!documentContent.value || documentContent.value.includes('premium-empty-state')) {
    return;
  }

  await getbashcode();

  let mimeType = props.MimeType || '';
  const htmlToSend = ref('');
  isGeneratingPdf.value = true;

  try {
  if (mimeType === 'prescription_type') {
    // For prescriptions, inject the barcode header and wrap content properly
    const barcodeHeader = `
        <div class="codebash-header">
            ${props.codebash}
            <img src="data:image/png;base64,${barcodeBase64.value}" alt="Barcode" class="barcode-image" />
        </div>`;
    
    // Wrap the prescription content in a proper container for text wrapping
    // You can add dynamic content here from your props or data
    const prescriptionContentWrapper = `
        <div class="prescription-content">
            ${props.prescriptionContent || '<!-- Prescription content will be injected here -->'}
        </div>`;
    
    let modifiedContent = documentContent.value;
    
    // Add barcode header to body for prescription
    if (modifiedContent.includes('<body>')) {
        modifiedContent = modifiedContent.replace('<body>', `<body>${barcodeHeader}`);
    } else {
        // If no body tag, wrap content with body and add barcode
        modifiedContent = `<body>${barcodeHeader}${modifiedContent}</body>`;
    }
    
    // Find the prescription-title div and add content wrapper after it
    const prescriptionTitleRegex = /(<div class="prescription-title"><\/div>)/;
    
    if (prescriptionTitleRegex.test(modifiedContent)) {
        // Replace the prescription title and add the content wrapper
        modifiedContent = modifiedContent.replace(
            prescriptionTitleRegex, 
            `$1${prescriptionContentWrapper}`
        );
    } else {
        // Fallback: add wrapper before the barcode section
        const barcodeRegex = /(<div class="codebash-header">)/;
        if (barcodeRegex.test(modifiedContent)) {
            modifiedContent = modifiedContent.replace(
                barcodeRegex, 
                `${prescriptionContentWrapper}$1`
            );
        }
    }
    
    htmlToSend.value = modifiedContent;
} else {
    // Handle other document types


    // Handle other document types

      // Original logic for other document types
      let css = '';
      for (const sheet of document.styleSheets) {
        try {
          const rules = sheet.cssRules || sheet.rules;
          for (const rule of rules) {
            css += rule.cssText;
          }
        } catch (e) {
          console.warn("Could not read stylesheet:", e);
        }
      }

      htmlToSend.value = `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document</title>
  <style>
    ${css}

    body, html { 
      background-color: #fff !important; 
      margin: 0;
      padding: 0;
      word-wrap: break-word; 
      box-shadow: none !important;
      border: none !important;
    }

    #pdf-container, 
    #pdf-container *, 
    .premium-preview-main,
    .premium-document-editor {
      box-shadow: none !important;
      border-radius: 0 !important;
      border: none !important;
    }

    #pdf-container > div {
      border: none !important;
      box-shadow: none !important;
    }

    #pdf-container img {
      max-width: 100% !important;
      height: auto !important;
    }

    #pdf-container table,
    #pdf-container figure,
    #pdf-container pre {
      page-break-inside: avoid !important;
    }

    #pdf-container th, #pdf-container td {
      border: 1px solid #bbb !important; 
      padding: 8px !important;
      color: #333 !important;
    }

    .codebash-header {
      position: absolute;
      top: 10px;
      right: 20px;
      padding: 6px 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      z-index: 1000;
      background: #fff !important; /* Ensure white background */
    }
    .barcode-image {
      max-width: 100%;
      height: 40px;
      display: block;
    }
    /* Other styles */
  </style>
</head>
<body>
  <div class="codebash-header">
    <img src="data:image/png;base64,${barcodeBase64.value}" alt="Barcode" />
    <div>${props.codebash}</div>
  </div>

  <div id="pdf-container">
    ${documentContent.value}
  </div>
</body>
</html>
`;
    }

    const response = await axios.post(`/api/consultation/${props.patientId}/save-pdf`, {
      html: htmlToSend.value,
      mime_type: mimeType,
      template_ids: JSON.stringify(props.selectedTemplates),
      patient_id: props.patientId,
      appointment_id: props.appointmentId,
      folder_id: props.folderid,
      doctorId: props.doctorId
    });

    if (response.data.success) {
      toaster.success('PDF generated and saved successfully!');
      emit('document-saved', response.data);
    } else {
      throw new Error(response.data.message || 'Failed to save PDF');
    }

  } catch (err) {
    console.error('PDF Save Error:', err);
    toaster.error(err.response?.data?.message || 'Failed to save PDF');
  } finally {
    isGeneratingPdf.value = false;
  }
};

// Utility function to read file as array buffer
const readFileAsArrayBuffer = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => resolve(e.target.result);
    reader.onerror = reject;
    reader.readAsArrayBuffer(file);
  });
};

const preserveWordFormatting = (htmlContent) => {
  const parser = new DOMParser();
  const doc = parser.parseFromString(htmlContent, 'text/html');

  const processElements = (elements, processor) => {
    Array.from(elements).forEach(processor);
  };

  processElements(doc.querySelectorAll('table'), (table) => {
    table.style.borderCollapse = 'collapse';
    table.style.width = '100%';
    table.style.margin = '10px 0';
    table.style.border = '1px solid #d1d5db';
    table.setAttribute('data-word-table', 'true');
  });

  processElements(doc.querySelectorAll('td, th'), (cell) => {
    cell.style.border = '1px solid #d1d5db';
    if (!cell.style.padding) {
      cell.style.padding = '8px';
    }
    cell.setAttribute('data-word-cell', 'true');
  });

  processElements(doc.querySelectorAll('img'), (img) => {
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.style.display = 'block';
    img.style.margin = '10px auto';
    img.setAttribute('data-word-image', 'true');
  });

  processElements(doc.querySelectorAll('ul, ol'), (list) => {
    list.style.marginLeft = '20px';
    list.style.marginBottom = '15px';
    list.setAttribute('data-word-list', 'true');
  });

  processElements(doc.querySelectorAll('h1, h2, h3, h4, h5, h6'), (heading) => {
    const level = heading.tagName.toLowerCase();
    heading.setAttribute('data-word-heading', level);

    if (!heading.style.fontWeight) {
      heading.style.fontWeight = 'bold';
    }
    if (!heading.style.marginBottom) {
      heading.style.marginBottom = '10px';
    }
  });

  processElements(doc.querySelectorAll('p'), (para) => {
    para.setAttribute('data-word-paragraph', 'true');
    if (!para.style.marginBottom) {
      para.style.marginBottom = '10px';
    }
  });

  return doc.body.innerHTML;
};

const cleanDocxPreviewHtml = (html) => {
  html = html.replace(/style="[^"]*(?:position|top|left|width|height|z-index|min-height)[^"]*"/g, '');
  html = html.replace(/<[^\/>][^>]*>\s*<\/[^>]+>/g, '');
  html = html.replace(/class="(?!docx-)[^"]*"/g, '');
  html = html.replace(/(?<!data-word-)data-[^=]+="[^"]*"/g, '');
  html = html.replace(/<\/p>\s*<p/g, '</p><p');
  html = html.replace(/\s+/g, ' ');
  html = html.replace(/>\s+</g, '><');

  return html;
};

const formatText = (command, value = null) => {
  if (!editorRef.value) return;

  document.execCommand(command, false, value);
  editorRef.value.focus();

  // Don't apply preserveWordFormatting during editing to maintain original structure
  const rawContent = editorRef.value.innerHTML;
  documentContent.value = rawContent;
  emit('update:previewContent', documentContent.value);
};

const insertList = (ordered = false) => {
  const command = ordered ? 'insertOrderedList' : 'insertUnorderedList';
  formatText(command);
};

const changeHeading = (level) => {
  if (level === 'p') {
    formatText('formatBlock', 'p');
  } else {
    formatText('formatBlock', `h${level}`);
  }
};

const insertLink = () => {
  const url = prompt('Enter URL:');
  if (url) {
    formatText('createLink', url);
  }
};

const toggleEditMode = () => {
  if (previewMode.value) {
    previewMode.value = false;
    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        editorRef.value.focus();
      }
    });
  } else {
    saveEditedContent();
  }
};

const saveEditedContent = () => {
  if (editorRef.value) {
    // Save the raw content without applying preserveWordFormatting
    const rawContent = editorRef.value.innerHTML;
    documentContent.value = rawContent;
    emit('update:previewContent', documentContent.value);
  }
  previewMode.value = true;
};

const refreshPreview = () => {
  // Reset to original content when refreshing
  documentContent.value = originalContent.value;
  emit('update:previewContent', documentContent.value);
  emit('refresh');
};

const handleKeydown = (event) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'b':
        event.preventDefault();
        formatText('bold');
        break;
      case 'i':
        event.preventDefault();
        formatText('italic');
        break;
      case 'u':
        event.preventDefault();
        formatText('underline');
        break;
      case 's':
        event.preventDefault();
        saveEditedContent();
        break;
    }
  }
};

const handleEditorInput = () => {
  if (editorRef.value && !previewMode.value) {
    // Don't apply preserveWordFormatting during real-time editing
    const rawContent = editorRef.value.innerHTML;
    documentContent.value = rawContent;
    emit('update:previewContent', documentContent.value);
  }
};

onMounted(() => {
  documentContent.value = props.previewContent;
  originalContent.value = props.previewContent; // Store original content on mount
});

// Expose methods for parent component
defineExpose({
  generateDocumentWithData,
  downloadGeneratedDocument,
  generatePdf,
});
</script>

<style scoped>
.premium-document-editor {
  width: 100%;
  max-width: 100%;
  min-height: 1000px; /* Use min-height instead of height */
  /* Remove overflow: hidden; */
}
.upload-progress {
  padding: 1rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 1rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #10b981);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  color: #64748b;
  text-align: center;
}

.premium-preview-main {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.premium-preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
  gap: 1rem;
}

.premium-preview-title .title-text {
  font-size: 1.1rem;
  font-weight: 600;
  color: #334155;
  margin: 0;
  display: flex;
  align-items: center;
}

.template-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  margin-left: 0.5rem;
  font-weight: 500;
}

.premium-preview-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  overflow: hidden;
}

.premium-upload-btn, .premium-download-btn {
  background-color: #10b981;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.premium-upload-btn:hover, .premium-download-btn:hover {
  background-color: #059669;
  transform: translateY(-1px);
}

.premium-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.premium-edit-btn {
  background-color: #8b5cf6;
  color: white;
}

.premium-edit-btn:hover, .premium-edit-btn.active {
  background-color: #7c3aed;
}

.premium-refresh-btn {
  background-color: #6b7280;
  color: white;
}

.premium-refresh-btn:hover {
  background-color: #4b5563;
}

.premium-word-btn {
  background-color: #2b579a;
  color: white;
}

.premium-word-btn:hover {
  background-color: #1e3f6f;
}

.premium-pdf-btn {
  background-color: #dc2626;
  color: white;
}

.premium-pdf-btn:hover {
  background-color: #b91c1c;
}

.premium-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* New toolbar styling */
.premium-editor-toolbar {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  background-color: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
}

.toolbar-group {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.toolbar-btn {
  padding: 0.375rem 0.75rem;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.toolbar-btn:hover {
  background-color: #f3f4f6;
  border-color: #9ca3af;
}

.save-btn {
  background-color: #10b981;
  color: white;
  border-color: #10b981;
}

.save-btn:hover {
  background-color: #059669;
}

.heading-select {
  padding: 0.375rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: white;
  font-size: 0.875rem;
}

.premium-document-preview-container {
  padding: 2rem;
  max-height: 70vh;
  overflow-y: auto;
  background-color: #fff;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: #64748b;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.document-wrapper {
  min-height: 400px;
}

.document-preview, .document-editor {
  font-family: 'Times New Roman', serif;
  font-size: 12pt;
  line-height: 1.6;
  color: #000;
  word-wrap: break-word;
}

.document-editor {
  border: 2px dashed #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  min-height: 400px;
  outline: none;
}

.document-editor:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Document styling for both preview and editor */
.document-preview :deep(h1),
.document-preview :deep(h2),
.document-preview :deep(h3),
.document-preview :deep(h4),
.document-preview :deep(h5),
.document-preview :deep(h6),
.document-editor h1,
.document-editor h2,
.document-editor h3,
.document-editor h4,
.document-editor h5,
.document-editor h6 {
  font-weight: bold;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1f2937;
}

.document-preview :deep(h1), .document-editor h1 { font-size: 24px; }
.document-preview :deep(h2), .document-editor h2 { font-size: 20px; }
.document-preview :deep(h3), .document-editor h3 { font-size: 18px; }
.document-preview :deep(h4), .document-editor h4 { font-size: 16px; }

.document-preview :deep(p),
.document-editor p {
  margin: 0 0 1rem 0;
  text-align: justify;
}

.document-preview :deep(table),
.document-editor table {
  border-collapse: collapse;
  width: 100%;
  margin: 1rem 0;
  border: 1px solid #d1d5db; /* Consistent border */
}

.document-preview :deep(td),
.document-preview :deep(th),
.document-editor td,
.document-editor th {
  border: 1px solid #d1d5db; /* Consistent border */
  padding: 0.75rem;
  text-align: left;
  vertical-align: top;
}

.document-preview :deep(th),
.document-editor th {
  background-color: #f9fafb;
  font-weight: bold;
}

.document-preview :deep(img),
.document-editor img {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1rem auto;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.document-preview :deep(ul),
.document-preview :deep(ol),
.document-editor ul,
.document-editor ol {
  margin: 1rem 0;
  padding-left: 2rem;
}

.document-preview :deep(li),
.document-editor li {
  margin-bottom: 0.5rem;
}

.document-preview :deep(blockquote),
.document-editor blockquote {
  margin: 1rem 0;
  padding: 1rem;
  background-color: #f8fafc;
  border-left: 4px solid #3b82f6;
  font-style: italic;
}

.document-preview :deep(code),
.document-editor code {
  background-color: #f1f5f9;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
}

.document-preview :deep(pre),
.document-editor pre {
  background-color: #f1f5f9;
  padding: 1rem;
  border-radius: 8px;
  overflow-x: auto;
  margin: 1rem 0;
}

.document-preview :deep(pre code),
.document-editor pre code {
  background: none;
  padding: 0;
}

/* Responsive design */
@media (max-width: 768px) {
  .premium-preview-header {
    flex-direction: column;
    align-items: stretch;
  }

  .premium-preview-actions {
    justify-content: center;
  }

  .premium-editor-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .toolbar-group {
    justify-content: center;
  }

  .premium-document-preview-container {
    padding: 1rem;
  }
}

/* Print styles */
@media print {
  .premium-preview-header,
  .premium-editor-toolbar {
    display: none;
  }

  .premium-document-preview-container {
    max-height: none;
    overflow: visible;
    padding: 0;
  }

  .document-preview,
  .document-editor {
    font-size: 12pt;
    line-height: 1.5;
  }
}

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.3);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-dialog {
  background: #fff;
  border-radius: 8px;
  padding: 2rem 2.5rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  min-width: 320px;
  max-width: 90vw;
  text-align: center;
}
.modal-actions {
  margin-top: 1.5rem;
  display: flex;
  gap: 1rem;
  justify-content: center;
}
.modal-overlay {
  z-index: 99999 !important; /* Extremely high z-index */
  pointer-events: auto !important; /* Ensure clicks pass through overlay */
}

.modal-dialog {
  z-index: 100000 !important; /* Higher than overlay */
  pointer-events: auto !important; /* Ensure clicks are active on dialog */
}

.modal-btn {
  pointer-events: auto !important; /* Ensure clicks are active on buttons */
  z-index: 100001 !important; /* Highest for buttons */
}
.modal-btn {
  padding: 0.5rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  font-weight: 500;
}
.modal-btn.confirm {
  background: #10b981;
  color: #fff;
}
.modal-btn.cancel {
  background: #e5e7eb;
  color: #374151;
}
</style>