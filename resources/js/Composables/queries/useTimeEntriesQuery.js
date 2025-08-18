import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { computed } from "vue";
import axios from "axios";
import { queryKeys } from "@/Services/queryClient.js";

export function useTimeEntriesQuery() {
    const queryClient = useQueryClient();

    // Query for paginated time entries with filters
    const useTimeEntriesListQuery = (optionsRef) => {
        return useQuery({
            queryKey: computed(() =>
                queryKeys.timeEntries.list({
                    status: optionsRef.status,
                    billable: optionsRef.is_billable,
                    date_from: optionsRef.date_from,
                    date_to: optionsRef.date_to,
                    page: optionsRef.page || 1,
                    per_page: optionsRef.per_page || 20,
                })
            ),
            queryFn: async () => {
                const params = new URLSearchParams();
                if (optionsRef.status)
                    params.append("status", optionsRef.status);
                if (optionsRef.is_billable)
                    params.append("billable", optionsRef.is_billable);
                if (optionsRef.date_from)
                    params.append("date_from", optionsRef.date_from);
                if (optionsRef.date_to)
                    params.append("date_to", optionsRef.date_to);
                params.append("page", optionsRef.page || 1);
                params.append("per_page", optionsRef.per_page || 20);

                const response = await axios.get(
                    `/api/time-entries?${params.toString()}`
                );
                return response.data;
            },
            staleTime: 1000 * 60 * 2, // 2 minutes
            gcTime: 1000 * 60 * 10, // 10 minutes
            keepPreviousData: true, // Keep previous data while loading new page
        });
    };

    // Query for time entries statistics
    const {
        data: timeEntriesStatsData,
        isLoading: loadingStats,
        error: statsError,
        refetch: refetchStats,
    } = useQuery({
        queryKey: queryKeys.timeEntries.stats(),
        queryFn: async () => {
            const response = await axios.get("/api/time-entries/stats/recent");
            return response.data.data;
        },
        staleTime: 1000 * 60 * 5, // 5 minutes for stats
        gcTime: 1000 * 60 * 15, // 15 minutes
    });

    // Query for approval statistics (managers/admins only)
    const useApprovalStatsQuery = () => {
        return useQuery({
            queryKey: queryKeys.timeEntries.approvalStats(),
            queryFn: async () => {
                const response = await axios.get(
                    "/api/time-entries/stats/approvals"
                );
                return response.data.data;
            },
            staleTime: 1000 * 60 * 5, // 5 minutes
            enabled: false, // Only load when explicitly requested
        });
    };

    // Query for specific time entry by ID
    const useTimeEntryQuery = (timeEntryId) => {
        return useQuery({
            queryKey: queryKeys.timeEntries.byId(timeEntryId),
            queryFn: async () => {
                const response = await axios.get(
                    `/api/time-entries/${timeEntryId}`
                );
                return response.data.data;
            },
            enabled: !!timeEntryId,
            staleTime: 1000 * 60 * 5, // 5 minutes for individual entries
        });
    };

    // Query for time entries by ticket
    const useTicketTimeEntriesQuery = (ticketId) => {
        return useQuery({
            queryKey: queryKeys.timeEntries.byTicket(ticketId),
            queryFn: async () => {
                const response = await axios.get(
                    `/api/tickets/${ticketId}/time-entries`
                );
                return response.data.data;
            },
            enabled: !!ticketId,
            staleTime: 1000 * 60 * 2, // 2 minutes
        });
    };

    // Mutations for CRUD operations
    const createTimeEntryMutation = useMutation({
        mutationFn: async (timeEntryData) => {
            const response = await axios.post(
                "/api/time-entries",
                timeEntryData
            );
            return response.data;
        },
        onSuccess: (data) => {
            // Invalidate and refetch relevant queries
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });

            // If this was for a specific ticket, invalidate ticket time entries too
            if (data.data?.ticket_id) {
                queryClient.invalidateQueries({
                    queryKey: queryKeys.timeEntries.byTicket(
                        data.data.ticket_id
                    ),
                });
            }
        },
        onError: (error) => {
            console.error("Failed to create time entry:", error);
        },
    });

    const updateTimeEntryMutation = useMutation({
        mutationFn: async ({ id, ...timeEntryData }) => {
            const response = await axios.put(
                `/api/time-entries/${id}`,
                timeEntryData
            );
            return response.data;
        },
        onMutate: async ({ id, ...timeEntryData }) => {
            // Cancel any outgoing refetches
            await queryClient.cancelQueries({
                queryKey: queryKeys.timeEntries.byId(id),
            });

            // Snapshot the previous value
            const previousTimeEntry = queryClient.getQueryData(
                queryKeys.timeEntries.byId(id)
            );

            // Optimistically update the cache
            queryClient.setQueryData(queryKeys.timeEntries.byId(id), {
                ...previousTimeEntry,
                ...timeEntryData,
            });

            return { previousTimeEntry };
        },
        onError: (err, { id }, context) => {
            // Roll back on error
            if (context?.previousTimeEntry) {
                queryClient.setQueryData(
                    queryKeys.timeEntries.byId(id),
                    context.previousTimeEntry
                );
            }
            console.error("Failed to update time entry:", err);
        },
        onSettled: (data, error, { id }) => {
            // Refetch to ensure we have the latest data
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.byId(id),
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });
        },
    });

    const deleteTimeEntryMutation = useMutation({
        mutationFn: async (timeEntryId) => {
            const response = await axios.delete(
                `/api/time-entries/${timeEntryId}`
            );
            return response.data;
        },
        onSuccess: (data, timeEntryId) => {
            // Remove from cache and invalidate related queries
            queryClient.removeQueries({
                queryKey: queryKeys.timeEntries.byId(timeEntryId),
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });
        },
        onError: (error) => {
            console.error("Failed to delete time entry:", error);
        },
    });

    // Approval workflow mutations
    const approveTimeEntryMutation = useMutation({
        mutationFn: async (timeEntryId) => {
            const response = await axios.post(
                `/api/time-entries/${timeEntryId}/approve`
            );
            return response.data;
        },
        onMutate: async (timeEntryId) => {
            // Optimistically update the time entry status
            await queryClient.cancelQueries({
                queryKey: queryKeys.timeEntries.byId(timeEntryId),
            });

            const previousTimeEntry = queryClient.getQueryData(
                queryKeys.timeEntries.byId(timeEntryId)
            );

            queryClient.setQueryData(queryKeys.timeEntries.byId(timeEntryId), {
                ...previousTimeEntry,
                status: "approved",
                approved_at: new Date().toISOString(),
            });

            return { previousTimeEntry };
        },
        onError: (err, timeEntryId, context) => {
            if (context?.previousTimeEntry) {
                queryClient.setQueryData(
                    queryKeys.timeEntries.byId(timeEntryId),
                    context.previousTimeEntry
                );
            }
            console.error("Failed to approve time entry:", err);
        },
        onSuccess: () => {
            // Invalidate related queries
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.approvalStats(),
            });
        },
    });

    const rejectTimeEntryMutation = useMutation({
        mutationFn: async ({ timeEntryId, reason }) => {
            const response = await axios.post(
                `/api/time-entries/${timeEntryId}/reject`,
                { reason }
            );
            return response.data;
        },
        onMutate: async ({ timeEntryId }) => {
            // Optimistically update the time entry status
            await queryClient.cancelQueries({
                queryKey: queryKeys.timeEntries.byId(timeEntryId),
            });

            const previousTimeEntry = queryClient.getQueryData(
                queryKeys.timeEntries.byId(timeEntryId)
            );

            queryClient.setQueryData(queryKeys.timeEntries.byId(timeEntryId), {
                ...previousTimeEntry,
                status: "rejected",
            });

            return { previousTimeEntry };
        },
        onError: (err, { timeEntryId }, context) => {
            if (context?.previousTimeEntry) {
                queryClient.setQueryData(
                    queryKeys.timeEntries.byId(timeEntryId),
                    context.previousTimeEntry
                );
            }
            console.error("Failed to reject time entry:", err);
        },
        onSuccess: () => {
            // Invalidate related queries
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.approvalStats(),
            });
        },
    });

    const bulkApproveTimeEntriesMutation = useMutation({
        mutationFn: async ({ time_entry_ids, notes }) => {
            const response = await axios.post(
                "/api/time-entries/bulk/approve",
                {
                    time_entry_ids,
                    notes,
                }
            );
            return response.data;
        },
        onSuccess: () => {
            // Invalidate all time entry queries since we updated multiple entries
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.approvalStats(),
            });
        },
        onError: (error) => {
            console.error("Failed to bulk approve time entries:", error);
        },
    });

    const bulkRejectTimeEntriesMutation = useMutation({
        mutationFn: async ({ time_entry_ids, reason }) => {
            const response = await axios.post("/api/time-entries/bulk/reject", {
                time_entry_ids,
                reason,
            });
            return response.data;
        },
        onSuccess: () => {
            // Invalidate all time entry queries since we updated multiple entries
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.stats(),
            });
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.approvalStats(),
            });
        },
        onError: (error) => {
            console.error("Failed to bulk reject time entries:", error);
        },
    });

    // Computed properties
    const timeEntriesStats = computed(() => timeEntriesStatsData.value || {});

    return {
        // Query factories
        useTimeEntriesListQuery,
        useTimeEntryQuery,
        useTicketTimeEntriesQuery,
        useApprovalStatsQuery,

        // Stats data
        timeEntriesStats,
        loadingStats,
        statsError,
        refetchStats,

        // Mutations
        createTimeEntry: createTimeEntryMutation.mutate,
        updateTimeEntry: updateTimeEntryMutation.mutate,
        deleteTimeEntry: deleteTimeEntryMutation.mutate,
        approveTimeEntry: approveTimeEntryMutation.mutate,
        rejectTimeEntry: rejectTimeEntryMutation.mutate,
        bulkApproveTimeEntries: bulkApproveTimeEntriesMutation.mutate,
        bulkRejectTimeEntries: bulkRejectTimeEntriesMutation.mutate,

        // Mutation states
        isCreatingTimeEntry: createTimeEntryMutation.isPending,
        isUpdatingTimeEntry: updateTimeEntryMutation.isPending,
        isDeletingTimeEntry: deleteTimeEntryMutation.isPending,
        isApprovingTimeEntry: approveTimeEntryMutation.isPending,
        isRejectingTimeEntry: rejectTimeEntryMutation.isPending,
        isBulkApproving: bulkApproveTimeEntriesMutation.isPending,
        isBulkRejecting: bulkRejectTimeEntriesMutation.isPending,

        // Errors
        createTimeEntryError: createTimeEntryMutation.error,
        updateTimeEntryError: updateTimeEntryMutation.error,
        deleteTimeEntryError: deleteTimeEntryMutation.error,
    };
}
