import axios from 'axios'

export const AdmissionService = {
  // Admissions
  getAdmissions(filters = {}) {
    return axios.get('/api/admissions', { params: filters })
  },

  getAdmission(id) {
    return axios.get(`/api/admissions/${id}`)
  },

  createAdmission(data) {
    return axios.post('/api/admissions', data)
  },

  updateAdmission(id, data) {
    return axios.patch(`/api/admissions/${id}`, data)
  },

  deleteAdmission(id) {
    return axios.delete(`/api/admissions/${id}`)
  },

  dischargeAdmission(id) {
    return axios.post(`/api/admissions/${id}/discharge`)
  },

  getActiveAdmissions(filters = {}) {
    return axios.get('/api/admissions/active', { params: filters })
  },

  getStatistics() {
    return axios.get('/api/admissions/statistics')
  },

  // Procedures
  getProcedures(admissionId, filters = {}) {
    return axios.get(`/api/admissions/${admissionId}/procedures`, { params: filters })
  },

  getProcedure(admissionId, procedureId) {
    return axios.get(`/api/admissions/${admissionId}/procedures/${procedureId}`)
  },

  createProcedure(admissionId, data) {
    return axios.post(`/api/admissions/${admissionId}/procedures`, data)
  },

  updateProcedure(admissionId, procedureId, data) {
    return axios.patch(`/api/admissions/${admissionId}/procedures/${procedureId}`, data)
  },

  completeProcedure(admissionId, procedureId) {
    return axios.post(`/api/admissions/${admissionId}/procedures/${procedureId}/complete`)
  },

  cancelProcedure(admissionId, procedureId) {
    return axios.post(`/api/admissions/${admissionId}/procedures/${procedureId}/cancel`)
  },

  // Documents
  getDocuments(admissionId, filters = {}) {
    return axios.get(`/api/admissions/${admissionId}/documents`, { params: filters })
  },

  createDocument(admissionId, data) {
    return axios.post(`/api/admissions/${admissionId}/documents`, data)
  },

  updateDocument(admissionId, documentId, data) {
    return axios.patch(`/api/admissions/${admissionId}/documents/${documentId}`, data)
  },

  // Billing Records
  getBillingRecords(admissionId, filters = {}) {
    return axios.get(`/api/admissions/${admissionId}/billing-records`, { params: filters })
  },

  // Discharge Tickets
  getDischargeTicket(admissionId) {
    return axios.get(`/api/admissions/${admissionId}/discharge-ticket`)
  },

  // Fiche Navette Integration
  getOrCreateFicheNavette(admissionId) {
    return axios.post(`/api/admissions/${admissionId}/fiche-navette`)
  },

  getFicheNavette(admissionId) {
    return axios.get(`/api/admissions/${admissionId}/fiche-navette`)
  },
}
