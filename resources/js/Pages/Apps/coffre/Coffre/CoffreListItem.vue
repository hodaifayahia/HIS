<template>
  <div class="coffre-card">
    <div class="card h-100 shadow-sm border-0">
      <div class="card-header bg-transparent border-0 pb-0">
        <div class="d-flex justify-content-between align-items-start">
          <div class="coffre-icon">
            <i class="fas fa-vault"></i>
          </div>
          <div class="dropdown">
            <button
              class="btn btn-link text-muted p-1"
              type="button"
              data-bs-toggle="dropdown"
            >
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
              <li>
                <button class="dropdown-item" @click="$emit('view', coffre)">
                  <i class="fas fa-eye me-2 text-primary"></i>
                  Voir les détails
                </button>
              </li>
              <li>
                <button class="dropdown-item" @click="$emit('edit', coffre)">
                  <i class="fas fa-edit me-2 text-warning"></i>
                  Modifier
                </button>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button 
                  class="dropdown-item text-danger" 
                  @click="$emit('delete', coffre)"
                >
                  <i class="fas fa-trash me-2"></i>
                  Supprimer
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="card-body pt-2">
        <h5 class="card-title mb-2">{{ coffre.name }}</h5>
        
        <div class="balance-display mb-3">
          <div class="balance-label">Solde actuel</div>
          <div class="balance-amount" :class="balanceClass">
            {{ formatCurrency(coffre.current_balance) }}
          </div>
        </div>

        <div class="coffre-details">
          <div class="detail-item mb-2">
            <i class="fas fa-map-marker-alt text-muted me-2"></i>
            <span class="detail-label">Emplacement:</span>
            <span class="detail-value">{{ coffre.location || 'Non spécifié' }}</span>
          </div>
          
          <div class="detail-item mb-2">
            <i class="fas fa-user text-muted me-2"></i>
            <span class="detail-label">Responsable:</span>
            <span class="detail-value">{{ coffre.responsible_user?.name || 'Non assigné' }}</span>
          </div>
          
          <div class="detail-item">
            <i class="fas fa-calendar text-muted me-2"></i>
            <span class="detail-label">Créé le:</span>
            <span class="detail-value">{{ formatDate(coffre.created_at) }}</span>
          </div>
        </div>
      </div>

      <div class="card-footer bg-transparent border-0">
        <div class="d-flex gap-2">
          <button 
            class="btn btn-outline-primary btn-sm flex-fill"
            @click="$emit('edit', coffre)"
          >
            <i class="fas fa-edit me-1"></i>
            Modifier
          </button>
          <button 
            class="btn btn-outline-info btn-sm flex-fill"
            @click="$emit('view', coffre)"
          >
            <i class="fas fa-eye me-1"></i>
            Détails
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Assign props to a variable so we can reference it in script
const props = defineProps({
  coffre: {
    type: Object,
    required: true
  }
})

// Capture emits
const emit = defineEmits(['edit', 'delete', 'view'])

// Computed
const balanceClass = computed(() => {
  const bal = props.coffre?.current_balance ?? 0
  return {
    'text-success': bal > 0,
    'text-warning': bal === 0,
    'text-danger': bal < 0
  }
})

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return 'Non spécifié'
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(dateString))
}
</script>

<style scoped>
.coffre-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.coffre-card:hover {
  transform: translateY(-5px);
}

.coffre-card:hover .card {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.card {
  border-radius: 15px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.coffre-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.card-title {
  font-weight: 600;
  color: #2d3748;
  font-size: 1.1rem;
}

.balance-display {
  background: linear-gradient(135deg, #f7fafc, #edf2f7);
  border-radius: 10px;
  padding: 15px;
  text-align: center;
}

.balance-label {
  font-size: 0.85rem;
  color: #718096;
  margin-bottom: 5px;
  font-weight: 500;
}

.balance-amount {
  font-size: 1.4rem;
  font-weight: 700;
}

.coffre-details {
  font-size: 0.9rem;
}

.detail-item {
  display: flex;
  align-items: center;
}

.detail-label {
  font-weight: 500;
  color: #4a5568;
  margin-right: 5px;
}

.detail-value {
  color: #718096;
  flex: 1;
}

.dropdown-menu {
  border-radius: 10px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
  padding: 10px 20px;
  font-size: 0.9rem;
  border-radius: 6px;
  margin: 2px 8px;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  transform: translateX(2px);
}

.btn-sm {
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.85rem;
}

.btn-outline-primary:hover,
.btn-outline-info:hover {
  transform: translateY(-1px);
}

@media (max-width: 576px) {
  .balance-amount {
    font-size: 1.2rem;
  }
  
  .card-title {
    font-size: 1rem;
  }
}
</style>
