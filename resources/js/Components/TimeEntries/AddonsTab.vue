<template>
  <div>
    <!-- Header with Actions -->
    <div class="bg-white shadow rounded-lg mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Ticket Addons</h3>
            <p class="mt-1 text-sm text-gray-500">
              Manage additional services and items added to tickets
            </p>
          </div>
          <button
            @click="showCreateAddonModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
            Add Addon
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="px-6 py-4 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="filters.status"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select
              v-model="filters.category"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
              <option value="">All Categories</option>
              <option value="product">Product</option>
              <option value="service">Service</option>
              <option value="expense">Expense</option>
              <option value="license">License</option>
              <option value="hardware">Hardware</option>
              <option value="software">Software</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Ticket</label>
            <UnifiedSelector
              v-model="filters.ticketId"
              type="ticket"
              placeholder="All tickets..."
              :clearable="true"
              class="mt-1"
            />
          </div>

          <div class="flex items-end">
            <button
              @click="clearFilters"
              class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Addons List -->
    <div class="bg-white shadow rounded-lg">
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>
      
      <div v-else-if="addons.length === 0" class="text-center py-12">
        <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No addons found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ Object.values(filters).some(f => f) ? 'Try adjusting your filters' : 'Get started by creating your first addon' }}
        </p>
      </div>

      <div v-else>
        <!-- Addons Table -->
        <div class="overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Item
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ticket
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Category
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Amount
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Added By
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th class="relative px-6 py-3">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="addon in addons" :key="addon.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ addon.name }}</div>
                    <div v-if="addon.description" class="text-sm text-gray-500">{{ addon.description }}</div>
                    <div class="text-xs text-gray-400">
                      {{ addon.quantity }} × ${{ addon.unit_price }} 
                      <span v-if="addon.discount_amount > 0">(-${{ addon.discount_amount }})</span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="addon.ticket" class="text-sm">
                    <div class="font-medium text-indigo-600">{{ addon.ticket.ticket_number }}</div>
                    <div class="text-gray-500">{{ addon.ticket.title }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getCategoryBadgeClass(addon.category)">
                    {{ addon.category }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  ${{ addon.total_amount }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="getStatusBadgeClass(addon.status)">
                    {{ addon.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ addon.added_by?.name || 'Unknown' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(addon.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center space-x-2">
                    <button
                      @click="editAddon(addon)"
                      class="text-indigo-600 hover:text-indigo-900"
                      title="Edit addon"
                    >
                      <PencilIcon class="h-4 w-4" />
                    </button>
                    <button
                      v-if="addon.status === 'pending'"
                      @click="approveAddon(addon)"
                      class="text-green-600 hover:text-green-900"
                      title="Approve addon"
                    >
                      <CheckIcon class="h-4 w-4" />
                    </button>
                    <button
                      v-if="addon.status === 'pending'"
                      @click="rejectAddon(addon)"
                      class="text-red-600 hover:text-red-900"
                      title="Reject addon"
                    >
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                    <button
                      v-if="addon.status === 'approved' && addon.can_unapprove"
                      @click="unapproveAddon(addon)"
                      class="text-yellow-600 hover:text-yellow-900"
                      title="Unapprove addon"
                    >
                      <ArrowPathIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <button
                @click="loadPage(pagination.current_page - 1)"
                :disabled="!pagination.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Previous
              </button>
              <button
                @click="loadPage(pagination.current_page + 1)"
                :disabled="!pagination.next_page_url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
              >
                Next
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  <button
                    @click="loadPage(pagination.current_page - 1)"
                    :disabled="!pagination.prev_page_url"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  >
                    Previous
                  </button>
                  <button
                    @click="loadPage(pagination.current_page + 1)"
                    :disabled="!pagination.next_page_url"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  >
                    Next
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Addon Modal -->
    <AddonModal
      :show="showCreateAddonModal || showEditAddonModal"
      :addon="addonToEdit"
      :mode="showEditAddonModal ? 'edit' : 'create'"
      @close="closeAddonModal"
      @saved="onAddonSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { 
  PlusIcon, 
  PencilIcon, 
  CheckIcon, 
  XMarkIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'
import UnifiedSelector from '@/Components/UI/UnifiedSelector.vue'
import AddonModal from '@/Components/TimeEntries/AddonModal.vue'

// Data
const loading = ref(false)
const addons = ref([])
const pagination = ref({})
const showCreateAddonModal = ref(false)
const showEditAddonModal = ref(false)
const addonToEdit = ref(null)

// Filters
const filters = reactive({
  status: '',
  category: '',
  ticketId: ''
})

// Methods
const loadAddons = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      ...Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => value !== '')
      )
    })

    const response = await fetch(`/api/ticket-addons?${params}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      }
    })

    if (response.ok) {
      const data = await response.json()
      addons.value = data.data.data || data.data
      pagination.value = {
        current_page: data.data.current_page || 1,
        last_page: data.data.last_page || 1,
        per_page: data.data.per_page || 20,
        total: data.data.total || 0,
        from: data.data.from || 0,
        to: data.data.to || 0,
        prev_page_url: data.data.prev_page_url,
        next_page_url: data.data.next_page_url
      }
    }
  } catch (error) {
    console.error('Error loading addons:', error)
  } finally {
    loading.value = false
  }
}

const loadPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadAddons(page)
  }
}

const clearFilters = () => {
  filters.status = ''
  filters.category = ''
  filters.ticketId = ''
}

const editAddon = (addon) => {
  addonToEdit.value = addon
  showEditAddonModal.value = true
}

const closeAddonModal = () => {
  showCreateAddonModal.value = false
  showEditAddonModal.value = false
  addonToEdit.value = null
}

const onAddonSaved = () => {
  closeAddonModal()
  loadAddons(pagination.value.current_page)
}

const approveAddon = async (addon) => {
  try {
    const response = await fetch(`/api/ticket-addons/${addon.id}/approve`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      }
    })

    if (response.ok) {
      loadAddons(pagination.value.current_page)
    }
  } catch (error) {
    console.error('Error approving addon:', error)
  }
}

const rejectAddon = async (addon) => {
  try {
    const response = await fetch(`/api/ticket-addons/${addon.id}/reject`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      }
    })

    if (response.ok) {
      loadAddons(pagination.value.current_page)
    }
  } catch (error) {
    console.error('Error rejecting addon:', error)
  }
}

const unapproveAddon = async (addon) => {
  try {
    const response = await fetch(`/api/ticket-addons/${addon.id}/unapprove`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      }
    })

    if (response.ok) {
      loadAddons(pagination.value.current_page)
    }
  } catch (error) {
    console.error('Error unapproving addon:', error)
  }
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getCategoryBadgeClass = (category) => {
  const classes = {
    product: 'bg-blue-100 text-blue-800',
    service: 'bg-purple-100 text-purple-800',
    expense: 'bg-orange-100 text-orange-800',
    license: 'bg-indigo-100 text-indigo-800',
    hardware: 'bg-gray-100 text-gray-800',
    software: 'bg-cyan-100 text-cyan-800',
    other: 'bg-pink-100 text-pink-800'
  }
  return classes[category] || 'bg-gray-100 text-gray-800'
}

// Watchers
watch(filters, () => {
  loadAddons(1) // Reset to first page when filters change
}, { deep: true })

// Lifecycle
onMounted(() => {
  loadAddons()
})
</script>