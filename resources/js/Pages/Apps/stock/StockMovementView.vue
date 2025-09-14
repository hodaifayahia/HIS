<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-br tw-from-blue-50 tw-via-indigo-50 tw-to-purple-50 tw-p-6">
    <Card class="tw-mb-8 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-700 tw-text-white tw-shadow-xl">
      <template #content>
        <div class="tw-flex tw-justify-between tw-items-center">
          <div>
            <h1 class="tw-text-4xl tw-font-bold tw-mb-2">Stock Movement Details</h1>
            <p class="tw-text-blue-100 tw-flex tw-items-center tw-gap-2">
              <i class="pi pi-hashtag"></i>
              {{ movement?.movement_number || 'Loading...' }}
            </p>
          </div>
          <div class="tw-flex tw-space-x-3">
            <Button
              @click="$router.go(-1)"
              icon="pi pi-arrow-left"
              severity="secondary"
              outlined
              class="tw-bg-white tw-rounded-xl tw-text-blue-600 hover:tw-bg-blue-50 tw-px-4 tw-py-2 tw-text-nowrap"
              v-tooltip.top="'Back to Requests'"
            />
          </div>
        </div>
      </template>
    </Card>

    <div v-if="loading" class="tw-flex tw-items-center tw-justify-center tw-min-h-96">
      <div class="tw-text-center">
        <div class="tw-relative">
          <div class="tw-w-32 tw-h-32 tw-bg-gradient-to-r tw-from-blue-500 tw-to-purple-600 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6 tw-shadow-2xl">
            <i class="pi pi-clipboard tw-text-white tw-text-5xl"></i>
          </div>
          <div class="tw-absolute tw-inset-0 tw-w-32 tw-h-32 tw-border-4 tw-border-white/30 tw-border-t-white tw-rounded-full tw-mx-auto tw-animate-spin"></div>
        </div>
        <h3 class="tw-text-xl tw-font-semibold tw-text-gray-900 tw-mb-2">Loading Request Details</h3>
        <p class="tw-text-gray-600">Please wait while we fetch your data...</p>
      </div>
    </div>

    <div v-else class="tw-max-w-9xl tw-mx-auto tw-px-6 tw-py-8">
      <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-4 tw-gap-6 tw-mb-8">
        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Total Products</p>
              <p class="tw-text-3xl tw-font-bold tw-text-gray-900">{{ movement?.items?.length || 0 }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-blue-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-box tw-text-blue-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Status</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900 tw-capitalize">{{ movement?.status || 'Unknown' }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-green-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-info-circle tw-text-green-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Created</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">{{ formatDate(movement?.created_at) }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-purple-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-calendar tw-text-purple-600 tw-text-xl"></i>
            </div>
          </div>
        </div>

        <div class="tw-bg-white tw-rounded-2xl tw-p-6 tw-shadow-lg tw-border tw-border-gray-100 hover:tw-shadow-xl tw-transition-all tw-duration-300">
          <div class="tw-flex tw-items-center tw-justify-between">
            <div>
              <p class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-1">Last Updated</p>
              <p class="tw-text-lg tw-font-bold tw-text-gray-900">{{ formatDate(movement?.updated_at) }}</p>
            </div>
            <div class="tw-w-12 tw-h-12 tw-bg-orange-100 tw-rounded-xl tw-flex tw-items-center tw-justify-center">
              <i class="pi pi-clock tw-text-orange-600 tw-text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="tw-grid tw-grid-cols-1 tw-xl:grid-cols-3 tw-gap-8">
        <div class="tw-xl:col-span-1">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-blue-500 tw-to-blue-600 tw-p-6 tw-text-white">
              <h3 class="tw-text-xl tw-font-bold tw-flex tw-items-center tw-gap-2">
                <i class="pi pi-info-circle"></i>
                Request Details
              </h3>
            </div>

            <div class="tw-p-6 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-4">
              <div class="tw-group tw-bg-gradient-to-r tw-from-blue-50 tw-to-blue-100 tw-rounded-xl tw-p-3 tw-border tw-border-blue-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-blue-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-building tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-blue-800 tw-mb-1">Requesting Service</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.requesting_service?.name }}</dd>
                  </div>
                </div>
              </div>

              <div class="tw-group tw-bg-gradient-to-r tw-from-green-50 tw-to-green-100 tw-rounded-xl tw-p-3 tw-border tw-border-green-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-green-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-building tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-green-800 tw-mb-1">Providing Service</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.providing_service?.name }}</dd>
                  </div>
                </div>
              </div>

              <div class="tw-group tw-bg-gradient-to-r tw-from-purple-50 tw-to-purple-100 tw-rounded-xl tw-p-3 tw-border tw-border-purple-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-purple-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-user tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-purple-800 tw-mb-1">Requested By</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ movement.requesting_user?.name }}</dd>
                    <dd class="tw-text-xs tw-text-gray-600">{{ movement.requesting_user?.email }}</dd>
                  </div>
                </div>
              </div>

              <div class="tw-group tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100 tw-rounded-xl tw-p-3 tw-border tw-border-gray-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-gray-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-info-circle tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-gray-800 tw-mb-1">Status</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium tw-capitalize">{{ movement.status }}</dd>
                  </div>
                </div>
              </div>

              <div v-if="movement.request_reason" class="tw-group tw-bg-gradient-to-r tw-from-orange-50 tw-to-orange-100 tw-rounded-xl tw-p-3 tw-border tw-border-orange-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-orange-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-question-circle tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-orange-800 tw-mb-1">Reason</dt>
                    <dd class="tw-text-sm tw-text-gray-900">{{ movement.request_reason }}</dd>
                  </div>
                </div>
              </div>

              <div v-if="movement.expected_delivery_date" class="tw-group tw-bg-gradient-to-r tw-from-indigo-50 tw-to-indigo-100 tw-rounded-xl tw-p-3 tw-border tw-border-indigo-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-indigo-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-calendar tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-indigo-800 tw-mb-1">Expected Delivery</dt>
                    <dd class="tw-text-sm tw-text-gray-900 tw-font-medium">{{ formatDate(movement.expected_delivery_date) }}</dd>
                  </div>
                </div>
              </div>

              <div v-if="movement.approval_notes" class="tw-group tw-bg-gradient-to-r tw-from-red-50 tw-to-red-100 tw-rounded-xl tw-p-3 tw-border tw-border-red-200 hover:tw-shadow-md tw-transition-all tw-duration-300">
                <div class="tw-flex tw-items-start tw-gap-3">
                  <div class="tw-w-8 tw-h-8 tw-bg-red-500 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-group-hover:tw-scale-110 tw-transition-transform tw-duration-300">
                    <i class="pi pi-comment tw-text-white tw-text-sm"></i>
                  </div>
                  <div class="tw-flex-1">
                    <dt class="tw-text-xs tw-font-semibold tw-text-red-800 tw-mb-1">Approval Notes</dt>
                    <dd class="tw-text-sm tw-text-gray-900">{{ movement.approval_notes }}</dd>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tw-xl:col-span-2">
          <div class="tw-bg-white tw-rounded-2xl tw-shadow-lg tw-border tw-border-gray-100 tw-overflow-hidden">
            <div class="tw-bg-gradient-to-r tw-from-slate-600 tw-to-slate-700 tw-p-6 tw-text-white">
              <div class="tw-flex tw-items-center tw-justify-between">
                <h3 class="tw-text-xl tw-font-bold tw-flex tw-items-center tw-gap-2">
                  <i class="pi pi-box"></i>
                  Requested Products
                  <span class="tw-text-sm tw-font-normal tw-text-slate-300">({{ movement.items?.length || 0 }} items)</span>
                </h3>
              </div>
            </div>

            <div class="tw-p-6">
              <div v-if="movement.items && movement.items.length > 0" class="tw-overflow-hidden tw-bg-white tw-rounded-xl tw-border tw-border-gray-200 tw-shadow-sm">
                <div class="tw-bg-gradient-to-r tw-from-slate-50 tw-to-slate-100 tw-px-6 tw-py-4 tw-border-b tw-border-gray-200">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <h4 class="tw-text-lg tw-font-semibold tw-text-slate-800 tw-flex tw-items-center tw-gap-2">
                      <i class="pi pi-list tw-text-slate-600"></i>
                      Products List
                    </h4>
                    <div class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-slate-600">
                      <span class="tw-flex tw-items-center tw-gap-1">
                        <i class="pi pi-info-circle"></i>
                        {{ filteredProducts.length }} of {{ movement.items.length }} products
                      </span>
                    </div>
                  </div>
                </div>

                <div class="tw-overflow-x-auto">
                  <table class="tw-w-full">
                    <thead class="tw-bg-gradient-to-r tw-from-gray-50 tw-to-gray-100">
                      <tr>
                        <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200">
                          Product
                        </th>
                        <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200">
                          Requested Quantity
                        </th>
                        <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200">
                          Unit
                        </th>
                        <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200">
                          Status
                        </th>
                        <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200">
                          Notes
                        </th>
                        <th class="tw-px-6 tw-py-4 tw-text-left tw-text-xs tw-font-semibold tw-text-gray-600 tw-uppercase tw-tracking-wider tw-border-b tw-border-gray-200">
                          Actions
                        </th>
                      </tr>
                    </thead>

                    <tbody class="tw-divide-y tw-divide-gray-200">
                      <tr
                        v-for="item in paginatedProducts"
                        :key="item.id"
                        class="tw-hover:bg-blue-50/30 tw-transition-colors tw-duration-200"
                      >
                        <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                          <div class="tw-flex tw-items-center tw-gap-3">
                            <div class="tw-w-10 tw-h-10 tw-bg-gradient-to-br tw-from-blue-500 tw-to-blue-600 tw-rounded-lg tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-shadow-sm">
                              <i class="pi pi-box tw-text-white tw-text-sm"></i>
                            </div>
                            <div class="tw-min-w-0 tw-flex-1">
                              <div class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-truncate">
                                {{ item.product?.name }}
                              </div>
                              <div class="tw-text-xs tw-text-gray-500 tw-flex tw-items-center tw-gap-2">
                                <span class="tw-flex tw-items-center tw-gap-1">
                                  <i class="pi pi-tag tw-text-xs"></i>
                                  {{ item.product?.code }}
                                </span>
                                <span class="tw-text-gray-300">•</span>
                                <span class="tw-flex tw-items-center tw-gap-1">
                                  <i class="pi pi-map-marker tw-text-xs"></i>
                                  {{ item.product?.stockage_name || 'Main Storage' }}
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>

                        <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                          <div class="tw-flex tw-items-center tw-gap-3">
                            <div class="tw-text-sm tw-font-medium tw-text-gray-900">
                              <!-- Corrected calculation for requested quantity -->
                              {{ getCalculatedQuantity(item) }} {{ getProductUnit(item.product) }}
                            </div>
                            <div v-if="item.quantity_by_box && item.product?.boite_de" class="tw-text-xs tw-text-blue-600 tw-font-medium">
                              ({{ item.requested_quantity }} boxes)
                            </div>
                          </div>
                        </td>

                        <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                          <div class="tw-flex tw-flex-col">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-900">
                              {{ item.quantity_by_box && item.product?.boite_de ? 'Boxes' : (item.product?.unit || 'units') }}
                            </span>
                            <span v-if="item.duration" class="tw-text-xs tw-text-purple-600 tw-font-medium tw-bg-purple-50 tw-px-2 tw-py-1 tw-rounded-full tw-inline-block tw-mt-1">
                              {{ item.duration }} {{ item.duration_unit }}
                            </span>
                          </div>
                        </td>

                        <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                          <div class="tw-flex tw-items-center tw-gap-2">
                            <div v-if="item.approved_quantity !== null && item.approved_quantity !== undefined" class="tw-flex tw-items-center tw-gap-2">
                              <i class="pi pi-check-circle tw-text-green-600"></i>
                              <span class="tw-text-sm tw-text-green-600 tw-font-medium">
                                Approved: {{ item.approved_quantity }}
                              </span>
                            </div>
                            <div v-if="item.executed_quantity !== null && item.executed_quantity !== undefined" class="tw-flex tw-items-center tw-gap-2">
                              <i class="pi pi-check tw-text-blue-600"></i>
                              <span class="tw-text-sm tw-text-blue-600 tw-font-medium">
                                Executed: {{ item.executed_quantity }}
                              </span>
                            </div>
                            <div v-if="item.approved_quantity === null && item.executed_quantity === null" class="tw-flex tw-items-center tw-gap-2">
                              <i class="pi pi-clock tw-text-gray-500"></i>
                              <span class="tw-text-sm tw-text-gray-500">Pending</span>
                            </div>
                          </div>
                        </td>

                        <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                          <div class="tw-text-sm tw-text-gray-600 tw-max-w-xs tw-truncate">
                            {{ item.notes || 'No notes' }}
                          </div>
                        </td>

                        <td class="tw-px-6 tw-py-4 tw-whitespace-nowrap">
                          <div class="tw-flex tw-items-center tw-space-x-2">
                            <Button
                              @click="openProductSelection(item)"
                              label="Select Products"
                              icon="pi pi-plus"
                              size="small"
                              class="tw-bg-blue-600 hover:tw-bg-blue-700 tw-text-white tw-px-3 tw-py-1 tw-text-xs"
                              v-tooltip="'Select specific inventory items to provide'"
                            />
                            <Button
                              v-if="item.selected_inventory && item.selected_inventory.length > 0"
                              @click="viewSelectedProducts(item)"
                              icon="pi pi-eye"
                              severity="secondary"
                              text
                              size="small"
                              v-tooltip="'View selected products'"
                            />
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-if="totalPages > 1" class="tw-bg-gray-50 tw-px-6 tw-py-4 tw-border-t tw-border-gray-200">
                  <div class="tw-flex tw-items-center tw-justify-between">
                    <div class="tw-text-sm tw-text-gray-600">
                      Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize, filteredProducts.length) }} of {{ filteredProducts.length }} products
                    </div>
                    <div class="tw-flex tw-items-center tw-gap-2">
                      <Button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        icon="pi pi-chevron-left"
                        severity="secondary"
                        text
                        class="tw-p-2"
                        size="small"
                      />
                      <div class="tw-flex tw-items-center tw-gap-1">
                        <Button
                          v-for="page in visiblePages"
                          :key="page"
                          @click="goToPage(page)"
                          :class="page === currentPage ? 'tw-bg-blue-500 tw-text-white' : 'tw-bg-white tw-text-gray-600 tw-border tw-border-gray-300'"
                          class="tw-w-8 tw-h-8 tw-rounded-lg tw-text-sm tw-font-medium hover:tw-bg-blue-500 hover:tw-text-white tw-transition-all tw-duration-200"
                          text
                        >
                          {{ page }}
                        </Button>
                      </div>
                      <Button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        icon="pi pi-chevron-right"
                        severity="secondary"
                        text
                        class="tw-p-2"
                        size="small"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="tw-text-center tw-py-16">
                <div class="tw-relative">
                  <div class="tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-gray-100 tw-to-gray-200 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-mx-auto tw-mb-6 tw-shadow-lg">
                    <i class="pi pi-box tw-text-4xl tw-text-gray-400"></i>
                  </div>
                  <div class="tw-absolute tw-inset-0 tw-w-24 tw-h-24 tw-bg-gradient-to-br tw-from-blue-400 tw-to-purple-500 tw-rounded-full tw-opacity-20 tw-animate-pulse"></div>
                </div>
                <h3 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-2">No products found</h3>
                <p class="tw-text-gray-600 tw-max-w-md tw-mx-auto">This request doesn't have any products associated with it.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ProductSelectionDialog
    v-model:visible="showProductSelectionDialog"
    :selected-item="selectedItemForSelection"
    :movement-id="movementId"
    @selection-saved="onSelectionSaved"
  />
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import ProductSelectionDialog from './ProductSelectionDialog.vue'

export default {
  name: 'StockMovementView',
  components: {
    Button,
    Card,
    ProductSelectionDialog
  },
  props: {
    movementId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const route = useRoute()
    const router = useRouter()

    // Reactive data
    const movement = ref({})
    const loading = ref(true)
    const currentPage = ref(1)
    const pageSize = ref(10)
    const showProductSelectionDialog = ref(false)
    const selectedItemForSelection = ref(null)

    // Computed
    const filteredProducts = computed(() => {
      return movement.value?.items || []
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredProducts.value.length / pageSize.value)
    })

    const paginatedProducts = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      const end = start + pageSize.value
      return filteredProducts.value.slice(start, end)
    })

    const visiblePages = computed(() => {
      const total = totalPages.value
      const current = currentPage.value
      const maxVisible = 5

      if (total <= maxVisible) {
        return Array.from({ length: total }, (_, i) => i + 1)
      }

      const start = Math.max(1, current - Math.floor(maxVisible / 2))
      const end = Math.min(total, start + maxVisible - 1)

      return Array.from({ length: end - start + 1 }, (_, i) => start + i)
    })

    // Methods
    const loadMovement = async () => {
      try {
        loading.value = true
        const response = await axios.get(`/api/stock-movements/${props.movementId}`)
        movement.value = response.data.data
      } catch (error) {
        console.error('Error loading movement:', error)
      } finally {
        loading.value = false
      }
    }

    const getCalculatedQuantity = (item) => {
      if (!item) return 0
      
      const baseQuantity = item.requested_quantity || 0
      
      // If quantity_by_box is true, multiply by boite_de to get the actual unit quantity
      if (item.quantity_by_box && item.product?.boite_de) {
        return baseQuantity * item.product.boite_de
      }
      
      return baseQuantity
    }

    const getProductUnit = (product) => {
      if (!product) return 'units'
      return product.unit || product.forme || 'units'
    }

    const formatDate = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString()
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const openProductSelection = (item) => {
      selectedItemForSelection.value = item
      showProductSelectionDialog.value = true
    }

    const viewSelectedProducts = (item) => {
      // TODO: Implement view selected products functionality
      console.log('View selected products for item:', item)
    }

    const onSelectionSaved = async (data) => {
      // Refresh the movement data to show updated selections
      await loadMovement()
    }

    // Lifecycle
    onMounted(async () => {
      await loadMovement()
    })

    return {
      movement,
      loading,
      currentPage,
      pageSize,
      showProductSelectionDialog,
      selectedItemForSelection,
      filteredProducts,
      totalPages,
      paginatedProducts,
      visiblePages,
      loadMovement,
      getProductUnit,
      formatDate,
      goToPage,
      openProductSelection,
      viewSelectedProducts,
      onSelectionSaved,
      getCalculatedQuantity
    }
  }
}
</script>
<style scoped>
.shadow-xl {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>
