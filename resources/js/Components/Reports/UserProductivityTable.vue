<template>
  <div class="p-6">
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>
    <div v-else-if="!data || data.length === 0" class="text-center py-12 text-gray-500">
      <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">No user data</h3>
      <p class="mt-1 text-sm text-gray-500">User productivity metrics will be displayed here.</p>
    </div>
    <div v-else>
      <!-- User productivity table content would go here -->
      <div class="text-center text-gray-500">
        <p>User productivity analytics coming soon</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { UserGroupIcon } from '@heroicons/vue/24/outline'

defineProps({
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})
</script>