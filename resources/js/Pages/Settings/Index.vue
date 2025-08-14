<template>
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Settings
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Configure system settings, email, billing, and more.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-6">
        <div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">

                        <!-- Tab Navigation -->
                        <div class="border-b border-gray-200 mb-8">
                            <div class="relative">
                                <!-- Left scroll button -->
                                <button
                                    v-show="canScrollLeft"
                                    @click="scrollTabs('left')"
                                    class="absolute left-0 top-0 bottom-0 z-10 bg-white shadow-sm border-r border-gray-200 px-2 flex items-center hover:bg-gray-50"
                                    type="button"
                                >
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                
                                <!-- Right scroll button -->
                                <button
                                    v-show="canScrollRight"
                                    @click="scrollTabs('right')"
                                    class="absolute right-0 top-0 bottom-0 z-10 bg-white shadow-sm border-l border-gray-200 px-2 flex items-center hover:bg-gray-50"
                                    type="button"
                                >
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                
                                <nav
                                    ref="tabsContainer"
                                    @scroll="updateScrollState"
                                    class="-mb-px flex space-x-8 overflow-x-auto scrollbar-hide"
                                    :class="{ 'pl-10': canScrollLeft, 'pr-10': canScrollRight }"
                                    aria-label="Tabs"
                                    style="scroll-behavior: smooth;"
                                >
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="navigateToTab(tab.id)"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex-shrink-0',
                                    ]"
                                >
                                    <component
                                        :is="tab.icon"
                                        class="w-5 h-5 mr-2 inline"
                                    />
                                    {{ tab.name }}
                                </button>
                                </nav>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div class="mt-8">
                            <!-- System Configuration Tab -->
                            <div
                                v-show="activeTab === 'system'"
                                class="space-y-8"
                            >
                                <SystemConfiguration
                                    :settings="systemSettings"
                                    :loading="loading.system"
                                    @update="updateSystemSettings"
                                />
                            </div>

                            <!-- Email Configuration Tab -->
                            <div
                                v-show="activeTab === 'email'"
                                class="space-y-8"
                            >
                                <EmailConfiguration
                                    :settings="emailSettings"
                                    :loading="loading.email"
                                    :testing="testing"
                                    @update="updateEmailSettings"
                                    @test-smtp="testSmtp"
                                    @test-imap="testImap"
                                />
                            </div>

                            <!-- Ticket Configuration Tab -->
                            <div
                                v-show="activeTab === 'tickets'"
                                class="space-y-8"
                            >
                                <TicketConfiguration
                                    :config="ticketConfig"
                                    :loading="loading.tickets"
                                    @refresh="loadTicketConfig"
                                />
                            </div>

                            <!-- Billing & Addons Tab -->
                            <div
                                v-show="activeTab === 'billing'"
                                class="space-y-8"
                            >
                                <BillingConfiguration
                                    :config="billingConfig"
                                    :loading="loading.billing"
                                    @refresh="loadBillingConfig"
                                />
                            </div>

                            <!-- Timer Settings Tab -->
                            <div
                                v-show="activeTab === 'timer'"
                                class="space-y-8"
                            >
                                <TimerSettings
                                    :settings="timerSettings"
                                    :loading="loading.timer"
                                    @update="updateTimerSettings"
                                />
                            </div>

                            <!-- User Management Tab -->
                            <div
                                v-show="activeTab === 'users'"
                                class="space-y-8"
                            >
                                <UserManagement
                                    :settings="userManagementSettings"
                                    :loading="loading.users"
                                    @update="updateUserManagementSettings"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

// Define persistent layout
defineOptions({
  layout: AppLayout
});

// Props from route
const props = defineProps({
  activeTab: {
    type: String,
    default: 'system'
  }
});
import SystemConfiguration from "@/Pages/Settings/Components/SystemConfiguration.vue";
import EmailConfiguration from "@/Pages/Settings/Components/EmailConfiguration.vue";
import TicketConfiguration from "@/Pages/Settings/Components/TicketConfiguration.vue";
import BillingConfiguration from "@/Pages/Settings/Components/BillingConfigurationNew.vue";
import TimerSettings from "@/Pages/Settings/Components/TimerSettings.vue";
import UserManagement from "@/Pages/Settings/Components/UserManagement.vue";
import {
    CogIcon,
    EnvelopeIcon,
    TicketIcon,
    CurrencyDollarIcon,
    ClockIcon,
    UsersIcon,
} from "@heroicons/vue/24/outline";

// Tab configuration
const tabs = [
    { id: "system", name: "System Config", icon: CogIcon },
    { id: "email", name: "Email Settings", icon: EnvelopeIcon },
    { id: "tickets", name: "Tickets", icon: TicketIcon },
    { id: "billing", name: "Billing & Addons", icon: CurrencyDollarIcon },
    { id: "timer", name: "Timer Settings", icon: ClockIcon },
    { id: "users", name: "User Management", icon: UsersIcon },
];

// Reactive state - initialize from props
const activeTab = ref(props.activeTab || "system");
const loading = reactive({
    system: false,
    email: false,
    tickets: false,
    billing: false,
    timer: false,
    users: false,
});
const testing = reactive({
    smtp: false,
    imap: false,
});

// Settings data
const systemSettings = ref({});
const emailSettings = ref({});
const ticketConfig = ref({});
const billingConfig = ref({});
const timerSettings = ref({});
const userManagementSettings = ref({});

// Tab scrolling functionality
const tabsContainer = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

// Tab scrolling methods
const updateScrollState = () => {
    if (!tabsContainer.value) return;
    
    const container = tabsContainer.value;
    canScrollLeft.value = container.scrollLeft > 0;
    canScrollRight.value = container.scrollLeft < (container.scrollWidth - container.clientWidth);
};

const scrollTabs = (direction) => {
    if (!tabsContainer.value) return;
    
    const scrollAmount = 200;
    const container = tabsContainer.value;
    
    if (direction === 'left') {
        container.scrollLeft -= scrollAmount;
    } else {
        container.scrollLeft += scrollAmount;
    }
};

// Navigate to tab using URL path params
const navigateToTab = (tabId) => {
    const currentUrl = new URL(window.location.href);
    const newPath = `/settings/${tabId}`;
    
    router.visit(newPath, {
        preserveState: true,
        preserveScroll: true,
        only: ['activeTab'],
        onSuccess: () => {
            activeTab.value = tabId;
        }
    });
};

// Watch for prop changes when navigating
watch(() => props.activeTab, (newTab) => {
    if (newTab && newTab !== activeTab.value) {
        activeTab.value = newTab;
    }
}, { immediate: true });

// Load all settings on mount
onMounted(async () => {
    await loadAllSettings();
    
    // Initialize scroll state after DOM is ready
    await nextTick();
    updateScrollState();
    
    // Update scroll state on window resize
    window.addEventListener('resize', updateScrollState);
});

// Cleanup on unmount
onUnmounted(() => {
    window.removeEventListener('resize', updateScrollState);
});

// Load all settings
const loadAllSettings = async () => {
    try {
        const response = await window.axios.get("/api/settings");
        const data = response.data.data;

        systemSettings.value = data.system || {};
        emailSettings.value = data.email || {};

        // Load additional configurations
        await Promise.all([
            loadTicketConfig(),
            loadBillingConfig(),
            loadTimerSettings(),
            loadUserManagementSettings(),
        ]);
    } catch (error) {
        console.error("Failed to load settings:", error);
    }
};

// Load ticket configuration
const loadTicketConfig = async () => {
    loading.tickets = true;
    try {
        const response = await window.axios.get("/api/settings/ticket-config");
        ticketConfig.value = response.data.data;
    } catch (error) {
        console.error("Failed to load ticket config:", error);
    } finally {
        loading.tickets = false;
    }
};

// Load billing configuration
const loadBillingConfig = async () => {
    loading.billing = true;
    try {
        const response = await window.axios.get("/api/settings/billing-config");
        billingConfig.value = response.data.data;
    } catch (error) {
        console.error("Failed to load billing config:", error);
    } finally {
        loading.billing = false;
    }
};

// Load timer settings
const loadTimerSettings = async () => {
    loading.timer = true;
    try {
        const response = await window.axios.get("/api/settings/timer");
        timerSettings.value = response.data.data;
    } catch (error) {
        console.error("Failed to load timer settings:", error);
    } finally {
        loading.timer = false;
    }
};

// Load user management settings
const loadUserManagementSettings = async () => {
    loading.users = true;
    try {
        const response = await window.axios.get(
            "/api/settings/user-management"
        );
        userManagementSettings.value = response.data.data;
    } catch (error) {
        console.error("Failed to load user management settings:", error);
    } finally {
        loading.users = false;
    }
};

// Update system settings
const updateSystemSettings = async (settings) => {
    loading.system = true;
    try {
        await window.axios.put("/api/settings/system", settings);
        systemSettings.value = { ...systemSettings.value, ...settings };
        // Show success message
        router.visit(route("settings.index"), {
            preserveState: true,
            only: [],
            onSuccess: () => {
                // Success feedback handled by Inertia
            },
        });
    } catch (error) {
        console.error("Failed to update system settings:", error);
    } finally {
        loading.system = false;
    }
};

// Update email settings
const updateEmailSettings = async (settings) => {
    loading.email = true;
    try {
        await window.axios.put("/api/settings/email", settings);
        emailSettings.value = { ...emailSettings.value, ...settings };
        // Show success message without full page refresh
        console.log("Email settings updated successfully");
    } catch (error) {
        console.error("Failed to update email settings:", error);
    } finally {
        loading.email = false;
    }
};

// Test SMTP configuration
const testSmtp = async (config) => {
    testing.smtp = true;
    try {
        const response = await window.axios.post(
            "/api/settings/email/test-smtp",
            config
        );
        return response.data;
    } catch (error) {
        return (
            error.response?.data || { success: false, message: "Test failed" }
        );
    } finally {
        testing.smtp = false;
    }
};

// Test IMAP configuration
const testImap = async (config) => {
    testing.imap = true;
    try {
        const response = await window.axios.post(
            "/api/settings/email/test-imap",
            config
        );
        return response.data;
    } catch (error) {
        return (
            error.response?.data || { success: false, message: "Test failed" }
        );
    } finally {
        testing.imap = false;
    }
};

// Update timer settings
const updateTimerSettings = async (settings) => {
    loading.timer = true;
    try {
        await window.axios.put("/api/settings/timer", settings);
        timerSettings.value = { ...timerSettings.value, ...settings };
        router.visit(route("settings.index"), {
            preserveState: true,
            only: [],
            onSuccess: () => {
                // Success feedback handled by Inertia
            },
        });
    } catch (error) {
        console.error("Failed to update timer settings:", error);
    } finally {
        loading.timer = false;
    }
};

// Update user management settings
const updateUserManagementSettings = async (settings) => {
    loading.users = true;
    try {
        await window.axios.put("/api/settings/user-management", settings);
        userManagementSettings.value = {
            ...userManagementSettings.value,
            auto_user_settings: {
                ...userManagementSettings.value.auto_user_settings,
                ...settings,
            },
        };
        router.visit(route("settings.index"), {
            preserveState: true,
            only: [],
            onSuccess: () => {
                // Success feedback handled by Inertia
            },
        });
    } catch (error) {
        console.error("Failed to update user management settings:", error);
    } finally {
        loading.users = false;
    }
};
</script>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;     /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;             /* Chrome, Safari and Opera */
}
</style>
