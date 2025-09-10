<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useToastr } from '../../../../Components/toster'; // Add curly braces for named export

const toastr = useToastr();

const router = useRouter();

const props = defineProps({
    organisme: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['edit', 'delete']); // No 'toggle-status' as organisme data doesn't have `is_active`

// Computed property for display name, handling potential missing name
const displayName = computed(() => props.organisme.name || 'Unnamed Organisme');

// Placeholder image URL
const placeholderLogo = computed(() => {
    return props.organisme.logo_url || `https://placehold.co/50x50/E0E0E0/333333?text=${displayName.value.charAt(0).toUpperCase()}`;
});

// Determine status text and class (mocked to 'Active' as per original Organisme data structure)
// If your API provides an 'is_active' field for organismes, you can uncomment the lines below
// and replace the hardcoded 'Active' status.
const statusText = computed(() => 'Active'); // Defaulting to Active as per current mock data
const statusClass = computed(() => 'status-active'); // Defaulting to active style

// Example if you had an is_active field:
// const statusText = computed(() => props.organisme.is_active ? 'Active' : 'Inactive');
// const statusClass = computed(() => props.organisme.is_active ? 'status-active' : 'status-inactive');
const gotoCompanyDetails = () => {
  router.push({
    name: 'convention.organisme-details',
    params: { id: props.organisme.id }
  });
};
</script>

<template>
    <tr @click="gotoCompanyDetails(organisme.id)" class="table-row">
        <td class="table-cell">{{ index + 1 }}</td>
        <td class="table-cell">
            <img :src="placeholderLogo" class="organisme-table-logo rounded-circle" alt="Organisme Logo"
                 onerror="this.onerror=null;this.src='https://placehold.co/50x50/E0E0E0/333333?text=O';" />
        </td>
        <td class="table-cell">
            <div class="organisme-name-details">
                <span class="name">{{ displayName }}</span>
                <span class="email">{{ organisme.email || 'N/A' }}</span>
            </div>
        </td>
        <td class="table-cell">{{ organisme.phone || 'N/A' }}</td>
        <td class="table-cell">{{ organisme.address || 'N/A' }}, {{ organisme.wilaya || 'N/A' }}</td>
        <td class="table-cell">
            <span :class="['status-badge', statusClass]">
                {{ statusText }}
            </span>
        </td>
        <td class="table-cell actions-cell">
            <button @click.stop.prevent="emit('edit', organisme)" class="action-button edit-button" title="Edit">
                <i class="fas fa-edit"></i>
            </button>
            <!-- Removed toggle-status button as 'is_active' is not in current organisme mock data -->
            <button @click.stop.prevent="emit('delete', organisme.id)" class="action-button delete-button" title="Delete">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
    <!-- Add this in the template, e.g. before the name -->
    <span
      v-if="organisme.organism_color"
      :style="{ backgroundColor: organisme.organism_color, display: 'inline-block', width: '16px', height: '16px', borderRadius: '50%', marginRight: '8px', border: '1px solid #ccc' }"
      title="Company Color"
    ></span>
</template>

<style scoped>
/* Styles copied and adapted from serviceslistitem.vue for table row display */
.table-row {
    transition: background-color 0.2s ease;
}
.table-row:nth-child(odd) {
    background-color: #f9fafb;
}
.table-row:hover {
    background-color: #f1f5f9;
}

.table-cell {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    color: #4b5563;
}

.organisme-table-logo {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%; /* Ensure it's circular */
    border: 2px solid #cbd5e1; /* Subtle border */
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.organisme-name-details {
    display: flex;
    flex-direction: column;
}
.organisme-name-details .name {
    font-weight: 600;
    color: #1e293b;
}
.organisme-name-details .email {
    font-size: 0.85rem;
    color: #64748b;
}

.status-badge {
    padding: 0.3em 0.6em;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    display: inline-block;
    min-width: 70px; /* Give it a consistent minimum width */
    text-align: center;
}

.status-active {
    background-color: #d1fae5; /* green-100 */
    color: #065f46; /* green-800 */
}

.status-inactive {
    background-color: #fee2e2; /* red-100 */
    color: #991b1b; /* red-800 */
}

.actions-cell {
    text-align: center;
    white-space: nowrap; /* Prevent buttons from wrapping */
}

.action-button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    margin: 0 0.4rem;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.2s ease;
    color: #64748b;
}

.action-button:hover {
    background-color: #e2e8f0;
    transform: translateY(-1px);
}

.edit-button:hover {
    color: #3b82f6; /* blue-500 */
}

/* Deactivate/Activate buttons (if re-added) would use these styles */
.deactivate-button {
    color: #f59e0b; /* amber-500 */
}
.deactivate-button:hover {
    color: #d97706; /* amber-600 */
}

.activate-button {
    color: #10b981; /* emerald-500 */
}
.activate-button:hover {
    color: #059669; /* emerald-600 */
}

.delete-button:hover {
    color: #ef4444; /* red-500 */
}
</style>
