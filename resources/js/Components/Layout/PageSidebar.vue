<template>
  <div class="space-y-4">
    <slot>
      <!-- Default empty - parent provides sidebar content -->
    </slot>
  </div>
</template>

<script setup>
// This component is a simple wrapper that provides consistent spacing
// for sidebar widgets and content. The actual collapsible behavior
// is handled by the StandardPageLayout component.
</script>

<style scoped>
/* Ensure consistent spacing between sidebar items */
.space-y-4 > * + * {
  margin-top: 1rem;
}
</style>