import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import { initializeCSRF } from '@/Services/api'
import { queryKeys } from '@/Services/queryClient'
import axios from 'axios'

// Initialize CSRF on module load
initializeCSRF()

// Billing Settings API
const billingApi = {
  getConfig: () => axios.get('/api/settings/billing-config').then(res => res.data),
  updateSettings: (data) => axios.put('/api/settings/billing-settings', data).then(res => res.data),
  
  // Billing Rates
  getBillingRates: () => axios.get('/api/billing-rates').then(res => res.data),
  createBillingRate: (data) => axios.post('/api/billing-rates', data).then(res => res.data),
  updateBillingRate: (id, data) => axios.put(`/api/billing-rates/${id}`, data).then(res => res.data),
  deleteBillingRate: (id) => axios.delete(`/api/billing-rates/${id}`).then(res => res.data),
  
  // Invoices & Billing Management
  getInvoices: () => axios.get('/api/billing/invoices').then(res => res.data),
  createInvoice: (data) => axios.post('/api/billing/invoices', data).then(res => res.data),
  getInvoice: (id) => axios.get(`/api/billing/invoices/${id}`).then(res => res.data),
  updateInvoice: (id, data) => axios.put(`/api/billing/invoices/${id}`, data).then(res => res.data),
  deleteInvoice: (id) => axios.delete(`/api/billing/invoices/${id}`).then(res => res.data),
  sendInvoice: (id) => axios.post(`/api/billing/invoices/${id}/send`).then(res => res.data),
  markInvoiceAsPaid: (id) => axios.post(`/api/billing/invoices/${id}/mark-paid`).then(res => res.data),
  
  // Billing Reports & Stats
  getDashboardStats: () => axios.get('/api/billing/reports/dashboard').then(res => res.data),
  getRevenueReport: (params = {}) => axios.get('/api/billing/reports/revenue', { params }).then(res => res.data),
  getOutstandingReport: () => axios.get('/api/billing/reports/outstanding').then(res => res.data),
  getPaymentsReport: (params = {}) => axios.get('/api/billing/reports/payments', { params }).then(res => res.data),
  
  // Billing Settings
  getBillingSettings: () => axios.get('/api/billing/billing-settings').then(res => res.data),
  createBillingSettings: (data) => axios.post('/api/billing/billing-settings', data).then(res => res.data),
  updateBillingSettings: (id, data) => axios.put(`/api/billing/billing-settings/${id}`, data).then(res => res.data),
  
  // Unbilled Items & Approval Workflow
  getUnbilledItems: (accountId, includeUnapproved = true) => 
    axios.get('/api/billing/unbilled-items', { 
      params: { 
        account_id: accountId, 
        include_unapproved: includeUnapproved 
      } 
    }).then(res => res.data),
    
  // Approval Actions
  approveTimeEntry: (id, notes) => axios.post(`/api/time-entries/${id}/approve`, { notes }).then(res => res.data),
  rejectTimeEntry: (id, notes) => axios.post(`/api/time-entries/${id}/reject`, { notes }).then(res => res.data),
  approveAddon: (id, notes) => axios.post(`/api/ticket-addons/${id}/approve`, { notes }).then(res => res.data),
  rejectAddon: (id, notes) => axios.post(`/api/ticket-addons/${id}/reject`, { notes }).then(res => res.data),
  
  // Bulk Approval Actions
  bulkApproveTimeEntries: (data) => axios.post('/api/time-entries/bulk/approve', data).then(res => res.data),
  bulkRejectTimeEntries: (data) => axios.post('/api/time-entries/bulk/reject', data).then(res => res.data),
  bulkApproveAddons: (data) => axios.post('/api/ticket-addons/bulk/approve', data).then(res => res.data),
  bulkRejectAddons: (data) => axios.post('/api/ticket-addons/bulk/reject', data).then(res => res.data),
}

// Billing Config Query
export function useBillingConfigQuery() {
  return useQuery({
    queryKey: queryKeys.billing.config,
    queryFn: () => billingApi.getConfig().then(res => res.data),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

// Addon Categories Query (from billing config)
export function useAddonCategoriesQuery() {
  return useQuery({
    queryKey: queryKeys.addonTemplates.categories,
    queryFn: () => billingApi.getConfig().then(res => res.data.addon_categories || {}),
    staleTime: 1000 * 60 * 10, // 10 minutes - categories don't change often
  })
}

// Billing Rates Query
export function useBillingRatesQuery() {
  return useQuery({
    queryKey: queryKeys.billing.rates,
    queryFn: async () => {
      const response = await billingApi.getBillingRates()
      return response.data // Extract the rates array from the response
    },
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

// Update Billing Settings Mutation
export function useUpdateBillingSettingsMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => billingApi.updateSettings(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.config })
    },
    onError: (error) => {
      console.error('Failed to update billing settings:', error)
    }
  })
}

// Create Billing Rate Mutation
export function useCreateBillingRateMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => billingApi.createBillingRate(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.rates })
    },
    onError: (error) => {
      console.error('Failed to create billing rate:', error)
    }
  })
}

// Update Billing Rate Mutation
export function useUpdateBillingRateMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, data }) => billingApi.updateBillingRate(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.rates })
    },
    onError: (error) => {
      console.error('Failed to update billing rate:', error)
    }
  })
}

// Delete Billing Rate Mutation
export function useDeleteBillingRateMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (id) => billingApi.deleteBillingRate(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.rates })
    },
    onError: (error) => {
      console.error('Failed to delete billing rate:', error)
    }
  })
}

// ====== NEW BILLING FUNCTIONALITY ======

// Dashboard Stats Query
export function useBillingDashboardStatsQuery() {
  return useQuery({
    queryKey: queryKeys.billing.dashboardStats,
    queryFn: () => billingApi.getDashboardStats().then(res => res.data),
    staleTime: 1000 * 60 * 2, // 2 minutes
  })
}

// Billing Settings Query
export function useBillingSettingsQuery() {
  return useQuery({
    queryKey: queryKeys.billing.settings,
    queryFn: () => billingApi.getBillingSettings().then(res => res.data),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

// Invoices Query
export function useInvoicesQuery() {
  return useQuery({
    queryKey: queryKeys.invoices.all,
    queryFn: () => billingApi.getInvoices().then(res => res.data),
    staleTime: 1000 * 60 * 2, // 2 minutes
  })
}

// Unbilled Items Query
export function useUnbilledItemsQuery(accountId, options = {}) {
  const { includeUnapproved = true, enabled = true } = options
  
  return useQuery({
    queryKey: ['billing', 'unbilled-items', computed(() => unref(accountId)), includeUnapproved],
    queryFn: () => {
      const id = unref(accountId)
      return billingApi.getUnbilledItems(id, includeUnapproved).then(res => res.data)
    },
    enabled: computed(() => enabled && !!unref(accountId)),
    staleTime: 1000 * 30, // 30 seconds - more frequent updates for approval workflow
  })
}

// Create Invoice Mutation
export function useCreateInvoiceMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => billingApi.createInvoice(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.invoices.all })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
      // Invalidate unbilled items for all accounts since items may have been invoiced
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
    },
    onError: (error) => {
      console.error('Failed to create invoice:', error)
    }
  })
}

// Approve Time Entry Mutation
export function useApproveTimeEntryMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, notes }) => billingApi.approveTimeEntry(id, notes),
    onSuccess: () => {
      // Invalidate time entries and unbilled items
      queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.list })
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
    },
    onError: (error) => {
      console.error('Failed to approve time entry:', error)
    }
  })
}

// Reject Time Entry Mutation
export function useRejectTimeEntryMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, notes }) => billingApi.rejectTimeEntry(id, notes),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.list })
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
    },
    onError: (error) => {
      console.error('Failed to reject time entry:', error)
    }
  })
}

// Approve Addon Mutation
export function useApproveAddonMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, notes }) => billingApi.approveAddon(id, notes),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
      // Also invalidate tickets queries as addons are related to tickets
      queryClient.invalidateQueries({ queryKey: queryKeys.tickets.list })
    },
    onError: (error) => {
      console.error('Failed to approve addon:', error)
    }
  })
}

// Reject Addon Mutation
export function useRejectAddonMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, notes }) => billingApi.rejectAddon(id, notes),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
      queryClient.invalidateQueries({ queryKey: queryKeys.tickets.list })
    },
    onError: (error) => {
      console.error('Failed to reject addon:', error)
    }
  })
}

// Bulk Approve Time Entries Mutation
export function useBulkApproveTimeEntriesMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => billingApi.bulkApproveTimeEntries(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.list })
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
    },
    onError: (error) => {
      console.error('Failed to bulk approve time entries:', error)
    }
  })
}

// Bulk Approve Addons Mutation
export function useBulkApproveAddonsMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => billingApi.bulkApproveAddons(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
      queryClient.invalidateQueries({ queryKey: queryKeys.tickets.list })
    },
    onError: (error) => {
      console.error('Failed to bulk approve addons:', error)
    }
  })
}

// Combined Bulk Approval Composable
export function useBulkApprovalMutations() {
  return {
    bulkApproveTimeEntries: useBulkApproveTimeEntriesMutation(),
    bulkApproveAddons: useBulkApproveAddonsMutation(),
    approveTimeEntry: useApproveTimeEntryMutation(),
    rejectTimeEntry: useRejectTimeEntryMutation(),
    approveAddon: useApproveAddonMutation(),
    rejectAddon: useRejectAddonMutation(),
  }
}

// Send Invoice Mutation
export function useSendInvoiceMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (invoiceId) => billingApi.sendInvoice(invoiceId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.invoices.all })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
    },
    onError: (error) => {
      console.error('Failed to send invoice:', error)
    }
  })
}

// Delete Invoice Mutation
export function useDeleteInvoiceMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (invoiceId) => billingApi.deleteInvoice(invoiceId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.invoices.all })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
      // Invalidate unbilled items as deleted invoice items become available again
      queryClient.invalidateQueries({ queryKey: ['billing', 'unbilled-items'] })
    },
    onError: (error) => {
      console.error('Failed to delete invoice:', error)
    }
  })
}

// Mark Invoice as Paid Mutation
export function useMarkInvoiceAsPaidMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (invoiceId) => billingApi.markInvoiceAsPaid(invoiceId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.invoices.all })
      queryClient.invalidateQueries({ queryKey: queryKeys.billing.dashboardStats })
    },
    onError: (error) => {
      console.error('Failed to mark invoice as paid:', error)
    }
  })
}

// Combined Invoice Actions Composable
export function useInvoiceActionsMutations() {
  return {
    sendInvoice: useSendInvoiceMutation(),
    deleteInvoice: useDeleteInvoiceMutation(),
    markAsPaid: useMarkInvoiceAsPaidMutation(),
  }
}