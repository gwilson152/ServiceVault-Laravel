import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import axios from 'axios'
import { queryKeys, invalidateQueries } from '@/Services/queryClient.js'

export function useTimersQuery() {
    const queryClient = useQueryClient()

    // Query for active timers - this replaces the manual API call in useTimerBroadcasting
    const { 
        data: activeTimersData, 
        isLoading: loadingActiveTimers, 
        error: activeTimersError,
        refetch: refetchActiveTimers
    } = useQuery({
        queryKey: queryKeys.timers.activeCurrent(),
        queryFn: async () => {
            const response = await axios.get('/api/timers/active/current')
            if (response.data.data) {
                // Normalize the data structure to handle both array and single timer responses
                const timers = Array.isArray(response.data.data) 
                    ? response.data.data.map(item => item.timer || item)
                    : [response.data.data.timer || response.data.data]
                return timers
            }
            return []
        },
        // Real-time data should be fresh
        staleTime: 1000 * 30,       // 30 seconds - timers need to be relatively fresh
        gcTime: 1000 * 60 * 5,      // 5 minutes cache time
        refetchOnWindowFocus: true,  // Refetch timers when user focuses window
        refetchInterval: 60000,      // Refetch every minute for running timers
        // Only refetch if we have running timers
        refetchIntervalInBackground: false,
    })

    // Computed property for active timers
    const activeTimers = computed(() => activeTimersData.value || [])

    // Query for all user timers (paginated)
    const useTimersListQuery = (options = {}) => {
        return useQuery({
            queryKey: queryKeys.timers.all,
            queryFn: async () => {
                const response = await axios.get('/api/timers', { params: options })
                return response.data
            },
            staleTime: 1000 * 60 * 2,  // 2 minutes
            enabled: false // Only load when explicitly requested
        })
    }

    // Query for specific timer by ID
    const useTimerQuery = (timerId) => {
        return useQuery({
            queryKey: queryKeys.timers.byId(timerId),
            queryFn: async () => {
                const response = await axios.get(`/api/timers/${timerId}`)
                return response.data.data
            },
            enabled: !!timerId,
            staleTime: 1000 * 60 * 1   // 1 minute for individual timers
        })
    }

    // Mutations for timer actions with optimistic updates
    const startTimerMutation = useMutation({
        mutationFn: async (timerData) => {
            const response = await axios.post('/api/timers', {
                ...timerData,
                stop_others: false // Support multiple concurrent timers
            })
            return response.data
        },
        onMutate: async (timerData) => {
            // Cancel any outgoing refetches to avoid optimistic update conflicts
            await queryClient.cancelQueries({ queryKey: queryKeys.timers.activeCurrent() })

            // Snapshot the previous value
            const previousTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []

            // Optimistically add the new timer
            const optimisticTimer = {
                id: `temp-${Date.now()}`,
                description: timerData.description || 'New Timer',
                status: 'running',
                started_at: new Date().toISOString(),
                duration: 0,
                total_paused_duration: 0,
                billing_rate: timerData.billing_rate || null,
                ...timerData
            }

            queryClient.setQueryData(
                queryKeys.timers.activeCurrent(), 
                [...previousTimers, optimisticTimer]
            )

            // Return a context object with the snapshotted value
            return { previousTimers, optimisticTimer }
        },
        onError: (err, timerData, context) => {
            // If the mutation fails, roll back to the previous value
            if (context?.previousTimers) {
                queryClient.setQueryData(queryKeys.timers.activeCurrent(), context.previousTimers)
            }
            console.error('Failed to start timer:', err)
        },
        onSuccess: (data, timerData, context) => {
            // Replace the optimistic timer with the real one from the server
            const previousTimers = context?.previousTimers || []
            const realTimer = data.data
            
            // Remove optimistic timer and add real timer
            const updatedTimers = previousTimers.concat(realTimer)
            queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
        },
        onSettled: () => {
            // Always refetch after error or success to sync with server
            queryClient.invalidateQueries({ queryKey: queryKeys.timers.activeCurrent() })
        }
    })

    const stopTimerMutation = useMutation({
        mutationFn: async (timerId) => {
            const response = await axios.post(`/api/timers/${timerId}/stop`)
            return response.data
        },
        onMutate: async (timerId) => {
            await queryClient.cancelQueries({ queryKey: queryKeys.timers.activeCurrent() })
            
            const previousTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
            
            // Optimistically remove the timer
            const updatedTimers = previousTimers.filter(timer => timer.id !== timerId)
            queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
            
            return { previousTimers }
        },
        onError: (err, timerId, context) => {
            if (context?.previousTimers) {
                queryClient.setQueryData(queryKeys.timers.activeCurrent(), context.previousTimers)
            }
            console.error('Failed to stop timer:', err)
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.timers.activeCurrent() })
        }
    })

    const pauseTimerMutation = useMutation({
        mutationFn: async (timerId) => {
            const response = await axios.post(`/api/timers/${timerId}/pause`)
            return response.data
        },
        onMutate: async (timerId) => {
            await queryClient.cancelQueries({ queryKey: queryKeys.timers.activeCurrent() })
            
            const previousTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
            
            // Optimistically update timer status
            const updatedTimers = previousTimers.map(timer => 
                timer.id === timerId ? { ...timer, status: 'paused' } : timer
            )
            queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
            
            return { previousTimers }
        },
        onError: (err, timerId, context) => {
            if (context?.previousTimers) {
                queryClient.setQueryData(queryKeys.timers.activeCurrent(), context.previousTimers)
            }
            console.error('Failed to pause timer:', err)
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.timers.activeCurrent() })
        }
    })

    const resumeTimerMutation = useMutation({
        mutationFn: async (timerId) => {
            const response = await axios.post(`/api/timers/${timerId}/resume`)
            return response.data
        },
        onMutate: async (timerId) => {
            await queryClient.cancelQueries({ queryKey: queryKeys.timers.activeCurrent() })
            
            const previousTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
            
            // Optimistically update timer status
            const updatedTimers = previousTimers.map(timer => 
                timer.id === timerId ? { ...timer, status: 'running' } : timer
            )
            queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
            
            return { previousTimers }
        },
        onError: (err, timerId, context) => {
            if (context?.previousTimers) {
                queryClient.setQueryData(queryKeys.timers.activeCurrent(), context.previousTimers)
            }
            console.error('Failed to resume timer:', err)
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.timers.activeCurrent() })
        }
    })

    const commitTimerMutation = useMutation({
        mutationFn: async ({ timerId, payload }) => {
            const response = await axios.post(`/api/timers/${timerId}/commit`, payload)
            return response.data
        },
        onMutate: async ({ timerId }) => {
            await queryClient.cancelQueries({ queryKey: queryKeys.timers.activeCurrent() })
            
            const previousTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
            
            // Optimistically remove the timer since it's now committed
            const updatedTimers = previousTimers.filter(timer => timer.id !== timerId)
            queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
            
            return { previousTimers }
        },
        onError: (err, { timerId }, context) => {
            if (context?.previousTimers) {
                queryClient.setQueryData(queryKeys.timers.activeCurrent(), context.previousTimers)
            }
            console.error('Failed to commit timer:', err)
        },
        onSettled: () => {
            // Invalidate both timers and time entries since we created a new time entry
            queryClient.invalidateQueries({ queryKey: queryKeys.timers.activeCurrent() })
            queryClient.invalidateQueries({ queryKey: queryKeys.timeEntries.all })
        }
    })

    const deleteTimerMutation = useMutation({
        mutationFn: async (timerId) => {
            const response = await axios.delete(`/api/timers/${timerId}?force=true`)
            return response.data
        },
        onMutate: async (timerId) => {
            await queryClient.cancelQueries({ queryKey: queryKeys.timers.activeCurrent() })
            
            const previousTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
            
            // Optimistically remove the timer
            const updatedTimers = previousTimers.filter(timer => timer.id !== timerId)
            queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
            
            return { previousTimers }
        },
        onError: (err, timerId, context) => {
            if (context?.previousTimers) {
                queryClient.setQueryData(queryKeys.timers.activeCurrent(), context.previousTimers)
            }
            console.error('Failed to delete timer:', err)
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.timers.activeCurrent() })
        }
    })

    // Sync timers function that uses query refetch
    const syncTimers = () => {
        return refetchActiveTimers()
    }

    // Helper function to update timer data from WebSocket events
    const addOrUpdateTimer = (timer) => {
        const currentTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
        const index = currentTimers.findIndex(t => t.id === timer.id)
        
        let updatedTimers
        if (index >= 0) {
            updatedTimers = [...currentTimers]
            updatedTimers[index] = timer
        } else {
            updatedTimers = [...currentTimers, timer]
        }
        
        queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
    }

    // Helper function to remove timer from cache
    const removeTimer = (timerId) => {
        const currentTimers = queryClient.getQueryData(queryKeys.timers.activeCurrent()) || []
        const updatedTimers = currentTimers.filter(t => t.id !== timerId)
        queryClient.setQueryData(queryKeys.timers.activeCurrent(), updatedTimers)
    }

    return {
        // Data
        activeTimers,
        loadingActiveTimers,
        activeTimersError,

        // Mutations
        startTimer: startTimerMutation.mutate,
        stopTimer: stopTimerMutation.mutate,
        pauseTimer: pauseTimerMutation.mutate,
        resumeTimer: resumeTimerMutation.mutate,
        commitTimer: commitTimerMutation.mutate,
        deleteTimer: deleteTimerMutation.mutate,

        // Query factories
        useTimersListQuery,
        useTimerQuery,

        // Utilities
        syncTimers,
        addOrUpdateTimer,
        removeTimer,
        refetchActiveTimers
    }
}