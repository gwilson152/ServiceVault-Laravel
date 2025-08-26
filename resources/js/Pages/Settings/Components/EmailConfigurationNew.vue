<template>
  <div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-semibold text-gray-900">Email System Configuration</h2>
          <p class="text-gray-600 mt-2">Configure your application's incoming and outgoing email services.</p>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- Test Configuration Button -->
          <button
            @click="testConfiguration"
            :disabled="testing"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="testing" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Testing...
            </span>
            <span v-else class="flex items-center">
              <CheckCircleIcon class="w-4 h-4 mr-2" />
              Test Configuration
            </span>
          </button>

          <!-- Save Button -->
          <button
            @click="saveConfiguration"
            :disabled="saving || !hasChanges"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
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
        </div>
      </div>
    </div>

    <!-- System Status Alert -->
    <div v-if="config.system_active" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-start">
        <CheckCircleIcon class="h-5 w-5 text-green-400 mt-0.5" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">Email System Active</h3>
          <p class="text-sm text-green-700 mt-1">
            Your email system is configured and actively processing emails.
            <span v-if="config.last_tested_at">
              Last tested: {{ formatDateTime(config.last_tested_at) }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <div v-else-if="!config.system_active && (config.incoming_enabled || config.outgoing_enabled)" 
         class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-start">
        <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mt-0.5" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Email System Configured but Inactive</h3>
          <p class="text-sm text-yellow-700 mt-1">
            Your email services are configured but the system is not active. Enable the system below to start processing emails.
          </p>
        </div>
      </div>
    </div>

    <!-- Main Configuration Form -->
    <form @submit.prevent="saveConfiguration" class="space-y-8">
      
      <!-- System Activation -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">System Status</h3>
          <p class="text-sm text-gray-500 mt-1">Control the overall email system activation</p>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <label for="system-active" class="text-base font-medium text-gray-900">
                Email System Active
              </label>
              <p class="text-sm text-gray-500">
                Enable this to activate email processing. Both incoming and outgoing services must be configured.
              </p>
            </div>
            <button
              type="button"
              @click="form.system_active = !form.system_active"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                form.system_active ? 'bg-indigo-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.system_active ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
      </div>

      <!-- Incoming Email Configuration -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Incoming Email Service</h3>
              <p class="text-sm text-gray-500 mt-1">Configure how your application receives emails</p>
            </div>
            <button
              type="button"
              @click="form.incoming_enabled = !form.incoming_enabled"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                form.incoming_enabled ? 'bg-indigo-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.incoming_enabled ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
        
        <div v-show="form.incoming_enabled" class="p-6 space-y-6">
          
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Provider</label>
            <select 
              v-model="form.incoming_provider" 
              @change="applyProviderDefaults('incoming')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select a provider...</option>
              <option value="imap">Generic IMAP</option>
              <option value="gmail">Gmail</option>
              <option value="outlook">Outlook/Office 365</option>
              <option value="exchange">Exchange Server</option>
            </select>
          </div>

          <!-- Server Configuration -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">IMAP Server</label>
              <input 
                v-model="form.incoming_host" 
                type="text" 
                placeholder="imap.gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
              <input 
                v-model="form.incoming_port" 
                type="number" 
                placeholder="993"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Authentication -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username/Email</label>
              <input 
                v-model="form.incoming_username" 
                type="email" 
                placeholder="your-email@gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
              <div class="relative">
                <input 
                  v-model="form.incoming_password" 
                  :type="showIncomingPassword ? 'text' : 'password'"
                  placeholder="Your password or app password"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                />
                <button
                  type="button"
                  @click="showIncomingPassword = !showIncomingPassword"
                  class="absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <EyeIcon v-if="!showIncomingPassword" class="h-4 w-4 text-gray-400" />
                  <EyeSlashIcon v-else class="h-4 w-4 text-gray-400" />
                </button>
              </div>
            </div>
          </div>

          <!-- Advanced Settings -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
              <select 
                v-model="form.incoming_encryption" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option value="ssl">SSL</option>
                <option value="tls">TLS</option>
                <option value="starttls">STARTTLS</option>
                <option value="none">None</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Folder</label>
              <input 
                v-model="form.incoming_folder" 
                type="text" 
                placeholder="INBOX"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

        </div>
      </div>

      <!-- Outgoing Email Configuration -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Outgoing Email Service</h3>
              <p class="text-sm text-gray-500 mt-1">Configure how your application sends emails</p>
            </div>
            <button
              type="button"
              @click="form.outgoing_enabled = !form.outgoing_enabled"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                form.outgoing_enabled ? 'bg-indigo-600' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.outgoing_enabled ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>
        </div>
        
        <div v-show="form.outgoing_enabled" class="p-6 space-y-6">
          
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Provider</label>
            <select 
              v-model="form.outgoing_provider" 
              @change="applyProviderDefaults('outgoing')"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select a provider...</option>
              <option value="smtp">Generic SMTP</option>
              <option value="gmail">Gmail</option>
              <option value="outlook">Outlook/Office 365</option>
              <option value="ses">Amazon SES</option>
              <option value="sendgrid">SendGrid</option>
              <option value="postmark">Postmark</option>
              <option value="mailgun">Mailgun</option>
            </select>
          </div>

          <!-- Server Configuration -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Server</label>
              <input 
                v-model="form.outgoing_host" 
                type="text" 
                placeholder="smtp.gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
              <input 
                v-model="form.outgoing_port" 
                type="number" 
                placeholder="587"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Authentication -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username/Email</label>
              <input 
                v-model="form.outgoing_username" 
                type="email" 
                placeholder="your-email@gmail.com"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
              <div class="relative">
                <input 
                  v-model="form.outgoing_password" 
                  :type="showOutgoingPassword ? 'text' : 'password'"
                  placeholder="Your password or app password"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                />
                <button
                  type="button"
                  @click="showOutgoingPassword = !showOutgoingPassword"
                  class="absolute inset-y-0 right-0 flex items-center pr-3"
                >
                  <EyeIcon v-if="!showOutgoingPassword" class="h-4 w-4 text-gray-400" />
                  <EyeSlashIcon v-else class="h-4 w-4 text-gray-400" />
                </button>
              </div>
            </div>
          </div>

          <!-- Encryption -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
            <select 
              v-model="form.outgoing_encryption" 
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
              <option value="starttls">STARTTLS</option>
              <option value="none">None</option>
            </select>
          </div>

        </div>
      </div>

      <!-- From Address Configuration -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Sender Information</h3>
          <p class="text-sm text-gray-500 mt-1">Configure the default sender information for outgoing emails</p>
        </div>
        <div class="p-6 space-y-6">
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">From Email Address *</label>
              <input 
                v-model="form.from_address" 
                type="email" 
                placeholder="noreply@yourcompany.com"
                required
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
              <input 
                v-model="form.from_name" 
                type="text" 
                placeholder="Your Company Support"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reply-To Address</label>
            <input 
              v-model="form.reply_to_address" 
              type="email" 
              placeholder="support@yourcompany.com"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p class="text-xs text-gray-500 mt-1">If different from the From address</p>
          </div>

        </div>
      </div>

      <!-- Processing Settings -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Processing Settings</h3>
          <p class="text-sm text-gray-500 mt-1">Configure how emails are processed by the system</p>
        </div>
        <div class="p-6 space-y-6">
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-900">Auto-create Tickets</label>
                <p class="text-xs text-gray-500">Create tickets from incoming emails</p>
              </div>
              <button
                type="button"
                @click="form.auto_create_tickets = !form.auto_create_tickets"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                  form.auto_create_tickets ? 'bg-indigo-600' : 'bg-gray-200'
                ]"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    form.auto_create_tickets ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>

            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-900">Process Commands</label>
                <p class="text-xs text-gray-500">Execute commands in emails</p>
              </div>
              <button
                type="button"
                @click="form.process_commands = !form.process_commands"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                  form.process_commands ? 'bg-indigo-600' : 'bg-gray-200'
                ]"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    form.process_commands ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>

            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-900">Send Confirmations</label>
                <p class="text-xs text-gray-500">Send confirmation emails</p>
              </div>
              <button
                type="button"
                @click="form.send_confirmations = !form.send_confirmations"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2',
                  form.send_confirmations ? 'bg-indigo-600' : 'bg-gray-200'
                ]"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    form.send_confirmations ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Retry Attempts</label>
            <input 
              v-model="form.max_retries" 
              type="number" 
              min="0" 
              max="10"
              class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <p class="text-xs text-gray-500 mt-1">Number of times to retry failed email processing</p>
          </div>

        </div>
      </div>

    </form>

    <!-- Test Results -->
    <div v-if="testResults" class="mt-8 bg-white shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Test Results</h3>
        <p class="text-sm text-gray-500 mt-1">Latest configuration test results</p>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <!-- Overall Status -->
          <div class="flex items-center">
            <CheckCircleIcon v-if="testResults.overall" class="h-5 w-5 text-green-500 mr-2" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2" />
            <span class="font-medium" :class="testResults.overall ? 'text-green-900' : 'text-red-900'">
              Overall: {{ testResults.overall ? 'Success' : 'Failed' }}
            </span>
          </div>

          <!-- Incoming Test -->
          <div v-if="testResults.incoming" class="flex items-start">
            <CheckCircleIcon v-if="testResults.incoming.success" class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2 mt-0.5" />
            <div>
              <div class="font-medium" :class="testResults.incoming.success ? 'text-green-900' : 'text-red-900'">
                Incoming: {{ testResults.incoming.message }}
              </div>
              <div class="text-sm text-gray-600">{{ testResults.incoming.details }}</div>
            </div>
          </div>

          <!-- Outgoing Test -->
          <div v-if="testResults.outgoing" class="flex items-start">
            <CheckCircleIcon v-if="testResults.outgoing.success" class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
            <XCircleIcon v-else class="h-5 w-5 text-red-500 mr-2 mt-0.5" />
            <div>
              <div class="font-medium" :class="testResults.outgoing.success ? 'text-green-900' : 'text-red-900'">
                Outgoing: {{ testResults.outgoing.message }}
              </div>
              <div class="text-sm text-gray-600">{{ testResults.outgoing.details }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  EyeIcon, 
  EyeSlashIcon, 
  XCircleIcon 
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  }
})

// State
const saving = ref(false)
const testing = ref(false)
const showIncomingPassword = ref(false)
const showOutgoingPassword = ref(false)
const testResults = ref(null)

// Form data
const form = useForm({
  // System status
  system_active: false,
  
  // Incoming configuration
  incoming_enabled: false,
  incoming_provider: '',
  incoming_host: '',
  incoming_port: 993,
  incoming_username: '',
  incoming_password: '',
  incoming_encryption: 'ssl',
  incoming_folder: 'INBOX',
  
  // Outgoing configuration
  outgoing_enabled: false,
  outgoing_provider: '',
  outgoing_host: '',
  outgoing_port: 587,
  outgoing_username: '',
  outgoing_password: '',
  outgoing_encryption: 'tls',
  
  // From address configuration
  from_address: '',
  from_name: '',
  reply_to_address: '',
  
  // Processing settings
  auto_create_tickets: true,
  process_commands: true,
  send_confirmations: true,
  max_retries: 3,
})

// Computed
const config = computed(() => props.config)
const hasChanges = computed(() => form.isDirty)

// Provider defaults
const providerDefaults = {
  outgoing: {
    smtp: { port: 587, encryption: 'tls' },
    gmail: { host: 'smtp.gmail.com', port: 587, encryption: 'tls' },
    outlook: { host: 'smtp-mail.outlook.com', port: 587, encryption: 'starttls' },
    ses: { port: 587, encryption: 'tls' },
    sendgrid: { host: 'smtp.sendgrid.net', port: 587, encryption: 'tls' },
    postmark: { host: 'smtp.postmarkapp.com', port: 587, encryption: 'tls' },
    mailgun: { host: 'smtp.mailgun.org', port: 587, encryption: 'tls' },
  },
  incoming: {
    imap: { port: 993, encryption: 'ssl', folder: 'INBOX' },
    gmail: { host: 'imap.gmail.com', port: 993, encryption: 'ssl', folder: 'INBOX' },
    outlook: { host: 'outlook.office365.com', port: 993, encryption: 'ssl', folder: 'INBOX' },
    exchange: { port: 993, encryption: 'ssl', folder: 'INBOX' },
  },
}

// Methods
function applyProviderDefaults(type) {
  const provider = type === 'incoming' ? form.incoming_provider : form.outgoing_provider
  const defaults = providerDefaults[type][provider]
  
  if (defaults) {
    Object.keys(defaults).forEach(key => {
      const formKey = `${type}_${key}`
      if (form[formKey] !== undefined) {
        form[formKey] = defaults[key]
      }
    })
  }
}

async function saveConfiguration() {
  saving.value = true
  try {
    await form.put('/api/email-system/config', {
      onSuccess: () => {
        // Show success notification
        console.log('Configuration saved successfully')
      },
      onError: (errors) => {
        console.error('Save failed:', errors)
      }
    })
  } finally {
    saving.value = false
  }
}

async function testConfiguration() {
  testing.value = true
  try {
    const response = await fetch('/api/email-system/test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form.data())
    })
    
    if (response.ok) {
      testResults.value = await response.json()
    }
  } catch (error) {
    console.error('Test failed:', error)
  } finally {
    testing.value = false
  }
}

function formatDateTime(dateTime) {
  if (!dateTime) return ''
  return new Date(dateTime).toLocaleString()
}

// Initialize form with existing config
onMounted(() => {
  if (config.value) {
    Object.keys(form.data()).forEach(key => {
      if (config.value[key] !== undefined) {
        form[key] = config.value[key]
      }
    })
    
    // Set test results if available
    if (config.value.test_results) {
      testResults.value = config.value.test_results
    }
  }
})
</script>