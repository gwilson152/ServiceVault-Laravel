<template>
  <StackedDialog 
    @close="$emit('close')" 
    :show="true" 
    :fullscreen="true"
    title="FreeScout Import Configuration"
  >
    <div class="flex flex-col h-full">
      <!-- Header with profile info -->
      <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">{{ profile.name }}</h2>
            <p class="text-sm text-gray-600">{{ profile.instance_url }}</p>
          </div>
          <div class="flex items-center space-x-2 text-sm text-gray-500">
            <span>{{ statistics.conversations.toLocaleString() }} conversations</span>
            <span>•</span>
            <span>{{ statistics.customers.toLocaleString() }} customers</span>
            <span>•</span>
            <span>{{ statistics.mailboxes.toLocaleString() }} mailboxes</span>
          </div>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="flex-shrink-0 border-b border-gray-200 bg-white">
        <div class="relative">
          <!-- Scroll container -->
          <div class="px-6 overflow-x-auto scrollbar-hide" ref="tabScrollContainer">
            <nav class="flex space-x-8 min-w-max" aria-label="Tabs">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  activeTab === tab.id
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center flex-shrink-0'
                ]"
              >
                <component :is="tab.icon" class="w-4 h-4 mr-2" />
                {{ tab.name }}
                <span v-if="tab.badge" class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ tab.badge }}
                </span>
              </button>
            </nav>
          </div>
          
          <!-- Gradient fade for scroll indication -->
          <div class="absolute top-0 right-0 bottom-0 w-8 bg-gradient-to-l from-white to-transparent pointer-events-none"></div>
        </div>
      </div>

      <!-- Tab Content - Scrollable -->
      <div class="flex-1 overflow-auto">
        <div class="p-6">
          <!-- Basic Settings Tab -->
          <div v-show="activeTab === 'basic'" class="space-y-8">
            <BasicSettingsTab 
              :config="config"
              :preview-data="previewData"
              :statistics="statistics"
              @update-config="updateConfig"
            />
          </div>


          <!-- Advanced Options Tab -->
          <div v-show="activeTab === 'advanced'" class="space-y-8">
            <AdvancedOptionsTab 
              :config="config"
              :preview-data="previewData"
              :statistics="statistics"
              @update-config="updateConfig"
            />
          </div>

          <!-- Summary Tab -->
          <div v-show="activeTab === 'summary'" class="space-y-8">
            <SummaryTab 
              :config="config"
              :preview-data="previewData"
              :profile="profile"
              :statistics="statistics"
              @preview="$emit('preview', $event)"
              @execute="$emit('execute', $event)"
            />
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="flex-shrink-0 px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        
        <div class="flex space-x-3">
          <button
            @click="$emit('preview', config)"
            class="px-4 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-300 rounded-md hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Preview Import
          </button>
          
          <button
            @click="handleSave"
            :disabled="savingConfig"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="savingConfig" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Saving...
            </span>
            <span v-else>Save Configuration</span>
          </button>
          
          <button
            @click="$emit('execute', config)"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Start Import
          </button>
        </div>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import BasicSettingsTab from './ImportConfigTabs/BasicSettingsTab.vue'
import AdvancedOptionsTab from './ImportConfigTabs/AdvancedOptionsTab.vue'
import SummaryTab from './ImportConfigTabs/SummaryTab.vue'
import {
  AdjustmentsHorizontalIcon,
  CogIcon,
  DocumentCheckIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  profile: {
    type: Object,
    required: true
  },
  previewData: {
    type: Object,
    required: true
  },
  loadingPreview: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['close', 'preview', 'save', 'execute'])

// Template refs
const tabScrollContainer = ref(null)

// Loading states
const savingConfig = ref(false)

// Tab configuration
const activeTab = ref('basic')
const tabs = [
  { id: 'basic', name: 'Import Settings', icon: AdjustmentsHorizontalIcon },
  { id: 'advanced', name: 'Advanced Configuration', icon: CogIcon },
  { id: 'summary', name: 'Review & Execute', icon: DocumentCheckIcon }
]

// Methods
const handleSave = async () => {
  savingConfig.value = true
  try {
    emit('save', config.value)
  } finally {
    // Let parent handle resetting this after save completes
    setTimeout(() => {
      savingConfig.value = false
    }, 2000)
  }
}

// Configuration state (simplified)
const config = ref({
  date_range: {
    start_date: null,
    end_date: null
  },
  account_strategy: 'domain_mapping_strict',
  agent_import_strategy: 'match_existing',
  continue_on_error: true,
  // Deprecated fields for backward compatibility with advanced tab
  sync_strategy: 'create_only',
  duplicate_detection: 'external_id',
  excluded_mailboxes: []
})

// Computed statistics from profile
const statistics = computed(() => {
  return {
    conversations: parseInt(props.profile.stats?.conversations?.replace(/,/g, '') || '0'),
    customers: parseInt(props.profile.stats?.customers?.replace(/,/g, '') || '0'),
    mailboxes: parseInt(props.profile.stats?.mailboxes?.replace(/,/g, '') || '0'),
    time_entries: props.previewData?.time_entries?.length || 0 // FreeScout doesn't always have time entry stats
  }
})

// Methods
const updateConfig = (updates) => {
  config.value = { ...config.value, ...updates }
}
</script>

<style scoped>
/* Hide scrollbar but keep functionality */
.scrollbar-hide {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;  /* Chrome, Safari, Opera */
}

/* Smooth scrolling and touch support */
.scrollbar-hide {
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch; /* iOS momentum scrolling */
  overscroll-behavior-x: contain; /* Prevent over-scroll effects */
}

/* Additional mobile-friendly touch scrolling */
@media (max-width: 768px) {
  .scrollbar-hide {
    scroll-snap-type: x proximity; /* Snap to tabs on mobile */
  }
  
  .scrollbar-hide nav button {
    scroll-snap-align: center;
  }
}
</style>