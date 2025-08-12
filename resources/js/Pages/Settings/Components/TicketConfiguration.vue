<template>
  <div class="space-y-8">
    <!-- Ticket Configuration Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Ticket Configuration</h2>
      <p class="text-gray-600 mt-2">Manage ticket statuses, categories, and workflow settings.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <template v-else>
      <!-- Ticket Statuses -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Statuses</h3>
          <a 
            href="/api/ticket-statuses"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Manage Statuses
          </a>
        </div>
        
        <div v-if="statuses && statuses.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="status in statuses" 
            :key="status.id"
            class="flex items-center p-3 border rounded-lg"
            :class="status.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50'"
          >
            <div 
              class="w-3 h-3 rounded-full mr-3"
              :style="{ backgroundColor: status.color }"
            ></div>
            <div class="flex-1">
              <div class="text-sm font-medium text-gray-900">{{ status.name }}</div>
              <div class="text-xs text-gray-500">
                {{ status.is_closed ? 'Closed' : 'Open' }}
                {{ status.is_default ? '(Default)' : '' }}
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket statuses configured.
        </div>
      </div>

      <!-- Ticket Categories -->
      <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-medium text-gray-900">Ticket Categories</h3>
          <a 
            href="/api/ticket-categories"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Manage Categories
          </a>
        </div>
        
        <div v-if="categories && categories.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="category in categories" 
            :key="category.id"
            class="p-4 border rounded-lg"
            :class="category.is_default ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50'"
          >
            <div class="flex items-center mb-2">
              <div 
                class="w-3 h-3 rounded-full mr-3"
                :style="{ backgroundColor: category.color }"
              ></div>
              <h4 class="text-sm font-medium text-gray-900">{{ category.name }}</h4>
              <span v-if="category.is_default" class="ml-2 text-xs text-indigo-600">(Default)</span>
            </div>
            
            <div class="text-xs text-gray-600 space-y-1">
              <div v-if="category.sla_hours">SLA: {{ category.sla_hours }} hours</div>
              <div v-if="category.default_estimated_hours">Est: {{ category.default_estimated_hours }}h</div>
              <div v-if="category.requires_approval" class="text-yellow-600">Requires Approval</div>
            </div>
          </div>
        </div>
        
        <div v-else class="text-sm text-gray-500 text-center py-4">
          No ticket categories configured.
        </div>
      </div>

      <!-- Workflow Configuration -->
      <div v-if="workflowTransitions" class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Workflow Transitions</h3>
        
        <div class="space-y-4">
          <div 
            v-for="(transitions, fromStatus) in workflowTransitions" 
            :key="fromStatus"
            class="p-4 bg-gray-50 rounded-lg"
          >
            <div class="text-sm font-medium text-gray-900 mb-2">
              From: <span class="capitalize">{{ fromStatus.replace('_', ' ') }}</span>
            </div>
            <div v-if="transitions.length > 0" class="flex flex-wrap gap-2">
              <span 
                v-for="toStatus in transitions" 
                :key="toStatus"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
              >
                {{ toStatus.replace('_', ' ') }}
              </span>
            </div>
            <div v-else class="text-xs text-gray-500 italic">
              No transitions allowed (final state)
            </div>
          </div>
        </div>
      </div>

      <!-- Refresh Button -->
      <div class="flex justify-end">
        <button
          type="button"
          @click="$emit('refresh')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh Configuration
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

defineEmits(['refresh'])

const statuses = computed(() => props.config.statuses || [])
const categories = computed(() => props.config.categories || [])
const workflowTransitions = computed(() => props.config.workflow_transitions || {})
</script>