<template>
    <AppLayout title="User Details">
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- User Header -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="h-20 w-20 bg-gray-300 rounded-full flex items-center justify-center"
                                    >
                                        <svg
                                            class="h-8 w-8 text-gray-600"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-6">
                                    <h1
                                        class="text-2xl font-bold text-gray-900"
                                    >
                                        {{ user.name }}
                                    </h1>
                                    <p class="text-sm text-gray-500">
                                        {{ user.email }}
                                    </p>
                                    <div
                                        class="flex items-center mt-2 space-x-3"
                                    >
                                        <span
                                            :class="
                                                getStatusBadge(
                                                    user.is_active
                                                        ? 'active'
                                                        : 'inactive'
                                                )
                                            "
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{
                                                user.is_active
                                                    ? "Active"
                                                    : "Inactive"
                                            }}
                                        </span>
                                        <span
                                            v-if="user.is_super_admin"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                        >
                                            Super Admin
                                        </span>
                                        <span
                                            v-if="user.last_active_at"
                                            class="text-sm text-gray-500"
                                        >
                                            Last active
                                            {{
                                                new Date(
                                                    user.last_active_at
                                                ).toLocaleDateString()
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <button
                                    @click="showEditModal = true"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                    Edit User
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="mb-6">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8">
                                <button
                                    @click="setActiveTab('profile')"
                                    :class="[
                                        activeTab === 'profile'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    ]"
                                >
                                    Profile
                                </button>
                                <button
                                    @click="setActiveTab('activity')"
                                    :class="[
                                        activeTab === 'activity'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    ]"
                                >
                                    Activity
                                </button>
                                <button
                                    @click="setActiveTab('tickets')"
                                    :class="[
                                        activeTab === 'tickets'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    ]"
                                >
                                    Service Tickets
                                </button>
                                <button
                                    @click="setActiveTab('time-entries')"
                                    :class="[
                                        activeTab === 'time-entries'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    ]"
                                >
                                    Time Entries
                                </button>
                                <button
                                    @click="setActiveTab('accounts')"
                                    :class="[
                                        activeTab === 'accounts'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                    ]"
                                >
                                    Account Access
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="bg-white shadow rounded-lg">
                        <!-- Profile Tab -->
                        <div v-if="activeTab === 'profile'" class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Personal Information -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Personal Information
                                    </h3>
                                    <dl class="space-y-3">
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Full Name
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{ user.name }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Email Address
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{ user.email }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Timezone
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{ user.timezone || "Not set" }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Language
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{ user.locale || "en" }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Account Status
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                <span
                                                    :class="
                                                        getStatusBadge(
                                                            user.is_active
                                                                ? 'active'
                                                                : 'inactive'
                                                        )
                                                    "
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                >
                                                    {{
                                                        user.is_active
                                                            ? "Active"
                                                            : "Inactive"
                                                    }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Member Since
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    new Date(
                                                        user.created_at
                                                    ).toLocaleDateString()
                                                }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Statistics -->
                                <div v-if="user.statistics">
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Statistics
                                    </h3>
                                    <dl class="space-y-3">
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Assigned Tickets
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    user.statistics
                                                        .total_assigned_tickets
                                                }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Created Tickets
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    user.statistics
                                                        .total_created_tickets
                                                }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Total Time Entries
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    user.statistics
                                                        .total_time_entries
                                                }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Total Time Logged
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    formatDuration(
                                                        user.statistics
                                                            .total_time_logged
                                                    )
                                                }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Active Timers
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                <span
                                                    :class="
                                                        user.statistics
                                                            .active_timers_count >
                                                        0
                                                            ? 'text-green-600'
                                                            : 'text-gray-900'
                                                    "
                                                >
                                                    {{
                                                        user.statistics
                                                            .active_timers_count
                                                    }}
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Tab -->
                        <div v-else-if="activeTab === 'activity'" class="p-6">
                            <div v-if="userActivity" class="space-y-6">
                                <!-- Recent Login Info -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Login Activity
                                    </h3>
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Last Login
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    userActivity.recent_logins
                                                        .last_login_at
                                                        ? new Date(
                                                              userActivity.recent_logins.last_login_at
                                                          ).toLocaleString()
                                                        : "Never"
                                                }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt
                                                class="text-sm font-medium text-gray-500"
                                            >
                                                Last Activity
                                            </dt>
                                            <dd class="text-sm text-gray-900">
                                                {{
                                                    userActivity.recent_logins
                                                        .last_active_at
                                                        ? new Date(
                                                              userActivity.recent_logins.last_active_at
                                                          ).toLocaleString()
                                                        : "Never"
                                                }}
                                            </dd>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Statistics -->
                                <div>
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Activity Statistics
                                    </h3>
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-4 gap-4"
                                    >
                                        <div class="bg-blue-50 p-4 rounded-lg">
                                            <div
                                                class="text-2xl font-bold text-blue-600"
                                            >
                                                {{
                                                    userActivity.statistics
                                                        .total_tickets_assigned
                                                }}
                                            </div>
                                            <div class="text-sm text-blue-600">
                                                Assigned Tickets
                                            </div>
                                        </div>
                                        <div class="bg-green-50 p-4 rounded-lg">
                                            <div
                                                class="text-2xl font-bold text-green-600"
                                            >
                                                {{
                                                    userActivity.statistics
                                                        .total_time_entries
                                                }}
                                            </div>
                                            <div class="text-sm text-green-600">
                                                Time Entries
                                            </div>
                                        </div>
                                        <div
                                            class="bg-purple-50 p-4 rounded-lg"
                                        >
                                            <div
                                                class="text-2xl font-bold text-purple-600"
                                            >
                                                {{
                                                    formatDuration(
                                                        userActivity.statistics
                                                            .total_time_logged
                                                    )
                                                }}
                                            </div>
                                            <div
                                                class="text-sm text-purple-600"
                                            >
                                                Time Logged
                                            </div>
                                        </div>
                                        <div
                                            class="bg-yellow-50 p-4 rounded-lg"
                                        >
                                            <div
                                                class="text-2xl font-bold text-yellow-600"
                                            >
                                                {{
                                                    userActivity.statistics
                                                        .active_timers
                                                }}
                                            </div>
                                            <div
                                                class="text-sm text-yellow-600"
                                            >
                                                Active Timers
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Activity -->
                                <div v-if="userActivity.recent_activity">
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Recent Activity
                                    </h3>

                                    <div
                                        class="grid grid-cols-1 lg:grid-cols-3 gap-6"
                                    >
                                        <!-- Recent Tickets -->
                                        <div>
                                            <h4
                                                class="text-sm font-medium text-gray-700 mb-2"
                                            >
                                                Recent Tickets
                                            </h4>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="ticket in userActivity
                                                        .recent_activity
                                                        .recent_tickets"
                                                    :key="ticket.id"
                                                    class="p-3 bg-gray-50 rounded-md"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900 truncate"
                                                    >
                                                        {{ ticket.title }}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{ ticket.status }} •
                                                        {{
                                                            new Date(
                                                                ticket.created_at
                                                            ).toLocaleDateString()
                                                        }}
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="
                                                        userActivity
                                                            .recent_activity
                                                            .recent_tickets
                                                            .length === 0
                                                    "
                                                    class="text-sm text-gray-500"
                                                >
                                                    No recent tickets
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recent Time Entries -->
                                        <div>
                                            <h4
                                                class="text-sm font-medium text-gray-700 mb-2"
                                            >
                                                Recent Time Entries
                                            </h4>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="entry in userActivity
                                                        .recent_activity
                                                        .recent_time_entries"
                                                    :key="entry.id"
                                                    class="p-3 bg-gray-50 rounded-md"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900 truncate"
                                                    >
                                                        {{ entry.description }}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{
                                                            formatDuration(
                                                                entry.duration
                                                            )
                                                        }}
                                                        • {{ entry.status }}
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="
                                                        userActivity
                                                            .recent_activity
                                                            .recent_time_entries
                                                            .length === 0
                                                    "
                                                    class="text-sm text-gray-500"
                                                >
                                                    No recent time entries
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recent Timers -->
                                        <div>
                                            <h4
                                                class="text-sm font-medium text-gray-700 mb-2"
                                            >
                                                Recent Timers
                                            </h4>
                                            <div class="space-y-2">
                                                <div
                                                    v-for="timer in userActivity
                                                        .recent_activity
                                                        .recent_timers"
                                                    :key="timer.id"
                                                    class="p-3 bg-gray-50 rounded-md"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900 truncate"
                                                    >
                                                        {{
                                                            timer.description ||
                                                            "Untitled Timer"
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        <span
                                                            :class="
                                                                getStatusBadge(
                                                                    timer.status
                                                                )
                                                            "
                                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium mr-2"
                                                        >
                                                            {{ timer.status }}
                                                        </span>
                                                        {{
                                                            formatDuration(
                                                                timer.duration
                                                            )
                                                        }}
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="
                                                        userActivity
                                                            .recent_activity
                                                            .recent_timers
                                                            .length === 0
                                                    "
                                                    class="text-sm text-gray-500"
                                                >
                                                    No recent timers
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Tickets Tab -->
                        <div v-else-if="activeTab === 'tickets'" class="p-6">
                            <div
                                v-if="ticketsLoading"
                                class="flex items-center justify-center py-8"
                            >
                                <div
                                    class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"
                                ></div>
                                <span class="ml-2 text-gray-600"
                                    >Loading tickets...</span
                                >
                            </div>
                            <div v-else>
                                <h3
                                    class="text-lg font-medium text-gray-900 mb-4"
                                >
                                    Assigned Service Tickets
                                </h3>
                                <div
                                    v-if="userTickets.length > 0"
                                    class="space-y-4"
                                >
                                    <div
                                        v-for="ticket in userTickets"
                                        :key="ticket.id"
                                        class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div class="flex-1">
                                                <h4
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ ticket.title }}
                                                </h4>
                                                <p
                                                    class="text-sm text-gray-500 mt-1"
                                                >
                                                    {{ ticket.description }}
                                                </p>
                                                <div
                                                    class="flex items-center mt-2 space-x-4"
                                                >
                                                    <span
                                                        class="text-xs text-gray-500"
                                                        >Ticket #{{
                                                            ticket.ticket_number
                                                        }}</span
                                                    >
                                                    <span
                                                        :class="
                                                            getStatusBadge(
                                                                ticket.status
                                                            )
                                                        "
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                    >
                                                        {{
                                                            ticket.status.replace(
                                                                "_",
                                                                " "
                                                            )
                                                        }}
                                                    </span>
                                                    <span
                                                        :class="
                                                            getTicketPriorityBadge(
                                                                ticket.priority
                                                            )
                                                        "
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                    >
                                                        {{ ticket.priority }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div
                                                    class="text-sm text-gray-500"
                                                >
                                                    {{
                                                        ticket.account
                                                            ?.display_name
                                                    }}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-400"
                                                >
                                                    {{
                                                        new Date(
                                                            ticket.created_at
                                                        ).toLocaleDateString()
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 48 48"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v16m18 0V5a2 2 0 00-2-2H9m0 0h10M9 7v4h6m4 0v8m-8 0h8m-8 4h8"
                                        />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">
                                        No assigned tickets found.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Time Entries Tab -->
                        <div
                            v-else-if="activeTab === 'time-entries'"
                            class="p-6"
                        >
                            <div
                                v-if="timeEntriesLoading"
                                class="flex items-center justify-center py-8"
                            >
                                <div
                                    class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"
                                ></div>
                                <span class="ml-2 text-gray-600"
                                    >Loading time entries...</span
                                >
                            </div>
                            <div v-else>
                                <h3
                                    class="text-lg font-medium text-gray-900 mb-4"
                                >
                                    Time Entries
                                </h3>
                                <div v-if="userTimeEntries.length > 0">
                                    <div class="overflow-x-auto">
                                        <table
                                            class="min-w-full divide-y divide-gray-200"
                                        >
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                    >
                                                        Description
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                    >
                                                        Duration
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                    >
                                                        Status
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                    >
                                                        Date
                                                    </th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                    >
                                                        Cost
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="bg-white divide-y divide-gray-200"
                                            >
                                                <tr
                                                    v-for="entry in userTimeEntries"
                                                    :key="entry.id"
                                                >
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                                    >
                                                        {{
                                                            entry.description ||
                                                            "No description"
                                                        }}
                                                        <div
                                                            v-if="
                                                                entry.billable
                                                            "
                                                            class="text-xs text-green-600"
                                                        >
                                                            Billable
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                                    >
                                                        {{
                                                            entry.duration_formatted
                                                        }}
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap"
                                                    >
                                                        <span
                                                            :class="
                                                                getStatusBadge(
                                                                    entry.status
                                                                )
                                                            "
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                        >
                                                            {{ entry.status }}
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                                    >
                                                        {{
                                                            new Date(
                                                                entry.started_at
                                                            ).toLocaleDateString()
                                                        }}
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                                    >
                                                        {{
                                                            entry.calculated_cost
                                                                ? formatCurrency(
                                                                      entry.calculated_cost
                                                                  )
                                                                : "—"
                                                        }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 48 48"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3h4V8"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8a4 4 0 11-8 0 4 4 0 018 0zM8 12v.01M16 12v.01"
                                        />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">
                                        No time entries found.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Accounts Tab -->
                        <div v-else-if="activeTab === 'accounts'" class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Account Access
                            </h3>
                            <div
                                v-if="user.accounts && user.accounts.length > 0"
                                class="space-y-4"
                            >
                                <div
                                    v-for="account in user.accounts"
                                    :key="account.id"
                                    class="border border-gray-200 rounded-lg p-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div>
                                            <h4
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ account.display_name }}
                                            </h4>
                                            <p
                                                v-if="account.company_name"
                                                class="text-sm text-gray-500"
                                            >
                                                {{ account.company_name }}
                                            </p>
                                            <div
                                                class="flex items-center mt-2 space-x-3"
                                            >
                                                <span
                                                    :class="
                                                        getStatusBadge(
                                                            account.account_type
                                                        )
                                                    "
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                >
                                                    {{ account.account_type }}
                                                </span>
                                                <span
                                                    class="text-xs text-gray-500"
                                                    >{{
                                                        account.users_count
                                                    }}
                                                    users</span
                                                >
                                                <span
                                                    class="text-xs text-gray-500"
                                                    >Level
                                                    {{
                                                        account.hierarchy_level
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-500">
                                                Assigned
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{
                                                    account.assigned_at
                                                        ? new Date(
                                                              account.assigned_at
                                                          ).toLocaleDateString()
                                                        : "Unknown"
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <svg
                                    class="mx-auto h-12 w-12 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 48 48"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m14 0l-2-2m2 2l-2-2"
                                    />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">
                                    No account assignments found.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Edit Modal -->
        <UserFormModal
            :open="showEditModal"
            :user="user"
            :accounts="accounts"
            :roleTemplates="roleTemplates"
            @close="showEditModal = false"
            @saved="handleUserUpdated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import UserFormModal from "@/Components/UserFormModal.vue";

// Props
const props = defineProps({
    user: Object,
    accounts: Array,
    roleTemplates: Array,
});

// Reactive data
const activeTab = ref("profile");
const showEditModal = ref(false);
const userActivity = ref(null);
const userTickets = ref([]);
const userTimeEntries = ref([]);
const ticketsLoading = ref(false);
const timeEntriesLoading = ref(false);

// Methods
const setActiveTab = (tab) => {
    activeTab.value = tab;

    if (tab === "activity" && !userActivity.value) {
        loadUserActivity();
    } else if (tab === "tickets" && userTickets.value.length === 0) {
        loadUserTickets();
    } else if (tab === "time-entries" && userTimeEntries.value.length === 0) {
        loadUserTimeEntries();
    }
};

const loadUserActivity = async () => {
    try {
        const response = await fetch(`/api/users/${props.user.id}/activity`);
        const data = await response.json();
        userActivity.value = data.data;
    } catch (error) {
        console.error("Error loading user activity:", error);
    }
};

const loadUserTickets = async () => {
    ticketsLoading.value = true;
    try {
        const response = await fetch(`/api/users/${props.user.id}/tickets`);
        const data = await response.json();
        userTickets.value = data.data;
    } catch (error) {
        console.error("Error loading user tickets:", error);
    } finally {
        ticketsLoading.value = false;
    }
};

const loadUserTimeEntries = async () => {
    timeEntriesLoading.value = true;
    try {
        const response = await fetch(
            `/api/users/${props.user.id}/time-entries`
        );
        const data = await response.json();
        userTimeEntries.value = data.data;
    } catch (error) {
        console.error("Error loading user time entries:", error);
    } finally {
        timeEntriesLoading.value = false;
    }
};

const handleUserUpdated = (updatedUser) => {
    showEditModal.value = false;
    // Reload the page to reflect changes
    router.reload();
};

const getStatusBadge = (status) => {
    const badges = {
        active: "bg-green-100 text-green-800",
        inactive: "bg-gray-100 text-gray-800",
        pending: "bg-yellow-100 text-yellow-800",
        approved: "bg-green-100 text-green-800",
        rejected: "bg-red-100 text-red-800",
        running: "bg-blue-100 text-blue-800",
        paused: "bg-yellow-100 text-yellow-800",
        stopped: "bg-gray-100 text-gray-800",
        customer: "bg-blue-100 text-blue-800",
        internal: "bg-purple-100 text-purple-800",
        open: "bg-blue-100 text-blue-800",
        in_progress: "bg-yellow-100 text-yellow-800",
        resolved: "bg-green-100 text-green-800",
        closed: "bg-gray-100 text-gray-800",
    };
    return badges[status] || "bg-gray-100 text-gray-800";
};

const getTicketPriorityBadge = (priority) => {
    const badges = {
        low: "bg-gray-100 text-gray-800",
        normal: "bg-blue-100 text-blue-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };
    return badges[priority] || "bg-gray-100 text-gray-800";
};

const formatDuration = (seconds) => {
    if (!seconds) return "0:00:00";
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours}:${minutes.toString().padStart(2, "0")}:${secs
        .toString()
        .padStart(2, "0")}`;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount);
};

// Lifecycle
onMounted(() => {
    // Load initial activity data if the activity tab is the default
    if (activeTab.value === "activity") {
        loadUserActivity();
    }
});
</script>
