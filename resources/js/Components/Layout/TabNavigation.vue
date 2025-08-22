<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";

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
    enableScrolling: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["update:modelValue", "tab-change"]);

const activeTab = ref("");

// Scrolling functionality
const tabsContainer = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

const checkScrollState = () => {
    if (!tabsContainer.value || !props.enableScrolling) return;
    
    const container = tabsContainer.value;
    canScrollLeft.value = container.scrollLeft > 0;
    canScrollRight.value = container.scrollLeft < (container.scrollWidth - container.clientWidth);
};

const scrollTabs = (direction) => {
    if (!tabsContainer.value) return;
    
    const container = tabsContainer.value;
    const scrollAmount = 200; // pixels to scroll
    
    if (direction === 'left') {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
    
    // Update scroll state after animation
    setTimeout(checkScrollState, 300);
};

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
        return "flex border-b border-gray-200";
    } else if (props.variant === "pills") {
        return "flex p-1 bg-gray-100 rounded-lg";
    } else {
        return "flex";
    }
});

// Lifecycle methods for scroll detection
onMounted(() => {
    if (props.enableScrolling) {
        nextTick(() => {
            checkScrollState();
            if (tabsContainer.value) {
                tabsContainer.value.addEventListener('scroll', checkScrollState);
                window.addEventListener('resize', checkScrollState);
            }
        });
    }
});

onUnmounted(() => {
    if (tabsContainer.value) {
        tabsContainer.value.removeEventListener('scroll', checkScrollState);
        window.removeEventListener('resize', checkScrollState);
    }
});
</script>

<template>
    <div class="relative" v-if="enableScrolling">
        <!-- Left Scroll Button -->
        <button
            v-show="canScrollLeft"
            @click="scrollTabs('left')"
            class="absolute left-0 top-0 bottom-0 z-10 flex items-center justify-center w-8 bg-gradient-to-r from-white via-white to-transparent"
            type="button"
            aria-label="Scroll tabs left"
        >
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Scrollable Tabs Container -->
        <div class="overflow-x-auto scrollbar-hide" ref="tabsContainer">
            <nav :class="containerClasses" aria-label="Tabs" style="min-width: max-content;">
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
                    class="whitespace-nowrap flex-shrink-0 mr-6"
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
        </div>

        <!-- Right Scroll Button -->
        <button
            v-show="canScrollRight"
            @click="scrollTabs('right')"
            class="absolute right-0 top-0 bottom-0 z-10 flex items-center justify-center w-8 bg-gradient-to-l from-white via-white to-transparent"
            type="button"
            aria-label="Scroll tabs right"
        >
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <!-- Non-scrollable fallback -->
    <nav v-else :class="containerClasses" aria-label="Tabs">
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
            class="mr-6"
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

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
