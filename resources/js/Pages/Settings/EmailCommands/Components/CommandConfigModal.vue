<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="lg"
    :title="`Configure ${command?.name} Command`"
  >
    <form @submit.prevent="saveConfig" class="space-y-6" v-if="command">
      <!-- Command Status -->
      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
        <div>
          <h3 class="text-sm font-medium text-gray-900">{{ command.name }}</h3>
          <p class="text-sm text-gray-500">{{ command.description }}</p>
        </div>
        <div class="flex items-center">
          <label class="text-sm font-medium text-gray-700 mr-3">Enabled</label>
          <button
            type="button"
            @click="form.enabled = !form.enabled"
            :class="[
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
              form.enabled ? 'bg-indigo-600' : 'bg-gray-200'
            ]"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition duration-200 ease-in-out',
                form.enabled ? 'translate-x-5' : 'translate-x-0'
              ]"
            />
          </button>
        </div>
      </div>

      <!-- Permission Settings -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Who can use this command?
        </label>
        <div class="space-y-2">
          <div v-for="role in availableRoles" :key="role" class="flex items-center">
            <input
              :id="`role-${role}`"
              v-model="form.allowed_roles"
              :value="role"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label :for="`role-${role}`" class="ml-3 text-sm text-gray-700">
              {{ role }}
            </label>
          </div>
        </div>
      </div>

      <!-- Validation Rules -->
      <div v-if="hasValidationRules">
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Validation Rules
        </label>
        
        <div class="space-y-4">
          <!-- Time Command Specific -->
          <div v-if="command.name === 'time'">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Min Time (minutes)</label>
                <input
                  v-model.number="form.validation_rules.min_time"
                  type="number"
                  min="1"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Max Time (minutes)</label>
                <input
                  v-model.number="form.validation_rules.max_time"
                  type="number"
                  min="1"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            <div class="mt-2">
              <label class="flex items-center">
                <input
                  v-model="form.validation_rules.require_description"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Require time entry description</span>
              </label>
            </div>
          </div>

          <!-- Priority Command Specific -->
          <div v-if="command.name === 'priority'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Allowed Priority Values</label>
            <div class="space-y-2">
              <div v-for="priority in availablePriorities" :key="priority" class="flex items-center">
                <input
                  :id="`priority-${priority}`"
                  v-model="form.validation_rules.allowed_priorities"
                  :value="priority"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label :for="`priority-${priority}`" class="ml-3 text-sm text-gray-700 capitalize">
                  {{ priority }}
                </label>
              </div>
            </div>
          </div>

          <!-- Status Command Specific -->
          <div v-if="command.name === 'status'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Allowed Status Values</label>
            <div class="space-y-2">
              <div v-for="status in availableStatuses" :key="status" class="flex items-center">
                <input
                  :id="`status-${status}`"
                  v-model="form.validation_rules.allowed_statuses"
                  :value="status"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <label :for="`status-${status}`" class="ml-3 text-sm text-gray-700">
                  {{ status.charAt(0).toUpperCase() + status.slice(1).replace('-', ' ') }}
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Rate Limiting -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Rate Limiting
        </label>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Max Uses per Hour</label>
            <input
              v-model.number="form.rate_limit.per_hour"
              type="number"
              min="1"
              placeholder="10"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Max Uses per Day</label>
            <input
              v-model.number="form.rate_limit.per_day"
              type="number"
              min="1"
              placeholder="50"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Audit Settings -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
          Audit & Logging
        </label>
        <div class="space-y-3">
          <label class="flex items-center">
            <input
              v-model="form.audit_settings.log_all_attempts"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Log all command attempts (including failures)</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.audit_settings.notify_on_failure"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Notify administrators on command failures</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.audit_settings.detailed_logging"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Enable detailed logging (includes email content)</span>
          </label>
        </div>
      </div>

      <!-- Custom Help Text -->
      <div>
        <label for="help_text" class="block text-sm font-medium text-gray-700">
          Custom Help Text (Optional)
        </label>
        <textarea
          id="help_text"
          v-model="form.help_text"
          rows="3"
          placeholder="Additional guidance for users on how to use this command..."
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        ></textarea>
      </div>
    </form>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        Cancel
      </button>
      
      <button
        @click="saveConfig"
        :disabled="saving"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        <span v-if="saving" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Saving...
        </span>
        <span v-else>Save Configuration</span>
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import { ref } from 'vue'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'

const props = defineProps({
  show: Boolean,
  command: Object
})

const emit = defineEmits(['close', 'saved'])

// State
const saving = ref(false)

// Form data
const form = reactive({
  enabled: true,
  allowed_roles: [],
  validation_rules: {},
  rate_limit: {
    per_hour: 10,
    per_day: 50
  },
  audit_settings: {
    log_all_attempts: true,
    notify_on_failure: false,
    detailed_logging: false
  },
  help_text: ''
})

// Available options
const availableRoles = ['Employee', 'Manager', 'Admin', 'Super Admin']
const availablePriorities = ['low', 'medium', 'high', 'urgent']
const availableStatuses = ['open', 'in-progress', 'pending', 'resolved', 'closed']

// Computed
const hasValidationRules = computed(() => {
  return ['time', 'priority', 'status', 'assign'].includes(props.command?.name)
})

// Watch for command changes
watch(() => props.command, (newCommand) => {
  if (newCommand) {
    // Initialize form with command data
    form.enabled = newCommand.enabled ?? true
    form.allowed_roles = newCommand.allowed_roles ? [...newCommand.allowed_roles] : ['Employee', 'Manager', 'Admin']
    form.help_text = newCommand.help_text || ''
    
    // Initialize validation rules based on command type
    if (newCommand.name === 'time') {
      form.validation_rules = {
        min_time: 1,
        max_time: 480, // 8 hours
        require_description: false,
        ...newCommand.validation_rules
      }
    } else if (newCommand.name === 'priority') {
      form.validation_rules = {
        allowed_priorities: ['low', 'medium', 'high', 'urgent'],
        ...newCommand.validation_rules
      }
    } else if (newCommand.name === 'status') {
      form.validation_rules = {
        allowed_statuses: ['open', 'in-progress', 'pending', 'resolved', 'closed'],
        ...newCommand.validation_rules
      }
    }
    
    // Initialize rate limiting
    form.rate_limit = {
      per_hour: 10,
      per_day: 50,
      ...newCommand.rate_limit
    }
    
    // Initialize audit settings
    form.audit_settings = {
      log_all_attempts: true,
      notify_on_failure: false,
      detailed_logging: false,
      ...newCommand.audit_settings
    }
  }
}, { immediate: true })

// Methods
async function saveConfig() {
  if (!props.command) return

  try {
    saving.value = true

    const configData = {
      enabled: form.enabled,
      allowed_roles: form.allowed_roles,
      validation_rules: form.validation_rules,
      rate_limit: form.rate_limit,
      audit_settings: form.audit_settings,
      help_text: form.help_text || null
    }

    const response = await fetch(`/api/email-commands/${props.command.name}/config`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(configData)
    })

    if (!response.ok) {
      throw new Error('Failed to save configuration')
    }

    emit('saved')

  } catch (error) {
    console.error('Error saving command configuration:', error)
    // Show error notification
  } finally {
    saving.value = false
  }
}
</script>