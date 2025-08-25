<template>
  <Head title="Email Templates" />

  <StandardPageLayout 
    title="Email Templates" 
    :show-sidebar="true"
    :show-filters="true"
  >
    <template #header-actions>
      <div class="flex items-center space-x-3">
        <!-- Import Templates -->
        <button
          @click="showImportModal = true"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
          Import
        </button>

        <!-- Create Template -->
        <button
          @click="createTemplate"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Create Template
        </button>
      </div>
    </template>

    <template #filters>
      <FilterSection>
        <!-- Template Type Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Template Type</label>
          <MultiSelect
            v-model="filters.templateTypes"
            :options="templateTypeOptions"
            placeholder="All types"
            value-key="value"
            label-key="label"
          />
        </div>

        <!-- Account Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
          <UnifiedSelector
            v-model="filters.accounts"
            type="accounts"
            placeholder="All accounts"
            :multiple="true"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <MultiSelect
            v-model="filters.statuses"
            :options="statusOptions"
            placeholder="All statuses"
            value-key="value"
            label-key="label"
          />
        </div>

        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search templates..."
            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </FilterSection>
    </template>

    <template #main-content>
      <!-- Templates Table -->
      <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Template
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Account
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Last Modified
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Variables
                </th>
                <th scope="col" class="relative px-6 py-3">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="template in templates.data" :key="template.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <EnvelopeIcon class="h-5 w-5 text-indigo-600" />
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        {{ template.name }}
                      </div>
                      <div class="text-sm text-gray-500" v-if="template.description">
                        {{ template.description }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    getTypeColorClass(template.type)
                  ]">
                    {{ getTypeLabel(template.type) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div v-if="template.account">
                    {{ template.account.name }}
                  </div>
                  <div v-else class="text-gray-500 italic">
                    Global
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    template.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]">
                    {{ template.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(template.updated_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="flex flex-wrap gap-1">
                    <span 
                      v-for="variable in getTemplateVariables(template)" 
                      :key="variable"
                      class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-md"
                    >
                      {{ variable }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center space-x-2">
                    <!-- Preview Button -->
                    <button
                      @click="previewTemplate(template)"
                      class="text-indigo-600 hover:text-indigo-900"
                      title="Preview Template"
                    >
                      <EyeIcon class="h-4 w-4" />
                    </button>
                    
                    <!-- Edit Button -->
                    <button
                      @click="editTemplate(template)"
                      class="text-indigo-600 hover:text-indigo-900"
                      title="Edit Template"
                    >
                      <PencilIcon class="h-4 w-4" />
                    </button>
                    
                    <!-- Duplicate Button -->
                    <button
                      @click="duplicateTemplate(template)"
                      class="text-gray-600 hover:text-gray-900"
                      title="Duplicate Template"
                    >
                      <DocumentDuplicateIcon class="h-4 w-4" />
                    </button>
                    
                    <!-- Delete Button -->
                    <button
                      @click="deleteTemplate(template)"
                      class="text-red-600 hover:text-red-900"
                      title="Delete Template"
                      v-if="!template.is_system_template"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="templates.meta" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <Pagination :meta="templates.meta" />
        </div>
      </div>

      <!-- No Templates State -->
      <div v-if="!templates.data?.length && !loading" class="text-center py-12">
        <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No email templates</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating your first email template.</p>
        <div class="mt-6">
          <button
            @click="createTemplate"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Create Template
          </button>
        </div>
      </div>
    </template>

    <template #sidebar>
      <!-- Template Stats -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ChartBarIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Templates</dt>
                <dd class="text-lg font-medium text-gray-900">{{ templateStats.total || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Template Types Summary -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Template Types</h3>
          <div class="space-y-3">
            <div v-for="type in templateStats.by_type" :key="type.type" class="flex justify-between">
              <span class="text-sm text-gray-600">{{ getTypeLabel(type.type) }}</span>
              <span class="text-sm font-medium text-gray-900">{{ type.count }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Variable Reference -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Available Variables</h3>
          <div class="space-y-2">
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                Ticket Variables
              </summary>
              <div class="mt-2 pl-4 space-y-1">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}ticket.number{{'}}'}}</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}ticket.subject{{'}}'}}</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}ticket.description{{'}}'}}</code>
              </div>
            </details>
            
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                User Variables
              </summary>
              <div class="mt-2 pl-4 space-y-1">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}user.name{{'}}'}}</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}user.email{{'}}'}}</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}user.role{{'}}'}}</code>
              </div>
            </details>
            
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                Account Variables
              </summary>
              <div class="mt-2 pl-4 space-y-1">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}account.name{{'}}'}}</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">{{'{{'}}account.contact{{'}}'}}</code>
              </div>
            </details>
          </div>
        </div>
      </div>
    </template>
  </StandardPageLayout>

  <!-- Template Editor Modal -->
  <TemplateEditorModal
    :show="showEditorModal"
    :template="selectedTemplate"
    @close="closeEditorModal"
    @saved="handleTemplateSaved"
  />

  <!-- Template Preview Modal -->
  <TemplatePreviewModal
    :show="showPreviewModal"
    :template="selectedTemplate"
    @close="showPreviewModal = false"
  />

  <!-- Import Templates Modal -->
  <ImportTemplatesModal
    :show="showImportModal"
    @close="showImportModal = false"
    @imported="handleTemplatesImported"
  />
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import {
  PlusIcon,
  EnvelopeIcon,
  EyeIcon,
  PencilIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  ChartBarIcon,
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

// Components
import StandardPageLayout from '@/Components/Layout/StandardPageLayout.vue'
import FilterSection from '@/Components/Layout/FilterSection.vue'
import MultiSelect from '@/Components/Form/MultiSelect.vue'
import UnifiedSelector from '@/Components/Form/UnifiedSelector.vue'
import Pagination from '@/Components/Pagination.vue'
import TemplateEditorModal from './Components/TemplateEditorModal.vue'
import TemplatePreviewModal from './Components/TemplatePreviewModal.vue'
import ImportTemplatesModal from './Components/ImportTemplatesModal.vue'

// State
const loading = ref(false)
const showEditorModal = ref(false)
const showPreviewModal = ref(false)
const showImportModal = ref(false)
const selectedTemplate = ref(null)

// Filters
const filters = reactive({
  templateTypes: [],
  accounts: [],
  statuses: [],
  search: ''
})

// Filter Options
const templateTypeOptions = [
  { value: 'ticket_created', label: 'Ticket Created' },
  { value: 'ticket_updated', label: 'Ticket Updated' },
  { value: 'ticket_closed', label: 'Ticket Closed' },
  { value: 'time_entry_added', label: 'Time Entry Added' },
  { value: 'invoice_generated', label: 'Invoice Generated' },
  { value: 'payment_received', label: 'Payment Received' },
  { value: 'user_invitation', label: 'User Invitation' },
  { value: 'custom', label: 'Custom' }
]

const statusOptions = [
  { value: true, label: 'Active' },
  { value: false, label: 'Inactive' }
]

// API Queries
const { data: templates, isLoading: templatesLoading, refetch: refetchTemplates } = useQuery({
  queryKey: ['email-templates', filters],
  queryFn: () => fetchTemplates(filters)
})

const { data: templateStats } = useQuery({
  queryKey: ['email-template-stats'],
  queryFn: fetchTemplateStats
})

// API Functions
async function fetchTemplates(filters) {
  const params = new URLSearchParams()
  
  if (filters.templateTypes.length) {
    filters.templateTypes.forEach(type => params.append('type[]', type))
  }
  if (filters.accounts.length) {
    filters.accounts.forEach(account => params.append('account_id[]', account.id))
  }
  if (filters.statuses.length) {
    filters.statuses.forEach(status => params.append('is_active[]', status))
  }
  if (filters.search) {
    params.append('search', filters.search)
  }

  const response = await fetch(`/api/email-templates?${params}`)
  if (!response.ok) throw new Error('Failed to fetch templates')
  return response.json()
}

async function fetchTemplateStats() {
  const response = await fetch('/api/email-templates/stats')
  if (!response.ok) throw new Error('Failed to fetch template stats')
  return response.json()
}

// Template Management
function createTemplate() {
  selectedTemplate.value = null
  showEditorModal.value = true
}

function editTemplate(template) {
  selectedTemplate.value = template
  showEditorModal.value = true
}

async function duplicateTemplate(template) {
  try {
    loading.value = true
    const response = await fetch(`/api/email-templates/${template.id}/duplicate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (!response.ok) throw new Error('Failed to duplicate template')
    
    await refetchTemplates()
    // Show success notification
  } catch (error) {
    console.error('Error duplicating template:', error)
    // Show error notification
  } finally {
    loading.value = false
  }
}

async function deleteTemplate(template) {
  if (!confirm('Are you sure you want to delete this template?')) {
    return
  }

  try {
    loading.value = true
    const response = await fetch(`/api/email-templates/${template.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (!response.ok) throw new Error('Failed to delete template')
    
    await refetchTemplates()
    // Show success notification
  } catch (error) {
    console.error('Error deleting template:', error)
    // Show error notification
  } finally {
    loading.value = false
  }
}

function previewTemplate(template) {
  selectedTemplate.value = template
  showPreviewModal.value = true
}

// Event Handlers
function closeEditorModal() {
  showEditorModal.value = false
  selectedTemplate.value = null
}

function handleTemplateSaved() {
  closeEditorModal()
  refetchTemplates()
}

function handleTemplatesImported() {
  showImportModal.value = false
  refetchTemplates()
}

// Helper Functions
function getTypeLabel(type) {
  const option = templateTypeOptions.find(opt => opt.value === type)
  return option ? option.label : type
}

function getTypeColorClass(type) {
  const colors = {
    'ticket_created': 'bg-blue-100 text-blue-800',
    'ticket_updated': 'bg-yellow-100 text-yellow-800',
    'ticket_closed': 'bg-green-100 text-green-800',
    'time_entry_added': 'bg-purple-100 text-purple-800',
    'invoice_generated': 'bg-indigo-100 text-indigo-800',
    'payment_received': 'bg-green-100 text-green-800',
    'user_invitation': 'bg-pink-100 text-pink-800',
    'custom': 'bg-gray-100 text-gray-800'
  }
  return colors[type] || 'bg-gray-100 text-gray-800'
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function getTemplateVariables(template) {
  if (!template.body && !template.subject) return []
  
  const content = `${template.subject || ''} ${template.body || ''}`
  const variablePattern = /\{\{([^}]+)\}\}/g
  const variables = []
  let match
  
  while ((match = variablePattern.exec(content)) !== null) {
    const variable = match[1].trim()
    if (!variables.includes(variable)) {
      variables.push(variable)
    }
  }
  
  return variables.slice(0, 5) // Show max 5 variables
}
</script>