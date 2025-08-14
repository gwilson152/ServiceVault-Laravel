<template>
    <AppLayout title="User Details">
        <!-- Loading state -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading user details...</span>
        </div>
        
        <!-- Error state -->
        <div v-else-if="error" class="flex items-center justify-center py-12">
            <div class="rounded-md bg-red-50 p-4">
                <div class="text-sm text-red-700">{{ error }}</div>
            </div>
        </div>
        
        <!-- User details content -->
        <div v-else>
        <!-- Page Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div class="flex items-center space-x-4">
                        <!-- Back Button -->
                        <Link
                            href="/users"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            <svg
                                class="w-6 h-6"
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

                        <!-- User Avatar -->
                        <div class="flex-shrink-0 h-12 w-12">
                            <div
                                class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center"
                            >
                                <span
                                    class="text-indigo-600 font-medium text-lg"
                                >
                                    {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                                </span>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">
                                {{ user?.name || 'Unknown User' }}
                            </h1>
                            <div class="flex items-center gap-3 mt-1">
                                <p class="text-sm text-gray-600">
                                    {{ user?.email || "No email" }}
                                </p>

                                <!-- Status Badge -->
                                <span
                                    :class="
                                        getStatusBadge(
                                            user?.is_active
                                                ? 'active'
                                                : 'inactive'
                                        )
                                    "
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                >
                                    {{ user?.is_active ? "Active" : "Inactive" }}
                                </span>

                                <!-- Super Admin Badge -->
                                <span
                                    v-if="user.role_template?.is_super_admin"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"
                                >
                                    Super Admin
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <!-- Toggle Status -->
                        <button
                            @click="toggleUserStatus"
                            :class="[
                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                                user?.is_active
                                    ? 'text-orange-700 bg-orange-50 hover:bg-orange-100'
                                    : 'text-green-700 bg-green-50 hover:bg-green-100',
                            ]"
                        >
                            {{ user?.is_active ? "Deactivate" : "Activate" }}
                        </button>

                        <!-- Edit User -->
                        <button
                            @click="showEditModal = true"
                            class="px-3 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                        >
                            <svg
                                class="w-4 h-4 inline mr-1"
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
                            Edit
                        </button>

                        <!-- Delete User -->
                        <button
                            @click="confirmDelete"
                            class="px-3 py-2 text-sm font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                        >
                            <svg
                                class="w-4 h-4 inline mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Quick Stats Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6"
            >
                <!-- Active Status -->
                <div
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                >
                    <div class="flex items-center">
                        <div
                            :class="[
                                'p-2 rounded-lg',
                                user?.is_active ? 'bg-green-100' : 'bg-gray-100',
                            ]"
                        >
                            <svg
                                class="w-6 h-6"
                                :class="
                                    user?.is_active
                                        ? 'text-green-600'
                                        : 'text-gray-600'
                                "
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">
                                Status
                            </p>
                            <p
                                :class="[
                                    'text-2xl font-semibold',
                                    user?.is_active
                                        ? 'text-green-600'
                                        : 'text-gray-600',
                                ]"
                            >
                                {{ user?.is_active ? "Active" : "Inactive" }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Assigned Tickets -->
                <div
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                >
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2V9a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">
                                Assigned Tickets
                            </p>
                            <p class="text-2xl font-semibold text-blue-600">
                                {{
                                    user.statistics?.total_assigned_tickets || 0
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Time Entries -->
                <div
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                >
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">
                                Time Entries
                            </p>
                            <p class="text-2xl font-semibold text-green-600">
                                {{ user.statistics?.total_time_entries || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Time Logged -->
                <div
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
                >
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-purple-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">
                                Time Logged
                            </p>
                            <p class="text-2xl font-semibold text-purple-600">
                                {{
                                    formatDuration(
                                        user.statistics?.total_time_logged || 0
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div
                class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6"
            >
                <div class="border-b border-gray-200 px-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="setActiveTab('overview')"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'overview'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            Overview
                        </button>
                        <button
                            @click="setActiveTab('permissions')"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'permissions'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            Roles & Permissions
                        </button>
                        <button
                            @click="setActiveTab('activity')"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'activity'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            Activity
                        </button>
                        <button
                            @click="setActiveTab('tickets')"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'tickets'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            Service Tickets
                        </button>
                        <button
                            @click="setActiveTab('time-entries')"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'time-entries'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            Time Entries
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Overview Tab -->
                    <div v-if="activeTab === 'overview'">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Personal Information -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Personal Information
                                </h3>
                                <dl class="space-y-4">
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Full Name
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{ user?.name || 'Unknown User' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Email Address
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{ user?.email || "No email" }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Timezone
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{ user?.timezone || "Not set" }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Language
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{ user?.locale || "en" }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Member Since
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{
                                                user?.created_at 
                                                    ? new Date(user.created_at).toLocaleDateString()
                                                    : 'Unknown'
                                            }}
                                        </dd>
                                    </div>
                                    <div v-if="user?.last_active_at">
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Last Active
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{
                                                formatRelativeDate(
                                                    user.last_active_at
                                                )
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Account & Role Information -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Account & Role
                                </h3>
                                <dl class="space-y-4">
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Primary Account
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{
                                                user.account?.name ||
                                                "No account assigned"
                                            }}
                                        </dd>
                                        <dd
                                            v-if="user.account?.account_type"
                                            class="text-xs text-gray-500 mt-1"
                                        >
                                            {{
                                                formatAccountType(
                                                    user.account.account_type
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Role Template
                                        </dt>
                                        <dd class="text-sm text-gray-900 mt-1">
                                            {{
                                                user.role_template?.name ||
                                                "No role assigned"
                                            }}
                                        </dd>
                                        <dd
                                            v-if="user.role_template?.context"
                                            class="text-xs text-gray-500 mt-1"
                                        >
                                            {{
                                                formatContext(
                                                    user.role_template.context
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div
                                        v-if="
                                            user.role_template?.is_super_admin
                                        "
                                    >
                                        <dt
                                            class="text-sm font-medium text-gray-500"
                                        >
                                            Administrative Access
                                        </dt>
                                        <dd class="text-sm mt-1">
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800"
                                            >
                                                Super Administrator
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Recent Activity Summary -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Recent Activity
                                </h3>
                                <div class="space-y-4">
                                    <!-- Active Timers -->
                                    <div
                                        v-if="
                                            user.statistics
                                                ?.active_timers_count > 0
                                        "
                                        class="flex items-center p-3 bg-yellow-50 rounded-lg"
                                    >
                                        <div
                                            class="p-2 bg-yellow-100 rounded-lg"
                                        >
                                            <svg
                                                class="w-5 h-5 text-yellow-600"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p
                                                class="text-sm font-medium text-yellow-800"
                                            >
                                                {{
                                                    user.statistics
                                                        .active_timers_count
                                                }}
                                                Active Timer(s)
                                            </p>
                                            <p class="text-xs text-yellow-600">
                                                Currently running
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Recent Stats -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div
                                            class="text-center p-3 bg-white rounded-lg border"
                                        >
                                            <p
                                                class="text-2xl font-semibold text-blue-600"
                                            >
                                                {{
                                                    user.statistics
                                                        ?.total_assigned_tickets ||
                                                    0
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Total Tickets
                                            </p>
                                        </div>
                                        <div
                                            class="text-center p-3 bg-white rounded-lg border"
                                        >
                                            <p
                                                class="text-2xl font-semibold text-green-600"
                                            >
                                                {{
                                                    user.statistics
                                                        ?.total_time_entries ||
                                                    0
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Time Entries
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Quick Actions -->
                                    <div class="pt-2">
                                        <button
                                            @click="setActiveTab('tickets')"
                                            class="w-full text-left p-2 text-sm text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        >
                                            → View all tickets
                                        </button>
                                        <button
                                            @click="
                                                setActiveTab('time-entries')
                                            "
                                            class="w-full text-left p-2 text-sm text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                        >
                                            → View time entries
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Roles & Permissions Tab -->
                    <div v-else-if="activeTab === 'permissions'">
                        <div class="space-y-6">
                            <!-- Role Template Information -->
                            <div
                                class="bg-white border border-gray-200 rounded-lg p-6"
                            >
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Assigned Role Template
                                </h3>
                                <div
                                    v-if="user.role_template"
                                    class="space-y-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div>
                                            <h4
                                                class="text-md font-medium text-gray-900"
                                            >
                                                {{ user.role_template.name }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                {{
                                                    user.role_template
                                                        .description ||
                                                    "No description available"
                                                }}
                                            </p>
                                            <div
                                                class="flex items-center gap-2 mt-2"
                                            >
                                                <span
                                                    class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded"
                                                >
                                                    {{
                                                        formatContext(
                                                            user.role_template
                                                                .context
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-if="
                                                        user.role_template
                                                            .is_super_admin
                                                    "
                                                    class="text-xs px-2 py-1 bg-purple-100 text-purple-800 rounded"
                                                >
                                                    Super Admin
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-500">
                                                {{
                                                    user.role_template
                                                        .permissions?.length ||
                                                    0
                                                }}
                                                permissions
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8">
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                        />
                                    </svg>
                                    <h3
                                        class="mt-2 text-sm font-medium text-gray-900"
                                    >
                                        No Role Assigned
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        This user does not have a role template
                                        assigned.
                                    </p>
                                </div>
                            </div>

                            <!-- Permissions Breakdown -->
                            <div
                                v-if="user.role_template?.permissions"
                                class="bg-white border border-gray-200 rounded-lg p-6"
                            >
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-4"
                                >
                                    Permission Details
                                </h3>
                                <div
                                    class="grid grid-cols-1 md:grid-cols-3 gap-6"
                                >
                                    <!-- Functional Permissions -->
                                    <div>
                                        <h4
                                            class="text-sm font-medium text-gray-900 mb-3"
                                        >
                                            Functional Permissions
                                        </h4>
                                        <div class="space-y-2">
                                            <div
                                                v-for="permission in getFunctionalPermissions(
                                                    user.role_template
                                                        .permissions
                                                )"
                                                :key="permission"
                                                class="text-sm text-gray-600 flex items-center"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-green-500 mr-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                {{
                                                    formatPermissionName(
                                                        permission
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Widget Permissions -->
                                    <div>
                                        <h4
                                            class="text-sm font-medium text-gray-900 mb-3"
                                        >
                                            Widget Access
                                        </h4>
                                        <div class="space-y-2">
                                            <div
                                                v-for="permission in getWidgetPermissions(
                                                    user.role_template
                                                        .permissions
                                                )"
                                                :key="permission"
                                                class="text-sm text-gray-600 flex items-center"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-blue-500 mr-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                                                    />
                                                </svg>
                                                {{
                                                    formatPermissionName(
                                                        permission
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Page Permissions -->
                                    <div>
                                        <h4
                                            class="text-sm font-medium text-gray-900 mb-3"
                                        >
                                            Page Access
                                        </h4>
                                        <div class="space-y-2">
                                            <div
                                                v-for="permission in getPagePermissions(
                                                    user.role_template
                                                        .permissions
                                                )"
                                                :key="permission"
                                                class="text-sm text-gray-600 flex items-center"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-purple-500 mr-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    />
                                                </svg>
                                                {{
                                                    formatPermissionName(
                                                        permission
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                    <div class="bg-purple-50 p-4 rounded-lg">
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
                                        <div class="text-sm text-purple-600">
                                            Time Logged
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 p-4 rounded-lg">
                                        <div
                                            class="text-2xl font-bold text-yellow-600"
                                        >
                                            {{
                                                userActivity.statistics
                                                    .active_timers
                                            }}
                                        </div>
                                        <div class="text-sm text-yellow-600">
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
                                                    userActivity.recent_activity
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
                                                    userActivity.recent_activity
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
                                                    userActivity.recent_activity
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
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
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
                                            <div class="text-sm text-gray-500">
                                                {{
                                                    ticket.account?.display_name
                                                }}
                                            </div>
                                            <div class="text-xs text-gray-400">
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
                    <div v-else-if="activeTab === 'time-entries'" class="p-6">
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
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
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
                                                        v-if="entry.billable"
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
                                <div class="flex items-center justify-between">
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
                                            <span class="text-xs text-gray-500"
                                                >{{
                                                    account.users_count
                                                }}
                                                users</span
                                            >
                                            <span class="text-xs text-gray-500"
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

        <!-- User Edit Modal -->
        <UserFormModal
            :open="showEditModal"
            :user="user"
            :accounts="accounts"
            :roleTemplates="roleTemplates"
            @close="showEditModal = false"
            @saved="handleUserUpdated"
        />

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-6 w-6 text-red-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Delete User
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete "{{
                                        user?.name || 'this user'
                                    }}"? This action cannot be undone and will
                                    remove all associated data.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="px-6 py-4 bg-gray-50 flex items-center justify-end space-x-2"
                >
                    <button
                        @click="showDeleteConfirm = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteUser"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Delete User
                    </button>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import UserFormModal from "@/Components/UserFormModal.vue";
import { useUserQuery, useUpdateUserMutation, useDeleteUserMutation, useUserActivityQuery, useUserTicketsQuery, useUserTimeEntriesQuery } from '@/Composables/queries/useUsersQuery'
import { useAccountSelectorQuery } from '@/Composables/queries/useAccountsQuery'
import { useRoleTemplatesQuery } from '@/Composables/queries/useUsersQuery'

// Props
const props = defineProps({
  userId: {
    type: [String, Number],
    required: true
  }
})

// Create reactive user ID
const userId = computed(() => {
  const id = props.userId
  // Ensure it's a string
  return typeof id === 'string' ? id : String(id)
})

// TanStack Query hooks
const { data: userData, isLoading: userLoading, error: userError } = useUserQuery(userId)
const { data: accountsData, isLoading: accountsLoading } = useAccountSelectorQuery()
const { data: roleTemplatesData, isLoading: roleTemplatesLoading } = useRoleTemplatesQuery()
const updateUserMutation = useUpdateUserMutation()
const deleteUserMutation = useDeleteUserMutation()

// Tab-specific query hooks
const { data: activityData, isLoading: activityLoading } = useUserActivityQuery(userId)
const { data: ticketsData, isLoading: ticketsLoading } = useUserTicketsQuery(userId)
const { data: timeEntriesData, isLoading: timeEntriesLoading } = useUserTimeEntriesQuery(userId)

// Computed properties for data
const user = computed(() => userData.value?.data)
const accounts = computed(() => accountsData.value?.data || [])
const roleTemplates = computed(() => roleTemplatesData.value?.data || [])
const userActivity = computed(() => activityData.value?.data)
const userTickets = computed(() => ticketsData.value?.data || [])
const userTimeEntries = computed(() => timeEntriesData.value?.data || [])
const loading = computed(() => userLoading.value || accountsLoading.value || roleTemplatesLoading.value)
const error = computed(() => {
  if (userError.value) return 'Failed to load user details'
  return null
})

// Reactive data
const activeTab = ref("overview");
const showEditModal = ref(false);
const showDeleteConfirm = ref(false);

// Methods
const setActiveTab = (tab) => {
    activeTab.value = tab;
    // TanStack Query will automatically load data when needed
};


const handleUserUpdated = () => {
    showEditModal.value = false;
    // TanStack Query will automatically invalidate and refetch
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

// New helper methods for the enhanced UI
const formatRelativeDate = (dateString) => {
    if (!dateString) return "";

    const date = new Date(dateString);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "today";
    if (diffDays === 1) return "yesterday";
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;

    return date.toLocaleDateString();
};

const formatAccountType = (type) => {
    const types = {
        customer: "Customer",
        prospect: "Prospect",
        partner: "Partner",
        internal: "Internal",
        individual: "Individual",
    };
    return types[type] || type;
};

const formatContext = (context) => {
    const contexts = {
        service_provider: "Service Provider",
        account_user: "Account User",
    };
    return contexts[context] || context;
};

const formatPermissionName = (permission) => {
    return permission
        .split(".")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ")
        .replace("_", " ");
};

const getFunctionalPermissions = (permissions) => {
    return permissions.filter(
        (p) =>
            !p.startsWith("widgets.") &&
            !p.startsWith("pages.") &&
            !p.includes("dashboard")
    );
};

const getWidgetPermissions = (permissions) => {
    return permissions.filter(
        (p) => p.startsWith("widgets.") || p.includes("dashboard")
    );
};

const getPagePermissions = (permissions) => {
    return permissions.filter((p) => p.startsWith("pages."));
};

// Action methods
const toggleUserStatus = async () => {
    if (!user.value) return;
    
    try {
        await updateUserMutation.mutateAsync({
            id: user.value.id,
            data: {
                ...user.value,
                is_active: !user.value.is_active,
            }
        });
    } catch (error) {
        console.error("Failed to toggle user status:", error);
    }
};

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const deleteUser = async () => {
    if (!user.value) return;
    
    try {
        await deleteUserMutation.mutateAsync(user.value.id);
        router.visit("/users");
    } catch (error) {
        console.error("Failed to delete user:", error);
    }
};

// No lifecycle hooks needed - TanStack Query handles data loading automatically
</script>
