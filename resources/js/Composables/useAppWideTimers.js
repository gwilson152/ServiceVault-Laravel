import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useAppWideTimers() {
    const page = usePage()
    const userTimers = ref([])
    const allTimers = ref([]) // For admin users
    const connectionStatus = ref('disconnected')
    const lastSync = ref(null)
    const currentTime = ref(new Date())
    
    let userChannel = null
    let adminChannel = null
    let timeUpdateInterval = null
    
    const user = computed(() => page.props.auth?.user)
    
    const isAdmin = computed(() => {
        if (!user.value) return false
        
        // Super admins are always admin
        if (user.value.is_super_admin) return true
        
        // Check for admin permissions (not hardcoded)
        const adminPermissions = ['admin.read', 'admin.write', 'system.manage']
        return adminPermissions.some(permission => 
            user.value.permissions?.includes(permission)
        )
    })
    
    const canViewAllTimers = computed(() => {
        if (!user.value) return false
        
        // Super admins can always view all timers
        if (user.value.is_super_admin) return true
        
        // Check for specific permissions to view all timers
        const allTimerPermissions = [
            'admin.read',
            'timers.view.all',
            'timers.manage.team'
        ]
        
        return allTimerPermissions.some(permission => 
            user.value.permissions?.includes(permission)
        )
    })
    
    // Basic timer FAB visibility - most users should see this
    const canViewAppWideTimers = computed(() => {
        if (!user.value) return false
        
        // Basic permissions for timer usage - most users have these
        const basicTimerPermissions = [
            'time.track',
            'timers.create',
            'timers.manage.own'
        ]
        
        // Super admins always have access
        if (user.value.is_super_admin) return true
        
        // Check for basic timer permissions OR admin permissions
        const adminPermissions = [
            'admin.read',
            'timers.view.all',
            'timers.manage.team',
            'managers.oversight'
        ]
        
        const allPermissions = [...basicTimerPermissions, ...adminPermissions]
        
        return allPermissions.some(permission => 
            user.value.permissions?.includes(permission)
        )
    })
    
    // Admin-only timer oversight permissions
    const canViewAdminTimerOverlay = computed(() => {
        if (!user.value) return false
        
        // Super admins can always see admin overlay
        if (user.value.is_super_admin) return true
        
        // Users with specific admin/manager permissions
        const adminPermissions = [
            'admin.read',
            'timers.view.all',
            'timers.manage.team',
            'managers.oversight'
        ]
        
        return adminPermissions.some(permission => 
            user.value.permissions?.includes(permission)
        )
    })
    
    const connectToChannels = () => {
        if (!user.value || !window.Echo) {
            console.warn('Echo or user not available for timer broadcasting')
            return
        }
        
        try {
            // Always connect to user's own timer channel
            userChannel = window.Echo.private(`user.${user.value.id}.timers`)
            
            userChannel
                .listen('TimerStarted', (e) => {
                    console.log('User timer started:', e.timer)
                    addOrUpdateUserTimer(e.timer)
                })
                .listen('TimerStopped', (e) => {
                    console.log('User timer stopped:', e.timer)
                    removeUserTimer(e.timer.id)
                })
                .listen('TimerUpdated', (e) => {
                    console.log('User timer updated:', e.timer)
                    addOrUpdateUserTimer(e.timer)
                })
                .listen('TimerDeleted', (e) => {
                    console.log('User timer deleted:', e.timer)
                    removeUserTimer(e.timer.id)
                })
                .subscribed(() => {
                    console.log('Connected to user timer channel')
                    connectionStatus.value = 'connected'
                    syncUserTimers()
                })
                .error((error) => {
                    console.error('User timer channel error:', error)
                    connectionStatus.value = 'error'
                })
            
            // Connect to admin channel if user has permissions
            if (canViewAllTimers.value) {
                adminChannel = window.Echo.private('admin.timers')
                
                adminChannel
                    .listen('TimerStarted', (e) => {
                        console.log('Admin: Timer started:', e.timer)
                        addOrUpdateAllTimer(e.timer)
                    })
                    .listen('TimerStopped', (e) => {
                        console.log('Admin: Timer stopped:', e.timer)
                        removeAllTimer(e.timer.id)
                    })
                    .listen('TimerUpdated', (e) => {
                        console.log('Admin: Timer updated:', e.timer)
                        addOrUpdateAllTimer(e.timer)
                    })
                    .listen('TimerDeleted', (e) => {
                        console.log('Admin: Timer deleted:', e.timer)
                        removeAllTimer(e.timer.id)
                    })
                    .subscribed(() => {
                        console.log('Connected to admin timer channel')
                        syncAllTimers()
                    })
                    .error((error) => {
                        console.error('Admin timer channel error:', error)
                    })
            }
            
        } catch (error) {
            console.error('Failed to connect to broadcasting channels:', error)
            connectionStatus.value = 'error'
        }
    }
    
    const disconnectFromChannels = () => {
        if (userChannel) {
            window.Echo.leave(`user.${user.value.id}.timers`)
            userChannel = null
        }
        if (adminChannel) {
            window.Echo.leave('admin.timers')
            adminChannel = null
        }
        connectionStatus.value = 'disconnected'
    }
    
    const addOrUpdateUserTimer = (timer) => {
        if (timer.user_id !== user.value.id) return
        
        const index = userTimers.value.findIndex(t => t.id === timer.id)
        if (index >= 0) {
            userTimers.value[index] = timer
        } else {
            userTimers.value.push(timer)
        }
        lastSync.value = new Date()
    }
    
    const removeUserTimer = (timerId) => {
        const index = userTimers.value.findIndex(t => t.id === timerId)
        if (index >= 0) {
            userTimers.value.splice(index, 1)
        }
        lastSync.value = new Date()
    }
    
    const addOrUpdateAllTimer = (timer) => {
        const index = allTimers.value.findIndex(t => t.id === timer.id)
        if (index >= 0) {
            allTimers.value[index] = timer
        } else {
            allTimers.value.push(timer)
        }
        lastSync.value = new Date()
    }
    
    const removeAllTimer = (timerId) => {
        const index = allTimers.value.findIndex(t => t.id === timerId)
        if (index >= 0) {
            allTimers.value.splice(index, 1)
        }
        lastSync.value = new Date()
    }
    
    const syncUserTimers = async () => {
        if (!user.value) return
        
        try {
            const response = await window.axios.get('/api/timers/active/current')
            if (response.data.data) {
                userTimers.value = Array.isArray(response.data.data) 
                    ? response.data.data.map(item => item.timer || item)
                    : [response.data.data.timer || response.data.data]
            }
            lastSync.value = new Date()
        } catch (error) {
            console.error('Failed to sync user timers:', error)
        }
    }
    
    const syncAllTimers = async () => {
        if (!canViewAllTimers.value) return
        
        try {
            const response = await window.axios.get('/api/admin/timers/all-active')
            if (response.data.data) {
                allTimers.value = response.data.data.map(item => ({
                    ...item.timer,
                    user: item.user,
                    calculations: item.calculations
                }))
            }
            lastSync.value = new Date()
        } catch (error) {
            console.error('Failed to sync all timers:', error)
        }
    }
    
    // Timer management methods
    const startTimer = async (timerData) => {
        try {
            const response = await window.axios.post('/api/timers', {
                ...timerData,
                stop_others: false
            })
            return response.data
        } catch (error) {
            console.error('Failed to start timer:', error)
            throw error
        }
    }
    
    const stopTimer = async (timerId) => {
        try {
            const response = await window.axios.post(`/api/timers/${timerId}/stop`)
            return response.data
        } catch (error) {
            console.error('Failed to stop timer:', error)
            throw error
        }
    }
    
    const pauseTimer = async (timerId) => {
        try {
            const response = await window.axios.post(`/api/timers/${timerId}/pause`)
            return response.data
        } catch (error) {
            console.error('Failed to pause timer:', error)
            throw error
        }
    }
    
    const resumeTimer = async (timerId) => {
        try {
            const response = await window.axios.post(`/api/timers/${timerId}/resume`)
            return response.data
        } catch (error) {
            console.error('Failed to resume timer:', error)
            throw error
        }
    }
    
    // Admin timer management
    const adminPauseTimer = async (timerId) => {
        if (!canViewAllTimers.value) throw new Error('Insufficient permissions')
        
        try {
            const response = await window.axios.post(`/api/admin/timers/${timerId}/pause`)
            return response.data
        } catch (error) {
            console.error('Failed to admin pause timer:', error)
            throw error
        }
    }
    
    const adminResumeTimer = async (timerId) => {
        if (!canViewAllTimers.value) throw new Error('Insufficient permissions')
        
        try {
            const response = await window.axios.post(`/api/admin/timers/${timerId}/resume`)
            return response.data
        } catch (error) {
            console.error('Failed to admin resume timer:', error)
            throw error
        }
    }
    
    const adminStopTimer = async (timerId) => {
        if (!canViewAllTimers.value) throw new Error('Insufficient permissions')
        
        try {
            const response = await window.axios.post(`/api/admin/timers/${timerId}/stop`)
            return response.data
        } catch (error) {
            console.error('Failed to admin stop timer:', error)
            throw error
        }
    }
    
    // Computed properties for different timer views
    const activeUserTimers = computed(() => {
        return userTimers.value.filter(timer => 
            ['running', 'paused'].includes(timer.status)
        )
    })
    
    const activeAllTimers = computed(() => {
        if (!canViewAllTimers.value) return []
        return allTimers.value.filter(timer => 
            ['running', 'paused'].includes(timer.status)
        )
    })
    
    const teamTimers = computed(() => {
        // Filter to show only team member timers (not all users)
        return allTimers.value.filter(timer => {
            // This would need team membership logic
            return timer.user_id !== user.value.id
        })
    })
    
    // Calculate durations for timers
    const calculateDuration = (timer) => {
        if (timer.status !== 'running') return timer.duration || 0
        
        const startedAt = new Date(timer.started_at)
        const now = currentTime.value
        const totalPaused = timer.total_paused_duration || 0
        
        return Math.max(0, Math.floor((now - startedAt) / 1000) - totalPaused)
    }
    
    const calculateAmount = (timer) => {
        if (!timer.billing_rate) return 0
        
        const duration = calculateDuration(timer)
        const hours = duration / 3600
        return hours * timer.billing_rate.rate
    }
    
    // Lifecycle management
    onMounted(() => {
        // Set up time update interval
        timeUpdateInterval = setInterval(() => {
            currentTime.value = new Date()
        }, 1000)
        
        // Connect to channels after Echo is ready
        setTimeout(() => {
            connectToChannels()
            syncUserTimers()
            if (canViewAllTimers.value) {
                syncAllTimers()
            }
        }, 1000)
    })
    
    onUnmounted(() => {
        disconnectFromChannels()
        if (timeUpdateInterval) {
            clearInterval(timeUpdateInterval)
        }
    })
    
    return {
        // State
        userTimers,
        allTimers,
        connectionStatus,
        lastSync,
        currentTime,
        
        // Computed
        isAdmin,
        canViewAllTimers,
        canViewAppWideTimers,
        canViewAdminTimerOverlay,
        activeUserTimers,
        activeAllTimers,
        teamTimers,
        
        // Methods
        connectToChannels,
        disconnectFromChannels,
        syncUserTimers,
        syncAllTimers,
        startTimer,
        stopTimer,
        pauseTimer,
        resumeTimer,
        adminPauseTimer,
        adminResumeTimer,
        adminStopTimer,
        calculateDuration,
        calculateAmount,
        
        // Timer management
        addOrUpdateUserTimer,
        removeUserTimer,
        addOrUpdateAllTimer,
        removeAllTimer
    }
}