<script setup>
import { computed } from 'vue';

const props = defineProps({
  doctors: {
    type: Array,
    required: true
  },
  filteredDoctors: {
    type: Array,
    required: true
  },
  selectedDoctors: {
    type: Array,
    required: true
  },
  selectedDoctorsInfo: {
    type: Array,
    required: true
  },
  searchTerm: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  },
  requestDescription: {
    type: String,
    default: ''
  }
});

const emit = defineEmits([
  'update:searchTerm',
  'toggleDoctorSelection',
  'update:requestDescription',
  'resetForm',
  'sendOpinionRequest'
]);

const handleSearchInput = (event) => {
  emit('update:searchTerm', event.target.value);
};

const handleDescriptionInput = (event) => {
  emit('update:requestDescription', event.target.value);
};

const handleToggleDoctorSelection = (doctorId) => {
  emit('toggleDoctorSelection', doctorId);
};

const handleResetForm = () => {
  emit('resetForm');
};

const handleSendOpinionRequest = () => {
  emit('sendOpinionRequest');
};
</script>

<template>
  <div class="form-step">
    <div class="step-header">
      <h3 class="step-title">
        <i class="fas fa-users me-2"></i>
        Select Specialist Doctors
      </h3>
      <p class="step-description">Choose doctors you'd like to request opinions from</p>
    </div>

    <div class="search-filters">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input
          :value="searchTerm"
          @input="handleSearchInput"
          type="text"
          placeholder="Search doctors by name or expertise..."
          class="search-input"
        />
      </div>
    </div>

    <div v-if="selectedDoctors.length > 0" class="selected-summary">
      <div class="summary-header">
        <h4>
          <i class="fas fa-check-circle me-2"></i>
          Selected Doctors ({{ selectedDoctors.length }})
        </h4>
      </div>
      <div class="selected-doctors-tags">
        <div
          v-for="doctor in selectedDoctorsInfo"
          :key="doctor.id"
          class="doctor-tag"
        >
          <span>{{ doctor.name }}</span>
          <button @click="handleToggleDoctorSelection(doctor.id)" class="remove-tag">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="doctors-grid">
      <div
        v-for="doctor in filteredDoctors"
        :key="doctor.id"
        class="doctor-card enhanced"
        :class="{ selected: selectedDoctors.includes(doctor.id) }"
        @click="handleToggleDoctorSelection(doctor.id)"
      >
        <div class="doctor-avatar">
          <i class="fas fa-user-md"></i>
        </div>
        <div class="doctor-info">
          <h4 class="doctor-name">{{ doctor.name }}</h4>
          <p class="doctor-specialization">{{ doctor.specialization }}</p>
        </div>
        <div class="selection-indicator">
          <div class="checkbox">
            <i class="fas fa-check"></i>
          </div>
        </div>
      </div>
    </div>
    <div>
        <label for="request-description"><h3><strong>Description</strong></h3></label>

    </div>
    <textarea
    id="request-description"
    :value="requestDescription"
      @input="handleDescriptionInput"
      name="request"
      cols="110"
      rows="10"
      ></textarea>
      
    <div class="form-actions">
      <button
        class="btn-secondary"
        @click="handleResetForm"
        :disabled="loading"
      >
        <i class="fas fa-undo me-2"></i>
        Reset Selection
      </button>
      <button
        class="btn-primary"
        :disabled="loading || selectedDoctors.length === 0 || !requestDescription.trim()"
        @click="handleSendOpinionRequest()"
      >
        <i class="fas fa-paper-plane me-2"></i>
        Send {{ selectedDoctors.length }} Request{{ selectedDoctors.length !== 1 ? 's' : '' }}
        <div v-if="loading" class="spinner"></div>
      </button>
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

/* Search and Filters */
.search-filters {
  margin-bottom: 1.5rem;
}

.search-box {
  position: relative;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  background: #f8fafc;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Selected Doctors Summary */
.selected-summary {
  background: #f0f9ff;
  border: 2px solid #bae6fd;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.summary-header h4 {
  color: #0369a1;
  margin: 0 0 1rem;
  font-size: 1rem;
}

.selected-doctors-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.doctor-tag {
  background: #0ea5e9;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.remove-tag {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.remove-tag:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Enhanced Doctors Grid */
.doctors-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.doctor-card.enhanced {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.doctor-card.enhanced:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  border-color: #4f46e5;
}

.doctor-card.enhanced.selected {
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border-color: #4f46e5;
  transform: translateY(-4px);
  box-shadow: 0 15px 35px rgba(79, 70, 229, 0.3);
}

.doctor-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  transition: all 0.3s ease;
}

.doctor-card.enhanced.selected .doctor-avatar {
  background: rgba(255, 255, 255, 0.2);
}

.doctor-name {
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: inherit;
}

.doctor-specialization {
  color: #64748b;
  margin: 0 0 1rem;
  font-size: 0.95rem;
}

.doctor-card.enhanced.selected .doctor-specialization {
  color: rgba(255, 255, 255, 0.9);
}

.selection-indicator {
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.checkbox {
  width: 24px;
  height: 24px;
  border: 2px solid #d1d5db;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  transition: all 0.3s ease;
}

.doctor-card.enhanced.selected .checkbox {
  background: rgba(255, 255, 255, 0.9);
  border-color: transparent;
  color: #4f46e5;
}

/* Textarea */
textarea[name="request"] {
  width: 100%;
  max-width: 800px; /* Adjust as needed */
  height: 200px;
  padding: 12px;
  font-size: 16px;
  font-family: 'Arial', sans-serif;
  color: #333;
  background-color: #f9f9f9;
  border: 2px solid #d1d5db;
  border-radius: 8px;
  resize: vertical;
  transition: all 0.3s ease;
  margin-top: 1rem; /* Added margin */
  margin-bottom: 1.5rem; /* Added margin */
}

textarea[name="request"]:focus {
  outline: none;
  border-color: #3b82f6;
  background-color: #fff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

textarea[name="request"]::placeholder {
  color: #9ca3af;
  opacity: 0.8;
}

textarea[name="request"]:hover {
  border-color: #a5b4fc;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 2px solid #e2e8f0;
  padding: 10px 5px ;
  border-radius: 10px;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  color: #4f46e5;
  border: 2px solid #4f46e5;
}

.btn-outline:hover:not(:disabled) {
  background: #4f46e5;
  color: white;
  transform: translateY(-1px);
}

.btn-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-primary{
      padding: 5px 10px;
    border-radius: 10px;
    border: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .doctors-grid {
    grid-template-columns: 1fr;
  }
  .form-actions {
    justify-content: stretch;
  }

  .form-actions .btn-primary,
  .form-actions .btn-secondary {
    flex: 1;
    justify-content: center;
    padding: 5px 10px;
    border-radius: 10px;
    border: 0;
  }
}

@media (max-width: 480px) {
  .selected-doctors-tags {
    flex-direction: column;
  }

  .doctor-tag {
    justify-content: space-between;
  }
}

/* Focus Styles for Accessibility */
.doctor-card.enhanced:focus {
  outline: 3px solid #4f46e5;
  outline-offset: 2px;
}
</style>