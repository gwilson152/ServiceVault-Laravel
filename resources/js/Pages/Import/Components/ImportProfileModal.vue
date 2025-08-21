<template>
  <StackedDialog 
    :show="show" 
    @close="$emit('close')"
    :title="getStepTitle()"
    max-width="3xl"
  >
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Basic Information -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Profile Name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="e.g., FreeScout Production"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
        </div>

        <div>
          <label for="database_type" class="block text-sm font-medium text-gray-700">Database Type</label>
          <select
            id="database_type"
            v-model="form.database_type"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
            <option value="">Select database type...</option>
            <option value="postgresql">PostgreSQL</option>
            <option value="mysql">MySQL (Coming Soon)</option>
            <option value="sqlite">SQLite (Coming Soon)</option>
          </select>
          <p class="mt-1 text-sm text-gray-500">
            Database engine type for this connection
          </p>
          <p v-if="errors.database_type" class="mt-1 text-sm text-red-600">{{ errors.database_type }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Optional description of this import profile..."
          />
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
        </div>

        <div>
          <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="3"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Internal notes, connection details, etc..."
          />
          <p class="mt-1 text-sm text-gray-500">
            Internal notes for this connection (not visible in imports)
          </p>
          <p v-if="errors.notes" class="mt-1 text-sm text-red-600">{{ errors.notes }}</p>
        </div>
      </div>

      <!-- Database Connection -->
      <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Database Connection</h3>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="host" class="block text-sm font-medium text-gray-700">Host</label>
            <input
              id="host"
              v-model="form.host"
              type="text"
              required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="localhost or database IP"
            />
            <p v-if="errors.host" class="mt-1 text-sm text-red-600">{{ errors.host }}</p>
          </div>

          <div>
            <label for="port" class="block text-sm font-medium text-gray-700">Port</label>
            <input
              id="port"
              v-model="form.port"
              type="number"
              required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="5432"
            />
            <p v-if="errors.port" class="mt-1 text-sm text-red-600">{{ errors.port }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6">
          <div>
            <label for="database" class="block text-sm font-medium text-gray-700">Database Name</label>
            <input
              id="database"
              v-model="form.database"
              type="text"
              required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="freescout"
            />
            <p v-if="errors.database" class="mt-1 text-sm text-red-600">{{ errors.database }}</p>
          </div>

          <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
            <input
              id="username"
              v-model="form.username"
              type="text"
              required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="database username"
            />
            <p v-if="errors.username" class="mt-1 text-sm text-red-600">{{ errors.username }}</p>
          </div>
        </div>

        <div class="mt-6">
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="database password"
          />
          <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
        </div>
      </div>

      <!-- SSL Configuration -->
      <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">SSL Configuration</h3>
        
        <div>
          <label for="ssl_mode" class="block text-sm font-medium text-gray-700">SSL Mode</label>
          <select
            id="ssl_mode"
            v-model="form.ssl_mode"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
            <option value="disable">Disable</option>
            <option value="allow">Allow</option>
            <option value="prefer">Prefer</option>
            <option value="require">Require</option>
            <option value="verify-ca">Verify CA</option>
            <option value="verify-full">Verify Full</option>
          </select>
          <p class="mt-1 text-sm text-gray-500">
            SSL connection mode for secure database connections
          </p>
          <p v-if="errors.ssl_mode" class="mt-1 text-sm text-red-600">{{ errors.ssl_mode }}</p>
        </div>
      </div>

      <!-- Test Connection -->
      <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Test Connection</h3>
            <p class="text-sm text-gray-500">Verify database connection before saving</p>
          </div>
          <button
            type="button"
            @click="testConnection"
            :disabled="isTestingConnection || !canTestConnection"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isTestingConnection" class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-gray-600 mr-2"></span>
            <ServerIcon v-else class="w-4 h-4 mr-2" />
            {{ isTestingConnection ? 'Testing...' : 'Test Connection' }}
          </button>
        </div>
        
        <div v-if="connectionTest" class="mt-4">
          <div
            :class="[
              'rounded-md p-4',
              connectionTest.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
            ]"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <CheckCircleIcon v-if="connectionTest.success" class="h-5 w-5 text-green-400" />
                <XCircleIcon v-else class="h-5 w-5 text-red-400" />
              </div>
              <div class="ml-3">
                <h3 :class="connectionTest.success ? 'text-green-800' : 'text-red-800'" class="text-sm font-medium">
                  {{ connectionTest.success ? 'Connection Successful' : 'Connection Failed' }}
                </h3>
                <div :class="connectionTest.success ? 'text-green-700' : 'text-red-700'" class="mt-2 text-sm">
                  <p>{{ connectionTest.message }}</p>
                  <div v-if="connectionTest.error" class="mt-2 p-2 bg-red-100 border border-red-200 rounded text-xs">
                    <p class="font-mono">{{ connectionTest.error }}</p>
                  </div>
                  <div v-if="connectionTest.server_info || connectionTest.database_info" class="mt-2 space-y-1">
                    <p><strong>Database:</strong> {{ (connectionTest.server_info || connectionTest.database_info).database }}</p>
                    <p><strong>Version:</strong> {{ (connectionTest.server_info || connectionTest.database_info).version }}</p>
                    <p v-if="(connectionTest.database_info || {}).table_count"><strong>Tables:</strong> {{ connectionTest.database_info.table_count }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center justify-end space-x-3">
          <button
            type="button"
            @click="$emit('close')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting || !canFinish"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSubmitting" class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
            {{ isSubmitting ? 'Saving...' : (props.profile ? 'Update Profile' : 'Create Profile') }}
          </button>
        </div>
      </div>
    </form>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import { 
  ServerIcon, 
  CheckCircleIcon, 
  XCircleIcon
} from '@heroicons/vue/24/outline'
import { useImportQueries } from '@/Composables/queries/useImportQueries.js'

const props = defineProps({
  show: Boolean,
  profile: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

// Composables
const { 
  createProfile, 
  updateProfile, 
  testConnection: testConnectionMutation, 
  isTestingConnection
} = useImportQueries()

// Form state
const form = reactive({
  name: '',
  database_type: '',
  description: '',
  notes: '',
  host: '',
  port: 5432,
  database: '',
  username: '',
  password: '',
  ssl_mode: 'prefer'
})

const errors = ref({})
const isSubmitting = ref(false)
const connectionTest = ref(null)

// Remove multi-step state - now just a single form

// Methods
const resetForm = () => {
  Object.assign(form, {
    name: '',
    database_type: '',
    description: '',
    notes: '',
    host: '',
    port: 5432,
    database: '',
    username: '',
    password: '',
    ssl_mode: 'prefer'
  })
  errors.value = {}
  connectionTest.value = null
}

// Computed
const canTestConnection = computed(() => {
  return form.host && form.port && form.database && form.username && form.password
})

// Computed properties
const canFinish = computed(() => {
  // Can finish when connection test is successful
  return connectionTest.value?.success === true
})

// Step navigation methods
const getStepTitle = () => {
  return props.profile ? 'Edit Import Profile' : 'Create Import Profile'
}

// Removed multi-step methods - now just a single connection form

// Watch for profile prop changes (editing mode)
watch(() => props.profile, (profile) => {
  if (profile) {
    Object.assign(form, {
      name: profile.name || '',
      database_type: profile.database_type || '',
      description: profile.description || '',
      notes: profile.notes || '',
      host: profile.host || '',
      port: profile.port || 5432,
      database: profile.database || '',
      username: profile.username || '',
      password: '', // Don't pre-fill password for security
      ssl_mode: profile.ssl_mode || 'prefer'
    })
  } else {
    resetForm()
  }
}, { immediate: true })

// Watch for modal show changes
watch(() => props.show, (show) => {
  if (!show) {
    connectionTest.value = null
    errors.value = {}
  }
})

// Methods continued
const testConnection = async () => {
  try {
    connectionTest.value = null
    errors.value = {}
    
    const result = await testConnectionMutation({
      database_type: form.database_type,
      host: form.host,
      port: form.port,
      database: form.database,
      username: form.username,
      password: form.password,
      ssl_mode: form.ssl_mode
    })
    
    // Handle response - mutation handles both success and structured failure responses
    if (result.connection_test) {
      connectionTest.value = result.connection_test
    } else {
      connectionTest.value = result
    }
  } catch (error) {
    console.error('Connection test error:', error)
    
    // Only handle truly unexpected errors
    connectionTest.value = {
      success: false,
      message: error.response?.data?.message || error.message || 'Unexpected error occurred',
      error: error.response?.data?.error
    }
  }
}

const handleSubmit = async () => {
  isSubmitting.value = true
  errors.value = {}
  
  try {
    if (props.profile) {
      // Update existing profile
      await updateProfile(props.profile.id, form)
    } else {
      // Create new profile
      await createProfile(form)
    }
    
    emit('saved')
  } catch (error) {
    if (error.data?.errors) {
      errors.value = error.data.errors
    } else {
      errors.value = { general: error.message || 'An error occurred' }
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>