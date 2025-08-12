<template>
  <div class="space-y-8">
    <!-- User Management Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">User Management</h2>
      <p class="text-gray-600 mt-2">Configure automatic user creation, domain mapping, and default account assignment.</p>
    </div>

    <!-- Auto User Creation Settings -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Automatic User Creation</h3>
      
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Enable Auto User Creation</label>
            <p class="text-xs text-gray-500 mt-1">Automatically create user accounts for unknown email addresses</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="enable_auto_user_creation"
              v-model="form.enable_auto_user_creation"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="enable_auto_user_creation" class="ml-2 text-sm text-gray-600">
              {{ form.enable_auto_user_creation ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div v-if="form.enable_auto_user_creation" class="ml-6 pl-6 border-l-2 border-gray-200 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Default Account for New Users</label>
            <select 
              v-model="form.default_account_for_new_users"
              class="mt-1 block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
            <p class="text-xs text-gray-500 mt-1">Account to assign new users when no domain mapping matches</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Default Role Template</label>
            <select 
              v-model="form.default_role_template_for_new_users"
              class="mt-1 block w-full max-w-md border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">Select Default Role...</option>
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
    </div>

    <!-- Domain Mapping Overview -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900">Email Domain Mapping</h3>
        <a 
          href="/dashboard/admin/domain-mappings"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Manage Domain Mappings
        </a>
      </div>
      
      <p class="text-sm text-gray-600 mb-4">
        Domain mapping allows automatic assignment of users to specific accounts and roles based on their email domain. 
        Configure patterns like "*.company.com" to automatically assign users from company.com and its subdomains.
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
              </div>
            </div>
            <div class="text-xs text-gray-500">
              Priority: {{ mapping.priority }}
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="text-sm text-gray-500 italic">
        No domain mappings configured. Users will be assigned to the default account.
      </div>
    </div>

    <!-- User Creation Workflow -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">User Creation Workflow</h3>
      
      <div class="space-y-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">How Automatic User Creation Works</h4>
          <ol class="text-xs text-blue-800 space-y-1 list-decimal list-inside">
            <li>Email received from unknown address</li>
            <li>System checks domain mappings for email domain match</li>
            <li>If match found: User assigned to mapped account and role</li>
            <li>If no match: User assigned to default account and role (if configured)</li>
            <li>Email verification sent (if required)</li>
            <li>Admin approval requested (if required)</li>
            <li>User account activated once all requirements are met</li>
          </ol>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-yellow-900 mb-2">Security Considerations</h4>
          <ul class="text-xs text-yellow-800 space-y-1 list-disc list-inside">
            <li>Auto-created accounts start with minimal permissions</li>
            <li>Email verification prevents unauthorized account creation</li>
            <li>Admin approval adds an additional security layer</li>
            <li>Domain mappings should be carefully configured to prevent unauthorized access</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- User Management Statistics -->
    <div v-if="userStats" class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">User Management Statistics</h3>
      
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
        {{ loading ? 'Saving...' : 'Save User Management Settings' }}
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
  enable_auto_user_creation: false,
  default_account_for_new_users: '',
  default_role_template_for_new_users: '',
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
  if (newSettings.auto_user_settings) {
    Object.assign(form, newSettings.auto_user_settings)
  }
}, { immediate: true, deep: true })

// Save settings
const saveSettings = () => {
  emit('update', { ...form })
}
</script>