<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="lg"
    title="Test Email Commands"
  >
    <div class="space-y-6">
      <!-- Test Input -->
      <div>
        <label for="test_subject" class="block text-sm font-medium text-gray-700">
          Email Subject with Commands
        </label>
        <textarea
          id="test_subject"
          v-model="testSubject"
          rows="3"
          placeholder="Re: Server Issue - time:45 priority:high status:resolved"
          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        ></textarea>
        <p class="mt-1 text-sm text-gray-500">
          Enter a subject line containing commands to test the parsing and validation
        </p>
      </div>

      <!-- Parse Button -->
      <div class="flex justify-center">
        <button
          @click="parseCommands"
          :disabled="!testSubject.trim() || parsing"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
        >
          <span v-if="parsing" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Parsing...
          </span>
          <span v-else>
            <BeakerIcon class="w-4 h-4 mr-2" />
            Parse Commands
          </span>
        </button>
      </div>

      <!-- Parsing Results -->
      <div v-if="parseResults" class="space-y-4">
        <!-- Summary -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-sm font-medium text-gray-900 mb-2">Parsing Summary</h3>
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-gray-500">Commands Found:</span>
              <span class="ml-2 font-medium">{{ parseResults.commands_found }}</span>
            </div>
            <div>
              <span class="text-gray-500">Valid:</span>
              <span class="ml-2 font-medium text-green-600">{{ parseResults.valid_commands }}</span>
            </div>
            <div>
              <span class="text-gray-500">Invalid:</span>
              <span class="ml-2 font-medium text-red-600">{{ parseResults.invalid_commands }}</span>
            </div>
          </div>
        </div>

        <!-- Valid Commands -->
        <div v-if="parseResults.valid?.length > 0">
          <h3 class="text-sm font-medium text-gray-900 mb-3">✅ Valid Commands</h3>
          <div class="space-y-3">
            <div v-for="command in parseResults.valid" :key="command.command + command.value" 
              class="border border-green-200 rounded-lg p-4 bg-green-50">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center">
                    <code class="bg-white px-2 py-1 rounded text-sm font-mono text-green-800">
                      {{ command.command }}:{{ command.value }}
                    </code>
                    <CheckCircleIcon class="h-5 w-5 text-green-500 ml-2" />
                  </div>
                  <p class="mt-2 text-sm text-green-700">{{ command.description }}</p>
                  <div v-if="command.action" class="mt-2">
                    <span class="text-xs font-medium text-green-800">Action:</span>
                    <span class="text-xs text-green-700 ml-1">{{ command.action }}</span>
                  </div>
                </div>
                <div class="ml-4">
                  <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                    {{ command.command }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Invalid Commands -->
        <div v-if="parseResults.invalid?.length > 0">
          <h3 class="text-sm font-medium text-gray-900 mb-3">❌ Invalid Commands</h3>
          <div class="space-y-3">
            <div v-for="command in parseResults.invalid" :key="command.command + command.value" 
              class="border border-red-200 rounded-lg p-4 bg-red-50">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center">
                    <code class="bg-white px-2 py-1 rounded text-sm font-mono text-red-800">
                      {{ command.command }}:{{ command.value }}
                    </code>
                    <XCircleIcon class="h-5 w-5 text-red-500 ml-2" />
                  </div>
                  <p class="mt-2 text-sm text-red-700">{{ command.error }}</p>
                  <div v-if="command.suggestion" class="mt-2">
                    <span class="text-xs font-medium text-red-800">Suggestion:</span>
                    <code class="text-xs text-red-700 ml-1 bg-white px-1 rounded">{{ command.suggestion }}</code>
                  </div>
                </div>
                <div class="ml-4">
                  <span class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                    Error
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Execution Preview -->
        <div v-if="parseResults.execution_preview" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="text-sm font-medium text-blue-900 mb-2">🔍 Execution Preview</h3>
          <p class="text-sm text-blue-800">{{ parseResults.execution_preview }}</p>
        </div>

        <!-- Warnings -->
        <div v-if="parseResults.warnings?.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h3 class="text-sm font-medium text-yellow-900 mb-2">⚠️ Warnings</h3>
          <ul class="text-sm text-yellow-800 space-y-1">
            <li v-for="warning in parseResults.warnings" :key="warning" class="flex">
              <span class="mr-2">•</span>
              <span>{{ warning }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Sample Commands -->
      <div v-if="!parseResults" class="space-y-4">
        <h3 class="text-sm font-medium text-gray-900">Try These Sample Commands</h3>
        <div class="grid grid-cols-1 gap-3">
          <button
            v-for="sample in sampleCommands"
            :key="sample.command"
            @click="testSubject = sample.command"
            class="text-left p-3 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
          >
            <code class="text-sm font-mono text-indigo-600 block mb-1">{{ sample.command }}</code>
            <p class="text-xs text-gray-600">{{ sample.description }}</p>
          </button>
        </div>
      </div>
    </div>

    <template #actions>
      <button
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        Close
      </button>
      
      <button
        v-if="parseResults"
        @click="resetTest"
        class="px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-300 rounded-md hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
      >
        Test Another
      </button>
    </template>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive } from 'vue'
import {
  BeakerIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/StackedDialog.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])

// State
const parsing = ref(false)
const testSubject = ref('')
const parseResults = ref(null)

// Sample commands for testing
const sampleCommands = [
  {
    command: 'Re: Server Issue - time:45 status:resolved',
    description: 'Add time entry and mark as resolved'
  },
  {
    command: 'Database backup time:2h priority:high',
    description: 'Add 2 hour time entry with high priority'
  },
  {
    command: 'Email fix - time:30 assign:john@company.com tag:email-issue',
    description: 'Multiple commands with assignment and tagging'
  },
  {
    command: 'Maintenance window due:tomorrow status:scheduled',
    description: 'Set due date and status'
  },
  {
    command: 'Invalid: time : 30 priority=urgent',
    description: 'Test invalid syntax (spaces and wrong separators)'
  }
]

// Methods
async function parseCommands() {
  if (!testSubject.value.trim()) return

  try {
    parsing.value = true

    const response = await fetch('/api/email-commands/test-parse', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        subject: testSubject.value
      })
    })

    if (!response.ok) {
      throw new Error('Failed to parse commands')
    }

    const result = await response.json()
    parseResults.value = result

  } catch (error) {
    console.error('Error parsing commands:', error)
    
    // Mock results for development/testing
    parseResults.value = generateMockResults(testSubject.value)
    
  } finally {
    parsing.value = false
  }
}

function resetTest() {
  parseResults.value = null
  testSubject.value = ''
}

// Mock function for development
function generateMockResults(subject) {
  const commandPattern = /(\w+):([^\s]+)/g
  const matches = [...subject.matchAll(commandPattern)]
  
  const validCommands = []
  const invalidCommands = []
  
  const knownCommands = {
    'time': { description: 'Add time entry', action: 'Will add time entry to ticket' },
    'priority': { description: 'Set priority', action: 'Will update ticket priority' },
    'status': { description: 'Update status', action: 'Will change ticket status' },
    'assign': { description: 'Assign ticket', action: 'Will assign ticket to user' },
    'tag': { description: 'Add tag', action: 'Will add tag to ticket' },
    'due': { description: 'Set due date', action: 'Will set ticket due date' }
  }
  
  matches.forEach(match => {
    const [fullMatch, command, value] = match
    
    if (knownCommands[command]) {
      validCommands.push({
        command,
        value,
        description: knownCommands[command].description,
        action: knownCommands[command].action.replace('Will', `Will ${value}`)
      })
    } else {
      invalidCommands.push({
        command,
        value,
        error: `Unknown command "${command}"`,
        suggestion: 'Use: time, priority, status, assign, tag, or due'
      })
    }
  })
  
  // Check for syntax errors
  const spacedCommands = subject.match(/\w+\s*:\s*\w+/g)
  if (spacedCommands) {
    spacedCommands.forEach(cmd => {
      if (cmd.includes(' ')) {
        const [command, value] = cmd.split(':').map(s => s.trim())
        invalidCommands.push({
          command,
          value,
          error: 'Invalid syntax: no spaces allowed around colon',
          suggestion: `${command}:${value}`
        })
      }
    })
  }

  return {
    commands_found: matches.length,
    valid_commands: validCommands.length,
    invalid_commands: invalidCommands.length,
    valid: validCommands,
    invalid: invalidCommands,
    execution_preview: validCommands.length > 0 
      ? `Would execute ${validCommands.length} command(s) successfully`
      : 'No valid commands to execute',
    warnings: invalidCommands.length > 0 
      ? ['Some commands contain errors and will be ignored']
      : []
  }
}
</script>