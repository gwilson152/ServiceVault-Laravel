# TanStack Query Integration

Service Vault uses TanStack Query (formerly React Query) for Vue.js to provide optimized data fetching, caching, and state management across the frontend application.

## Overview

TanStack Query replaces traditional axios calls with intelligent query and mutation management, providing:

- **Automatic Caching**: Intelligent cache management with configurable stale times
- **Background Updates**: Automatic refetching of stale data in the background
- **Optimistic Updates**: Instant UI feedback with automatic rollback on errors
- **Request Deduplication**: Eliminates duplicate requests for the same data
- **Reactive Query Parameters**: Query keys that automatically trigger refetches when dependencies change

## Query Architecture

### Query Key Structure

```javascript
// /resources/js/Services/queryClient.js
export const queryKeys = {
  timeEntries: {
    all: ['time-entries'],
    list: (filters = {}) => [...queryKeys.timeEntries.all, 'list', filters],
    byId: (id) => [...queryKeys.timeEntries.all, 'detail', id],
    byTicket: (ticketId) => [...queryKeys.timeEntries.all, 'ticket', ticketId],
    stats: () => [...queryKeys.timeEntries.all, 'stats'],
    approvalStats: () => [...queryKeys.timeEntries.all, 'approval-stats']
  },
  timers: {
    all: ['timers'],
    active: () => [...queryKeys.timers.all, 'active'],
    byUser: (userId) => [...queryKeys.timers.all, 'user', userId]
  }
}
```

### Composable Pattern

Service Vault uses Vue composables to encapsulate TanStack Query logic:

```javascript
// /resources/js/Composables/queries/useTimeEntriesQuery.js
export function useTimeEntriesQuery() {
  const queryClient = useQueryClient()

  // Query factory for paginated time entries
  const useTimeEntriesListQuery = (optionsRef) => {
    return useQuery({
      queryKey: computed(() => queryKeys.timeEntries.list({
        status: optionsRef.status,
        billable: optionsRef.billable,
        date_from: optionsRef.date_from,
        date_to: optionsRef.date_to,
        page: optionsRef.page || 1,
        per_page: optionsRef.per_page || 20
      })),
      queryFn: async () => {
        const params = new URLSearchParams()
        // Build query parameters...
        const response = await axios.get(`/api/time-entries?${params.toString()}`)
        return response.data
      },
      staleTime: 1000 * 60 * 2,  // 2 minutes
      gcTime: 1000 * 60 * 10,    // 10 minutes
      keepPreviousData: true,     // Keep previous data while loading new page
    })
  }

  // Mutation with optimistic updates
  const approveTimeEntryMutation = useMutation({
    mutationFn: async (timeEntryId) => {
      const response = await axios.post(`/api/time-entries/${timeEntryId}/approve`)
      return response.data
    },
    onMutate: async (timeEntryId) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: queryKeys.timeEntries.byId(timeEntryId) })
      
      // Snapshot the previous value
      const previousTimeEntry = queryClient.getQueryData(queryKeys.timeEntries.byId(timeEntryId))
      
      // Optimistically update the cache
      queryClient.setQueryData(queryKeys.timeEntries.byId(timeEntryId), {
        ...previousTimeEntry,
        status: 'approved',
        approved_at: new Date().toISOString()
      })
      
      return { previousTimeEntry }
    },
    onError: (err, timeEntryId, context) => {
      // Roll back on error
      if (context?.previousTimeEntry) {
        queryClient.setQueryData(queryKeys.timeEntries.byId(timeEntryId), context.previousTimeEntry)
      }
      console.error('Failed to approve time entry:', err)
    },
    onSuccess: () => {
      // Invalidate related queries
      queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.all })
      queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.stats() })
    }
  })

  return {
    useTimeEntriesListQuery,
    approveTimeEntry: approveTimeEntryMutation.mutate,
    isApprovingTimeEntry: approveTimeEntryMutation.isPending
  }
}
```

## Integration Patterns

### Component Integration

```vue
<!-- /resources/js/Pages/TimeEntries/Index.vue -->
<script setup>
import { useTimeEntriesQuery } from '@/Composables/queries/useTimeEntriesQuery.js'

// Initialize query composable
const {
  useTimeEntriesListQuery,
  approveTimeEntry,
  isApprovingTimeEntry
} = useTimeEntriesQuery()

// Reactive query options
const queryOptions = reactive({
  status: '',
  billable: '',
  page: 1,
  per_page: 20
})

// Use TanStack Query for data fetching
const {
  data: timeEntriesData,
  isLoading: loading,
  error: timeEntriesError,
  refetch: refetchTimeEntries
} = useTimeEntriesListQuery(queryOptions)

// Computed for backward compatibility
const timeEntries = computed(() => timeEntriesData.value?.data || { data: [], total: 0 })

// Reactive query that updates when filters change
watch([queryOptions], () => {
  // Query automatically refetches when queryOptions change
}, { deep: true })
</script>
```

### Error Handling

```javascript
// Global error handling in query client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error) => {
        // Don't retry on 4xx errors
        if (error.response?.status >= 400 && error.response?.status < 500) {
          return false
        }
        return failureCount < 3
      },
      staleTime: 1000 * 60 * 5, // 5 minutes default
    },
    mutations: {
      retry: false, // Don't retry mutations by default
      onError: (error, variables, context) => {
        // Global mutation error handling
        console.error('Mutation error:', error)
        
        // Show user-friendly error messages
        if (error.response?.status === 422) {
          // Validation errors
        } else if (error.response?.status >= 500) {
          // Server errors
        }
      }
    }
  }
})
```

## Cache Management

### Invalidation Strategies

```javascript
// Smart cache invalidation based on relationships
const invalidateRelatedQueries = (timeEntryData) => {
  // Invalidate time entry lists
  queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.all })
  
  // If this was for a specific ticket, invalidate ticket time entries
  if (timeEntryData.ticket_id) {
    queryClient.invalidateQueries({ 
      queryKey: queryKeys.timeEntries.byTicket(timeEntryData.ticket_id) 
    })
  }
  
  // Invalidate stats
  queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.stats() })
}
```

### Background Updates

```javascript
// Configure background refetching
const useTimeEntriesListQuery = (optionsRef) => {
  return useQuery({
    // ... other options
    refetchOnWindowFocus: true,
    refetchOnReconnect: true,
    refetchIntervalInBackground: false,
    refetchInterval: 1000 * 60 * 5, // 5 minutes for active data
  })
}
```

## Performance Benefits

### Before (Axios)

```javascript
// Multiple API calls, no caching, manual loading states
const loading = ref(false)
const timeEntries = ref([])
const stats = ref(null)

const loadTimeEntries = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/time-entries')
    timeEntries.value = response.data.data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await axios.get('/api/time-entries/stats/recent')
    stats.value = response.data.data
  } catch (error) {
    console.error(error)
  }
}

// Manual cache management
const approveEntry = async (id) => {
  await axios.post(`/api/time-entries/${id}/approve`)
  // Manually reload both time entries and stats
  await loadTimeEntries()
  await loadStats()
}
```

### After (TanStack Query)

```javascript
// Automatic caching, optimistic updates, intelligent refetching
const {
  data: timeEntries,
  isLoading: loading,
  error
} = useTimeEntriesListQuery(queryOptions)

const {
  data: stats
} = useQuery({
  queryKey: queryKeys.timeEntries.stats(),
  queryFn: () => axios.get('/api/time-entries/stats/recent')
})

// Optimistic updates with automatic cache management
const { mutate: approveEntry } = useMutation({
  mutationFn: (id) => axios.post(`/api/time-entries/${id}/approve`),
  onMutate: async (id) => {
    // Instantly update UI
    const previousEntry = queryClient.getQueryData(['time-entries', id])
    queryClient.setQueryData(['time-entries', id], {
      ...previousEntry,
      status: 'approved'
    })
    return { previousEntry }
  },
  onSuccess: () => {
    // Automatically invalidate and refetch related data
    queryClient.invalidateQueries({ queryKey: ['time-entries'] })
    queryClient.invalidateQueries({ queryKey: ['stats'] })
  }
})
```

## Migration Benefits

1. **Reduced Bundle Size**: Eliminated redundant axios calls and manual cache management
2. **Improved UX**: Instant feedback with optimistic updates
3. **Better Performance**: Intelligent caching prevents unnecessary API calls
4. **Automatic Sync**: Background updates keep data fresh
5. **Error Resilience**: Built-in retry logic and error boundaries
6. **Developer Experience**: Declarative data fetching with loading and error states

## Current Implementation Status

- ✅ **Time Entries**: Full migration with optimistic updates
- ✅ **Timer Management**: Active timer queries with real-time updates
- ✅ **Statistics**: Dashboard stats with background refetching
- ✅ **Error Handling**: Global error boundaries and user feedback
- ✅ **Cache Invalidation**: Smart invalidation based on data relationships

For detailed implementation examples, see the respective feature documentation in `/docs/features/`.