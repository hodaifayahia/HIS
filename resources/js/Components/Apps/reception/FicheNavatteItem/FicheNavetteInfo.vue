<!-- components/Reception/FicheNavette/FicheNavetteInfo.vue -->
<script setup>
import { computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Chip from 'primevue/chip'
import Badge from 'primevue/badge'
import Button from 'primevue/button'

// Add useToast
const toast = useToast()

const props = defineProps({
  fiche: {
    type: Object,
    required: true
  },
  totalAmount: {
    type: Number,
    required: true
  },
  itemsCount: {
    type: Number,
    required: true
  },
  groupsCount: {
    type: Number,
    required: true
  },
  conventionCompanies: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['show-convention-details', 'show-all-conventions'])

// Add company colors array (same as FicheNavetteItemCreate)
const companyColors = [
  { bg: '#3B82F6', light: '#DBEAFE', border: '#93C5FD', name: 'Blue' },
  { bg: '#10B981', light: '#D1FAE5', border: '#6EE7B7', name: 'Green' },
  { bg: '#8B5CF6', light: '#EDE9FE', border: '#C4B5FD', name: 'Purple' },
  { bg: '#F59E0B', light: '#FEF3C7', border: '#FCD34D', name: 'Yellow' },
  { bg: '#EF4444', light: '#FEE2E2', border: '#FECACA', name: 'Red' },
  { bg: '#06B6D4', light: '#CFFAFE', border: '#67E8F9', name: 'Cyan' },
  { bg: '#84CC16', light: '#ECFCCB', border: '#BEF264', name: 'Lime' },
  { bg: '#EC4899', light: '#FCE7F3', border: '#F9A8D4', name: 'Pink' },
]

// Function to get company color (same as FicheNavetteItemCreate)
const getCompanyColor = (company, index) => {
  // First check if company has organism_color
  if (company.organism_color) {
    console.log('Custom organism color found:', company.organism_color);
    return {
      bg: company.organism_color,
      light: company.organism_color + '22', // Add transparency
      border: company.organism_color + '66',
      name: 'Custom'
    }
  }
  
  // Fallback to palette based on company ID or index
  const colorIndex = company.id ? (company.id % companyColors.length) : (index % companyColors.length)
  return companyColors[colorIndex]
}

// Enhanced computed properties with colors
const conventionCompaniesWithColors = computed(() => {
  return props.conventionCompanies.map((company, index) => ({
    ...company,
    color: getCompanyColor(company, index)
  }))
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'DZD'
  }).format(amount || 0)
}

const getStatusSeverity = (status) => {
  const statusMap = {
    'pending': 'warning',
    'in_progress': 'info',
    'completed': 'success',
    'cancelled': 'danger',
    'required': 'secondary'
  }
  return statusMap[status] || 'secondary'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const statusLabel = computed(() => {
  const labels = {
    'pending': 'En attente',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'cancelled': 'Annulé',
    'required': 'Requis'
  }
  return labels[props.fiche.status] || props.fiche.status
})

const totalConventions = computed(() => {
  return props.conventionCompanies.reduce((total, company) => {
    return total + (company.conventions_count || company.conventions?.length || 0)
  }, 0)
})

const hasConventions = computed(() => {
  return props.conventionCompanies && props.conventionCompanies.length > 0
})

// Enhanced click handler - pass company with color
const showOrganismeDetails = (organisme) => {
  console.log('Showing organisme details:', organisme) // Debug
  emit('show-convention-details', organisme)
}

const showAllConventions = () => {
  emit('show-all-conventions')
}

// Add file handling methods
const getFileIcon = (mimeTypeOrName) => {
  if (!mimeTypeOrName) return 'pi pi-file'
  const type = mimeTypeOrName.toLowerCase()
  if (type.includes('pdf')) return 'pi pi-file-pdf'
  if (type.includes('word') || type.includes('doc')) return 'pi pi-file-word'
  if (type.includes('excel') || type.includes('xls')) return 'pi pi-file-excel'
  if (type.includes('image')) return 'pi pi-image'
  return 'pi pi-file'
}

const formatFileSize = (bytes) => {
  if (!bytes) return 'Unknown size'
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const viewFile = (file) => {
  if (file && file.id) {
    window.open(`/api/fiche-navette/files/${file.id}/view`, '_blank')
  } else {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'File ID not available',
      life: 3000
    })
  }
}

const downloadFile = (file) => {
  if (file && file.id) {
    const link = document.createElement('a')
    link.href = `/api/fiche-navette/files/${file.id}/download`
    link.download = file.original_name || 'download'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'File ID not available',
      life: 3000
    })
  }
}
</script>

<template>
  <Card class="fiche-info-card">
    
    <template #content>
      <div class="fiche-info-content">
        <!-- Patient Section -->
        <div class="info-section patient-section">
          <div class="section-header">
            <i class="pi pi-user section-icon"></i>
            <h4 class="section-title">Patient Information</h4>
          </div>
          <div class="patient-details">
            <Avatar 
              :label="fiche.patient_name.charAt(0)" 
              class="patient-avatar"
              size="large"
              shape="circle"
            />
            <div class="patient-info">
              <h5 class="patient-name">{{ fiche.patient_name }}</h5>
              <p class="patient-id">ID: {{ fiche.patient_id }}</p>
            </div>
          </div>
        </div>

        <!-- Fiche Details Section -->
        <div class="info-section details-section">
          <div class="section-header">
            <i class="pi pi-info-circle section-icon"></i>
            <h4 class="section-title">Fiche Details</h4>
          </div>
          <div class="details-grid">
            <div class="detail-item">
              <span class="detail-label">
                <i class="pi pi-calendar mr-2"></i>
                Date
              </span>
              <span class="detail-value">{{ formatDate(fiche.fiche_date) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">
                <i class="pi pi-flag mr-2"></i>
                Status
              </span>
              <Tag 
                :value="statusLabel"
                :severity="getStatusSeverity(fiche.status)"
                class="status-tag"
              />
            </div>
            <div class="detail-item">
              <span class="detail-label">
                <i class="pi pi-flag mr-2"></i>
                balance
              </span>
            {{fiche.patient_balance}}
            </div>
          </div>
        </div>

        <!-- Enhanced Convention Companies Section with Colors -->
        <div v-if="hasConventions" class="info-section convention-section">
          <div class="section-header">
            <i class="pi pi-building section-icon"></i>
            <h4 class="section-title">Convention Companies Used</h4>
          </div>
          <div class="convention-content">
            <div class="convention-summary">
              <div class="summary-stats">
                <div class="stat-item">
                  <span class="stat-number">{{ conventionCompaniesWithColors.length }}</span>
                  <span class="stat-label">Companies</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">{{ totalConventions }}</span>
                  <span class="stat-label">Conventions</span>
                </div>
              </div>
            </div>
            
            <div class="convention-companies-display">
              <div class="companies-tags">
                <Tag
                  v-for="company in conventionCompaniesWithColors.slice(0, 3)"
                  :key="company.id"
                  :value="company.organisme_name || company.company_name"
                  class="company-tag"
                  @click="showOrganismeDetails(company)"
                  :style="{
                    backgroundColor: company.organisme_color,
                    color: '#fff',
                    borderColor: company.organisme_color,
                    border: `2px solid ${company.organisme_color }`
                  }"
                >

                  <template #default>
                    <div class="tag-content">
                      <i class="pi pi-building" :style="{ color: '#fff' }"></i>
                      <span>{{ company.organisme_name || company.company_name }}</span>
                      <Badge 
                        v-if="company.conventions_count || company.conventions?.length" 
                        :value="company.conventions_count || company.conventions?.length" 
                        :style="{
                          backgroundColor: company.organisme_color || company.color.bg,
                          color: '#fff'
                        }"
                      />
                    </div>
                  </template>
                </Tag>
                
                <Tag
                  v-if="conventionCompaniesWithColors.length > 3"
                  :value="`+${conventionCompaniesWithColors.length - 3} more`"
                  severity="secondary"
                  class="more-companies-tag"
                  @click="showAllConventions"
                />
              </div>
              
              <div v-if="conventionCompaniesWithColors.length > 3" class="view-all-section">
                <Button
                  label="View All Companies"
                  icon="pi pi-external-link"
                  class="p-button-text p-button-sm"
                  @click="showAllConventions"
                />
              </div>
            </div>

         
          </div>
        </div>

        <!-- Summary Section -->
        <div class="info-section summary-section">
          <div class="section-header">
            <i class="pi pi-chart-line section-icon"></i>
            <h4 class="section-title">Summary</h4>
          </div>
          <div class="summary-grid">
            <div class="summary-item">
              <div class="summary-icon items-icon">
                <i class="pi pi-list"></i>
              </div>
              <div class="summary-content">
                <span class="summary-value">{{ itemsCount }}</span>
                <span class="summary-label">Total Items</span>
              </div>
            </div>
            
            <div class="summary-item total-item">
              <div class="summary-icon total-icon">
                <i class="pi pi-dollar"></i>
              </div>
              <div class="summary-content">
                <span class="summary-value total-value">{{ formatCurrency(totalAmount) }}</span>
                <span class="summary-label">Total Amount</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
/* Keep all existing styles and add these new enhanced ones */

/* Enhanced Company Tag Styles */
.company-tag {
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  padding: 0.5rem 1rem;
}

.company-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  filter: brightness(1.05);
}

.tag-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Convention Content Layout */
.convention-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.convention-summary {
  background: var(--surface-50);
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid var(--primary-color);
}

.summary-stats {
  display: flex;
  justify-content: space-around;
  text-align: center;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
  font-weight: 500;
}

.companies-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.more-companies-tag {
  cursor: pointer;
  transition: all 0.2s ease;
}

.more-companies-tag:hover {
  background: var(--primary-100);
}

.view-all-section {
  text-align: center;
  margin-top: 0.5rem;
}

.conventions-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 1rem;
}

/* Enhanced Company Detail Card */
.company-detail-card {
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
  margin-bottom: 1rem;
}

.company-detail-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.company-header {
  margin: -1rem -1rem 1rem -1rem;
  padding: 1rem;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.company-header h5 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

/* Convention Detail Enhancements */
.convention-detail {
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  transition: all 0.2s ease;
}

.convention-detail:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.convention-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.convention-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 1rem;
}

/* Prestations Styles (MAIN FOCUS) */
.convention-prestations {
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.prestations-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
  letter-spacing: 0.025em;
}

.prestations-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.prestation-item {
  display: flex;
  flex-direction: column;
  padding: 0.5rem;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  border-left: 3px solid;
  transition: all 0.2s ease;
  background: #f8fafc;
}

.prestation-item:hover {
  transform: translateX(2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.prestation-name {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.prestation-spec {
  font-size: 0.75rem;
  font-weight: 500;
  margin-top: 0.25rem;
}

.more-prestations {
  color: #6b7280;
  font-style: italic;
  text-align: center;
  padding: 0.5rem;
  font-size: 0.75rem;
}

/* File Item Enhancements (Secondary) */
.convention-files {
  margin-top: 0.75rem;
}

.files-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
  letter-spacing: 0.025em;
}

.files-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  border-left: 3px solid;
  transition: all 0.2s ease;
}

.file-item:hover {
  background: #f1f5f9;
  transform: translateX(2px);
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.file-info i {
  font-size: 1rem;
}

.file-name {
  font-size: 0.875rem;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
  font-weight: 500;
}

.file-actions {
  display: flex;
  gap: 0.25rem;
}

.file-actions .p-button {
  padding: 0.4rem;
  min-width: auto;
  transition: all 0.2s ease;
}

.file-actions .p-button:hover {
  transform: scale(1.1);
}

.more-files {
  color: #6b7280;
  font-style: italic;
  text-align: center;
  padding: 0.5rem;
  font-size: 0.75rem;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 4px;
  margin-top: 0.25rem;
}

/* Keep all existing styles */
.fiche-info-card {
  background: linear-gradient(135deg, white 0%, var(--surface-50) 100%);
  border: 1px solid var(--surface-200);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.fiche-info-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  padding: 0.5rem;
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid var(--surface-200);
}

.section-icon {
  color: var(--primary-color);
  font-size: 1.25rem;
}

.section-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-color);
}

/* Patient Section */
.patient-details {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.patient-avatar {
  background: var(--primary-color);
  color: white;
  font-weight: 600;
}

.patient-info {
  flex: 1;
}

.patient-name {
  margin: 0 0 0.25rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
}

.patient-id {
  margin: 0;
  color: var(--text-color-secondary);
  font-size: 0.9rem;
}

/* Details Section */
.details-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: var(--surface-50);
  border-radius: 8px;
  border-left: 4px solid var(--primary-color);
}

.detail-label {
  font-weight: 500;
  color: var(--text-color-secondary);
  display: flex;
  align-items: center;
}

.detail-value {
  font-weight: 600;
  color: var(--text-color);
}

.status-tag {
  font-weight: 600;
}

/* Summary Section */
.summary-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--surface-50);
  border-radius: 12px;
  transition: all 0.2s ease;
}

.summary-item:hover {
  background: var(--surface-100);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.summary-item.total-item {
  background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%);
  border: 1px solid var(--primary-200);
}

.summary-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.items-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.groups-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.total-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.summary-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
  line-height: 1;
}

.summary-value.total-value {
  color: var(--primary-color);
  font-size: 1.75rem;
}

.summary-label {
  font-size: 0.875rem;
  color: var(--text-color-secondary);
  font-weight: 500;
  margin-top: 0.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .fiche-info-content {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .fiche-info-content {
    gap: 1rem;
    padding: 0;
  }
  
  .patient-details {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .detail-item {
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
  }
  
  .summary-item {
    padding: 0.75rem;
  }
  
  .summary-value {
    font-size: 1.25rem;
  }
  
  .summary-value.total-value {
    font-size: 1.5rem;
  }
  
  .company-detail-card {
    margin-bottom: 1rem;
  }
  
  .convention-header {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }
  
  .file-item, .prestation-item {
    padding: 0.75rem 0.5rem;
  }
  
  .file-name {
    max-width: 120px;
  }
  
  .file-actions {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
