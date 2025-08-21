<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        // Expected format: [{ id: 'entries', name: 'Time Entries', icon: 'path...', count: 42 }]
    },
    modelValue: {
        type: String,
        default: null,
    },
    defaultTab: {
        type: String,
        default: null,
    },
    showCounts: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: "border", // 'border' | 'pills' | 'underline'
    },
});

const emit = defineEmits(["update:modelValue", "tab-change"]);

const activeTab = ref("");

// Initialize active tab
const initializeTab = () => {
    const initialTab =
        props.modelValue || props.defaultTab || props.tabs[0]?.id || "";
    activeTab.value = initialTab;
    if (initialTab && initialTab !== props.modelValue) {
        emit("update:modelValue", initialTab);
        emit("tab-change", initialTab);
    }
};

// Watch for tabs changes to initialize
watch(
    () => props.tabs,
    () => {
        if (props.tabs.length > 0 && !activeTab.value) {
            initializeTab();
        }
    },
    { immediate: true }
);

// Watch for external modelValue changes
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue && newValue !== activeTab.value) {
            activeTab.value = newValue;
        }
    }
);

const setActiveTab = (tabId) => {
    activeTab.value = tabId;
    emit("update:modelValue", tabId);
    emit("tab-change", tabId);
};

// Computed styles based on variant
const tabClasses = computed(() => {
    const base =
        "flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200";

    if (props.variant === "pills") {
        return {
            active: `${base} bg-indigo-100 text-indigo-700`,
            inactive: `${base} text-gray-500 hover:text-gray-700 hover:bg-gray-100`,
        };
    } else if (props.variant === "underline") {
        return {
            active: `${base} text-indigo-600 border-b-2 border-indigo-500 rounded-none`,
            inactive: `${base} text-gray-500 hover:text-gray-700 border-b-2 border-transparent rounded-none`,
        };
    } else {
        // border variant (default)
        return {
            active: `${base} bg-white text-indigo-600 border border-indigo-200 shadow-sm`,
            inactive: `${base} text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-transparent`,
        };
    }
});

const containerClasses = computed(() => {
    if (props.variant === "underline") {
        return "flex space-x-6 border-b border-gray-200";
    } else if (props.variant === "pills") {
        return "flex space-x-2 p-1 bg-gray-100 rounded-lg";
    } else {
        return "flex space-x-2";
    }
});
</script>

<template>
    <nav :class="containerClasses" aria-label="Tabs">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="setActiveTab(tab.id)"
            :class="
                activeTab === tab.id ? tabClasses.active : tabClasses.inactive
            "
            :aria-selected="activeTab === tab.id"
            role="tab"
        >
            <!-- Tab Icon -->
            <svg
                v-if="tab.icon"
                class="w-4 h-4 mr-2 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    :d="tab.icon"
                />
            </svg>

            <!-- Tab Name -->
            <span>{{ tab.name }}</span>

            <!-- Tab Count Badge -->
            <span
                v-if="showCounts && tab.count !== undefined"
                :class="[
                    'ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-medium rounded-full',
                    activeTab === tab.id
                        ? 'bg-indigo-200 text-indigo-800'
                        : 'bg-gray-200 text-gray-600',
                ]"
            >
                {{ tab.count }}
            </span>
        </button>
    </nav>
</template>
