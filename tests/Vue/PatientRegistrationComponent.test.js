import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import PatientRegistrationComponent from '@/components/Patient/PatientRegistrationComponent.vue'
import axios from 'axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

describe('PatientRegistrationComponent', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(PatientRegistrationComponent, {
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
    it('renders the patient registration component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('has all required form fields', () => {
      expect(wrapper.find('input[name="first_name"]').exists()).toBe(true)
      expect(wrapper.find('input[name="last_name"]').exists()).toBe(true)
      expect(wrapper.find('input[name="email"]').exists()).toBe(true)
      expect(wrapper.find('input[name="phone"]').exists()).toBe(true)
      expect(wrapper.find('input[name="date_of_birth"]').exists()).toBe(true)
      expect(wrapper.find('select[name="gender"]').exists()).toBe(true)
      expect(wrapper.find('textarea[name="address"]').exists()).toBe(true)
    })

    it('has submit button', () => {
      const submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.exists()).toBe(true)
      expect(submitButton.text()).toContain('Register Patient')
    })
  })

  describe('Form Data Binding', () => {
    it('updates first name when input changes', async () => {
      const firstNameInput = wrapper.find('input[name="first_name"]')
      await firstNameInput.setValue('John')
      
      expect(wrapper.vm.patient.first_name).toBe('John')
    })

    it('updates last name when input changes', async () => {
      const lastNameInput = wrapper.find('input[name="last_name"]')
      await lastNameInput.setValue('Doe')
      
      expect(wrapper.vm.patient.last_name).toBe('Doe')
    })

    it('updates email when input changes', async () => {
      const emailInput = wrapper.find('input[name="email"]')
      await emailInput.setValue('john.doe@example.com')
      
      expect(wrapper.vm.patient.email).toBe('john.doe@example.com')
    })

    it('updates phone when input changes', async () => {
      const phoneInput = wrapper.find('input[name="phone"]')
      await phoneInput.setValue('+1234567890')
      
      expect(wrapper.vm.patient.phone).toBe('+1234567890')
    })

    it('updates date of birth when input changes', async () => {
      const dobInput = wrapper.find('input[name="date_of_birth"]')
      await dobInput.setValue('1990-01-01')
      
      expect(wrapper.vm.patient.date_of_birth).toBe('1990-01-01')
    })

    it('updates gender when select changes', async () => {
      const genderSelect = wrapper.find('select[name="gender"]')
      await genderSelect.setValue('male')
      
      expect(wrapper.vm.patient.gender).toBe('male')
    })

    it('updates address when textarea changes', async () => {
      const addressTextarea = wrapper.find('textarea[name="address"]')
      await addressTextarea.setValue('123 Main St, City, Country')
      
      expect(wrapper.vm.patient.address).toBe('123 Main St, City, Country')
    })
  })

  describe('Form Validation', () => {
    it('shows validation error for empty first name', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.first-name-error').text()).toContain('First name is required')
    })

    it('shows validation error for empty last name', async () => {
      const firstNameInput = wrapper.find('input[name="first_name"]')
      await firstNameInput.setValue('John')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.last-name-error').text()).toContain('Last name is required')
    })

    it('shows validation error for invalid email format', async () => {
      const emailInput = wrapper.find('input[name="email"]')
      await emailInput.setValue('invalid-email')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.email-error').text()).toContain('Please enter a valid email')
    })

    it('shows validation error for invalid phone format', async () => {
      const phoneInput = wrapper.find('input[name="phone"]')
      await phoneInput.setValue('invalid-phone')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.phone-error').text()).toContain('Please enter a valid phone number')
    })

    it('shows validation error for future date of birth', async () => {
      const dobInput = wrapper.find('input[name="date_of_birth"]')
      const futureDate = new Date()
      futureDate.setFullYear(futureDate.getFullYear() + 1)
      await dobInput.setValue(futureDate.toISOString().split('T')[0])
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.dob-error').text()).toContain('Date of birth cannot be in the future')
    })

    it('shows validation error for empty gender selection', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.gender-error').text()).toContain('Gender is required')
    })
  })

  describe('API Integration', () => {
    it('calls patient registration API with correct data', async () => {
      mockedAxios.post.mockResolvedValue({
        data: {
          id: 1,
          first_name: 'John',
          last_name: 'Doe',
          email: 'john.doe@example.com'
        }
      })

      // Fill form with valid data
      await wrapper.find('input[name="first_name"]').setValue('John')
      await wrapper.find('input[name="last_name"]').setValue('Doe')
      await wrapper.find('input[name="email"]').setValue('john.doe@example.com')
      await wrapper.find('input[name="phone"]').setValue('+1234567890')
      await wrapper.find('input[name="date_of_birth"]').setValue('1990-01-01')
      await wrapper.find('select[name="gender"]').setValue('male')
      await wrapper.find('textarea[name="address"]').setValue('123 Main St')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(mockedAxios.post).toHaveBeenCalledWith('/api/patients', {
        first_name: 'John',
        last_name: 'Doe',
        email: 'john.doe@example.com',
        phone: '+1234567890',
        date_of_birth: '1990-01-01',
        gender: 'male',
        address: '123 Main St'
      })
    })

    it('handles successful registration response', async () => {
      const mockResponse = {
        data: {
          id: 1,
          first_name: 'John',
          last_name: 'Doe',
          email: 'john.doe@example.com'
        }
      }
      
      mockedAxios.post.mockResolvedValue(mockResponse)

      // Fill form with valid data
      await wrapper.find('input[name="first_name"]').setValue('John')
      await wrapper.find('input[name="last_name"]').setValue('Doe')
      await wrapper.find('input[name="email"]').setValue('john.doe@example.com')
      await wrapper.find('select[name="gender"]').setValue('male')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()

      expect(wrapper.find('.success-message').text()).toContain('Patient registered successfully')
      expect(wrapper.vm.$router.push).toHaveBeenCalledWith('/patients/1')
    })

    it('handles registration API error', async () => {
      mockedAxios.post.mockRejectedValue({
        response: {
          data: {
            message: 'Email already exists',
            errors: {
              email: ['The email has already been taken.']
            }
          }
        }
      })

      // Fill form with valid data
      await wrapper.find('input[name="first_name"]').setValue('John')
      await wrapper.find('input[name="last_name"]').setValue('Doe')
      await wrapper.find('input[name="email"]').setValue('existing@example.com')
      await wrapper.find('select[name="gender"]').setValue('male')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()

      expect(wrapper.find('.error-message').text()).toContain('Email already exists')
      expect(wrapper.find('.email-error').text()).toContain('The email has already been taken.')
    })

    it('shows loading state during API call', async () => {
      mockedAxios.post.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))

      // Fill form with valid data
      await wrapper.find('input[name="first_name"]').setValue('John')
      await wrapper.find('input[name="last_name"]').setValue('Doe')
      await wrapper.find('select[name="gender"]').setValue('male')

      const form = wrapper.find('form')
      form.trigger('submit.prevent')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
      expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
      expect(wrapper.find('button[type="submit"]').text()).toContain('Registering...')
    })
  })

  describe('Form Reset', () => {
    it('resets form after successful registration', async () => {
      mockedAxios.post.mockResolvedValue({
        data: { id: 1, first_name: 'John', last_name: 'Doe' }
      })

      // Fill form
      await wrapper.find('input[name="first_name"]').setValue('John')
      await wrapper.find('input[name="last_name"]').setValue('Doe')
      await wrapper.find('input[name="email"]').setValue('john@example.com')

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      await wrapper.vm.$nextTick()

      // Check if form is reset
      expect(wrapper.vm.patient.first_name).toBe('')
      expect(wrapper.vm.patient.last_name).toBe('')
      expect(wrapper.vm.patient.email).toBe('')
    })

    it('has reset button that clears form', async () => {
      // Fill form
      await wrapper.find('input[name="first_name"]').setValue('John')
      await wrapper.find('input[name="last_name"]').setValue('Doe')
      await wrapper.find('input[name="email"]').setValue('john@example.com')

      const resetButton = wrapper.find('button[type="reset"]')
      await resetButton.trigger('click')

      expect(wrapper.vm.patient.first_name).toBe('')
      expect(wrapper.vm.patient.last_name).toBe('')
      expect(wrapper.vm.patient.email).toBe('')
    })
  })
})