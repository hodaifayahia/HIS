<script setup>
defineProps({
  currentStep: {
    type: Number,
    required: true
  }
});
</script>

<template>
  <div class="progress-steps">
    <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
      <div class="step-indicator">
        <i class="fas fa-stethoscope"></i>
      </div>
      <span class="step-label">Choose Specialization</span>
    </div>
    <div class="step-connector" :class="{ active: currentStep > 1 }"></div>
    <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
      <div class="step-indicator">
        <i class="fas fa-users"></i>
      </div>
      <span class="step-label">Select Doctors</span>
    </div>
    <div class="step-connector" :class="{ active: currentStep > 2 }"></div>
    <div class="step" :class="{ active: currentStep >= 3 }">
      <div class="step-indicator">
        <i class="fas fa-paper-plane"></i>
      </div>
      <span class="step-label">Send Requests</span>
    </div>
  </div>
</template>

<style scoped>
/* Progress Steps */
.progress-steps {
  display: flex;
  justify-content: center;
  align-items: center;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 50px;
  padding: 1rem 2rem;
  max-width: 600px;
  margin: 0 auto;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  opacity: 0.6;
  transition: all 0.3s ease;
}

.step.active {
  opacity: 1;
}

.step.completed {
  opacity: 1;
}

.step-indicator {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
  transition: all 0.3s ease;
}

.step.active .step-indicator {
  background: rgba(255, 255, 255, 0.9);
  color: #4f46e5;
  transform: scale(1.1);
}

.step.completed .step-indicator {
  background: #10b981;
  color: white;
}

.step-label {
  font-size: 0.9rem;
  color: white;
  font-weight: 500;
  text-align: center;
}

.step-connector {
  width: 60px;
  height: 2px;
  background: rgba(255, 255, 255, 0.3);
  margin: 0 1rem;
  transition: all 0.3s ease;
}

.step-connector.active {
  background: rgba(255, 255, 255, 0.8);
}

/* Responsive */
@media (max-width: 768px) {
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }

  .step {
    flex-direction: row;
    gap: 1rem;
  }

  .step-connector {
    display: none;
  }
}
</style>