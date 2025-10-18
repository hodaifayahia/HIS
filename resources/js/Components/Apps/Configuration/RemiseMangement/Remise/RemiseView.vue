<script setup>
import { defineProps, defineEmits } from 'vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

defineProps({
  remise: {
    type: Object,
    required: true
  }
})

defineEmits(['close'])

const formatCurrency = (value) => {
  // Current time is Tuesday, July 29, 2025 at 11:11:26 AM CET.
  // Locale for Algeria (fr-DZ) and currency DZD
  return new Intl.NumberFormat('fr-DZ', {
    style: 'currency',
    currency: 'DZD'
  }).format(value || 0)
}
</script>

<template>
  <div v-if="remise" class="remise-view-details">
    <div class="remise-overview">
      <h3 class="remise-title">{{ remise.name }}</h3>
      <p v-if="remise.description" class="remise-description">{{ remise.description }}</p>
      <p v-else class="remise-description-placeholder">No description provided for this remise.</p>
    </div>

    <div class="remise-info-grid grid">
      <div class="col-12 md:col-6 remise-info-item">
        <strong class="info-label">Code:</strong>
        <Tag :value="remise.code" class="info-tag p-tag-info" />
      </div>

      <div class="col-12 md:col-6 remise-info-item">
        <strong class="info-label">Type:</strong>
        <Tag
          :value="remise.type"
          :class="remise.type === 'fixed' ? 'info-tag p-tag-success' : 'info-tag p-tag-warning'"
        />
      </div>

      <div class="col-12 md:col-6 remise-info-item">
        <strong class="info-label">Value:</strong>
        <span class="info-value">
          <span v-if="remise.type === 'fixed'">
            {{ formatCurrency(remise.amount) }}
          </span>
          <span v-else>
            {{ remise.percentage }}%
          </span>
        </span>
      </div>

      <div class="col-12 md:col-6 remise-info-item">
        <strong class="info-label">Status:</strong>
        <Tag
          :value="remise.is_active ? 'Active' : 'Inactive'"
          :class="remise.is_active ? 'info-tag p-tag-success' : 'info-tag p-tag-danger'"
        />
      </div>
    </div>

    <div class="remise-section" v-if="remise.users && remise.users.length > 0">
      <h4 class="section-title">Assigned Users:</h4>
      <div class="flex flex-wrap gap-2 user-tags-container">
        <Tag
          v-for="user in remise.users"
          :key="user.id"
          :value="user.name"
          class="remise-user-tag"
        />
      </div>
    </div>
    <div class="remise-section-placeholder" v-else>
      <p class="text-muted">No users assigned to this remise.</p>
    </div>

    <div class="remise-section" v-if="remise.prestations && remise.prestations.length > 0">
      <h4 class="section-title">Applied Prestations:</h4>
      <div class="flex flex-wrap gap-2 prestation-tags-container">
        <Tag
          v-for="prestation in remise.prestations"
          :key="prestation.id"
          :value="prestation.name"
          class="remise-prestation-tag"
        />
      </div>
    </div>
    <div class="remise-section-placeholder" v-else>
      <p class="text-muted">No prestations applied to this remise.</p>
    </div>

    <div class="flex justify-content-end mt-5 pt-3 border-top-1 surface-border">
      <Button
        label="Close"
        icon="pi pi-times"
        class="p-button-secondary close-button"
        @click="$emit('close')"
      />
    </div>
  </div>
  <div v-else class="remise-view-placeholder">
    <p>Select a remise to view its details.</p>
  </div>
</template>

<style scoped>
.remise-view-details {
  padding: 1.5rem;
  font-family: 'Inter', sans-serif;
  color: var(--text-color);
}

.remise-overview {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--surface-border);
}

.remise-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-top: 0;
  margin-bottom: 0.75rem;
  background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark-color) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.remise-description {
  color: var(--text-color-secondary);
  font-style: italic;
  line-height: 1.5;
  margin-bottom: 0;
  font-size: 0.95rem;
}

.remise-description-placeholder {
  color: var(--text-color-secondary);
  font-style: italic;
  font-size: 0.95rem;
}

.remise-info-grid {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px dashed var(--surface-border); /* Dashed separator */
}

.remise-info-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.75rem; /* Space between items */
}

.info-label {
  font-weight: 600;
  color: var(--text-color);
  margin-right: 0.75rem; /* Space between label and value/tag */
  min-width: 80px; /* Align labels */
}

.info-value {
  color: var(--text-color);
  font-weight: 500;
  font-size: 1rem;
}

/* Tag styling consistent with other components */
.info-tag {
  font-size: 0.85rem;
  padding: 0.35em 0.8em;
  border-radius: 9999px;
  font-weight: 600;
  text-transform: capitalize;
}

.p-tag-info { background-color: var(--blue-500); color: var(--blue-50); }
.p-tag-success { background-color: var(--green-500); color: var(--green-50); }
.p-tag-warning { background-color: var(--orange-500); color: var(--orange-50); }
.p-tag-danger { background-color: var(--red-500); color: var(--red-50); }


/* Section Styling (Users & Prestations) */
.remise-section {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--surface-border);
}

.remise-section:first-of-type {
  margin-top: 0;
  border-top: none;
  padding-top: 0;
}

.section-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 1rem;
}

.user-tags-container,
.prestation-tags-container {
  max-height: 120px; /* Limit height for long lists */
  overflow-y: auto; /* Enable scrolling if too many tags */
  padding-right: 0.5rem; /* Space for scrollbar */
}

/* Custom tags for users and prestations */
.remise-user-tag {
  background-color: var(--primary-100); /* Lighter primary for user tags */
  color: var(--primary-700);
  font-size: 0.85rem;
  padding: 0.3em 0.7em;
  border-radius: var(--border-radius);
  font-weight: 500;
}

.remise-prestation-tag {
  background-color: var(--surface-200); /* Neutral for prestation tags */
  color: var(--text-color-secondary);
  font-size: 0.85rem;
  padding: 0.3em 0.7em;
  border-radius: var(--border-radius);
  font-weight: 500;
}

.remise-section-placeholder {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--surface-border);
}

.remise-section-placeholder p {
  color: var(--text-color-secondary);
  font-style: italic;
  font-size: 0.95rem;
}


/* Close Button */
.close-button {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: var(--border-radius);
  transition: all 0.2s ease-in-out;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  background: var(--surface-50);
  color: var(--text-color);
  border: 1px solid var(--surface-border);
}

.close-button:hover {
  background: var(--surface-100);
  border-color: var(--surface-d);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

/* Placeholder for when no remise is selected */
.remise-view-placeholder {
  text-align: center;
  padding: 3rem;
  color: var(--text-color-secondary);
  font-size: 1.1rem;
  font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .remise-view-details {
    padding: 1rem;
  }
  .remise-title {
    font-size: 1.5rem;
  }
  .remise-info-item {
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 1rem;
  }
  .info-label {
    margin-bottom: 0.25rem;
    min-width: unset;
  }
  .section-title {
    font-size: 1rem;
  }
  .user-tags-container,
  .prestation-tags-container {
    max-height: 100px; /* Adjust for smaller screens */
  }
}
</style>