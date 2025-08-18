<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">
                    Addon Templates
                </h3>
                <p class="text-sm text-gray-600">
                    Create reusable templates for common addon items that can be
                    quickly added to tickets.
                </p>
            </div>
            <button
                @click="showCreateModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                </svg>
                Add Template
            </button>
        </div>

        <!-- Categories Filter -->
        <div class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700"
                >Filter by category:</label
            >
            <select
                v-model="selectedCategory"
                class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">All Categories</option>
                <option
                    v-for="(label, key) in categoryOptions"
                    :key="key"
                    :value="key"
                >
                    {{ label }}
                </option>
            </select>
        </div>

        <!-- Loading State -->
        <div
            v-if="addonTemplatesQuery.isLoading.value"
            class="flex justify-center py-8"
        >
            <div
                class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"
            ></div>
        </div>

        <!-- Error State -->
        <div
            v-else-if="addonTemplatesQuery.isError.value"
            class="text-center py-8"
        >
            <p class="text-red-600">Failed to load addon templates</p>
            <button
                @click="addonTemplatesQuery.refetch()"
                class="mt-2 text-indigo-600 hover:text-indigo-500"
            >
                Try again
            </button>
        </div>

        <!-- Templates List -->
        <div
            v-else-if="filteredTemplates.length > 0"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="template in filteredTemplates"
                :key="template.id"
                class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <h4 class="text-sm font-medium text-gray-900">
                                {{ template.name }}
                            </h4>
                            <span
                                :class="[
                                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                    template.is_active
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-gray-100 text-gray-800',
                                ]"
                            >
                                {{ template.is_active ? "Active" : "Inactive" }}
                            </span>
                        </div>

                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mb-2"
                        >
                            {{ getCategoryLabel(template.category) }}
                        </span>

                        <p
                            v-if="template.description"
                            class="text-sm text-gray-600 mb-3"
                        >
                            {{ template.description }}
                        </p>

                        <div class="text-sm text-gray-500 space-y-1">
                            <div
                                v-if="template.sku"
                                class="flex justify-between"
                            >
                                <span>SKU:</span>
                                <span class="font-medium">{{
                                    template.sku
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Default Qty:</span>
                                <span class="font-medium">{{
                                    template.default_quantity
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Unit Price:</span>
                                <span class="font-medium"
                                    >${{ template.default_unit_price }}</span
                                >
                            </div>
                            <div class="flex justify-between">
                                <span>Billable:</span>
                                <span
                                    :class="
                                        template.billable
                                            ? 'text-green-600'
                                            : 'text-gray-500'
                                    "
                                >
                                    {{ template.billable ? "Yes" : "No" }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-2 ml-4">
                        <button
                            @click="editTemplate(template)"
                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteTemplate(template)"
                            class="text-red-600 hover:text-red-900 text-sm font-medium"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="text-center py-12 bg-white border border-gray-200 rounded-lg"
        >
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
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                {{
                    selectedCategory
                        ? "No templates in this category"
                        : "No addon templates"
                }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                {{
                    selectedCategory
                        ? "Try selecting a different category."
                        : "Get started by creating your first addon template."
                }}
            </p>
            <div class="mt-6">
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Add Template
                </button>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <AddonTemplateModal
            :show="showCreateModal"
            :template="selectedTemplate"
            @close="closeModal"
        />
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import {
    useAddonTemplatesQuery,
    useDeleteAddonTemplateMutation,
} from "@/Composables/queries/useAddonTemplatesQuery";
import AddonTemplateModal from "./AddonTemplateModal.vue";

// TanStack Query hooks
const addonTemplatesQuery = useAddonTemplatesQuery();
const deleteTemplateMutation = useDeleteAddonTemplateMutation();

// Local state
const showCreateModal = ref(false);
const selectedTemplate = ref(null);
const selectedCategory = ref("");

// Category options
const categoryOptions = {
    product: "Product",
    service: "Service",
    license: "License",
    hardware: "Hardware",
    software: "Software",
    expense: "Expense",
    other: "Other",
};

// Computed
const addonTemplates = computed(() => addonTemplatesQuery.data.value || []);

const filteredTemplates = computed(() => {
    if (!selectedCategory.value) return addonTemplates.value;
    return addonTemplates.value.filter(
        (template) => template.category === selectedCategory.value
    );
});

// Methods
const getCategoryLabel = (categoryKey) => {
    return categoryOptions[categoryKey] || categoryKey;
};

const editTemplate = (template) => {
    selectedTemplate.value = template;
    showCreateModal.value = true;
};

const deleteTemplate = async (template) => {
    if (
        !confirm(
            `Are you sure you want to delete the addon template "${template.name}"?`
        )
    )
        return;

    try {
        await deleteTemplateMutation.mutateAsync(template.id);
    } catch (error) {
        console.error("Failed to delete addon template:", error);
        alert("Failed to delete addon template. Please try again.");
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    selectedTemplate.value = null;
};
</script>
