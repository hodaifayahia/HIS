import axios from 'axios';

export const ficheNavetteService = {
    /**
     * Get all services with their doctors
     */
    

    /**
     * Get prestations by service ID
     */
    async getPrestationsByService(serviceId) {
        try {
            const response = await axios.get(`/api/reception/prestations/by-service/${serviceId}`);
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            console.error('Error fetching prestations by service:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch prestations',
                error
            };
        }
    },
   async getPrestationsPackage(packageId) {
    try {
        console.log('Service: Fetching prestations for package ID:', packageId);
        const response = await axios.get(`/api/reception/prestations/packages/${packageId}`);
        return {
            success: true,
            data: response.data.data || response.data
        };
    } catch (error) {
        console.error('Error fetching prestations by package:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to fetch prestations',
            error
        };
    }
},

    /**
     * Get packages by service ID
     */
    async getPackagesByService(serviceId) {
        try {
            const response = await axios.get(`/api/reception/packages/by-service/${serviceId}`);
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            console.error('Error fetching packages by service:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch packages',
                error
            };
        }
    },

   /**
 * Get all prestations using the main prestation endpoint (not reception endpoint)
 */
async getAllPrestations() {
    try {
        // Use the new dedicated endpoint
        const response = await axios.get('/api/reception/prestations/all');
        return {
            success: true,
            data: response.data.data || response.data
        };
    } catch (error) {
        console.error('Error fetching all prestations:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to fetch prestations',
            error
        };
    }
},

    /**
     * Get all packages
     */
    async getAllPackages(params = {}) {
        try {
            const response = await axios.get('/api/prestation-packages', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching all packages:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch packages',
                error
            };
        }
    },

    /**
     * Get prestation dependencies
     */
  /**
 * Get prestation dependencies by IDs
 */
async getPrestationsDependencies(prestationIds) {
    try {
        console.log('Service: Fetching dependencies for IDs:', prestationIds);
        
        // Ensure prestationIds is an array
        const ids = Array.isArray(prestationIds) ? prestationIds : [prestationIds];
        
        const response = await axios.post('/api/reception/prestations/dependencies', { 
            ids: ids 
        });
        
        return {
            success: true,
            data: response.data.data
        };
    } catch (error) {
        console.error('Error fetching prestation dependencies:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to fetch dependencies',
            error
        };
    }
},

    /**
     * Search prestations with filters
     */
    async searchPrestations(params = {}) {
        try {
            const response = await axios.get('/api/reception/prestations/search', { params });
            return {
                success: true,
                data: response.data.data,
                pagination: response.data.pagination
            };
        } catch (error) {
            console.error('Error searching prestations:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to search prestations',
                error
            };
        }
    },

    /**
     * Get all specializations
     */
    async getAllSpecializations() {
        try {
            const response = await axios.get('/api/specializations');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching specializations:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch specializations',
                error
            };
        }
    },

    /**
     * Create fiche navette with items
     */
    async createFicheNavette(data) {
        try {
            const response = await axios.post('/api/reception/fiche-navette', data);
            return {
                success: true,
                data: response.data.data,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error creating fiche navette:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create fiche navette',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Get all fiche navettes
     */
    async getAll(params = {}) {
        try {
            const response = await axios.get('/api/reception/fiche-navette', { params });
            return {
                success: true,
                data: response.data.data,
                pagination: response.data.meta || response.data.pagination
            };
        } catch (error) {
            console.error('Error fetching fiche navettes:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch fiche navettes',
                error
            };
        }
    },

    /**
     * Get fiche navette by ID
     */
    async getById(id) {
        try {
            const response = await axios.get(`/api/reception/fiche-navette/${id}`);
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            console.error('Error fetching fiche navette:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch fiche navette',
                error
            };
        }
    },

    /**
     * Update fiche navette
     */
    async update(id, data) {
        try {
            const response = await axios.put(`/api/reception/fiche-navette/${id}`, data);
            return {
                success: true,
                data: response.data.data,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error updating fiche navette:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update fiche navette',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Delete fiche navette
     */
    async delete(id) {
        try {
            const response = await axios.delete(`/api/reception/fiche-navette/${id}`);
            return {
                success: true,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error deleting fiche navette:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to delete fiche navette',
                error
            };
        }
    },

    /**
     * Get prestations by specialization ID
     */
    async getPrestationsBySpecialization(specializationId) {
        try {
            const response = await axios.get(`/api/reception/prestations/by-specialization/${specializationId}`);
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            console.error('Error fetching prestations by specialization:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch prestations',
                error
            };
        }
    },

    /**
     * Get packages by specialization ID
     */
    async getPackagesBySpecialization(specializationId) {
        try {
            const response = await axios.get(`/api/reception/packages/by-specialization/${specializationId}`);
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            console.error('Error fetching packages by specialization:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch packages',
                error
            };
        }
    },

    /**
     * Get doctors by specialization ID using existing doctor route
     */
  async getDoctorsBySpecialization(specializationId) {
        try {
            const response = await axios.get(`/api/doctors/specializations/${specializationId}`);
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching doctors by specialization:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch doctors',
                error
            };
        }
    },

    /**
     * Get all prestations with package information for custom tab
     */
   async getAllPrestationsWithPackages(params = {}) {
        try {
            const response = await axios.get('/api/reception/prestations/with-packages', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching prestations with packages:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch prestations',
                error
            };
        }
    },

    /**
     * Get all doctors using existing route
     */
    async getAllDoctors() {
        try {
            // Use the existing search route with empty query
            const response = await axios.get('/api/doctors/search?q=');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching all doctors:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch doctors',
                error
            };
        }
    },

    /**
     * Get all prestations using existing route
     */
    async getAllPrestations() {
        try {
            // Use the existing search route with empty query
            const response = await axios.get('/api/reception/prestations/search?q=');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching all prestations:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch prestations',
                error
            };
        }
    },

    /**
     * Get items for a fiche navette using ficheNavetteItemController
     */
    async getFicheNavetteItems(ficheNavetteId) {
        try {
            const response = await axios.get(`/api/reception/fiche-navette/${ficheNavetteId}/items`);
            return {
                success: true,
                data: response.data.data
            };
        } catch (error) {
            console.error('Error fetching fiche navette items:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch items',
                error
            };
        }
    },

    /**
     * Add items to an existing fiche navette using ficheNavetteItemController
     */
    async addItemsToFiche(ficheNavetteId, data) {
        try {
            console.log('Adding items to fiche navette:', ficheNavetteId, data);
            
            const response = await axios.post(`/api/reception/fiche-navette/${ficheNavetteId}/items`, data);
            
            return {
                success: true,
                data: response.data.data,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error adding items to fiche navette:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to add items to fiche navette',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Create a simple fiche navette (no items) using ficheNavetteController
     */
    async createSimpleFiche(data) {
        try {
            const response = await axios.post('/api/reception/fiche-navette', {
                patient_id: data.patient_id,
                notes: data.notes
            });
            
            return {
                success: true,
                data: response.data.data,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error creating fiche navette:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to create fiche navette',
                errors: error.response?.data?.errors || {},
                error
            };
        }
    },

    /**
     * Update a fiche navette item using ficheNavetteItemController
     */
    async updateFicheNavetteItem(ficheNavetteId, itemId, data) {
        try {
            const response = await axios.put(`/api/reception/fiche-navette/${ficheNavetteId}/items/${itemId}`, data);
            return {
                success: true,
                data: response.data.data,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error updating fiche navette item:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to update item',
                error
            };
        }
    },

    /**
     * Remove a fiche navette item using ficheNavetteItemController
     */
    async removeFicheNavetteItem(ficheNavetteId, itemId) {
        try {
            const response = await axios.delete(`/api/reception/fiche-navette/${ficheNavetteId}/items/${itemId}`);
            return {
                success: true,
                data: response.data.data,
                message: response.data.message
            };
        } catch (error) {
            console.error('Error removing fiche navette item:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to remove item',
                error
            };
        }
    },

    /**
     * Get all companies (organismes)
     */
    async getCompanies() {
        try {
            const response = await axios.get('/api/organismes');
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching companies:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch companies',
                error
            };
        }
    },

    /**
     * Get all conventions with optional filtering
     */
    async getConventions(params = {}) {
        try {
            const response = await axios.get('/api/conventions', { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching conventions:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch conventions',
                error
            };
        }
    },

    /**
     * Get conventions by organisme IDs
     */
    async getConventionsByOrganismes(organismeIds) {
        console.log('Fetching conventions for organismes:', organismeIds);
        
        try {
            const response = await axios.get('/api/conventions', {
                params: { 
                    organisme_ids: organismeIds.join(','),
                    per_page: -1 // Get all results without pagination
                }
            });
            console.log('Convention response:', response.data);

            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching conventions by organismes:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch conventions',
                error
            };
        }
    },

    /**
     * Get prestations by convention
     */
    async getPrestationsByConvention(conventionId, priseEnChargeDate = null) {
        try {
            const params = {};
            if (priseEnChargeDate) {
                params.prise_en_charge_date = priseEnChargeDate;
            }

            const response = await axios.get(`/api/reception/prestations/by-convention/${conventionId}`, { params });
            return {
                success: true,
                data: response.data.data || response.data
            };
        } catch (error) {
            console.error('Error fetching prestations by convention:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch prestations',
                error
            };
        }
    },

    /**
     * Get prestations with convention pricing including date
     */
    async getPrestationsWithConventionPricing(conventionIds = [], priseEnChargeDate = null) {
        try {
            const params = { convention_ids: conventionIds.join(',') };
            if (priseEnChargeDate) {
                params.prise_en_charge_date = priseEnChargeDate;
            }

            const response = await axios.get('/api/reception/prestations/with-convention-pricing', { params });
            return {
                success: true,
                data: response.data.data || response.data,
                meta: response.data.meta || {}
            };
        } catch (error) {
            console.error('Error fetching prestations with convention pricing:', error);
            return {
                success: false,
                message: error.response?.data?.message || 'Failed to fetch prestations',
                error
            };
        }
    },
  async createConventionPrescription(ficheNavetteId, data) {
    try {
        const response = await axios.post(`/api/fiche-navette/${ficheNavetteId}/convention-prescription`, data, {
            headers: {
                'Content-Type': 'multipart/form-data' // Important for file upload
            }
        });
        return response.data;
    } catch (error) {
        console.error('Error creating convention prescription:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to create convention prescription'
        };
    }
},
async removeDependency(dependencyId) {
    try {
        const response = await axios.delete(`/api/reception/dependencies/${dependencyId}`)
        return response.data
    } catch (error) {
        console.error('Error removing dependency:', error)
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to remove dependency'
        }
    }
},

async getPatientConventions(patientId , ficheNavetteId) {
    try {
        const response = await axios.get(`/api/reception/patients/${patientId}/conventions`,{
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
},
async getGroupedItems(ficheNavetteId) {
    try {
        const response = await axios.get(`/api/reception/fiche-navette/${ficheNavetteId}/grouped-items`);
        return {
            success: true,
            data: response.data.data,
            meta: response.data.meta
        };
    } catch (error) {
        console.error('Error fetching grouped items:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to fetch grouped items',
            error
        };
    }
},

    /**
     * Upload convention files
     */
    async uploadConventionFiles(files) {
        try {
            const formData = new FormData()
            files.forEach((file, index) => {
                formData.append(`files[${index}]`, file)
            })

            const response = await axios.post('/api/fiche-navette-items/upload-convention-files', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            return response.data
        } catch (error) {
            console.error('Error uploading convention files:', error)
            return { success: false, message: error.response?.data?.message || 'Upload failed' }
        }
    },
 async getFamilyAuthorization(priseEnChargeDate) {
    try {
        const response = await axios.get('/api/conventions/family-authorization', {
            params: { prise_en_charge_date: priseEnChargeDate },
        });
        return response.data;
    } catch (error) {
        console.error('Error fetching family authorization:', error);
        return {
            success: false,
            message: error.response?.data?.message || 'Failed to fetch family authorization',
        };
    }
},

    /**
     * Download convention file
     */
    async downloadConventionFile(fileId) {
        try {
            const response = await axios.get(`/api/fiche-navette-items/download-convention-file/${fileId}`, {
                responseType: 'blob'
            })
            return response
        } catch (error) {
            console.error('Error downloading file:', error)
            throw error
        }
    }
};

export default ficheNavetteService;