<template>
    <StandardPageLayout
        title="Settings"
        subtitle="Configure system settings, email, billing, and more."
        :show-sidebar="false"
        :show-filters="false"
    >
        <template #tabs>
            <TabNavigation
                v-model="activeTab"
                :tabs="navigationTabs"
                variant="underline"
                @tab-change="handleTabChange"
            />
        </template>

        <template #main-content>
            <!-- System Configuration Tab -->
            <div v-show="activeTab === 'system'" class="space-y-8">
                <SystemConfiguration
                    :settings="systemSettings"
                    :loading="loading.system"
                    @update="updateSystemSettings"
                />
            </div>

            <!-- Email Configuration Tab -->
            <div v-show="activeTab === 'email'" class="space-y-8">
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
            <div v-show="activeTab === 'tickets'" class="space-y-8">
                <TicketConfiguration
                    :config="ticketConfig"
                    :loading="loading.tickets"
                    @refresh="loadTicketConfig"
                />
            </div>

            <!-- Billing & Addons Tab -->
            <div v-show="activeTab === 'billing'" class="space-y-8">
                <BillingConfiguration
                    :config="billingConfig"
                    :loading="loading.billing"
                    @refresh="loadBillingConfig"
                />
            </div>

            <!-- Timer Settings Tab -->
            <div v-show="activeTab === 'timer'" class="space-y-8">
                <TimerSettings
                    :settings="timerSettings"
                    :loading="loading.timer"
                    @update="updateTimerSettings"
                />
            </div>

            <!-- User Management Tab -->
            <div v-show="activeTab === 'users'" class="space-y-8">
                <UserManagement
                    :settings="userManagementSettings"
                    :loading="loading.users"
                    @update="updateUserManagementSettings"
                />
            </div>

            <!-- Advanced Tab -->
            <div v-show="activeTab === 'advanced'" class="space-y-8">
                <AdvancedSettings
                    :settings="advancedSettings"
                    :loading="loading.advanced"
                    @update="updateAdvancedSettings"
                />
            </div>

            <!-- Nuclear Reset Tab -->
            <div v-show="activeTab === 'reset'" class="space-y-8">
                <NuclearResetSection />
            </div>
        </template>
    </StandardPageLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import StandardPageLayout from "@/Layouts/StandardPageLayout.vue";
import TabNavigation from "@/Components/Layout/TabNavigation.vue";

// Define persistent layout
defineOptions({
    layout: (h, page) => h(AppLayout, () => page),
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
import AdvancedSettings from "@/Pages/Settings/Components/AdvancedSettings.vue";
import NuclearResetSection from "@/Components/Settings/NuclearResetSection.vue";

// Get current user from page props
const page = usePage()
const user = computed(() => page.props.auth?.user)

// Navigation tabs configuration
const navigationTabs = computed(() => {
    const baseTabs = [
        { 
            id: "system", 
            name: "System Config", 
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
        },
        { 
            id: "email", 
            name: "Email Settings", 
            icon: 'M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
        },
        { 
            id: "tickets", 
            name: "Tickets", 
            icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'
        },
        { 
            id: "billing", 
            name: "Billing & Addons", 
            icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        },
        { 
            id: "timer", 
            name: "Timer Settings", 
            icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
        },
        { 
            id: "users", 
            name: "User Management", 
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'
        },
    ]
    
    // Only show Advanced tab to super admin users
    if (user.value?.is_super_admin) {
        baseTabs.push({ 
            id: "advanced", 
            name: "Advanced", 
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
        })
    }
    
    baseTabs.push({ 
        id: "reset", 
        name: "Nuclear Reset", 
        icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
    })
    
    return baseTabs
})

// Reactive state - initialize from props
const activeTab = ref(props.activeTab || "system");
const loading = reactive({
    system: false,
    email: false,
    tickets: false,
    billing: false,
    timer: false,
    users: false,
    advanced: false,
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
const advancedSettings = ref({});

// Tab change handler
const handleTabChange = (tabId) => {
    router.visit(`/settings/${tabId}`, {
        preserveState: true,
        preserveScroll: true
    });
};

// Navigate to tab using URL path params
const navigateToTab = (tabId) => {
    const currentUrl = new URL(window.location.href);
    const newPath = `/settings/${tabId}`;
    
    if (currentUrl.pathname !== newPath) {
        router.visit(newPath, {
            preserveState: true,
            preserveScroll: true
        });
    }
};

// Load functions for each tab
const loadSystemSettings = async () => {
    try {
        loading.system = true;
        const response = await fetch('/api/settings/system');
        if (response.ok) {
            systemSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load system settings:', error);
    } finally {
        loading.system = false;
    }
};

const loadEmailSettings = async () => {
    try {
        loading.email = true;
        const response = await fetch('/api/settings/email');
        if (response.ok) {
            emailSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load email settings:', error);
    } finally {
        loading.email = false;
    }
};

const loadTicketConfig = async () => {
    try {
        loading.tickets = true;
        const response = await fetch('/api/settings/ticket-config');
        if (response.ok) {
            ticketConfig.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load ticket config:', error);
    } finally {
        loading.tickets = false;
    }
};

const loadBillingConfig = async () => {
    try {
        loading.billing = true;
        const response = await fetch('/api/billing-rates');
        if (response.ok) {
            billingConfig.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load billing config:', error);
    } finally {
        loading.billing = false;
    }
};

const loadTimerSettings = async () => {
    try {
        loading.timer = true;
        const response = await fetch('/api/settings/timer');
        if (response.ok) {
            timerSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load timer settings:', error);
    } finally {
        loading.timer = false;
    }
};

const loadUserManagementSettings = async () => {
    try {
        loading.users = true;
        const response = await fetch('/api/settings/user-management');
        if (response.ok) {
            userManagementSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load user management settings:', error);
    } finally {
        loading.users = false;
    }
};

const loadAdvancedSettings = async () => {
    try {
        loading.advanced = true;
        const response = await fetch('/api/settings/advanced');
        if (response.ok) {
            advancedSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load advanced settings:', error);
    } finally {
        loading.advanced = false;
    }
};

// Update functions for each tab
const updateSystemSettings = async (data) => {
    try {
        loading.system = true;
        const response = await fetch('/api/settings/system', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            systemSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to update system settings:', error);
    } finally {
        loading.system = false;
    }
};

const updateEmailSettings = async (data) => {
    try {
        loading.email = true;
        const response = await fetch('/api/settings/email', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            emailSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to update email settings:', error);
    } finally {
        loading.email = false;
    }
};

const updateTimerSettings = async (data) => {
    try {
        loading.timer = true;
        const response = await fetch('/api/settings/timer', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            timerSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to update timer settings:', error);
    } finally {
        loading.timer = false;
    }
};

const updateUserManagementSettings = async (data) => {
    try {
        loading.users = true;
        const response = await fetch('/api/settings/user-management', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            userManagementSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to update user management settings:', error);
    } finally {
        loading.users = false;
    }
};

const updateAdvancedSettings = async (data) => {
    try {
        loading.advanced = true;
        const response = await fetch('/api/settings/advanced', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            advancedSettings.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to update advanced settings:', error);
    } finally {
        loading.advanced = false;
    }
};

// Email testing functions
const testSmtp = async () => {
    try {
        testing.smtp = true;
        const response = await fetch('/api/settings/email/test-smtp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });
        
        const result = await response.json();
        if (response.ok) {
            alert('SMTP test successful!');
        } else {
            alert(`SMTP test failed: ${result.message}`);
        }
    } catch (error) {
        console.error('SMTP test failed:', error);
        alert('SMTP test failed: ' + error.message);
    } finally {
        testing.smtp = false;
    }
};

const testImap = async () => {
    try {
        testing.imap = true;
        const response = await fetch('/api/settings/email/test-imap', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        });
        
        const result = await response.json();
        if (response.ok) {
            alert('IMAP test successful!');
        } else {
            alert(`IMAP test failed: ${result.message}`);
        }
    } catch (error) {
        console.error('IMAP test failed:', error);
        alert('IMAP test failed: ' + error.message);
    } finally {
        testing.imap = false;
    }
};

// Load initial data based on active tab
const loadInitialData = () => {
    switch (activeTab.value) {
        case 'system':
            loadSystemSettings();
            break;
        case 'email':
            loadEmailSettings();
            break;
        case 'tickets':
            loadTicketConfig();
            break;
        case 'billing':
            loadBillingConfig();
            break;
        case 'timer':
            loadTimerSettings();
            break;
        case 'users':
            loadUserManagementSettings();
            break;
        case 'advanced':
            if (user.value?.is_super_admin) {
                loadAdvancedSettings();
            }
            break;
    }
};

// Watch for tab changes and load appropriate data
watch(activeTab, (newTab) => {
    loadInitialData();
});

// Initialize on mount
onMounted(() => {
    loadInitialData();
});
</script>