<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  content: {
    type: String,
    required: true
  },
  previewMode: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:content', 'save-caret']);

const editorRef = ref(null);
const lastCaretPosition = ref(null);

const saveCaretPosition = () => {
  if (window.getSelection) {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
      lastCaretPosition.value = selection.getRangeAt(0).cloneRange();
      emit('save-caret', lastCaretPosition.value);
    }
  }
};

const restoreCaretPosition = () => {
  if (lastCaretPosition.value && window.getSelection) {
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(lastCaretPosition.value);
  }
};

const handleEditorInput = (event) => {
  if (!editorRef.value) return;
  
  saveCaretPosition();
  const scrollTop = editorRef.value.scrollTop;
  const scrollLeft = editorRef.value.scrollLeft;
  
  emit('update:content', editorRef.value.innerHTML);
  
  setTimeout(() => {
    if (editorRef.value) {
      editorRef.value.scrollTop = scrollTop;
      editorRef.value.scrollLeft = scrollLeft;
      restoreCaretPosition();
    }
  }, 0);
};

onMounted(() => {
  if (editorRef.value) {
    editorRef.value.addEventListener('mouseup', saveCaretPosition);
    editorRef.value.addEventListener('keyup', saveCaretPosition);
    editorRef.value.addEventListener('focus', () => {
      if (lastCaretPosition.value) {
        setTimeout(restoreCaretPosition, 0);
      }
    });
  }
});
</script>

<template>
  <div class="premium-editor-main">
    <div v-if="!previewMode" 
      ref="editorRef" 
      class="premium-document-editor" 
      contenteditable="true"
      @input="handleEditorInput" 
      @mouseup="saveCaretPosition" 
      @keyup="saveCaretPosition" 
      v-html="content"
    ></div>

    <div v-else 
      class="premium-document-preview" 
      v-html="content"
    ></div>
  </div>
</template>