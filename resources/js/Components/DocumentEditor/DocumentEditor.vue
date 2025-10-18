<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import { useToastr } from '../../Components/toster'; // Assuming this path is correct
// import { useSweetAlert } from '../../Components/useSweetAlert'; // Uncomment if needed

import DocumentPreview from '../DocumentEditor/DocumentPreview.vue';
import DocumentToolbar from '../DocumentEditor/DocumentToolbar.vue';
import DocumentUploader from '../DocumentEditor/DocumentUploader.vue';

import { useDocumentConversion } from '../composables/useDocumentConversion'; // Adjust path as necessary
import { useTemplateProcessing } from '../composables/useTemplateProcessing';
import { useDocumentActions } from '../composables/useDocumentActions';

const toaster = useToastr();
// const sweetAlert = useSweetAlert(); // Uncomment if needed

const props = defineProps({
  previewContent: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  },
  selectedTemplates: { // Kept for completeness, but not directly used in this breakdown yet
    type: Array,
    default: () => []
  },
  templateData: {
    type: Object,
    default: () => ({})
  },
  patientId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['update:previewContent', 'refresh', 'documentGenerated']);

const previewMode = ref(true);
const documentContent = ref(props.previewContent);
const editorRef = ref(null); // Still needed for contenteditable div reference in DocumentPreview
const templateFile = ref(null);
const isTemplate = ref(false);
const uploadProgress = ref(0); // Managed by DocumentUploader now, but reflected here

// Use composables
const {
  renderDocxToHtml,
  generateWordDocument,
  generatePdfDocument,
  cleanDocxPreviewHtml,
  preserveWordFormatting,
  convertImagesToBase64, // Exposed from conversion for PDF generation
  waitForImagesToLoad, // Exposed from conversion for PDF generation
} = useDocumentConversion(toaster); // Pass toaster if needed inside composable

const {
  detectTemplateFile,
  processTemplateFile, // Renamed from handleTemplateFile for clarity
  extractPlaceholders,
  generateSampleData,
  generateDocumentWithData,
} = useTemplateProcessing(toaster, renderDocxToHtml, cleanDocxPreviewHtml, preserveWordFormatting); // Pass dependencies

const {
  downloadDocument,
  printDocument,
  savePdfToBackend,
} = useDocumentActions(toaster); // Pass patientId when calling savePdfToBackend

// Watch for changes in the previewContent prop
watch(() => props.previewContent, (newValue) => {
  if (previewMode.value) {
    documentContent.value = newValue;
  }
});

// --- Document Uploader Handlers ---
const handleFileUploaded = async ({ file, progress }) => {
  uploadProgress.value = progress; // Update local progress based on uploader's event

  const isTemplateFile = await detectTemplateFile(file);
  if (isTemplateFile) {
    isTemplate.value = true;
    templateFile.value = file;
    // Process template file and get rendered HTML
    const { renderedHtml, placeholders, renderedData } = await processTemplateFile(file, props.templateData);
    documentContent.value = renderedHtml;
    emit('documentGenerated', {
      type: 'template',
      placeholders,
      templateFile: file,
      renderedData: renderedData
    });
  } else {
    isTemplate.value = false;
    // For regular docx, use renderDocxToHtml from useDocumentConversion
    const docxBlob = new Blob([await file.arrayBuffer()], { type: file.type });
    documentContent.value = await renderDocxToHtml(docxBlob);
  }
  uploadProgress.value = 100; // Indicate completion
};

// --- Editor Mode Handlers ---
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
    const rawContent = editorRef.value.innerHTML;
    const preservedContent = preserveWordFormatting(rawContent);
    documentContent.value = preservedContent;
    emit('update:previewContent', documentContent.value);
  }
  previewMode.value = true;
};

const handleEditorInput = (newContent) => {
  // DocumentPreview emits the raw content, clean and preserve it here
  const preservedContent = preserveWordFormatting(newContent);
  documentContent.value = preservedContent;
  emit('update:previewContent', documentContent.value);
};

const handleEditorKeydown = (event) => {
  // This is delegated to DocumentPreview now
  // For global shortcuts like Ctrl+S, you might keep a listener here or in a higher component
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 's':
        event.preventDefault();
        saveEditedContent();
        break;
      // Other global shortcuts can be handled here or in DocumentToolbar
    }
  }
};


// --- Toolbar Formatting Handlers ---
const handleFormatText = (command, value = null) => {
  // editorRef is passed to DocumentPreview which directly handles execCommand
  // We just need to ensure the parent's documentContent state is updated
  if (editorRef.value) {
    document.execCommand(command, false, value);
    editorRef.value.focus();
    handleEditorInput(editorRef.value.innerHTML); // Trigger update
  }
};

const handleChangeHeading = (level) => {
  handleFormatText('formatBlock', level === 'p' ? 'p' : `h${level}`);
};

const handleInsertList = (ordered = false) => {
  handleFormatText(ordered ? 'insertOrderedList' : 'insertUnorderedList');
};

const handleInsertLink = () => {
  const url = prompt('Enter URL:');
  if (url) {
    handleFormatText('createLink', url);
  }
};

// --- Document Action Handlers ---
const handleGenerateWord = async () => {
  const contentToGenerate = documentContent.value;
  if (!contentToGenerate) {
    toaster.error('No content to generate Word document from');
    return;
  }
  await generateWordDocument(contentToGenerate);
};

const handleGeneratePdf = async () => {
  const contentToGenerate = documentContent.value;
  if (!contentToGenerate) {
    toaster.error('No content to generate PDF from');
    return;
  }
  // This now uses the composable which will include styling and image conversion
  await generatePdfDocument(contentToGenerate, convertImagesToBase64, waitForImagesToLoad);
};

const handlePrintDocument = async () => {
  const contentToPrint = documentContent.value;
  if (!contentToPrint) {
    toaster.error('No content to print');
    return;
  }
  // Reuse generatePdfDocument from composable
  const pdfBlob = await generatePdfDocument(contentToPrint, convertImagesToBase64, waitForImagesToLoad);
  if (pdfBlob) {
    printDocument(pdfBlob);
  }
};

const handleSavePdfToBackend = async () => {
  const contentToSave = documentContent.value;
  if (!contentToSave) {
    toaster.error('No content to save as PDF');
    return;
  }
  const pdfBlob = await generatePdfDocument(contentToSave, convertImagesToBase64, waitForImagesToLoad,true);
  if (pdfBlob) {
    await savePdfToBackend(pdfBlob, props.patientId);
  }
};

const handleDownloadGeneratedDocument = async (data = props.templateData) => {
  if (!templateFile.value) {
    toaster.error('No template file to download from.');
    return;
  }
  try {
    const blob = await generateDocumentWithData(templateFile.value, data);
    downloadDocument(blob, `generated-template-${Date.now()}.docx`);
  } catch (error) {
    console.error('Download failed:', error);
    toaster.error('Failed to generate document for download. Please check your template data.');
  }
};

const refreshPreview = () => {
  emit('refresh');
};

onMounted(() => {
  documentContent.value = props.previewContent;
});

// Expose methods for parent component if needed
defineExpose({
  generateDocumentWithData: handleDownloadGeneratedDocument,
  generateWordDocument: handleGenerateWord,
  generatePdfDocument: handleGeneratePdf,
  printDocument: handlePrintDocument,
  savePdfToBackend: handleSavePdfToBackend,
});
</script>

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
          <DocumentUploader @file-uploaded="handleFileUploaded" />

          <button
            @click="toggleEditMode"
            class="premium-btn premium-edit-btn"
            :class="{ 'active': !previewMode }"
          >
            {{ previewMode ? '✏️ Edit' : '👁️ Preview' }}
          </button>

          <button @click="refreshPreview" class="premium-btn premium-refresh-btn">
            🔄 Refresh
          </button>

          <button
            @click="handleGeneratePdf"
            class="premium-btn premium-pdf-btn"
            :disabled="!documentContent"
          >
            📑 PDF
          </button>

          <button
            @click="handlePrintDocument"
            class="premium-btn premium-print-btn"
            :disabled="!documentContent"
          >
            🖨️ Print
          </button>

          <button
            @click="handleSavePdfToBackend"
            class="premium-btn premium-save-to-backend-btn"
            :disabled="!documentContent"
          >
            💾 Save PDF
          </button>

          <button
            v-if="isTemplate"
            @click="handleDownloadGeneratedDocument()"
            class="premium-download-btn"
            :disabled="!templateFile"
          >
            ⬇️ Download
          </button>
        </div>
      </div>

      <DocumentToolbar
        v-if="!previewMode"
        @format="handleFormatText"
        @change-heading="handleChangeHeading"
        @insert-list="handleInsertList"
        @insert-link="handleInsertLink"
        @save="saveEditedContent"
      />

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

        <DocumentPreview
          v-else
          :content="documentContent"
          :preview-mode="previewMode"
          @update:content="handleEditorInput"
          @editor-keydown="handleEditorKeydown"
          :editor-ref="editorRef"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Main container for the whole editor */
.premium-document-editor {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  background-color: #fff;
  display: flex;
  flex-direction: column;
}

/* Upload progress bar */
.upload-progress {
  padding: 10px 20px;
  background-color: #f0f8ff;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  gap: 15px;
}

.progress-bar {
  flex-grow: 1;
  height: 8px;
  background-color: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: #4CAF50;
  transition: width 0.3s ease-in-out;
  border-radius: 4px;
}

.progress-text {
  font-size: 0.85em;
  color: #555;
}

/* Main preview area */
.premium-preview-main {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

/* Header with title and actions */
.premium-preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  background-color: #f9f9f9;
  flex-wrap: wrap; /* Allow wrapping on smaller screens */
  gap: 10px; /* Space between items when wrapped */
}

.premium-preview-title .title-text {
  margin: 0;
  font-size: 1.2em;
  color: #333;
  display: flex;
  align-items: center;
  gap: 8px;
}

.template-badge {
  background-color: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75em;
  font-weight: bold;
}

.premium-preview-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* Common button styles */
.premium-btn {
  padding: 8px 15px;
  border: 1px solid #ccc;
  border-radius: 5px;
  background-color: #f0f0f0;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.2s, border-color 0.2s;
  display: flex;
  align-items: center;
  gap: 5px;
}

.premium-btn:hover:not(:disabled) {
  background-color: #e0e0e0;
  border-color: #bbb;
}

.premium-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Specific button styles */
.premium-edit-btn.active {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}

.premium-edit-btn.active:hover {
  background-color: #218838;
}

.premium-pdf-btn,
.premium-print-btn,
.premium-save-to-backend-btn {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}

.premium-pdf-btn:hover:not(:disabled),
.premium-print-btn:hover:not(:disabled),
.premium-save-to-backend-btn:hover:not(:disabled) {
  background-color: #0056b3;
  border-color: #0056b3;
}

.premium-download-btn {
  background-color: #17a2b8;
  color: white;
  border-color: #17a2b8;
}

.premium-download-btn:hover:not(:disabled) {
  background-color: #138496;
  border-color: #138496;
}

/* Document Preview/Editor Container */
.premium-document-preview-container {
  flex-grow: 1;
  padding: 20px;
  background-color: #fdfdfd;
  min-height: 400px;
  overflow-y: auto;
  position: relative; /* For loading state overlay */
}

/* Loading and Empty States */
.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  color: #666;
}

.loading-spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 3em;
  margin-bottom: 10px;
}

.empty-state h3 {
  margin: 0 0 5px 0;
  color: #555;
}

.empty-state p {
  font-size: 0.9em;
  color: #777;
}

/* This is the general container for the docx-preview output */
.document-wrapper {
  background-color: #fff;
  padding: 20px;
  border: 1px solid #eee;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  min-height: 300px;
}

/* Specific styles for docx-preview content.
   These styles target elements generated by docx-preview.
   You might need to adjust these based on your actual docx files. */
.document-wrapper :deep(.docx-wrapper) {
  padding: 0 !important; /* Override default docx-preview padding */
  margin: 0 auto !important; /* Center the content if it's smaller than wrapper */
  max-width: 794px; /* A4 width at 96 DPI, roughly */
  box-shadow: none; /* Remove docx-preview's default shadow */
  border: none; /* Remove docx-preview's default border */
}

.document-wrapper :deep(p) {
  margin-bottom: 0.5em; /* Default paragraph spacing */
  line-height: 1.5;
}

.document-wrapper :deep(h1),
.document-wrapper :deep(h2),
.document-wrapper :deep(h3),
.document-wrapper :deep(h4),
.document-wrapper :deep(h5),
.document-wrapper :deep(h6) {
  margin-top: 1em;
  margin-bottom: 0.5em;
  font-weight: bold;
}

.document-wrapper :deep(table) {
  border-collapse: collapse;
  width: 100%;
  margin: 1em 0;
  border: 1px solid #d1d5db; /* Default table border */
}

.document-wrapper :deep(td),
.document-wrapper :deep(th) {
  border: 1px solid #d1d5db; /* Default cell border */
  padding: 8px;
  vertical-align: top;
}

.document-wrapper :deep(th) {
  background-color: #f7f7f7;
  font-weight: bold;
}

.document-wrapper :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1em auto;
}

.document-wrapper :deep(ul),
.document-wrapper :deep(ol) {
  margin: 1em 0;
  padding-left: 20px;
}

.document-wrapper :deep(li) {
  margin-bottom: 0.5em;
}

.document-wrapper :deep(strong),
.document-wrapper :deep(b) {
  font-weight: bold;
}

.document-wrapper :deep(em),
.document-wrapper :deep(i) {
  font-style: italic;
}

.document-wrapper :deep(u) {
  text-decoration: underline;
}

/* Specific styling for the contenteditable area */
.document-editor {
  border: 1px solid #ddd;
  padding: 20px;
  min-height: 300px;
  background-color: #ffffff;
  font-family: 'Times New Roman', serif;
  font-size: 11pt;
  line-height: 1.5;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
  overflow-y: auto;
  cursor: text;
}

/* Ensure images within contenteditable are draggable and resizable (browser native) */
.document-editor :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1em auto;
  cursor: grab;
}

/* Basic styling for when in edit mode */
.document-editor:focus {
  outline: 2px solid #007bff;
  outline-offset: -1px;
}
</style>