import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { computed } from "vue";
import axios from "axios";
import { queryKeys } from "@/Services/queryClient.js";

export function useInvoiceQuery(invoiceId, options = {}) {
    const queryClient = useQueryClient();

    // Query for individual invoice with line items
    const {
        data: invoiceData,
        isLoading: loading,
        error,
        refetch: refetchInvoice,
    } = useQuery({
        queryKey: queryKeys.invoices.byId(invoiceId),
        queryFn: async () => {
            const response = await axios.get(`/api/billing/invoices/${invoiceId}`);
            return response.data.data;
        },
        enabled: !!invoiceId,
        staleTime: 1000 * 60 * 2, // 2 minutes
    });

    // Query for available items that can be added to invoice
    const {
        data: availableItemsData,
        isLoading: loadingAvailableItems,
        refetch: refetchAvailableItems,
    } = useQuery({
        queryKey: queryKeys.invoices.availableItems(invoiceId),
        queryFn: async () => {
            const response = await axios.get(`/api/billing/invoices/${invoiceId}/available-items`);
            return response.data.data;
        },
        enabled: !!invoiceId && (options.loadAvailableItems ?? false),
        staleTime: 1000 * 30, // 30 seconds
    });

    // Mutation to update invoice details
    const updateInvoiceMutation = useMutation({
        mutationFn: async (invoiceData) => {
            const response = await axios.put(
                `/api/billing/invoices/${invoiceId}`,
                invoiceData
            );
            return response.data;
        },
        onSuccess: (data) => {
            // Update cache with new data
            queryClient.setQueryData(queryKeys.invoices.byId(invoiceId), data.data);
            
            // Invalidate related queries
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to update invoice:", error);
        },
    });

    // Mutation to add line item
    const addLineItemMutation = useMutation({
        mutationFn: async (lineItemData) => {
            const response = await axios.post(
                `/api/billing/invoices/${invoiceId}/line-items`,
                lineItemData
            );
            return response.data;
        },
        onSuccess: () => {
            // Refetch invoice data to get updated line items and totals
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.byId(invoiceId),
            });
            
            // Refetch available items as some items may no longer be available
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.availableItems(invoiceId),
            });
            
            // Invalidate invoices list
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to add line item:", error);
        },
    });

    // Mutation to remove line item
    const removeLineItemMutation = useMutation({
        mutationFn: async (lineItemId) => {
            const response = await axios.delete(
                `/api/billing/invoices/${invoiceId}/line-items/${lineItemId}`
            );
            return response.data;
        },
        onSuccess: () => {
            // Refetch invoice data to get updated line items and totals
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.byId(invoiceId),
            });
            
            // Refetch available items as the removed item may be available again
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.availableItems(invoiceId),
            });
            
            // Invalidate invoices list
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to remove line item:", error);
        },
    });

    // Mutation to update line item (custom items only)
    const updateLineItemMutation = useMutation({
        mutationFn: async ({ lineItemId, ...updateData }) => {
            const response = await axios.put(
                `/api/billing/invoices/${invoiceId}/line-items/${lineItemId}`,
                updateData
            );
            return response.data;
        },
        onSuccess: () => {
            // Refetch invoice data to get updated totals
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.byId(invoiceId),
            });
            
            // Invalidate invoices list
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to update line item:", error);
        },
    });

    // Mutation to send invoice
    const sendInvoiceMutation = useMutation({
        mutationFn: async () => {
            const response = await axios.post(
                `/api/billing/invoices/${invoiceId}/send`
            );
            return response.data;
        },
        onSuccess: (data) => {
            // Update cache with new status
            queryClient.setQueryData(queryKeys.invoices.byId(invoiceId), data.data);
            
            // Invalidate invoices list
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to send invoice:", error);
        },
    });

    // Mutation to mark invoice as paid
    const markAsPaidMutation = useMutation({
        mutationFn: async () => {
            const response = await axios.post(
                `/api/billing/invoices/${invoiceId}/mark-paid`
            );
            return response.data;
        },
        onSuccess: (data) => {
            // Update cache with new status
            queryClient.setQueryData(queryKeys.invoices.byId(invoiceId), data.data);
            
            // Invalidate invoices list
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to mark invoice as paid:", error);
        },
    });

    // Mutation to delete invoice
    const deleteInvoiceMutation = useMutation({
        mutationFn: async () => {
            const response = await axios.delete(
                `/api/billing/invoices/${invoiceId}`
            );
            return response.data;
        },
        onSuccess: () => {
            // Remove from cache
            queryClient.removeQueries({
                queryKey: queryKeys.invoices.byId(invoiceId),
            });
            
            // Invalidate invoices list
            queryClient.invalidateQueries({
                queryKey: queryKeys.invoices.all,
            });
        },
        onError: (error) => {
            console.error("Failed to delete invoice:", error);
        },
    });

    // Computed properties
    const invoice = computed(() => invoiceData.value || null);
    const availableItems = computed(() => availableItemsData.value || { time_entries: [], ticket_addons: [] });

    return {
        // Data
        invoice,
        availableItems,
        
        // Loading states
        loading,
        loadingAvailableItems,
        
        // Error state
        error,
        
        // Refetch functions
        refetchInvoice,
        refetchAvailableItems,
        
        // Mutation functions
        updateInvoice: updateInvoiceMutation.mutate,
        addLineItem: addLineItemMutation.mutate,
        removeLineItem: removeLineItemMutation.mutate,
        updateLineItem: updateLineItemMutation.mutate,
        sendInvoice: sendInvoiceMutation.mutate,
        markAsPaid: markAsPaidMutation.mutate,
        deleteInvoice: deleteInvoiceMutation.mutate,
        
        // Mutation loading states
        isUpdating: updateInvoiceMutation.isPending,
        isAddingLineItem: addLineItemMutation.isPending,
        isRemovingLineItem: removeLineItemMutation.isPending,
        isUpdatingLineItem: updateLineItemMutation.isPending,
        isSending: sendInvoiceMutation.isPending,
        isMarkingPaid: markAsPaidMutation.isPending,
        isDeleting: deleteInvoiceMutation.isPending,
        
        // Mutation errors
        updateError: updateInvoiceMutation.error,
        addLineItemError: addLineItemMutation.error,
        removeLineItemError: removeLineItemMutation.error,
        updateLineItemError: updateLineItemMutation.error,
        sendError: sendInvoiceMutation.error,
        markAsPaidError: markAsPaidMutation.error,
        deleteError: deleteInvoiceMutation.error,
    };
}

// Factory function for invoice lists query (can be used in other components)
export function useInvoicesListQuery(filtersRef) {
    return useQuery({
        queryKey: computed(() => 
            queryKeys.invoices.list({
                status: filtersRef?.status,
                account_id: filtersRef?.account_id,
                date_from: filtersRef?.date_from,
                date_to: filtersRef?.date_to,
                page: filtersRef?.page || 1,
                per_page: filtersRef?.per_page || 20,
            })
        ),
        queryFn: async () => {
            const params = new URLSearchParams();
            if (filtersRef?.status) params.append("status", filtersRef.status);
            if (filtersRef?.account_id) params.append("account_id", filtersRef.account_id);
            if (filtersRef?.date_from) params.append("date_from", filtersRef.date_from);
            if (filtersRef?.date_to) params.append("date_to", filtersRef.date_to);
            params.append("page", filtersRef?.page || 1);
            params.append("per_page", filtersRef?.per_page || 20);

            const response = await axios.get(`/api/billing/invoices?${params.toString()}`);
            return response.data;
        },
        staleTime: 1000 * 60 * 2, // 2 minutes
        keepPreviousData: true,
    });
}