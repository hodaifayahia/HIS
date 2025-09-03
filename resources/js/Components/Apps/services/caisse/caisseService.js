import axios from 'axios';

export const caisseService = {
  /**
   * Fetch fiche-navette patient summary (today + unpaid)
   * filters: { service_id, patient_name, status, per_page, page }
   */
  getFichePatientSummary(filters = {}) {
    return axios
      .get('/caisse/fiche-navette/patient-summary', { params: filters })
      .then((res) => {
        // if API returns paginated resource, res.data may contain data + meta
        return { success: true, data: res.data?.data ?? res.data, meta: res.data?.meta ?? null };
      })
      .catch((err) => {
        console.error('caisseService.getFichePatientSummary error', err);
        return {
          success: false,
          message: err.response?.data?.message ?? err.message,
          errors: err.response?.data?.errors ?? null
        };
      });
  },
  
};

export default caisseService;