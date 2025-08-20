<template>
  <StackedDialog
    :show="show"
    title="Add Items to Invoice"
    max-width="4xl"
    @close="$emit('close')"
  >
    <div class="space-y-6">
      <!-- Search and Filters -->
      <div class="flex items-center space-x-4">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search items..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
        <div>
          <select
            v-model="itemTypeFilter"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">All Types</option>
            <option value="time_entry">Time Entries</option>
            <option value="ticket_addon">Ticket Add-ons</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Items List -->
      <div v-else-if="filteredItems.length > 0" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 max-h-96 overflow-y-auto">
          <div
            v-for="item in filteredItems"
            :key="`${item.type}-${item.id}`"
            class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer"
            @click="toggleItemSelection(item)"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-3">
                <input
                  :checked="isItemSelected(item)"
                  type="checkbox"
                  class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  @click.stop="toggleItemSelection(item)"
                />
                
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <h4 class="text-sm font-medium text-gray-900">{{ item.description }}</h4>
                    <span
                      :class="[
                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                        item.type === 'time_entry' 
                          ? 'bg-blue-100 text-blue-800' 
                          : 'bg-purple-100 text-purple-800'
                      ]"
                    >
                      {{ item.type === 'time_entry' ? 'Time Entry' : 'Add-on' }}
                    </span>
                  </div>
                  
                  <div class="mt-1 text-sm text-gray-500">
                    <div class="flex items-center space-x-4">
                      <span>{{ formatQuantity(item) }} × ${{ formatCurrency(item.unit_price) }}</span>
                      <span v-if="item.ticket" class="text-indigo-600">
                        #{{ item.ticket.ticket_number }} - {{ item.ticket.title }}
                      </span>
                      <span v-if="item.user" class="text-gray-600">{{ item.user.name }}</span>
                      <span v-else-if="item.added_by" class="text-gray-600">{{ item.added_by.name }}</span>
                    </div>
                  </div>
                  
                  <div v-if="item.started_at" class="mt-1 text-xs text-gray-400">
                    {{ formatDate(item.started_at) }}
                  </div>
                </div>
              </div>
              
              <div class="text-right">
                <div class="text-sm font-medium text-gray-900">
                  ${{ formatCurrency(item.total_amount) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No items available</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'No items match your search.' : 'All approved items have been invoiced or there are no billable items.' }}
        </p>
      </div>

      <!-- Custom Line Item Form -->
      <div class="border-t border-gray-200 pt-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Add Custom Item</h3>
          <button
            @click="showCustomForm = !showCustomForm"
            class="text-sm text-indigo-600 hover:text-indigo-500"
          >
            {{ showCustomForm ? 'Hide' : 'Show' }} Custom Form
          </button>
        </div>

        <div v-if="showCustomForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <input
              v-model="customItem.description"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Item description..."
            />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Quantity</label>
              <input
                v-model="customItem.quantity"
                type="number"
                step="0.01"
                min="0.01"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="1.00"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Unit Price</label>
              <input
                v-model="customItem.unit_price"
                type="number"
                step="0.01"
                min="0"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="0.00"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <button
              @click="addCustomItem"
              :disabled="!canAddCustomItem || isAddingItems"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Add Custom Item
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-600">
        <span v-if="selectedItems.length > 0">
          {{ selectedItems.length }} item(s) selected • Total: ${{ formatCurrency(selectedTotal) }}
        </span>
        <span v-else>
          No items selected
        </span>
      </div>

      <div class="flex space-x-3">
        <button
          @click="$emit('close')"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Cancel
        </button>
        
        <button
          @click="addSelectedItems"
          :disabled="selectedItems.length === 0 || isAddingItems"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isAddingItems" class="mr-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
          </span>
          {{ isAddingItems ? 'Adding...' : `Add ${selectedItems.length} Item(s)` }}
        </button>
      </div>
    </div>
  </StackedDialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import StackedDialog from '@/Components/StackedDialog.vue'
import { DocumentIcon } from '@heroicons/vue/24/outline'
import { useInvoiceQuery } from '@/Composables/queries/useInvoiceQuery.js'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  invoiceId: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['close', 'item-added'])

// State
const searchQuery = ref('')
const itemTypeFilter = ref('')
const selectedItems = ref([])
const showCustomForm = ref(false)

const customItem = reactive({
  description: '',
  quantity: 1,
  unit_price: 0
})

// Query composable - don't load available items automatically
const { 
  availableItems, 
  loadingAvailableItems: loading,
  refetchAvailableItems,
  addLineItem,
  isAddingLineItem: isAddingItems
} = useInvoiceQuery(props.invoiceId, { loadAvailableItems: false })

// Load available items only when modal is opened
watch(() => props.show, (isOpen) => {
  if (isOpen && props.invoiceId) {
    refetchAvailableItems()
  }
}, { immediate: true })

// Computed
const filteredItems = computed(() => {
  if (!availableItems.value) return []
  
  let items = [
    ...(availableItems.value.time_entries || []),
    ...(availableItems.value.ticket_addons || [])
  ]

  // Filter by type
  if (itemTypeFilter.value) {
    items = items.filter(item => item.type === itemTypeFilter.value)
  }

  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item => 
      item.description.toLowerCase().includes(query) ||
      (item.ticket && item.ticket.ticket_number.toLowerCase().includes(query)) ||
      (item.user && item.user.name.toLowerCase().includes(query)) ||
      (item.added_by && item.added_by.name.toLowerCase().includes(query))
    )
  }

  return items
})

const selectedTotal = computed(() => {
  return selectedItems.value.reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0)
})

const canAddCustomItem = computed(() => {
  return customItem.description.trim() && 
         customItem.quantity > 0 && 
         customItem.unit_price >= 0
})

// Methods
const isItemSelected = (item) => {
  return selectedItems.value.some(selected => 
    selected.type === item.type && selected.id === item.id
  )
}

const toggleItemSelection = (item) => {
  if (isItemSelected(item)) {
    selectedItems.value = selectedItems.value.filter(selected =>
      !(selected.type === item.type && selected.id === item.id)
    )
  } else {
    selectedItems.value.push(item)
  }
}

const addSelectedItems = async () => {
  if (selectedItems.value.length === 0) return

  try {
    for (const item of selectedItems.value) {
      await addLineItem({
        item_type: item.type,
        item_id: item.id
      })
    }

    // Reset state
    selectedItems.value = []
    emit('item-added')
  } catch (error) {
    console.error('Failed to add items:', error)
  }
}

const addCustomItem = async () => {
  if (!canAddCustomItem.value) return

  try {
    await addLineItem({
      item_type: 'custom',
      description: customItem.description.trim(),
      quantity: customItem.quantity,
      unit_price: customItem.unit_price
    })

    // Reset form
    customItem.description = ''
    customItem.quantity = 1
    customItem.unit_price = 0
    showCustomForm.value = false

    emit('item-added')
  } catch (error) {
    console.error('Failed to add custom item:', error)
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const formatQuantity = (item) => {
  if (item.type === 'time_entry') {
    const hours = Math.floor(item.quantity)
    const minutes = Math.round((item.quantity - hours) * 60)
    if (hours === 0) return `${minutes}m`
    if (minutes === 0) return `${hours}h`
    return `${hours}h ${minutes}m`
  }
  return item.quantity
}
</script>