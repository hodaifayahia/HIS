<template>
  <Dialog 
    :visible="visible" 
    @update:visible="$emit('update:visible', $event)"
    :header="isEditing ? 'Edit Emergency Planning' : 'New Emergency Planning'"
    :style="{ width: '55vw' }"
    :modal="true"
    class="p-fluid emergency-planning-dialog"
  >
    <form @submit.prevent="savePlanning" class="tw-space-y-6">
      <!-- Header Section -->
      <div class="tw-bg-gradient-to-r tw-from-blue-50 tw-to-indigo-50 tw-rounded-xl tw-p-6 tw-border tw-border-blue-100 tw-shadow-sm">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-5">
          <div class="tw-w-10 tw-h-10 tw-bg-blue-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-shadow-md">
            <i class="pi pi-calendar-plus tw-text-white tw-text-lg"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-0">Basic Information</h3>
            <p class="tw-text-sm tw-text-gray-600 tw-mb-0">Select nurse and service details</p>
          </div>
        </div>

        <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
          <!-- Nurse Selection -->
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-user tw-text-blue-500"></i>
              Nurse <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown 
              v-model="form.nurse_id" 
              :options="nurses" 
              option-label="name" 
              option-value="id"
              placeholder="Select Nurse"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.nurse_id }"
              @change="checkConflicts"
              filter
            >
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2 tw-py-1">
                  <div class="tw-w-8 tw-h-8 tw-bg-gradient-to-br tw-from-blue-400 tw-to-blue-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-semibold tw-text-sm">
                    {{ slotProps.option.name ? slotProps.option.name.charAt(0).toUpperCase() : 'N' }}
                  </div>
                  <div class="tw-font-medium tw-text-gray-800">{{ slotProps.option.name }}</div>
                </div>
              </template>
              <template #value="slotProps">
                <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-w-7 tw-h-7 tw-bg-gradient-to-br tw-from-blue-400 tw-to-blue-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-semibold tw-text-xs">
                    {{ nurses.find(d => d.id === slotProps.value)?.name?.charAt(0).toUpperCase() || 'N' }}
                  </div>
                  <span class="tw-font-medium">{{ nurses.find(d => d.id === slotProps.value)?.name }}</span>
                </div>
                <span v-else class="tw-text-gray-500">{{ slotProps.placeholder }}</span>
              </template>
            </Dropdown>
            <small v-if="errors.nurse_id" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
              <i class="pi pi-exclamation-circle"></i>
              {{ errors.nurse_id }}
            </small>
          </div>

          <!-- Service Selection -->
          <div>
            <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-briefcase tw-text-blue-500"></i>
              Service <span class="tw-text-red-500">*</span>
            </label>
            <Dropdown 
              v-model="form.service_id" 
              :options="services" 
              option-label="name" 
              option-value="id"
              placeholder="Select Service"
              class="tw-w-full"
              :class="{ 'p-invalid': errors.service_id }"
              filter
            >
              <template #option="slotProps">
                <div class="tw-flex tw-items-center tw-gap-2 tw-py-1">
                  <i class="pi pi-circle-fill tw-text-indigo-500 tw-text-xs"></i>
                  <span class="tw-font-medium tw-text-gray-800">{{ slotProps.option.name }}</span>
                </div>
              </template>
            </Dropdown>
            <small v-if="errors.service_id" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
              <i class="pi pi-exclamation-circle"></i>
              {{ errors.service_id }}
            </small>
          </div>
        </div>
      </div>

      <!-- Schedule Section -->
      <div class="tw-bg-gradient-to-r tw-from-purple-50 tw-to-pink-50 tw-rounded-xl tw-p-6 tw-border tw-border-purple-100 tw-shadow-sm">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-5">
          <div class="tw-w-10 tw-h-10 tw-bg-purple-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-shadow-md">
            <i class="pi pi-clock tw-text-white tw-text-lg"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-0">Schedule Details</h3>
            <p class="tw-text-sm tw-text-gray-600 tw-mb-0">Set date and shift times</p>
          </div>
        </div>

        <!-- Date -->
        <div class="tw-mb-5">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-calendar tw-text-purple-500"></i>
            Planning Date <span class="tw-text-red-500">*</span>
          </label>
          <Calendar 
            v-model="form.planning_date" 
            placeholder="Select Date"
            class="tw-w-full"
            :class="{ 'p-invalid': errors.planning_date }"
            :min-date="new Date()"
            date-format="yy-mm-dd"
            @date-select="checkConflicts"
            showIcon
          />
          <small v-if="errors.planning_date" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
            <i class="pi pi-exclamation-circle"></i>
            {{ errors.planning_date }}
          </small>
        </div>

        <!-- Shift Times -->
        <div class="tw-space-y-4">
          <!-- Smart Scheduling Helper -->
          <div v-if="form.nurse_id && form.planning_date" class="tw-bg-gradient-to-r tw-from-emerald-50 tw-to-teal-50 tw-border-2 tw-border-emerald-200 tw-rounded-xl tw-p-4 tw-shadow-sm">
            <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
              <div class="tw-flex tw-items-center tw-gap-2">
                <div class="tw-w-8 tw-h-8 tw-bg-emerald-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-shadow">
                  <i class="pi pi-sparkles tw-text-white tw-text-sm"></i>
                </div>
                <span class="tw-font-bold tw-text-emerald-800 tw-text-base">AI Smart Scheduling</span>
              </div>
              <Button 
                @click="getSuggestedStartTime" 
                size="small" 
                class="p-button-success p-button-sm tw-shadow-sm hover:tw-shadow-md tw-transition-shadow"
                label="Auto-Schedule"
                icon="pi pi-bolt"
              />
            </div>
            <p class="tw-text-sm tw-text-emerald-700 tw-leading-relaxed">
              Let our AI find the optimal time slot automatically. It analyzes existing schedules to suggest the next available shift.
            </p>
          </div>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-sign-in tw-text-purple-500"></i>
                Start Time <span class="tw-text-red-500">*</span>
              </label>
              <Calendar 
                v-model="form.shift_start_time" 
                time-only 
                placeholder="Start Time"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.shift_start_time }"
                @date-select="checkConflicts"
                showIcon
                icon="pi pi-clock"
              />
              <small v-if="errors.shift_start_time" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
                <i class="pi pi-exclamation-circle"></i>
                {{ errors.shift_start_time }}
              </small>
            </div>
            <div>
              <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-sign-out tw-text-purple-500"></i>
                End Time <span class="tw-text-red-500">*</span>
              </label>
              <Calendar 
                v-model="form.shift_end_time" 
                time-only 
                placeholder="End Time"
                class="tw-w-full"
                :class="{ 'p-invalid': errors.shift_end_time }"
                @date-select="checkConflicts"
                showIcon
                icon="pi pi-clock"
              />
              <small v-if="errors.shift_end_time" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
                <i class="pi pi-exclamation-circle"></i>
                {{ errors.shift_end_time }}
              </small>
            </div>
          </div>
        </div>

        <!-- Shift Type -->
        <div class="tw-mt-5">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-sun tw-text-purple-500"></i>
            Shift Type <span class="tw-text-red-500">*</span>
          </label>
          <Dropdown 
            v-model="form.shift_type" 
            :options="shiftTypeOptions" 
            option-label="label" 
            option-value="value"
            append-to="self"
            placeholder="Select Shift Type"
            class="tw-w-full"
            :class="{ 'p-invalid': errors.shift_type }"
          >
            <template #option="slotProps">
              <div class="tw-flex tw-items-center tw-gap-3 tw-py-1">
                <i :class="getShiftIcon(slotProps.option.value)" class="tw-text-lg"></i>
                <span class="tw-font-medium">{{ slotProps.option.label }}</span>
              </div>
            </template>
            <template #value="slotProps">
              <div v-if="slotProps.value" class="tw-flex tw-items-center tw-gap-2">
                <i :class="getShiftIcon(slotProps.value)" class="tw-text-base"></i>
                <span>{{ shiftTypeOptions.find(s => s.value === slotProps.value)?.label }}</span>
              </div>
              <span v-else class="tw-text-gray-500">{{ slotProps.placeholder }}</span>
            </template>
          </Dropdown>
          <small v-if="errors.shift_type" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
            <i class="pi pi-exclamation-circle"></i>
            {{ errors.shift_type }}
          </small>
        </div>
      </div>

      <!-- Additional Details Section -->
      <div class="tw-bg-gradient-to-r tw-from-amber-50 tw-to-orange-50 tw-rounded-xl tw-p-6 tw-border tw-border-amber-100 tw-shadow-sm">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-5">
          <div class="tw-w-10 tw-h-10 tw-bg-amber-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-shadow-md">
            <i class="pi pi-file-edit tw-text-white tw-text-lg"></i>
          </div>
          <div>
            <h3 class="tw-text-lg tw-font-bold tw-text-gray-800 tw-mb-0">Additional Details</h3>
            <p class="tw-text-sm tw-text-gray-600 tw-mb-0">Optional notes and settings</p>
          </div>
        </div>

        <!-- Notes -->
        <div class="tw-mb-4">
          <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2 tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-comment tw-text-amber-500"></i>
            Notes
          </label>
          <Textarea 
            v-model="form.notes" 
            placeholder="Add any additional notes or special instructions..."
            rows="4"
            class="tw-w-full"
            :class="{ 'p-invalid': errors.notes }"
          />
          <small v-if="errors.notes" class="p-error tw-flex tw-items-center tw-gap-1 tw-mt-1">
            <i class="pi pi-exclamation-circle"></i>
            {{ errors.notes }}
          </small>
        </div>

        <!-- Active Status -->
        <div class="tw-flex tw-items-center tw-gap-3 tw-bg-white tw-rounded-lg tw-p-4 tw-border tw-border-amber-200">
          <Checkbox v-model="form.is_active" :binary="true" inputId="activeStatus" />
          <label for="activeStatus" class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-cursor-pointer tw-flex tw-items-center tw-gap-2">
            <i class="pi pi-check-circle tw-text-green-500"></i>
            Active Planning
          </label>
        </div>
      </div>

      <!-- Conflict Warning -->
      <div v-if="hasConflicts" class="tw-bg-gradient-to-r tw-from-red-50 tw-to-pink-50 tw-border-2 tw-border-red-300 tw-rounded-xl tw-p-6 tw-shadow-lg tw-animate-pulse">
        <div class="tw-flex tw-items-center tw-gap-3 tw-mb-4">
          <div class="tw-w-12 tw-h-12 tw-bg-red-500 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-shadow-md">
            <i class="pi pi-exclamation-triangle tw-text-white tw-text-xl"></i>
          </div>
          <div>
            <h3 class="tw-font-bold tw-text-red-800 tw-text-lg tw-mb-0">Schedule Conflict Detected!</h3>
            <p class="tw-text-sm tw-text-red-600 tw-mb-0">The following conflicts need your attention</p>
          </div>
        </div>

        <div class="tw-space-y-3 tw-mb-4">
          <div v-for="conflict in conflicts" :key="conflict.id" 
               class="tw-bg-white tw-border-2 tw-border-red-200 tw-rounded-lg tw-p-4 tw-shadow-sm hover:tw-shadow-md tw-transition-shadow">
            <div class="tw-flex tw-items-start tw-justify-between">
              <div class="tw-flex tw-items-start tw-gap-3">
                <div class="tw-w-10 tw-h-10 tw-bg-gradient-to-br tw-from-red-400 tw-to-red-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-text-white tw-font-bold tw-text-sm tw-flex-shrink-0">
                  {{ (conflict.nurse_display_name || conflict.nurse?.name || conflict.nurse?.display_name || 'Unknown').charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="tw-font-bold tw-text-red-900 tw-text-base tw-mb-1">
                    {{ conflict.nurse_display_name || conflict.nurse?.name || conflict.nurse?.display_name || 'Unknown Nurse' }}
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-red-700 tw-mb-2">
                    <i class="pi pi-clock"></i>
                    <span class="tw-font-semibold">{{ formatTime(conflict.shift_start_time) }} - {{ formatTime(conflict.shift_end_time) }}</span>
                  </div>
                  <div class="tw-text-xs tw-text-gray-600 tw-flex tw-items-center tw-gap-1">
                    <i class="pi pi-building tw-text-xs"></i>
                    {{ conflict.service?.name || conflict.service?.display_name || 'Unknown Service' }}
                  </div>
                </div>
              </div>
              <Tag 
                :value="conflict.shift_type" 
                :severity="getShiftTypeSeverity(conflict.shift_type)"
                class="tw-capitalize tw-font-semibold"
              />
            </div>
          </div>
        </div>

        <div class="tw-bg-gradient-to-r tw-from-yellow-50 tw-to-amber-50 tw-border-2 tw-border-yellow-300 tw-rounded-lg tw-p-4 tw-shadow-sm">
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
            <div class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-w-8 tw-h-8 tw-bg-yellow-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-shadow">
                <i class="pi pi-wrench tw-text-white"></i>
              </div>
              <span class="tw-font-bold tw-text-yellow-800">Quick Resolution</span>
            </div>
            <Button 
              @click="resolveConflict" 
              size="small" 
              class="p-button-warning p-button-sm tw-shadow-sm hover:tw-shadow-md tw-transition-shadow"
              label="Auto-Fix Now"
              icon="pi pi-bolt"
            />
          </div>
          <p class="tw-text-sm tw-text-yellow-800 tw-leading-relaxed">
            Our AI can automatically resolve this conflict by finding the next available time slot. 
            Click <span class="tw-font-semibold">"Auto-Fix Now"</span> or manually adjust the times above.
          </p>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-pt-4 tw-border-t tw-border-gray-200">
        <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-600">
          <i class="pi pi-info-circle tw-text-blue-500"></i>
          <span>Fields marked with <span class="tw-text-red-500 tw-font-semibold">*</span> are required</span>
        </div>
        <div class="tw-flex tw-gap-3">
          <Button 
            @click="$emit('update:visible', false)" 
            label="Cancel" 
            class="p-button-text p-button-secondary tw-font-semibold hover:tw-bg-gray-100"
            icon="pi pi-times"
          />
          <Button 
            @click="savePlanning" 
            :label="isEditing ? 'Update Planning' : 'Create Planning'" 
            :loading="saving"
            class="p-button-success tw-font-semibold tw-shadow-md hover:tw-shadow-lg tw-transition-shadow"
            :icon="isEditing ? 'pi pi-check' : 'pi pi-plus'"
            :disabled="hasConflicts"
          />
        </div>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import axios from 'axios'

const props = defineProps({
  visible: Boolean,
  planning: Object,
  nurses: Array,
  services: Array
})

const emit = defineEmits(['update:visible', 'saved'])

const toast = useToast()

// Data
const form = ref({
  nurse_id: null,
  service_id: null,
  planning_date: null,
  shift_start_time: null,
  shift_end_time: null,
  shift_type: null,
  notes: '',
  is_active: true
})

const errors = ref({})
const saving = ref(false)
const conflicts = ref([])
const hasConflicts = ref(false)

// Options
const shiftTypeOptions = [
  { label: 'Day Shift', value: 'day' },
  { label: 'Night Shift', value: 'night' },
  { label: 'Emergency Shift', value: 'emergency' }
]

// Computed
const isEditing = computed(() => !!props.planning?.id)

// Methods
const resetForm = () => {
  form.value = {
    nurse_id: null,
    service_id: null,
    planning_date: null,
    shift_start_time: null,
    shift_end_time: null,
    shift_type: null,
    notes: '',
    is_active: true
  }
  errors.value = {}
  conflicts.value = []
  hasConflicts.value = false
}

const populateForm = () => {
  if (props.planning) {
    form.value = {
      nurse_id: props.planning.nurse_id,
      service_id: props.planning.service_id,
      planning_date: props.planning.planning_date instanceof Date 
        ? props.planning.planning_date 
        : new Date(props.planning.planning_date),
      shift_start_time: props.planning.shift_start_time ? 
        (typeof props.planning.shift_start_time === 'string' && props.planning.shift_start_time.includes(':')
          ? new Date('2000-01-01 ' + props.planning.shift_start_time)
          : props.planning.shift_start_time) : null,
      shift_end_time: props.planning.shift_end_time ? 
        (typeof props.planning.shift_end_time === 'string' && props.planning.shift_end_time.includes(':')
          ? new Date('2000-01-01 ' + props.planning.shift_end_time)
          : props.planning.shift_end_time) : null,
      shift_type: props.planning.shift_type,
      notes: props.planning.notes || '',
      is_active: props.planning.is_active ?? true
    }
  }
}

const checkConflicts = async () => {
  if (!form.value.nurse_id || !form.value.planning_date || 
      !form.value.shift_start_time || !form.value.shift_end_time) {
    hasConflicts.value = false
    conflicts.value = []
    return
  }

  try {
    const params = {
      nurse_id: form.value.nurse_id,
      planning_date: formatDateForAPI(form.value.planning_date),
      shift_start_time: formatTimeForAPI(form.value.shift_start_time),
      shift_end_time: formatTimeForAPI(form.value.shift_end_time)
    }

    if (isEditing.value) {
      params.exclude_id = props.planning.id
    }

    const response = await axios.post('/api/nursing-emergency-planning/check-conflicts', params)
    hasConflicts.value = response.data.has_conflicts
    conflicts.value = response.data.conflicts || []
  } catch (error) {
    console.error('Failed to check conflicts:', error)
    hasConflicts.value = false
    conflicts.value = []
  }
}

const getSuggestedStartTime = async () => {
  if (!form.value.planning_date) {
    return
  }

  try {
    const params = {
      planning_date: formatDateForAPI(form.value.planning_date),
      smart: 'true'
    }

    const response = await axios.get('/api/nursing-emergency-planning/next-available-time', { params })
    
    if (response.data.success && response.data.suggestion) {
      const suggestion = response.data.suggestion
      
      // Set the suggested times
      form.value.shift_start_time = new Date(`2000-01-01 ${suggestion.suggested_start_time}:00`)
      form.value.shift_end_time = new Date(`2000-01-01 ${suggestion.suggested_end_time}:00`)
      
      // Set shift type based on time
      if (suggestion.is_overnight) {
        form.value.shift_type = 'night'
      } else {
        const startHour = parseInt(suggestion.suggested_start_time.split(':')[0])
        if (startHour >= 6 && startHour < 14) {
          form.value.shift_type = 'day'
        } else if (startHour >= 14 && startHour < 22) {
          form.value.shift_type = 'evening'
        } else {
          form.value.shift_type = 'night'
        }
      }
      
      // Update date if next day is suggested
      if (suggestion.next_day && suggestion.suggested_date) {
        form.value.planning_date = new Date(suggestion.suggested_date)
      }
      
      let message = `Suggested: ${suggestion.suggested_start_time} - ${suggestion.suggested_end_time}`
      if (suggestion.is_overnight) {
        message += ' (overnight shift)'
      }
      if (suggestion.next_day) {
        message += ' (next day)'
      }
      
      toast.add({
        severity: 'info',
        summary: 'Smart Time Suggestion',
        detail: message,
        life: 4000
      })
    }
  } catch (error) {
    console.error('Failed to get suggested time:', error)
    
    // Fallback to simple suggestion
    try {
      const params = {
        planning_date: formatDateForAPI(form.value.planning_date),
        nurse_id: form.value.nurse_id
      }

      const response = await axios.get('/api/nursing-emergency-planning/next-available-time', { params })
      
      if (response.data.success && response.data.suggested_start_time) {
        const suggestedTime = response.data.suggested_start_time
        form.value.shift_start_time = new Date(`2000-01-01 ${suggestedTime}:00`)
        
        toast.add({
          severity: 'info',
          summary: 'Time Suggestion',
          detail: `Suggested start time: ${suggestedTime}`,
          life: 3000
        })
      }
    } catch (fallbackError) {
      console.error('Failed to get fallback suggestion:', fallbackError)
    }
  }
}

const resolveConflict = async () => {
  if (!hasConflicts.value || !form.value.planning_date) {
    return
  }

  // Get smart suggestion to resolve conflict
  await getSuggestedStartTime()
  
  // Recheck conflicts after applying suggestion
  setTimeout(() => {
    checkConflicts()
  }, 500)
}

const savePlanning = async () => {
  if (hasConflicts.value) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Cannot save planning due to schedule conflicts',
      life: 3000
    })
    return
  }

  saving.value = true
  errors.value = {}

  try {
    const payload = {
      ...form.value,
      planning_date: formatDateForAPI(form.value.planning_date),
      shift_start_time: formatTimeForAPI(form.value.shift_start_time),
      shift_end_time: formatTimeForAPI(form.value.shift_end_time),
      month: form.value.planning_date ? form.value.planning_date.getMonth() + 1 : null,
      year: form.value.planning_date ? form.value.planning_date.getFullYear() : null
    }

    let response
    if (isEditing.value) {
      response = await axios.put(`/api/nursing-emergency-planning/${props.planning.id}`, payload)
    } else {
      response = await axios.post('/api/nursing-emergency-planning', payload)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: response.data.message,
      life: 3000
    })

    emit('saved')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save planning',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

// Utility functions
const formatDateForAPI = (date) => {
  if (!date) return null
  const d = new Date(date)
  return d.getFullYear() + '-' + 
         String(d.getMonth() + 1).padStart(2, '0') + '-' + 
         String(d.getDate()).padStart(2, '0')
}

const formatTimeForAPI = (time) => {
  if (!time) return null
  const t = new Date(time)
  return String(t.getHours()).padStart(2, '0') + ':' + 
         String(t.getMinutes()).padStart(2, '0')
}

const formatTime = (time) => {
  return new Date('2000-01-01 ' + time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getShiftTypeSeverity = (type) => {
  switch (type) {
    case 'day': return 'success'
    case 'night': return 'info'
    case 'emergency': return 'danger'
    default: return 'secondary'
  }
}

const getShiftIcon = (shiftType) => {
  switch (shiftType) {
    case 'day': return 'pi pi-sun'
    case 'night': return 'pi pi-moon'
    case 'emergency': return 'pi pi-exclamation-triangle'
    default: return 'pi pi-clock'
  }
}

// Watchers
watch(() => props.visible, (newVal) => {
  if (newVal) {
    nextTick(() => {
      if (props.planning) {
        populateForm()
      } else {
        resetForm()
      }
    })
  }
})

watch(() => props.planning, () => {
  if (props.visible && props.planning) {
    populateForm()
  }
}, { deep: true })

// Watch form changes to check conflicts
watch([
  () => form.value.nurse_id,
  () => form.value.planning_date,
  () => form.value.shift_start_time,
  () => form.value.shift_end_time
], () => {
  checkConflicts()
}, { deep: true })
</script>

<style scoped>
:deep(.p-calendar input) {
  width: 100%;
}

:deep(.p-dropdown) {
  width: 100%;
}
</style>
