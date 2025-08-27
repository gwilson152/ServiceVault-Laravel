<template>
  <div class="space-y-8">
    <!-- Email Processing Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Email Processing Configuration</h2>
      <p class="text-gray-600 mt-2">
        Configure how incoming emails are processed, including automatic user creation, domain mapping, and ticket routing.
      </p>
    </div>

    <!-- Processing Strategy Configuration -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Email Processing Strategy</h3>
      
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Enable Email Processing</label>
            <p class="text-xs text-gray-500 mt-1">Process incoming emails to create tickets and manage users</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="enable_email_processing"
              v-model="form.enable_email_processing"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="enable_email_processing" class="ml-2 text-sm text-gray-600">
              {{ form.enable_email_processing ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div v-if="form.enable_email_processing" class="ml-6 pl-6 border-l-2 border-gray-200 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Auto Create Tickets</label>
            <div class="flex items-center mt-2">
              <input 
                type="checkbox" 
                id="auto_create_tickets"
                v-model="form.auto_create_tickets"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="auto_create_tickets" class="ml-2 text-sm text-gray-600">
                Automatically create tickets from incoming emails
              </label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Auto Create Users</label>
            <div class="flex items-center mt-2">
              <input 
                type="checkbox" 
                id="auto_create_users"
                v-model="form.auto_create_users"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="auto_create_users" class="ml-2 text-sm text-gray-600">
                Automatically create user accounts for unknown email addresses
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Unmapped Domain Strategy -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Unmapped Domain Strategy</h3>
      <p class="text-sm text-gray-600 mb-4">
        Configure how to handle emails from domains that don't have specific domain mappings configured.
      </p>

      <div class="space-y-4">
        <div class="space-y-3">
          <label>
            <input 
              type="radio" 
              name="unmapped_strategy" 
              value="create_account" 
              v-model="form.unmapped_domain_strategy"
              class="mr-3"
            />
            <span class="text-sm font-medium text-gray-700">Create New Account</span>
            <p class="text-xs text-gray-500 ml-6">Automatically create a new business account for the domain</p>
          </label>

          <label>
            <input 
              type="radio" 
              name="unmapped_strategy" 
              value="assign_default_account" 
              v-model="form.unmapped_domain_strategy"
              class="mr-3"
            />
            <span class="text-sm font-medium text-gray-700">Assign to Default Account</span>
            <p class="text-xs text-gray-500 ml-6">Route to a specified default business account</p>
          </label>

          <div v-if="form.unmapped_domain_strategy === 'assign_default_account'" class="ml-6 pl-4 border-l-2 border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-2">Default Account</label>
            <select 
              v-model="form.default_account_id"
              class="block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              required
            >
              <option value="">Select Default Account...</option>
              <option 
                v-for="account in availableAccounts"
                :key="account.id"
                :value="account.id"
              >
                {{ account.name }} ({{ account.account_type }})
              </option>
            </select>
          </div>

          <label>
            <input 
              type="radio" 
              name="unmapped_strategy" 
              value="queue_for_review" 
              v-model="form.unmapped_domain_strategy"
              class="mr-3"
            />
            <span class="text-sm font-medium text-gray-700">Queue for Manual Review</span>
            <p class="text-xs text-gray-500 ml-6">Hold emails for admin to manually assign domain mappings</p>
          </label>

          <label>
            <input 
              type="radio" 
              name="unmapped_strategy" 
              value="reject" 
              v-model="form.unmapped_domain_strategy"
              class="mr-3"
            />
            <span class="text-sm font-medium text-gray-700">Reject Email</span>
            <p class="text-xs text-gray-500 ml-6">Reject emails from unmapped domains (emails will be ignored)</p>
          </label>
        </div>
      </div>
    </div>

    <!-- Auto User Creation Settings -->
    <div v-if="form.auto_create_users" class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Auto User Creation Settings</h3>
      
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Default Role Template for New Users</label>
          <select 
            v-model="form.default_role_template_id"
            class="mt-1 block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">No Default Role...</option>
            <option 
              v-for="roleTemplate in availableRoleTemplates"
              :key="roleTemplate.id"
              :value="roleTemplate.id"
            >
              {{ roleTemplate.name }} ({{ roleTemplate.context }})
            </option>
          </select>
          <p class="text-xs text-gray-500 mt-1">Role template to assign to new auto-created users</p>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Require Email Verification</label>
            <p class="text-xs text-gray-500 mt-1">New users must verify their email address before accessing the system</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="require_email_verification"
              v-model="form.require_email_verification"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="require_email_verification" class="ml-2 text-sm text-gray-600">
              {{ form.require_email_verification ? 'Required' : 'Optional' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Require Admin Approval</label>
            <p class="text-xs text-gray-500 mt-1">New auto-created accounts need admin approval before activation</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="require_admin_approval"
              v-model="form.require_admin_approval"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="require_admin_approval" class="ml-2 text-sm text-gray-600">
              {{ form.require_admin_approval ? 'Required' : 'Not Required' }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Domain Mapping Integration -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Email Domain Routing</h3>
        <a 
          :href="route('settings.email.domain-mappings')"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Manage Domain Mappings
        </a>
      </div>
      
      <p class="text-sm text-gray-600 mb-4">
        Email domain mappings control how incoming emails are routed to business accounts based on sender domain patterns.
        This integrates with the processing strategy above for complete email workflow automation.
      </p>

      <div v-if="domainMappingsPreview && domainMappingsPreview.length > 0" class="space-y-3">
        <div class="text-sm font-medium text-gray-700">Active Domain Mappings:</div>
        <div class="space-y-2">
          <div 
            v-for="mapping in domainMappingsPreview" 
            :key="mapping.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
          >
            <div>
              <div class="text-sm font-medium text-gray-900">{{ mapping.domain_pattern }}</div>
              <div class="text-xs text-gray-500">
                → {{ mapping.account_name }} 
                <span v-if="mapping.role_name">({{ mapping.role_name }})</span>
                <span v-if="mapping.auto_create_tickets" class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded">Auto Tickets</span>
              </div>
            </div>
            <div class="text-xs text-gray-500">
              Priority: {{ mapping.priority }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="text-sm text-gray-500 italic">
        No domain mappings configured. All emails will use the unmapped domain strategy above.
      </div>
    </div>

    <!-- Processing Workflow -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Email Processing Workflow</h3>
      
      <div class="space-y-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">How Email Processing Works</h4>
          <ol class="text-xs text-blue-800 space-y-1 list-decimal list-inside">
            <li>Incoming email received from email service</li>
            <li>System checks for existing EmailDomainMapping patterns (highest priority)</li>
            <li>If found: Route to mapped account with specified ticket creation settings</li>
            <li>If not found: Apply unmapped domain strategy configured above</li>
            <li>Create or find user account (with domain-specific role if mapped)</li>
            <li>Create ticket if auto-creation is enabled for this routing</li>
            <li>Send email verification if required for new users</li>
            <li>Queue for admin approval if required for new accounts</li>
          </ol>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-yellow-900 mb-2">Processing Priority Order</h4>
          <ol class="text-xs text-yellow-800 space-y-1 list-decimal list-inside">
            <li><strong>EmailDomainMapping</strong>: Specific email patterns (@company.com, support@company.com)</li>
            <li><strong>General DomainMapping</strong>: User creation patterns (*.company.com for user management)</li>
            <li><strong>Unmapped Domain Strategy</strong>: Fallback behavior configured above</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- User Management Statistics -->
    <div v-if="userStats" class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Email Processing Statistics</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-900">{{ userStats.total_users || 0 }}</div>
          <div class="text-sm text-gray-500">Total Users</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ userStats.active_users || 0 }}</div>
          <div class="text-sm text-gray-500">Active Users</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-yellow-600">{{ userStats.pending_approval || 0 }}</div>
          <div class="text-sm text-gray-500">Pending Approval</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ userStats.auto_created || 0 }}</div>
          <div class="text-sm text-gray-500">Auto-Created</div>
        </div>
      </div>

      <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-lg font-bold text-indigo-600">{{ userStats.emails_processed_today || 0 }}</div>
            <div class="text-sm text-gray-500">Emails Processed Today</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-bold text-green-600">{{ userStats.tickets_created_today || 0 }}</div>
            <div class="text-sm text-gray-500">Tickets Created Today</div>
          </div>
          <div class="text-center">
            <div class="text-lg font-bold text-purple-600">{{ userStats.users_created_today || 0 }}</div>
            <div class="text-sm text-gray-500">Users Created Today</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-end">
      <button
        type="button"
        @click="saveSettings"
        :disabled="loading"
        :class="[
          'inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm',
          loading
            ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
            : 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        ]"
      >
        <span v-if="loading" class="mr-2">
          <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </span>
        {{ loading ? 'Saving...' : 'Save Email Processing Settings' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update'])

// Form data with defaults
const form = reactive({
  enable_email_processing: true,
  auto_create_tickets: true,
  auto_create_users: true,
  unmapped_domain_strategy: 'assign_default_account',
  default_account_id: '',
  default_role_template_id: '',
  require_email_verification: true,
  require_admin_approval: true,
})

// Extract data from settings prop
const availableAccounts = computed(() => props.settings.accounts || [])
const availableRoleTemplates = computed(() => props.settings.role_templates || [])
const domainMappingsPreview = computed(() => props.settings.domain_mappings_preview || [])
const userStats = computed(() => props.settings.user_stats || null)

// Watch for settings prop changes
watch(() => props.settings, (newSettings) => {
  if (newSettings.email_processing_settings) {
    Object.assign(form, newSettings.email_processing_settings)
  }
}, { immediate: true, deep: true })

// Save settings
const saveSettings = () => {
  emit('update', { ...form })
}
</script>