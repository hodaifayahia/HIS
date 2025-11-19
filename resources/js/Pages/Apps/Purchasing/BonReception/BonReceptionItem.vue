<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-slate-50 tw-to-blue-50 tw-p-6">
    <!-- Status Alert -->
    <Message v-if="bonReception.status && bonReception.status !== 'pending'" :severity="getStatusSeverity(bonReception.status)" :closable="false" class="tw-mb-6 tw-rounded-xl tw-shadow-sm tw-border-l-4">
      <div class="tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i :class="getStatusIcon(bonReception.status)" class="tw-text-lg"></i>
          <div>
            <span class="tw-font-semibold">Status: {{ getStatusLabel(bonReception.status) }}</span>
            <p class="tw-text-sm tw-mt-1 tw-opacity-90">{{ bonReception.status !== 'pending' ? 'This reception is locked for editing.' : '' }}</p>
          </div>
        </div>
        <div v-if="bonReception.bon_retour_id" class="tw-flex tw-items-center tw-gap-2">
          <i class="pi pi-reply tw-text-orange-500"></i>
          <span class="tw-text-sm tw-font-medium">Return note created</span>
          <Button 
            label="View Return" 
            icon="pi pi-external-link"
            size="small"
            outlined
            severity="warning"
            @click="viewReturn"
            class="tw-rounded-lg"
          />
        </div>
      </div>
    </Message>

    <!-- Surplus Alert -->
    <Message v-if="hasSurplusItems" severity="warn" :closable="false" class="tw-mb-6 tw-rounded-xl tw-shadow-sm tw-border-l-4 tw-bg-gradient-to-r tw-from-orange-50 tw-to-yellow-50">
      <div class="tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-3">
          <i class="pi pi-exclamation-triangle tw-text-orange-600 tw-text-xl"></i>
          <div>
            <span class="tw-font-semibold tw-text-orange-800">Surplus Items Detected</span>
            <p class="tw-text-sm tw-text-orange-700 tw-mt-1">{{ surplusItems.length }} items with {{ totalSurplusQuantity }} surplus units</p>
          </div>
        </div>
        <Button 
          v-if="bonReception.status === 'pending' && !bonReception.bon_retour_id"
          label="Handle Surplus" 
          icon="pi pi-cog"
          size="small"
          severity="warning"
          outlined
          @click="openSurplusDialog"
          class="tw-rounded-lg tw-font-medium"
        />
      </div>
    </Message>

    <!-- Main Content Card -->
    <Card class="tw-shadow-xl tw-rounded-2xl tw-border-0 tw-overflow-hidden">
      <template #content>
        <TabView>
          <!-- Information Tab -->
          <TabPanel header="Information" class="tw-p-0">
            <div class="tw-p-8">
              <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-8">
                <!-- Left Column -->
                <div class="tw-space-y-6">
                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-hashtag tw-text-blue-500"></i>
                      Reception Code
                    </label>
                    <InputText
                      v-model="form.bonReceptionCode"
                      disabled
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-blue-500"
                    />
                  </div>

                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-shopping-cart tw-text-green-500"></i>
                      Purchase Order
                      <span v-if="isEditing && !form.bon_commend_id" class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      v-model="form.bon_commend_id"
                      :options="bonCommends"
                      optionLabel="bonCommendCode"
                      optionValue="id"
                      placeholder="Select purchase order"
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      :disabled="!isEditing || mode === 'edit'"
                      @change="onBonCommendChange"
                      filter
                    />
                  </div>

                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-building tw-text-purple-500"></i>
                      Supplier <span v-if="isEditing" class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      v-model="form.fournisseur_id"
                      :options="suppliers"
                      optionLabel="company_name"
                      optionValue="id"
                      placeholder="Select supplier"
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      filter
                      :disabled="!isEditing || form.bon_commend_id"
                    />
                  </div>

                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-calendar tw-text-indigo-500"></i>
                      Receipt Date <span v-if="isEditing" class="tw-text-red-500">*</span>
                    </label>
                    <Calendar
                      v-model="form.date_reception"
                      dateFormat="yy-mm-dd"
                      showIcon
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      :disabled="!isEditing"
                    />
                  </div>
                </div>

                <!-- Right Column -->
                <div class="tw-space-y-6">
                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-user tw-text-teal-500"></i>
                      Received By <span v-if="isEditing" class="tw-text-red-500">*</span>
                    </label>
                    <Dropdown
                      v-model="form.received_by"
                      :options="users"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select user"
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      filter
                      :disabled="!isEditing"
                    />
                  </div>

                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-truck tw-text-orange-500"></i>
                      Delivery Note Number
                    </label>
                    <InputText
                      v-model="form.bon_livraison_numero"
                      placeholder="Delivery note number"
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      :disabled="!isEditing"
                    />
                  </div>

                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-file-pdf tw-text-red-500"></i>
                      Invoice Number
                    </label>
                    <InputText
                      v-model="form.facture_numero"
                      placeholder="Invoice number"
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      :disabled="!isEditing"
                    />
                  </div>

                  <div class="tw-space-y-2">
                    <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-box tw-text-cyan-500"></i>
                      Number of Packages
                    </label>
                    <InputNumber
                      v-model="form.nombre_colis"
                      :min="0"
                      class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                      :disabled="!isEditing"
                    />
                  </div>
                </div>

                <!-- Full Width Observations -->
                <div class="lg:tw-col-span-2 tw-space-y-2">
                  <label class="tw-block tw-text-sm tw-font-semibold tw-text-gray-700 tw-flex tw-items-center tw-gap-2">
                    <i class="pi pi-comment tw-text-gray-500"></i>
                    Observations
                  </label>
                  <Textarea
                    v-model="form.observation"
                    rows="4"
                    placeholder="Any observations or notes..."
                    class="tw-w-full tw-rounded-lg tw-border-gray-200 tw-shadow-sm"
                    :disabled="!isEditing"
                  />
                </div>
              </div>
            </div>
          </TabPanel>

      <!-- Items Tab -->
      <TabPanel header="Items" class="tw-p-0">
        <div class="tw-p-8">
          <!-- Header Section -->
          <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4 tw-mb-6">
            <div>
              <h3 class="tw-text-xl tw-font-bold tw-text-gray-900 tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-list tw-text-blue-600"></i>
                Reception Items
              </h3>
              <p class="tw-text-sm tw-text-gray-600 tw-mt-1">{{ form.items.length }} items in this reception</p>
            </div>
            <div class="tw-flex tw-gap-3">
              <Button 
                v-if="isEditing && !form.bon_commend_id"
                icon="pi pi-plus" 
                label="Add Item"
                severity="success"
                outlined
                @click="addItem"
                :disabled="!form.fournisseur_id"
                class="tw-rounded-lg tw-font-medium"
              />
              <Button 
                v-if="form.bon_commend_id && isEditing"
                icon="pi pi-sync" 
                label="Sync with Order"
                severity="info"
                outlined
                @click="syncWithBonCommend"
                class="tw-rounded-lg tw-font-medium"
              />
            </div>
          </div>

          <!-- Items Table -->
          <div class="tw-bg-white tw-rounded-xl tw-shadow-sm tw-border tw-border-gray-200 tw-overflow-hidden">
            <DataTable 
              :value="form.items" 
              responsiveLayout="scroll"
              class="tw-text-sm"
              :scrollable="true"
              :paginator="form.items.length > 10"
              :rows="10"
              paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
            >
              <template #empty>
                <div class="tw-text-center tw-py-12">
                  <i class="pi pi-inbox tw-text-6xl tw-text-gray-300 tw-mb-4"></i>
                  <h4 class="tw-text-lg tw-font-semibold tw-text-gray-600 tw-mb-2">No Items Added</h4>
                  <p class="tw-text-gray-500">Start by adding items to this reception or sync with a purchase order.</p>
                </div>
              </template>

              <Column header="#" class="tw-w-16 tw-bg-gray-50">
                <template #body="slotProps">
                  <span class="tw-font-medium tw-text-gray-600">{{ slotProps.index + 1 }}</span>
                </template>
              </Column>

              <Column header="Product" class="tw-min-w-48">
                <template #body="slotProps">
                  <div v-if="isEditing && !form.bon_commend_id" class="tw-space-y-2">
                    <Dropdown
                      v-model="slotProps.data.product_id"
                      :options="products"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select product"
                      class="tw-w-full tw-rounded-lg"
                      filter
                      @change="onProductChange($event, slotProps.index)"
                    />
                  </div>
                  <div v-else class="tw-space-y-1">
                    <div class="tw-font-semibold tw-text-gray-900">{{ getProductName(slotProps.data.product_id) }}</div>
                    <div class="tw-text-xs tw-text-gray-500 tw-bg-gray-100 tw-px-2 tw-py-1 tw-rounded-full tw-inline-block">
                      {{ getProductCode(slotProps.data.product_id) }}
                    </div>
                  </div>
                </template>
              </Column>

              <Column header="Ordered" class="tw-w-24 tw-bg-gray-50">
                <template #body="slotProps">
                  <div class="tw-text-center">
                    <span class="tw-font-medium tw-text-gray-700">{{ slotProps.data.quantity_ordered || '-' }}</span>
                  </div>
                </template>
              </Column>

              <Column header="Received" class="tw-w-32">
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <InputNumber
                      v-if="isEditing"
                      v-model="slotProps.data.quantity_received"
                      :min="0"
                      class="tw-w-full tw-rounded-lg"
                      @input="calculateVariances(slotProps.index)"
                    />
                    <span v-else class="tw-font-medium tw-text-gray-900">{{ slotProps.data.quantity_received }}</span>
                  </div>
                </template>
              </Column>

              <Column header="Variance" class="tw-w-32">
                <template #body="slotProps">
                  <div v-if="slotProps.data.quantity_surplus > 0" class="tw-flex tw-items-center tw-gap-2">
                    <Tag :value="`+${slotProps.data.quantity_surplus}`" severity="warning" class="tw-rounded-full">
                      <i class="pi pi-plus tw-mr-1"></i>
                    </Tag>
                  </div>
                  <div v-else-if="slotProps.data.quantity_shortage > 0" class="tw-flex tw-items-center tw-gap-2">
                    <Tag :value="`-${slotProps.data.quantity_shortage}`" severity="danger" class="tw-rounded-full">
                      <i class="pi pi-minus tw-mr-1"></i>
                    </Tag>
                  </div>
                  <div v-else class="tw-flex tw-items-center tw-gap-2">
                    <Tag value="OK" severity="success" class="tw-rounded-full">
                      <i class="pi pi-check tw-mr-1"></i>
                    </Tag>
                  </div>
                </template>
              </Column>

              <Column header="Unit Price" class="tw-w-32">
                <template #body="slotProps">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <InputNumber
                      v-if="isEditing"
                      v-model="slotProps.data.unit_price"
                      mode="currency"
                      currency="USD"
                      class="tw-w-full tw-rounded-lg"
                    />
                    <span v-else class="tw-font-medium tw-text-green-600">{{ formatCurrency(slotProps.data.unit_price) }}</span>
                  </div>
                </template>
              </Column>

              <Column header="Status" class="tw-w-32">
                <template #body="slotProps">
                  <Tag :value="getItemStatusLabel(slotProps.data.status)" :severity="getItemStatusSeverity(slotProps.data.status)" class="tw-rounded-full tw-font-medium" />
                </template>
              </Column>

              <Column header="Notes" class="tw-min-w-40">
                <template #body="slotProps">
                  <InputText
                    v-if="isEditing"
                    v-model="slotProps.data.notes"
                    placeholder="Notes"
                    class="tw-w-full tw-rounded-lg"
                  />
                  <span v-else class="tw-text-gray-600">{{ slotProps.data.notes || '-' }}</span>
                </template>
              </Column>

              <Column v-if="isEditing && !form.bon_commend_id" header="" class="tw-w-16">
                <template #body="slotProps">
                  <Button 
                    icon="pi pi-trash" 
                    size="small"
                    outlined
                    severity="danger"
                    @click="removeItem(slotProps.index)"
                    class="tw-rounded-lg"
                  />
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </TabPanel>

      <!-- Summary Tab -->
      <TabPanel header="Summary" class="tw-p-0">
        <div class="tw-p-8">
          <div class="tw-grid tw-grid-cols-1 xl:tw-grid-cols-3 tw-gap-6 tw-mb-8">
            <!-- Reception Summary Card -->
            <Card class="tw-shadow-lg tw-rounded-xl tw-border-0 tw-bg-gradient-to-br tw-from-blue-50 tw-to-indigo-100" title="Reception Summary">
              <div class="tw-space-y-4">
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Total Items:</span>
                  <span class="tw-font-bold tw-text-blue-600 tw-text-lg">{{ form.items.length }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Total Ordered:</span>
                  <span class="tw-font-bold tw-text-gray-900">{{ totalOrdered }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Total Received:</span>
                  <span class="tw-font-bold tw-text-green-600 tw-text-lg">{{ totalReceived }}</span>
                </div>
                
                <Divider class="tw-my-4" />
                
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-orange-50 tw-rounded-lg tw-border tw-border-orange-200">
                  <span class="tw-text-gray-700 tw-font-medium">Total Surplus:</span>
                  <span class="tw-font-bold tw-text-orange-600">
                    {{ totalSurplus > 0 ? `+${totalSurplus}` : '0' }}
                  </span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-red-50 tw-rounded-lg tw-border tw-border-red-200">
                  <span class="tw-text-gray-700 tw-font-medium">Total Shortage:</span>
                  <span class="tw-font-bold tw-text-red-600">
                    {{ totalShortage > 0 ? `-${totalShortage}` : '0' }}
                  </span>
                </div>
              </div>
            </Card>

            <!-- Financial Summary Card -->
            <Card class="tw-shadow-lg tw-rounded-xl tw-border-0 tw-bg-gradient-to-br tw-from-green-50 tw-to-emerald-100" title="Financial Summary">
              <div class="tw-space-y-4">
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Subtotal:</span>
                  <span class="tw-font-semibold tw-text-gray-900">{{ formatCurrency(subtotal) }}</span>
                </div>
                
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Tax:</span>
                  <span class="tw-font-semibold tw-text-gray-900">{{ formatCurrency(0) }}</span>
                </div>
                
                <Divider class="tw-my-4" />
                
                <div class="tw-flex tw-justify-between tw-items-center tw-p-4 tw-bg-green-500 tw-rounded-lg tw-text-white">
                  <span class="tw-font-bold tw-text-lg">Total:</span>
                  <span class="tw-font-bold tw-text-xl">{{ formatCurrency(subtotal) }}</span>
                </div>
              </div>
            </Card>

            <!-- Status Information Card -->
            <Card v-if="bonReception.id" class="tw-shadow-lg tw-rounded-xl tw-border-0 tw-bg-gradient-to-br tw-from-purple-50 tw-to-violet-100" title="Status Information">
              <div class="tw-space-y-4">
                <div class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Status:</span>
                  <Tag :value="getStatusLabel(bonReception.status)" :severity="getStatusSeverity(bonReception.status)" class="tw-rounded-full tw-font-medium" />
                </div>
                
                <div v-if="bonReception.created_at" class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Created:</span>
                  <span class="tw-font-medium tw-text-gray-900">{{ formatDate(bonReception.created_at) }}</span>
                </div>
                
                <div v-if="bonReception.confirmed_at" class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-white tw-rounded-lg tw-shadow-sm">
                  <span class="tw-text-gray-600 tw-font-medium">Confirmed:</span>
                  <span class="tw-font-medium tw-text-green-600">{{ formatDate(bonReception.confirmed_at) }}</span>
                </div>
                
                <div v-if="bonReception.bon_retour_id" class="tw-flex tw-justify-between tw-items-center tw-p-3 tw-bg-orange-50 tw-rounded-lg tw-border tw-border-orange-200">
                  <span class="tw-text-gray-700 tw-font-medium">Return Note:</span>
                  <Button 
                    label="View" 
                    size="small"
                    outlined
                    severity="warning"
                    @click="viewReturn"
                    class="tw-rounded-lg"
                  />
                </div>
              </div>
            </Card>
          </div>

          <!-- Surplus Items Detail -->
          <div v-if="surplusItems.length > 0" class="tw-bg-white tw-rounded-xl tw-shadow-lg tw-border tw-border-orange-200 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-orange-500 tw-to-orange-600 tw-p-4">
              <h4 class="tw-text-white tw-font-bold tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                Surplus Items Detail
              </h4>
            </div>
            <div class="tw-p-6">
              <DataTable :value="surplusItems" class="tw-text-sm tw-border-0">
                <Column field="product.name" header="Product" class="tw-font-medium" />
                <Column field="quantity_ordered" header="Ordered" class="tw-text-center" />
                <Column field="quantity_received" header="Received" class="tw-text-center tw-font-semibold tw-text-green-600" />
                <Column field="quantity_surplus" header="Surplus" class="tw-text-center">
                  <template #body="slotProps">
                    <Tag :value="`+${slotProps.data.quantity_surplus}`" severity="warning" class="tw-rounded-full tw-font-bold">
                      <i class="pi pi-plus tw-mr-1"></i>
                    </Tag>
                  </template>
                </Column>
                <Column header="Value" class="tw-text-right tw-font-semibold tw-text-orange-600">
                  <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.quantity_surplus * slotProps.data.unit_price) }}
                  </template>
                </Column>
              </DataTable>
            </div>
          </div>
        </div>
      </TabPanel>
    </TabView>

    <!-- Enhanced Action Buttons -->
    <div class="tw-mt-8 tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-stretch sm:tw-items-center tw-gap-4 tw-p-6 tw-bg-white tw-rounded-xl tw-shadow-lg tw-border tw-border-gray-200">
      <div class="tw-flex tw-gap-3">
        <Button 
          v-if="bonReception.status === 'pending' && hasSurplusItems && !bonReception.bon_retour_id"
          label="Create Return for Surplus"
          icon="pi pi-reply"
          severity="warning"
          outlined
          @click="createReturnForSurplus"
          :loading="creatingReturn"
          class="tw-rounded-lg tw-font-medium tw-px-4"
        />
      </div>

      <div class="tw-flex tw-gap-3">
        <Button 
          v-if="mode === 'view' && bonReception.status === 'pending'"
          label="Edit Reception"
          icon="pi pi-pencil"
          severity="info"
          outlined
          @click="enableEdit"
          class="tw-rounded-lg tw-font-medium"
        />
        
        <Button 
          v-if="isEditing"
          label="Save Changes"
          icon="pi pi-save"
          severity="success"
          @click="saveReception"
          :loading="saving"
          class="tw-rounded-lg tw-font-medium tw-px-6"
        />
        
        <Button 
          v-if="bonReception.status === 'pending'"
          label="Confirm Reception"
          icon="pi pi-check-circle"
          severity="success"
          @click="confirmReception"
          :loading="confirming"
          class="tw-rounded-lg tw-font-medium tw-px-6"
        />
        
        <Button 
          label="Close"
          icon="pi pi-times"
          outlined
          severity="secondary"
          @click="$emit('cancelled')"
          class="tw-rounded-lg tw-font-medium"
        />
      </div>
    </div>

    <!-- Handle Surplus Dialog -->
    <BonReceptionConfirmDialog
      v-model="showSurplusDialog"
      :bon-reception-id="bonReception.id"
      @confirmed="onSurplusHandled"
    />
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

// PrimeVue Components
import Button from 'primevue/button'
import Card from 'primevue/card'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Calendar from 'primevue/calendar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Tag from 'primevue/tag'

// Custom Components
import BonReceptionConfirmDialog from '../../../../Components/Apps/Purchasing/BonReception/BonReceptionConfirmDialog.vue'

const props = defineProps({
  bonReceptionId: {
    type: Number,
    required: true
  },
  mode: {
    type: String,
    default: 'view',
    validator: (value) => ['view', 'edit', 'create'].includes(value)
  }
})

const emit = defineEmits(['saved', 'cancelled'])

const router = useRouter()
const toast = useToast()

// Data
const bonReception = ref({})
const suppliers = ref([])
const products = ref([])
const bonCommends = ref([])
const users = ref([])
const isEditing = ref(props.mode !== 'view')
const showSurplusDialog = ref(false)

// Form
const form = ref({
  bonReceptionCode: '',
  bon_commend_id: null,
  fournisseur_id: null,
  date_reception: new Date(),
  received_by: null,
  bon_livraison_numero: '',
  facture_numero: '',
  nombre_colis: 0,
  observation: '',
  items: []
})

// Loading states
const loading = ref(false)
const saving = ref(false)
const confirming = ref(false)
const creatingReturn = ref(false)

// Computed
const hasSurplusItems = computed(() => surplusItems.value.length > 0)

const surplusItems = computed(() => {
  return form.value.items.filter(item => item.quantity_surplus > 0)
})

const totalSurplusQuantity = computed(() => {
  return surplusItems.value.reduce((sum, item) => sum + item.quantity_surplus, 0)
})

const totalOrdered = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_ordered || 0), 0)
})

const totalReceived = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_received || 0), 0)
})

const totalSurplus = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_surplus || 0), 0)
})

const totalShortage = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity_shortage || 0), 0)
})

const subtotal = computed(() => {
  return form.value.items.reduce((sum, item) => {
    return sum + ((item.quantity_received || 0) * (item.unit_price || 0))
  }, 0)
})

// Methods
const loadBonReception = async () => {
  try {
    loading.value = true
    const response = await axios.get(`/api/bon-receptions/${props.bonReceptionId}`)
    
    if (response.data.status === 'success') {
      bonReception.value = response.data.data
      
      // Populate form
      form.value = {
        bonReceptionCode: bonReception.value.bonReceptionCode || '',
        bon_commend_id: bonReception.value.bon_commend_id,
        fournisseur_id: bonReception.value.fournisseur_id,
        date_reception: new Date(bonReception.value.date_reception),
        received_by: bonReception.value.received_by,
        bon_livraison_numero: bonReception.value.bon_livraison_numero || '',
        facture_numero: bonReception.value.facture_numero || '',
        nombre_colis: bonReception.value.nombre_colis || 0,
        observation: bonReception.value.observation || '',
        items: bonReception.value.items || []
      }
    }
  } catch (err) {
    console.error('Error loading bon reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load reception'
    })
  } finally {
    loading.value = false
  }
}

// Load reference data
const loadSuppliers = async () => {
  try {
    const response = await axios.get('/api/fournisseurs')
    if (response.data.status === 'success') {
      suppliers.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading suppliers:', err)
  }
}

const loadProducts = async () => {
  try {
    const response = await axios.get('/api/products')
    if (response.data.status === 'success') {
      products.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading products:', err)
  }
}

const loadBonCommends = async () => {
  try {
    const response = await axios.get('/api/bon-receptions/meta/bon-commends')
    if (response.data.status === 'success') {
      bonCommends.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading bon commends:', err)
  }
}

const loadUsers = async () => {
  try {
    const response = await axios.get('/api/users')
    if (response.data.status === 'success') {
      users.value = response.data.data
    }
  } catch (err) {
    console.error('Error loading users:', err)
  }
}

const enableEdit = () => {
  isEditing.value = true
}

const addItem = () => {
  form.value.items.push({
    product_id: null,
    quantity_ordered: 0,
    quantity_received: 0,
    quantity_surplus: 0,
    quantity_shortage: 0,
    unit_price: 0,
    status: 'pending',
    notes: ''
  })
}

const removeItem = (index) => {
  form.value.items.splice(index, 1)
}

const onProductChange = (productId, index) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    form.value.items[index].unit_price = product.purchase_price || 0
  }
}

const onBonCommendChange = async () => {
  if (!form.value.bon_commend_id) return
  
  const bonCommend = bonCommends.value.find(bc => bc.id === form.value.bon_commend_id)
  if (bonCommend) {
    form.value.fournisseur_id = bonCommend.fournisseur_id
    await syncWithBonCommend()
  }
}

const syncWithBonCommend = async () => {
  if (!form.value.bon_commend_id) return
  
  try {
    const response = await axios.get(`/api/bon-commends/${form.value.bon_commend_id}`)
    
    if (response.data.status === 'success') {
      const bonCommend = response.data.data
      
      // Map bon commend items to reception items
      form.value.items = bonCommend.items.map(item => ({
        bon_commend_item_id: item.id,
        product_id: item.product_id,
        quantity_ordered: item.quantity_desired,
        quantity_received: item.quantity_desired, // Default to ordered
        quantity_surplus: 0,
        quantity_shortage: 0,
        unit: item.unit,
        unit_price: item.price,
        status: 'pending',
        notes: item.notes || ''
      }))
    }
  } catch (err) {
    console.error('Error syncing with bon commend:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to sync with purchase order'
    })
  }
}

const calculateVariances = (index) => {
  const item = form.value.items[index]
  const ordered = item.quantity_ordered || 0
  const received = item.quantity_received || 0
  
  item.quantity_surplus = Math.max(0, received - ordered)
  item.quantity_shortage = Math.max(0, ordered - received)
  
  // Update status
  if (received === ordered && ordered > 0) {
    item.status = 'received'
  } else if (received > 0 && received < ordered) {
    item.status = 'partial'
  } else if (item.quantity_surplus > 0) {
    item.status = 'excess'
  } else if (item.quantity_shortage > 0) {
    item.status = 'missing'
  } else {
    item.status = 'pending'
  }
}

const saveReception = async () => {
  try {
    saving.value = true
    
    // Prepare data for submission - only include id for existing items
    const data = { 
      ...form.value,
      items: form.value.items.map(item => {
        // Create a clean copy of the item
        const cleanItem = {
          product_id: item.product_id,
          quantity_ordered: item.quantity_ordered || 0,
          quantity_received: item.quantity_received || 0,
          quantity_surplus: item.quantity_surplus || 0,
          quantity_shortage: item.quantity_shortage || 0,
          unit_price: item.unit_price || 0,
          status: item.status || 'pending',
          notes: item.notes || '',
          unit: item.unit || null
        }
        
        // Only include bon_commend_item_id if it exists
        if (item.bon_commend_item_id) {
          cleanItem.bon_commend_item_id = item.bon_commend_item_id
        }
        
        // Only include id if it exists and is a valid number
        if (item.id && typeof item.id === 'number' && item.id > 0) {
          cleanItem.id = item.id
        }
        
        return cleanItem
      })
    }
    
    const response = await axios.put(`/api/bon-receptions/${props.bonReceptionId}`, data)
    
    if (response.data.status === 'success') {
      // Reload the data to get updated IDs
      await loadBonReception()
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reception saved successfully'
      })
      emit('saved')
    }
  } catch (err) {
    console.error('Error saving reception:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to save reception'
    })
  } finally {
    saving.value = false
  }
}

const confirmReception = () => {
  showSurplusDialog.value = true
}

const onSurplusHandled = async (data) => {
  showSurplusDialog.value = false
  
  try {
    // Reload the reception data to get updated status
    await loadBonReception()
    
    // Disable editing after confirmation
    isEditing.value = false
    
    if (data.bonRetour) {
      bonReception.value.bon_retour_id = data.bonRetour.id
      toast.add({
        severity: 'success',
        summary: 'Reception Confirmed',
        detail: `Return note ${data.bonRetour.bon_retour_code} created for surplus items`,
        life: 5000
      })
    } else {
      toast.add({
        severity: 'success',
        summary: 'Reception Confirmed',
        detail: 'Reception confirmed successfully'
      })
    }
    
    emit('saved')
  } catch (err) {
    console.error('Error in onSurplusHandled:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to complete confirmation'
    })
  }
}

const createReturnForSurplus = async () => {
  try {
    creatingReturn.value = true
    
    const response = await axios.post(`/api/bon-receptions/${bonReception.value.id}/confirm`, {
      surplus_action: 'return',
      return_notes: 'Manual return creation for surplus items'
    })
    
    if (response.data.status === 'success' && response.data.data.bonRetour) {
      bonReception.value.bon_retour_id = response.data.data.bonRetour.id
      
      toast.add({
        severity: 'success',
        summary: 'Return Created',
        detail: `Return note ${response.data.data.bonRetour.bon_retour_code} created successfully`,
        life: 5000
      })
      
      emit('saved')
    }
  } catch (err) {
    console.error('Error creating return:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to create return'
    })
  } finally {
    creatingReturn.value = false
  }
}

const viewReturn = () => {
  if (bonReception.value.bon_retour_id) {
    router.push(`/purchasing/bon-retours/${bonReception.value.bon_retour_id}`)
  }
}

const openSurplusDialog = () => {
  showSurplusDialog.value = true
}

// Utility functions
const getProductName = (productId) => {
  const product = products.value.find(p => p.id === productId)
  return product ? product.name : 'Unknown Product'
}

const getProductCode = (productId) => {
  const product = products.value.find(p => p.id === productId)
  return product ? product.code : ''
}

const getItemStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    received: 'Received',
    partial: 'Partial',
    excess: 'Excess',
    missing: 'Missing'
  }
  return labels[status] || status
}

const getItemStatusSeverity = (status) => {
  const severities = {
    pending: 'secondary',
    received: 'success',
    partial: 'warning',
    excess: 'warning',
    missing: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusIcon = (status) => {
  const icons = {
    pending: 'pi pi-clock',
    completed: 'pi pi-check-circle',
    canceled: 'pi pi-times-circle',
    rejected: 'pi pi-exclamation-triangle'
  }
  return icons[status] || 'pi pi-info-circle'
}

const getStatusSeverity = (status) => {
  const severities = {
    pending: 'info',
    completed: 'success',
    canceled: 'warning',
    rejected: 'danger'
  }
  return severities[status] || 'info'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending',
    completed: 'Completed',
    canceled: 'Canceled',
    rejected: 'Rejected'
  }
  return labels[status] || status
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

// Lifecycle
// Watcher to disable editing when reception is confirmed
watch(() => bonReception.value.status, (newStatus) => {
  if (newStatus === 'completed') {
    isEditing.value = false
  }
})

onMounted(async () => {
  await Promise.all([
    loadSuppliers(),
    loadProducts(),
    loadBonCommends(),
    loadUsers(),
    loadBonReception()
  ])
})
</script>

<style scoped>
:deep(.p-card) {
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  border: 0;
}

:deep(.p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}
</style>
