<script setup>
defineProps({
  specializations: {
    type: Array,
    required: true
  },
  selectedSpecialization: {
    type: [Number, String, null],
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
});2

const emit = defineEmits(['update:selectedSpecialization']);

const handleSpecializationChange = (event) => {
  emit('update:selectedSpecialization', event.target.value);
};
</script>

<template>
  <div class="form-step">
    <div class="step-header">
      <h3 class="step-title">
        <i class="fas fa-stethoscope me-2"></i>
        Select Medical Specialization
      </h3>
      <p class="step-description">Choose the area of expertise you need consultation for</p>
    </div>

    <div class="form-group">
      <div class="select-wrapper">
        <select
          :value="selectedSpecialization"
          @change="handleSpecializationChange"
          class="form-select enhanced"
          :disabled="loading"
        >
          <option :value="null" disabled>Choose a medical specialization...</option>
          <option
            v-for="spec in specializations"
            :key="spec.id"
            :value="spec.id"
          >
            {{ spec.name }}
          </option>
        </select>
        <i class="fas fa-chevron-down select-arrow"></i>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Form Steps */
.form-step {
  margin-bottom: 2rem;
}

.step-header {
  margin-bottom: 1.5rem;
  text-align: center;
}

.step-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.5rem;
}

.step-description {
  color: #64748b;
  margin: 0;
}

/* Enhanced Form Controls */
.form-group {
  margin-bottom: 1.5rem;
}

.select-wrapper {
  position: relative;
}

.form-select.enhanced {
  width: 100%;
  padding: 1rem 3rem 1rem 1rem;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  background: #f8fafc;
  font-size: 1rem;
  color: #334155;
  transition: all 0.3s ease;
  appearance: none; /* Remove default arrow */
}

.form-select.enhanced:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.select-arrow {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none; /* Make sure arrow doesn't block clicks */
}
</style>