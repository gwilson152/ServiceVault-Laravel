import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { accountsApi, initializeCSRF } from '@/Services/api'
import { queryKeys } from '@/Services/queryClient'

// Initialize CSRF on module load
initializeCSRF()

export function useAccountsQuery(params = {}) {
  return useQuery({
    queryKey: [...queryKeys.accounts.list, params],
    queryFn: () => accountsApi.getAll(params).then(res => res.data),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

export function useAccountQuery(id) {
  return useQuery({
    queryKey: queryKeys.accounts.byId(id),
    queryFn: () => accountsApi.getById(id).then(res => res.data),
    enabled: !!id,
  })
}

export function useAccountSelectorQuery() {
  return useQuery({
    queryKey: queryKeys.accounts.selector,
    queryFn: () => accountsApi.getSelector().then(res => res.data),
    staleTime: 1000 * 60 * 10, // 10 minutes - hierarchical data doesn't change often
  })
}

export function useCreateAccountMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => accountsApi.create(data),
    onSuccess: () => {
      // Invalidate and refetch accounts list
      queryClient.invalidateQueries({ queryKey: queryKeys.accounts.all })
    },
    onError: (error) => {
      console.error('Failed to create account:', error)
    }
  })
}

export function useUpdateAccountMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, data }) => accountsApi.update(id, data),
    onSuccess: (_, { id }) => {
      // Invalidate affected queries
      queryClient.invalidateQueries({ queryKey: queryKeys.accounts.byId(id) })
      queryClient.invalidateQueries({ queryKey: queryKeys.accounts.list })
      queryClient.invalidateQueries({ queryKey: queryKeys.accounts.selector })
    },
    onError: (error) => {
      console.error('Failed to update account:', error)
    }
  })
}

export function useDeleteAccountMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (id) => accountsApi.delete(id),
    onSuccess: () => {
      // Invalidate and refetch accounts list
      queryClient.invalidateQueries({ queryKey: queryKeys.accounts.all })
    },
    onError: (error) => {
      console.error('Failed to delete account:', error)
    }
  })
}