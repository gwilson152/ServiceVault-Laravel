<template>
  <div class="space-y-6">
    <!-- Header with Add Button -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">Ticket Add-ons</h3>
      <button
        v-if="canAddAddon"
        @click="showAddAddonModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <span>Add Add-on</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="flex items-center space-x-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select 
          v-model="filters.status" 
          @change="loadAddons"
          class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
        <select 
          v-model="filters.type" 
          @change="loadAddons"
          class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">All Types</option>
          <option value="additional_work">Additional Work</option>
          <option value="emergency_support">Emergency Support</option>
          <option value="consultation">Consultation</option>
          <option value="training">Training</option>
          <option value="custom">Custom</option>
        </select>
      </div>
    </div>

    <!-- Add-ons List -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Loading add-ons...</p>
    </div>

    <div v-else-if="addons.length === 0" class="text-center py-12 text-gray-500">
      <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <p>No add-ons found for this ticket.</p>
      <p class="text-sm mt-1">Add-ons allow you to request additional work or services beyond the original ticket scope.</p>
    </div>

    <div v-else class="space-y-4">
      <div 
        v-for="addon in addons" 
        :key="addon.id"
        class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow"
      >
        <!-- Addon Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <div class="flex items-center space-x-3 mb-2">
              <h4 class="text-lg font-medium text-gray-900">{{ addon.title }}</h4>
              <span :class="getStatusClasses(addon.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ formatStatus(addon.status) }}
              </span>
              <span v-if="addon.priority" :class="getPriorityClasses(addon.priority)" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ formatPriority(addon.priority) }}
              </span>
            </div>
            <div class="flex items-center space-x-4 text-sm text-gray-600">
              <span>{{ formatAddonType(addon.type) }}</span>
              <span v-if="addon.estimated_hours">{{ addon.estimated_hours }}h estimated</span>
              <span v-if="addon.estimated_cost" class="text-green-600 font-medium">${{ addon.estimated_cost }}</span>
              <span>{{ formatDate(addon.created_at) }}</span>
            </div>
          </div>
          
          <!-- Actions Dropdown -->
          <div class="relative" v-if="canManageAddon(addon)">
            <button
              @click="toggleActionMenu(addon.id)"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
              </svg>
            </button>
            
            <div v-if="activeActionMenu === addon.id" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
              <div class="py-1">
                <button 
                  v-if="canEditAddon(addon)"
                  @click="editAddon(addon)"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  Edit Add-on
                </button>
                <button 
                  v-if="canApproveAddon(addon)"
                  @click="approveAddon(addon)"
                  class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50"
                >
                  Approve
                </button>
                <button 
                  v-if="canRejectAddon(addon)"
                  @click="rejectAddon(addon)"
                  class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                >
                  Reject
                </button>
                <button 
                  v-if="canCompleteAddon(addon)"
                  @click="completeAddon(addon)"
                  class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50"
                >
                  Mark Complete
                </button>
                <hr v-if="canDeleteAddon(addon)" class="my-1">
                <button 
                  v-if="canDeleteAddon(addon)"
                  @click="deleteAddon(addon)"
                  class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Addon Description -->
        <div v-if="addon.description" class="mb-4">
          <p class="text-gray-700 text-sm">{{ addon.description }}</p>
        </div>

        <!-- Addon Details Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <div v-if="addon.requested_by">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</label>
            <p class="mt-1 text-sm text-gray-900">{{ addon.requested_by?.name || 'Unknown' }}</p>
          </div>
          <div v-if="addon.approved_by">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</label>
            <p class="mt-1 text-sm text-gray-900">{{ addon.approved_by?.name || 'Unknown' }}</p>
          </div>
          <div v-if="addon.actual_hours">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Hours</label>
            <p class="mt-1 text-sm text-gray-900">{{ addon.actual_hours }}h</p>
          </div>
          <div v-if="addon.actual_cost">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Cost</label>
            <p class="mt-1 text-sm text-gray-900 font-medium">${{ addon.actual_cost }}</p>
          </div>
        </div>

        <!-- Approval/Rejection Reason -->
        <div v-if="addon.approval_notes" class="mb-4 p-3 bg-gray-50 rounded-md">
          <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">
            {{ addon.status === 'approved' ? 'Approval' : 'Rejection' }} Notes
          </label>
          <p class="text-sm text-gray-700">{{ addon.approval_notes }}</p>
        </div>

        <!-- Progress Bar for Approved/In Progress Add-ons -->
        <div v-if="addon.status === 'approved' && addon.estimated_hours" class="mb-4">
          <div class="flex justify-between text-sm text-gray-600 mb-1">
            <span>Progress</span>
            <span>{{ addon.actual_hours || 0 }}h / {{ addon.estimated_hours }}h</span>
          </div>
          <div class="bg-gray-200 rounded-full h-2">
            <div 
              :style="{ width: Math.min((addon.actual_hours || 0) / addon.estimated_hours * 100, 100) + '%' }"
              :class="(addon.actual_hours || 0) > addon.estimated_hours ? 'bg-red-500' : 'bg-blue-500'"
              class="h-2 rounded-full transition-all duration-300"
            ></div>
          </div>
        </div>

        <!-- Time Entries for this Add-on -->
        <div v-if="addon.time_entries?.length > 0" class="border-t border-gray-200 pt-4">
          <h5 class="text-sm font-medium text-gray-900 mb-2">Time Entries</h5>
          <div class="space-y-2">
            <div 
              v-for="entry in addon.time_entries" 
              :key="entry.id"
              class="flex items-center justify-between text-sm"
            >
              <div class="flex items-center space-x-2">
                <span class="text-gray-600">{{ entry.user?.name || 'Unknown' }}</span>
                <span class="text-gray-400">-</span>
                <span class="text-gray-600">{{ formatDate(entry.started_at) }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="font-medium text-gray-900">{{ formatDuration(entry.duration) }}</span>
                <span v-if="entry.billable_amount" class="text-green-600">${{ entry.billable_amount }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Card -->
    <div v-if="addons.length > 0" class="bg-blue-50 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Add-ons Summary</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-900">{{ summary.total_count }}</div>
          <div class="text-sm text-gray-600">Total Add-ons</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ summary.approved_count }}</div>
          <div class="text-sm text-gray-600">Approved</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">${{ summary.total_estimated_cost }}</div>
          <div class="text-sm text-gray-600">Estimated</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">${{ summary.total_actual_cost }}</div>
          <div class="text-sm text-gray-600">Actual</div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <AddAddonModal 
      v-if="showAddAddonModal"
      :ticket="ticket"
      @saved="handleAddonSaved"
      @cancelled="showAddAddonModal = false"
    />
    
    <EditAddonModal 
      v-if="showEditAddonModal"
      :ticket="ticket"
      :addon="selectedAddon"
      @saved="handleAddonSaved"
      @cancelled="showEditAddonModal = false"
    />
    
    <AddonApprovalModal
      v-if="showApprovalModal"
      :addon="selectedAddon"
      :action="approvalAction"
      @approved="handleAddonApproval"
      @cancelled="showApprovalModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'

// Import child components
import AddAddonModal from './AddAddonModal.vue'
import EditAddonModal from './EditAddonModal.vue'
import AddonApprovalModal from './AddonApprovalModal.vue'

// Props
const props = defineProps({
  ticket: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['updated'])

// Reactive data
const addons = ref([])
const loading = ref(false)
const activeActionMenu = ref(null)

// Modal states
const showAddAddonModal = ref(false)
const showEditAddonModal = ref(false)
const showApprovalModal = ref(false)
const selectedAddon = ref(null)
const approvalAction = ref(null) // 'approve' or 'reject'

// Filters
const filters = ref({
  status: '',
  type: ''
})

// Summary data
const summary = ref({
  total_count: 0,
  approved_count: 0,
  total_estimated_cost: '0.00',
  total_actual_cost: '0.00'
})

// Computed properties
const canAddAddon = computed(() => {
  // TODO: Implement proper permission checking
  return true
})

// Methods
const formatStatus = (status) => {
  return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown'
}

const formatPriority = (priority) => {
  return priority?.charAt(0).toUpperCase() + priority?.slice(1) || 'Normal'
}

const formatAddonType = (type) => {
  const typeMap = {
    'additional_work': 'Additional Work',
    'emergency_support': 'Emergency Support',
    'consultation': 'Consultation',
    'training': 'Training',
    'custom': 'Custom'
  }
  return typeMap[type] || type
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDuration = (seconds) => {
  if (!seconds) return '0m'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  } else {
    return `${minutes}m`
  }
}

const getStatusClasses = (status) => {
  const statusMap = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'completed': 'bg-blue-100 text-blue-800'
  }
  
  return statusMap[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityClasses = (priority) => {
  const priorityMap = {
    'low': 'bg-gray-100 text-gray-800',
    'normal': 'bg-blue-100 text-blue-800',
    'high': 'bg-orange-100 text-orange-800',
    'urgent': 'bg-red-100 text-red-800'
  }
  
  return priorityMap[priority] || 'bg-gray-100 text-gray-800'
}

// Permission checking methods
const canManageAddon = (addon) => {
  // TODO: Implement proper permission checking
  return true
}

const canEditAddon = (addon) => {
  return addon.status === 'pending' && canManageAddon(addon)
}

const canApproveAddon = (addon) => {
  return addon.status === 'pending' && canManageAddon(addon)
}

const canRejectAddon = (addon) => {
  return addon.status === 'pending' && canManageAddon(addon)
}

const canCompleteAddon = (addon) => {
  return addon.status === 'approved' && canManageAddon(addon)
}

const canDeleteAddon = (addon) => {
  return addon.status === 'pending' && canManageAddon(addon)
}

// Data loading methods
const loadAddons = async () => {
  if (!props.ticket?.id) return
  
  loading.value = true
  
  try {
    const params = new URLSearchParams({
      ...Object.fromEntries(Object.entries(filters.value).filter(([_, v]) => v !== ''))
    })
    
    const response = await axios.get(`/api/tickets/${props.ticket.id}/addons?${params}`)
    addons.value = response.data.data || []
    
    // Calculate summary
    calculateSummary()
    
  } catch (error) {
    console.error('Failed to load addons:', error)
    addons.value = []
  } finally {
    loading.value = false
  }
}

const calculateSummary = () => {
  summary.value = {
    total_count: addons.value.length,
    approved_count: addons.value.filter(addon => addon.status === 'approved').length,
    total_estimated_cost: addons.value.reduce((sum, addon) => sum + parseFloat(addon.estimated_cost || 0), 0).toFixed(2),
    total_actual_cost: addons.value.reduce((sum, addon) => sum + parseFloat(addon.actual_cost || 0), 0).toFixed(2)
  }
}

// UI interaction methods
const toggleActionMenu = (addonId) => {
  activeActionMenu.value = activeActionMenu.value === addonId ? null : addonId
}

// Close action menu when clicking outside
const closeActionMenus = () => {
  activeActionMenu.value = null
}

// Addon action methods
const editAddon = (addon) => {
  selectedAddon.value = addon
  showEditAddonModal.value = true
  closeActionMenus()
}

const approveAddon = (addon) => {
  selectedAddon.value = addon
  approvalAction.value = 'approve'
  showApprovalModal.value = true
  closeActionMenus()
}

const rejectAddon = (addon) => {
  selectedAddon.value = addon
  approvalAction.value = 'reject'
  showApprovalModal.value = true
  closeActionMenus()
}

const completeAddon = async (addon) => {
  if (!confirm('Mark this add-on as completed?')) return
  
  try {
    await axios.post(`/api/ticket-addons/${addon.id}/complete`)
    await loadAddons()
    emit('updated')
  } catch (error) {
    console.error('Failed to complete addon:', error)
  }
  
  closeActionMenus()
}

const deleteAddon = async (addon) => {
  if (!confirm('Are you sure you want to delete this add-on? This action cannot be undone.')) return
  
  try {
    await axios.delete(`/api/ticket-addons/${addon.id}`)
    await loadAddons()
    emit('updated')
  } catch (error) {
    console.error('Failed to delete addon:', error)
  }
  
  closeActionMenus()
}

// Modal handlers
const handleAddonSaved = () => {
  showAddAddonModal.value = false
  showEditAddonModal.value = false
  selectedAddon.value = null
  loadAddons()
  emit('updated')
}

const handleAddonApproval = () => {
  showApprovalModal.value = false
  selectedAddon.value = null
  approvalAction.value = null
  loadAddons()
  emit('updated')
}

// Lifecycle
onMounted(() => {
  loadAddons()
  document.addEventListener('click', closeActionMenus)
})

// Watchers
watch(() => props.ticket?.id, (newId) => {
  if (newId) {
    loadAddons()
  }
})
</script>