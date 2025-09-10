<!-- components/Reception/FicheNavette/FicheNavetteHeader.vue -->
<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

const props = defineProps({
  ficheId: {
    type: String,
    required: true
  },
  fiche: {
    type: Object,
    default: null
  },
  showCreateForm: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['go-back', 'toggle-create-form'])

const breadcrumbItems = computed(() => [
  { label: 'Reception', route: '/reception' },
  { label: 'Fiche Navette', route: '/reception/fiche-navette' },
  { label: `Fiche #${props.ficheId}` }
])

const breadcrumbHome = { icon: 'pi pi-home', route: '/' }
</script>

<template>
  <div class="fiche-header">
    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb-section">
      <Breadcrumb :model="breadcrumbItems" :home="breadcrumbHome" />
    </div>

    <!-- Main Header -->
    <div class="header-card">
      <div class="header-content">
        <div class="header-left">
          <Button 
            icon="pi pi-arrow-left"
            class="p-button-text p-button-rounded"
            @click="$emit('go-back')"
            v-tooltip.bottom="'Back to Fiche List'"
            size="large"
          />
          <div class="title-section">
            <div class="title-group">
              <div class="title-icon">
                <i class="pi pi-file-edit"></i>
              </div>
              <div class="title-content">
                <h1 class="page-title">Fiche Navette #{{ ficheId }}</h1>
                <p class="page-subtitle" v-if="fiche">
                  <i class="pi pi-user mr-2"></i>
                  {{ fiche.patient_name }}
                  <span class="divider">•</span>
                  <i class="pi pi-calendar mr-2"></i>
                  {{ new Date(fiche.fiche_date).toLocaleDateString('fr-FR', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                  }) }}
                </p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="header-actions">
          <Button 
            :icon="showCreateForm ? 'pi pi-times' : 'pi pi-plus'"
            :label="showCreateForm ? 'Cancel' : 'Add Items'"
            :class="showCreateForm ? 'p-button-outlined' : 'p-button-primary'"
            @click="$emit('toggle-create-form')"
            size="large"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fiche-header {
  margin-bottom: 2rem;
}

.breadcrumb-section {
  margin-bottom: 1rem;
}

.header-card {
  background: linear-gradient(135deg, var(--primary-50) 0%, white 100%);
  border: 1px solid var(--primary-100);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  gap: 2rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
}

.title-section {
  flex: 1;
}

.title-group {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.title-icon {
  background: var(--primary-color);
  color: white;
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  box-shadow: 0 4px 12px rgba(var(--primary-color-rgb), 0.3);
}

.title-content {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 0.5rem 0;
  line-height: 1.2;
}

.page-subtitle {
  color: var(--text-color-secondary);
  margin: 0;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.divider {
  color: var(--text-color-secondary);
  opacity: 0.5;
  margin: 0 0.5rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .header-content {
    padding: 1.5rem;
  }
  
  .title-icon {
    width: 3rem;
    height: 3rem;
    font-size: 1.25rem;
  }
  
  .page-title {
    font-size: 1.75rem;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
    padding: 1.5rem;
  }
  
  .header-left {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .title-group {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .page-subtitle {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .divider {
    display: none;
  }
  
  .header-actions {
    width: 100%;
  }
  
  .header-actions .p-button {
    flex: 1;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.5rem;
  }
  
  .title-icon {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 1rem;
  }
}
</style>
