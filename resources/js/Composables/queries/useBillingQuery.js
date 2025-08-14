import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
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
    queryFn: () => billingApi.getBillingRates().then(res => res.data),
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