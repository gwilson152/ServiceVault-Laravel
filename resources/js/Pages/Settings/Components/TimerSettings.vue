<template>
  <div class="space-y-8">
    <!-- Timer Settings Header -->
    <div>
      <h2 class="text-2xl font-semibold text-gray-900">Timer Settings</h2>
      <p class="text-gray-600 mt-2">Configure timer behavior, synchronization, and default settings for time tracking.</p>
    </div>

    <!-- Timer Behavior Settings -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Timer Behavior</h3>
      
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Allow Concurrent Timers</label>
            <p class="text-xs text-gray-500 mt-1">Allow users to run multiple timers simultaneously</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="allow_concurrent_timers"
              v-model="form.allow_concurrent_timers"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="allow_concurrent_timers" class="ml-2 text-sm text-gray-600">
              {{ form.allow_concurrent_timers ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Default Auto-Stop</label>
            <p class="text-xs text-gray-500 mt-1">Automatically stop other timers when starting a new one</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="default_auto_stop"
              v-model="form.default_auto_stop"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="default_auto_stop" class="ml-2 text-sm text-gray-600">
              {{ form.default_auto_stop ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Auto-Commit on Stop</label>
            <p class="text-xs text-gray-500 mt-1">Automatically convert timers to time entries when stopped</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="auto_commit_on_stop"
              v-model="form.auto_commit_on_stop"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="auto_commit_on_stop" class="ml-2 text-sm text-gray-600">
              {{ form.auto_commit_on_stop ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Require Description</label>
            <p class="text-xs text-gray-500 mt-1">Require users to enter a description when starting timers</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="require_description"
              v-model="form.require_description"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="require_description" class="ml-2 text-sm text-gray-600">
              {{ form.require_description ? 'Required' : 'Optional' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Default Billable Status</label>
            <p class="text-xs text-gray-500 mt-1">Default billable status for new timers and time entries</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="default_billable"
              v-model="form.default_billable"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="default_billable" class="ml-2 text-sm text-gray-600">
              {{ form.default_billable ? 'Billable' : 'Non-billable' }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Synchronization Settings -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Cross-Device Synchronization</h3>
      
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Sync Interval</label>
          <div class="mt-1 flex items-center space-x-2">
            <input 
              type="number" 
              v-model.number="form.sync_interval_seconds"
              min="1" 
              max="60"
              class="block w-24 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-600">seconds</span>
          </div>
          <p class="text-xs text-gray-500 mt-1">How often timers synchronize across devices (1-60 seconds)</p>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Sync Performance Guide</h4>
          <div class="space-y-2 text-xs text-gray-600">
            <div class="flex justify-between">
              <span>1-2 seconds:</span>
              <span class="font-medium text-green-600">Real-time (Higher server load)</span>
            </div>
            <div class="flex justify-between">
              <span>3-5 seconds:</span>
              <span class="font-medium text-blue-600">Near real-time (Recommended)</span>
            </div>
            <div class="flex justify-between">
              <span>10-30 seconds:</span>
              <span class="font-medium text-yellow-600">Moderate sync (Lower server load)</span>
            </div>
            <div class="flex justify-between">
              <span>30+ seconds:</span>
              <span class="font-medium text-gray-600">Slow sync (Minimal server load)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timer Validation Rules -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Timer Validation</h3>
      
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Minimum Timer Duration</label>
          <div class="mt-1 flex items-center space-x-2">
            <input 
              type="number" 
              v-model.number="form.min_timer_duration_minutes"
              min="0" 
              max="60"
              class="block w-24 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-600">minutes</span>
          </div>
          <p class="text-xs text-gray-500 mt-1">Minimum duration required for timer entries (0 = no limit)</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Maximum Timer Duration</label>
          <div class="mt-1 flex items-center space-x-2">
            <input 
              type="number" 
              v-model.number="form.max_timer_duration_hours"
              min="1" 
              max="24"
              class="block w-24 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <span class="text-sm text-gray-600">hours</span>
          </div>
          <p class="text-xs text-gray-500 mt-1">Maximum duration for a single timer session (1-24 hours)</p>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Auto-Stop Long Timers</label>
            <p class="text-xs text-gray-500 mt-1">Automatically stop timers that exceed maximum duration</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="auto_stop_long_timers"
              v-model="form.auto_stop_long_timers"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="auto_stop_long_timers" class="ml-2 text-sm text-gray-600">
              {{ form.auto_stop_long_timers ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Timer Display Settings -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-6">Display Settings</h3>
      
      <div class="space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Time Display Format</label>
          <select 
            v-model="form.time_display_format"
            class="mt-1 block w-full max-w-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="hms">Hours:Minutes:Seconds (2:30:45)</option>
            <option value="hm">Hours:Minutes (2:30)</option>
            <option value="decimal">Decimal Hours (2.5)</option>
          </select>
          <p class="text-xs text-gray-500 mt-1">How timer durations are displayed to users</p>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Show Timer Overlay</label>
            <p class="text-xs text-gray-500 mt-1">Display floating timer overlay on all pages</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="show_timer_overlay"
              v-model="form.show_timer_overlay"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="show_timer_overlay" class="ml-2 text-sm text-gray-600">
              {{ form.show_timer_overlay ? 'Enabled' : 'Disabled' }}
            </label>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Play Sound on Timer Events</label>
            <p class="text-xs text-gray-500 mt-1">Audio notifications for timer start/stop events</p>
          </div>
          <div class="flex items-center">
            <input 
              type="checkbox" 
              id="play_timer_sounds"
              v-model="form.play_timer_sounds"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="play_timer_sounds" class="ml-2 text-sm text-gray-600">
              {{ form.play_timer_sounds ? 'Enabled' : 'Disabled' }}
            </label>
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
        {{ loading ? 'Saving...' : 'Save Timer Settings' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

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
  // Timer Behavior
  default_auto_stop: false,
  allow_concurrent_timers: true,
  auto_commit_on_stop: false,
  require_description: true,
  default_billable: true,
  
  // Synchronization
  sync_interval_seconds: 5,
  
  // Validation
  min_timer_duration_minutes: 0,
  max_timer_duration_hours: 8,
  auto_stop_long_timers: false,
  
  // Display
  time_display_format: 'hms',
  show_timer_overlay: true,
  play_timer_sounds: false,
})

// Watch for settings prop changes
watch(() => props.settings, (newSettings) => {
  Object.assign(form, newSettings)
}, { immediate: true, deep: true })

// Save settings
const saveSettings = () => {
  emit('update', { ...form })
}
</script>