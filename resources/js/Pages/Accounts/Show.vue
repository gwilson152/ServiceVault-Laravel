<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Page Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <div class="flex items-center space-x-4">
                        <!-- Back Button -->
                        <Link
                            :href="route('accounts.index')"
                            class="text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </Link>

                        <!-- Account Info -->
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ account?.name || "Loading..." }}
                            </h1>
                            <div class="flex items-center space-x-3 mt-1">
                                <span
                                    v-if="account"
                                    :class="accountTypeClasses"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{
                                        formatAccountType(account.account_type)
                                    }}
                                </span>
                                <span
                                    v-if="account"
                                    :class="statusClasses"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{
                                        account.is_active
                                            ? "Active"
                                            : "Inactive"
                                    }}
                                </span>
                                <span
                                    v-if="account?.company_name"
                                    class="text-sm text-gray-500"
                                >
                                    {{ account.company_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <button
                            @click="showEditModal = true"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                        >
                            Edit Account
                        </button>
                        <button
                            @click="showMoreActions = !showMoreActions"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors relative"
                        >
                            More Actions
                            <div
                                v-if="showMoreActions"
                                class="absolute right-0 top-full mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-10"
                            >
                                <button
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50"
                                >
                                    Manage Users
                                </button>
                                <button
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50"
                                >
                                    View Tickets
                                </button>
                                <button
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50"
                                >
                                    Billing History
                                </button>
                                <button
                                    v-if="account?.is_active"
                                    @click="deactivateAccount"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 text-red-600"
                                >
                                    Deactivate
                                </button>
                                <button
                                    v-else
                                    @click="activateAccount"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-green-50 text-green-600"
                                >
                                    Activate
                                </button>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div v-if="loading" class="text-center py-8">
                <div
                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
                ></div>
                <p class="mt-2 text-gray-500">Loading account details...</p>
            </div>

            <div v-else-if="error" class="text-center py-8">
                <div class="text-red-600 mb-4">
                    <svg
                        class="w-12 h-12 mx-auto"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                        />
                    </svg>
                </div>
                <p class="text-red-600 font-medium">
                    Failed to load account details
                </p>
                <p class="text-gray-500 text-sm mt-1">{{ error }}</p>
                <button
                    @click="loadAccount"
                    class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                >
                    Try Again
                </button>
            </div>

            <div v-else-if="account" class="space-y-6">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav
                        class="-mb-px flex space-x-8"
                        aria-label="Account tabs"
                    >
                        <button
                            v-for="tab in accountTabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm flex items-center',
                            ]"
                        >
                            <component :is="tab.icon" class="w-5 h-5 mr-2" />
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Overview Tab -->
                        <div v-show="activeTab === 'overview'">
                            <!-- Account Information Card -->
                            <div
                                class="bg-white rounded-lg shadow-sm border border-gray-200"
                            >
                                <div class="p-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Account Information
                                    </h3>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                    >
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Account Name</label
                                            >
                                            <p class="mt-1 text-gray-900">
                                                {{ account.name }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Company Name</label
                                            >
                                            <p class="mt-1 text-gray-900">
                                                {{
                                                    account.company_name ||
                                                    "N/A"
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Account Type</label
                                            >
                                            <p class="mt-1">
                                                <span
                                                    :class="accountTypeClasses"
                                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                                >
                                                    {{
                                                        formatAccountType(
                                                            account.account_type
                                                        )
                                                    }}
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Status</label
                                            >
                                            <p class="mt-1">
                                                <span
                                                    :class="statusClasses"
                                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                                >
                                                    {{
                                                        account.is_active
                                                            ? "Active"
                                                            : "Inactive"
                                                    }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div v-if="account.description">
                                        <label
                                            class="text-sm font-medium text-gray-500"
                                            >Description</label
                                        >
                                        <p class="mt-1 text-gray-900">
                                            {{ account.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information Card -->
                            <div
                                class="bg-white rounded-lg shadow-sm border border-gray-200"
                            >
                                <div class="p-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Contact Information
                                    </h3>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                    >
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Contact Person</label
                                            >
                                            <p class="mt-1 text-gray-900">
                                                {{
                                                    account.contact_person ||
                                                    "N/A"
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Email</label
                                            >
                                            <p class="mt-1 text-gray-900">
                                                {{ account.email || "N/A" }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Phone</label
                                            >
                                            <p class="mt-1 text-gray-900">
                                                {{ account.phone || "N/A" }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="text-sm font-medium text-gray-500"
                                                >Website</label
                                            >
                                            <p class="mt-1">
                                                <a
                                                    v-if="account.website"
                                                    :href="account.website"
                                                    target="_blank"
                                                    class="text-blue-600 hover:text-blue-700"
                                                >
                                                    {{ account.website }}
                                                </a>
                                                <span
                                                    v-else
                                                    class="text-gray-900"
                                                    >N/A</span
                                                >
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Information Card -->
                            <div
                                v-if="
                                    account.address || account.billing_address
                                "
                                class="bg-white rounded-lg shadow-sm border border-gray-200"
                            >
                                <div class="p-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        Address Information
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-6"
                                    >
                                        <!-- Primary Address -->
                                        <div v-if="account.address">
                                            <h4
                                                class="font-medium text-gray-900 mb-2"
                                            >
                                                Primary Address
                                            </h4>
                                            <div
                                                class="text-gray-700 space-y-1"
                                            >
                                                <p>{{ account.address }}</p>
                                                <p
                                                    v-if="
                                                        account.city ||
                                                        account.state ||
                                                        account.postal_code
                                                    "
                                                >
                                                    <span v-if="account.city">{{
                                                        account.city
                                                    }}</span
                                                    ><span
                                                        v-if="
                                                            account.city &&
                                                            account.state
                                                        "
                                                        >, </span
                                                    ><span
                                                        v-if="account.state"
                                                        >{{
                                                            account.state
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="
                                                            account.postal_code
                                                        "
                                                        >{{
                                                            account.postal_code
                                                        }}</span
                                                    >
                                                </p>
                                                <p v-if="account.country">
                                                    {{ account.country }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Billing Address -->
                                        <div v-if="account.billing_address">
                                            <h4
                                                class="font-medium text-gray-900 mb-2"
                                            >
                                                Billing Address
                                            </h4>
                                            <div
                                                class="text-gray-700 space-y-1"
                                            >
                                                <p>
                                                    {{
                                                        account.billing_address
                                                    }}
                                                </p>
                                                <p
                                                    v-if="
                                                        account.billing_city ||
                                                        account.billing_state ||
                                                        account.billing_postal_code
                                                    "
                                                >
                                                    <span
                                                        v-if="
                                                            account.billing_city
                                                        "
                                                        >{{
                                                            account.billing_city
                                                        }}</span
                                                    ><span
                                                        v-if="
                                                            account.billing_city &&
                                                            account.billing_state
                                                        "
                                                        >, </span
                                                    ><span
                                                        v-if="
                                                            account.billing_state
                                                        "
                                                        >{{
                                                            account.billing_state
                                                        }}</span
                                                    >
                                                    <span
                                                        v-if="
                                                            account.billing_postal_code
                                                        "
                                                        >{{
                                                            account.billing_postal_code
                                                        }}</span
                                                    >
                                                </p>
                                                <p
                                                    v-if="
                                                        account.billing_country
                                                    "
                                                >
                                                    {{
                                                        account.billing_country
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Tab -->
                        <div v-show="activeTab === 'billing'">
                            <BillingRateOverrides :account-id="account.id" />
                        </div>

                        <!-- Users Tab -->
                        <div v-show="activeTab === 'users'">
                            <div
                                class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
                            >
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Account Users
                                </h3>
                                <p class="text-gray-600">
                                    User management interface coming soon...
                                </p>
                            </div>
                        </div>

                        <!-- Tickets Tab -->
                        <div v-show="activeTab === 'tickets'">
                            <div
                                class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
                            >
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Account Tickets
                                </h3>
                                <p class="text-gray-600">
                                    Ticket list interface coming soon...
                                </p>
                            </div>
                        </div>

                        <!-- Reports Tab -->
                        <div v-show="activeTab === 'reports'">
                            <div
                                class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
                            >
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Reports & Analytics
                                </h3>
                                <p class="text-gray-600">
                                    Reports interface coming soon...
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Stats -->
                        <div
                            class="bg-white rounded-lg shadow-sm border border-gray-200"
                        >
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Quick Stats
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600"
                                        >Users</span
                                    >
                                    <span
                                        class="text-lg font-semibold text-gray-900"
                                        >{{ stats.users_count || 0 }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600"
                                        >Active Tickets</span
                                    >
                                    <span
                                        class="text-lg font-semibold text-blue-600"
                                        >{{
                                            stats.active_tickets_count || 0
                                        }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600"
                                        >Total Tickets</span
                                    >
                                    <span
                                        class="text-lg font-semibold text-gray-900"
                                        >{{
                                            stats.total_tickets_count || 0
                                        }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600"
                                        >Time Logged (Hours)</span
                                    >
                                    <span
                                        class="text-lg font-semibold text-green-600"
                                        >{{
                                            (
                                                stats.total_time_hours || 0
                                            ).toFixed(1)
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div
                            class="bg-white rounded-lg shadow-sm border border-gray-200"
                        >
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Recent Activity
                                </h3>
                            </div>
                            <div class="p-6">
                                <div
                                    v-if="recentActivity.length === 0"
                                    class="text-center text-gray-500 py-4"
                                >
                                    No recent activity
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="activity in recentActivity"
                                        :key="activity.id"
                                        class="flex items-start space-x-3"
                                    >
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-2 h-2 bg-blue-500 rounded-full mt-2"
                                            ></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
                                                {{ activity.description }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    formatDate(
                                                        activity.created_at
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Hierarchy -->
                        <div
                            v-if="
                                account.parent_account_id || children.length > 0
                            "
                            class="bg-white rounded-lg shadow-sm border border-gray-200"
                        >
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Account Hierarchy
                                </h3>
                            </div>
                            <div class="p-6">
                                <!-- Parent Account -->
                                <div
                                    v-if="account.parent_account_id"
                                    class="mb-4"
                                >
                                    <h4
                                        class="text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Parent Account
                                    </h4>
                                    <Link
                                        :href="
                                            route(
                                                'accounts.show',
                                                account.parent_account_id
                                            )
                                        "
                                        class="text-blue-600 hover:text-blue-700"
                                    >
                                        {{
                                            account.parent_account?.name ||
                                            "Loading..."
                                        }}
                                    </Link>
                                </div>

                                <!-- Child Accounts -->
                                <div v-if="children.length > 0">
                                    <h4
                                        class="text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Child Accounts
                                    </h4>
                                    <div class="space-y-2">
                                        <Link
                                            v-for="child in children"
                                            :key="child.id"
                                            :href="
                                                route('accounts.show', child.id)
                                            "
                                            class="block text-blue-600 hover:text-blue-700 text-sm"
                                        >
                                            {{ child.name }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import BillingRateOverrides from "@/Components/Accounts/BillingRateOverrides.vue";
import axios from "axios";

// Tab icons
import {
    InformationCircleIcon,
    CurrencyDollarIcon,
    UserGroupIcon,
    TicketIcon,
    ChartBarIcon,
} from "@heroicons/vue/24/outline";

// Define persistent layout
defineOptions({
    layout: AppLayout,
});

// Props
const props = defineProps({
    accountId: {
        type: [String, Number],
        required: true,
    },
});

// State
const account = ref(null);
const stats = ref({});
const recentActivity = ref([]);
const children = ref([]);
const loading = ref(true);
const error = ref(null);
const showEditModal = ref(false);
const showMoreActions = ref(false);
const activeTab = ref("overview");

// Tab configuration
const accountTabs = [
    { id: "overview", name: "Overview", icon: InformationCircleIcon },
    { id: "billing", name: "Billing Rates", icon: CurrencyDollarIcon },
    { id: "users", name: "Users", icon: UserGroupIcon },
    { id: "tickets", name: "Tickets", icon: TicketIcon },
    { id: "reports", name: "Reports", icon: ChartBarIcon },
];

// Computed
const accountTypeClasses = computed(() => {
    if (!account.value) return "";

    const type = account.value.account_type;
    return (
        {
            customer: "bg-blue-100 text-blue-800",
            prospect: "bg-yellow-100 text-yellow-800",
            partner: "bg-green-100 text-green-800",
            internal: "bg-purple-100 text-purple-800",
        }[type] || "bg-gray-100 text-gray-800"
    );
});

const statusClasses = computed(() => {
    if (!account.value) return "";

    return account.value.is_active
        ? "bg-green-100 text-green-800"
        : "bg-red-100 text-red-800";
});

// Methods
const loadAccount = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/api/accounts/${props.accountId}`);
        account.value = response.data.data;

        // Load related data
        await Promise.all([
            loadAccountStats(),
            loadRecentActivity(),
            loadChildren(),
        ]);
    } catch (err) {
        console.error("Failed to load account:", err);
        error.value =
            err.response?.data?.message || "Failed to load account details";
    } finally {
        loading.value = false;
    }
};

const loadAccountStats = async () => {
    try {
        const response = await axios.get(
            `/api/accounts/${props.accountId}/stats`
        );
        stats.value = response.data.data || {};
    } catch (err) {
        console.error("Failed to load account stats:", err);
    }
};

const loadRecentActivity = async () => {
    try {
        const response = await axios.get(
            `/api/accounts/${props.accountId}/activity`
        );
        recentActivity.value = response.data.data || [];
    } catch (err) {
        console.error("Failed to load recent activity:", err);
    }
};

const loadChildren = async () => {
    try {
        const response = await axios.get(
            `/api/accounts/${props.accountId}/children`
        );
        children.value = response.data.data || [];
    } catch (err) {
        console.error("Failed to load child accounts:", err);
    }
};

const formatAccountType = (type) => {
    const types = {
        customer: "Customer",
        prospect: "Prospect",
        partner: "Partner",
        internal: "Internal",
    };
    return types[type] || type;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = now - date;
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "Today";
    if (diffDays === 1) return "Yesterday";
    if (diffDays < 7) return `${diffDays} days ago`;

    return date.toLocaleDateString();
};

const activateAccount = async () => {
    try {
        await axios.patch(`/api/accounts/${props.accountId}`, {
            is_active: true,
        });
        account.value.is_active = true;
        showMoreActions.value = false;
    } catch (err) {
        console.error("Failed to activate account:", err);
    }
};

const deactivateAccount = async () => {
    try {
        await axios.patch(`/api/accounts/${props.accountId}`, {
            is_active: false,
        });
        account.value.is_active = false;
        showMoreActions.value = false;
    } catch (err) {
        console.error("Failed to deactivate account:", err);
    }
};

// Lifecycle
onMounted(() => {
    loadAccount();
});
</script>
