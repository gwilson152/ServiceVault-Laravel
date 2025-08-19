<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999]"
      @click="$emit('close')"
    >
      <div
        :style="{
          left: x + 'px',
          top: y + 'px'
        }"
        class="absolute bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-48"
        @click.stop
      >
        <slot :item="target" :close="() => $emit('close')" />
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false
  },
  x: {
    type: Number,
    required: true
  },
  y: {
    type: Number,
    required: true
  },
  target: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])
</script>