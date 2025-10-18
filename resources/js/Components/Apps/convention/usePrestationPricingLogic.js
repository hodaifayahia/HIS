// src/composables/usePrestationPricingLogic.js
import { ref, computed, watch } from 'vue';

export function usePrestationPricingLogic(contractDataRef, initialPricing = {}) {
  const prix = ref(initialPricing.prix || 0);
  const company_price = ref(initialPricing.company_price || 0);
  const patient_price = ref(initialPricing.patient_price || 0);
  const patientPercentage = ref(initialPricing.patientPercentage || 0);

  const priceValidation = ref({
    companyExceedsMax: false,
    patientDiffers: false,
    calculatedPatientPart: 0,
    calculatedCompanyPart: 0,
  });

  const validatePricing = () => {
    const maxPrice = parseFloat(contractDataRef.value?.max_price || 0);
    const discountPercentage = parseFloat(contractDataRef.value?.discount_percentage || 0);

    const currentGlobalPrice = prix.value;
    const currentCompanyPart = company_price.value;
    const currentPatientPart = patient_price.value;

    priceValidation.value = {
      companyExceedsMax: false,
      patientDiffers: false,
      calculatedPatientPart: 0,
      calculatedCompanyPart: 0,
    };

    if (!contractDataRef.value || currentGlobalPrice === undefined || currentGlobalPrice === null) {
      return;
    }

    // Calculate ideal company and patient shares based on discount
    const idealCompanyShare = currentGlobalPrice * (discountPercentage / 100);
    let idealPatientShare = currentGlobalPrice - idealCompanyShare;

    let cappedCalculatedCompanyPart = idealCompanyShare;
    let adjustedCalculatedPatientPart = idealPatientShare;

    // Apply max_price capping to the ideal company share
    if (maxPrice > 0 && idealCompanyShare > maxPrice) {
      const excess = idealCompanyShare - maxPrice;
      cappedCalculatedCompanyPart = maxPrice;
      adjustedCalculatedPatientPart = idealPatientShare + excess;
    }

    // Check if user-entered company part exceeds max
    if (maxPrice > 0 && currentCompanyPart > maxPrice) {
      priceValidation.value.companyExceedsMax = true;
    }

    // Check if user-entered patient part implies company part exceeds max
    const impliedCompanyPartFromPatientInput = currentGlobalPrice - currentPatientPart;
    if (maxPrice > 0 && impliedCompanyPartFromPatientInput > maxPrice) {
      priceValidation.value.companyExceedsMax = true;
    }

    // Check if patient part differs from calculated amount
    priceValidation.value.patientDiffers =
      Math.abs(currentPatientPart - adjustedCalculatedPatientPart) > 0.01; // Using a small tolerance

    priceValidation.value.calculatedPatientPart = adjustedCalculatedPatientPart;
    priceValidation.value.calculatedCompanyPart = cappedCalculatedCompanyPart;
  };

  const calculatePartsFromGlobal = () => {
    if (!contractDataRef.value || prix.value === undefined || prix.value === null) {
      company_price.value = 0;
      patient_price.value = 0;
      patientPercentage.value = 0;
      validatePricing();
      return;
    }

    const globalPrice = parseFloat(prix.value || 0);
    const discountPercentage = parseFloat(contractDataRef.value.discount_percentage || 0);
    const maxPrice = parseFloat(contractDataRef.value.max_price || 0);

    if (globalPrice > 0) {
      const companySharePercentage = discountPercentage / 100;
      let calculatedCompanyPart = globalPrice * companySharePercentage;
      let calculatedPatientPart = globalPrice - calculatedCompanyPart;

      if (maxPrice > 0 && calculatedCompanyPart > maxPrice) {
        const excess = calculatedCompanyPart - maxPrice;
        calculatedCompanyPart = maxPrice;
        calculatedPatientPart = calculatedPatientPart + excess;
      }

      company_price.value = parseFloat(calculatedCompanyPart.toFixed(2));
      patient_price.value = parseFloat(calculatedPatientPart.toFixed(2));
      patientPercentage.value = parseFloat(((calculatedPatientPart / globalPrice) * 100).toFixed(2));
    } else {
      company_price.value = 0;
      patient_price.value = 0;
      patientPercentage.value = 0;
    }
    validatePricing();
  };

  const calculatePatientFromCompany = () => {
    const globalPrice = parseFloat(prix.value || 0);
    const currentCompanyPart = parseFloat(company_price.value || 0);

    if (globalPrice > 0) {
      patient_price.value = parseFloat((globalPrice - currentCompanyPart).toFixed(2));
      patientPercentage.value = parseFloat(((patient_price.value / globalPrice) * 100).toFixed(2));
    } else {
      patient_price.value = 0;
      patientPercentage.value = 0;
    }
    validatePricing();
  };

  const calculateCompanyFromPatient = () => {
    const globalPrice = parseFloat(prix.value || 0);
    const currentPatientPart = parseFloat(patient_price.value || 0);

    if (globalPrice > 0) {
      company_price.value = parseFloat((globalPrice - currentPatientPart).toFixed(2));
      patientPercentage.value = parseFloat(((currentPatientPart / globalPrice) * 100).toFixed(2));
    } else {
      company_price.value = 0;
      patientPercentage.value = 0;
    }
    validatePricing();
  };

  // Watchers for immediate validation and recalculation
  watch(prix, calculatePartsFromGlobal, { immediate: true });
  watch(company_price, validatePricing, { immediate: true });
  watch(patient_price, validatePricing, { immediate: true });

  // Re-run calculations if contract data changes
  watch(contractDataRef, () => {
    calculatePartsFromGlobal();
  }, { deep: true });

  return {
    prix,
    company_price,
    patient_price,
    patientPercentage,
    priceValidation,
    validatePricing,
    calculatePartsFromGlobal,
    calculatePatientFromCompany,
    calculateCompanyFromPatient,
  };
}