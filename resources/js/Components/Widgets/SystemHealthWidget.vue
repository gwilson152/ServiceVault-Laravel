<template>
  <div class="system-health-widget">
    <!-- Health Status Grid -->
    <div class="grid grid-cols-2 gap-3 mb-4">
      <div class="health-item">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600">Database</span>
          <span :class="getHealthBadgeClass(widgetData?.database)" class="px-2 py-1 text-xs font-medium rounded-full">
            {{ widgetData?.database || 'unknown' }}
          </span>
        </div>
      </div>
      
      <div class="health-item">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600">Redis</span>
          <span :class="getHealthBadgeClass(widgetData?.redis)" class="px-2 py-1 text-xs font-medium rounded-full">
            {{ widgetData?.redis || 'unknown' }}
          </span>
        </div>
      </div>
      
      <div class="health-item">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600">Storage</span>
          <span :class="getHealthBadgeClass(widgetData?.storage_disk)" class="px-2 py-1 text-xs font-medium rounded-full">
            {{ widgetData?.storage_disk || 'unknown' }}
          </span>
        </div>
      </div>
      
      <div class="health-item">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600">Overall</span>
          <span :class="getHealthBadgeClass(overallHealth)" class="px-2 py-1 text-xs font-medium rounded-full">
            {{ overallHealth }}
          </span>
        </div>
      </div>
    </div>

    <!-- Queue Information -->
    <div class="queue-info">
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600">Queue Jobs</span>
        <span class="font-medium">{{ widgetData?.queue_jobs || 0 }}</span>
      </div>
      <div class="flex items-center justify-between text-sm mt-1">
        <span class="text-gray-600">Failed Jobs</span>
        <span class="font-medium text-red-600">{{ widgetData?.failed_jobs || 0 }}</span>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-4 pt-3 border-t border-gray-100">
      <button 
        @click="refreshHealth"
        :disabled="isRefreshing"
        class="w-full text-sm text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
      >
        <span v-if="isRefreshing">Refreshing...</span>
        <span v-else>Refresh Status</span>
      </button>
    </div>
  </div>
</template>


<script setup>
import { computed, ref } from 'vue'

// Props
const props = defineProps({
  widgetData: {
    type: Object,
    default: () => ({})
  },
  widgetConfig: {
    type: Object,
    default: () => ({})
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['refresh', 'configure'])

// State
const isRefreshing = ref(false)

// Computed
const overallHealth = computed(() => {
  if (!props.widgetData) return 'unknown'
  
  const services = [
    props.widgetData.database,
    props.widgetData.redis,
    props.widgetData.storage_disk
  ]
  
  if (services.some(service => service === 'error')) {
    return 'error'
  }
  
  if (services.some(service => service === 'warning')) {
    return 'warning'
  }
  
  if (services.every(service => service === 'healthy')) {
    return 'healthy'
  }
  
  return 'unknown'
})

// Methods
const getHealthBadgeClass = (status) => {
  switch (status) {
    case 'healthy':
      return 'bg-green-100 text-green-800'
    case 'warning':
      return 'bg-yellow-100 text-yellow-800'
    case 'error':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const refreshHealth = () => {
  isRefreshing.value = true
  emit('refresh')
  setTimeout(() => {
    isRefreshing.value = false
  }, 1000)
}
</script>

<style scoped>
.health-item {
  padding: 0.5rem 0;
}

.queue-info {
  background-color: #f9fafb;
  padding: 0.75rem;
  border-radius: 0.375rem;
  border: 1px solid #e5e7eb;
}
</style>