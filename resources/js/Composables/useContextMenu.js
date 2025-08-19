import { ref, nextTick } from 'vue'

export function useContextMenu() {
  const showContextMenu = ref(false)
  const contextMenuX = ref(0)
  const contextMenuY = ref(0)
  const contextMenuTarget = ref(null)

  const openContextMenu = (event, target = null) => {
    event.preventDefault()
    
    contextMenuX.value = event.clientX
    contextMenuY.value = event.clientY
    contextMenuTarget.value = target
    showContextMenu.value = true
    
    // Close menu when clicking outside
    nextTick(() => {
      const closeMenu = () => {
        showContextMenu.value = false
        document.removeEventListener('click', closeMenu)
        document.removeEventListener('contextmenu', closeMenu)
      }
      
      document.addEventListener('click', closeMenu)
      document.addEventListener('contextmenu', closeMenu)
    })
  }

  const closeContextMenu = () => {
    showContextMenu.value = false
    contextMenuTarget.value = null
  }

  return {
    showContextMenu,
    contextMenuX,
    contextMenuY,
    contextMenuTarget,
    openContextMenu,
    closeContextMenu
  }
}