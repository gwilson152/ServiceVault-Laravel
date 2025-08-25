<template>
    <StackedDialog
        :show="show"
        @close="$emit('close')"
        title="Query Builder Import Preview"
        max-width="7xl"
    >
        <div class="space-y-6">
            <!-- Profile Header -->
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CircleStackIcon class="h-6 w-6 text-indigo-600" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-indigo-900">
                                {{ profile?.name }}
                            </h3>
                            <p class="text-sm text-indigo-700">
                                Import Simulation • {{ profile?.host }}:{{
                                    profile?.port
                                }}/{{ profile?.database }}
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="hasQueryConfiguration"
                        @click="runSimulation"
                        :disabled="isRunningSimulation"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 disabled:opacity-50"
                    >
                        <ArrowPathIcon
                            v-if="isRunningSimulation"
                            class="animate-spin -ml-1 mr-2 h-4 w-4"
                        />
                        <PlayIcon v-else class="-ml-1 mr-2 h-4 w-4" />
                        {{
                            isRunningSimulation
                                ? "Running..."
                                : "Run Simulation"
                        }}
                    </button>
                </div>
            </div>

            <!-- Configuration Status -->
            <div
                v-if="!hasQueryConfiguration"
                class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center"
            >
                <ExclamationTriangleIcon
                    class="mx-auto h-12 w-12 text-amber-400"
                />
                <h4 class="mt-2 text-sm font-medium text-amber-900">
                    Query Configuration Required
                </h4>
                <p class="mt-1 text-sm text-amber-700">
                    This profile needs a custom query configuration to simulate
                    imports.
                </p>
                <div class="mt-4">
                    <button
                        @click="$emit('open-query-builder')"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200"
                    >
                        <CogIcon class="-ml-1 mr-2 h-4 w-4" />
                        Query Builder
                    </button>
                </div>
            </div>

            <!-- Query Configuration Display -->
            <div
                v-else-if="queryConfig"
                class="bg-white border border-gray-200 rounded-lg"
            >
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-medium text-gray-900">
                        Query Configuration
                    </h4>
                    <p class="text-sm text-gray-600">
                        Built with Query Builder
                    </p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Configuration Details -->
                        <div class="space-y-4">
                            <div>
                                <h5 class="text-sm font-medium text-gray-900">
                                    Base Table
                                </h5>
                                <p
                                    class="text-sm text-gray-700 font-mono bg-gray-50 px-2 py-1 rounded"
                                >
                                    {{ queryConfig?.base_table || 'No base table' }}
                                </p>
                            </div>

                            <div v-if="queryConfig?.joins?.length">
                                <h5 class="text-sm font-medium text-gray-900">
                                    Joins ({{ queryConfig.joins.length }})
                                </h5>
                                <div class="space-y-2">
                                    <div
                                        v-for="join in queryConfig.joins"
                                        :key="`${join.table}-${join.leftColumn}`"
                                        class="text-xs bg-gray-50 p-2 rounded font-mono"
                                    >
                                        {{ join.type }} JOIN {{ join.table }} ON
                                        {{ join.leftColumn }} =
                                        {{ join.rightColumn }}
                                    </div>
                                </div>
                            </div>

                            <div v-if="queryConfig?.fields?.length">
                                <h5 class="text-sm font-medium text-gray-900">
                                    Selected Fields ({{
                                        queryConfig.fields.length
                                    }})
                                </h5>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="field in queryConfig.fields.slice(
                                            0,
                                            8
                                        )"
                                        :key="field.source"
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                    >
                                        {{ field.source }}
                                        <ArrowRightIcon class="ml-1 h-3 w-3" />
                                        {{ field.target }}
                                    </span>
                                    <span
                                        v-if="queryConfig.fields.length > 8"
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                                    >
                                        +{{
                                            queryConfig.fields.length - 8
                                        }}
                                        more
                                    </span>
                                </div>
                            </div>

                            <div v-if="queryConfig?.filters?.length">
                                <h5 class="text-sm font-medium text-gray-900">
                                    Filters ({{ queryConfig.filters.length }})
                                </h5>
                                <div class="space-y-1">
                                    <div
                                        v-for="filter in queryConfig.filters"
                                        :key="filter.field"
                                        class="text-xs bg-amber-50 p-2 rounded"
                                    >
                                        {{ filter.field }}
                                        {{ filter.operator }} {{ filter.value }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h5 class="text-sm font-medium text-gray-900">
                                    Target Type
                                </h5>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    {{
                                        formatTargetType(
                                            queryConfig?.target_type
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Generated SQL Preview -->
                        <div>
                            <h5 class="text-sm font-medium text-gray-900 mb-2">
                                Generated SQL
                            </h5>
                            <div
                                class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs font-mono overflow-x-auto"
                            >
                                <pre>{{
                                    generatedSQL ||
                                    "SQL will be generated during simulation..."
                                }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simulation Results -->
            <div
                v-if="simulationResults"
                class="bg-white border border-gray-200 rounded-lg"
            >
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-medium text-gray-900">
                            Import Simulation Results
                        </h4>
                        <div class="text-sm text-gray-600">
                            {{
                                simulationResults.estimated_records || 0
                            }}
                            records estimated
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ simulationResults.estimated_records || 0 }}
                            </div>
                            <div class="text-sm text-blue-700">
                                Records to Import
                            </div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600">
                                {{ simulationResults.mapped_fields || 0 }}
                            </div>
                            <div class="text-sm text-green-700">
                                Fields Mapped
                            </div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ queryConfig?.joins?.length || 0 }}
                            </div>
                            <div class="text-sm text-purple-700">
                                Table Joins
                            </div>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-orange-600">
                                {{ simulationResults.target_entities || 0 }}
                            </div>
                            <div class="text-sm text-orange-700">
                                Target Entities
                            </div>
                        </div>
                    </div>

                    <!-- Sample Import Data -->
                    <div>
                        <h5 class="text-base font-medium text-gray-900 mb-4">
                            Sample Import Data
                        </h5>
                        <p class="text-sm text-gray-600 mb-4">
                            Preview of how source data will be transformed and
                            imported into Service Vault:
                        </p>

                        <div
                            v-if="
                                simulationResults.sample_data &&
                                simulationResults.sample_data.length > 0
                            "
                            class="overflow-x-auto"
                        >
                            <table
                                class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg"
                            >
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Source Data
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            ⟶
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >
                                            Service Vault Entity
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="(
                                            record, index
                                        ) in simulationResults.sample_data.slice(
                                            0,
                                            5
                                        )"
                                        :key="index"
                                        class="hover:bg-gray-50"
                                    >
                                        <td class="px-4 py-4 text-sm">
                                            <div class="space-y-1">
                                                <div
                                                    v-for="(
                                                        value, field
                                                    ) in record.source"
                                                    :key="field"
                                                    class="text-xs"
                                                >
                                                    <span
                                                        class="font-mono text-gray-600"
                                                        >{{ field }}:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        formatCellValue(value)
                                                    }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <ArrowRightIcon
                                                class="h-5 w-5 text-gray-400"
                                            />
                                        </td>
                                        <td class="px-4 py-4 text-sm">
                                            <div class="space-y-1">
                                                <div
                                                    class="text-xs font-medium text-indigo-600"
                                                >
                                                    {{
                                                        formatTargetType(
                                                            queryConfig?.target_type
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    v-for="(
                                                        value, field
                                                    ) in record.target"
                                                    :key="field"
                                                    class="text-xs"
                                                >
                                                    <span
                                                        class="font-mono text-gray-600"
                                                        >{{ field }}:</span
                                                    >
                                                    <span
                                                        class="ml-2 font-medium"
                                                        >{{
                                                            formatCellValue(
                                                                value
                                                            )
                                                        }}</span
                                                    >
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-else
                            class="text-center py-12 bg-gray-50 rounded-lg"
                        >
                            <DocumentIcon
                                class="mx-auto h-12 w-12 text-gray-300"
                            />
                            <p class="mt-2 text-sm text-gray-500">
                                No sample data available
                            </p>
                        </div>
                    </div>

                    <!-- Validation Warnings -->
                    <div
                        v-if="
                            simulationResults.warnings &&
                            simulationResults.warnings.length > 0
                        "
                        class="space-y-3"
                    >
                        <h5 class="text-base font-medium text-gray-900">
                            Validation Warnings
                        </h5>
                        <div
                            v-for="warning in simulationResults.warnings"
                            :key="warning"
                            class="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
                        >
                            <div class="flex">
                                <ExclamationTriangleIcon
                                    class="h-5 w-5 text-yellow-400 mr-3 mt-0.5"
                                />
                                <p class="text-sm text-yellow-700">
                                    {{ warning }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simulation Error -->
            <div
                v-else-if="simulationError"
                class="bg-red-50 border border-red-200 rounded-lg p-4"
            >
                <div class="flex">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-red-800">
                            Simulation Failed
                        </h4>
                        <p class="mt-1 text-sm text-red-700">
                            {{ simulationError }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div
                class="flex items-center justify-between pt-4 border-t border-gray-200"
            >
                <div class="text-sm text-gray-500">
                    <p v-if="simulationResults">
                        Import simulation completed successfully
                    </p>
                    <p v-else-if="hasQueryConfiguration">
                        Run simulation to preview your import
                    </p>
                    <p v-else>Configure queries to enable import simulation</p>
                </div>

                <div class="flex space-x-3">
                    <button
                        @click="$emit('close')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Close
                    </button>
                    <button
                        v-if="simulationResults"
                        @click="executeImport"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <PlayIcon class="-ml-1 mr-2 h-4 w-4" />
                        Execute Import
                    </button>
                </div>
            </div>
        </div>
    </StackedDialog>
</template>

<script setup>
import { ref, computed } from "vue";
import StackedDialog from "@/Components/StackedDialog.vue";
import {
    CircleStackIcon,
    CogIcon,
    DocumentTextIcon,
    ServerIcon,
    EyeIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    PlayIcon,
    DocumentIcon,
    ArrowRightIcon,
} from "@heroicons/vue/24/outline";
import { useImportQueries } from "@/Composables/queries/useImportQueries.js";

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    profile: {
        type: Object,
        default: null,
    },
});

// Emits
const emit = defineEmits([
    "close",
    "execute-import",
    "open-query-builder",
    "open-template-selector",
]);

// Composables
const {
    previewImport,
    testConnection: testProfileConnection,
    executeImport: executeImportMutation,
} = useImportQueries();

// State
const isRunningSimulation = ref(false);
const simulationResults = ref(null);
const simulationError = ref(null);
const generatedSQL = ref(null);

// Computed
const queryConfig = computed(() => {
    return props.profile?.configuration || null;
});

const hasQueryConfiguration = computed(() => {
    return !!(
        queryConfig.value?.base_table && queryConfig.value?.fields?.length > 0
    );
});

const targetEntitiesCount = computed(() => {
    if (!simulationResults.value) return 0;
    return simulationResults.value.target_entities || 0;
});

// Methods
const runSimulation = async () => {
    if (!props.profile || !hasQueryConfiguration.value) return;

    isRunningSimulation.value = true;
    simulationError.value = null;
    simulationResults.value = null;
    generatedSQL.value = null;

    try {
        // Simulate the import by running the query and analyzing results
        const response = await fetch(
            `/api/import/profiles/${props.profile.id}/simulate`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    configuration: queryConfig.value,
                    limit: 10, // Get sample data for preview
                }),
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        simulationResults.value = data.results;
        generatedSQL.value = data.sql;
    } catch (error) {
        console.error("Simulation failed:", error);
        simulationError.value =
            error.message || "Failed to run import simulation";
    } finally {
        isRunningSimulation.value = false;
    }
};

const executeImport = async () => {
    if (!props.profile) return;

    try {
        await executeImportMutation(props.profile.id);
        emit("execute-import", props.profile);
        emit("close");
    } catch (error) {
        console.error("Import execution failed:", error);
        alert(`Import failed: ${error.message}`);
    }
};

const formatTargetType = (targetType) => {
    if (!targetType) return "Unknown";

    return targetType
        .replace(/_/g, " ")
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};

const formatCellValue = (value) => {
    if (value === null || value === undefined) {
        return "—";
    }

    if (typeof value === "string" && value.length > 50) {
        return value.substring(0, 50) + "...";
    }

    if (typeof value === "object") {
        return JSON.stringify(value).substring(0, 50) + "...";
    }

    return String(value);
};
</script>
