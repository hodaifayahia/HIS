export const useCurrencyFormatter = () => {
  const formatCurrency = (amount) => {
    const value = Number(amount ?? 0)
    return new Intl.NumberFormat('fr-DZ', { 
      style: 'currency', 
      currency: 'DZD', 
      minimumFractionDigits: 2 
    }).format(value)
  }

  return {
    formatCurrency
  }
}
