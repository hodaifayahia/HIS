export const useTransactionHelpers = () => {
  const getTransactionTypeText = (type) => {
    const types = {
      'payment': 'Paiement',
      'refund': 'Remboursement',
      'donation': 'Don',
      'balance_credit': 'Crédit Compte',
      'adjustment': 'Ajustement'
    }
    return types[type] || type
  }

  const getTransactionTypeClass = (type) => {
    const classes = {
      'payment': 'tw-text-green-600',
      'refund': 'tw-text-red-600',
      'donation': 'tw-text-purple-600',
      'balance_credit': 'tw-text-blue-600',
      'adjustment': 'tw-text-yellow-600'
    }
    return classes[type] || 'tw-text-gray-600'
  }

  return {
    getTransactionTypeText,
    getTransactionTypeClass
  }
}
