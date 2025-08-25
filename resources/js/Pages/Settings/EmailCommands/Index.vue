<template>
  <Head title="Email Commands" />

  <StandardPageLayout 
    title="Email Subject Commands" 
    :show-sidebar="true"
    :show-filters="false"
  >
    <template #header-actions>
      <div class="flex items-center space-x-3">
        <!-- Help Documentation -->
        <button
          @click="showHelpModal = true"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <QuestionMarkCircleIcon class="w-4 h-4 mr-2" />
          Help & Examples
        </button>

        <!-- Test Commands -->
        <button
          @click="showTestModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <BeakerIcon class="w-4 h-4 mr-2" />
          Test Commands
        </button>
      </div>
    </template>

    <template #main-content>
      <!-- Commands Overview -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <div class="flex">
          <InformationCircleIcon class="h-6 w-6 text-blue-600 mt-0.5" />
          <div class="ml-4">
            <h3 class="text-lg font-medium text-blue-900">Email Subject Commands</h3>
            <p class="mt-2 text-sm text-blue-800">
              Users can include special commands in email subjects to automatically update tickets, add time entries, and perform other actions.
              Commands use the format <code class="bg-blue-100 px-1 rounded">command:value</code> and can be combined in the same subject line.
            </p>
            <div class="mt-3">
              <h4 class="text-sm font-medium text-blue-900">Example:</h4>
              <div class="mt-1 p-2 bg-blue-100 rounded">
                <code class="text-sm text-blue-800">
                  Re: Server Issue - time:45 priority:high status:in-progress
                </code>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Commands List -->
      <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">Available Commands</h3>
              <p class="mt-1 max-w-2xl text-sm text-gray-500">
                {{ enabledCommands.length }} of {{ commands.length }} commands are currently enabled
              </p>
            </div>
            <div class="flex space-x-2">
              <button
                @click="enableAll"
                class="text-sm text-indigo-600 hover:text-indigo-500"
              >
                Enable All
              </button>
              <span class="text-gray-300">|</span>
              <button
                @click="disableAll"
                class="text-sm text-gray-600 hover:text-gray-500"
              >
                Disable All
              </button>
            </div>
          </div>
        </div>

        <div class="divide-y divide-gray-200">
          <div v-for="command in commands" :key="command.name" class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center">
                  <h4 class="text-lg font-medium text-gray-900">
                    {{ command.name }}
                  </h4>
                  <span :class="[
                    'ml-3 inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    command.enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]">
                    {{ command.enabled ? 'Enabled' : 'Disabled' }}
                  </span>
                </div>
                
                <p class="mt-1 text-sm text-gray-600">{{ command.description }}</p>
                
                <!-- Command Examples -->
                <div class="mt-3">
                  <h5 class="text-sm font-medium text-gray-700 mb-2">Examples:</h5>
                  <div class="space-y-1">
                    <div v-for="example in command.examples" :key="example" class="flex items-center">
                      <code class="bg-gray-100 px-2 py-1 rounded text-sm text-gray-800">
                        {{ example }}
                      </code>
                      <button
                        @click="copyToClipboard(example)"
                        class="ml-2 text-gray-400 hover:text-gray-600"
                        title="Copy to clipboard"
                      >
                        <ClipboardDocumentIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Permissions -->
                <div class="mt-4">
                  <h5 class="text-sm font-medium text-gray-700 mb-2">Who can use this command:</h5>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="role in command.allowed_roles" :key="role" 
                      class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-md">
                      {{ role }}
                    </span>
                  </div>
                </div>

                <!-- Usage Statistics -->
                <div v-if="command.stats" class="mt-4 grid grid-cols-3 gap-4 text-sm">
                  <div>
                    <span class="text-gray-500">Total Uses:</span>
                    <span class="ml-2 font-medium">{{ command.stats.total_uses }}</span>
                  </div>
                  <div>
                    <span class="text-gray-500">Success Rate:</span>
                    <span class="ml-2 font-medium">{{ command.stats.success_rate }}%</span>
                  </div>
                  <div>
                    <span class="text-gray-500">Last Used:</span>
                    <span class="ml-2 font-medium">{{ command.stats.last_used || 'Never' }}</span>
                  </div>
                </div>
              </div>

              <div class="ml-6 flex items-center space-x-3">
                <!-- Enable/Disable Toggle -->
                <button
                  @click="toggleCommand(command)"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                    command.enabled ? 'bg-indigo-600' : 'bg-gray-200'
                  ]"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition duration-200 ease-in-out',
                      command.enabled ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>

                <!-- Command Settings -->
                <button
                  @click="configureCommand(command)"
                  class="text-gray-400 hover:text-gray-600"
                  title="Configure command settings"
                >
                  <CogIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Command Builder -->
      <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Interactive Command Builder</h3>
          <p class="mt-1 text-sm text-gray-500">
            Build and test commands interactively to understand the syntax
          </p>
          
          <div class="mt-6 space-y-4">
            <!-- Command Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Select Command</label>
              <select
                v-model="selectedCommand"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="">Choose a command...</option>
                <option v-for="command in enabledCommands" :key="command.name" :value="command.name">
                  {{ command.name }} - {{ command.description }}
                </option>
              </select>
            </div>

            <!-- Command Value Input -->
            <div v-if="selectedCommand">
              <label class="block text-sm font-medium text-gray-700">Command Value</label>
              <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center px-3 py-2 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                  {{ selectedCommand }}:
                </span>
                <input
                  v-model="commandValue"
                  type="text"
                  :placeholder="getCommandPlaceholder(selectedCommand)"
                  class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </div>
            </div>

            <!-- Generated Command -->
            <div v-if="generatedCommand">
              <label class="block text-sm font-medium text-gray-700">Generated Command</label>
              <div class="mt-1 flex rounded-md shadow-sm">
                <input
                  :value="generatedCommand"
                  readonly
                  class="block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                />
                <button
                  @click="copyToClipboard(generatedCommand)"
                  class="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-sm text-gray-700 rounded-md hover:bg-gray-50"
                >
                  <ClipboardDocumentIcon class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Command Preview -->
            <div v-if="commandPreview" class="p-4 bg-blue-50 border border-blue-200 rounded-md">
              <h4 class="text-sm font-medium text-blue-900">What this command will do:</h4>
              <p class="mt-1 text-sm text-blue-800">{{ commandPreview }}</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #sidebar>
      <!-- Command Statistics -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ChartBarIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Commands Used (30 days)</dt>
                <dd class="text-lg font-medium text-gray-900">{{ commandStats.total_commands || 0 }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <div class="text-sm">
            <span class="font-medium text-green-600">{{ commandStats.success_rate || 0 }}%</span>
            <span class="text-gray-500"> success rate</span>
          </div>
        </div>
      </div>

      <!-- Most Used Commands -->
      <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Most Used Commands</h3>
          <div class="space-y-3">
            <div v-for="command in popularCommands" :key="command.name" class="flex justify-between">
              <span class="text-sm text-gray-600">{{ command.name }}</span>
              <span class="text-sm font-medium text-gray-900">{{ command.uses }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Reference -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Reference</h3>
          <div class="space-y-2">
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                Time Commands
              </summary>
              <div class="mt-2 pl-4 space-y-1">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">time:30</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">time:1.5h</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">time:90m</code>
              </div>
            </details>
            
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                Status Commands
              </summary>
              <div class="mt-2 pl-4 space-y-1">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">status:open</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">status:in-progress</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">status:resolved</code>
              </div>
            </details>
            
            <details class="group">
              <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                Priority Commands
              </summary>
              <div class="mt-2 pl-4 space-y-1">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">priority:low</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">priority:high</code>
                <code class="text-xs bg-gray-100 px-2 py-1 rounded block">priority:urgent</code>
              </div>
            </details>
          </div>
        </div>
      </div>
    </template>
  </StandardPageLayout>

  <!-- Command Configuration Modal -->
  <CommandConfigModal
    :show="showConfigModal"
    :command="selectedCommandForConfig"
    @close="closeConfigModal"
    @saved="handleConfigSaved"
  />

  <!-- Help Modal -->
  <CommandHelpModal
    :show="showHelpModal"
    @close="showHelpModal = false"
  />

  <!-- Test Commands Modal -->
  <CommandTestModal
    :show="showTestModal"
    @close="showTestModal = false"
  />
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, reactive, computed, onMounted } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import {
  InformationCircleIcon,
  QuestionMarkCircleIcon,
  BeakerIcon,
  ChartBarIcon,
  ClipboardDocumentIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

// Components
import StandardPageLayout from '@/Components/Layout/StandardPageLayout.vue'
import CommandConfigModal from './Components/CommandConfigModal.vue'
import CommandHelpModal from './Components/CommandHelpModal.vue'
import CommandTestModal from './Components/CommandTestModal.vue'

// State
const showConfigModal = ref(false)
const showHelpModal = ref(false)
const showTestModal = ref(false)
const selectedCommandForConfig = ref(null)
const selectedCommand = ref('')
const commandValue = ref('')

// Query Client
const queryClient = useQueryClient()

// API Queries
const { data: commands = [], isLoading: commandsLoading } = useQuery({
  queryKey: ['email-commands'],
  queryFn: fetchCommands
})

const { data: commandStats = {} } = useQuery({
  queryKey: ['email-command-stats'],
  queryFn: fetchCommandStats
})

// Mutations
const toggleCommandMutation = useMutation({
  mutationFn: ({ command, enabled }) => toggleCommandEnabled(command, enabled),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['email-commands'] })
  }
})

// API Functions
async function fetchCommands() {
  const response = await fetch('/api/email-commands')
  if (!response.ok) throw new Error('Failed to fetch commands')
  return response.json()
}

async function fetchCommandStats() {
  const response = await fetch('/api/email-commands/stats')
  if (!response.ok) throw new Error('Failed to fetch command stats')
  return response.json()
}

async function toggleCommandEnabled(command, enabled) {
  const response = await fetch(`/api/email-commands/${command}/toggle`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ enabled })
  })
  
  if (!response.ok) throw new Error('Failed to toggle command')
  return response.json()
}

// Computed
const enabledCommands = computed(() => {
  return commands.value.filter(cmd => cmd.enabled)
})

const popularCommands = computed(() => {
  return commands.value
    .filter(cmd => cmd.stats?.total_uses > 0)
    .sort((a, b) => (b.stats?.total_uses || 0) - (a.stats?.total_uses || 0))
    .slice(0, 5)
})

const generatedCommand = computed(() => {
  if (!selectedCommand.value || !commandValue.value) return ''
  return `${selectedCommand.value}:${commandValue.value}`
})

const commandPreview = computed(() => {
  if (!selectedCommand.value || !commandValue.value) return ''
  
  const command = commands.value.find(cmd => cmd.name === selectedCommand.value)
  if (!command) return ''
  
  return command.preview_template?.replace('{value}', commandValue.value) || ''
})

// Default Commands Configuration
const defaultCommands = [
  {
    name: 'time',
    description: 'Add a time entry to the ticket',
    examples: ['time:30', 'time:1.5h', 'time:90m'],
    allowed_roles: ['Employee', 'Manager', 'Admin'],
    enabled: true,
    preview_template: 'Add {value} minutes as a time entry to this ticket',
    placeholder: '30 (minutes) or 1.5h'
  },
  {
    name: 'priority',
    description: 'Set the ticket priority level',
    examples: ['priority:high', 'priority:urgent', 'priority:low'],
    allowed_roles: ['Manager', 'Admin'],
    enabled: true,
    preview_template: 'Set ticket priority to {value}',
    placeholder: 'low, medium, high, urgent'
  },
  {
    name: 'status',
    description: 'Update the ticket status',
    examples: ['status:in-progress', 'status:resolved', 'status:closed'],
    allowed_roles: ['Employee', 'Manager', 'Admin'],
    enabled: true,
    preview_template: 'Change ticket status to {value}',
    placeholder: 'open, in-progress, resolved, closed'
  },
  {
    name: 'assign',
    description: 'Assign the ticket to a specific agent',
    examples: ['assign:john@company.com', 'assign:sarah.wilson'],
    allowed_roles: ['Manager', 'Admin'],
    enabled: true,
    preview_template: 'Assign this ticket to {value}',
    placeholder: 'email@company.com or username'
  },
  {
    name: 'due',
    description: 'Set or update the ticket due date',
    examples: ['due:2025-01-30', 'due:+3d', 'due:tomorrow'],
    allowed_roles: ['Employee', 'Manager', 'Admin'],
    enabled: true,
    preview_template: 'Set ticket due date to {value}',
    placeholder: '2025-01-30 or +3d or tomorrow'
  },
  {
    name: 'category',
    description: 'Set the ticket category',
    examples: ['category:technical', 'category:billing', 'category:general'],
    allowed_roles: ['Manager', 'Admin'],
    enabled: true,
    preview_template: 'Set ticket category to {value}',
    placeholder: 'technical, billing, general'
  },
  {
    name: 'billing',
    description: 'Set the billing rate for time entries',
    examples: ['billing:standard', 'billing:premium', 'billing:consulting'],
    allowed_roles: ['Manager', 'Admin'],
    enabled: true,
    preview_template: 'Use {value} billing rate for time entries',
    placeholder: 'rate name or hourly amount'
  },
  {
    name: 'tag',
    description: 'Add tags to the ticket',
    examples: ['tag:urgent', 'tag:server-issue', 'tag:follow-up'],
    allowed_roles: ['Employee', 'Manager', 'Admin'],
    enabled: true,
    preview_template: 'Add tag "{value}" to this ticket',
    placeholder: 'tag name'
  },
  {
    name: 'close',
    description: 'Close the ticket immediately',
    examples: ['close', 'close:resolved', 'close:duplicate'],
    allowed_roles: ['Manager', 'Admin'],
    enabled: false,
    preview_template: 'Close this ticket with reason: {value}',
    placeholder: 'reason (optional)'
  },
  {
    name: 'reopen',
    description: 'Reopen a closed ticket',
    examples: ['reopen', 'reopen:additional-work'],
    allowed_roles: ['Employee', 'Manager', 'Admin'],
    enabled: false,
    preview_template: 'Reopen this ticket with reason: {value}',
    placeholder: 'reason (optional)'
  }
]

// Use default commands if no data loaded
const commandsWithDefaults = computed(() => {
  if (commands.value.length > 0) {
    return commands.value
  }
  return defaultCommands
})

// Methods
function toggleCommand(command) {
  const newEnabled = !command.enabled
  toggleCommandMutation.mutate({ 
    command: command.name, 
    enabled: newEnabled 
  })
  
  // Optimistic update
  command.enabled = newEnabled
}

function configureCommand(command) {
  selectedCommandForConfig.value = command
  showConfigModal.value = true
}

function closeConfigModal() {
  showConfigModal.value = false
  selectedCommandForConfig.value = null
}

function handleConfigSaved() {
  closeConfigModal()
  queryClient.invalidateQueries({ queryKey: ['email-commands'] })
}

function enableAll() {
  commandsWithDefaults.value.forEach(command => {
    if (!command.enabled) {
      toggleCommand(command)
    }
  })
}

function disableAll() {
  commandsWithDefaults.value.forEach(command => {
    if (command.enabled) {
      toggleCommand(command)
    }
  })
}

function getCommandPlaceholder(commandName) {
  const command = commandsWithDefaults.value.find(cmd => cmd.name === commandName)
  return command?.placeholder || 'value'
}

async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    // Show success notification
  } catch (error) {
    console.error('Error copying to clipboard:', error)
    // Show error notification
  }
}

// Override commands data with defaults until API is available
if (!commandsLoading.value && commandsWithDefaults.value.length === 0) {
  commands.value = defaultCommands
}
</script>