import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import { usersApi, roleTemplatesApi, initializeCSRF } from '@/Services/api'
import { queryKeys } from '@/Services/queryClient'

// Initialize CSRF on module load
initializeCSRF()

export function useUsersQuery(params = {}) {
  return useQuery({
    queryKey: queryKeys.users.list(params),
    queryFn: () => usersApi.getAll(params).then(res => res.data),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

export function useUserQuery(id) {
  return useQuery({
    queryKey: computed(() => queryKeys.users.byId(unref(id))),
    queryFn: () => usersApi.getById(unref(id)).then(res => res.data),
    enabled: computed(() => !!unref(id)),
  })
}

export function useAssignableUsersQuery() {
  return useQuery({
    queryKey: ['users', 'assignable'],
    queryFn: () => usersApi.getAssignable().then(res => res.data),
    staleTime: 1000 * 60 * 10, // 10 minutes - assignable users don't change often
  })
}

export function useRoleTemplatesQuery(params = {}) {
  return useQuery({
    queryKey: queryKeys.roleTemplates.list(params),
    queryFn: () => roleTemplatesApi.getAll(params).then(res => res.data),
    staleTime: 1000 * 60 * 10, // 10 minutes - role templates don't change often
  })
}

export function useCreateUserMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (data) => usersApi.create(data),
    onSuccess: () => {
      // Invalidate and refetch users list
      queryClient.invalidateQueries({ queryKey: queryKeys.users.all })
      queryClient.invalidateQueries({ queryKey: ['users', 'assignable'] })
    },
    onError: (error) => {
      console.error('Failed to create user:', error)
    }
  })
}

export function useUpdateUserMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, data }) => usersApi.update(id, data),
    onSuccess: (_, { id }) => {
      // Invalidate affected queries
      queryClient.invalidateQueries({ queryKey: queryKeys.users.byId(id) })
      queryClient.invalidateQueries({ queryKey: queryKeys.users.all })
      queryClient.invalidateQueries({ queryKey: ['users', 'assignable'] })
    },
    onError: (error) => {
      console.error('Failed to update user:', error)
    }
  })
}

export function useDeleteUserMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: (id) => usersApi.delete(id),
    onSuccess: () => {
      // Invalidate and refetch users list
      queryClient.invalidateQueries({ queryKey: queryKeys.users.all })
      queryClient.invalidateQueries({ queryKey: ['users', 'assignable'] })
    },
    onError: (error) => {
      console.error('Failed to delete user:', error)
    }
  })
}

export function useToggleUserStatusMutation() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, user }) => {
      const updatedData = {
        ...user,
        is_active: !user.is_active
      }
      return usersApi.update(id, updatedData)
    },
    onSuccess: (_, { id }) => {
      // Invalidate affected queries
      queryClient.invalidateQueries({ queryKey: queryKeys.users.byId(id) })
      queryClient.invalidateQueries({ queryKey: queryKeys.users.all })
      queryClient.invalidateQueries({ queryKey: ['users', 'assignable'] })
    },
    onError: (error) => {
      console.error('Failed to toggle user status:', error)
    }
  })
}

export function useUserActivityQuery(userId) {
  return useQuery({
    queryKey: computed(() => ['users', 'activity', unref(userId)]),
    queryFn: () => usersApi.getActivity(unref(userId)).then(res => res.data),
    enabled: computed(() => !!unref(userId)),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

export function useUserTicketsQuery(userId) {
  return useQuery({
    queryKey: computed(() => ['users', 'tickets', unref(userId)]),
    queryFn: () => usersApi.getTickets(unref(userId)).then(res => res.data),
    enabled: computed(() => !!unref(userId)),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

export function useUserTimeEntriesQuery(userId) {
  return useQuery({
    queryKey: computed(() => ['users', 'timeEntries', unref(userId)]),
    queryFn: () => usersApi.getTimeEntries(unref(userId)).then(res => res.data),
    enabled: computed(() => !!unref(userId)),
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}