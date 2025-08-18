<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Page Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Back Button -->
                        <Link
                            :href="route('tickets.index')"
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

                        <!-- Ticket Info -->
                        <div>
                            <div class="flex items-center space-x-3">
                                <h1 class="text-2xl font-bold text-gray-900">
                                    {{ ticket?.title || "Loading..." }}
                                </h1>
                                <button
                                    v-if="canEdit && ticket?.title"
                                    @click="startEditingTitle"
                                    class="text-blue-600 hover:text-blue-700 text-sm transition-colors"
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
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center space-x-3 mt-1">
                                <span
                                    class="text-sm font-medium text-gray-500"
                                    >{{ ticket?.ticket_number }}</span
                                >
                                <span
                                    v-if="ticket"
                                    :class="statusClasses"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ formatStatus(ticket.status) }}
                                </span>
                                <span
                                    v-if="ticket"
                                    :class="priorityClasses"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ formatPriority(ticket.priority) }}
                                </span>
                                <span
                                    v-if="ticket?.due_date"
                                    :class="dueDateClasses"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    Due {{ formatDate(ticket.due_date) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <!-- Timer Controls -->
                        <div
                            v-if="canManageTime"
                            class="flex items-center space-x-2"
                        >
                            <button
                                v-if="!activeTimer"
                                @click="startTimer"
                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center space-x-2"
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
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>Start Timer</span>
                            </button>

                            <div v-else class="flex items-center space-x-2">
                                <div
                                    class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 flex items-center space-x-2"
                                >
                                    <div
                                        class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                                    ></div>
                                    <span class="text-green-700 font-medium">{{
                                        formatDuration(activeTimer.duration)
                                    }}</span>
                                </div>
                                <button
                                    @click="stopTimer"
                                    class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                                >
                                    Stop
                                </button>
                            </div>
                        </div>

                        <!-- Status Change -->
                        <div v-if="canChangeStatus" class="relative">
                            <select
                                :value="ticket?.status"
                                @change="changeStatus($event.target.value)"
                                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="waiting_customer">
                                    Waiting Customer
                                </option>
                                <option value="on_hold">On Hold</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <!-- More Actions -->
                        <div class="relative" ref="actionsDropdown">
                            <button
                                @click="showActionsMenu = !showActionsMenu"
                                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                                        d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                                    />
                                </svg>
                            </button>

                            <div
                                v-if="showActionsMenu"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10"
                            >
                                <div class="py-1">
                                    <button
                                        @click="duplicateTicket"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        Duplicate Ticket
                                    </button>
                                    <button
                                        @click="exportTicket"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        Export to PDF
                                    </button>
                                    <hr class="my-1" />
                                    <button
                                        @click="deleteTicket"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                    >
                                        Delete Ticket
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <div
                    class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
                ></div>
                <p class="mt-2 text-gray-600">Loading ticket details...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="ticketError" class="text-center py-12">
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
                <p class="text-red-600 font-medium">Failed to load ticket</p>
                <p class="text-gray-500 text-sm mt-1">{{ ticketError }}</p>
                <button
                    @click="refetchTicket"
                    class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                >
                    Try Again
                </button>
            </div>

            <!-- No Ticket Found -->
            <div v-else-if="!ticket" class="text-center py-12">
                <div class="text-gray-400 mb-4">
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
                <p class="text-gray-600 font-medium">Ticket not found</p>
                <p class="text-gray-500 text-sm mt-1">
                    The ticket you're looking for doesn't exist or you don't
                    have permission to view it.
                </p>
                <Link
                    :href="route('tickets.index')"
                    class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-block"
                >
                    Back to Tickets
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                <!-- Main Content Area (3 columns) -->
                <div class="xl:col-span-3 space-y-6">
                    <!-- Ticket Description -->
                    <div
                        class="bg-white rounded-lg shadow-sm border border-gray-200"
                    >
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Description
                                </h2>
                                <button
                                    v-if="canEdit && !editingDescription"
                                    @click="startEditingDescription"
                                    class="text-blue-600 hover:text-blue-700 text-sm transition-colors"
                                >
                                    Edit
                                </button>
                            </div>

                            <div v-if="editingDescription">
                                <textarea
                                    v-model="editedDescription"
                                    rows="6"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Describe the issue or request..."
                                ></textarea>
                                <div class="flex items-center space-x-2 mt-3">
                                    <button
                                        @click="saveDescription"
                                        :disabled="savingDescription"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                                    >
                                        {{
                                            savingDescription
                                                ? "Saving..."
                                                : "Save"
                                        }}
                                    </button>
                                    <button
                                        @click="cancelEditingDescription"
                                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <div v-else class="prose max-w-none text-gray-700">
                                <div
                                    v-if="ticket.description"
                                    v-html="
                                        formatDescription(ticket.description)
                                    "
                                ></div>
                                <div v-else class="text-gray-500 italic">
                                    No description provided.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabbed Content Area -->
                    <div
                        class="bg-white rounded-lg shadow-sm border border-gray-200"
                    >
                        <!-- Tab Navigation -->
                        <div class="border-b border-gray-200">
                            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="navigateToTab(tab.id)"
                                    :class="[
                                        activeTab === tab.id
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                                    ]"
                                >
                                    {{ tab.label }}
                                    <span
                                        v-if="tab.badge"
                                        class="ml-2 bg-gray-100 text-gray-900 py-0.5 px-2 rounded-full text-xs font-medium"
                                    >
                                        {{ tab.badge }}
                                    </span>
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        <div class="p-6">
                            <!-- Comments & Messages Tab -->
                            <div v-if="activeTab === 'messages'">
                                <TicketCommentsSection
                                    :ticket-id="props.ticketId"
                                    :messages="messages"
                                    :permissions="permissions"
                                    :on-message-sent="sendMessage"
                                    :on-new-message="handleNewComment"
                                />
                            </div>

                            <!-- Time Tracking Tab -->
                            <div v-if="activeTab === 'time'" class="space-y-6">
                                <TimeTrackingManager
                                    :ticket="ticket"
                                    :permissions="permissions"
                                    @updated="loadTimeTrackingData"
                                />
                            </div>

                            <!-- Addons Tab -->
                            <div
                                v-if="activeTab === 'addons'"
                                class="space-y-6"
                            >
                                <TicketAddonManager
                                    :ticket="ticket"
                                    :permissions="permissions"
                                    @updated="refetchTicket"
                                />
                            </div>

                            <!-- Activity Timeline Tab -->
                            <div
                                v-if="activeTab === 'activity'"
                                class="space-y-6"
                            >
                                <ActivityTimeline :ticket="ticket" />
                            </div>

                            <!-- Billing Tab -->
                            <div
                                v-if="activeTab === 'billing'"
                                class="space-y-6"
                            >
                                <BillingOverview :ticket="ticket" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (1 column) -->
                <div class="xl:col-span-1 space-y-6">
                    <!-- Ticket Details Card -->
                    <div
                        class="bg-white rounded-lg shadow-sm border border-gray-200"
                    >
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Details
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Status -->
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Status</label
                                >
                                <div class="mt-1 flex items-center space-x-2">
                                    <span
                                        :class="statusClasses"
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ formatStatus(ticket.status) }}
                                    </span>
                                    <button
                                        v-if="canChangeStatus"
                                        @click="showStatusModal = true"
                                        class="text-blue-600 hover:text-blue-700 text-xs"
                                    >
                                        Change
                                    </button>
                                </div>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Priority</label
                                >
                                <div class="mt-1">
                                    <span
                                        :class="priorityClasses"
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ formatPriority(ticket.priority) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Account -->
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Account</label
                                >
                                <div class="mt-1">
                                    <Link
                                        v-if="ticket.account?.id"
                                        :href="
                                            route(
                                                'accounts.show',
                                                ticket.account.id
                                            )
                                        "
                                        class="text-sm text-blue-600 hover:text-blue-700"
                                    >
                                        {{ ticket.account.name }}
                                    </Link>
                                    <span v-else class="text-sm text-gray-500">
                                        No Account Assigned
                                    </span>
                                </div>
                            </div>

                            <!-- Assigned Agent -->
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Assigned Agent</label
                                >
                                <div class="mt-1 flex items-center space-x-2">
                                    <span class="text-sm text-gray-900">{{
                                        ticket.agent?.name || "Unassigned"
                                    }}</span>
                                    <button
                                        v-if="canAssign"
                                        @click="showAssignModal = true"
                                        class="text-blue-600 hover:text-blue-700 text-xs"
                                    >
                                        {{
                                            ticket.agent ? "Reassign" : "Assign"
                                        }}
                                    </button>
                                </div>
                            </div>

                            <!-- Customer -->
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Customer</label
                                >
                                <div class="mt-1">
                                    <p class="text-sm text-gray-900">
                                        {{
                                            ticket.customer?.name ||
                                            ticket.customer_name ||
                                            "No customer assigned"
                                        }}
                                    </p>
                                    <p
                                        v-if="ticket.customer_email"
                                        class="text-xs text-gray-500"
                                    >
                                        {{ ticket.customer_email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Due Date -->
                            <div v-if="ticket.due_date">
                                <label class="text-sm font-medium text-gray-600"
                                    >Due Date</label
                                >
                                <div class="mt-1">
                                    <span
                                        :class="dueDateClasses"
                                        class="text-sm"
                                    >
                                        {{ formatDate(ticket.due_date) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Estimated Hours -->
                            <div v-if="ticket.estimated_hours">
                                <label class="text-sm font-medium text-gray-600"
                                    >Estimated Hours</label
                                >
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ ticket.estimated_hours }}h
                                </p>
                            </div>

                            <!-- Created -->
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Created</label
                                >
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ formatDateTime(ticket.created_at) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    by {{ ticket.createdBy?.name || "Unknown" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Time Summary Card -->
                    <div
                        class="bg-white rounded-lg shadow-sm border border-gray-200"
                    >
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Time Summary
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600"
                                    >Total Logged</label
                                >
                                <p
                                    class="mt-1 text-2xl font-bold text-gray-900"
                                >
                                    {{ formatDuration(totalTimeLogged) }}
                                </p>
                            </div>

                            <div
                                v-if="
                                    permissions?.canViewTimers &&
                                    activeTimers?.length > 0
                                "
                            >
                                <label class="text-sm font-medium text-gray-600"
                                    >Active Timers</label
                                >
                                <div class="mt-2 space-y-2">
                                    <div
                                        v-for="timer in activeTimers"
                                        :key="timer.id"
                                        class="flex items-center justify-between p-2 bg-green-50 border border-green-200 rounded-lg"
                                    >
                                        <div
                                            class="flex items-center space-x-2"
                                        >
                                            <div
                                                class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                                            ></div>
                                            <span
                                                class="text-sm text-gray-700"
                                                >{{
                                                    timer.user?.name ||
                                                    "Unknown"
                                                }}</span
                                            >
                                        </div>
                                        <span
                                            class="text-sm font-medium text-green-700"
                                            >{{
                                                formatDuration(timer.duration)
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <div v-if="estimatedVsActual">
                                <label class="text-sm font-medium text-gray-600"
                                    >Progress</label
                                >
                                <div class="mt-2">
                                    <div
                                        class="flex justify-between text-sm text-gray-600"
                                    >
                                        <span
                                            >{{
                                                formatDuration(totalTimeLogged)
                                            }}
                                            /
                                            {{
                                                formatDuration(
                                                    ticket.estimated_hours *
                                                        3600
                                                )
                                            }}</span
                                        >
                                        <span
                                            >{{
                                                Math.round(estimatedVsActual)
                                            }}%</span
                                        >
                                    </div>
                                    <div
                                        class="mt-1 bg-gray-200 rounded-full h-2"
                                    >
                                        <div
                                            :class="
                                                estimatedVsActual > 100
                                                    ? 'bg-red-500'
                                                    : 'bg-blue-500'
                                            "
                                            :style="{
                                                width:
                                                    Math.min(
                                                        estimatedVsActual,
                                                        100
                                                    ) + '%',
                                            }"
                                            class="h-2 rounded-full transition-all duration-300"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments Card -->
                    <div
                        class="bg-white rounded-lg shadow-sm border border-gray-200"
                    >
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Attachments
                            </h3>
                        </div>
                        <div class="p-6">
                            <div
                                v-if="ticketAttachments.length > 0"
                                class="space-y-3"
                            >
                                <div
                                    v-for="attachment in ticketAttachments"
                                    :key="`${attachment.message_id}-${attachment.filename}`"
                                    class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                                >
                                    <div class="flex items-center space-x-3">
                                        <!-- File icon based on type -->
                                        <div class="flex-shrink-0">
                                            <svg
                                                v-if="
                                                    attachment.mime_type?.startsWith(
                                                        'image/'
                                                    )
                                                "
                                                class="w-5 h-5 text-blue-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <svg
                                                v-else-if="
                                                    attachment.mime_type ===
                                                    'application/pdf'
                                                "
                                                class="w-5 h-5 text-red-500"
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
                                            <svg
                                                v-else
                                                class="w-5 h-5 text-gray-500"
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
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="text-sm font-medium text-gray-900 truncate"
                                            >
                                                {{ attachment.filename }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{
                                                    formatFileSize(
                                                        attachment.size
                                                    )
                                                }}
                                                •
                                                {{
                                                    formatDateTime(
                                                        attachment.message_date
                                                    )
                                                }}
                                                •
                                                {{
                                                    attachment.uploaded_by.name
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <a
                                        :href="`/storage/${attachment.path}`"
                                        target="_blank"
                                        class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>

                            <div v-else class="text-center py-6">
                                <svg
                                    class="w-8 h-8 text-gray-400 mx-auto mb-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                    />
                                </svg>
                                <p class="text-sm text-gray-500">
                                    No attachments yet
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Related Tickets Card -->
                    <div
                        v-if="relatedTickets?.length > 0"
                        class="bg-white rounded-lg shadow-sm border border-gray-200"
                    >
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Related Tickets
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div
                                    v-for="relatedTicket in relatedTickets"
                                    :key="relatedTicket.id"
                                    class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                                >
                                    <div class="flex-1">
                                        <Link
                                            v-if="relatedTicket.id"
                                            :href="
                                                route(
                                                    'tickets.show',
                                                    relatedTicket.id
                                                )
                                            "
                                            class="text-sm font-medium text-blue-600 hover:text-blue-700"
                                        >
                                            {{ relatedTicket.title }}
                                        </Link>
                                        <span
                                            v-else
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ relatedTicket.title }}
                                        </span>
                                        <p class="text-xs text-gray-500">
                                            {{ relatedTicket.ticket_number }}
                                        </p>
                                    </div>
                                    <span
                                        :class="
                                            getStatusClasses(
                                                relatedTicket.status
                                            )
                                        "
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ formatStatus(relatedTicket.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals and Overlays -->
        <TitleEditModal
            v-if="editingTitle"
            :ticket="ticket"
            @saved="handleTitleSaved"
            @cancelled="editingTitle = false"
        />

        <StatusChangeModal
            v-if="showStatusModal"
            :ticket="ticket"
            @changed="handleStatusChanged"
            @cancelled="showStatusModal = false"
        />

        <AssignmentModal
            v-if="showAssignModal"
            :ticket="ticket"
            @assigned="handleAssignmentChanged"
            @cancelled="showAssignModal = false"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";
import {
    useTicketQuery,
    useTicketCommentsQuery,
    useTicketTimersQuery,
    useTicketTimeEntriesQuery,
    useTicketRelatedQuery,
    useUpdateTicketMutation,
    useAddCommentMutation,
} from "@/Composables/queries/useTicketsQuery";
import { useTicketCommentBroadcasting } from "@/Composables/useTicketCommentBroadcasting";

// Import components
import TimeTrackingManager from "@/Components/Tickets/TimeTrackingManager.vue";
import TicketAddonManager from "@/Components/Tickets/TicketAddonManager.vue";
import ActivityTimeline from "@/Components/Tickets/ActivityTimeline.vue";
import BillingOverview from "@/Components/Tickets/BillingOverview.vue";
import TitleEditModal from "@/Components/Tickets/TitleEditModal.vue";
import StatusChangeModal from "@/Components/Tickets/StatusChangeModal.vue";
import AssignmentModal from "@/Components/Tickets/AssignmentModal.vue";
import TicketCommentsSection from "@/Components/Tickets/TicketCommentsSection.vue";

// Define persistent layout
defineOptions({
    layout: AppLayout,
});

// Props
const props = defineProps({
    ticketId: {
        type: String,
        required: true,
    },
    ticket: {
        type: Object,
        required: false,
    },
    permissions: {
        type: Object,
        required: false,
        default: () => ({}),
    },
    activeTab: {
        type: String,
        required: false,
        default: null,
    },
});

// Access user data from Inertia page props
const page = usePage();
const user = computed(() => page.props.auth?.user);

// Get ticket ID from props
const ticketId = computed(() => props.ticketId);

// TanStack Query hooks for data fetching
const {
    data: ticket,
    isLoading: ticketLoading,
    error: ticketError,
    refetch: refetchTicket,
} = useTicketQuery(ticketId);
// Only query comments if user has permission to view them
const {
    data: messages,
    isLoading: messagesLoading,
    refetch: refetchMessages,
} = props.permissions?.canViewComments
    ? useTicketCommentsQuery(ticketId)
    : { data: ref([]), isLoading: ref(false), refetch: () => {} };

// Real-time comment handling
const handleNewComment = (comment) => {
    console.log("New comment received via broadcasting:", comment);

    // Always refetch comments to ensure we get the latest data with proper formatting
    // This is more reliable than manually manipulating the reactive data
    console.log("Triggering comment refetch due to new comment");
    refetchMessages();
};

// Set up real-time broadcasting for comments with user permissions
const userPermissions = {
    canViewInternalComments:
        props.permissions?.canViewInternalComments || false,
    isAdmin: user.value?.isSuperAdmin || false,
    canManageTickets: props.permissions?.canManageTickets || false,
};
useTicketCommentBroadcasting(props.ticketId, handleNewComment, userPermissions);

// Only query timers if user has timer permissions
const { data: activeTimers, isLoading: timersLoading } = props.permissions
    ?.canViewTimers
    ? useTicketTimersQuery(ticketId)
    : { data: ref([]), isLoading: ref(false) };

// Only query time entries if user has permission
const { data: timeEntries, isLoading: timeEntriesLoading } = props.permissions
    ?.canViewTimeEntries
    ? useTicketTimeEntriesQuery(ticketId)
    : { data: ref([]), isLoading: ref(false) };

// Related tickets for activity tab
const { data: relatedTickets, isLoading: relatedLoading } = props.permissions
    ?.canViewActivity
    ? useTicketRelatedQuery(ticketId)
    : { data: ref([]), isLoading: ref(false) };

// Mutations
const updateTicketMutation = useUpdateTicketMutation();
const addCommentMutation = useAddCommentMutation();

// UI State
// Initialize active tab from prop or default to first available tab
const activeTab = ref(props.activeTab || "messages");
const editingDescription = ref(false);
const editedDescription = ref("");
const savingDescription = ref(false);
const editingTitle = ref(false);
const showStatusModal = ref(false);
const showAssignModal = ref(false);
const activeTimer = ref(null);
const showActionsMenu = ref(false);

// Debug log to see what we received
console.log("Ticket Show page initialized with ticketId:", props.ticketId);
console.log("Ticket data from TanStack Query:", ticket.value);

// Computed properties
const loading = computed(() => ticketLoading.value || messagesLoading.value);
const totalTimeLogged = computed(() => {
    if (!timeEntries.value) return 0;
    return timeEntries.value.reduce(
        (total, entry) => total + (entry.duration || 0),
        0
    );
});

const tabs = computed(() => {
    const availableTabs = [];

    // Messages tab - always available if user can view comments
    if (props.permissions?.canViewComments) {
        availableTabs.push({
            id: "messages",
            label: "Messages",
            badge: messages.value?.length > 0 ? messages.value.length : null,
        });
    }

    // Time Tracking tab - only if user can view timers or time entries
    if (
        props.permissions?.canViewTimers ||
        props.permissions?.canViewTimeEntries
    ) {
        availableTabs.push({
            id: "time",
            label: "Time Tracking",
            badge:
                activeTimers.value?.length > 0
                    ? activeTimers.value.length
                    : null,
        });
    }

    // Add-ons tab - only if user can view add-ons
    if (props.permissions?.canViewAddons) {
        availableTabs.push({
            id: "addons",
            label: "Add-ons",
        });
    }

    // Activity tab - only if user can view activity
    if (props.permissions?.canViewActivity) {
        availableTabs.push({
            id: "activity",
            label: "Activity",
        });
    }

    // Billing tab - only if user can view billing
    if (props.permissions?.canViewBilling) {
        availableTabs.push({
            id: "billing",
            label: "Billing",
        });
    }

    return availableTabs;
});

// Watch for tabs changes and update active tab if current one is not available
watch(
    tabs,
    (newTabs) => {
        if (newTabs.length > 0) {
            // If current active tab is not available, redirect to first available tab
            if (!newTabs.find((tab) => tab.id === activeTab.value)) {
                const firstTab = newTabs[0].id;
                // Navigate to the first available tab if current tab is not accessible
                navigateToTab(firstTab);
            }
        }
    },
    { immediate: true }
);

// Watch for activeTab prop changes from URL navigation
watch(
    () => props.activeTab,
    (newActiveTab) => {
        if (newActiveTab && newActiveTab !== activeTab.value) {
            // Check if the tab is available to the user
            const availableTab = tabs.value.find(tab => tab.id === newActiveTab);
            if (availableTab) {
                activeTab.value = newActiveTab;
            }
        }
    },
    { immediate: true }
);

const statusClasses = computed(() => {
    if (!ticket.value) return "";
    return getStatusClasses(ticket.value.status);
});

const priorityClasses = computed(() => {
    if (!ticket.value) return "";

    const priorityMap = {
        low: "bg-gray-100 text-gray-800",
        normal: "bg-blue-100 text-blue-800",
        medium: "bg-yellow-100 text-yellow-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };

    return priorityMap[ticket.value.priority] || "bg-gray-100 text-gray-800";
});

const dueDateClasses = computed(() => {
    if (!ticket.value?.due_date) return "";

    const dueDate = new Date(ticket.value.due_date);
    const now = new Date();
    const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return "text-red-600 font-medium"; // Overdue
    } else if (diffDays <= 1) {
        return "text-orange-600 font-medium"; // Due soon
    } else {
        return "text-gray-600"; // Normal
    }
});

const estimatedVsActual = computed(() => {
    if (!ticket.value?.estimated_hours || !totalTimeLogged.value) return null;
    return (
        (totalTimeLogged.value / (ticket.value.estimated_hours * 3600)) * 100
    );
});

const canEdit = computed(() => {
    // TODO: Implement proper permission checking
    return true;
});

const canChangeStatus = computed(() => {
    // TODO: Implement proper permission checking
    return true;
});

const canAssign = computed(() => {
    // TODO: Implement proper permission checking
    return true;
});

const canManageTime = computed(() => {
    // Check permissions passed from backend
    return (
        props.permissions?.canViewTimers ||
        props.permissions?.canCreateTimers ||
        false
    );
});

const currentUserId = computed(() => user.value?.id);

// Methods
const navigateToTab = (tabId) => {
    // Update the URL to include the tab parameter
    const currentUrl = route('tickets.show', { ticket: props.ticketId });
    const newUrl = tabId === 'messages' ? currentUrl : `${currentUrl}/${tabId}`;
    
    // Navigate with Inertia to maintain state
    router.visit(newUrl, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Replace current history entry instead of adding new one
        onSuccess: () => {
            activeTab.value = tabId;
        }
    });
};

const getStatusClasses = (status) => {
    const statusMap = {
        open: "bg-blue-100 text-blue-800",
        in_progress: "bg-yellow-100 text-yellow-800",
        waiting_customer: "bg-purple-100 text-purple-800",
        on_hold: "bg-gray-100 text-gray-800",
        resolved: "bg-green-100 text-green-800",
        closed: "bg-gray-100 text-gray-800",
        cancelled: "bg-red-100 text-red-800",
    };

    return statusMap[status] || "bg-gray-100 text-gray-800";
};

const formatStatus = (status) => {
    return (
        status?.replace("_", " ").replace(/\b\w/g, (l) => l.toUpperCase()) ||
        "Unknown"
    );
};

const formatPriority = (priority) => {
    return priority?.charAt(0).toUpperCase() + priority?.slice(1) || "Unknown";
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatDateTime = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatDuration = (seconds) => {
    if (!seconds) return "0m";

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const formatDescription = (description) => {
    if (!description) return "";
    // Simple HTML formatting for line breaks
    return description.replace(/\n/g, "<br>");
};

const formatMessage = (content) => {
    if (!content) return "";
    // Simple HTML formatting for line breaks and basic markdown
    return content
        .replace(/\n/g, "<br>")
        .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
        .replace(/\*(.*?)\*/g, "<em>$1</em>");
};

// Computed properties for active timer (from the current user)
const activeTimerFromData = computed(() => {
    if (!activeTimers.value || !user.value?.id) return null;
    return activeTimers.value.find((timer) => timer.user_id === user.value.id);
});

// Update activeTimer to use the computed value
watch(
    activeTimerFromData,
    (newTimer) => {
        activeTimer.value = newTimer;
    },
    { immediate: true }
);

// Action methods
const sendMessage = async (messageData) => {
    if (!ticket.value?.id) return;
    
    try {
        // Create FormData as expected by the API
        const formData = new FormData();
        formData.append("content", messageData.content);
        formData.append("is_internal", messageData.is_internal ? "1" : "0");
        
        await addCommentMutation.mutateAsync({
            ticketId: ticket.value.id,
            formData,
        });
        
        // Refresh messages to show the new comment
        refetchMessages();
    } catch (error) {
        console.error("Failed to send message:", error);
        throw error; // Re-throw so the component can handle it
    }
};

const startTimer = async () => {
    if (!ticket.value?.id || activeTimer.value) return;

    try {
        const response = await axios.post("/api/timers", {
            ticket_id: ticket.value.id,
            description: `Working on ${ticket.value.title || "ticket"}`,
        });

        // Refresh timer data
        await loadTimeTrackingData();
    } catch (error) {
        console.error("Failed to start timer:", error);
    }
};

const stopTimer = async () => {
    if (!activeTimer.value) return;

    try {
        await axios.post(`/api/timers/${activeTimer.value.id}/stop`);

        // Refresh timer data
        await loadTimeTrackingData();
    } catch (error) {
        console.error("Failed to stop timer:", error);
    }
};

const changeStatus = async (newStatus) => {
    if (!ticket.value?.id || ticket.value.status === newStatus) return;

    try {
        const response = await axios.put(`/api/tickets/${ticket.value.id}`, {
            status: newStatus,
        });

        ticket.value.status = newStatus;

        // Reload messages to show status change activity
        await loadMessages();
    } catch (error) {
        console.error("Failed to change status:", error);
    }
};

// Inline editing methods
const startEditingDescription = () => {
    editingDescription.value = true;
    editedDescription.value = ticket.value.description || "";
};

const cancelEditingDescription = () => {
    editingDescription.value = false;
    editedDescription.value = "";
};

const saveDescription = async () => {
    if (!ticket.value?.id) return;

    savingDescription.value = true;

    try {
        await axios.put(`/api/tickets/${ticket.value.id}`, {
            description: editedDescription.value.trim(),
        });

        ticket.value.description = editedDescription.value.trim();
        editingDescription.value = false;
        editedDescription.value = "";

        // Reload messages to show description change activity
        await loadMessages();
    } catch (error) {
        console.error("Failed to save description:", error);
    } finally {
        savingDescription.value = false;
    }
};

const startEditingTitle = () => {
    editingTitle.value = true;
};

// Modal handlers
const handleTitleSaved = (newTitle) => {
    editingTitle.value = false;
    // Refetch ticket data and messages to get updated info and activity log
    refetchTicket();
    refetchMessages();
};

const handleStatusChanged = (newStatus) => {
    showStatusModal.value = false;
    // Refetch data to get updated status and activity log
    refetchTicket();
    refetchMessages();
};

const handleAssignmentChanged = (newAgent) => {
    showAssignModal.value = false;
    // Refetch data to get updated assignment and activity log
    refetchTicket();
    refetchMessages();
};

const ticketAttachments = ref([]);

// Load all attachments for the ticket
const loadTicketAttachments = async () => {
    if (!ticket.value?.id) return;

    try {
        const response = await axios.get(
            `/api/tickets/${ticket.value.id}/comments`
        );

        // Extract all attachments from messages
        const allAttachments = [];
        response.data.data.forEach((message) => {
            if (message.attachments && message.attachments.length > 0) {
                message.attachments.forEach((attachment) => {
                    allAttachments.push({
                        ...attachment,
                        message_id: message.id,
                        message_date: message.created_at,
                        uploaded_by: message.user,
                    });
                });
            }
        });

        ticketAttachments.value = allAttachments.reverse(); // Newest first
    } catch (error) {
        console.error("Failed to load ticket attachments:", error);
    }
};

// File upload handler
const handleFileUpload = async (event) => {
    const files = Array.from(event.target.files);
    if (files.length === 0) return;

    selectedFiles.value = files;

    // Clear the input to allow re-selecting the same files if needed
    event.target.value = "";
};

// Action menu methods
const duplicateTicket = () => {
    // TODO: Implement ticket duplication
    console.log("Duplicate ticket");
    showActionsMenu.value = false;
};

const exportTicket = () => {
    // TODO: Implement ticket export
    console.log("Export ticket");
    showActionsMenu.value = false;
};

const deleteTicket = () => {
    // TODO: Implement ticket deletion with confirmation
    console.log("Delete ticket");
    showActionsMenu.value = false;
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (
        showActionsMenu.value &&
        !event.target.closest('[ref="actionsDropdown"]')
    ) {
        showActionsMenu.value = false;
    }
};

// Lifecycle
onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});
</script>
