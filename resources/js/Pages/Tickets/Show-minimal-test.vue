<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-6">
          <div class="flex items-center space-x-4">
            <!-- Back Button -->
            <Link 
              :href="route('tickets.index')" 
              class="text-gray-600 hover:text-gray-900 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </Link>
            
            <!-- Ticket Info -->
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                Ticket #{{ ticketId }}
              </h1>
              <div class="flex items-center space-x-3 mt-1">
                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  Loading...
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="text-center py-8">
        <p class="text-gray-500">Minimal test version - Ticket ID: {{ ticketId }}</p>
        <p class="text-gray-500 mt-2">If this page doesn't cause layout rerender, we know the issue is in the complex components</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Define persistent layout
defineOptions({
  layout: AppLayout
})

// Props
const props = defineProps({
  ticketId: {
    type: String,
    required: true
  }
})

// Simple computed
const ticketId = computed(() => props.ticketId)
</script>