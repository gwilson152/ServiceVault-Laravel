import { QueryClient } from '@tanstack/vue-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Cache data for 5 minutes by default
      staleTime: 1000 * 60 * 5,
      // Keep data in cache for 10 minutes
      gcTime: 1000 * 60 * 10,
      // Retry failed requests 3 times
      retry: 3,
      // Retry delay function (exponential backoff)
      retryDelay: (attemptIndex) => Math.min(1000 * 2 ** attemptIndex, 30000),
      // Refetch on window focus for important data
      refetchOnWindowFocus: true,
      // Don't refetch on reconnect for most queries
      refetchOnReconnect: false,
      // Network mode - always try to fetch, even if offline
      networkMode: 'always',
    },
    mutations: {
      // Retry mutations on network errors
      retry: 1,
      // Network mode for mutations
      networkMode: 'online',
    },
  },
})

// Query key factory for consistent query keys across the app
export const queryKeys = {
  // Timer-related queries
  timers: {
    all: ['timers'],
    active: () => [...queryKeys.timers.all, 'active'],
    activeCurrent: () => [...queryKeys.timers.active(), 'current'],
    byId: (id) => [...queryKeys.timers.all, 'byId', id],
  },
  
  // Navigation-related queries
  navigation: {
    all: ['navigation'],
    items: (grouped = false) => [...queryKeys.navigation.all, 'items', { grouped }],
    breadcrumbs: (route) => [...queryKeys.navigation.all, 'breadcrumbs', route],
    access: (routes) => [...queryKeys.navigation.all, 'access', routes],
  },
  
  // Dashboard-related queries
  dashboard: {
    all: ['dashboard'],
    widgets: () => [...queryKeys.dashboard.all, 'widgets'],
    widgetData: (widgetKey) => [...queryKeys.dashboard.all, 'widgetData', widgetKey],
    meta: () => [...queryKeys.dashboard.all, 'meta'],
  },
  
  // User-related queries
  users: {
    all: ['users'],
    current: () => [...queryKeys.users.all, 'current'],
    byId: (id) => [...queryKeys.users.all, 'byId', id],
    list: (filters) => [...queryKeys.users.all, 'list', filters],
  },
  
  // Role and permission queries
  roles: {
    all: ['roles'],
    list: () => [...queryKeys.roles.all, 'list'],
    byId: (id) => [...queryKeys.roles.all, 'byId', id],
    permissions: (roleId) => [...queryKeys.roles.all, 'permissions', roleId],
  },
  
  // Ticket-related queries
  tickets: {
    all: ['tickets'],
    list: (filters) => [...queryKeys.tickets.all, 'list', filters],
    byId: (id) => [...queryKeys.tickets.all, 'byId', id],
    stats: () => [...queryKeys.tickets.all, 'stats'],
  },
  
  // Time entries
  timeEntries: {
    all: ['timeEntries'],
    list: (filters) => [...queryKeys.timeEntries.all, 'list', filters],
    byId: (id) => [...queryKeys.timeEntries.all, 'byId', id],
    stats: () => [...queryKeys.timeEntries.all, 'stats'],
  },
}

// Helper functions for query invalidation patterns
export const invalidateQueries = {
  // Invalidate all timer-related queries
  timers: () => queryClient.invalidateQueries({ queryKey: queryKeys.timers.all }),
  
  // Invalidate navigation queries (useful after permission changes)
  navigation: () => queryClient.invalidateQueries({ queryKey: queryKeys.navigation.all }),
  
  // Invalidate dashboard queries
  dashboard: () => queryClient.invalidateQueries({ queryKey: queryKeys.dashboard.all }),
  
  // Invalidate specific widget data
  widget: (widgetKey) => queryClient.invalidateQueries({ 
    queryKey: queryKeys.dashboard.widgetData(widgetKey) 
  }),
  
  // Invalidate user-related queries
  users: () => queryClient.invalidateQueries({ queryKey: queryKeys.users.all }),
  
  // Invalidate role-related queries
  roles: () => queryClient.invalidateQueries({ queryKey: queryKeys.roles.all }),
  
  // Invalidate ticket-related queries
  tickets: () => queryClient.invalidateQueries({ queryKey: queryKeys.tickets.all }),
  
  // Invalidate time entry queries
  timeEntries: () => queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.all }),
}

export default queryClient