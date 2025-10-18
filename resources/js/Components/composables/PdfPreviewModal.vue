<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  isVisible: {
    type: Boolean,
    required: true,
  },
  pdfUrl: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['close']);

const closeModal = () => {
  emit('close');
};
</script>
<template>
  <div v-if="isVisible" class="modal-overlay">
    <div class="modal-content pdf-preview-modal-content">
      <div class="modal-header">
        <h3>Document Preview</h3>
        <button @click="closeModal" class="close-modal-btn">&times;</button>
      </div>
      <div class="modal-body pdf-preview-body">
        <iframe v-if="pdfUrl" :src="pdfUrl" style="width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
        <p v-else>No PDF available for preview.</p>
      </div>
      <div class="modal-footer">
        <button @click="closeModal" class="premium-btn cancel-btn">Close</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 100000;
}

/* Base modal content styles */
.modal-content {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
  display: flex; /* Make it a flex container */
  flex-direction: column; /* Stack children vertically */
  animation: fadeInScale 0.3s ease-out;
  z-index: 100000;
  /* Specific sizing for PDF preview */
  width: 90vw; /* Take 90% of viewport width */
  max-width: 1000px; /* But not more than 900px */
  height: 90vh; /* Take 90% of viewport height */
  max-height: 800px; /* But not more than 800px */
}

/* Specific overrides for PDF preview modal */
.pdf-preview-modal-content {
  padding: 20px; /* Maintain consistent padding inside the modal */
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px; /* Consistent padding */
  margin-bottom: 10px; /* Space below header */
}

.modal-header h3 {
  margin: 0;
  font-size: 1.4rem;
  color: #333;
}

.close-modal-btn {
  background: none;
  border: none;
  font-size: 1.8rem;
  cursor: pointer;
  color: #999;
  transition: color 0.2s;
  padding: 0 5px; /* Adjust padding for the close button itself */
}

.close-modal-btn:hover {
  color: #333;
}

.modal-body.pdf-preview-body {
  flex-grow: 1; /* This is crucial: allows the body to take all available space */
  overflow: hidden; /* Hide any overflow within the body */
  padding: 0; /* Remove padding to make iframe fill entirely */
  display: flex; /* Make body a flex container to ensure iframe fills it */
  align-items: center; /* Center "No PDF" message if shown */
  justify-content: center; /* Center "No PDF" message if shown */
}

.modal-body iframe {
  width: 100%;
  height: 100%; /* Important: iframe now fills the flex-grown body */
  border: none;
  display: block; /* Ensures no extra space below iframe */
}

.modal-footer {
  margin-top: 10px; /* Space above footer */
  padding-top: 10px; /* Padding inside footer */
  border-top: 1px solid #eee; /* Separator line */
  display: flex;
  justify-content: flex-end; /* Align close button to the right */
}

.premium-btn {
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
}

.cancel-btn {
  background-color: #e0e0e0;
  color: #333;
  border: 1px solid #ccc;
}

.cancel-btn:hover {
  background-color: #d0d0d0;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>