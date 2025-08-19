<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div
            class="px-6 py-4 border-b border-gray-200 flex items-center justify-between"
        >
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Time Entries
                    <span
                        v-if="!timeEntriesLoading && timeEntries?.data"
                        class="text-sm font-normal text-gray-500 ml-2"
                    >
                        ({{ timeEntries.total }})
                    </span>
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Manage time entries for {{ account?.name }}
                </p>
            </div>
            <button
                v-if="canCreateTimeEntry"
                @click="showCreateModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm flex items-center space-x-2"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                </svg>
                <span>Add Entry</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div v-if="stats" class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ClockIcon class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-blue-600">
                                Total Hours
                            </p>
                            <p class="text-2xl font-semibold text-blue-900">
                                {{ formatHours(stats.total_hours) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CurrencyDollarIcon class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-green-600">
                                Billable Hours
                            </p>
                            <p class="text-2xl font-semibold text-green-900">
                                {{ formatHours(stats.billable_hours) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ExclamationTriangleIcon class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-yellow-600">
                                Pending
                            </p>
                            <p class="text-2xl font-semibold text-yellow-900">
                                {{ stats.pending_count }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon class="h-6 w-6 text-purple-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-purple-600">
                                Total Amount
                            </p>
                            <p class="text-2xl font-semibold text-purple-900">
                                ${{ formatCurrency(stats.total_amount) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        User
                    </label>
                    <select
                        v-model="filters.user_id"
                        @change="applyFilters"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Users</option>
                        <option
                            v-for="user in availableUsers"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }}
                        </option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>
                    <select
                        v-model="filters.status"
                        @change="applyFilters"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="billed">Billed</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Date From
                    </label>
                    <input
                        v-model="filters.date_from"
                        type="date"
                        @change="applyFilters"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Date To
                    </label>
                    <input
                        v-model="filters.date_to"
                        type="date"
                        @change="applyFilters"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                
                <div class="flex items-end">
                    <button
                        @click="clearFilters"
                        class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors text-sm"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Time Entries Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-if="canApprove"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12"
                        >
                            <input
                                v-model="selectAll"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                @change="toggleSelectAll"
                            />
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            User
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Ticket
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Date
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Duration
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Description
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Status
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Billable
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="timeEntriesLoading" class="animate-pulse">
                        <td
                            :colspan="canApprove ? 9 : 8"
                            class="px-6 py-4 text-center text-gray-500"
                        >
                            Loading time entries...
                        </td>
                    </tr>
                    <tr v-else-if="timeEntries?.data?.length === 0">
                        <td
                            :colspan="canApprove ? 9 : 8"
                            class="px-6 py-4 text-center text-gray-500"
                        >
                            No time entries found for this account.
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="entry in timeEntries?.data || []"
                        :key="entry.id"
                        class="hover:bg-gray-50"
                    >
                        <td v-if="canApprove" class="px-6 py-4 whitespace-nowrap">
                            <input
                                v-if="entry.status === 'pending'"
                                v-model="selectedEntries"
                                :value="entry.id"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-xs font-medium text-gray-700">
                                        {{ entry.user?.name?.charAt(0)?.toUpperCase() || "?" }}
                                    </span>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ entry.user?.name || "Unknown" }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ entry.user?.email }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="entry.ticket" class="text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ entry.ticket.ticket_number }}
                                </div>
                                <div class="text-gray-500 truncate max-w-xs" :title="entry.ticket.title">
                                    {{ entry.ticket.title }}
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500">
                                No ticket
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ formatDate(entry.started_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ formatDuration(entry.duration) }}
                            </div>
                            <div
                                v-if="entry.break_duration"
                                class="text-xs text-gray-500"
                            >
                                -{{ formatDuration(entry.break_duration) }} break
                            </div>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <div
                                class="text-sm text-gray-900 truncate"
                                :title="entry.description"
                            >
                                {{ entry.description || "No description" }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="getStatusClasses(entry.status)"
                                class="px-2 py-1 rounded-full text-xs font-medium"
                            >
                                {{ formatStatus(entry.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span
                                    v-if="entry.billable"
                                    class="text-green-600 text-sm font-medium"
                                >
                                    ${{ formatCurrency(entry.billable_amount) }}
                                </span>
                                <span v-else class="text-gray-400 text-sm">
                                    Non-billable
                                </span>
                            </div>
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                        >
                            <button
                                v-if="canEditTimeEntry(entry)"
                                @click="editEntry(entry)"
                                class="text-blue-600 hover:text-blue-700 transition-colors"
                            >
                                Edit
                            </button>
                            <button
                                v-if="canApprove && entry.status === 'pending'"
                                @click="approveEntry(entry)"
                                class="text-green-600 hover:text-green-700 transition-colors"
                            >
                                Approve
                            </button>
                            <button
                                v-if="canApprove && entry.status === 'pending'"
                                @click="rejectEntry(entry)"
                                class="text-red-600 hover:text-red-700 transition-colors"
                            >
                                Reject
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="timeEntries?.last_page > 1"
            class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
        >
            <div class="text-sm text-gray-700">
                Showing {{ timeEntries.from }} to {{ timeEntries.to }} of
                {{ timeEntries.total }} entries
            </div>
            <div class="flex items-center space-x-2">
                <button
                    @click="loadPage(timeEntries.current_page - 1)"
                    :disabled="timeEntries.current_page === 1"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Previous
                </button>
                <span class="text-sm text-gray-700">
                    Page {{ timeEntries.current_page }} of {{ timeEntries.last_page }}
                </span>
                <button
                    @click="loadPage(timeEntries.current_page + 1)"
                    :disabled="timeEntries.current_page === timeEntries.last_page"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div
            v-if="selectedEntries.length > 0 && canApprove"
            class="sticky bottom-0 bg-white border-t border-gray-200 p-4"
        >
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">
                    {{ selectedEntries.length }} entries selected
                </span>
                <div class="flex items-center space-x-2">
                    <button
                        @click="bulkApprove"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Approve Selected
                    </button>
                    <button
                        @click="bulkReject"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Reject Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Time Entry Modal -->
    <UnifiedTimeEntryDialog
        :show="showCreateModal || showEditModal"
        :mode="showEditModal ? 'edit' : 'create'"
        :time-entry="selectedEntry"
        :preselected-account-id="accountId"
        @close="closeModal"
        @saved="handleEntrySaved"
    />
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

// Import components and icons
import UnifiedTimeEntryDialog from "@/Components/TimeEntries/UnifiedTimeEntryDialog.vue";
import {
    ClockIcon,
    CurrencyDollarIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
} from "@heroicons/vue/24/outline";

// Props
const props = defineProps({
    accountId: {
        type: [String, Number],
        required: true,
    },
    account: {
        type: Object,
        default: () => ({}),
    },
});

// Page and user data
const page = usePage();
const user = computed(() => page.props.auth?.user);

// Reactive data
const timeEntries = ref(null);
const stats = ref({});
const availableUsers = ref([]);
const timeEntriesLoading = ref(false);
const selectedEntries = ref([]);
const selectAll = ref(false);

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedEntry = ref(null);

// Filters
const filters = ref({
    user_id: "",
    status: "",
    date_from: "",
    date_to: "",
    page: 1,
});

// Computed properties
const canCreateTimeEntry = computed(() => {
    return (
        user.value?.user_type === 'service_provider' ||
        user.value?.permissions?.includes("time.create") ||
        user.value?.permissions?.includes("time.manage") ||
        user.value?.permissions?.includes("admin.manage") ||
        user.value?.permissions?.includes("admin.write")
    );
});

const canApprove = computed(() => {
    return (
        user.value?.user_type === 'service_provider' ||
        user.value?.permissions?.includes("time.manage") ||
        user.value?.permissions?.includes("time.approve") ||
        user.value?.permissions?.includes("teams.manage") ||
        user.value?.permissions?.includes("admin.manage") ||
        user.value?.permissions?.includes("admin.write")
    );
});

// Permission checking methods (same as TimeTrackingManager)
const canEditTimeEntry = (entry) => {
    // Can't edit non-pending entries
    if (entry.status !== "pending") {
        return false;
    }
    
    // Original creator can always edit their own pending entries
    if (entry.user_id === user.value?.id) {
        return true;
    }
    
    // Service providers and users with time management permissions can edit time entries
    if (user.value?.user_type === 'service_provider' || 
        user.value?.permissions?.includes('time.manage') ||
        user.value?.permissions?.includes('time.edit.all') ||
        user.value?.permissions?.includes('admin.manage') ||
        user.value?.permissions?.includes('admin.write')) {
        return true;
    }
    
    // Team managers can edit entries from their team members
    if (user.value?.permissions?.includes('time.edit.team') ||
        user.value?.permissions?.includes('teams.manage')) {
        return true;
    }
    
    return false;
};

// Formatting methods
const formatHours = (seconds) => {
    if (!seconds) return "0.0";
    return (seconds / 3600).toFixed(1);
};

const formatCurrency = (amount) => {
    if (!amount) return "0.00";
    return parseFloat(amount).toFixed(2);
};

const formatDuration = (seconds) => {
    if (!seconds) return "0m";
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString();
};

const formatStatus = (status) => {
    return status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown';
};

const getStatusClasses = (status) => {
    const statusMap = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'billed': 'bg-blue-100 text-blue-800'
    };
    
    return statusMap[status] || 'bg-gray-100 text-gray-800';
};

// Data loading methods
const loadTimeEntries = async () => {
    if (!props.accountId) return;
    
    timeEntriesLoading.value = true;
    try {
        const params = new URLSearchParams({
            account_id: props.accountId,
            page: filters.value.page,
            per_page: 20,
            ...Object.fromEntries(
                Object.entries(filters.value).filter(([_, v]) => v !== "" && v !== null)
            ),
        });

        const response = await axios.get(`/api/time-entries?${params}`);
        timeEntries.value = response.data;
        
        // Load stats
        await loadStats();
    } catch (error) {
        console.error("Failed to load time entries:", error);
        timeEntries.value = { data: [], total: 0 };
    } finally {
        timeEntriesLoading.value = false;
    }
};

const loadStats = async () => {
    if (!props.accountId) return;
    
    try {
        const params = new URLSearchParams({
            account_id: props.accountId,
            ...Object.fromEntries(
                Object.entries(filters.value).filter(([key, v]) => 
                    key !== 'page' && v !== "" && v !== null
                )
            ),
        });
        
        const response = await axios.get(`/api/time-entries/stats?${params}`);
        stats.value = response.data.data || {};
    } catch (error) {
        console.error("Failed to load stats:", error);
        stats.value = {};
    }
};

const loadAvailableUsers = async () => {
    if (!props.accountId) return;
    
    try {
        const response = await axios.get(`/api/accounts/${props.accountId}/users`);
        availableUsers.value = response.data.data || [];
    } catch (error) {
        console.error("Failed to load users:", error);
        availableUsers.value = [];
    }
};

// Action methods
const applyFilters = () => {
    filters.value.page = 1;
    loadTimeEntries();
};

const clearFilters = () => {
    filters.value = {
        user_id: "",
        status: "",
        date_from: "",
        date_to: "",
        page: 1,
    };
    loadTimeEntries();
};

const loadPage = (page) => {
    if (page >= 1 && page <= timeEntries.value?.last_page) {
        filters.value.page = page;
        loadTimeEntries();
    }
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedEntries.value = timeEntries.value?.data
            ?.filter(entry => entry.status === 'pending')
            ?.map(entry => entry.id) || [];
    } else {
        selectedEntries.value = [];
    }
};

const editEntry = (entry) => {
    selectedEntry.value = entry;
    showEditModal.value = true;
};

const approveEntry = async (entry) => {
    try {
        await axios.post(`/api/time-entries/${entry.id}/approve`);
        loadTimeEntries();
    } catch (error) {
        console.error("Failed to approve entry:", error);
    }
};

const rejectEntry = async (entry) => {
    try {
        await axios.post(`/api/time-entries/${entry.id}/reject`);
        loadTimeEntries();
    } catch (error) {
        console.error("Failed to reject entry:", error);
    }
};

const bulkApprove = async () => {
    try {
        await axios.post("/api/time-entries/bulk/approve", {
            time_entry_ids: selectedEntries.value,
        });
        selectedEntries.value = [];
        selectAll.value = false;
        loadTimeEntries();
    } catch (error) {
        console.error("Failed to approve entries:", error);
    }
};

const bulkReject = async () => {
    try {
        await axios.post("/api/time-entries/bulk/reject", {
            time_entry_ids: selectedEntries.value,
        });
        selectedEntries.value = [];
        selectAll.value = false;
        loadTimeEntries();
    } catch (error) {
        console.error("Failed to reject entries:", error);
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedEntry.value = null;
};

const handleEntrySaved = () => {
    closeModal();
    loadTimeEntries();
};

// Watch for account changes
watch(() => props.accountId, (newAccountId) => {
    if (newAccountId) {
        loadTimeEntries();
        loadAvailableUsers();
    }
});

// Lifecycle
onMounted(() => {
    if (props.accountId) {
        loadTimeEntries();
        loadAvailableUsers();
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>