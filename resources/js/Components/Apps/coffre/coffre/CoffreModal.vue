<template>
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="fas fa-vault me-2"></i>
          {{ isEditing ? 'Modifier le coffre' : 'Nouveau coffre' }}
        </h4>
        <button type="button" class="btn-close" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="saveCoffre">
          <!-- Name Field -->
          <div class="form-group mb-3">
            <label for="name" class="form-label">
              <i class="fas fa-tag me-1"></i>
              Nom du coffre *
            </label>
            <input
              v-model="form.name"
              type="text"
              id="name"
              class="form-control"
              :class="{ 'is-invalid': errors.name }"
              placeholder="Entrez le nom du coffre"
              required
            >
            <div v-if="errors.name" class="invalid-feedback">
              {{ errors.name[0] }}
            </div>
          </div>

          <!-- Current Balance Field -->
          <div class="form-group mb-3">
            <label for="current_balance" class="form-label">
              <i class="fas fa-euro-sign me-1"></i>
              Solde actuel *
            </label>
            <div class="input-group">
              <input
                v-model.number="form.current_balance"
                type="number"
                id="current_balance"
                class="form-control"
                :class="{ 'is-invalid': errors.current_balance }"
                step="0.01"
                min="0"
                placeholder="0.00"
                required
              >
              <span class="input-group-text">€</span>
            </div>
            <div v-if="errors.current_balance" class="invalid-feedback">
              {{ errors.current_balance[0] }}
            </div>
          </div>

          <!-- Service/Location Selection -->
          <div class="form-group mb-3">
            <label for="service_id" class="form-label">
              <i class="fas fa-map-marker-alt me-1"></i>
              Service/Emplacement
            </label>
            <select
              v-model="form.service_id"
              id="service_id"
              class="form-select"
              :class="{ 'is-invalid': errors.location }"
              @change="updateLocation"
            >
              <option value="">Sélectionner un service</option>
              <option
                v-for="service in services"
                :key="service.id"
                :value="service.id"
              >
                {{ service.name }}
              </option>
            </select>
            
            <!-- Manual Location Input -->
            <input
              v-model="form.location"
              type="text"
              class="form-control mt-2"
              :class="{ 'is-invalid': errors.location }"
              placeholder="Ou saisir manuellement l'emplacement"
            >
            <div v-if="errors.location" class="invalid-feedback">
              {{ errors.location[0] }}
            </div>
          </div>

          <!-- Responsible User Selection -->
          <div class="form-group mb-4">
            <label for="responsible_user_id" class="form-label">
              <i class="fas fa-user me-1"></i>
              Utilisateur responsable *
            </label>
            <select
              v-model="form.responsible_user_id"
              id="responsible_user_id"
              class="form-select"
              :class="{ 'is-invalid': errors.responsible_user_id }"
              required
            >
              <option value="">Sélectionner un utilisateur</option>
              <option
                v-for="user in users"
                :key="user.id"
                :value="user.id"
              >
                {{ user.name }}
              </option>
            </select>
            <div v-if="errors.responsible_user_id" class="invalid-feedback">
              {{ errors.responsible_user_id[0] }}
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex gap-3">
            <button
              type="button"
              class="btn btn-secondary flex-fill"
              @click="$emit('close')"
              :disabled="saving"
            >
              <i class="fas fa-times me-1"></i>
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary flex-fill"
              :disabled="saving"
            >
              <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else :class="`fas ${isEditing ? 'fa-save' : 'fa-plus'} me-1`"></i>
              {{ saving ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { coffreService } from '../../services/Coffre/CoffreService'

// Props
const props = defineProps({
  coffre: {
    type: Object,
    default: null
  },
  isEditing: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['close', 'saved'])

// Reactive state
const services = ref([])
const users = ref([])
const saving = ref(false)
const errors = ref({})

const form = reactive({
  name: '',
  current_balance: 0,
  location: '',
  service_id: '',
  responsible_user_id: ''
})

// Methods
const loadData = async () => {
  // Load services
  const servicesResult = await coffreService.getServices()
  if (servicesResult.success) {
    services.value = servicesResult.data
  }

  // Load users
  const usersResult = await coffreService.getUsers()
  if (usersResult.success) {
    users.value = usersResult.data
    
    // Set default responsible user to current user if not editing
    if (!props.isEditing && usersResult.data.length > 0) {
      // Assuming there's a way to get current user, otherwise remove this
      // form.responsible_user_id = getCurrentUserId()
    }
  }
}

const updateLocation = () => {
  if (form.service_id) {
    const selectedService = services.value.find(s => s.id === form.service_id)
    if (selectedService) {
      form.location = selectedService.name
    }
  }
}

const saveCoffre = async () => {
  saving.value = true
  errors.value = {}

  const data = {
    name: form.name,
    current_balance: form.current_balance,
    location: form.location,
    responsible_user_id: form.responsible_user_id
  }

  const result = props.isEditing
    ? await coffreService.update(props.coffre.id, data)
    : await coffreService.create(data)

  if (result.success) {
    emit('saved', result.message)
  } else {
    if (result.errors) {
      errors.value = result.errors
    } else {
      // Handle general error
      alert(result.message)
    }
  }

  saving.value = false
}

// Watchers
watch(() => props.coffre, (newCoffre) => {
  if (newCoffre && props.isEditing) {
    form.name = newCoffre.name
    form.current_balance = newCoffre.current_balance
    form.location = newCoffre.location
    form.responsible_user_id = newCoffre.responsible_user_id
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  loadData()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 15px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: between;
  align-items: center;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 15px 15px 0 0;
}

.modal-title {
  margin: 0;
  font-weight: 600;
  font-size: 1.2rem;
}

.btn-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  padding: 0.5rem;
  border-radius: 50%;
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(90deg);
}

.modal-body {
  padding: 2rem;
}

.form-label {
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 0.5rem;
}

.form-control,
.form-select {
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.input-group-text {
  border-radius: 0 10px 10px 0;
  border: 1px solid #e2e8f0;
  border-left: none;
  background: #f8f9fa;
  font-weight: 600;
  color: #667eea;
}

.btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
}

.btn-secondary {
  background: #6c757d;
  border: none;
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .modal-header {
    padding: 1rem 1.5rem;
  }
}
</style>
