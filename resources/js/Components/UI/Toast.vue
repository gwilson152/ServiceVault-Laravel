<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 scale-95 translate-y-2"
    enter-to-class="opacity-100 scale-100 translate-y-0"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 scale-100 translate-y-0"
    leave-to-class="opacity-0 scale-95 translate-y-2"
  >
    <div
      v-if="visible"
      :class="toastClasses"
      class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg backdrop-blur-md border border-white/10 max-w-sm w-full"
      role="alert"
      :aria-live="type === 'error' ? 'assertive' : 'polite'"
      @mouseenter="handleMouseEnter"
      @mouseleave="handleMouseLeave"
    >
      <!-- Icon -->
      <div class="flex-shrink-0" v-if="showIcon">
        <component :is="iconComponent" :class="iconClasses" class="h-5 w-5" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div v-if="title" :class="titleClasses" class="font-medium text-sm leading-5">
          {{ title }}
        </div>
        <div v-if="message" :class="messageClasses" class="text-sm leading-5" :class="{ 'mt-1': title }">
          {{ message }}
        </div>
        
        <!-- Action Button -->
        <div v-if="actionText" class="mt-2">
          <button
            @click="handleAction"
            :class="actionButtonClasses"
            class="text-sm font-medium hover:underline focus:outline-none focus:underline transition-colors duration-150"
          >
            {{ actionText }}
          </button>
        </div>
      </div>

      <!-- Close Button -->
      <div class="flex-shrink-0" v-if="closeable">
        <button
          @click="close"
          :class="closeButtonClasses"
          class="rounded-md p-1 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20 transition-colors duration-150"
          aria-label="Close notification"
        >
          <XMarkIcon class="h-4 w-4" />
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { 
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  title: {
    type: String,
    default: null
  },
  message: {
    type: String,
    required: true
  },
  duration: {
    type: Number,
    default: 5000
  },
  persistent: {
    type: Boolean,
    default: false
  },
  closeable: {
    type: Boolean,
    default: true
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  actionText: {
    type: String,
    default: null
  },
  onAction: {
    type: Function,
    default: null
  },
  position: {
    type: String,
    default: 'top-right',
    validator: (value) => ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'top-center', 'bottom-center'].includes(value)
  }
})

const emit = defineEmits(['close', 'action'])

const visible = ref(false)
let timeoutId = null

// Toast type configurations
const typeConfig = {
  success: {
    icon: CheckCircleIcon,
    bg: 'bg-green-500/10',
    border: 'border-green-500/20',
    iconColor: 'text-green-400',
    titleColor: 'text-green-100',
    messageColor: 'text-green-200/90',
    actionColor: 'text-green-300 hover:text-green-200',
    closeColor: 'text-green-300/70 hover:text-green-200'
  },
  error: {
    icon: XCircleIcon,
    bg: 'bg-red-500/10',
    border: 'border-red-500/20',
    iconColor: 'text-red-400',
    titleColor: 'text-red-100',
    messageColor: 'text-red-200/90',
    actionColor: 'text-red-300 hover:text-red-200',
    closeColor: 'text-red-300/70 hover:text-red-200'
  },
  warning: {
    icon: ExclamationTriangleIcon,
    bg: 'bg-yellow-500/10',
    border: 'border-yellow-500/20',
    iconColor: 'text-yellow-400',
    titleColor: 'text-yellow-100',
    messageColor: 'text-yellow-200/90',
    actionColor: 'text-yellow-300 hover:text-yellow-200',
    closeColor: 'text-yellow-300/70 hover:text-yellow-200'
  },
  info: {
    icon: InformationCircleIcon,
    bg: 'bg-blue-500/10',
    border: 'border-blue-500/20',
    iconColor: 'text-blue-400',
    titleColor: 'text-blue-100',
    messageColor: 'text-blue-200/90',
    actionColor: 'text-blue-300 hover:text-blue-200',
    closeColor: 'text-blue-300/70 hover:text-blue-200'
  }
}

// Computed properties for styling
const config = computed(() => typeConfig[props.type])

const toastClasses = computed(() => [
  config.value.bg,
  config.value.border
])

const iconComponent = computed(() => config.value.icon)
const iconClasses = computed(() => config.value.iconColor)
const titleClasses = computed(() => config.value.titleColor)
const messageClasses = computed(() => config.value.messageColor)
const actionButtonClasses = computed(() => config.value.actionColor)
const closeButtonClasses = computed(() => config.value.closeColor)

// Methods
const close = () => {
  visible.value = false
  if (timeoutId) {
    clearTimeout(timeoutId)
  }
  
  // Wait for transition to complete before emitting close
  setTimeout(() => {
    emit('close', props.id)
  }, 200)
}

const handleAction = () => {
  if (props.onAction) {
    props.onAction()
  }
  emit('action', props.id)
  
  // Close toast after action unless it's persistent
  if (!props.persistent) {
    close()
  }
}

const startTimer = () => {
  if (!props.persistent && props.duration > 0) {
    timeoutId = setTimeout(() => {
      close()
    }, props.duration)
  }
}

const pauseTimer = () => {
  if (timeoutId) {
    clearTimeout(timeoutId)
  }
}

// Lifecycle
onMounted(() => {
  // Small delay to trigger enter animation
  setTimeout(() => {
    visible.value = true
    startTimer()
  }, 50)
})

// Pause timer on hover, resume on leave
const handleMouseEnter = () => {
  pauseTimer()
}

const handleMouseLeave = () => {
  startTimer()
}

defineExpose({
  close,
  pauseTimer,
  startTimer
})
</script>

<script>
export default {
  name: 'Toast'
}
</script>