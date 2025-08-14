<template>
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Ticket Addons
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Additional items and services for this ticket
                    </p>
                </div>
                <button
                    v-if="canManageAddons"
                    @click="showAddAddonModal = true"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <PlusIcon class="h-4 w-4 mr-1" />
                    Add Addon
                </button>
            </div>
        </div>

        <div class="px-4 py-5 sm:p-6">
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- No Addons -->
            <div v-else-if="addons.length === 0" class="text-center py-8">
                <CubeIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No addons</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding an addon to this ticket.</p>
                <div class="mt-6" v-if="canManageAddons">
                    <button
                        @click="showAddAddonModal = true"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Add First Addon
                    </button>
                </div>
            </div>

            <!-- Addons List -->
            <div v-else class="space-y-4">
                <div
                    v-for="addon in addons"
                    :key="addon.id"
                    class="border border-gray-200 rounded-lg p-4 bg-white"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-sm font-medium text-gray-900">
                                    {{ addon.name }}
                                </h4>
                                <span
                                    v-if="addon.category"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                >
                                    {{ getCategoryLabel(addon.category) }}
                                </span>
                                <span
                                    v-if="addon.is_billable"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    Billable
                                </span>
                            </div>
                            
                            <p v-if="addon.description" class="mt-1 text-sm text-gray-600">
                                {{ addon.description }}
                            </p>
                            
                            <div class="mt-2 text-sm text-gray-500">
                                <span v-if="addon.sku" class="mr-4">SKU: {{ addon.sku }}</span>
                                <span class="mr-4">Qty: {{ addon.quantity }}</span>
                                <span class="mr-4">Unit: {{ formatPrice(addon.unit_price) }}</span>
                                <span v-if="addon.discount_amount > 0" class="mr-4 text-orange-600">
                                    Discount: -{{ formatPrice(addon.discount_amount) }}
                                </span>
                            </div>
                            
                            <div class="mt-2 flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    Added by {{ addon.added_by?.name || 'Unknown' }} on {{ formatDate(addon.created_at) }}
                                </div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ formatPrice(addon.total_amount) }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions Menu -->
                        <div v-if="canManageAddons" class="flex items-center space-x-2 ml-4">
                            <button
                                @click="editAddon(addon)"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-100 rounded hover:bg-indigo-200"
                            >
                                <PencilIcon class="h-3 w-3 mr-1" />
                                Edit
                            </button>
                            <button
                                @click="deleteAddon(addon)"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded hover:bg-red-200"
                            >
                                <TrashIcon class="h-3 w-3 mr-1" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="border-t border-gray-200 pt-4 mt-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Total Items:</span>
                                <span class="ml-2 font-medium">{{ addons.length }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Total Cost:</span>
                                <span class="ml-2 font-semibold text-lg">{{ formatPrice(totalAddonCost) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Addon Modal -->
        <AddAddonModal
            :show="showAddAddonModal"
            :ticket="ticket"
            @close="showAddAddonModal = false"
            @added="handleAddonAdded"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { PlusIcon, CubeIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import AddAddonModal from '@/Components/Modals/AddAddonModal.vue'
import axios from 'axios'

const props = defineProps({
    ticket: {
        type: Object,
        required: true
    },
    canManageAddons: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['addon-updated'])

const loading = ref(false)
const addons = ref([])
const showAddAddonModal = ref(false)

const categoryLabels = {
    product: 'Product',
    service: 'Service', 
    license: 'License',
    hardware: 'Hardware',
    software: 'Software',
    expense: 'Expense',
    other: 'Other'
}

// Computed values
const totalAddonCost = computed(() => {
    return addons.value
        .filter(addon => addon.is_billable)
        .reduce((total, addon) => total + parseFloat(addon.total_amount), 0)
})

// Helper functions
const getCategoryLabel = (category) => {
    return categoryLabels[category] || category.charAt(0).toUpperCase() + category.slice(1)
}


const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Load addons
const loadAddons = async () => {
    loading.value = true
    try {
        const response = await axios.get('/api/ticket-addons', {
            params: { ticket_id: props.ticket.id }
        })
        addons.value = response.data.data || []
    } catch (error) {
        console.error('Failed to load ticket addons:', error)
    } finally {
        loading.value = false
    }
}

// Handle addon added
const handleAddonAdded = (newAddon) => {
    addons.value.unshift(newAddon)
    emit('addon-updated')
}


// Edit addon
const editAddon = (addon) => {
    // This could open an edit modal or navigate to edit page
    console.log('Edit addon:', addon)
    // For now, just log - implement edit functionality as needed
}

// Delete addon
const deleteAddon = async (addon) => {
    if (!confirm(`Are you sure you want to delete "${addon.name}"?`)) return
    
    try {
        await axios.delete(`/api/ticket-addons/${addon.id}`)
        const index = addons.value.findIndex(a => a.id === addon.id)
        if (index !== -1) {
            addons.value.splice(index, 1)
        }
        emit('addon-updated')
    } catch (error) {
        console.error('Failed to delete addon:', error)
        alert('Failed to delete addon. Please try again.')
    }
}

onMounted(() => {
    loadAddons()
})
</script>