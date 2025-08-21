<template>
  <div>
    <!-- Desktop header (hidden on mobile since mobile has collapsible button) -->
    <div class="p-4 border-b border-gray-200 hidden lg:block">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ title }}
      </h3>
    </div>
    
    <!-- Filter content -->
    <div class="p-4">
      <slot>
        <!-- Default empty - parent provides filter controls -->
      </slot>
    </div>
  </div>
</template>

<script setup>
// Props
defineProps({
  title: {
    type: String,
    default: 'Filters & Search'
  }
})
</script>