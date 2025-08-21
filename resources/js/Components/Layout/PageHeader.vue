<template>
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <!-- Header Content -->
    <div class="min-w-0 flex-1">
      <div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ title }}
        </h2>
        <p v-if="subtitle" class="text-sm text-gray-600 mt-1 hidden sm:block">
          {{ subtitle }}
        </p>
      </div>
    </div>
    
    <!-- Header Actions -->
    <div class="flex items-center gap-2">
      <slot name="actions">
        <!-- Default empty - parent provides action buttons -->
      </slot>
    </div>
  </div>
</template>

<script setup>
// Props
defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  }
})
</script>