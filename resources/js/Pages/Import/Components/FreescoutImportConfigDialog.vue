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
            <span>{{ (previewData.conversations?.length || 0).toLocaleString() }} conversations</span>
            <span>•</span>
            <span>{{ (previewData.customers?.length || 0).toLocaleString() }} customers</span>
            <span>•</span>
            <span>{{ (previewData.mailboxes?.length || 0).toLocaleString() }} mailboxes</span>
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
              @update-config="updateConfig"
            />
          </div>

          <!-- Data Analysis Tab -->
          <div v-show="activeTab === 'analysis'" class="space-y-8">
            <DataAnalysisTab 
              :config="config"
              :preview-data="previewData"
              :profile="profile"
              @update-config="updateConfig"
            />
          </div>

          <!-- Advanced Options Tab -->
          <div v-show="activeTab === 'advanced'" class="space-y-8">
            <AdvancedOptionsTab 
              :config="config"
              :preview-data="previewData"
              @update-config="updateConfig"
            />
          </div>

          <!-- Summary Tab -->
          <div v-show="activeTab === 'summary'" class="space-y-8">
            <SummaryTab 
              :config="config"
              :preview-data="previewData"
              :profile="profile"
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
import DataAnalysisTab from './ImportConfigTabs/DataAnalysisTab.vue'
import AdvancedOptionsTab from './ImportConfigTabs/AdvancedOptionsTab.vue'
import SummaryTab from './ImportConfigTabs/SummaryTab.vue'
import {
  AdjustmentsHorizontalIcon,
  ChartBarIcon,
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
const emit = defineEmits(['close', 'preview', 'execute'])

// Template refs
const tabScrollContainer = ref(null)

// Tab configuration
const activeTab = ref('basic')
const tabs = [
  { id: 'basic', name: 'Import Settings', icon: AdjustmentsHorizontalIcon },
  { id: 'analysis', name: 'Data Analysis & Mapping', icon: ChartBarIcon },
  { id: 'advanced', name: 'Advanced Configuration', icon: CogIcon },
  { id: 'summary', name: 'Review & Execute', icon: DocumentCheckIcon }
]

// Configuration state
const config = ref({
  limits: {
    conversations: null,
    time_entries: null,
    customers: null,
    mailboxes: null
  },
  account_strategy: 'map_mailboxes',
  user_strategy: 'map_emails',
  sync_strategy: 'create_only',
  duplicate_detection: 'external_id',
  date_range: {
    enabled: false,
    start_date: null,
    end_date: null
  },
  excluded_mailboxes: [],
  unmapped_user_handling: 'skip',
  conversation_mapping: {
    map_threads_to_comments: true,
    preserve_thread_structure: true
  },
  time_entry_mapping: 'preserve_original'
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