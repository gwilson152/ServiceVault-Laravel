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
          <h3 class="text-lg font-medium text-gray-900">Active Domain Mappings</h3>
          <p class="text-sm text-gray-500 mt-1">Configure email routing patterns and account assignments</p>
        </div>

        <!-- Empty State -->
        <div class="p-6 text-center">
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
            <span class="text-sm font-medium text-gray-900">0</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Active Patterns</span>
            <span class="text-sm font-medium text-gray-900">0</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Accounts Configured</span>
            <span class="text-sm font-medium text-gray-900">0</span>
          </div>
        </div>
      </div>

      <!-- Pattern Examples -->
      <div class="bg-white shadow rounded-lg p-5">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Pattern Examples</h3>
        <div class="space-y-3 text-sm">
          <div>
            <code class="bg-gray-100 px-2 py-1 rounded">@acme.com</code>
            <p class="text-gray-600 mt-1">Matches any email from acme.com domain</p>
          </div>
          <div>
            <code class="bg-gray-100 px-2 py-1 rounded">support@acme.com</code>
            <p class="text-gray-600 mt-1">Matches exact email address</p>
          </div>
          <div>
            <code class="bg-gray-100 px-2 py-1 rounded">*@acme.com</code>
            <p class="text-gray-600 mt-1">Wildcard pattern for domain</p>
          </div>
        </div>
      </div>
    </template>
  </StandardPageLayout>

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
        <!-- Domain Pattern -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Email Pattern
          </label>
          <input
            v-model="createForm.domain_pattern"
            type="text"
            placeholder="@company.com or support@company.com"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          />
          <p class="text-sm text-gray-500 mt-1">
            Use @ for domain patterns, exact email addresses, or * for wildcards
          </p>
        </div>

        <!-- Pattern Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Pattern Type
          </label>
          <select
            v-model="createForm.pattern_type"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          >
            <option value="domain">Domain (@company.com)</option>
            <option value="email">Exact Email (support@company.com)</option>
            <option value="wildcard">Wildcard (*@company.com)</option>
          </select>
        </div>

        <!-- Account Selection (Placeholder) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Account
          </label>
          <select
            v-model="createForm.account_id"
            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          >
            <option value="">Select Account...</option>
            <!-- Placeholder - would be populated from accounts API -->
          </select>
          <p class="text-sm text-gray-500 mt-1">
            Choose which business account should receive tickets from this email pattern
          </p>
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
import { ref, reactive, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import StandardPageLayout from '@/Layouts/StandardPageLayout.vue'
import { 
  PlusIcon, 
  ArrowLeftIcon, 
  EnvelopeIcon, 
  InformationCircleIcon,
  XMarkIcon 
} from '@heroicons/vue/24/outline'

// State
const showCreateModal = ref(false)
const creating = ref(false)
const createModalRef = ref()

// Create form
const createForm = reactive({
  domain_pattern: '',
  pattern_type: 'domain',
  account_id: '',
  default_priority: 'medium',
  auto_create_tickets: true
})

// Methods
function createMapping() {
  creating.value = true
  // TODO: Implement API call to create domain mapping
  console.log('Creating domain mapping:', createForm)
  
  setTimeout(() => {
    creating.value = false
    showCreateModal.value = false
    // Reset form
    Object.assign(createForm, {
      domain_pattern: '',
      pattern_type: 'domain', 
      account_id: '',
      default_priority: 'medium',
      auto_create_tickets: true
    })
  }, 1000)
}

// Watch modal state to handle dialog element
watch(showCreateModal, (show) => {
  if (show && createModalRef.value) {
    createModalRef.value.showModal()
  } else if (createModalRef.value) {
    createModalRef.value.close()
  }
})
</script>