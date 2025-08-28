<template>
  <Head title="Email Domain Mappings" />

  <StandardPageLayout 
    title="Email Domain Mappings" 
    subtitle="Configure how email addresses map to business accounts for ticket routing"
    :show-sidebar="true"
    :show-filters="false"
  >
    <template #header-actions>
      <div class="flex items-center space-x-3">
        <!-- Back to Settings -->
        <a
          :href="route('settings.index', 'email')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Back to Email Settings
        </a>

        <!-- Add Domain Mapping -->
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Add Domain Mapping
        </button>
      </div>
    </template>

    <template #main-content>
      <!-- Configuration Notice -->
      <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
          <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">Domain Mapping Configuration</h3>
            <p class="text-sm text-blue-700 mt-1">
              Domain mappings route incoming emails to specific business accounts based on email patterns. 
              Configure patterns like '@company.com' or 'support@company.com' to automatically assign tickets to accounts.
            </p>
          </div>
        </div>
      </div>

      <!-- Domain Mappings List -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Active Domain Mappings</h3>
              <p class="text-sm text-gray-500 mt-1">Configure email routing patterns and account assignments</p>
            </div>
            <button
              @click="loadDomainMappings"
              :disabled="loading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              <ArrowPathIcon :class="['w-4 h-4 mr-2', loading ? 'animate-spin' : '']" />
              Refresh
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="p-6 text-center">
          <div class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">Loading domain mappings...</span>
          </div>
        </div>

        <!-- Mappings Table -->
        <div v-else-if="domainMappings.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pattern</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="(mapping, index) in domainMappings" 
                :key="mapping.id" 
                class="hover:bg-gray-50 transition-colors duration-150"
                :class="{ 'bg-blue-50 border-blue-200': draggedIndex === index }"
                draggable="true"
                @dragstart="handleDragStart($event, index)"
                @dragover="handleDragOver($event)"
                @dragenter="handleDragEnter($event, index)"
                @dragleave="handleDragLeave($event)"
                @drop="handleDrop($event, index)"
                @dragend="handleDragEnd"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <EnvelopeIcon class="h-4 w-4 text-indigo-600" />
                      </div>
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ mapping.domain }}</div>
                      <div class="text-sm text-gray-500 capitalize">{{ getPatternTypeDisplay(mapping.domain) }} pattern</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="mapping.account">
                    <div class="text-sm font-medium text-gray-900">{{ mapping.account.name }}</div>
                    <div class="text-sm text-gray-500">{{ mapping.account.email || 'No email' }}</div>
                  </div>
                  <div v-else class="text-sm text-gray-500 italic">No account assigned</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <div class="flex items-center justify-center">
                    <Bars3Icon 
                      class="h-5 w-5 text-gray-400 cursor-grab hover:cursor-grabbing hover:text-gray-600 transition-colors" 
                      title="Drag to reorder"
                    />
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    mapping.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ mapping.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="editMapping(mapping)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      Edit
                    </button>
                    <button
                      @click="deleteMapping(mapping)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-else class="p-6 text-center">
          <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No domain mappings configured</h3>
          <p class="mt-1 text-sm text-gray-500">
            Get started by creating your first domain mapping to route emails to accounts.
          </p>
          <div class="mt-6">
            <button
              @click="showCreateModal = true"
              type="button"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              Add Domain Mapping
            </button>
          </div>
        </div>
      </div>
    </template>

    <template #sidebar>
      <!-- Quick Stats -->
      <div class="bg-white shadow rounded-lg p-5 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Domain Routing Stats</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Total Mappings</span>
            <span class="text-sm font-medium text-gray-900">{{ domainMappings.length }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Active Patterns</span>
            <span class="text-sm font-medium text-gray-900">{{ activeMappingsCount }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Accounts Configured</span>
            <span class="text-sm font-medium text-gray-900">{{ uniqueAccountsCount }}</span>
          </div>
        </div>
      </div>

      <!-- Pattern Examples -->
      <div class="bg-white shadow rounded-lg p-5">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Auto-Detected Patterns</h3>
        <div class="space-y-3 text-sm">
          <div>
            <div class="flex items-center justify-between mb-1">
              <code class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">DOMAIN</code>
            </div>
            <code class="bg-gray-100 px-2 py-1 rounded">@acme.com</code> or <code class="bg-gray-100 px-2 py-1 rounded">acme.com</code>
            <p class="text-gray-600 mt-1 text-xs">Matches any email from the domain</p>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">EMAIL</code>
            </div>
            <code class="bg-gray-100 px-2 py-1 rounded">support@acme.com</code>
            <p class="text-gray-600 mt-1 text-xs">Matches exact email address only</p>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <code class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs">WILDCARD</code>
            </div>
            <code class="bg-gray-100 px-2 py-1 rounded">*@acme.com</code>
            <p class="text-gray-600 mt-1 text-xs">Flexible wildcard matching</p>
          </div>
        </div>
        
        <div class="mt-4 p-3 bg-blue-50 rounded-md">
          <p class="text-xs text-blue-700">
            <strong>Auto-Detection:</strong> Pattern types are determined automatically from your input — no need to specify!
          </p>
        </div>
      </div>
    </template>
  </StandardPageLayout>

  <!-- Edit Domain Mapping Modal -->
  <dialog
    ref="editModalRef"
    :open="showEditModal"
    @close="showEditModal = false"
    class="relative z-50 w-full max-w-2xl mx-auto mt-16 p-0 rounded-lg shadow-xl bg-white border-0"
  >
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Edit Domain Mapping</h3>
        <button
          @click="showEditModal = false"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="updateMapping" class="space-y-4" v-if="editingMapping">
        <!-- Email Pattern -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email Pattern
          </label>
          <input
            v-model="editForm.domain_pattern"
            type="text"
            placeholder="@company.com, support@company.com, or *@company.com"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          />
          <div class="mt-2 text-sm text-gray-600">
            <p class="font-medium mb-1">Pattern types are automatically detected:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
              <li><code class="bg-gray-100 px-1 rounded">@company.com</code> or <code class="bg-gray-100 px-1 rounded">company.com</code> → Domain pattern</li>
              <li><code class="bg-gray-100 px-1 rounded">support@company.com</code> → Exact email pattern</li>
              <li><code class="bg-gray-100 px-1 rounded">*@company.com</code> → Wildcard pattern</li>
            </ul>
          </div>
        </div>

        <!-- Account Selection -->
        <div>
          <SimpleAccountUserSelector
            v-model:accountId="editForm.account_id"
            :showUserSelector="false"
          />
          <p class="text-sm text-gray-500 mt-1">
            Choose which business account should receive tickets from this email pattern
          </p>
        </div>

        <!-- Order Info -->
        <div class="bg-blue-50 p-3 rounded-md">
          <p class="text-sm text-blue-700">
            <strong>Processing Order:</strong> This mapping will be evaluated in the order shown in the list. 
            Use drag-and-drop to reorder mappings for priority.
          </p>
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
          <input
            v-model="editForm.is_active"
            type="checkbox"
            id="edit_is_active"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="edit_is_active" class="ml-2 text-sm text-gray-700">
            Active (process emails from this pattern)
          </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="showEditModal = false"
            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="updating"
            class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ updating ? 'Updating...' : 'Update Mapping' }}
          </button>
        </div>
      </form>
    </div>
  </dialog>

  <!-- Create Domain Mapping Modal -->
  <dialog
    ref="createModalRef"
    :open="showCreateModal"
    @close="showCreateModal = false"
    class="relative z-50 w-full max-w-2xl mx-auto mt-16 p-0 rounded-lg shadow-xl bg-white border-0"
  >
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Create Domain Mapping</h3>
        <button
          @click="showCreateModal = false"
          class="text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="createMapping" class="space-y-4">
        <!-- Email Pattern -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email Pattern
          </label>
          <input
            v-model="createForm.domain_pattern"
            type="text"
            placeholder="@company.com, support@company.com, or *@company.com"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          />
          <div class="mt-2 text-sm text-gray-600">
            <p class="font-medium mb-1">Pattern types are automatically detected:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
              <li><code class="bg-gray-100 px-1 rounded">@company.com</code> or <code class="bg-gray-100 px-1 rounded">company.com</code> → Domain pattern</li>
              <li><code class="bg-gray-100 px-1 rounded">support@company.com</code> → Exact email pattern</li>
              <li><code class="bg-gray-100 px-1 rounded">*@company.com</code> → Wildcard pattern</li>
            </ul>
          </div>
        </div>

        <!-- Account Selection -->
        <div>
          <SimpleAccountUserSelector
            v-model:accountId="createForm.account_id"
            :showUserSelector="false"
          />
          <p class="text-sm text-gray-500 mt-1">
            Choose which business account should receive tickets from this email pattern
          </p>
        </div>

        <!-- Order Info -->
        <div class="bg-blue-50 p-3 rounded-md">
          <p class="text-sm text-blue-700">
            <strong>Processing Order:</strong> New mappings are added to the end of the list. 
            Use drag-and-drop to reorder mappings for priority after creation.
          </p>
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
          <input
            v-model="createForm.is_active"
            type="checkbox"
            id="is_active"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            checked
          />
          <label for="is_active" class="ml-2 text-sm text-gray-700">
            Active (process emails from this pattern)
          </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t">
          <button
            type="button"
            @click="showCreateModal = false"
            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="creating"
            class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ creating ? 'Creating...' : 'Create Mapping' }}
          </button>
        </div>
      </form>
    </div>
  </dialog>
</template>

<script setup>
import { ref, reactive, watch, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StandardPageLayout from '@/Layouts/StandardPageLayout.vue'
import SimpleAccountUserSelector from '@/Components/UI/SimpleAccountUserSelector.vue'
import { 
  PlusIcon, 
  ArrowLeftIcon, 
  ArrowPathIcon,
  EnvelopeIcon, 
  InformationCircleIcon,
  XMarkIcon,
  Bars3Icon
} from '@heroicons/vue/24/outline'

// State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const creating = ref(false)
const updating = ref(false)
const createModalRef = ref()
const editModalRef = ref()
const loading = ref(false)
const domainMappings = ref([])
const editingMapping = ref(null)
const draggedIndex = ref(null)
const dragOverIndex = ref(null)

// Create form
const createForm = reactive({
  domain_pattern: '',
  account_id: '',
  is_active: true
})

// Edit form
const editForm = reactive({
  domain_pattern: '',
  account_id: '',
  is_active: true
})


// API Methods
async function loadDomainMappings() {
  loading.value = true
  try {
    const response = await fetch('/api/email-system/domain-mappings')
    if (response.ok) {
      const result = await response.json()
      domainMappings.value = result.data || result
    } else {
      console.error('Failed to load domain mappings')
    }
  } catch (error) {
    console.error('Error loading domain mappings:', error)
  } finally {
    loading.value = false
  }
}

async function createMapping() {
  if (!createForm.account_id) {
    alert('Please select an account')
    return
  }
  
  creating.value = true
  try {
    const response = await fetch('/api/email-system/domain-mappings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        domain: createForm.domain_pattern,
        account_id: createForm.account_id,
        is_active: createForm.is_active
      })
    })
    
    if (response.ok) {
      await loadDomainMappings() // Reload list
      showCreateModal.value = false
      resetForm()
      alert('Domain mapping created successfully')
    } else {
      const error = await response.json()
      alert(`Failed to create domain mapping: ${error.message || 'Unknown error'}`)
    }
  } catch (error) {
    console.error('Error creating domain mapping:', error)
    alert('Failed to create domain mapping: Network error')
  } finally {
    creating.value = false
  }
}

function editMapping(mapping) {
  editingMapping.value = mapping
  
  // Populate edit form
  editForm.domain_pattern = mapping.domain
  editForm.account_id = mapping.account_id
  editForm.is_active = mapping.is_active
  
  showEditModal.value = true
}

async function updateMapping() {
  if (!editForm.account_id) {
    alert('Please select an account')
    return
  }
  
  updating.value = true
  try {
    const response = await fetch(`/api/email-system/domain-mappings/${editingMapping.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        domain: editForm.domain_pattern,
        account_id: editForm.account_id,
        is_active: editForm.is_active
      })
    })
    
    if (response.ok) {
      await loadDomainMappings() // Reload list
      showEditModal.value = false
      resetEditForm()
      alert('Domain mapping updated successfully')
    } else {
      const error = await response.json()
      alert(`Failed to update domain mapping: ${error.message || 'Unknown error'}`)
    }
  } catch (error) {
    console.error('Error updating domain mapping:', error)
    alert('Failed to update domain mapping: Network error')
  } finally {
    updating.value = false
  }
}

async function deleteMapping(mapping) {
  if (!confirm(`Are you sure you want to delete the domain mapping for "${mapping.domain}"?`)) {
    return
  }
  
  try {
    const response = await fetch(`/api/email-system/domain-mappings/${mapping.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (response.ok) {
      await loadDomainMappings() // Reload list
      alert('Domain mapping deleted successfully')
    } else {
      const error = await response.json()
      alert(`Failed to delete domain mapping: ${error.message || 'Unknown error'}`)
    }
  } catch (error) {
    console.error('Error deleting domain mapping:', error)
    alert('Failed to delete domain mapping: Network error')
  }
}

function resetForm() {
  Object.assign(createForm, {
    domain_pattern: '',
    account_id: '',
    is_active: true
  })
}

function resetEditForm() {
  Object.assign(editForm, {
    domain_pattern: '',
    account_id: '',
    is_active: true
  })
  editingMapping.value = null
}

// Helper function to display pattern type
function getPatternTypeDisplay(pattern) {
  if (!pattern) return 'unknown'
  
  // Check for wildcards
  if (pattern.includes('*')) {
    return 'wildcard'
  }
  
  // Check if it's a full email address
  if (pattern.includes('@') && !pattern.startsWith('@')) {
    return 'email'
  }
  
  // Default to domain pattern
  return 'domain'
}

// Watch modal state to handle dialog element
watch(showCreateModal, (show) => {
  if (show && createModalRef.value) {
    createModalRef.value.showModal()
  } else if (createModalRef.value) {
    createModalRef.value.close()
  }
})

watch(showEditModal, (show) => {
  if (show && editModalRef.value) {
    editModalRef.value.showModal()
  } else if (editModalRef.value) {
    editModalRef.value.close()
  }
})

// Computed stats
const activeMappingsCount = computed(() => {
  return domainMappings.value.filter(mapping => mapping.is_active).length
})

const uniqueAccountsCount = computed(() => {
  const accountIds = new Set(domainMappings.value.map(mapping => mapping.account_id).filter(Boolean))
  return accountIds.size
})

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Drag and Drop Methods
function handleDragStart(event, index) {
  draggedIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/html', event.target)
}

function handleDragOver(event) {
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
}

function handleDragEnter(event, index) {
  event.preventDefault()
  dragOverIndex.value = index
}

function handleDragLeave(event) {
  // Only clear if we're leaving the entire row
  if (!event.currentTarget.contains(event.relatedTarget)) {
    dragOverIndex.value = null
  }
}

function handleDrop(event, dropIndex) {
  event.preventDefault()
  
  if (draggedIndex.value === null || draggedIndex.value === dropIndex) {
    return
  }
  
  const draggedMapping = domainMappings.value[draggedIndex.value]
  const newMappings = [...domainMappings.value]
  
  // Remove the dragged item
  newMappings.splice(draggedIndex.value, 1)
  
  // Insert at the new position
  newMappings.splice(dropIndex, 0, draggedMapping)
  
  // Update the local array
  domainMappings.value = newMappings
  
  // Update sort_order values and save to backend
  updateSortOrder()
}

function handleDragEnd() {
  draggedIndex.value = null
  dragOverIndex.value = null
}

async function updateSortOrder() {
  const updates = domainMappings.value.map((mapping, index) => ({
    id: mapping.id,
    sort_order: (index + 1) * 10 // Use increments of 10 to allow for easy insertion later
  }))
  
  try {
    const response = await fetch('/api/email-system/domain-mappings/reorder', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ mappings: updates })
    })
    
    if (!response.ok) {
      console.error('Failed to update sort order')
      // Optionally reload the data to revert changes
      await loadDomainMappings()
    }
  } catch (error) {
    console.error('Error updating sort order:', error)
    // Optionally reload the data to revert changes
    await loadDomainMappings()
  }
}

// Load data on component mount
onMounted(() => {
  loadDomainMappings()
})
</script>