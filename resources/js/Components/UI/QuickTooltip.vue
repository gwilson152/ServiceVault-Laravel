<template>
  <div class="relative inline-block" @mouseenter="showTooltip" @mouseleave="hideTooltip">
    <!-- Trigger element -->
    <div ref="trigger" class="trigger">
      <slot></slot>
    </div>
    
    <!-- Tooltip - teleported to body to avoid clipping -->
    <Teleport to="body">
      <div 
        v-if="content && isVisible"
        ref="tooltip"
        class="absolute z-[9999] px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-lg pointer-events-none"
        :class="maxWidth"
        :style="tooltipStyle"
        role="tooltip"
      >
        {{ content }}
        
        <!-- Arrow -->
        <div 
          class="absolute w-2 h-2 bg-gray-900 transform rotate-45"
          :style="arrowStyle"
        ></div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
  content: {
    type: String,
    required: true
  },
  position: {
    type: String,
    default: 'auto',
    validator: (value) => ['top', 'bottom', 'left', 'right', 'auto'].includes(value)
  },
  maxWidth: {
    type: String,
    default: 'max-w-xs'
  }
})

const trigger = ref(null)
const tooltip = ref(null)
const isVisible = ref(false)
const tooltipPosition = ref({ x: 0, y: 0 })
const actualPosition = ref('top')

const showTooltip = async () => {
  isVisible.value = true
  await nextTick()
  calculatePosition()
}

const hideTooltip = () => {
  isVisible.value = false
}

const calculatePosition = () => {
  if (!trigger.value || !tooltip.value) return
  
  const triggerRect = trigger.value.getBoundingClientRect()
  const tooltipRect = tooltip.value.getBoundingClientRect()
  const viewportWidth = window.innerWidth
  const viewportHeight = window.innerHeight
  const padding = 8
  
  let position = props.position
  let x = 0
  let y = 0
  
  // Auto-detect best position if position is 'auto'
  if (position === 'auto') {
    const spaceTop = triggerRect.top
    const spaceBottom = viewportHeight - triggerRect.bottom
    const spaceLeft = triggerRect.left
    const spaceRight = viewportWidth - triggerRect.right
    
    if (spaceRight >= tooltipRect.width + padding) {
      position = 'right'
    } else if (spaceLeft >= tooltipRect.width + padding) {
      position = 'left'
    } else if (spaceTop >= tooltipRect.height + padding) {
      position = 'top'
    } else {
      position = 'bottom'
    }
  }
  
  actualPosition.value = position
  
  switch (position) {
    case 'top':
      x = triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2
      y = triggerRect.top - tooltipRect.height - padding
      break
    case 'bottom':
      x = triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2
      y = triggerRect.bottom + padding
      break
    case 'left':
      x = triggerRect.left - tooltipRect.width - padding
      y = triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2
      break
    case 'right':
      x = triggerRect.right + padding
      y = triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2
      break
  }
  
  // Keep tooltip within viewport bounds
  x = Math.max(padding, Math.min(x, viewportWidth - tooltipRect.width - padding))
  y = Math.max(padding, Math.min(y, viewportHeight - tooltipRect.height - padding))
  
  tooltipPosition.value = { x, y }
}

const tooltipStyle = computed(() => ({
  left: `${tooltipPosition.value.x}px`,
  top: `${tooltipPosition.value.y}px`,
}))

const arrowStyle = computed(() => {
  const offset = 6
  
  switch (actualPosition.value) {
    case 'top':
      return {
        top: '100%',
        left: '50%',
        transform: 'translateX(-50%) translateY(-50%)',
      }
    case 'bottom':
      return {
        bottom: '100%',
        left: '50%',
        transform: 'translateX(-50%) translateY(50%)',
      }
    case 'left':
      return {
        left: '100%',
        top: '50%',
        transform: 'translateY(-50%) translateX(-50%)',
      }
    case 'right':
      return {
        right: '100%',
        top: '50%',
        transform: 'translateY(-50%) translateX(50%)',
      }
    default:
      return {}
  }
})
</script>