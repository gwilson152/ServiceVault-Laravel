<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="lg"
    title="Import Email Templates"
  >
    <div class="space-y-6">
      <!-- Import Method Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Import Method</label>
        <div class="space-y-2">
          <div class="flex items-center">
            <input
              id="method_file"
              v-model="importMethod"
              type="radio"
              value="file"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <label for="method_file" class="ml-3 text-sm text-gray-700">
              Upload JSON file
            </label>
          </div>
          <div class="flex items-center">
            <input
              id="method_system"
              v-model="importMethod"
              type="radio"
              value="system"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <label for="method_system" class="ml-3 text-sm text-gray-700">
              Import system templates
            </label>
          </div>
        </div>
      </div>

      <!-- File Upload -->
      <div v-if="importMethod === 'file'">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Select Templates File
        </label>
        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400">
          <div class="space-y-1 text-center">
            <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
            <div class="flex text-sm text-gray-600">
              <label
                for="file-upload"
                class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
              >
                <span>Upload a file</span>
                <input
                  id="file-upload"
                  ref="fileInput"
                  type="file"
                  accept=".json"
                  class="sr-only"
                  @change="handleFileSelect"
                />
              </label>
              <p class="pl-1">or drag and drop</p>
            </div>
            <p class="text-xs text-gray-500">JSON files up to 10MB</p>
          </div>
        </div>

        <!-- Selected File Info -->
        <div v-if="selectedFile" class="mt-3 p-3 bg-gray-50 rounded-md">
          <div class="flex items-center">
            <DocumentTextIcon class="h-5 w-5 text-gray-400 mr-2" />
            <div>
              <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
              <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- System Templates -->
      <div v-if="importMethod === 'system'">
        <div class="bg-blue-50 rounded-md p-4 mb-4">
          <div class="flex">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
            <div class="ml-3">
              <p class="text-sm text-blue-800">
                This will import the standard ServiceVault email templates for common notifications.
                Existing templates will not be overwritten.
              </p>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
            <div>
              <p class="text-sm font-medium text-gray-900">Ticket Notifications</p>
              <p class="text-xs text-gray-500">Created, updated, closed, assigned templates</p>
            </div>
            <input
              v-model="systemTemplateCategories.tickets"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
            <div>
              <p class="text-sm font-medium text-gray-900">Invoice & Billing</p>
              <p class="text-xs text-gray-500">Invoice generated, sent, payment received templates</p>
            </div>
            <input
              v-model="systemTemplateCategories.billing"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
            <div>
              <p class="text-sm font-medium text-gray-900">User Management</p>
              <p class="text-xs text-gray-500">Invitation, password reset, verification templates</p>
            </div>
            <input
              v-model="systemTemplateCategories.users"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
          </div>
        </div>
      </div>

      <!-- Import Options -->
      <div class="space-y-4">
        <div class="flex items-center">
          <input
            id="overwrite_existing"
            v-model="importOptions.overwriteExisting"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="overwrite_existing" class="ml-3 text-sm text-gray-700">
            Overwrite existing templates with same name
          </label>
        </div>

        <div class="flex items-center">
          <input
            id="activate_imported"
            v-model="importOptions.activateImported"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="activate_imported" class="ml-3 text-sm text-gray-700">
            Activate imported templates immediately
          </label>
        </div>
      </div>

      <!-- Preview (if file selected) -->
      <div v-if="filePreview.length > 0" class="border border-gray-200 rounded-md">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
          <h4 class="text-sm font-medium text-gray-900">
            Templates to Import ({{ filePreview.length }})
          </h4>
        </div>
        <div class="max-h-48 overflow-y-auto">
          <div v-for="template in filePreview" :key="template.name" class="px-4 py-3 border-b border-gray-100 last:border-b-0">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">{{ template.name }}</p>
                <p class="text-xs text-gray-500">{{ template.type }} • {{ template.description || 'No description' }}</p>
              </div>
              <div class="flex items-center">
                <span v-if="template.exists" class="inline-flex px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full">
                  Exists
                </span>
                <span v-else class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                  New
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        Cancel
      </button>
      
      <button
        @click="importTemplates"
        :disabled="!canImport || importing"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        <span v-if="importing" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Importing...
        </span>
        <span v-else>Import Templates</span>
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import {
  DocumentArrowUpIcon,
  DocumentTextIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'imported'])

// State
const importing = ref(false)
const importMethod = ref('system')
const selectedFile = ref(null)
const fileInput = ref(null)
const filePreview = ref([])

const systemTemplateCategories = reactive({
  tickets: true,
  billing: true,
  users: true
})

const importOptions = reactive({
  overwriteExisting: false,
  activateImported: true
})

// Computed
const canImport = computed(() => {
  if (importMethod.value === 'file') {
    return selectedFile.value !== null
  } else if (importMethod.value === 'system') {
    return Object.values(systemTemplateCategories).some(Boolean)
  }
  return false
})

// Methods
function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    previewFileContents(file)
  }
}

async function previewFileContents(file) {
  try {
    const text = await file.text()
    const data = JSON.parse(text)
    
    // Validate and preview templates
    if (Array.isArray(data)) {
      filePreview.value = data.map(template => ({
        ...template,
        exists: false // TODO: Check if template exists
      }))
    } else {
      throw new Error('Invalid file format')
    }
  } catch (error) {
    console.error('Error previewing file:', error)
    // Show error notification
    selectedFile.value = null
    filePreview.value = []
  }
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB'
  return Math.round(bytes / 1048576) + ' MB'
}

async function importTemplates() {
  try {
    importing.value = true

    let response
    
    if (importMethod.value === 'file' && selectedFile.value) {
      // File import
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      formData.append('overwrite_existing', importOptions.overwriteExisting)
      formData.append('activate_imported', importOptions.activateImported)

      response = await fetch('/api/email-templates/import/file', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
      })
    } else if (importMethod.value === 'system') {
      // System templates import
      const categories = Object.entries(systemTemplateCategories)
        .filter(([key, value]) => value)
        .map(([key, value]) => key)

      response = await fetch('/api/email-templates/import/system', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          categories,
          overwrite_existing: importOptions.overwriteExisting,
          activate_imported: importOptions.activateImported
        })
      })
    }

    if (!response.ok) {
      throw new Error('Import failed')
    }

    const result = await response.json()
    
    emit('imported', result)
    
    // Reset form
    selectedFile.value = null
    filePreview.value = []
    if (fileInput.value) {
      fileInput.value.value = ''
    }

  } catch (error) {
    console.error('Error importing templates:', error)
    // Show error notification
  } finally {
    importing.value = false
  }
}
</script>