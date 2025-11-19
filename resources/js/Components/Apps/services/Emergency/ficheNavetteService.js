import { ficheNavetteService as receptionService } from '../Reception/ficheNavetteService'

// Emergency service that uses the same reception service but with emergency API endpoints
class EmergencyFicheNavetteService {
    constructor() {
        this.baseURL = '/api/emergency'
    }

    // Delegate all methods to the reception service but with emergency endpoints
    async getPrestationsByService(serviceId) {
        return await this.makeRequest('get', `/prestations/by-service/${serviceId}`)
    }

    async getPrestationsPackage(packageId) {
        return await this.makeRequest('get', `/prestations/packages/${packageId}`)
    }

    async getPackagesByService(serviceId) {
        return await this.makeRequest('get', `/packages/by-service/${serviceId}`)
    }

    async getAllPrestations() {
        return await this.makeRequest('get', '/prestations/all')
    }

    async getAllPackages(params = {}) {
        return await this.makeRequest('get', '/prestation-packages', { params })
    }

    async getPrestationsDependencies(prestationIds) {
        const ids = Array.isArray(prestationIds) ? prestationIds : [prestationIds]
        return await this.makeRequest('post', '/prestations/dependencies', { ids })
    }

    async searchPrestations(params = {}) {
        return await this.makeRequest('get', '/prestations/search', { params })
    }

    async getAllSpecializations() {
        return await this.makeRequest('get', '/api/specializations') // This one uses the global API
    }

    async createFicheNavette(data) {
        return await this.makeRequest('post', '/fiche-navette', data)
    }

    async getAll(params = {}) {
        return await this.makeRequest('get', '/fiche-navette', { params })
    }

    async getPrestationsForFicheByAuthenticatedUser(params = {}) {
        return await this.makeRequest('get', '/prestations/fichenavette', { params })
    }

    async getById(id) {
        return await this.makeRequest('get', `/fiche-navette/${id}`)
    }

    async update(id, data) {
        return await this.makeRequest('put', `/fiche-navette/${id}`, data)
    }

    async delete(id) {
        return await this.makeRequest('delete', `/fiche-navette/${id}`)
    }

    async getPrestationsBySpecialization(specializationId) {
        return await this.makeRequest('get', `/prestations/by-specialization/${specializationId}`)
    }

    async getPackagesBySpecialization(specializationId) {
        return await this.makeRequest('get', `/packages/by-specialization/${specializationId}`)
    }

    async getDoctorsBySpecialization(specializationId) {
        return await this.makeRequest('get', `/api/doctors/specializations/${specializationId}`) // Global API
    }

    async getAllPrestationsWithPackages(params = {}) {
        return await this.makeRequest('get', '/prestations/with-packages', { params })
    }

    async getAllDoctors() {
        return await this.makeRequest('get', '/api/doctors') // Global API
    }

    async getFicheNavetteItems(ficheNavetteId) {
        return await this.makeRequest('get', `/fiche-navette/${ficheNavetteId}/items`)
    }

    async getFicheNavetteById(ficheNavetteId) {
        return await this.makeRequest('get', `/fiche-navette/${ficheNavetteId}`)
    }

    async addItemsToFiche(ficheNavetteId, data) {
        return await this.makeRequest('post', `/fiche-navette/${ficheNavetteId}/items`, data)
    }

    async createSimpleFiche(data) {
        return await this.makeRequest('post', '/fiche-navette', {
            patient_id: data.patient_id,
            notes: data.notes
        })
    }

    async updateFicheNavetteItem(ficheNavetteId, itemId, data) {
        return await this.makeRequest('put', `/fiche-navette/${ficheNavetteId}/items/${itemId}`, data)
    }

    async removeFicheNavetteItem(ficheNavetteId, itemId) {
        return await this.makeRequest('delete', `/fiche-navette/${ficheNavetteId}/items/${itemId}`)
    }

    async getCompanies() {
        return await this.makeRequest('get', '/api/organismes') // Global API
    }

    async getConventions(params = {}) {
        return await this.makeRequest('get', '/api/conventions', { params }) // Global API
    }

    async getConventionsByOrganismes(organismeIds) {
        return await this.makeRequest('get', '/api/conventions', {
            params: { 
                organisme_ids: organismeIds.join(','),
                per_page: -1
            }
        })
    }

    async getPrestationsByConvention(conventionId, priseEnChargeDate = null) {
        const params = {}
        if (priseEnChargeDate) {
            params.prise_en_charge_date = priseEnChargeDate
        }
        return await this.makeRequest('get', `/prestations/by-convention/${conventionId}`, { params })
    }

    async getPrestationsWithConventionPricing(conventionIds = [], priseEnChargeDate = null) {
        const params = { convention_ids: conventionIds.join(',') }
        if (priseEnChargeDate) {
            params.prise_en_charge_date = priseEnChargeDate
        }
        return await this.makeRequest('get', '/prestations/with-convention-pricing', { params })
    }

    async createConventionPrescription(ficheNavetteId, data) {
        return await this.makeRequest('post', `/api/fiche-navette/${ficheNavetteId}/convention-prescription`, data, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    }

    async removeDependency(dependencyId) {
        return await this.makeRequest('delete', `/dependencies/${dependencyId}`)
    }

   async getPatientConventions(patientId, ficheNavetteId) {
       try {
           // Use the shared patients conventions endpoint (no reception prefix)
           const response = await axios.get(`/api/reception/patients/${patientId}/conventions`, {
               params: {
                   fiche_navette_id: ficheNavetteId
               }
           })
   
           return {
               success: true,
               data: response.data.data || response.data
           }
       } catch (error) {
           console.error('Error fetching patient conventions:', error)
           return {
               success: false,
               message: error.response?.data?.message || 'Failed to fetch patient conventions',
               error
           }
       }
   }

    async getGroupedItems(ficheNavetteId) {
        return await this.makeRequest('get', `/fiche-navette/${ficheNavetteId}/grouped-items`)
    }

    async uploadConventionFiles(files) {
        const formData = new FormData()
        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file)
        })
        return await this.makeRequest('post', '/api/fiche-navette-items/upload-convention-files', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    }

    async getFamilyAuthorization(priseEnChargeDate) {
        return await this.makeRequest('get', '/api/conventions/family-authorization', {
            params: { prise_en_charge_date: priseEnChargeDate }
        })
    }

    async downloadConventionFile(fileId) {
        return await this.makeRequest('get', `/api/fiche-navette-items/download-convention-file/${fileId}`, null, {
            responseType: 'blob'
        })
    }

    async getCustomPackages() {
        return await this.makeRequest('get', '/api/fiche-navette-custom-packages') // Global API
    }

    async printTicket(ficheNavetteId) {
        try {
            const axios = (await import('axios')).default
            const response = await axios.post(
                `${this.baseURL}/fiche-navette/${ficheNavetteId}/print-ticket`,
                {},
                {
                    responseType: 'blob' // Important for PDF download
                }
            )

            // Create blob link to download
            const url = window.URL.createObjectURL(new Blob([response.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `fiche-navette-ticket-${ficheNavetteId}.pdf`)
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)

            return {
                success: true,
                message: 'Ticket printed successfully'
            }
        } catch (error) {
            console.error('Error printing ticket:', error)
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to print ticket',
                error
            }
        }
    }

    // Helper method to make requests with proper error handling
    async makeRequest(method, url, data = null, config = {}) {
        try {
            const axios = (await import('axios')).default
            
            // Determine the full URL
            const fullUrl = url.startsWith('/api/') ? url : `${this.baseURL}${url}`
            
            let response
            if (method === 'get') {
                response = await axios.get(fullUrl, config)
            } else if (method === 'post') {
                response = await axios.post(fullUrl, data, config)
            } else if (method === 'put') {
                response = await axios.put(fullUrl, data, config)
            } else if (method === 'delete') {
                response = await axios.delete(fullUrl, config)
            }

            return {
                success: true,
                data: response.data.data || response.data,
                message: response.data.message,
                pagination: response.data.meta || response.data.pagination,
                total: response.data.total
            }
        } catch (error) {
            console.error(`Error in ${method.toUpperCase()} ${url}:`, error)
            return {
                success: false,
                message: error.response?.data?.message || `Failed to ${method} ${url}`,
                errors: error.response?.data?.errors || {},
                error
            }
        }
    }
}

// Export instance
export const ficheNavetteService = new EmergencyFicheNavetteService()
export default ficheNavetteService