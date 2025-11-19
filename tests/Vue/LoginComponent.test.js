import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import LoginComponent from '@/components/Auth/LoginComponent.vue'
import axios from 'axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

describe('LoginComponent', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(LoginComponent, {
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
    it('renders the login component', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('has email input field', () => {
      const emailInput = wrapper.find('input[type="email"]')
      expect(emailInput.exists()).toBe(true)
    })

    it('has password input field', () => {
      const passwordInput = wrapper.find('input[type="password"]')
      expect(passwordInput.exists()).toBe(true)
    })

    it('has remember me checkbox', () => {
      const rememberCheckbox = wrapper.find('input[type="checkbox"]')
      expect(rememberCheckbox.exists()).toBe(true)
    })

    it('has login button', () => {
      const loginButton = wrapper.find('button[type="submit"]')
      expect(loginButton.exists()).toBe(true)
      expect(loginButton.text()).toContain('Login')
    })
  })

  describe('DOM Interactions', () => {
    it('updates email data when input changes', async () => {
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      
      expect(wrapper.vm.email).toBe('test@example.com')
    })

    it('updates password data when input changes', async () => {
      const passwordInput = wrapper.find('input[type="password"]')
      await passwordInput.setValue('password123')
      
      expect(wrapper.vm.password).toBe('password123')
    })

    it('toggles password visibility', async () => {
      const toggleButton = wrapper.find('.password-toggle')
      const passwordInput = wrapper.find('input[name="password"]')
      
      // Initially password should be hidden
      expect(passwordInput.attributes('type')).toBe('password')
      
      // Click toggle button
      await toggleButton.trigger('click')
      expect(passwordInput.attributes('type')).toBe('text')
      
      // Click again to hide
      await toggleButton.trigger('click')
      expect(passwordInput.attributes('type')).toBe('password')
    })

    it('toggles remember me checkbox', async () => {
      const rememberCheckbox = wrapper.find('input[type="checkbox"]')
      
      // Initially unchecked
      expect(wrapper.vm.rememberMe).toBe(false)
      
      // Check the checkbox
      await rememberCheckbox.setChecked(true)
      expect(wrapper.vm.rememberMe).toBe(true)
      
      // Uncheck the checkbox
      await rememberCheckbox.setChecked(false)
      expect(wrapper.vm.rememberMe).toBe(false)
    })
  })

  describe('Form Validation', () => {
    it('shows validation error for empty email', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.email-error').text()).toContain('Email is required')
    })

    it('shows validation error for invalid email format', async () => {
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('invalid-email')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.email-error').text()).toContain('Please enter a valid email')
    })

    it('shows validation error for empty password', async () => {
      const emailInput = wrapper.find('input[type="email"]')
      await emailInput.setValue('test@example.com')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      
      expect(wrapper.find('.password-error').text()).toContain('Password is required')
    })
  })

  describe('API Integration', () => {
    it('calls login API with correct credentials', async () => {
      mockedAxios.post.mockResolvedValue({
        data: {
          token: 'fake-jwt-token',
          user: { id: 1, name: 'Test User', email: 'test@example.com' }
        }
      })

      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      const form = wrapper.find('form')

      await emailInput.setValue('test@example.com')
      await passwordInput.setValue('password123')
      await form.trigger('submit.prevent')

      expect(mockedAxios.post).toHaveBeenCalledWith('/api/auth/login', {
        email: 'test@example.com',
        password: 'password123',
        remember: false
      })
    })

    it('handles successful login response', async () => {
      const mockResponse = {
        data: {
          token: 'fake-jwt-token',
          user: { id: 1, name: 'Test User', email: 'test@example.com' }
        }
      }
      
      mockedAxios.post.mockResolvedValue(mockResponse)

      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      const form = wrapper.find('form')

      await emailInput.setValue('test@example.com')
      await passwordInput.setValue('password123')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()

      expect(wrapper.vm.$router.push).toHaveBeenCalledWith('/dashboard')
    })

    it('handles login API error', async () => {
      mockedAxios.post.mockRejectedValue({
        response: {
          data: {
            message: 'Invalid credentials'
          }
        }
      })

      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      const form = wrapper.find('form')

      await emailInput.setValue('test@example.com')
      await passwordInput.setValue('wrongpassword')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()

      expect(wrapper.find('.error-message').text()).toContain('Invalid credentials')
    })

    it('shows loading state during API call', async () => {
      mockedAxios.post.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))

      const form = wrapper.find('form')
      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')

      await emailInput.setValue('test@example.com')
      await passwordInput.setValue('password123')
      
      form.trigger('submit.prevent')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
      expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    })
  })

  describe('Remember Me Functionality', () => {
    it('includes remember flag in API call when checked', async () => {
      mockedAxios.post.mockResolvedValue({
        data: { token: 'fake-token', user: {} }
      })

      const emailInput = wrapper.find('input[type="email"]')
      const passwordInput = wrapper.find('input[type="password"]')
      const rememberCheckbox = wrapper.find('input[type="checkbox"]')
      const form = wrapper.find('form')

      await emailInput.setValue('test@example.com')
      await passwordInput.setValue('password123')
      await rememberCheckbox.setChecked(true)
      await form.trigger('submit.prevent')

      expect(mockedAxios.post).toHaveBeenCalledWith('/api/auth/login', {
        email: 'test@example.com',
        password: 'password123',
        remember: true
      })
    })
  })
})