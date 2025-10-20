import { mount, shallowMount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import axios from 'axios'
import GlobalPayment from '@/Pages/Apps/caisse/GlobalPayment.vue'
import CaissePatientPayment from '@/Pages/Apps/caisse/CaissePatientPayment.vue'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

// Mock banks data
const mockBanks = [
  { id: 1, name: 'Test Bank 1', code: 'TB001', is_active: true },
  { id: 2, name: 'Test Bank 2', code: 'TB002', is_active: true },
  { id: 3, name: 'Inactive Bank', code: 'IB001', is_active: false }
]

// Mock patient data
const mockPatient = {
  id: 1,
  first_name: 'John',
  last_name: 'Doe',
  phone: '1234567890'
}

// Mock fiche navette data
const mockFicheNavette = {
  id: 1,
  patient_id: 1,
  reference: 'FN-123456',
  total_amount: 100.00
}

describe('GlobalPayment Component', () => {
  let wrapper

  beforeEach(() => {
    // Reset all mocks
    vi.clearAllMocks()
    
    // Mock successful axios responses
    mockedAxios.get.mockResolvedValue({
      data: {
        success: true,
        data: mockBanks
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  it('renders bank selection dropdown when payment method is bank_transfer', async () => {
    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: []
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    // Set payment method to bank_transfer
    await wrapper.setData({ paymentMethod: 'bank_transfer' })
    
    // Check if bank selection is visible
    const bankSelect = wrapper.find('[data-testid="bank-select"]')
    expect(bankSelect.exists()).toBe(true)
  })

  it('hides bank selection when payment method is not bank_transfer', async () => {
    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: []
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    // Set payment method to cash
    await wrapper.setData({ paymentMethod: 'cash' })
    
    // Check if bank selection is hidden
    const bankSelect = wrapper.find('[data-testid="bank-select"]')
    expect(bankSelect.exists()).toBe(false)
  })

  it('loads banks on component mount', async () => {
    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: []
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    await wrapper.vm.$nextTick()

    // Verify axios was called to fetch banks
    expect(mockedAxios.get).toHaveBeenCalledWith('/api/banks')
    
    // Verify banks are loaded
    expect(wrapper.vm.banks).toEqual(mockBanks)
  })

  it('filters only active banks in the dropdown', async () => {
    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: []
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    await wrapper.vm.$nextTick()
    await wrapper.setData({ paymentMethod: 'bank_transfer' })

    const bankOptions = wrapper.findAll('[data-testid="bank-option"]')
    
    // Should only show active banks (2 out of 3)
    expect(bankOptions).toHaveLength(2)
    
    // Verify inactive bank is not shown
    const inactiveBankOption = bankOptions.find(option => 
      option.text().includes('Inactive Bank')
    )
    expect(inactiveBankOption).toBeUndefined()
  })

  it('updates selectedBank when bank is selected', async () => {
    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: []
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    await wrapper.vm.$nextTick()
    await wrapper.setData({ paymentMethod: 'bank_transfer' })

    const bankSelect = wrapper.find('[data-testid="bank-select"]')
    await bankSelect.setValue('1')

    expect(wrapper.vm.selectedBank).toBe('1')
  })

  it('includes bank transaction data in payment payload', async () => {
    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: [{ id: 1, amount: 100.00 }]
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    await wrapper.vm.$nextTick()
    await wrapper.setData({ 
      paymentMethod: 'bank_transfer',
      selectedBank: '1',
      totalAmount: 100.00
    })

    // Mock successful payment response
    mockedAxios.post.mockResolvedValue({
      data: { success: true }
    })

    // Trigger payment
    await wrapper.vm.processPayment()

    // Verify payment payload includes bank transaction data
    expect(mockedAxios.post).toHaveBeenCalledWith(
      '/api/financial-transactions-bulk-payment',
      expect.objectContaining({
        payment_method: 'bank_transfer',
        is_bank_transaction: true,
        bank_id: 1
      })
    )
  })

  it('handles bank loading error gracefully', async () => {
    // Mock axios error
    mockedAxios.get.mockRejectedValue(new Error('Network error'))

    wrapper = mount(GlobalPayment, {
      props: {
        patient: mockPatient,
        ficheNavette: mockFicheNavette,
        items: []
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    await wrapper.vm.$nextTick()

    // Verify banks array is empty on error
    expect(wrapper.vm.banks).toEqual([])
    
    // Verify error handling doesn't break the component
    expect(wrapper.exists()).toBe(true)
  })
})

describe('CaissePatientPayment Component', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
    
    mockedAxios.get.mockResolvedValue({
      data: {
        success: true,
        data: mockBanks
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  it('passes bank selection props to GlobalPayment component', async () => {
    wrapper = mount(CaissePatientPayment, {
      props: {
        patient: mockPatient
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    await wrapper.vm.$nextTick()

    const globalPaymentComponent = wrapper.findComponent({ name: 'GlobalPayment' })
    
    expect(globalPaymentComponent.exists()).toBe(true)
    expect(globalPaymentComponent.props('patient')).toEqual(mockPatient)
  })

  it('handles bulk payment with bank transaction data', async () => {
    wrapper = mount(CaissePatientPayment, {
      props: {
        patient: mockPatient
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    const bulkPayload = {
      fiche_navette_id: 1,
      caisse_session_id: 1,
      cashier_id: 1,
      patient_id: 1,
      payment_method: 'bank_transfer',
      transaction_type: 'payment',
      total_amount: 100.00,
      is_bank_transaction: true,
      bank_id: 1,
      items: [{ fiche_navette_item_id: 1, amount: 100.00 }]
    }

    // Mock successful response
    mockedAxios.post.mockResolvedValue({
      data: { success: true }
    })

    // Trigger bulk payment
    await wrapper.vm.processBulkPayment(bulkPayload)

    // Verify API call was made with correct data
    expect(mockedAxios.post).toHaveBeenCalledWith(
      '/api/financial-transactions-bulk-payment',
      bulkPayload
    )
  })

  it('validates bank selection is required for bank transfers', async () => {
    wrapper = mount(CaissePatientPayment, {
      props: {
        patient: mockPatient
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    const invalidPayload = {
      payment_method: 'bank_transfer',
      is_bank_transaction: true,
      // Missing bank_id
      total_amount: 100.00
    }

    // Mock validation error response
    mockedAxios.post.mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            bank_id: ['Bank selection is required for bank transactions.']
          }
        }
      }
    })

    try {
      await wrapper.vm.processBulkPayment(invalidPayload)
    } catch (error) {
      expect(error.response.status).toBe(422)
      expect(error.response.data.errors.bank_id).toContain(
        'Bank selection is required for bank transactions.'
      )
    }
  })

  it('resets bank selection when payment method changes from bank_transfer', async () => {
    wrapper = mount(CaissePatientPayment, {
      props: {
        patient: mockPatient
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    // Set initial bank transfer data
    await wrapper.setData({
      paymentMethod: 'bank_transfer',
      selectedBank: '1'
    })

    expect(wrapper.vm.selectedBank).toBe('1')

    // Change payment method to cash
    await wrapper.setData({ paymentMethod: 'cash' })

    // Bank selection should be reset
    expect(wrapper.vm.selectedBank).toBe(null)
  })

  it('displays bank selection error messages', async () => {
    wrapper = mount(CaissePatientPayment, {
      props: {
        patient: mockPatient
      },
      global: {
        mocks: {
          $axios: mockedAxios
        }
      }
    })

    // Set validation errors
    await wrapper.setData({
      errors: {
        bank_id: ['Bank selection is required for bank transactions.']
      }
    })

    const errorMessage = wrapper.find('[data-testid="bank-error"]')
    expect(errorMessage.exists()).toBe(true)
    expect(errorMessage.text()).toContain('Bank selection is required for bank transactions.')
  })
})