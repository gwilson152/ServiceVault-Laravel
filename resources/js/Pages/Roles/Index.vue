<template>
    <div>
        <Head title="Roles & Permissions" />

        <!-- Page Header -->
        <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        Roles & Permissions
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage role templates and permissions
                    </p>
                </div>
            </div>
        </div>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header Section -->
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            Role Templates
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Manage role templates with three-dimensional
                            permissions: functional, widget, and page access
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        @click="$inertia.visit(route('roles.create'))"
                    >
                        Create Role Template
                    </button>
                </div>

                <!-- Role Templates List -->
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div v-if="loading" class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-center">
                            <div
                                class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
                            ></div>
                            <span class="ml-2 text-gray-600"
                                >Loading role templates...</span
                            >
                        </div>
                    </div>

                    <div v-else-if="error" class="px-4 py-5 sm:p-6">
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="text-sm text-red-700">
                                {{ error }}
                            </div>
                        </div>
                    </div>

                    <ul
                        v-else-if="roleTemplates.length > 0"
                        role="list"
                        class="divide-y divide-gray-200"
                    >
                        <li
                            v-for="template in roleTemplates"
                            :key="template.id"
                            class="px-4 py-4 sm:px-6"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center"
                                        >
                                            <svg
                                                class="h-5 w-5 text-indigo-600"
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
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <p
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ template.name }}
                                            </p>
                                            <span
                                                v-if="!template.is_modifiable"
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                            >
                                                System Role
                                            </span>
                                            <span
                                                v-if="template.is_default"
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            >
                                                Default
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            {{ template.description }}
                                        </p>
                                        <div
                                            class="mt-1 flex items-center text-xs text-gray-500"
                                        >
                                            <span
                                                >{{
                                                    template.users_count
                                                }}
                                                users</span
                                            >
                                            <span class="mx-2">•</span>
                                            <span
                                                >{{
                                                    template.permission_counts
                                                        .functional
                                                }}
                                                functional</span
                                            >
                                            <span class="mx-2">•</span>
                                            <span
                                                >{{
                                                    template.permission_counts
                                                        .widget
                                                }}
                                                widget</span
                                            >
                                            <span class="mx-2">•</span>
                                            <span
                                                >{{
                                                    template.permission_counts
                                                        .page
                                                }}
                                                page permissions</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        @click="openPreview(template)"
                                    >
                                        <svg
                                            class="w-4 h-4 mr-1.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        Preview
                                    </button>
                                    <button
                                        v-if="template.is_modifiable"
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                                        @click="openWidgetManager(template)"
                                    >
                                        <svg
                                            class="w-4 h-4 mr-1.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"
                                            />
                                        </svg>
                                        Widgets
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        @click="
                                            $inertia.visit(
                                                route('roles.show', template.id)
                                            )
                                        "
                                    >
                                        View
                                    </button>
                                    <button
                                        v-if="template.is_modifiable"
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        @click="
                                            $inertia.visit(
                                                route('roles.edit', template.id)
                                            )
                                        "
                                    >
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="px-4 py-5 sm:p-6">
                        <div class="text-center">
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No role templates
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Get started by creating your first role
                                template.
                            </p>
                            <div class="mt-6">
                                <button
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    @click="
                                        $inertia.visit(route('roles.create'))
                                    "
                                >
                                    Create Role Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Preview Modal -->
        <DashboardPreviewModal
            :open="showPreviewModal"
            :role-template="selectedRoleTemplate"
            @close="showPreviewModal = false"
        />

        <!-- Widget Assignment Modal -->
        <WidgetAssignmentModal
            :open="showWidgetModal"
            :role-template="selectedRoleTemplate"
            @close="showWidgetModal = false"
            @saved="handleWidgetsSaved"
        />
    </div>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DashboardPreviewModal from "@/Components/DashboardPreviewModal.vue";
import WidgetAssignmentModal from "@/Components/WidgetAssignmentModal.vue";
import { ref, onMounted } from "vue";
import axios from "axios";

// Define persistent layout
defineOptions({
    layout: AppLayout,
});

const roleTemplates = ref([]);
const loading = ref(true);
const error = ref(null);
const showPreviewModal = ref(false);
const showWidgetModal = ref(false);
const selectedRoleTemplate = ref(null);

const loadRoleTemplates = async () => {
    try {
        loading.value = true;
        const response = await axios.get("/api/role-templates");
        roleTemplates.value = response.data.data;
    } catch (err) {
        error.value = "Failed to load role templates";
        console.error("Role templates loading error:", err);
    } finally {
        loading.value = false;
    }
};

const openPreview = (template) => {
    selectedRoleTemplate.value = template;
    showPreviewModal.value = true;
};

const openWidgetManager = (template) => {
    selectedRoleTemplate.value = template;
    showWidgetModal.value = true;
};

const handleWidgetsSaved = () => {
    showWidgetModal.value = false;
    // Optionally reload role templates to refresh counts
    loadRoleTemplates();
};

onMounted(() => {
    loadRoleTemplates();
});
</script>
