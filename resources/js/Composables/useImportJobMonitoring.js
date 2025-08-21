import { ref, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useImportJobMonitoring() {
  const page = usePage()
  const user = page.props.auth?.user
  
  // State
  const activeJobs = ref(new Map())
  const jobStatuses = ref(new Map())
  const connectionStatus = ref('disconnected')
  const echo = ref(null)
  
  // WebSocket connection setup
  const setupWebSocketConnection = () => {
    if (typeof window !== 'undefined' && window.Echo) {
      echo.value = window.Echo
      connectionStatus.value = 'connected'
      
      // Subscribe to user-specific import channel
      if (user?.id) {
        echo.value.private(`user.${user.id}`)
          .listen('.import.progress.updated', handleProgressUpdate)
          .listen('.import.job.status.changed', handleStatusChange)
      }
      
      // Subscribe to global import events if user has permission
      if (user?.permissions?.includes('system.import') || user?.role?.name === 'admin') {
        echo.value.private('import.global')
          .listen('.import.progress.updated', handleProgressUpdate)
          .listen('.import.job.status.changed', handleStatusChange)
      }
    }
  }
  
  // Event handlers
  const handleProgressUpdate = (event) => {
    const { job_id, progress_percentage, current_operation, ...stats } = event
    
    // Update active jobs map
    if (activeJobs.value.has(job_id)) {
      const job = activeJobs.value.get(job_id)
      activeJobs.value.set(job_id, {
        ...job,
        progress_percentage,
        current_operation,
        ...stats,
        last_updated: new Date().toISOString()
      })
    }
    
    // Emit custom event for components to listen to
    document.dispatchEvent(new CustomEvent('import-progress-updated', {
      detail: { job_id, progress_percentage, current_operation, ...stats }
    }))
  }
  
  const handleStatusChange = (event) => {
    const { job_id, status, event_type, ...data } = event
    
    // Update job status
    jobStatuses.value.set(job_id, status)
    
    // Update active jobs if present
    if (activeJobs.value.has(job_id)) {
      const job = activeJobs.value.get(job_id)
      activeJobs.value.set(job_id, {
        ...job,
        status,
        ...data,
        last_updated: new Date().toISOString()
      })
      
      // Remove from active jobs if completed/failed
      if (['completed', 'failed', 'cancelled'].includes(status)) {
        setTimeout(() => {
          activeJobs.value.delete(job_id)
        }, 5000) // Keep for 5 seconds for user to see final state
      }
    }
    
    // Emit custom event
    document.dispatchEvent(new CustomEvent('import-status-changed', {
      detail: { job_id, status, event_type, ...data }
    }))
    
    // Show notification for important status changes
    showStatusNotification(job_id, status, event_type, data)
  }
  
  const showStatusNotification = (jobId, status, eventType, data) => {
    const notifications = {
      job_started: {
        title: 'Import Started',
        message: `Import job ${jobId.slice(0, 8)}... has started`,
        type: 'info'
      },
      job_completed: {
        title: 'Import Completed',
        message: `Import job completed successfully! ${data.records_imported || 0} records imported`,
        type: 'success'
      },
      job_failed: {
        title: 'Import Failed',
        message: `Import job failed: ${data.errors || 'Unknown error'}`,
        type: 'error'
      }
    }
    
    const notification = notifications[eventType]
    if (notification) {
      // Emit notification event that can be caught by notification components
      document.dispatchEvent(new CustomEvent('show-notification', {
        detail: notification
      }))
    }
  }
  
  // Job management methods
  const startMonitoringJob = (job) => {
    activeJobs.value.set(job.id, {
      ...job,
      monitored_since: new Date().toISOString()
    })
    
    // Subscribe to job-specific channel
    if (echo.value) {
      echo.value.private(`import.job.${job.id}`)
        .listen('.import.progress.updated', handleProgressUpdate)
        .listen('.import.job.status.changed', handleStatusChange)
    }
  }
  
  const stopMonitoringJob = (jobId) => {
    activeJobs.value.delete(jobId)
    jobStatuses.value.delete(jobId)
    
    // Leave job-specific channel
    if (echo.value) {
      echo.value.leave(`import.job.${jobId}`)
    }
  }
  
  const getJobProgress = (jobId) => {
    return activeJobs.value.get(jobId) || null
  }
  
  const getJobStatus = (jobId) => {
    return jobStatuses.value.get(jobId) || null
  }
  
  const getAllActiveJobs = () => {
    return Array.from(activeJobs.value.values())
  }
  
  const getActiveJobsCount = () => {
    return activeJobs.value.size
  }
  
  // API integration for initial job states
  const fetchJobStatus = async (jobId) => {
    try {
      const response = await fetch(`/api/import/jobs/${jobId}/status`, {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      if (response.ok) {
        const job = await response.json()
        
        if (job.status === 'running') {
          startMonitoringJob(job)
        }
        
        return job
      }
    } catch (error) {
      console.error('Failed to fetch job status:', error)
    }
    
    return null
  }
  
  const fetchActiveJobs = async () => {
    try {
      const response = await fetch('/api/import/jobs?status=running', {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      if (response.ok) {
        const data = await response.json()
        const jobs = data.data || data.jobs || []
        
        // Start monitoring all active jobs
        jobs.forEach(job => {
          if (job.status === 'running') {
            startMonitoringJob(job)
          }
        })
        
        return jobs
      }
    } catch (error) {
      console.error('Failed to fetch active jobs:', error)
    }
    
    return []
  }
  
  // Job control methods
  const cancelJob = async (jobId) => {
    try {
      const response = await fetch(`/api/import/jobs/${jobId}/cancel`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      if (response.ok) {
        const result = await response.json()
        
        // Update local state immediately
        if (activeJobs.value.has(jobId)) {
          const job = activeJobs.value.get(jobId)
          activeJobs.value.set(jobId, {
            ...job,
            status: 'cancelled',
            last_updated: new Date().toISOString()
          })
        }
        
        return result
      } else {
        throw new Error('Failed to cancel job')
      }
    } catch (error) {
      console.error('Failed to cancel job:', error)
      throw error
    }
  }
  
  // Cleanup
  const cleanup = () => {
    if (echo.value && user?.id) {
      echo.value.leave(`user.${user.id}`)
      
      if (user?.permissions?.includes('system.import') || user?.role?.name === 'admin') {
        echo.value.leave('import.global')
      }
      
      // Leave all job-specific channels
      activeJobs.value.forEach((job, jobId) => {
        echo.value.leave(`import.job.${jobId}`)
      })
    }
    
    activeJobs.value.clear()
    jobStatuses.value.clear()
    connectionStatus.value = 'disconnected'
  }
  
  // Lifecycle
  onMounted(() => {
    setupWebSocketConnection()
    fetchActiveJobs()
  })
  
  onUnmounted(() => {
    cleanup()
  })
  
  return {
    // State
    activeJobs,
    jobStatuses,
    connectionStatus,
    
    // Methods
    startMonitoringJob,
    stopMonitoringJob,
    getJobProgress,
    getJobStatus,
    getAllActiveJobs,
    getActiveJobsCount,
    fetchJobStatus,
    fetchActiveJobs,
    cancelJob,
    cleanup
  }
}