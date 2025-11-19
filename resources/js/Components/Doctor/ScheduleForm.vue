<template>
  <div class="schedule-form">
    <Form @submit="handleSubmit" :validation-schema="validationSchema" v-slot="{ errors, values }">
      <div class="form-grid">
        <!-- Basic Schedule Information -->
        <Card class="form-section">
          <template #header>
            <div class="section-header">
              <i class="pi pi-calendar"></i>
              <h3>Basic Information</h3>
            </div>
          </template>
          
          <template #content>
            <div class="field-grid">
              <div class="field">
                <label for="day_of_week">Day of Week *</label>
                <Field name="day_of_week" v-slot="{ field }">
                  <Dropdown
                    v-bind="field"
                    :options="daysOfWeek"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Day"
                    :class="{ 'p-invalid': errors.day_of_week }"
                    class="w-full"
                  />
                </Field>
                <small class="p-error">{{ errors.day_of_week }}</small>
              </div>

              <div class="field">
                <label for="shift_period">Shift Period *</label>
                <Field name="shift_period" v-slot="{ field }">
                  <SelectButton
                    v-bind="field"
                    :options="shiftPeriods"
                    optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': errors.shift_period }"
                  />
                </Field>
                <small class="p-error">{{ errors.shift_period }}</small>
              </div>

              <div class="field">
                <label for="date">Specific Date (Optional)</label>
                <Field name="date" v-slot="{ field }">
                  <Calendar
                    v-bind="field"
                    placeholder="Select specific date"
                    :class="{ 'p-invalid': errors.date }"
                    class="w-full"
                    :minDate="new Date()"
                  />
                </Field>
                <small class="p-error">{{ errors.date }}</small>
                <small class="field-help">Leave empty for recurring weekly schedule</small>
              </div>
            </div>
          </template>
        </Card>

        <!-- Time Configuration -->
        <Card class="form-section">
          <template #header>
            <div class="section-header">
              <i class="pi pi-clock"></i>
              <h3>Time Configuration</h3>
            </div>
          </template>
          
          <template #content>
            <div class="field-grid">
              <div class="field">
                <label for="start_time">Start Time *</label>
                <Field name="start_time" v-slot="{ field }">
                  <InputMask
                    v-bind="field"
                    mask="99:99"
                    placeholder="08:00"
                    :class="{ 'p-invalid': errors.start_time }"
                    class="w-full"
                  />
                </Field>
                <small class="p-error">{{ errors.start_time }}</small>
              </div>

              <div class="field">
                <label for="end_time">End Time *</label>
                <Field name="end_time" v-slot="{ field }">
                  <InputMask
                    v-bind="field"
                    mask="99:99"
                    placeholder="17:00"
                    :class="{ 'p-invalid': errors.end_time }"
                    class="w-full"
                  />
                </Field>
                <small class="p-error">{{ errors.end_time }}</small>
              </div>

              <div class="field">
                <label for="number_of_patients_per_day">Patients Per Day *</label>
                <Field name="number_of_patients_per_day" v-slot="{ field }">
                  <InputNumber
                    v-bind="field"
                    :min="1"
                    :max="100"
                    :class="{ 'p-invalid': errors.number_of_patients_per_day }"
                    class="w-full"
                    :showButtons="true"
                  />
                </Field>
                <small class="p-error">{{ errors.number_of_patients_per_day }}</small>
              </div>
            </div>
          </template>
        </Card>

        <!-- Break Configuration -->
        <Card class="form-section">
          <template #header>
            <div class="section-header">
              <i class="pi pi-pause"></i>
              <h3>Break Configuration</h3>
            </div>
          </template>
          
          <template #content>
            <div class="field-grid">
              <div class="field">
                <label for="break_duration">Break Duration (minutes)</label>
                <Field name="break_duration" v-slot="{ field }">
                  <InputNumber
                    v-bind="field"
                    :min="0"
                    :max="120"
                    :step="15"
                    placeholder="30"
                    :class="{ 'p-invalid': errors.break_duration }"
                    class="w-full"
                    :showButtons="true"
                  />
                </Field>
                <small class="p-error">{{ errors.break_duration }}</small>
                <small class="field-help">Total break duration for this shift</small>
              </div>

              <div class="field full-width" v-if="values.break_duration > 0">
                <label>Break Times</label>
                <div class="break-times-container">
                  <div 
                    v-for="(breakTime, index) in breakTimes" 
                    :key="index"
                    class="break-time-item"
                  >
                    <div class="break-time-inputs">
                      <InputMask
                        v-model="breakTime.start"
                        mask="99:99"
                        placeholder="Start time"
                        class="break-input"
                      />
                      <span class="break-separator">to</span>
                      <InputMask
                        v-model="breakTime.end"
                        mask="99:99"
                        placeholder="End time"
                        class="break-input"
                      />
                    </div>
                    <Button
                      icon="pi pi-trash"
                      @click="removeBreakTime(index)"
                      class="p-button-text p-button-danger p-button-sm"
                      v-tooltip="'Remove break'"
                    />
                  </div>
                  
                  <Button
                    icon="pi pi-plus"
                    label="Add Break Time"
                    @click="addBreakTime"
                    class="p-button-outlined p-button-sm"
                  />
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Advanced Settings -->
        <Card class="form-section">
          <template #header>
            <div class="section-header">
              <i class="pi pi-cog"></i>
              <h3>Advanced Settings</h3>
            </div>
          </template>
          
          <template #content>
            <div class="field-grid">
              <div class="field">
                <div class="checkbox-field">
                  <Field name="is_active" v-slot="{ field }">
                    <Checkbox
                      v-bind="field"
                      :binary="true"
                      inputId="is_active"
                    />
                  </Field>
                  <label for="is_active">Active Schedule</label>
                </div>
                <small class="field-help">Inactive schedules won't be available for appointments</small>
              </div>

              <div class="field full-width">
                <label>Excluded Dates</label>
                <div class="excluded-dates-container">
                  <div 
                    v-for="(excludedDate, index) in excludedDates" 
                    :key="index"
                    class="excluded-date-item"
                  >
                    <Calendar
                      v-model="excludedDate.date"
                      placeholder="Select date to exclude"
                      class="excluded-date-input"
                      :minDate="new Date()"
                    />
                    <Button
                      icon="pi pi-trash"
                      @click="removeExcludedDate(index)"
                      class="p-button-text p-button-danger p-button-sm"
                      v-tooltip="'Remove excluded date'"
                    />
                  </div>
                  
                  <Button
                    icon="pi pi-plus"
                    label="Add Excluded Date"
                    @click="addExcludedDate"
                    class="p-button-outlined p-button-sm"
                  />
                </div>
                <small class="field-help">Dates when this schedule should not be available</small>
              </div>

              <div class="field full-width">
                <label>Modified Times for Specific Dates</label>
                <div class="modified-times-container">
                  <div 
                    v-for="(modifiedTime, index) in modifiedTimes" 
                    :key="index"
                    class="modified-time-item"
                  >
                    <Calendar
                      v-model="modifiedTime.date"
                      placeholder="Select date"
                      class="modified-date-input"
                      :minDate="new Date()"
                    />
                    <InputMask
                      v-model="modifiedTime.start_time"
                      mask="99:99"
                      placeholder="Start time"
                      class="modified-time-input"
                    />
                    <InputMask
                      v-model="modifiedTime.end_time"
                      mask="99:99"
                      placeholder="End time"
                      class="modified-time-input"
                    />
                    <Button
                      icon="pi pi-trash"
                      @click="removeModifiedTime(index)"
                      class="p-button-text p-button-danger p-button-sm"
                      v-tooltip="'Remove modified time'"
                    />
                  </div>
                  
                  <Button
                    icon="pi pi-plus"
                    label="Add Modified Time"
                    @click="addModifiedTime"
                    class="p-button-outlined p-button-sm"
                  />
                </div>
                <small class="field-help">Override schedule times for specific dates</small>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <Button
          type="button"
          label="Cancel"
          @click="$emit('cancel')"
          class="p-button-outlined"
        />
        <Button
          type="submit"
          :label="schedule?.id ? 'Update Schedule' : 'Create Schedule'"
          :loading="loading"
          class="p-button-success"
        />
      </div>
    </Form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Form, Field } from 'vee-validate'
import * as yup from 'yup'

// PrimeVue Components
import Card from 'primevue/card'
import Button from 'primevue/button'
import Dropdown from 'primevue/dropdown'
import SelectButton from 'primevue/selectbutton'
import Calendar from 'primevue/calendar'
import InputMask from 'primevue/inputmask'
import InputNumber from 'primevue/inputnumber'
import Checkbox from 'primevue/checkbox'

// Props and Emits
const props = defineProps({
  schedule: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['save', 'cancel'])

// Reactive Data
const breakTimes = ref([])
const excludedDates = ref([])
const modifiedTimes = ref([])

// Constants
const daysOfWeek = [
  { label: 'Monday', value: 'monday' },
  { label: 'Tuesday', value: 'tuesday' },
  { label: 'Wednesday', value: 'wednesday' },
  { label: 'Thursday', value: 'thursday' },
  { label: 'Friday', value: 'friday' },
  { label: 'Saturday', value: 'saturday' },
  { label: 'Sunday', value: 'sunday' }
]

const shiftPeriods = [
  { label: 'Morning', value: 'morning' },
  { label: 'Afternoon', value: 'afternoon' }
]

// Validation Schema
const validationSchema = yup.object({
  day_of_week: yup.string().required('Day of week is required'),
  shift_period: yup.string().required('Shift period is required'),
  start_time: yup.string()
    .required('Start time is required')
    .matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/, 'Invalid time format'),
  end_time: yup.string()
    .required('End time is required')
    .matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/, 'Invalid time format')
    .test('is-after-start', 'End time must be after start time', function(value) {
      const { start_time } = this.parent
      if (!start_time || !value) return true
      return value > start_time
    }),
  number_of_patients_per_day: yup.number()
    .required('Number of patients is required')
    .min(1, 'Must be at least 1 patient')
    .max(100, 'Cannot exceed 100 patients'),
  break_duration: yup.number().min(0, 'Break duration cannot be negative'),
  date: yup.date().nullable(),
  is_active: yup.boolean()
})

// Methods
const handleSubmit = (values) => {
  const scheduleData = {
    ...values,
    break_times: breakTimes.value.length > 0 ? JSON.stringify(breakTimes.value) : null,
    excluded_dates: excludedDates.value.length > 0 ? JSON.stringify(excludedDates.value.map(ed => ed.date)) : null,
    modified_times: modifiedTimes.value.length > 0 ? JSON.stringify(modifiedTimes.value) : null
  }
  
  emit('save', scheduleData)
}

const addBreakTime = () => {
  breakTimes.value.push({ start: '', end: '' })
}

const removeBreakTime = (index) => {
  breakTimes.value.splice(index, 1)
}

const addExcludedDate = () => {
  excludedDates.value.push({ date: null })
}

const removeExcludedDate = (index) => {
  excludedDates.value.splice(index, 1)
}

const addModifiedTime = () => {
  modifiedTimes.value.push({ date: null, start_time: '', end_time: '' })
}

const removeModifiedTime = (index) => {
  modifiedTimes.value.splice(index, 1)
}

const populateFormData = () => {
  if (props.schedule) {
    // Populate break times
    if (props.schedule.break_times) {
      try {
        breakTimes.value = JSON.parse(props.schedule.break_times)
      } catch (e) {
        breakTimes.value = []
      }
    }
    
    // Populate excluded dates
    if (props.schedule.excluded_dates) {
      try {
        const dates = JSON.parse(props.schedule.excluded_dates)
        excludedDates.value = dates.map(date => ({ date: new Date(date) }))
      } catch (e) {
        excludedDates.value = []
      }
    }
    
    // Populate modified times
    if (props.schedule.modified_times) {
      try {
        const times = JSON.parse(props.schedule.modified_times)
        modifiedTimes.value = times.map(time => ({
          date: new Date(time.date),
          start_time: time.start_time,
          end_time: time.end_time
        }))
      } catch (e) {
        modifiedTimes.value = []
      }
    }
  }
}

// Watchers
watch(() => props.schedule, populateFormData, { immediate: true })

// Lifecycle
onMounted(() => {
  populateFormData()
})
</script>

<style scoped>
.schedule-form {
  padding: 1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-section {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e2e8f0;
}

.section-header i {
  color: #4299e1;
}

.section-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field.full-width {
  grid-column: 1 / -1;
}

.field label {
  font-weight: 600;
  color: #4a5568;
  font-size: 0.875rem;
}

.field-help {
  color: #718096;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.checkbox-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.break-times-container,
.excluded-dates-container,
.modified-times-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
}

.break-time-item,
.excluded-date-item,
.modified-time-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: white;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.break-time-inputs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.break-input,
.modified-time-input {
  flex: 1;
}

.break-separator {
  color: #718096;
  font-weight: 500;
}

.excluded-date-input,
.modified-date-input {
  flex: 1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8f9fa;
  border-radius: 0 0 8px 8px;
  margin: 0 -1rem -1rem -1rem;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .field-grid {
    grid-template-columns: 1fr;
  }
  
  .break-time-item,
  .excluded-date-item,
  .modified-time-item {
    flex-direction: column;
    align-items: stretch;
  }
  
  .break-time-inputs {
    flex-direction: column;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>