<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentDate: {
    type: Date,
    required: true
  }
});

const emit = defineEmits(['previous-month', 'next-month']);

const currentMonthYear = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
  }).format(props.currentDate);
});

const previousMonth = () => {
  emit('previous-month');
};

const nextMonth = () => {
  emit('next-month');
};
</script>

<template>
  <div class="card mb-4 shadow-sm rounded-lg">
    <div class="card-header d-flex justify-content-between align-items-center p-3 bg-primary text-white">
      <h4 class="card-title mb-0"></h4>
      <div class="d-flex align-items-center">
        <button @click="previousMonth" class="btn btn-light btn-sm me-2">
          &lt; <!-- Left arrow for previous month -->
        </button>
       
        <span class="fw-bold mx-3 fs-5 text-black" style="color:black;">{{ currentMonthYear }}</span>
        <button @click="nextMonth" class="btn btn-light btn-sm ms-2">
          &gt; <!-- Right arrow for next month -->
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Component-specific styles */
</style>