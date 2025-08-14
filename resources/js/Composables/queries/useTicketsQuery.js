import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { queryKeys, invalidateQueries } from "@/Services/queryClient";
import { computed, unref } from "vue";
import axios from "axios";

// Fetch tickets with filters
export function useTicketsQuery(filters = {}) {
    return useQuery({
        queryKey: computed(() => queryKeys.tickets.list(unref(filters))),
        queryFn: async () => {
            const params = new URLSearchParams();
            const currentFilters = unref(filters);

            // Add filter parameters
            if (currentFilters.search) params.append("search", currentFilters.search);
            if (currentFilters.status) params.append("status", currentFilters.status);
            if (currentFilters.priority) params.append("priority", currentFilters.priority);
            if (currentFilters.assignment)
                params.append("assignment", currentFilters.assignment);
            if (currentFilters.account_id)
                params.append("account_id", currentFilters.account_id);

            const response = await axios.get(
                `/api/tickets?${params.toString()}`
            );
            return response.data.data || [];
        },
        staleTime: 1000 * 60 * 2, // 2 minutes
        gcTime: 1000 * 60 * 10, // 10 minutes
    });
}

// Fetch single ticket by ID
export function useTicketQuery(ticketId) {
    return useQuery({
        queryKey: queryKeys.tickets.byId(unref(ticketId)),
        queryFn: async () => {
            const id = unref(ticketId);
            const response = await axios.get(`/api/tickets/${id}`);
            return response.data.data;
        },
        enabled: !!unref(ticketId),
        staleTime: 1000 * 60, // 1 minute
    });
}

// Create ticket mutation
export function useCreateTicketMutation() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (ticketData) => {
            const response = await axios.post("/api/tickets", ticketData);
            return response.data.data;
        },
        onSuccess: (newTicket) => {
            // Invalidate and refetch tickets list
            invalidateQueries.tickets();

            // Optimistically add the new ticket to existing cache
            queryClient.setQueriesData(
                { queryKey: queryKeys.tickets.all },
                (oldData) => {
                    if (Array.isArray(oldData)) {
                        return [newTicket, ...oldData];
                    }
                    return oldData;
                }
            );
        },
        onError: (error) => {
            console.error("Failed to create ticket:", error);
        },
    });
}

// Update ticket mutation
export function useUpdateTicketMutation() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async ({ ticketId, data }) => {
            const response = await axios.put(`/api/tickets/${ticketId}`, data);
            return response.data.data;
        },
        onSuccess: (updatedTicket) => {
            // Update the specific ticket in cache
            queryClient.setQueryData(
                queryKeys.tickets.byId(updatedTicket.id),
                updatedTicket
            );

            // Update the ticket in all lists
            queryClient.setQueriesData(
                { queryKey: queryKeys.tickets.all },
                (oldData) => {
                    if (Array.isArray(oldData)) {
                        return oldData.map((ticket) =>
                            ticket.id === updatedTicket.id
                                ? updatedTicket
                                : ticket
                        );
                    }
                    return oldData;
                }
            );
        },
        onError: (error) => {
            console.error("Failed to update ticket:", error);
        },
    });
}

// Delete ticket mutation
export function useDeleteTicketMutation() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (ticketId) => {
            await axios.delete(`/api/tickets/${ticketId}`);
            return ticketId;
        },
        onSuccess: (ticketId) => {
            // Remove from cache
            queryClient.removeQueries({
                queryKey: queryKeys.tickets.byId(ticketId),
            });

            // Remove from lists
            queryClient.setQueriesData(
                { queryKey: queryKeys.tickets.all },
                (oldData) => {
                    if (Array.isArray(oldData)) {
                        return oldData.filter(
                            (ticket) => ticket.id !== ticketId
                        );
                    }
                    return oldData;
                }
            );
        },
        onError: (error) => {
            console.error("Failed to delete ticket:", error);
        },
    });
}

// Ticket stats query
export function useTicketStatsQuery() {
    return useQuery({
        queryKey: queryKeys.tickets.stats(),
        queryFn: async () => {
            const response = await axios.get("/api/tickets/stats");
            return response.data.data;
        },
        staleTime: 1000 * 60 * 5, // 5 minutes
        gcTime: 1000 * 60 * 15, // 15 minutes
    });
}

// Ticket comments/messages query
export function useTicketCommentsQuery(ticketId) {
    return useQuery({
        queryKey: [...queryKeys.tickets.byId(unref(ticketId)), "comments"],
        queryFn: async () => {
            const id = unref(ticketId);
            const response = await axios.get(`/api/tickets/${id}/comments`);
            return response.data.data || [];
        },
        enabled: !!unref(ticketId),
        staleTime: 1000 * 30, // 30 seconds - comments should be relatively fresh
        gcTime: 1000 * 60 * 5, // 5 minutes
    });
}

// Ticket timers query
export function useTicketTimersQuery(ticketId) {
    return useQuery({
        queryKey: [...queryKeys.tickets.byId(unref(ticketId)), "timers"],
        queryFn: async () => {
            const id = unref(ticketId);
            const response = await axios.get(`/api/tickets/${id}/timers`);
            return response.data.data || [];
        },
        enabled: !!unref(ticketId),
        staleTime: 1000 * 30, // 30 seconds
        gcTime: 1000 * 60 * 5, // 5 minutes
    });
}

// Ticket time entries query
export function useTicketTimeEntriesQuery(ticketId) {
    return useQuery({
        queryKey: [...queryKeys.tickets.byId(unref(ticketId)), "time-entries"],
        queryFn: async () => {
            const id = unref(ticketId);
            const response = await axios.get(`/api/tickets/${id}/time-entries`);
            return response.data.data || [];
        },
        enabled: !!unref(ticketId),
        staleTime: 1000 * 60, // 1 minute
        gcTime: 1000 * 60 * 10, // 10 minutes
    });
}

// Ticket related tickets query
export function useTicketRelatedQuery(ticketId) {
    return useQuery({
        queryKey: [...queryKeys.tickets.byId(unref(ticketId)), "related"],
        queryFn: async () => {
            const id = unref(ticketId);
            const response = await axios.get(`/api/tickets/${id}/related`);
            return response.data.data || [];
        },
        enabled: !!unref(ticketId),
        staleTime: 1000 * 60 * 5, // 5 minutes
        gcTime: 1000 * 60 * 15, // 15 minutes
    });
}

// Add comment mutation
export function useAddCommentMutation() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async ({ ticketId, formData }) => {
            const response = await axios.post(
                `/api/tickets/${ticketId}/comments`,
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                }
            );
            return response.data.data;
        },
        onSuccess: (newComment, { ticketId }) => {
            // Invalidate comments to refetch
            queryClient.invalidateQueries({
                queryKey: [...queryKeys.tickets.byId(ticketId), "comments"],
            });

            // Also invalidate ticket to update activity/message counts
            queryClient.invalidateQueries({
                queryKey: queryKeys.tickets.byId(ticketId),
            });
        },
        onError: (error) => {
            console.error("Failed to add comment:", error);
        },
    });
}
