<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    consultations: {
        type: Array,
        required: true
    },
    selectedConsultation: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['select', 'filter']);

// Filter state
const filterDate = ref('');
const filterDoctor = ref('');
const searchQuery = ref('');

// Computed properties
const uniqueDoctors = computed(() => {
    const doctors = new Set();
    props.consultations.forEach(c => {
        if (c.doctor?.name) {
            doctors.add(c.doctor.name);
        }
    });
    return Array.from(doctors).sort();
});

const filteredConsultations = computed(() => {
    return props.consultations.filter(consultation => {
        const dateMatch = !filterDate.value || consultation.date.startsWith(filterDate.value);
        const doctorMatch = !filterDoctor.value || consultation.doctor?.name === filterDoctor.value;
        const searchMatch = !searchQuery.value || 
            (consultation.name?.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (consultation.doctor?.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
        
        return dateMatch && doctorMatch && searchMatch;
    }).sort((a, b) => new Date(b.date) - new Date(a.date));
});

// Handlers
const handleConsultationSelect = (event) => {
    const selectedId = parseInt(event.target.value, 10);
    const consultation = filteredConsultations.value.find(c => c.id === selectedId);
    if (consultation) {
        emit('select', consultation);
    }
};

// Watch for filter changes and emit
watch([filterDate, filterDoctor, searchQuery], () => {
    emit('filter', {
        date: filterDate.value,
        doctor: filterDoctor.value,
        search: searchQuery.value
    });
});


const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-CA');
};
</script>

<template>
    <div class="consultation-controls">
        <!-- Consultation Selector -->
        <div class="control-section">
            <div class="consultation-selector">
                <label for="consultation-select">Select Consultation</label>
                <div class="select-wrapper">
                    <select 
                        id="consultation-select" 
                        class="control-input" 
                        @change="handleConsultationSelect"
                    >
                        <option v-if="filteredConsultations.length === 0" disabled selected>
                            No matching consultations
                        </option>
                        <option 
                            v-for="consultation in filteredConsultations" 
                            :key="consultation.id" 
                            :value="consultation.id"
                            :selected="selectedConsultation && consultation.id === selectedConsultation.id"
                        >
                            {{ formatDate(consultation.date) }} - 
                            Dr. {{ consultation.doctor?.name || 'Unknown' }} - 
                            {{ consultation.name }}
                        </option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="control-section filters-section">
            <div class="filter-group">
                <label for="date-filter">Date</label>
                <input 
                    type="date" 
                    id="date-filter" 
                    v-model="filterDate" 
                    class="control-input" 
                />
            </div>
            
            <div class="filter-group">
                <label for="doctor-filter">Doctor</label>
                <select 
                    id="doctor-filter" 
                    v-model="filterDoctor" 
                    class="control-input"
                >
                    <option value="">All Doctors</option>
                    <option 
                        v-for="doctor in uniqueDoctors" 
                        :key="doctor" 
                        :value="doctor"
                    >
                        {{ doctor }}
                    </option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search-filter">Search</label>
                <input 
                    type="text" 
                    id="search-filter" 
                    v-model="searchQuery" 
                    class="control-input" 
                    placeholder="Search consultations..."
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.consultation-controls {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.control-section {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    padding: 1.25rem;
}

.filters-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.consultation-selector {
    position: relative;
}

.select-wrapper {
    position: relative;
}

.select-arrow {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    pointer-events: none;
}

.control-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background-color: var(--bg-color);
    font-size: 1rem;
    transition: all 0.2s ease;
}

.control-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

@media (min-width: 768px) {
    .consultation-controls {
        grid-template-columns: 2fr 3fr;
        align-items: start;
    }
    
    .filters-section {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>