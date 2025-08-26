<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="lg"
    title="Email System Settings"
  >
    <div class="space-y-6">
      <!-- IMAP Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-medium text-gray-900">IMAP Configuration</h3>
            <p class="text-sm text-gray-500">Incoming email server settings</p>
          </div>
          <div class="flex items-center">
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              settings.imap_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            ]">
              <span :class="[
                'w-1.5 h-1.5 rounded-full mr-1.5',
                settings.imap_enabled ? 'bg-green-400' : 'bg-red-400'
              ]"></span>
              {{ settings.imap_enabled ? 'Connected' : 'Disconnected' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">IMAP Host</label>
            <input
              v-model="form.imap_host"
              type="text"
              placeholder="imap.gmail.com"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">IMAP Port</label>
            <input
              v-model.number="form.imap_port"
              type="number"
              placeholder="993"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input
              v-model="form.imap_username"
              type="text"
              placeholder="support@company.com"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input
              v-model="form.imap_password"
              :type="showPasswords ? 'text' : 'password'"
              placeholder="••••••••"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>

        <div class="mt-4 flex items-center space-x-4">
          <label class="flex items-center">
            <input
              v-model="form.imap_encryption"
              value="ssl"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">SSL/TLS</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.imap_encryption"
              value="starttls"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">STARTTLS</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.imap_encryption"
              value="none"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">None</span>
          </label>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <button
            @click="testImapConnection"
            :disabled="testing.imap"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <span v-if="testing.imap" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Testing...
            </span>
            <span v-else>Test Connection</span>
          </button>

          <div class="text-sm">
            <span v-if="testResults.imap === 'success'" class="text-green-600 flex items-center">
              <CheckCircleIcon class="h-4 w-4 mr-1" />
              Connection successful
            </span>
            <span v-else-if="testResults.imap === 'failed'" class="text-red-600 flex items-center">
              <XCircleIcon class="h-4 w-4 mr-1" />
              Connection failed
            </span>
          </div>
        </div>
      </div>

      <!-- SMTP Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-medium text-gray-900">SMTP Configuration</h3>
            <p class="text-sm text-gray-500">Outgoing email server settings</p>
          </div>
          <div class="flex items-center">
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              settings.smtp_enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            ]">
              <span :class="[
                'w-1.5 h-1.5 rounded-full mr-1.5',
                settings.smtp_enabled ? 'bg-green-400' : 'bg-red-400'
              ]"></span>
              {{ settings.smtp_enabled ? 'Connected' : 'Disconnected' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">SMTP Host</label>
            <input
              v-model="form.smtp_host"
              type="text"
              placeholder="smtp.gmail.com"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">SMTP Port</label>
            <input
              v-model.number="form.smtp_port"
              type="number"
              placeholder="587"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Username</label>
            <input
              v-model="form.smtp_username"
              type="text"
              placeholder="support@company.com"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input
              v-model="form.smtp_password"
              :type="showPasswords ? 'text' : 'password'"
              placeholder="••••••••"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>

        <div class="mt-4 flex items-center space-x-4">
          <label class="flex items-center">
            <input
              v-model="form.smtp_encryption"
              value="tls"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">TLS</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.smtp_encryption"
              value="ssl"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">SSL</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.smtp_encryption"
              value="none"
              type="radio"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">None</span>
          </label>
        </div>

        <div class="mt-4 flex items-center justify-between">
          <button
            @click="testSmtpConnection"
            :disabled="testing.smtp"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            <span v-if="testing.smtp" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Testing...
            </span>
            <span v-else>Test Connection</span>
          </button>

          <div class="text-sm">
            <span v-if="testResults.smtp === 'success'" class="text-green-600 flex items-center">
              <CheckCircleIcon class="h-4 w-4 mr-1" />
              Connection successful
            </span>
            <span v-else-if="testResults.smtp === 'failed'" class="text-red-600 flex items-center">
              <XCircleIcon class="h-4 w-4 mr-1" />
              Connection failed
            </span>
          </div>
        </div>
      </div>

      <!-- Processing Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-900">Processing Settings</h3>
          <p class="text-sm text-gray-500">Configure how emails are processed</p>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Check Interval (minutes)</label>
              <input
                v-model.number="form.check_interval"
                type="number"
                min="1"
                max="60"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">How often to check for new emails</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Batch Size</label>
              <input
                v-model.number="form.batch_size"
                type="number"
                min="1"
                max="100"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">Number of emails to process at once</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Auto-Processing Options</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="form.auto_create_tickets"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Auto-create tickets from emails</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="form.auto_process_commands"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Auto-process subject commands</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="form.auto_attach_files"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Auto-attach email files to tickets</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Default Account for New Tickets</label>
            <select
              v-model="form.default_account_id"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">Select an account...</option>
              <option v-for="account in availableAccounts" :key="account.id" :value="account.id">
                {{ account.name }}
              </option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Account to use when creating tickets from emails</p>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="mb-4">
          <h3 class="text-lg font-medium text-gray-900">Security Settings</h3>
          <p class="text-sm text-gray-500">Email security and validation settings</p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="flex items-center">
              <input
                v-model="form.scan_attachments"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Scan attachments for viruses</span>
            </label>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Blocked File Extensions</label>
            <textarea
              v-model="form.blocked_extensions"
              rows="2"
              placeholder=".exe, .bat, .com, .scr"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">Comma-separated list of file extensions to block</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Max Attachment Size (MB)</label>
            <input
              v-model.number="form.max_attachment_size"
              type="number"
              min="1"
              max="100"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>

          <div>
            <label class="flex items-center">
              <input
                v-model="form.require_authentication"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-700">Require sender authentication for commands</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 ml-6">Only allow commands from authenticated users</p>
          </div>
        </div>
      </div>

      <!-- Show/Hide Passwords Toggle -->
      <div class="flex justify-center">
        <button
          @click="showPasswords = !showPasswords"
          class="text-sm text-indigo-600 hover:text-indigo-500"
        >
          {{ showPasswords ? 'Hide' : 'Show' }} passwords
        </button>
      </div>
    </div>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        Cancel
      </button>
      
      <button
        @click="saveSettings"
        :disabled="saving"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
      >
        <span v-if="saving" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Saving...
        </span>
        <span v-else>Save Settings</span>
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/StackedDialog.vue'

const props = defineProps({
  show: Boolean,
  settings: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['close', 'saved'])

// State
const saving = ref(false)
const showPasswords = ref(false)
const testing = reactive({ imap: false, smtp: false })
const testResults = reactive({ imap: null, smtp: null })
const availableAccounts = ref([])

// Form data
const form = reactive({
  // IMAP settings
  imap_host: '',
  imap_port: 993,
  imap_username: '',
  imap_password: '',
  imap_encryption: 'ssl',
  
  // SMTP settings
  smtp_host: '',
  smtp_port: 587,
  smtp_username: '',
  smtp_password: '',
  smtp_encryption: 'tls',
  
  // Processing settings
  check_interval: 5,
  batch_size: 10,
  auto_create_tickets: true,
  auto_process_commands: true,
  auto_attach_files: true,
  default_account_id: '',
  
  // Security settings
  scan_attachments: true,
  blocked_extensions: '.exe, .bat, .com, .scr, .pif',
  max_attachment_size: 25,
  require_authentication: false
})

// Watch for settings changes
watch(() => props.settings, (newSettings) => {
  if (newSettings) {
    Object.assign(form, newSettings)
  }
}, { immediate: true, deep: true })

// Load accounts when modal opens
watch(() => props.show, async (show) => {
  if (show) {
    await loadAccounts()
  }
})

// Methods
async function loadAccounts() {
  try {
    const response = await fetch('/api/search/accounts?limit=100')
    const data = await response.json()
    availableAccounts.value = data.results || []
  } catch (error) {
    console.error('Error loading accounts:', error)
  }
}

async function testImapConnection() {
  try {
    testing.imap = true
    testResults.imap = null

    const response = await fetch('/api/email-admin/test-connection', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        type: 'imap',
        host: form.imap_host,
        port: form.imap_port,
        username: form.imap_username,
        password: form.imap_password,
        encryption: form.imap_encryption
      })
    })

    const result = await response.json()
    testResults.imap = response.ok ? 'success' : 'failed'
    
    if (!response.ok) {
      console.error('IMAP test failed:', result.message)
    }

  } catch (error) {
    testResults.imap = 'failed'
    console.error('Error testing IMAP connection:', error)
  } finally {
    testing.imap = false
  }
}

async function testSmtpConnection() {
  try {
    testing.smtp = true
    testResults.smtp = null

    const response = await fetch('/api/email-admin/test-connection', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        type: 'smtp',
        host: form.smtp_host,
        port: form.smtp_port,
        username: form.smtp_username,
        password: form.smtp_password,
        encryption: form.smtp_encryption
      })
    })

    const result = await response.json()
    testResults.smtp = response.ok ? 'success' : 'failed'
    
    if (!response.ok) {
      console.error('SMTP test failed:', result.message)
    }

  } catch (error) {
    testResults.smtp = 'failed'
    console.error('Error testing SMTP connection:', error)
  } finally {
    testing.smtp = false
  }
}

async function saveSettings() {
  try {
    saving.value = true

    const response = await fetch('/api/email-admin/settings', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form)
    })

    if (!response.ok) {
      throw new Error('Failed to save settings')
    }

    emit('saved')
    
  } catch (error) {
    console.error('Error saving settings:', error)
  } finally {
    saving.value = false
  }
}
</script>