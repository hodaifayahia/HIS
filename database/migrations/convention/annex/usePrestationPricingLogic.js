// src/usePrestationPricingLogic.js
import { ref, computed, watch } from "vue";

export function usePrestationPricingLogic(contractDataRef, initialPrestationRef) {
  const prix = ref(initialPrestationRef?.prix || 0);
  const company_price = ref(initialPrestationRef?.company_price || 0);
  const patient_price = ref(initialPrestationRef?.patient_price || 0);
  const patientPercentage = ref(initialPrestationRef?.patientPercentage || 0);

  const priceValidation = ref({
    companyExceedsMax: false,
    patientDiffers: false,
    calculatedPatientPart: 0,
    calculatedCompanyPart: 0,
  });

  const validatePricing = () => {
    const contract = contractDataRef.value;
    const maxPrice = parseFloat(contract?.max_price || 0);
    const discountPercentage = parseFloat(contract?.discount_percentage || 0);

    const globalPrice = parseFloat(prix.value || 0);
    const currentCompanyPart = parseFloat(company_price.value || 0);
    const currentPatientPart = parseFloat(patient_price.value || 0);

    // Reset validation state
    priceValidation.value = {
      companyExceedsMax: false,
      patientDiffers: false,
      calculatedPatientPart: 0,
      calculatedCompanyPart: 0,
    };

    if (!contract) {
      return;
    }

    // Calculate ideal company and patient shares based on discount
    const idealCompanyShare = globalPrice * (discountPercentage / 100);
    let idealPatientShare = globalPrice - idealCompanyShare;

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
    // Only if globalPrice is not zero to avoid division by zero or infinite values
    if (globalPrice > 0) {
      const impliedCompanyPartFromPatientInput = globalPrice - currentPatientPart;
      if (maxPrice > 0 && impliedCompanyPartFromPatientInput > maxPrice) {
        priceValidation.value.companyExceedsMax = true;
      }
    }


    // Check if patient part differs from calculated amount
    // Use a small tolerance for floating point comparisons
    priceValidation.value.patientDiffers =
      Math.abs(currentPatientPart - adjustedCalculatedPatientPart) > 0.01;

    priceValidation.value.calculatedPatientPart = adjustedCalculatedPatientPart;
    priceValidation.value.calculatedCompanyPart = cappedCalculatedCompanyPart;
  };

  const calculatePartsFromGlobal = () => {
    const contract = contractDataRef.value;
    if (!contract) {
      company_price.value = 0;
      patient_price.value = 0;
      patientPercentage.value = 0;
      validatePricing();
      return;
    }

    const globalPrice = parseFloat(prix.value || 0);
    const discountPercentage = parseFloat(contract.discount_percentage || 0);
    const maxPrice = parseFloat(contract.max_price || 0);

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
      patientPercentage.value = parseFloat(((patient_price.value / globalPrice) * 100).toFixed(2));
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

  // Initial validation and calculations when the composable is used
  // and when contractDataRef or initialPrestationRef change
  watch(
    [contractDataRef, initialPrestationRef],
    () => {
      // Re-initialize values from initialPrestationRef if it changes
      if (initialPrestationRef && Object.keys(initialPrestationRef).length > 0) {
        prix.value = parseFloat(initialPrestationRef.prix || 0);
        company_price.value = parseFloat(initialPrestationRef.company_price || 0);
        patient_price.value = parseFloat(initialPrestationRef.patient_price || 0);
        if (prix.value > 0) {
          patientPercentage.value = parseFloat(((patient_price.value / prix.value) * 100).toFixed(2));
        } else {
          patientPercentage.value = 0;
        }
      }
      validatePricing();
    },
    { immediate: true, deep: true }
  );

  // Watch for direct changes to prix, company_price, patient_price to re-validate
  watch([prix, company_price, patient_price], () => {
    validatePricing();
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