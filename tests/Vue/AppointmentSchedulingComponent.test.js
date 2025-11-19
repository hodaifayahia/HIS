import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import AppointmentSchedulingComponent from '@/components/Appointment/AppointmentSchedulingComponent.vue'
import axios from 'axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

describe('AppointmentSchedulingComponent', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(AppointmentSchedulingComponent, {
      global: {
        mocks: {
          $router: {
            push: vi.fn()
          }
        }
      }
    })
  })

  describe('Component Existence and Props', () => {
    it('renders the appointment scheduling component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('has all required form fields', () => {
      expect(wrapper.find('select[name="patient_id"]').exists()).toBe(true)
      expect(wrapper.find('select[name="doctor_id"]').exists()).toBe(true)
      expect(wrapper.find('input[name="appointment_date"]').exists()).toBe(true)
      expect(wrapper.find('select[name="appointment_time"]').exists()).toBe(true)
      expect(wrapper.find('select[name="appointment_type"]').exists()).toBe(true)
      expect(wrapper.find('textarea[name="notes"]').exists()).toBe(true)
    })

    it('has schedule appointment button', () => {
      const scheduleButton = wrapper.find('button[type="submit"]')
      expect(scheduleButton.exists()).toBe(true)
      expect(scheduleButton.text()).toContain('Schedule Appointment')
    })
  })

  describe('Form Data Binding', () => {
    it('updates patient selection when changed', async () => {
      const patientSelect = wrapper.find('select[name="patient_id"]')
      await patientSelect.setValue('123')
      
      expect(wrapper.vm.appointment.patient_id).toBe('123')
    })

    it('updates doctor selection when changed', async () => {
      const doctorSelect = wrapper.find('select[name="doctor_id"]')
      await doctorSelect.setValue('456')
      
      expect(wrapper.vm.appointment.doctor_id).toBe('456')
    })

    it('updates appointment date when changed', async () => {
      const dateInput = wrapper.find('input[name="appointment_date"]')
      await dateInput.setValue('2024-12-25')
      
      expect(wrapper.vm.appointment.appointment_date).toBe('2024-12-25')
    })

    it('updates appointment time when changed', async () => {
      const timeSelect = wrapper.find('select[name="appointment_time"]')
      await timeSelect.setValue('10:00')
      
      expect(wrapper.vm.appointment.appointment_time).toBe('10:00')
    })

    it('updates appointment type when changed', async () => {
      const typeSelect = wrapper.find('select[name="appointment_type"]')
      await typeSelect.setValue('consultation')
      
      expect(wrapper.vm.appointment.appointment_type).toBe('consultation')
    })

    it('updates notes when changed', async () => {
      const notesTextarea = wrapper.find('textarea[name="notes"]')
      await notesTextarea.setValue('Patient needs follow-up')
      
      expect(wrapper.vm.appointment.notes).toBe('Patient needs follow-up')
    })
  })

  describe('Form Validation', () => {
    it('shows validation error for empty patient selection', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.patient-error').text()).toContain('Patient is required')
    })

    it('shows validation error for empty doctor selection', async () => {
      const patientSelect = wrapper.find('select[name="patient_id"]')
      await patientSelect.setValue('123')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.doctor-error').text()).toContain('Doctor is required')
    })

    it('shows validation error for past appointment date', async () => {
      const dateInput = wrapper.find('input[name="appointment_date"]')
      const pastDate = new Date()
      pastDate.setDate(pastDate.getDate() - 1)
      await dateInput.setValue(pastDate.toISOString().split('T')[0])
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.date-error').text()).toContain('Appointment date cannot be in the past')
    })

    it('shows validation error for empty appointment time', async () => {
      const patientSelect = wrapper.find('select[name="patient_id"]')
      const doctorSelect = wrapper.find('select[name="doctor_id"]')
      const dateInput = wrapper.find('input[name="appointment_date"]')
      
      await patientSelect.setValue('123')
      await doctorSelect.setValue('456')
      await dateInput.setValue('2024-12-25')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.time-error').text()).toContain('Appointment time is required')
    })

    it('shows validation error for empty appointment type', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.type-error').text()).toContain('Appointment type is required')
    })
  })

  describe('Time Slot Availability', () => {
    it('loads available time slots when doctor and date are selected', async () => {
      mockedAxios.get.mockResolvedValue({
        data: {
          available_slots: ['09:00', '10:00', '11:00', '14:00', '15:00']
        }
      })

      const doctorSelect = wrapper.find('select[name="doctor_id"]')
      const dateInput = wrapper.find('input[name="appointment_date"]')
      
      await doctorSelect.setValue('456')
      await dateInput.setValue('2024-12-25')

      await wrapper.vm.$nextTick()

      expect(mockedAxios.get).toHaveBeenCalledWith('/api/appointments/available-slots', {
        params: {
          doctor_id: '456',
          date: '2024-12-25'
        }
      })

      expect(wrapper.vm.availableTimeSlots).toEqual(['09:00', '10:00', '11:00', '14:00', '15:00'])
    })

    it('disables unavailable time slots', async () => {
      await wrapper.setData({
        availableTimeSlots: ['09:00', '11:00', '15:00']
      })

      const timeOptions = wrapper.findAll('select[name="appointment_time"] option')
      const unavailableOption = timeOptions.find(option => option.attributes('value') === '10:00')
      
      expect(unavailableOption.attributes('disabled')).toBeDefined()
    })
  })

  describe('API Integration', () => {
    it('calls appointment scheduling API with correct data', async () => {
      mockedAxios.post.mockResolvedValue({
        data: {
          id: 1,
          patient_id: 123,
          doctor_id: 456,
          appointment_date: '2024-12-25',
          appointment_time: '10:00'
        }
      })

      // Fill form with valid data
      await wrapper.find('select[name="patient_id"]').setValue('123')
      await wrapper.find('select[name="doctor_id"]').setValue('456')
      await wrapper.find('input[name="appointment_date"]').setValue('2024-12-25')
      await wrapper.find('select[name="appointment_time"]').setValue('10:00')
      await wrapper.find('select[name="appointment_type"]').setValue('consultation')
      await wrapper.find('textarea[name="notes"]').setValue('Regular checkup')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(mockedAxios.post).toHaveBeenCalledWith('/api/appointments', {
        patient_id: '123',
        doctor_id: '456',
        appointment_date: '2024-12-25',
        appointment_time: '10:00',
        appointment_type: 'consultation',
        notes: 'Regular checkup'
      })
    })

    it('handles successful appointment scheduling', async () => {
      const mockResponse = {
        data: {
          id: 1,
          patient_id: 123,
          doctor_id: 456,
          appointment_date: '2024-12-25',
          appointment_time: '10:00'
        }
      }
      
      mockedAxios.post.mockResolvedValue(mockResponse)

      // Fill form with valid data
      await wrapper.find('select[name="patient_id"]').setValue('123')
      await wrapper.find('select[name="doctor_id"]').setValue('456')
      await wrapper.find('input[name="appointment_date"]').setValue('2024-12-25')
      await wrapper.find('select[name="appointment_time"]').setValue('10:00')
      await wrapper.find('select[name="appointment_type"]').setValue('consultation')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()

      expect(wrapper.find('.success-message').text()).toContain('Appointment scheduled successfully')
      expect(wrapper.vm.$router.push).toHaveBeenCalledWith('/appointments/1')
    })

    it('handles appointment scheduling API error', async () => {
      mockedAxios.post.mockRejectedValue({
        response: {
          data: {
            message: 'Time slot not available',
            errors: {
              appointment_time: ['The selected time slot is no longer available.']
            }
          }
        }
      })

      // Fill form with valid data
      await wrapper.find('select[name="patient_id"]').setValue('123')
      await wrapper.find('select[name="doctor_id"]').setValue('456')
      await wrapper.find('input[name="appointment_date"]').setValue('2024-12-25')
      await wrapper.find('select[name="appointment_time"]').setValue('10:00')
      await wrapper.find('select[name="appointment_type"]').setValue('consultation')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()

      expect(wrapper.find('.error-message').text()).toContain('Time slot not available')
      expect(wrapper.find('.time-error').text()).toContain('The selected time slot is no longer available.')
    })

    it('shows loading state during API call', async () => {
      mockedAxios.post.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))

      // Fill form with valid data
      await wrapper.find('select[name="patient_id"]').setValue('123')
      await wrapper.find('select[name="doctor_id"]').setValue('456')
      await wrapper.find('select[name="appointment_type"]').setValue('consultation')

      const form = wrapper.find('form')
      form.trigger('submit.prevent')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
      expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
      expect(wrapper.find('button[type="submit"]').text()).toContain('Scheduling...')
    })
  })

  describe('Patient and Doctor Loading', () => {
    it('loads patients on component mount', async () => {
      mockedAxios.get.mockResolvedValue({
        data: [
          { id: 1, name: 'John Doe' },
          { id: 2, name: 'Jane Smith' }
        ]
      })

      wrapper = mount(AppointmentSchedulingComponent)
      await wrapper.vm.$nextTick()

      expect(mockedAxios.get).toHaveBeenCalledWith('/api/patients')
      expect(wrapper.vm.patients).toEqual([
        { id: 1, name: 'John Doe' },
        { id: 2, name: 'Jane Smith' }
      ])
    })

    it('loads doctors on component mount', async () => {
      mockedAxios.get.mockResolvedValue({
        data: [
          { id: 1, name: 'Dr. Smith' },
          { id: 2, name: 'Dr. Johnson' }
        ]
      })

      wrapper = mount(AppointmentSchedulingComponent)
      await wrapper.vm.$nextTick()

      expect(mockedAxios.get).toHaveBeenCalledWith('/api/doctors')
      expect(wrapper.vm.doctors).toEqual([
        { id: 1, name: 'Dr. Smith' },
        { id: 2, name: 'Dr. Johnson' }
      ])
    })
  })
})