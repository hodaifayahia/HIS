<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToastr } from '../../../../Components/toster'
import { useSweetAlert } from '../../../../Components/useSweetAlert'

const toastr = useToastr()
const sweetAlert = useSweetAlert()

const wilayaByRegion = ref([
  {
    region: 'Northern Algeria',
    wilayas: [
      { code: '16', name: 'Algiers' },
      { code: '09', name: 'Blida' },
      { code: '35', name: 'Boumerdès' },
      { code: '42', name: 'Tipaza' },
      { code: '44', name: 'Aïn Defla' },
      { code: '02', name: 'Chlef' },
      { code: '27', name: 'Mostaganem' },
      { code: '48', name: 'Relizane' },
      { code: '31', name: 'Oran' },
      { code: '46', name: 'Aïn Témouchent' },
      { code: '13', name: 'Tlemcen' },
      { code: '22', name: 'Sidi Bel Abbès' },
      { code: '29', name: 'Mascara' },
      { code: '20', name: 'Saïda' },
      { code: '14', name: 'Tiaret' },
      { code: '38', name: 'Tissemsilt' },
      { code: '26', name: 'Médéa' },
      { code: '10', name: 'Bouira' },
      { code: '15', name: 'Tizi Ouzou' },
      { code: '06', name: 'Béjaïa' },
      { code: '18', name: 'Jijel' },
      { code: '21', name: 'Skikda' },
      { code: '23', name: 'Annaba' },
      { code: '36', name: 'El Tarf' }
    ]
  },
  {
    region: 'High Plateaus',
    wilayas: [
      { code: '03', name: 'Laghouat' },
      { code: '17', name: 'Djelfa' },
      { code: '28', name: 'M\'Sila' },
      { code: '19', name: 'Sétif' },
      { code: '34', name: 'Bordj Bou Arréridj' },
      { code: '43', name: 'Mila' },
      { code: '25', name: 'Constantine' },
      { code: '04', name: 'Oum El Bouaghi' },
      { code: '05', name: 'Batna' },
      { code: '40', name: 'Khenchela' },
      { code: '12', name: 'Tébessa' },
      { code: '41', name: 'Souk Ahras' },
      { code: '24', name: 'Guelma' },
      { code: '32', name: 'El Bayadh' },
      { code: '45', name: 'Naâma' }
    ]
  },
  {
    region: 'Sahara',
    wilayas: [
      { code: '01', name: 'Adrar' },
      { code: '08', name: 'Béchar' },
      { code: '11', name: 'Tamanrasset' },
      { code: '30', name: 'Ouargla' },
      { code: '33', name: 'Illizi' },
      { code: '37', name: 'Tindouf' },
      { code: '39', name: 'El Oued' },
      { code: '47', name: 'Ghardaïa' },
      { code: '07', name: 'Biskra' },
      { code: '49', name: 'Timimoun' },
      { code: '50', name: 'Bordj Badji Mokhtar' },
      { code: '51', name: 'Ouled Djellal' },
      { code: '52', name: 'Béni Abbès' },
      { code: '53', name: 'In Salah' },
      { code: '54', name: 'In Guezzam' },
      { code: '55', name: 'Touggourt' },
      { code: '56', name: 'Djanet' },
      { code: '57', name: 'El M\'Ghair' },
      { code: '58', name: 'El Meniaa' }
    ]
  }
])

const getWilayaName = (code) => {
  const wilaya = wilayaList.value.find(w => w.code === code)
  return wilaya ? wilaya.name : ''
}

const getWilayaByRegion = (region) => {
  const regionData = wilayaByRegion.value.find(r => r.region === region)
  return regionData ? regionData.wilayas : []
}

const wilayaList = computed(() => {
  return wilayaByRegion.value.flatMap(region => region.wilayas)
})

const searchTerm = ref('')
const filteredWilayas = computed(() => {
  if (!searchTerm.value) return wilayaList.value
  return wilayaList.value.filter(wilaya =>
    wilaya.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    wilaya.code.includes(searchTerm.value)
  )
})

const logoFileInput = ref(null)
const profileImageFileInput = ref(null)
const logoFile = ref(null)
const profileImageFile = ref(null)
const logoFileName = ref('')
const profileImageFileName = ref('')
const logoPreview = ref('')
const profileImagePreview = ref('')

const form = reactive({
 id: null, // Add this line
  name: '',
  legal_form: '',
  trade_register_number: '',
  tax_id_nif: '',
  statistical_id: '',
  article_number: '',
  wilaya: '',
  address: '',
  postal_code: '',
  phone: '',
  fax: '',
  mobile: '',
  email: '',
  website: '',
  latitude: null,
  longitude: null,
  initial_invoice_number: '',
  initial_credit_note_number: '',
  logo_file: null,
  profile_image_file: null,
  description: '',
  industry: '',
  creation_date: '',
  number_of_employees: null
})

const isLoading = ref(false)

const handleFileUpload = (event, type) => {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      toastr.error('File size must be less than 2MB.')
      type === 'logo' ? removeLogo() : removeProfileImage()
      return
    }

    if (!file.type.startsWith('image/')) {
      toastr.error('Please select an image file (PNG, JPG, SVG).')
      type === 'logo' ? removeLogo() : removeProfileImage()
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      if (type === 'logo') {
        logoFile.value = file
        logoFileName.value = file.name
        form.logo_file = file
        logoPreview.value = e.target.result
      } else {
        profileImageFile.value = file
        profileImageFileName.value = file.name
        form.profile_image_file = file
        profileImagePreview.value = e.target.result
      }
    }
    reader.readAsDataURL(file)
  }
}

const handleLogoUpload = (event) => handleFileUpload(event, 'logo')
const handleProfileImageUpload = (event) => handleFileUpload(event, 'profile')

const removeLogo = () => {
  logoFile.value = null
  logoFileName.value = ''
  logoPreview.value = ''
  form.logo_file = null
  if (logoFileInput.value) logoFileInput.value.value = ''
}

const removeProfileImage = () => {
  profileImageFile.value = null
  profileImageFileName.value = ''
  profileImagePreview.value = ''
  form.profile_image_file = null
  if (profileImageFileInput.value) profileImageFileInput.value.value = ''
}
const saveSettings = async () => {
  isLoading.value = true
  try {
    const formData = new FormData()
    
    // Add all form fields to FormData
    Object.keys(form).forEach(key => {
      if (form[key] !== null && form[key] !== '') {
        formData.append(key, form[key])
      }
    })

    let response
    
    // Check if we have an ID to determine if this is an update or create
    if (form.id) {
      // Update existing record
      formData.append('_method', 'PUT') // Laravel method spoofing for file uploads
      response = await axios.post(`/api/organismes/${form.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
    } else {
      // Create new record
      response = await axios.post('/api/organismes', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      // Store the ID from the created record
      if (response.data && response.data.data && response.data.data.id) {
        form.id = response.data.data.id
      }
    }

    toastr.success('Settings saved successfully!')
    console.log('Settings saved:', response.data)

  } catch (error) {
    console.error('Error saving settings:', error)
    const message = error.response?.data?.message || 'Something went wrong.'
    toastr.error(message)
  } finally {
    isLoading.value = false
  }
}

const resetForm = async () => {
  const confirmed = await sweetAlert.confirm('Are you sure?', 'This will reset all fields.')
  if (!confirmed) return

  Object.keys(form).forEach(key => {
    if (typeof form[key] === 'string') {
      form[key] = ''
    } else {
      form[key] = null
    }
  })

  removeLogo()
  removeProfileImage()
  toastr.info('Form reset.')
}
const loadSettings = async () => {
  try {
    console.log('Loading settings...')
    const response = await axios.get('/api/organismes/settings')
    
    // Check if response has the expected structure
    if (!response.data || !response.data.data) {
      console.error('Invalid API response structure:', response.data)
      toastr.error('Invalid response format from server.')
      return
    }
    
    const data = response.data.data
    
    // Safely assign form data including the ID
    Object.keys(form).forEach(key => {
      if (key !== 'logo_file' && key !== 'profile_image_file') {
        if (data && typeof data === 'object' && data.hasOwnProperty(key)) {
          form[key] = data[key]
        }
      }
    })
    
    // Handle image previews safely
    if (data && data.logo_url) {
      logoPreview.value = data.logo_url
    }
    if (data && data.profile_image_url) {
      profileImagePreview.value = data.profile_image_url
    }
    
    console.log('Settings loaded:', form)
    toastr.success('Settings loaded successfully!')
  } catch (error) {
    console.error('Error loading settings:', error)
    const message = error.response?.data?.message || 'Failed to load settings.'
    toastr.error(message)
  }
}





onMounted(() => {
  loadSettings()
})
</script>


<template>
  <div class="organization-settings">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
          <div class="settings-header">
            <h1 class="settings-title">
              <i class="fas fa-building me-3"></i>
              Organization Settings
            </h1>
            <p class="settings-subtitle">Configure your main organization details</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="saveSettings" class="settings-form">
        <div class="row g-4">
          <div class="col-12">
            <div class="form-section">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="fas fa-info-circle me-2"></i>
                  General Information
                </h3>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="name" class="form-label required">Company Name</label>
                    <input
                      type="text"
                      id="name"
                      v-model="form.name"
                      class="form-control premium-input"
                      placeholder="Enter company name"
                      required
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="legal_form" class="form-label">Legal Form</label>
                    <select
                      id="legal_form"
                      v-model="form.legal_form"
                      class="form-select "
                    >
                      <option value="">Select legal form</option>
                      <option value="EURL">EURL</option>
                      <option value="SARL">SARL</option>
                      <option value="SPA">SPA</option>
                      <option value="SNC">SNC</option>
                      <option value="SCS">SCS</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="trade_register_number" class="form-label">Trade Register Number</label>
                    <input
                      type="text"
                      id="trade_register_number"
                      v-model="form.trade_register_number"
                      class="form-control premium-input"
                      placeholder="Enter trade register number"
                    />
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="tax_id_nif" class="form-label">Tax ID (NIF)</label>
                    <input
                      type="text"
                      id="tax_id_nif"
                      v-model="form.tax_id_nif"
                      class="form-control premium-input"
                      placeholder="Enter tax ID"
                    />
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="statistical_id" class="form-label">Statistical ID</label>
                    <input
                      type="text"
                      id="statistical_id"
                      v-model="form.statistical_id"
                      class="form-control premium-input"
                      placeholder="Enter statistical ID"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="article_number" class="form-label">Article Number</label>
                    <input
                      type="text"
                      id="article_number"
                      v-model="form.article_number"
                      class="form-control premium-input"
                      placeholder="Enter article number"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="industry" class="form-label">Industry</label>
                    <input
                      type="text"
                      id="industry"
                      v-model="form.industry"
                      class="form-control premium-input"
                      placeholder="Enter industry sector"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-section">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="fas fa-map-marker-alt me-2"></i>
                  Contact Information
                </h3>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="wilaya" class="form-label">Wilaya</label>
                    <select
                      id="wilaya"
                      v-model="form.wilaya"
                      class="form-select "
                    >
                      <option value="">Select wilaya</option>
                      <optgroup
                        v-for="region in wilayaByRegion"
                        :key="region.region"
                        :label="region.region"
                      >
                        <option
                          v-for="wilaya in region.wilayas"
                          :key="wilaya.code"
                          :value="wilaya.code"
                        >
                          {{ wilaya.code }} - {{ wilaya.name }}
                        </option>
                      </optgroup>
                    </select>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="postal_code" class="form-label">Postal Code</label>
                    <input
                      type="text"
                      id="postal_code"
                      v-model="form.postal_code"
                      class="form-control premium-input"
                      placeholder="Enter postal code"
                      maxlength="10"
                    />
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <textarea
                      id="address"
                      v-model="form.address"
                      class="form-control premium-input"
                      rows="3"
                      placeholder="Enter full address"
                    ></textarea>
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="phone" class="form-label">Phone</label>
                    <input
                      type="tel"
                      id="phone"
                      v-model="form.phone"
                      class="form-control premium-input"
                      placeholder="+213 XX XX XX XX"
                      maxlength="20"
                    />
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="mobile" class="form-label">Mobile</label>
                    <input
                      type="tel"
                      id="mobile"
                      v-model="form.mobile"
                      class="form-control premium-input"
                      placeholder="+213 XX XX XX XX"
                      maxlength="20"
                    />
                  </div>
                </div>

                <div class="col-md-4">
                  <div class="form-group">
                    <label for="fax" class="form-label">Fax</label>
                    <input
                      type="tel"
                      id="fax"
                      v-model="form.fax"
                      class="form-control premium-input"
                      placeholder="+213 XX XX XX XX"
                      maxlength="20"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input
                      type="email"
                      id="email"
                      v-model="form.email"
                      class="form-control premium-input"
                      placeholder="company@example.com"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="website" class="form-label">Website</label>
                    <input
                      type="url"
                      id="website"
                      v-model="form.website"
                      class="form-control premium-input"
                      placeholder="https://www.example.com"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-section">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="fas fa-briefcase me-2"></i>
                  Business Details
                </h3>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="creation_date" class="form-label">Creation Date</label>
                    <input
                      type="date"
                      id="creation_date"
                      v-model="form.creation_date"
                      class="form-control premium-input"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="number_of_employees" class="form-label">Number of Employees</label>
                    <input
                      type="number"
                      id="number_of_employees"
                      v-model="form.number_of_employees"
                      class="form-control premium-input"
                      placeholder="Enter number of employees"
                      min="0"
                    />
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      class="form-control premium-input"
                      rows="4"
                      placeholder="Enter company description"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-section">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="fas fa-file-invoice me-2"></i>
                  Invoice Settings
                </h3>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="initial_invoice_number" class="form-label">Initial Invoice Number</label>
                    <input
                      type="text"
                      id="initial_invoice_number"
                      v-model="form.initial_invoice_number"
                      class="form-control premium-input"
                      placeholder="INV-0001"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="initial_credit_note_number" class="form-label">Initial Credit Note Number</label>
                    <input
                      type="text"
                      id="initial_credit_note_number"
                      v-model="form.initial_credit_note_number"
                      class="form-control premium-input"
                      placeholder="CN-0001"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-section">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="fas fa-images me-2"></i>
                  Media & Branding
                </h3>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="logo_file" class="form-label">Company Logo</label>
                    <div class="file-upload-wrapper">
                      <input
                        type="file"
                        id="logo_file"
                        ref="logoFileInput"
                        @change="handleLogoUpload"
                        accept="image/png, image/jpeg, image/svg+xml"
                        class="file-input"
                        hidden
                      />
                      <label for="logo_file" class="file-upload-button">
                        <i class="fas fa-upload me-2"></i> Choose File
                      </label>
                      <span class="file-name-display">{{ logoFileName || 'No file chosen' }}</span>
                      <p class="file-hint">PNG, JPG, SVG up to 2MB</p>

                      <div v-if="logoPreview" class="file-preview-container">
                        <img :src="logoPreview" alt="Logo preview" class="preview-image" />
                        <button type="button" @click="removeLogo" class="remove-file-btn" aria-label="Remove logo">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="profile_image_file" class="form-label">Profile Image</label>
                    <div class="file-upload-wrapper">
                      <input
                        type="file"
                        id="profile_image_file"
                        ref="profileImageFileInput"
                        @change="handleProfileImageUpload"
                        accept="image/png, image/jpeg, image/svg+xml"
                        class="file-input"
                        hidden
                      />
                      <label for="profile_image_file" class="file-upload-button">
                        <i class="fas fa-upload me-2"></i> Choose File
                      </label>
                      <span class="file-name-display">{{ profileImageFileName || 'No file chosen' }}</span>
                      <p class="file-hint">PNG, JPG, SVG up to 2MB</p>

                      <div v-if="profileImagePreview" class="file-preview-container">
                        <img :src="profileImagePreview" alt="Profile image preview" class="preview-image" />
                        <button type="button" @click="removeProfileImage" class="remove-file-btn" aria-label="Remove profile image">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-section">
              <div class="section-header">
                <h3 class="section-title">
                  <i class="fas fa-globe me-2"></i>
                  Geographic Coordinates
                </h3>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input
                      type="number"
                      id="latitude"
                      v-model="form.latitude"
                      class="form-control premium-input"
                      placeholder="36.7538"
                      step="0.0000001"
                      min="-90"
                      max="90"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input
                      type="number"
                      id="longitude"
                      v-model="form.longitude"
                      class="form-control premium-input"
                      placeholder="3.0588"
                      step="0.0000001"
                      min="-180"
                      max="180"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <div class="d-flex justify-content-end gap-3">
           
            <button type="submit" class="btn btn-primary btn-lg" :disabled="isLoading">
              <i class="fas fa-save me-2"></i>
              <span v-if="isLoading">Saving...</span>
              <span v-else>Save Settings</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* Premium Organization Settings Styles */
.organization-settings {
    background: linear-gradient(135deg, #5271fa 0%, #2d72f1 100%);
  min-height: 100vh;
  padding: 2rem 0;
}

.container-fluid {
  max-width: 1200px;
  margin: 0 auto;
}

/* Header Styles */
.settings-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.settings-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #5271fa 0%, #2d72f1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.settings-subtitle {
  font-size: 1.1rem;
  color: #6c757d;
  margin: 0;
}

/* Form Styles */
.settings-form {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Enhanced select styling */
.form-select.premium-input {
  background-position: right 0.75rem center;
  background-size: 16px 12px;
}

.form-select.premium-input optgroup {
  font-weight: 600;
  color: #667eea;
  background-color: #f8f9fa;
  padding: 0.5rem;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e9ecef;
}

.form-select.premium-input option {
  padding: 0.5rem 1rem;
  color: #2c3e50;
  background-color: white;
}

.form-select.premium-input option:checked {
  background-color: #667eea;
  color: white;
}

/* Loading state for dynamic data */
.select-loading {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24'%3E%3Cpath fill='%23667eea' d='M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z'%3E%3CanimateTransform attributeName='transform' dur='0.75s' repeatCount='indefinite' type='rotate' values='0 12 12;360 12 12'/%3E%3C/path%3E%3C/svg%3E");
  background-position: right 0.75rem center;
  background-repeat: no-repeat;
}

.form-section {
  background: #ffffff;
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.form-section:hover {
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.section-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f8f9fa;
}

.section-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
  display: flex;
  align-items: center;
}

.section-title i {
  color: #667eea;
}

/* Form Group Styles */
.form-group {
  margin-bottom: 1rem;
}

.form-label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-label.required::after {
  content: " *";
  color: #e74c3c;
}

/* Premium Input Styles */
.premium-input {
  border: 2px solid #e9ecef;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.premium-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
  background: #ffffff;
  transform: translateY(-1px);
}

.premium-input:hover {
  border-color: #adb5bd;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.premium-input::placeholder {
  color: #adb5bd;
  font-style: italic;
}

/* Select Styles */
.form-select.premium-input {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23667eea' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
}

/* Textarea Styles */
textarea.premium-input {
  resize: vertical;
  min-height: 100px;
}

/* Action Buttons */
.form-actions {
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 2px solid #f8f9fa;
}

.btn-lg {
  padding: 0.75rem 2rem;
  font-size: 1.1rem;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-outline-secondary {
  border: 2px solid #6c757d;
  color: #6c757d;
  background: transparent;
}

.btn-outline-secondary:hover {
  background: #6c757d;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
}

/* File Upload Styles */
.file-upload-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
  border: 2px dashed #e9ecef;
  border-radius: 10px;
  padding: 1.5rem;
  background-color: #f8f9fa;
  transition: all 0.3s ease;
}

.file-upload-wrapper:hover {
  border-color: #667eea;
  background-color: #eff2fa;
}

.file-upload-button {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
}

.file-upload-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.file-name-display {
  font-size: 0.9rem;
  color: #495057;
  font-style: italic;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%; /* Ensure it doesn't overflow */
}

.file-hint {
  font-size: 0.8rem;
  color: #6c757d;
  margin-top: 0.25rem;
  margin-bottom: 0;
}

.file-preview-container {
  margin-top: 1rem;
  position: relative;
  width: 120px; /* Standardize preview size */
  height: 120px;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #fff;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.preview-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain; /* Ensures the image fits within the box without cropping */
  display: block;
}

.remove-file-btn {
  position: absolute;
  top: 5px;
  right: 5px;
  background-color: rgba(220, 53, 69, 0.9);
  color: white;
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  font-size: 0.8rem;
  transition: background-color 0.2s ease;
}

.remove-file-btn:hover {
  background-color: #dc3545;
}

/* Responsive Design */
@media (max-width: 768px) {
  .organization-settings {
    padding: 1rem 0;
  }

  .settings-header {
    padding: 1.5rem;
    margin-bottom: 1rem;
  }

  .settings-title {
    font-size: 2rem;
  }

  .settings-form {
    padding: 1rem;
  }

  .form-section {
    padding: 1rem;
  }

  .form-actions .d-flex {
    flex-direction: column;
  }

  .btn-lg {
    width: 100%;
    margin-bottom: 0.5rem;
  }

  .file-upload-wrapper {
    padding: 1rem; /* Adjust padding for smaller screens */
  }

  .file-upload-button {
    width: 100%; /* Make button full width on small screens */
    justify-content: center;
  }
}

/* Animation for form sections */
.form-section {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Loading state */
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #5a6fd8, #6a4190);
}
</style>