<template>
  <StackedDialog
    :show="show"
    @close="$emit('close')"
    size="xl"
    title="Email Command Help & Documentation"
  >
    <div class="space-y-8">
      <!-- Overview -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">How Email Commands Work</h3>
        <div class="prose max-w-none text-sm">
          <p>
            Email commands allow you to perform actions on tickets by including special commands in your email subject line. 
            This feature enables automation and quick updates without needing to access the web interface.
          </p>
          <p>
            Commands use the format <code>command:value</code> and can be combined in the same email subject. 
            The email will be processed normally (creating comments, etc.) and then the commands will be executed.
          </p>
        </div>
      </div>

      <!-- Command Syntax -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Command Syntax</h3>
        <div class="bg-gray-50 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Basic Format</h4>
              <code class="bg-white px-3 py-2 rounded border text-sm block">command:value</code>
              <p class="text-xs text-gray-600 mt-1">No spaces around the colon</p>
            </div>
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Multiple Commands</h4>
              <code class="bg-white px-3 py-2 rounded border text-sm block">time:30 priority:high</code>
              <p class="text-xs text-gray-600 mt-1">Separate with spaces</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Command Reference -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Complete Command Reference</h3>
        
        <div class="space-y-6">
          <!-- Time Command -->
          <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-base font-semibold text-gray-900">time</h4>
              <span class="inline-flex px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                Most Used
              </span>
            </div>
            <p class="text-sm text-gray-600 mb-3">Add a time entry to the ticket</p>
            
            <div class="space-y-2">
              <div>
                <strong class="text-sm">Formats:</strong>
                <div class="mt-1 space-x-2">
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">time:30</code>
                  <span class="text-xs text-gray-500">30 minutes</span>
                </div>
                <div class="mt-1 space-x-2">
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">time:1.5h</code>
                  <span class="text-xs text-gray-500">1 hour 30 minutes</span>
                </div>
                <div class="mt-1 space-x-2">
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">time:90m</code>
                  <span class="text-xs text-gray-500">90 minutes</span>
                </div>
              </div>
              
              <div>
                <strong class="text-sm">Example:</strong>
                <div class="mt-1 p-2 bg-blue-50 rounded">
                  <code class="text-xs">Re: Server Issue - Fixed DNS configuration time:45</code>
                </div>
              </div>
            </div>
          </div>

          <!-- Priority Command -->
          <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-base font-semibold text-gray-900 mb-3">priority</h4>
            <p class="text-sm text-gray-600 mb-3">Set the ticket priority level</p>
            
            <div class="space-y-2">
              <div>
                <strong class="text-sm">Valid Values:</strong>
                <div class="mt-1 flex flex-wrap gap-2">
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">low</code>
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">medium</code>
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">high</code>
                  <code class="bg-red-100 px-2 py-1 rounded text-xs text-red-800">urgent</code>
                </div>
              </div>
              
              <div>
                <strong class="text-sm">Example:</strong>
                <div class="mt-1 p-2 bg-blue-50 rounded">
                  <code class="text-xs">Re: System Down - priority:urgent status:open</code>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Command -->
          <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-base font-semibold text-gray-900 mb-3">status</h4>
            <p class="text-sm text-gray-600 mb-3">Update the ticket status</p>
            
            <div class="space-y-2">
              <div>
                <strong class="text-sm">Common Values:</strong>
                <div class="mt-1 flex flex-wrap gap-2">
                  <code class="bg-blue-100 px-2 py-1 rounded text-xs text-blue-800">open</code>
                  <code class="bg-yellow-100 px-2 py-1 rounded text-xs text-yellow-800">in-progress</code>
                  <code class="bg-purple-100 px-2 py-1 rounded text-xs text-purple-800">pending</code>
                  <code class="bg-green-100 px-2 py-1 rounded text-xs text-green-800">resolved</code>
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs">closed</code>
                </div>
              </div>
              
              <div>
                <strong class="text-sm">Example:</strong>
                <div class="mt-1 p-2 bg-blue-50 rounded">
                  <code class="text-xs">Re: Email Issue - status:resolved time:120</code>
                </div>
              </div>
            </div>
          </div>

          <!-- Assign Command -->
          <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-base font-semibold text-gray-900 mb-3">assign</h4>
            <p class="text-sm text-gray-600 mb-3">Assign the ticket to a specific agent</p>
            
            <div class="space-y-2">
              <div>
                <strong class="text-sm">Formats:</strong>
                <div class="mt-1 space-y-1">
                  <div>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">assign:john@company.com</code>
                    <span class="text-xs text-gray-500 ml-2">Full email address</span>
                  </div>
                  <div>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">assign:sarah.wilson</code>
                    <span class="text-xs text-gray-500 ml-2">Username only</span>
                  </div>
                  <div>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">assign:me</code>
                    <span class="text-xs text-gray-500 ml-2">Assign to yourself</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Due Date Command -->
          <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-base font-semibold text-gray-900 mb-3">due</h4>
            <p class="text-sm text-gray-600 mb-3">Set or update the ticket due date</p>
            
            <div class="space-y-2">
              <div>
                <strong class="text-sm">Formats:</strong>
                <div class="mt-1 space-y-1">
                  <div>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">due:2025-01-30</code>
                    <span class="text-xs text-gray-500 ml-2">Specific date</span>
                  </div>
                  <div>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">due:+3d</code>
                    <span class="text-xs text-gray-500 ml-2">3 days from now</span>
                  </div>
                  <div>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">due:tomorrow</code>
                    <span class="text-xs text-gray-500 ml-2">Natural language</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tag Command -->
          <div class="border border-gray-200 rounded-lg p-4">
            <h4 class="text-base font-semibold text-gray-900 mb-3">tag</h4>
            <p class="text-sm text-gray-600 mb-3">Add tags to the ticket for better organization</p>
            
            <div class="space-y-2">
              <div>
                <strong class="text-sm">Examples:</strong>
                <div class="mt-1 space-y-1">
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs block w-fit">tag:server-issue</code>
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs block w-fit">tag:follow-up</code>
                  <code class="bg-gray-100 px-2 py-1 rounded text-xs block w-fit">tag:billing-related</code>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Real Examples -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Real-World Examples</h3>
        
        <div class="space-y-4">
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="font-medium text-green-900 mb-2">✅ Good Examples</h4>
            <div class="space-y-2">
              <div>
                <code class="bg-white px-3 py-2 rounded text-sm block">Re: Website Issue - Fixed CSS problem time:90 status:resolved</code>
                <p class="text-xs text-green-700 mt-1">✓ Adds 90 minutes time entry and marks as resolved</p>
              </div>
              <div>
                <code class="bg-white px-3 py-2 rounded text-sm block">Server maintenance scheduled priority:high due:2025-01-30</code>
                <p class="text-xs text-green-700 mt-1">✓ Sets high priority and due date</p>
              </div>
              <div>
                <code class="bg-white px-3 py-2 rounded text-sm block">Re: Database backup time:2h assign:dba@company.com tag:maintenance</code>
                <p class="text-xs text-green-700 mt-1">✓ Multiple commands working together</p>
              </div>
            </div>
          </div>
          
          <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h4 class="font-medium text-red-900 mb-2">❌ Common Mistakes</h4>
            <div class="space-y-2">
              <div>
                <code class="bg-white px-3 py-2 rounded text-sm block">time : 30</code>
                <p class="text-xs text-red-700 mt-1">❌ Spaces around colon</p>
              </div>
              <div>
                <code class="bg-white px-3 py-2 rounded text-sm block">priority=high</code>
                <p class="text-xs text-red-700 mt-1">❌ Wrong separator (use colon, not equals)</p>
              </div>
              <div>
                <code class="bg-white px-3 py-2 rounded text-sm block">status:invalid-status</code>
                <p class="text-xs text-red-700 mt-1">❌ Invalid status value</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Permissions -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Permissions & Security</h3>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-amber-600 mt-0.5" />
            <div class="ml-3">
              <h4 class="text-sm font-medium text-amber-800">Important Security Notes</h4>
              <ul class="mt-2 text-sm text-amber-700 space-y-1">
                <li>• Commands are only executed if you have permission to perform the action</li>
                <li>• Invalid commands are ignored and logged for security</li>
                <li>• Some commands (like assign, priority) may be restricted to managers</li>
                <li>• All command executions are audited and logged</li>
              </ul>
            </div>
          </div>
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
    </template>
  </StackedDialog>
</template>

<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import StackedDialog from '@/Components/Modals/StackedDialog.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close'])
</script>

<style scoped>
.prose code {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
}
</style>