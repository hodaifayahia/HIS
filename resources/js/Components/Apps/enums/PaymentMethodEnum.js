export const PaymentMethodEnum = {
  PREPAYMENT: 'prepayment',
  POSTPAYMENT: 'postpayment',
  VERSEMENT: 'versement'
}

export const PaymentMethodLabels = {
  [PaymentMethodEnum.PREPAYMENT]: 'Pre-payment',
  [PaymentMethodEnum.POSTPAYMENT]: 'Post-payment',
  [PaymentMethodEnum.VERSEMENT]: 'Versement'
}

export const PaymentMethodIcons = {
  [PaymentMethodEnum.PREPAYMENT]: 'fas fa-wallet',
  [PaymentMethodEnum.POSTPAYMENT]: 'fas fa-file-invoice-dollar',
  [PaymentMethodEnum.VERSEMENT]: 'fas fa-university'
}

// Helper functions
export const getPaymentMethodLabel = (key) => {
  return PaymentMethodLabels[key] || key
}

export const getPaymentMethodIcon = (key) => {
  return PaymentMethodIcons[key] || 'fas fa-credit-card'
}

export const getPaymentMethodsForDropdown = () => {
  return Object.values(PaymentMethodEnum).map(value => ({
    value,
    label: getPaymentMethodLabel(value),
    icon: getPaymentMethodIcon(value)
  }))
}

export const isValidPaymentMethod = (key) => {
  return Object.values(PaymentMethodEnum).includes(key)
}
