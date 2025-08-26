<template>
  <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
      <h4 class="text-sm font-medium text-gray-900">Available Variables</h4>
      <div class="flex space-x-2">
        <select
          v-model="selectedCategory"
          class="text-xs border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="">All Categories</option>
          <option v-for="category in categories" :key="category.key" :value="category.key">
            {{ category.label }}
          </option>
        </select>
        
        <input
          v-model="searchTerm"
          type="text"
          placeholder="Search variables..."
          class="text-xs border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 w-32"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="category in filteredCategories" :key="category.key" class="space-y-2">
        <h5 class="text-xs font-medium text-gray-700 uppercase tracking-wide">
          {{ category.label }}
        </h5>
        <div class="space-y-1">
          <button
            v-for="variable in category.variables"
            :key="variable.key"
            @click="insertVariable(variable.key)"
            class="flex items-start w-full text-left p-2 text-xs bg-white border border-gray-200 rounded hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
            :title="variable.description"
          >
            <div class="flex-1">
              <div class="font-mono text-indigo-600">
                {{ variable.key }}
              </div>
              <div class="text-gray-600 mt-1">{{ variable.label }}</div>
              <div v-if="variable.example" class="text-gray-500 italic mt-1">
                e.g., "{{ variable.example }}"
              </div>
            </div>
            <PlusIcon class="h-3 w-3 text-gray-400 mt-0.5 ml-2" />
          </button>
        </div>
      </div>
    </div>

    <!-- Advanced Variables Section -->
    <details v-if="advancedVariables.length > 0" class="mt-4">
      <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
        Advanced Variables & Formatting
      </summary>
      <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="variable in advancedVariables" :key="variable.key" class="space-y-1">
          <button
            @click="insertVariable(variable.key)"
            class="flex items-start w-full text-left p-2 text-xs bg-white border border-gray-200 rounded hover:bg-indigo-50 hover:border-indigo-300"
            :title="variable.description"
          >
            <div class="flex-1">
              <div class="font-mono text-purple-600">
                {{ variable.key }}
              </div>
              <div class="text-gray-600 mt-1">{{ variable.label }}</div>
              <div v-if="variable.example" class="text-gray-500 italic mt-1">
                {{ variable.example }}
              </div>
            </div>
            <PlusIcon class="h-3 w-3 text-gray-400 mt-0.5 ml-2" />
          </button>
        </div>
      </div>
    </details>

    <!-- Variable Format Examples -->
    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
      <h5 class="text-xs font-medium text-blue-800 mb-2">Supported Variable Formats:</h5>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
        <code class="bg-blue-100 px-2 py-1 rounded text-blue-700">variable</code>
        <code class="bg-blue-100 px-2 py-1 rounded text-blue-700">{variable}</code>
        <code class="bg-blue-100 px-2 py-1 rounded text-blue-700">[variable]</code>
        <code class="bg-blue-100 px-2 py-1 rounded text-blue-700">$variable$</code>
        <code class="bg-blue-100 px-2 py-1 rounded text-blue-700">${variable}</code>
        <code class="bg-blue-100 px-2 py-1 rounded text-blue-700">%variable%</code>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { PlusIcon } from '@heroicons/vue/20/solid'

const emit = defineEmits(['insert'])

// State
const selectedCategory = ref('')
const searchTerm = ref('')

// Variable Categories
const categories = [
  {
    key: 'ticket',
    label: 'Ticket Variables',
    variables: [
      {
        key: 'ticket.id',
        label: 'Ticket ID',
        description: 'Unique ticket identifier',
        example: 'f47ac10b-58cc-4372-a567-0e02b2c3d479'
      },
      {
        key: 'ticket.number',
        label: 'Ticket Number',
        description: 'Human-readable ticket number',
        example: 'TKT-2025-0001'
      },
      {
        key: 'ticket.subject',
        label: 'Ticket Subject',
        description: 'The ticket subject/title',
        example: 'Email server is down'
      },
      {
        key: 'ticket.description',
        label: 'Ticket Description',
        description: 'Full ticket description',
        example: 'The email server has been...'
      },
      {
        key: 'ticket.status',
        label: 'Ticket Status',
        description: 'Current ticket status',
        example: 'Open'
      },
      {
        key: 'ticket.priority',
        label: 'Ticket Priority',
        description: 'Ticket priority level',
        example: 'High'
      },
      {
        key: 'ticket.category',
        label: 'Ticket Category',
        description: 'Ticket category',
        example: 'Technical Support'
      },
      {
        key: 'ticket.created_at',
        label: 'Created Date',
        description: 'When the ticket was created',
        example: 'January 15, 2025'
      },
      {
        key: 'ticket.updated_at',
        label: 'Updated Date',
        description: 'When the ticket was last updated',
        example: 'January 16, 2025'
      },
      {
        key: 'ticket.due_at',
        label: 'Due Date',
        description: 'When the ticket is due',
        example: 'January 20, 2025'
      }
    ]
  },
  {
    key: 'user',
    label: 'User Variables',
    variables: [
      {
        key: 'user.id',
        label: 'User ID',
        description: 'Unique user identifier',
        example: 'a12b34c5-d678-90ef-1234-567890abcdef'
      },
      {
        key: 'user.name',
        label: 'User Name',
        description: 'Full name of the user',
        example: 'John Doe'
      },
      {
        key: 'user.first_name',
        label: 'First Name',
        description: 'User\'s first name',
        example: 'John'
      },
      {
        key: 'user.last_name',
        label: 'Last Name',
        description: 'User\'s last name',
        example: 'Doe'
      },
      {
        key: 'user.email',
        label: 'User Email',
        description: 'User\'s email address',
        example: 'john.doe@company.com'
      },
      {
        key: 'user.role',
        label: 'User Role',
        description: 'User\'s role in the system',
        example: 'Manager'
      },
      {
        key: 'user.title',
        label: 'Job Title',
        description: 'User\'s job title',
        example: 'IT Manager'
      },
      {
        key: 'user.phone',
        label: 'Phone Number',
        description: 'User\'s phone number',
        example: '+1 (555) 123-4567'
      }
    ]
  },
  {
    key: 'account',
    label: 'Account Variables',
    variables: [
      {
        key: 'account.id',
        label: 'Account ID',
        description: 'Unique account identifier',
        example: 'acc-12345'
      },
      {
        key: 'account.name',
        label: 'Account Name',
        description: 'Name of the account/company',
        example: 'Acme Corporation'
      },
      {
        key: 'account.contact_name',
        label: 'Primary Contact',
        description: 'Primary contact person',
        example: 'Jane Smith'
      },
      {
        key: 'account.contact_email',
        label: 'Contact Email',
        description: 'Primary contact email',
        example: 'jane.smith@acme.com'
      },
      {
        key: 'account.phone',
        label: 'Account Phone',
        description: 'Account phone number',
        example: '+1 (555) 987-6543'
      },
      {
        key: 'account.address',
        label: 'Account Address',
        description: 'Account billing address',
        example: '123 Business St, City, ST 12345'
      }
    ]
  },
  {
    key: 'agent',
    label: 'Agent/Assigned User Variables',
    variables: [
      {
        key: 'agent.name',
        label: 'Agent Name',
        description: 'Name of assigned agent',
        example: 'Sarah Wilson'
      },
      {
        key: 'agent.email',
        label: 'Agent Email',
        description: 'Agent\'s email address',
        example: 'sarah.wilson@company.com'
      },
      {
        key: 'agent.title',
        label: 'Agent Title',
        description: 'Agent\'s job title',
        example: 'Senior Support Specialist'
      },
      {
        key: 'agent.phone',
        label: 'Agent Phone',
        description: 'Agent\'s phone number',
        example: '+1 (555) 456-7890'
      }
    ]
  },
  {
    key: 'time_entry',
    label: 'Time Entry Variables',
    variables: [
      {
        key: 'time_entry.duration',
        label: 'Duration',
        description: 'Time entry duration',
        example: '2 hours 30 minutes'
      },
      {
        key: 'time_entry.description',
        label: 'Description',
        description: 'Time entry description',
        example: 'Fixed email server issue'
      },
      {
        key: 'time_entry.date',
        label: 'Entry Date',
        description: 'Date of time entry',
        example: 'January 15, 2025'
      },
      {
        key: 'time_entry.rate',
        label: 'Billing Rate',
        description: 'Hourly billing rate',
        example: '$150.00/hour'
      }
    ]
  },
  {
    key: 'invoice',
    label: 'Invoice Variables',
    variables: [
      {
        key: 'invoice.id',
        label: 'Invoice ID',
        description: 'Unique invoice identifier',
        example: 'INV-2025-0001'
      },
      {
        key: 'invoice.number',
        label: 'Invoice Number',
        description: 'Invoice number',
        example: 'INV-2025-0001'
      },
      {
        key: 'invoice.total',
        label: 'Invoice Total',
        description: 'Total invoice amount',
        example: '$1,250.00'
      },
      {
        key: 'invoice.due_date',
        label: 'Due Date',
        description: 'Invoice due date',
        example: 'February 15, 2025'
      },
      {
        key: 'invoice.status',
        label: 'Invoice Status',
        description: 'Current invoice status',
        example: 'Sent'
      }
    ]
  },
  {
    key: 'system',
    label: 'System Variables',
    variables: [
      {
        key: 'system.name',
        label: 'System Name',
        description: 'Name of your ServiceVault instance',
        example: 'ServiceVault Support'
      },
      {
        key: 'system.url',
        label: 'System URL',
        description: 'Base URL of your system',
        example: 'https://support.company.com'
      },
      {
        key: 'system.support_email',
        label: 'Support Email',
        description: 'System support email address',
        example: 'support@company.com'
      },
      {
        key: 'system.support_phone',
        label: 'Support Phone',
        description: 'System support phone number',
        example: '+1 (555) 123-HELP'
      }
    ]
  }
]

// Advanced Variables (conditional, loops, formatting)
const advancedVariables = [
  {
    key: '#if ticket.priority == "High"',
    label: 'Conditional Block Start',
    description: 'Show content only if condition is true',
    example: 'Use with {{#endif}} to close'
  },
  {
    key: '/if',
    label: 'Conditional Block End',
    description: 'Closes conditional block',
    example: 'Closes {{#if}} block'
  },
  {
    key: '#each time_entries',
    label: 'Loop Block Start',
    description: 'Loop through array items',
    example: 'Use with {{/each}} to close'
  },
  {
    key: '/each',
    label: 'Loop Block End',
    description: 'Closes loop block',
    example: 'Closes {{#each}} block'
  },
  {
    key: 'date:Y-m-d',
    label: 'Date Format',
    description: 'Format date variables',
    example: '{{ticket.created_at:Y-m-d}} → 2025-01-15'
  },
  {
    key: 'currency',
    label: 'Currency Format',
    description: 'Format currency values',
    example: '{{invoice.total:currency}} → $1,250.00'
  }
]

// Computed
const filteredCategories = computed(() => {
  let filtered = categories

  // Filter by category
  if (selectedCategory.value) {
    filtered = categories.filter(cat => cat.key === selectedCategory.value)
  }

  // Filter by search term
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.map(category => ({
      ...category,
      variables: category.variables.filter(variable =>
        variable.key.toLowerCase().includes(search) ||
        variable.label.toLowerCase().includes(search) ||
        variable.description.toLowerCase().includes(search)
      )
    })).filter(category => category.variables.length > 0)
  }

  return filtered
})

// Methods
function insertVariable(variableKey) {
  emit('insert', variableKey)
}
</script>