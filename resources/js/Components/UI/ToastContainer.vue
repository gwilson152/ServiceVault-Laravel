<template>
  <Teleport to="body">
    <div
      v-for="position in activePositions"
      :key="position"
      :class="getContainerClasses(position)"
      class="fixed z-50 p-4 pointer-events-none"
    >
      <TransitionGroup
        tag="div"
        name="toast-list"
        class="space-y-3"
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
        move-class="transition-transform duration-200"
      >
        <Toast
          v-for="toast in getToastsForPosition(position)"
          :key="toast.id"
          v-bind="toast"
          @close="removeToast"
          @action="handleToastAction"
          @mouseenter="pauseToast(toast.id)"
          @mouseleave="resumeToast(toast.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import Toast from './Toast.vue'
import { useToastStore } from '@/Stores/toastStore'

const toastStore = useToastStore()

// Get unique positions that have toasts
const activePositions = computed(() => {
  const positions = new Set()
  toastStore.toasts.forEach(toast => {
    positions.add(toast.position || 'top-right')
  })
  return Array.from(positions)
})

// Get toasts for a specific position
const getToastsForPosition = (position) => {
  return toastStore.toasts.filter(toast => (toast.position || 'top-right') === position)
}

// Position-based container classes
const getContainerClasses = (position) => {
  const positions = {
    'top-left': 'top-0 left-0',
    'top-right': 'top-0 right-0',
    'top-center': 'top-0 left-1/2 transform -translate-x-1/2',
    'bottom-left': 'bottom-0 left-0',
    'bottom-right': 'bottom-0 right-0',
    'bottom-center': 'bottom-0 left-1/2 transform -translate-x-1/2'
  }
  
  return positions[position] || positions['top-right']
}

// Methods
const removeToast = (toastId) => {
  toastStore.remove(toastId)
}

const handleToastAction = (toastId) => {
  const toast = toastStore.toasts.find(t => t.id === toastId)
  if (toast && toast.onAction) {
    toast.onAction()
  }
}

const pauseToast = (toastId) => {
  // Could implement pause functionality here if needed
}

const resumeToast = (toastId) => {
  // Could implement resume functionality here if needed
}
</script>

<script>
export default {
  name: 'ToastContainer'
}
</script>

<style scoped>
/* Custom list transition styles */
.toast-list-move,
.toast-list-enter-active,
.toast-list-leave-active {
  transition: all 0.2s ease;
}

.toast-list-enter-from,
.toast-list-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-10px);
}

.toast-list-leave-active {
  position: absolute;
  right: 0;
  width: 100%;
}
</style>