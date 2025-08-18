<script setup>
import { ref, computed, watch } from 'vue'
import StackedDialog from './StackedDialog.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    tabs: {
        type: Array,
        required: true,
        // Expected format: [{ id: 'basic', name: 'Basic Info', icon: 'path...' }]
    },
    defaultTab: {
        type: String,
        default: null,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    saveLabel: {
        type: String,
        default: 'Save',
    },
    saving: {
        type: Boolean,
        default: false,
    },
    showFooter: {
        type: Boolean,
        default: true,
    },
    allowDropdowns: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close', 'save', 'tab-change'])

const activeTab = ref('')

// Initialize and watch for show prop to reset tab
const initializeTab = () => {
    const initialTab = props.defaultTab || props.tabs[0]?.id || ''
    console.log('TabbedDialog - Initializing tab:', initialTab, 'Available tabs:', props.tabs.map(t => t.id))
    activeTab.value = initialTab
    if (initialTab) {
        emit('tab-change', initialTab)
    }
}

// Initialize tab when component mounts
watch(() => props.tabs, () => {
    if (props.tabs.length > 0 && !activeTab.value) {
        initializeTab()
    }
}, { immediate: true })

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        initializeTab()
    }
})

// Watch for tab changes
watch(activeTab, (newTab) => {
    emit('tab-change', newTab)
})

const close = () => {
    emit('close')
}

const save = () => {
    emit('save')
}

const setActiveTab = (tabId) => {
    activeTab.value = tabId
    emit('tab-change', tabId)
}
</script>

<template>
    <StackedDialog
        :show="show"
        :title="title"
        :max-width="maxWidth"
        :closeable="closeable"
        :show-footer="false"
        :pad-content="false"
        :allow-dropdowns="allowDropdowns"
        @close="close"
    >
        <!-- Tab Navigation Below Header -->
        <div class="border-b border-gray-200 px-6">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="setActiveTab(tab.id)"
                    :class="[
                        activeTab === tab.id
                            ? 'border-indigo-500 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center'
                    ]"
                >
                    <svg v-if="tab.icon" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                    </svg>
                    {{ tab.name }}
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- General error slot -->
            <slot name="errors" />
            
            <!-- Tab content area -->
            <div>
                <slot :activeTab="activeTab" />
            </div>
        </div>

        <!-- Footer with buttons -->
        <div v-if="showFooter" class="border-t border-gray-200 px-6 py-4 flex items-center justify-end space-x-2">
            <slot name="footer-start" />
            
            <button
                type="button"
                @click="close"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Cancel
            </button>
            
            <button
                type="button"
                @click="save"
                :disabled="saving"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="saving" class="inline w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ saving ? 'Saving...' : saveLabel }}
            </button>
            
            <slot name="footer-end" />
        </div>
    </StackedDialog>
</template>