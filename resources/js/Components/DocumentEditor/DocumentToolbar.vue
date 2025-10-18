<script setup>
import { ref } from 'vue';

const emit = defineEmits([
  'format',
  'change-heading',
  'insert-list',
  'insert-link',
  'save'
]);

const headingLevel = ref('p');

const onFormat = (command, value = null) => {
  emit('format', command, value);
};

const onChangeHeading = (event) => {
  emit('change-heading', event.target.value);
};

const onInsertList = (ordered) => {
  emit('insert-list', ordered);
};

const onInsertLink = () => {
  emit('insert-link');
};

const onSave = () => {
  emit('save');
};
</script>

<template>
  <div class="premium-editor-toolbar">
    <div class="toolbar-group">
      <button @click="onFormat('bold')" class="toolbar-btn" title="Bold">
        <strong>B</strong>
      </button>
      <button @click="onFormat('italic')" class="toolbar-btn" title="Italic">
        <em>I</em>
      </button>
      <button @click="onFormat('underline')" class="toolbar-btn" title="Underline">
        <u>U</u>
      </button>
    </div>

    <div class="toolbar-group">
      <select v-model="headingLevel" @change="onChangeHeading" class="heading-select">
        <option value="p">Paragraph</option>
        <option value="1">Heading 1</option>
        <option value="2">Heading 2</option>
        <option value="3">Heading 3</option>
        <option value="4">Heading 4</option>
      </select>
    </div>

    <div class="toolbar-group">
      <button @click="onInsertList(false)" class="toolbar-btn" title="Bullet List">
        • List
      </button>
      <button @click="onInsertList(true)" class="toolbar-btn" title="Numbered List">
        1. List
      </button>
    </div>

    <div class="toolbar-group">
      <button @click="onInsertLink()" class="toolbar-btn" title="Insert Link">
        🔗 Link
      </button>
    </div>

    <div class="toolbar-group">
      <button @click="onSave()" class="toolbar-btn save-btn" title="Save (Ctrl+S)">
        💾 Save
      </button>
    </div>
  </div>
</template>

<style scoped>
.premium-editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 10px 20px;
  background-color: #f0f0f0;
  border-bottom: 1px solid #e0e0e0;
}

.toolbar-group {
  display: flex;
  gap: 5px;
  align-items: center;
}

.toolbar-btn {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #fff;
  cursor: pointer;
  font-size: 0.9em;
  min-width: 40px; /* Ensure buttons have a minimum width */
  text-align: center;
  transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s;
}

.toolbar-btn:hover {
  background-color: #e9e9e9;
  border-color: #b0b0b0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.toolbar-btn:active {
  background-color: #ddd;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
}

.toolbar-btn strong {
  font-weight: bold;
}

.toolbar-btn em {
  font-style: italic;
}

.toolbar-btn u {
  text-decoration: underline;
}

.heading-select {
  padding: 7px 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #fff;
  font-size: 0.9em;
  cursor: pointer;
}

.heading-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.save-btn {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}

.save-btn:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}
</style>