import { ref, computed } from 'vue'

export const usePaymentHelpers = () => {
  // Helper: return the effective final/price for an item, preferring dependency-specific fields
  const getItemFinalPrice = (it) => {
    if (!it) return 0
    if (it.is_dependency) return Number(it.final_price ?? it.base_price ?? it.dependency_price ?? 0)
    return Number(it.final_price ?? 0)
  }

  // Helper: return the effective paid amount for an item, preferring dependency-specific fields
  const getItemPaidAmount = (it) => {
    if (!it) return 0
    if (it.is_dependency) return Number(it.paid_amount ?? it.payed_amount ?? it.paid ?? 0)
    return Number(it.paid_amount ?? 0)
  }

  const getItemRemainingAmount = (it) => {
    if (!it) return 0
    
    if (it.is_dependency) {
      // For dependencies, calculate remaining from dependency fields
      const finalPrice = Number(it.final_price ?? it.base_price ?? it.dependency_price ?? 0)
      const paid = Number(it.paid_amount ?? it.payed_amount ?? it.paid ?? 0)
      const calculated = Math.max(0, finalPrice - paid)
      
      // Only trust remaining_amount if it matches our calculation or if both final_price and paid are 0
      const storedRemaining = Number(it.remaining_amount ?? 0)
      if (finalPrice === 0 && paid === 0) {
        return storedRemaining
      }
      
      // Return calculated value to fix incorrect remaining_amount from backend
      return calculated
    }
    
    // For regular items, calculate remaining from regular fields
    const finalPrice = Number(it.final_price ?? 0)
    const paid = Number(it.paid_amount ?? 0)
    const calculated = Math.max(0, finalPrice - paid)
    
    // Only trust remaining_amount if it matches our calculation or if both final_price and paid are 0
    const storedRemaining = Number(it.remaining_amount ?? 0)
    if (finalPrice === 0 && paid === 0) {
      return storedRemaining
    }
    
    // Return calculated value to fix incorrect remaining_amount from backend
    return calculated
  }

  const mapPaymentMethod = (m) => {
    if (!m) return 'cash'
    if (m === 'cheque') return 'check'
    if (m === 'card') return 'card'
    if (m === 'cash') return 'cash'
    return m
  }

  return {
    getItemFinalPrice,
    getItemPaidAmount,
    getItemRemainingAmount,
    mapPaymentMethod
  }
}
