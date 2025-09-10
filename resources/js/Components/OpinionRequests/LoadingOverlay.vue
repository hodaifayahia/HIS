<template>
  <div class="loading-overlay">
    <div class="loading-spinner">
      <div class="spinner-ring"></div>
      <p>Processing request...</p>
    </div>
  </div>
</template>

<style scoped>
/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
}

.loading-spinner {
  text-align: center;
  color: #4f46e5;
}

.spinner-ring {
  width: 60px;
  height: 60px;
  border: 6px solid #e2e8f0;
  border-top: 6px solid #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

.loading-spinner p {
  font-size: 1.1rem;
  font-weight: 500;
  margin: 0;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>